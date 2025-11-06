<template>
  <div class="ai-generation-progress">
    <div class="progress-header">
      <div class="status-icon-container">
        <div v-if="status === 'processing'" class="spinner"></div>
        <span v-else class="status-icon" :class="statusClass">{{ statusIcon }}</span>
      </div>
      <div class="status-info">
        <h3>{{ statusTitle }}</h3>
        <p class="status-subtitle">{{ statusSubtitle }}</p>
        <p v-if="props.modelName" class="model-name">
          <span class="model-icon">🤖</span>
          <span class="model-text">{{ props.modelName }}</span>
        </p>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar-container">
      <div class="progress-bar" :class="{ 'indeterminate': progress === 0 && isGenerating }">
        <div 
          v-if="progress > 0"
          class="progress-fill" 
          :class="{ 'progress-pulsing': isGenerating }"
          :style="{ width: progress + '%' }"
        >
          <span class="progress-text" v-if="progress >= 8">{{ progress }}%</span>
        </div>
        <div v-else-if="isGenerating" class="progress-fill-indeterminate"></div>
      </div>
    </div>

    <!-- Real-time Stats -->
    <div v-if="isGenerating || status === 'completed'" class="stats-grid">
      <!-- Tokens Generated -->
      <div class="stat-card">
        <div class="stat-icon">🔢</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatNumber(currentTokens) }}</div>
          <div class="stat-label">{{ currentTokens === 0 && status === 'processing' ? 'Evaluating...' : 'Tokens Generated' }}</div>
        </div>
      </div>

      <!-- Generation Speed -->
      <div class="stat-card">
        <div class="stat-icon">⚡</div>
        <div class="stat-content">
          <div class="stat-value">{{ tokensPerSecond > 0 ? tokensPerSecond.toFixed(1) : '—' }}</div>
          <div class="stat-label">Tokens/sec</div>
        </div>
      </div>

      <!-- Elapsed Time -->
      <div class="stat-card">
        <div class="stat-icon">⏱️</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatTime(elapsedTime) }}</div>
          <div class="stat-label">Elapsed Time</div>
        </div>
      </div>

      <!-- Estimated Remaining -->
      <div class="stat-card" v-if="estimatedRemaining > 0 && isGenerating">
        <div class="stat-icon">⏳</div>
        <div class="stat-content">
          <div class="stat-value">{{ formatTime(estimatedRemaining) }}</div>
          <div class="stat-label">Est. Remaining</div>
        </div>
      </div>
    </div>

    <!-- Total Tokens (when completed) -->
    <div v-if="status === 'completed' && totalTokens > 0" class="completion-stats">
      <div class="completion-badge">
        <span class="badge-icon">✨</span>
        <span class="badge-text">Generation Complete!</span>
      </div>
      <div class="token-breakdown">
        <div class="token-item">
          <span class="token-label">Input Tokens:</span>
          <span class="token-value">{{ formatNumber(promptTokens) }}</span>
        </div>
        <div class="token-item">
          <span class="token-label">Output Tokens:</span>
          <span class="token-value">{{ formatNumber(completionTokens) }}</span>
        </div>
        <div class="token-item total">
          <span class="token-label">Total:</span>
          <span class="token-value">{{ formatNumber(totalTokens) }}</span>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="status === 'failed' && errorMessage" class="error-message">
      <span class="error-icon">⚠️</span>
      <span>{{ errorMessage }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  jobId: string
  autoStart?: boolean
  modelName?: string
}>()

const emit = defineEmits<{
  complete: [data: any]
  error: [error: string]
}>()

// State
const status = ref<'queued' | 'processing' | 'completed' | 'failed'>('queued')
const progress = ref(0)
const currentTokens = ref(0)
const tokensPerSecond = ref(0)
const elapsedTime = ref(0)
const estimatedRemaining = ref(0)
const promptTokens = ref(0)
const completionTokens = ref(0)
const totalTokens = ref(0)
const contentLength = ref(0)
const charsPerSecond = ref(0)
const errorMessage = ref('')

let eventSource: EventSource | null = null
let timerInterval: number | null = null
let clientStartTime = 0

