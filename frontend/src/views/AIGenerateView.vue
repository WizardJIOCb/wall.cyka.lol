<template>
  <div class="ai-generate-view">
    <!-- Job Status Display (if viewing existing job) -->
    <div v-if="currentJob && currentJob.status !== 'completed'" class="job-status-section">
      <div class="status-card">
        <div class="status-header">
          <h2>🤖 AI Generation in Progress</h2>
          <button @click="cancelJob" class="btn-danger-sm">Cancel</button>
        </div>
        
        <div class="progress-info">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: `${jobProgress}%` }"></div>
          </div>
          <p class="progress-text">{{ jobStatusText }}</p>
        </div>

        <div v-if="currentJob.queue_position > 0" class="queue-info">
          <span class="icon">⏳</span>
          <span>Position {{ currentJob.queue_position }} in queue</span>
        </div>

        <div class="job-details">
          <p><strong>Prompt:</strong> {{ currentJob.prompt }}</p>
          <p><strong>Status:</strong> {{ currentJob.status }}</p>
          <p><strong>Started:</strong> {{ formatDate(currentJob.created_at) }}</p>
        </div>
      </div>
    </div>

    <!-- Generation Result (if completed) -->
    <div v-else-if="currentJob && currentJob.status === 'completed'" class="result-section">
      <div class="result-header">
        <h2>✅ Generation Complete!</h2>
        <div class="result-actions">
          <button @click="downloadApp" class="btn-secondary">
            <span class="icon">📥</span>
            <span>Download</span>
          </button>
          <button @click="publishApp" class="btn-primary">
            <span class="icon">📤</span>
            <span>Publish</span>
          </button>
          <button @click="remixApp" class="btn-secondary">
            <span class="icon">🔄</span>
            <span>Remix</span>
          </button>
        </div>
      </div>

      <div class="app-preview">
        <iframe
          v-if="currentJob.result_url"
          :src="currentJob.result_url"
          class="preview-frame"
          sandbox="allow-scripts allow-forms"
        ></iframe>
        <div v-else class="preview-placeholder">
          <p>Preview not available</p>
        </div>
      </div>

      <div class="code-viewer">
        <div class="code-header">
          <h3>Generated Code</h3>
          <button @click="copyCode" class="btn-copy">Copy</button>
        </div>
        <pre class="code-content">{{ currentJob.generated_code || 'Code not available' }}</pre>
      </div>
    </div>

    <!-- New Generation Form -->
    <div v-else class="generation-form">
      <div class="form-header">
        <h1>{{ $t('navigation.aiGenerate') }}</h1>
        <p class="subtitle">Describe your web application and let AI build it for you</p>
      </div>

      <div class="prompt-section">
        <label class="form-label">
          <span>Prompt</span>
          <span class="char-count">{{ promptText.length }}/2000</span>
        </label>
        <textarea
          v-model="promptText"
          class="prompt-input"
          placeholder="Example: Create a simple todo list app with add, delete, and mark as complete functionality. Use a clean, modern design with blue colors."
          rows="6"
          maxlength="2000"
          @input="calculateCost"
        ></textarea>
        
        <div class="prompt-tips">
          <p><strong>Tips for better results:</strong></p>
          <ul>
            <li>Be specific about functionality and features</li>
            <li>Describe the design style and colors</li>
            <li>Mention any specific technologies if needed</li>
            <li>Keep it under 2000 characters</li>
          </ul>
        </div>
      </div>

      <div class="options-section">
        <div class="option-group">
          <label class="form-label">Model</label>
          <select v-model="selectedModel" class="form-select" @change="calculateCost">
            <option value="deepseek-coder">DeepSeek Coder (Recommended)</option>
            <option value="llama-coder">Llama Coder</option>
            <option value="codellama">CodeLlama</option>
          </select>
        </div>

        <div class="option-group">
          <label class="form-label">Priority</label>
          <select v-model="priority" class="form-select" @change="calculateCost">
            <option value="normal">Normal (Free)</option>
            <option value="high">High (+50 🧱)</option>
            <option value="urgent">Urgent (+150 🧱)</option>
          </select>
        </div>
      </div>

      <div class="cost-section">
        <div class="cost-display">
          <span class="cost-label">Estimated Cost:</span>
          <span class="cost-value">{{ estimatedCost }} 🧱</span>
        </div>
        <div class="balance-display">
          <span>Your Balance:</span>
          <span class="balance-value">{{ bricksBalance }} 🧱</span>
        </div>
        <p v-if="estimatedCost > bricksBalance" class="warning-text">
          ⚠️ Insufficient bricks. You need {{ estimatedCost - bricksBalance }} more bricks.
        </p>
      </div>

      <button
        @click="submitGeneration"
        :disabled="!canGenerate || generating"
        class="btn-generate"
      >
        {{ generating ? 'Submitting...' : 'Generate Application' }}
      </button>
    </div>

    <!-- Generation History -->
    <div class="history-section">
      <h2>Recent Generations</h2>
      
      <div v-if="loadingHistory" class="loading-container">
        <div class="spinner"></div>
      </div>

      <div v-else-if="history.length === 0" class="empty-state">
        <p>No generation history yet</p>
      </div>

      <div v-else class="history-grid">
        <div
          v-for="item in history"
          :key="item.job_id"
          class="history-card"
          @click="loadJob(item.job_id)"
        >
          <div class="history-status" :class="item.status">
            {{ getStatusIcon(item.status) }}
          </div>
          <div class="history-content">
            <p class="history-prompt">{{ truncate(item.prompt, 100) }}</p>
            <div class="history-meta">
              <span class="history-status-text">{{ item.status }}</span>
              <span class="history-date">{{ formatDate(item.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api/client'

const props = defineProps<{ jobId?: string }>()

const route = useRoute()
const router = useRouter()

const authStore = useAuthStore()
const promptText = ref('')
const selectedModel = ref('deepseek-coder')
const priority = ref('normal')
const estimatedCost = ref(100)
const bricksBalance = ref(authStore.user?.bricks_balance || 0)
const generating = ref(false)
const currentJob = ref<any>(null)
const history = ref<any[]>([])
const loadingHistory = ref(false)
let pollInterval: any = null

const canGenerate = computed(() => {
  return promptText.value.length >= 50 && estimatedCost.value <= bricksBalance.value
})

const jobProgress = computed(() => {
  if (!currentJob.value) return 0
  const statusProgress: Record<string, number> = {
    queued: 10,
    analyzing: 30,
    generating: 60,
    finalizing: 90,
    completed: 100,
    failed: 0
  }
  return statusProgress[currentJob.value.status] || 0
})

const jobStatusText = computed(() => {
  if (!currentJob.value) return ''
  const statusTexts: Record<string, string> = {
    queued: 'Waiting in queue...',
    analyzing: 'Analyzing your prompt...',
    generating: 'Generating code...',
    finalizing: 'Finalizing application...',
    completed: 'Generation complete!',
    failed: 'Generation failed'
  }
  return statusTexts[currentJob.value.status] || 'Processing...'
})

const calculateCost = () => {
  let baseCost = 100
  const charMultiplier = Math.ceil(promptText.value.length / 200)
  const priorityCosts = { normal: 0, high: 50, urgent: 150 }
  
  estimatedCost.value = baseCost + (charMultiplier * 10) + priorityCosts[priority.value as keyof typeof priorityCosts]
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleString()
}

const truncate = (text: string, length: number) => {
  return text.length > length ? text.substring(0, length) + '...' : text
}

const getStatusIcon = (status: string) => {
  const icons: Record<string, string> = {
    queued: '⏳',
    analyzing: '🔍',
    generating: '⚙️',
    finalizing: '🔧',
    completed: '✅',
    failed: '❌'
  }
  return icons[status] || '📝'
}

const loadBricksBalance = async () => {
  try {
    // Refresh user data to get latest balance
    const userResponse = await apiClient.get('/auth/me')
    if (userResponse.success && userResponse.data.user) {
      authStore.updateUser(userResponse.data.user)
      bricksBalance.value = userResponse.data.user.bricks_balance || 0
    }
  } catch (err) {
    console.error('Error loading bricks balance:', err)
  }
}

const loadHistory = async () => {
  try {
    loadingHistory.value = true
    const response = await apiClient.get('/ai/history?limit=10')
    if (response.data.success && response.data.data.jobs) {
      history.value = response.data.data.jobs
    }
  } catch (err) {
    console.error('Error loading history:', err)
  } finally {
    loadingHistory.value = false
  }
}

const submitGeneration = async () => {
  try {
    generating.value = true
    
    const response = await apiClient.post('/ai/generate', {
      prompt: promptText.value,
      model: selectedModel.value,
      priority: priority.value
    })

    console.log('AI Generate Response:', response)

    if (response.success && response.data?.job) {
      currentJob.value = response.data.job
      // Refresh balance after deduction
      await loadBricksBalance()
      
      // Redirect to user's wall to see the post with generation progress
      router.push('/wall/me')
    }
  } catch (err: any) {
    console.error('Generation error:', err)
    alert(err.response?.data?.message || 'Failed to start generation')
  } finally {
    generating.value = false
  }
}

const loadJob = async (jobId?: string) => {
  try {
    const id = jobId || props.jobId
    if (!id) return

    const response = await apiClient.get(`/ai/jobs/${id}`)
    if (response.data.success && response.data.data.job) {
      currentJob.value = response.data.data.job
      
      if (currentJob.value.status !== 'completed' && currentJob.value.status !== 'failed') {
        startPolling()
      }
    }
  } catch (err) {
    console.error('Error loading job:', err)
  }
}

const startPolling = () => {
  if (pollInterval) return
  
  pollInterval = setInterval(async () => {
    if (currentJob.value && (currentJob.value.status === 'completed' || currentJob.value.status === 'failed')) {
      stopPolling()
      return
    }
    
    await loadJob()
  }, 3000)
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

const cancelJob = async () => {
  if (!currentJob.value) return
  
  try {
    await apiClient.post(`/queue/jobs/${currentJob.value.job_id}/cancel`)
    currentJob.value.status = 'cancelled'
    stopPolling()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to cancel job')
  }
}

const downloadApp = () => {
  if (!currentJob.value?.generated_code) return
  
  const blob = new Blob([currentJob.value.generated_code], { type: 'text/html' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `ai-app-${currentJob.value.job_id}.html`
  a.click()
  URL.revokeObjectURL(url)
}

const publishApp = () => {
  console.log('Publish app functionality coming soon')
}

const remixApp = () => {
  if (!currentJob.value) return
  promptText.value = currentJob.value.prompt
  currentJob.value = null
}

const copyCode = () => {
  if (!currentJob.value?.generated_code) return
  
  navigator.clipboard.writeText(currentJob.value.generated_code)
  alert('Code copied to clipboard!')
}

onMounted(() => {
  loadBricksBalance()
  loadHistory()
  
  if (props.jobId) {
    loadJob(props.jobId)
  }
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.ai-generate-view {
  max-width: 1000px;
  margin: 0 auto;
  padding: var(--spacing-6);
}

.job-status-section,
.generation-form,
.result-section {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  margin-bottom: var(--spacing-6);
  box-shadow: var(--shadow-md);
}

.status-card {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.progress-bar {
  height: 8px;
  background: var(--color-bg-secondary);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--color-primary), #10b981);
  transition: width 0.5s ease;
}

.progress-text {
  text-align: center;
  color: var(--color-text-secondary);
  margin-top: var(--spacing-2);
}

.queue-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
}

.job-details {
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
}

.job-details p {
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.form-header {
  margin-bottom: var(--spacing-6);
}

.form-header h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.subtitle {
  color: var(--color-text-secondary);
}

.prompt-section {
  margin-bottom: var(--spacing-6);
}

.form-label {
  display: flex;
  justify-content: space-between;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.char-count {
  color: var(--color-text-secondary);
  font-weight: 400;
}

.prompt-input {
  width: 100%;
  padding: var(--spacing-4);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 1rem;
  font-family: inherit;
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
  resize: vertical;
}

.prompt-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.prompt-tips {
  margin-top: var(--spacing-3);
  padding: var(--spacing-3);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
}

.prompt-tips ul {
  margin-top: var(--spacing-2);
  padding-left: var(--spacing-5);
}

.prompt-tips li {
  margin-bottom: var(--spacing-1);
  color: var(--color-text-secondary);
}

.options-section {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.form-select {
  width: 100%;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
  cursor: pointer;
}

.cost-section {
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-6);
}

.cost-display,
.balance-display {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--spacing-2);
}

.cost-value,
.balance-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary);
}

.warning-text {
  color: #ef4444;
  margin-top: var(--spacing-2);
}

.btn-generate {
  width: 100%;
  padding: var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-size: 1.125rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-generate:hover:not(:disabled) {
  background: var(--color-primary-dark);
  transform: translateY(-2px);
}

.btn-generate:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
}

.result-actions {
  display: flex;
  gap: var(--spacing-3);
}

.app-preview {
  margin-bottom: var(--spacing-6);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.preview-frame {
  width: 100%;
  height: 600px;
  border: none;
}

.preview-placeholder {
  height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
}

.code-viewer {
  background: #1e1e1e;
  border-radius: var(--radius-md);
  overflow: hidden;
}

.code-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-3) var(--spacing-4);
  background: #2d2d2d;
}

.code-header h3 {
  color: white;
  font-size: 1rem;
}

.btn-copy {
  padding: var(--spacing-2) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
}

.code-content {
  padding: var(--spacing-4);
  color: #d4d4d4;
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
  overflow-x: auto;
  max-height: 400px;
}

.history-section {
  margin-top: var(--spacing-8);
}

.history-section h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: var(--spacing-4);
  color: var(--color-text-primary);
}

.history-grid {
  display: grid;
  gap: var(--spacing-4);
}

.history-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: var(--spacing-4);
  padding: var(--spacing-4);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s;
}

.history-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.history-status {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 1.5rem;
  background: var(--color-bg-secondary);
}

.history-prompt {
  font-weight: 600;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.history-meta {
  display: flex;
  gap: var(--spacing-4);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.btn-primary,
.btn-secondary,
.btn-danger-sm {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-4);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-secondary {
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
}

.btn-danger-sm {
  background: #ef4444;
  color: white;
  padding: var(--spacing-2) var(--spacing-3);
  font-size: 0.875rem;
}

.loading-container {
  text-align: center;
  padding: var(--spacing-8);
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto;
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: var(--spacing-8);
  color: var(--color-text-secondary);
}

@media (max-width: 768px) {
  .options-section {
    grid-template-columns: 1fr;
  }
  
  .result-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-4);
  }
  
  .result-actions {
    width: 100%;
    flex-direction: column;
  }
}
</style>
