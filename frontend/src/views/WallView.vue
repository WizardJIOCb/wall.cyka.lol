<template>
  <div class="wall-view">
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading wall...</p>
    </div>

    <div v-else-if="error" class="error-container">
      <h2>Error Loading Wall</h2>
      <p>{{ error }}</p>
      <button @click="$router.push('/')" class="btn-primary">Go Home</button>
    </div>

    <div v-else-if="wall" class="wall-content">
      <!-- Wall Header -->
      <div class="wall-header">
        <div class="wall-banner" :style="bannerStyle"></div>
        <div class="wall-info">
          <div class="wall-avatar">
            <img :src="wall.owner_avatar || wall.avatar_url || '/assets/images/default-avatar.svg'" :alt="wall.owner_username || wall.display_name" />
          </div>
          <div class="wall-details">
            <h1 class="wall-title">{{ wall.display_name }}</h1>
            <p v-if="wall.owner_username" class="wall-owner">by @{{ wall.owner_username }}</p>
            <p v-if="wall.description" class="wall-description">{{ wall.description }}</p>
          </div>
          <div class="wall-stats">
            <div class="stat">
              <span class="stat-value">{{ wall.posts_count || 0 }}</span>
              <span class="stat-label">Posts</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ wall.subscribers_count || 0 }}</span>
              <span class="stat-label">Followers</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Posts Feed -->
      <div class="posts-section">
        <h2 class="section-title">Posts</h2>
        
        <div v-if="loadingPosts" class="loading-posts">
          <div class="spinner"></div>
          <p>Loading posts...</p>
        </div>

        <div v-else-if="posts.length === 0" class="empty-state">
          <p>No posts yet on this wall.</p>
          <button v-if="isOwnWall" @click="createPost" class="btn-primary">Create First Post</button>
        </div>

        <div v-else class="posts-grid">
          <div v-for="post in posts" :key="post.post_id" class="post-card" :class="{ 'ai-post': post.post_type === 'ai_app' }">
            <!-- AI Generation Real-time Progress (if AI post is generating) -->
            <AIGenerationProgress 
              v-if="post.post_type === 'ai_app' && (post.ai_status === 'queued' || post.ai_status === 'processing') && post.job_id"
              :jobId="post.job_id"
              :modelName="post.ai_model"
              :auto-start="true"
              @complete="handleGenerationComplete(post)"
              @error="handleGenerationError(post, $event)"
            />
            
            <!-- Regular Post Content -->
            <div class="post-header">
              <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="post-avatar" />
              <div class="post-meta">
                <span class="post-author">@{{ post.author_username }}</span>
                <span class="post-time">{{ formatDate(post.created_at) }}</span>
              </div>
            </div>
            <div class="post-content">
              <div v-if="post.content_html" v-html="post.content_html"></div>
              <p v-else>{{ post.content_text }}</p>
            </div>
            
            <!-- AI Generated Content Preview (if completed) -->
            <div v-if="post.post_type === 'ai_app' && post.ai_status === 'completed' && post.ai_content" class="ai-content-preview">
              <div class="ai-preview-header">
                <span class="preview-icon">✨</span>
                <h4>AI Response</h4>
              </div>
              <div class="ai-preview-prompt">
                <strong>Prompt:</strong> {{ truncateText(post.ai_content.user_prompt, 150) }}
              </div>
              <div v-if="post.ai_content.html_content" class="ai-preview-text">
                {{ truncateText(post.ai_content.html_content.replace(/<[^>]*>/g, ''), 200) }}
              </div>
              <button @click="openAIModal(post)" class="btn-open-ai">
                <span class="icon">👁️</span>
                <span>View Full Response</span>
              </button>
            </div>
            <div v-if="post.media_attachments && post.media_attachments.length > 0" class="post-media">
              <img v-for="media in post.media_attachments" :key="media.attachment_id" :src="media.file_url" :alt="media.file_name" />
            </div>
            <div class="post-actions">
              <button class="action-btn">
                <span class="icon">👍</span>
                <span>{{ post.reaction_count || 0 }}</span>
              </button>
              <button class="action-btn">
                <span class="icon">💬</span>
                <span>{{ post.comment_count || 0 }}</span>
              </button>
              <button class="action-btn">
                <span class="icon">👁</span>
                <span>{{ post.view_count || 0 }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Load More -->
        <div v-if="hasMorePosts && !loadingPosts" class="load-more">
          <button @click="loadMorePosts" class="btn-secondary">Load More</button>
        </div>
      </div>
    </div>
  </div>

  <!-- AI Content Modal -->
  <Teleport to="body">
    <div v-if="showAIModal" class="modal-overlay" @click.self="closeAIModal">
      <div class="modal-content ai-modal">
        <div class="modal-header">
          <h2>🤖 AI Response</h2>
          <button @click="closeAIModal" class="btn-close">×</button>
        </div>
        
        <div v-if="selectedAIPost" class="modal-body">
          <div class="ai-modal-section">
            <h3>📝 Prompt</h3>
            <div class="ai-prompt-full">
              {{ selectedAIPost.ai_content?.user_prompt || 'No prompt available' }}
            </div>
          </div>
          
          <div class="ai-modal-section ai-stats" v-if="selectedAIPost.ai_content">
            <h3>📊 Generation Stats</h3>
            <div class="stats-grid">
              <div class="stat-item">
                <span class="stat-label">Model:</span>
                <span class="stat-value">{{ selectedAIPost.ai_content.generation_model || 'Unknown' }}</span>
              </div>
              <div class="stat-item" v-if="selectedAIPost.ai_content.generation_time">
                <span class="stat-label">Time:</span>
                <span class="stat-value">{{ (selectedAIPost.ai_content.generation_time / 1000).toFixed(2) }}s</span>
              </div>
              <div class="stat-item" v-if="selectedAIPost.ai_content.input_tokens">
                <span class="stat-label">Input Tokens:</span>
                <span class="stat-value">{{ selectedAIPost.ai_content.input_tokens.toLocaleString() }}</span>
              </div>
              <div class="stat-item" v-if="selectedAIPost.ai_content.output_tokens">
                <span class="stat-label">Output Tokens:</span>
                <span class="stat-value">{{ selectedAIPost.ai_content.output_tokens.toLocaleString() }}</span>
              </div>
              <div class="stat-item" v-if="selectedAIPost.ai_content.total_tokens">
                <span class="stat-label">Total Tokens:</span>
                <span class="stat-value">{{ selectedAIPost.ai_content.total_tokens.toLocaleString() }}</span>
              </div>
            </div>
          </div>
          
          <div class="ai-modal-section" v-if="selectedAIPost.ai_content?.html_content">
            <div class="section-header">
              <h3>💬 Response</h3>
              <button @click="copyToClipboard(selectedAIPost.ai_content.html_content, 'Response')" class="btn-copy-small">📋 Copy</button>
            </div>
            <div class="ai-response-content" v-html="selectedAIPost.ai_content.html_content"></div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="closeAIModal" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api/client'
import AIGenerationProgress from '@/components/ai/AIGenerationProgress.vue'

const props = defineProps<{ wallId?: string }>()

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loading = ref(true)
const loadingPosts = ref(false)
const error = ref<string | null>(null)
const wall = ref<any>(null)
const posts = ref<any[]>([])
const page = ref(1)
const limit = 20
const hasMorePosts = ref(true)
let pollInterval: any = null

const isOwnWall = computed(() => {
  return wall.value && authStore.user && wall.value.user_id === authStore.user.id
})

const bannerStyle = computed(() => {
  if (wall.value?.theme) {
    return { backgroundColor: wall.value.theme }
  }
  return { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

const loadWall = async () => {
  try {
    loading.value = true
    error.value = null

    let wallIdToFetch = props.wallId || route.params.wallId

    // Check if we have a valid wallIdToFetch
    if (!wallIdToFetch) {
      throw new Error('No wall identifier provided')
    }

    let response;
    
    // Handle special case: /wall/me
    if (wallIdToFetch === 'me') {
      // Use the dedicated endpoint for current user's wall
      response = await apiClient.get('/walls/me')
    } else {
      // Fetch wall data by ID or slug
      response = await apiClient.get(`/walls/${wallIdToFetch}`)
    }

    if (response?.success && response?.data?.wall) {
      wall.value = response.data.wall
      await loadPosts()
    } else {
      throw new Error(response?.message || 'Wall not found')
    }
  } catch (err: any) {
    console.error('Error loading wall:', err)
    if (err.response?.status === 404) {
      error.value = 'The requested wall could not be found. It may not exist or you may not have permission to view it.'
    } else {
      error.value = err.response?.data?.message || err.message || 'Failed to load wall'
    }
  } finally {
    loading.value = false
  }
}

const loadPosts = async (isPolling = false) => {
  if (!wall.value) return
  
  try {
    // Don't show loading spinner when polling
    if (!isPolling) {
      loadingPosts.value = true
    }
    
    const offset = (page.value - 1) * limit
    const response = await apiClient.get(`/walls/${wall.value.wall_id}/posts?limit=${limit}&offset=${offset}`)
    
    if (response?.success && response?.data?.posts) {
      const newPosts = response.data.posts
      
      if (page.value === 1) {
        if (isPolling) {
          // Update existing posts instead of replacing the entire array
          updatePostsData(newPosts)
        } else {
          posts.value = newPosts
        }
      } else {
        posts.value = [...posts.value, ...newPosts]
      }
      
      hasMorePosts.value = newPosts.length === limit
      
      // Start polling if there are pending AI posts
      checkForPendingAIPosts()
    }
  } catch (err: any) {
    console.error('Error loading posts:', err)
  } finally {
    if (!isPolling) {
      loadingPosts.value = false
    }
  }
}

const updatePostsData = (newPosts: any[]) => {
  // Create a map of new posts by post_id for quick lookup
  const newPostsMap = new Map(newPosts.map(post => [post.post_id, post]))
  
  // Update existing posts with new data
  posts.value.forEach((post, index) => {
    const updatedPost = newPostsMap.get(post.post_id)
    if (updatedPost) {
      // Only update if AI status changed to avoid unnecessary re-renders
      if (post.ai_status !== updatedPost.ai_status) {
        posts.value[index] = { ...post, ...updatedPost }
      }
      newPostsMap.delete(post.post_id)
    }
  })
  
  // Add any new posts that weren't in the existing list
  if (newPostsMap.size > 0) {
    posts.value = [...Array.from(newPostsMap.values()), ...posts.value]
  }
}

const checkForPendingAIPosts = () => {
  const hasPendingAI = posts.value.some(
    post => post.post_type === 'ai_app' && 
           (post.ai_status === 'queued' || post.ai_status === 'processing')
  )
  
  if (hasPendingAI) {
    startPolling()
  } else {
    stopPolling()
  }
}

const startPolling = () => {
  if (pollInterval) return
  
  pollInterval = setInterval(async () => {
    await loadPosts(true) // Pass true to indicate polling
  }, 3000) // Poll every 3 seconds
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

const loadMorePosts = () => {
  page.value++
  loadPosts()
}

const createPost = () => {
  // Navigate to post creation (to be implemented)
  console.log('Create post functionality coming soon')
}

// Handle AI generation completion
const handleGenerationComplete = async (post: any) => {
  console.log('Generation completed for post:', post.post_id)
  // Reload posts to get the updated content
  await loadPosts()
  stopPolling()
}

// Handle AI generation error
const handleGenerationError = (post: any, errorMsg: string) => {
  console.error('Generation failed for post:', post.post_id, errorMsg)
  // Reload posts to reflect failed status
  loadPosts()
}

const getAIProgress = (status: string) => {
  const progressMap: Record<string, number> = {
    'queued': 10,
    'processing': 60,
    'completed': 100,
    'failed': 0
  }
  return progressMap[status] || 0
}

const getAIStatusText = (status: string) => {
  const statusTextMap: Record<string, string> = {
    'queued': 'Waiting in queue...',
    'processing': 'Generating your application...',
    'completed': 'Generation complete!',
    'failed': 'Generation failed'
  }
  return statusTextMap[status] || 'Processing...'
}

const truncateText = (text: string, maxLength: number) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const selectedAIPost = ref<any>(null)
const showAIModal = ref(false)

const openAIModal = async (post: any) => {
  try {
    // Fetch full AI application data
    const response: any = await apiClient.get(`/ai/apps/${post.ai_app_id}`)
    
    // Check if response has the expected structure
    if (response?.success && response?.data?.app) {
      selectedAIPost.value = {
        ...post,
        ai_content: response.data.app
      }
      showAIModal.value = true
      // Lock body scroll
      document.body.style.overflow = 'hidden'
    }
  } catch (err: any) {
    console.error('Error loading AI content:', err)
  }
}

const closeAIModal = () => {
  showAIModal.value = false
  selectedAIPost.value = null
  // Restore body scroll
  document.body.style.overflow = ''
}

const copyToClipboard = (text: string, label: string) => {
  navigator.clipboard.writeText(text).then(() => {
    // Successfully copied
    console.log(`${label} copied to clipboard`)
  }).catch(err => {
    console.error('Failed to copy:', err)
  })
}

const downloadAIApp = () => {
  if (!selectedAIPost.value?.ai_content) return
  
  const content = selectedAIPost.value.ai_content
  const scriptOpen = '<script>'
  const scriptClose = '<' + '/script>'
  const bodyClose = '<' + '/body>'
  const htmlClose = '<' + '/html>'
  
  const fullHTML = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Generated App</title>
  <style>
${content.css_content || ''}
  </style>
</head>
<body>
${content.html_content || ''}
  ${scriptOpen}
${content.js_content || ''}
  ${scriptClose}
${bodyClose}
${htmlClose}`
  
  const blob = new Blob([fullHTML], { type: 'text/html' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `ai-app-${selectedAIPost.value.post_id}.html`
  a.click()
  URL.revokeObjectURL(url)
}

onMounted(() => {
  loadWall()
})

onUnmounted(() => {
  stopPolling()
  // Restore body scroll in case modal was open
  document.body.style.overflow = ''
})
</script>

<style scoped>
.wall-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-4);
}

.loading-container,
.error-container {
  text-align: center;
  padding: var(--spacing-8);
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto var(--spacing-4);
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.wall-header {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: var(--spacing-6);
  box-shadow: var(--shadow-md);
}

.wall-banner {
  height: 200px;
  width: 100%;
}

.wall-info {
  padding: var(--spacing-6);
  position: relative;
}

.wall-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  border: 4px solid var(--color-bg-elevated);
  margin-top: -60px;
  margin-bottom: var(--spacing-4);
}

.wall-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.wall-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.wall-owner {
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-2);
}

.wall-description {
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-4);
}

.wall-stats {
  display: flex;
  gap: var(--spacing-6);
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.posts-section {
  margin-top: var(--spacing-6);
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: var(--spacing-4);
  color: var(--color-text-primary);
}

.loading-posts {
  text-align: center;
  padding: var(--spacing-8);
}

.empty-state {
  text-align: center;
  padding: var(--spacing-8);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
}

.posts-grid {
  display: grid;
  gap: var(--spacing-4);
}

.post-card {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  transition: box-shadow 0.2s;
}

.post-card:hover {
  box-shadow: var(--shadow-md);
}

.post-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  margin-bottom: var(--spacing-3);
}

.post-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.post-meta {
  display: flex;
  flex-direction: column;
}

.post-author {
  font-weight: 600;
  color: var(--color-text-primary);
}

.post-time {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.post-content {
  margin-bottom: var(--spacing-3);
  line-height: 1.6;
  color: var(--color-text-primary);
}

.post-media {
  margin-bottom: var(--spacing-3);
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--spacing-2);
}

.post-media img {
  width: 100%;
  border-radius: var(--radius-md);
  object-fit: cover;
}

.post-actions {
  display: flex;
  gap: var(--spacing-4);
  padding-top: var(--spacing-3);
  border-top: 1px solid var(--color-border);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2) var(--spacing-3);
  background: transparent;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: background-color 0.2s;
}

.action-btn:hover {
  background-color: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.load-more {
  text-align: center;
  margin-top: var(--spacing-6);
}

.btn-primary,
.btn-secondary {
  padding: var(--spacing-3) var(--spacing-6);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
  border: none;
}

.btn-primary:hover {
  background: var(--color-primary-dark);
}

.btn-secondary {
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
}

.btn-secondary:hover {
  background: var(--color-primary);
  color: white;
}

/* AI Generation Status Styles */
.post-card.ai-post {
  border: 2px solid var(--color-primary);
  background: linear-gradient(to bottom, var(--color-bg-elevated), var(--color-bg-primary));
}

.ai-generation-status {
  padding: var(--spacing-4);
  margin-bottom: var(--spacing-4);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: var(--radius-md);
  color: white;
}

.status-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-3);
}

.status-icon {
  font-size: 1.5rem;
}

.status-header h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
}

.progress-bar {
  height: 8px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: var(--radius-full);
  overflow: hidden;
  margin-bottom: var(--spacing-2);
}

.progress-fill {
  height: 100%;
  background: white;
  transition: width 0.5s ease;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.status-text {
  margin: 0;
  font-size: 0.875rem;
  opacity: 0.9;
}

.ai-content-preview {
  margin-top: var(--spacing-4);
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
  border: 2px solid var(--color-primary);
}

.ai-preview-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-3);
}

.preview-icon {
  font-size: 1.25rem;
}

.ai-preview-header h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.ai-preview-prompt {
  padding: var(--spacing-3);
  background: var(--color-bg-primary);
  border-radius: var(--radius-sm);
  margin-bottom: var(--spacing-3);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
}

.ai-preview-code,
.ai-preview-text {
  margin-bottom: var(--spacing-3);
  padding: var(--spacing-3);
  background: var(--color-bg-primary);
  border-radius: var(--radius-sm);
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  line-height: 1.6;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-open-ai {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3);
  background: linear-gradient(135deg, var(--color-primary) 0%, #10b981 100%);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn-open-ai:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.modal-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  background: rgba(0, 0, 0, 0.85) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  z-index: 9999 !important;
  padding: var(--spacing-4) !important;
  overflow-y: auto !important;
  opacity: 1 !important;
  visibility: visible !important;
  pointer-events: auto !important;
}

.modal-content {
  position: relative;
  background: #ffffff !important;
  border-radius: var(--radius-lg);
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
  z-index: 10000;
  pointer-events: auto !important;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-5);
  border-bottom: 1px solid var(--color-border);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.btn-close {
  background: none;
  border: none;
  font-size: 2rem;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
  transition: background 0.2s;
}

.btn-close:hover {
  background: var(--color-bg-secondary);
}

.modal-body {
  padding: var(--spacing-5);
  overflow-y: auto;
  flex: 1;
}

.ai-modal-section {
  margin-bottom: var(--spacing-6);
}

.ai-modal-section:last-child {
  margin-bottom: 0;
}

.ai-modal-section h3 {
  margin: 0 0 var(--spacing-3) 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-3);
}

.section-header h3 {
  margin: 0;
}

.btn-copy-small {
  padding: var(--spacing-2) var(--spacing-3);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-copy-small:hover {
  background: var(--color-primary-dark);
}

.ai-prompt-full {
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
  border-left: 4px solid var(--color-primary);
  color: var(--color-text-primary);
  line-height: 1.6;
  font-size: 0.9375rem;
}

.code-block {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: var(--spacing-4);
  border-radius: var(--radius-md);
  overflow-x: auto;
  font-family: 'Courier New', Consolas, Monaco, monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0;
  max-height: 400px;
  overflow-y: auto;
}

.ai-response-content {
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
  line-height: 1.7;
  color: var(--color-text-primary);
  font-size: 0.9375rem;
  max-height: 600px;
  overflow-y: auto;
}

.ai-response-content pre {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: var(--spacing-3);
  border-radius: var(--radius-sm);
  overflow-x: auto;
  font-family: 'Courier New', Consolas, Monaco, monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  margin: var(--spacing-3) 0;
}

.ai-response-content code {
  background: rgba(0, 0, 0, 0.1);
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
  font-family: 'Courier New', Consolas, Monaco, monospace;
  font-size: 0.875em;
}

.ai-response-content p {
  margin: var(--spacing-3) 0;
}

.ai-response-content h1,
.ai-response-content h2,
.ai-response-content h3 {
  margin-top: var(--spacing-4);
  margin-bottom: var(--spacing-2);
  font-weight: 600;
}

.ai-response-content ul,
.ai-response-content ol {
  margin: var(--spacing-3) 0;
  padding-left: var(--spacing-5);
}

.ai-response-content blockquote {
  border-left: 4px solid var(--color-primary);
  padding-left: var(--spacing-3);
  margin: var(--spacing-3) 0;
  color: var(--color-text-secondary);
  font-style: italic;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-3);
  padding: var(--spacing-5);
  border-top: 1px solid var(--color-border);
}

.modal-footer .btn-primary,
.modal-footer .btn-secondary {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

/* AI Stats Section */
.ai-stats .stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-3);
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.stat-value {
  font-size: 1.125rem;
  color: var(--color-text-primary);
  font-weight: 600;
}

@media (max-width: 768px) {
  .ai-stats .stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .wall-banner {
    height: 120px;
  }
  
  .wall-avatar {
    width: 80px;
    height: 80px;
    margin-top: -40px;
  }
  
  .wall-title {
    font-size: 1.5rem;
  }
}
</style>