// Computed
const isGenerating = computed(() => status.value === 'processing')

const statusIcon = computed(() => {
  switch (status.value) {
    case 'queued': return '⏳'
    case 'processing': return '🤖'
    case 'completed': return '✅'
    case 'failed': return '❌'
    default: return '❓'
  }
})

const statusClass = computed(() => {
  return `status-${status.value}`
})

const statusTitle = computed(() => {
  switch (status.value) {
    case 'queued': return 'Generation Queued'
    case 'processing': return 'AI is Generating...'
    case 'completed': return 'Generation Complete'
    case 'failed': return 'Generation Failed'
    default: return 'Unknown Status'
  }
})

const statusSubtitle = computed(() => {
  if (status.value === 'queued') {
    return 'Waiting in queue...'
  }
  if (status.value === 'processing') {
    if (contentLength.value === 0) {
      return 'Preparing... Evaluating prompt and loading model'
    }
    if (progress.value === 0 && contentLength.value > 0) {
      return 'Preparing generation...' 
    }
    if (tokensPerSecond.value > 0) {
      return `Generating at ${tokensPerSecond.value.toFixed(1)} tokens/sec (${Math.round(charsPerSecond.value)} chars/sec)`
    }
    return `Generating content... ${contentLength.value} characters`
  }
  return ''
})

// Methods
function formatNumber(num: number): string {
  return num.toLocaleString()
}

function formatTime(ms: number): string {
  const seconds = Math.floor(ms / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)

  if (hours > 0) {
    return `${hours}h ${minutes % 60}m ${seconds % 60}s`
  } else if (minutes > 0) {
    return `${minutes}m ${seconds % 60}s`
  } else {
    return `${seconds}s`
  }
}

function startTracking() {
  if (eventSource) return

  const apiUrl = import.meta.env.VITE_API_URL || ''
  const streamUrl = `${apiUrl}/api/v1/ai/generation/${props.jobId}/progress`

  console.log('[AIGenerationProgress] Starting SSE connection to:', streamUrl)

  clientStartTime = Date.now()

  // Start client-side timer for real-time elapsed time display
  timerInterval = window.setInterval(() => {
    if (status.value === 'processing') {
      elapsedTime.value = Date.now() - clientStartTime
    }
  }, 100) // Update every 100ms for smooth display

  // Connect to SSE stream
  eventSource = new EventSource(streamUrl)

  eventSource.addEventListener('progress', (event) => {
    console.log('[AIGenerationProgress] Received progress event:', event.data)
    const data = JSON.parse(event.data)
    
    status.value = data.status
    progress.value = data.progress || 0
    currentTokens.value = data.current_tokens || 0
    tokensPerSecond.value = data.tokens_per_second || 0
    estimatedRemaining.value = data.estimated_remaining || 0
    promptTokens.value = data.prompt_tokens || 0
    completionTokens.value = data.completion_tokens || 0
    totalTokens.value = data.total_tokens || 0
    contentLength.value = data.content_length || 0
    charsPerSecond.value = data.chars_per_second || 0

    // ONLY sync start time when first starting, don't override running timer
    if (data.elapsed_time && status.value === 'processing' && elapsedTime.value === 0) {
      const serverElapsed = data.elapsed_time
      clientStartTime = Date.now() - serverElapsed
      elapsedTime.value = serverElapsed
    }
  })

  eventSource.addEventListener('complete', (event) => {
    console.log('[AIGenerationProgress] Generation complete:', event.data)
    const data = JSON.parse(event.data)
    
    status.value = 'completed'
    progress.value = 100
    totalTokens.value = data.total_tokens || totalTokens.value
    
    if (data.elapsed_time) {
      elapsedTime.value = data.elapsed_time
    }

    stopTracking()
    emit('complete', data)
  })

  eventSource.addEventListener('error', (event) => {
    console.error('[AIGenerationProgress] SSE error event:', event)
    let data: any = {}
    try {
      data = JSON.parse((event as any).data)
    } catch (e) {
      data = { error: 'Connection error' }
    }

    status.value = 'failed'
    errorMessage.value = data.error || 'Unknown error occurred'
    
    stopTracking()
    emit('error', errorMessage.value)
  })

  eventSource.onerror = (error) => {
    console.error('[AIGenerationProgress] SSE connection error:', error)
    // Don't immediately fail - might be a temporary network issue
    // The server will timeout after 10 minutes if needed
  }
}

function stopTracking() {
  if (eventSource) {
    eventSource.close()
    eventSource = null
  }

  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

// Lifecycle
onMounted(() => {
  if (props.autoStart !== false) {
    startTracking()
  }
})

onUnmounted(() => {
  stopTracking()
})

// Expose methods for parent component
defineExpose({
  startTracking,
  stopTracking
})
</script>

<style scoped>
.ai-generation-progress {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-radius: 12px;
  padding: 1.5rem;
  margin: 1rem 0;
}

.progress-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.status-icon-container {
  flex-shrink: 0;
}

.spinner {
  width: 2.5rem;
  height: 2.5rem;
  border: 4px solid rgba(102, 126, 234, 0.2);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.status-icon {
  font-size: 2.5rem;
  display: inline-block;
  animation: pulse 2s ease-in-out infinite;
}

.status-icon.status-processing {
  animation: spin 2s linear infinite;
}

.status-icon.status-completed {
  animation: bounce 0.5s ease-in-out;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes bounce {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

.status-info h3 {
  margin: 0;
  font-size: 1.25rem;
  color: var(--color-text-primary, #1a1a1a);
}

.status-subtitle {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  color: var(--color-text-secondary, #666);
}

.model-name {
  margin: 0.5rem 0 0 0;
  font-size: 0.75rem;
  color: var(--color-primary, #667eea);
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 500;
}

.model-icon {
  font-size: 0.875rem;
}

.model-text {
  font-family: 'Courier New', monospace;
}

.progress-bar-container {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.progress-bar {
  flex: 1;
  height: 32px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: 16px;
  overflow: hidden;
  position: relative;
}

.progress-bar.indeterminate {
  background: linear-gradient(90deg, 
    rgba(0, 0, 0, 0.1) 0%, 
    rgba(102, 126, 234, 0.2) 50%, 
    rgba(0, 0, 0, 0.1) 100%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  transition: width 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.progress-fill-indeterminate {
  height: 100%;
  width: 40%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  animation: indeterminateProgress 1.5s ease-in-out infinite;
  box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
  position: absolute;
  left: 0;
}

@keyframes indeterminateProgress {
  0% {
    left: -40%;
    opacity: 0.8;
  }
  50% {
    opacity: 1;
  }
  100% {
    left: 100%;
    opacity: 0.8;
  }
}

.progress-pulsing {
  animation: progressPulse 1.5s ease-in-out infinite;
}

@keyframes progressPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.progress-text {
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.progress-label {
  font-weight: 600;
  color: var(--color-text-primary, #1a1a1a);
  min-width: 45px;
  text-align: right;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.stat-icon {
  font-size: 1.75rem;
}

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary, #667eea);
  line-height: 1.2;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #666);
  margin-top: 0.25rem;
}

.completion-stats {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.completion-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  margin-bottom: 1rem;
}

.badge-icon {
  font-size: 1.25rem;
}

.token-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: white;
  border-radius: 8px;
  padding: 1rem;
}

.token-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.token-item.total {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 2px solid rgba(0, 0, 0, 0.1);
  font-weight: 700;
}

.token-label {
  color: var(--color-text-secondary, #666);
  font-size: 0.875rem;
}

.token-value {
  color: var(--color-primary, #667eea);
  font-weight: 600;
}

.token-item.total .token-value {
  font-size: 1.125rem;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: rgba(248, 113, 113, 0.1);
  border: 1px solid rgba(248, 113, 113, 0.3);
  border-radius: 8px;
  color: #dc2626;
  margin-top: 1rem;
}

.error-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .progress-bar-container {
    flex-direction: column;
    align-items: stretch;
  }
  
  .progress-label {
    text-align: center;
  }
}
</style>
