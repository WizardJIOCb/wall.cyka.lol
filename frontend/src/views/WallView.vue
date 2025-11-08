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
            <p v-if="wall.owner_bio" class="wall-description">{{ wall.owner_bio }}</p>
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
          <div v-for="post in visiblePosts" :key="post.post_id" class="post-card" :class="{ 'ai-post': post.post_type === 'ai_app' }">
            <!-- AI Generation Real-time Progress (if AI post is generating) -->
            <AIGenerationProgress 
              v-if="post.post_type === 'ai_app' && (post.ai_status === 'queued' || post.ai_status === 'processing') && post.ai_job_id"
              :jobId="post.ai_job_id"
              :modelName="post.ai_model"
              :auto-start="true"
              @complete="handleGenerationComplete(post)"
              @error="handleGenerationError(post, $event)"
            />
            
            <!-- Show Prompt and Partial Content for Generating Posts -->
            <div v-if="post.post_type === 'ai_app' && (post.ai_status === 'queued' || post.ai_status === 'processing') && post.ai_job_id" class="ai-content-info">
              <!-- Show the prompt -->
              <div v-if="post.ai_content?.user_prompt" class="ai-preview-prompt">
                <strong>Prompt:</strong> {{ truncateText(post.ai_content.user_prompt, 150) }}
              </div>
              
              <!-- Show partial response if available -->
              <div v-if="post.ai_content?.html_content">
                <div class="ai-preview-response">
                  <strong>Generated Response:</strong>
                </div>
                <div class="ai-preview-text ai-generating" v-html="getPreviewHtml(post.ai_content.html_content)"></div>
              </div>
              
              <!-- Button to open modal - always show for processing posts -->
              <button v-if="post.ai_status === 'processing'" @click="openAIModal(post)" class="btn-open-ai">
                <span class="icon">👁️</span>
                <span>{{ post.ai_content?.html_content ? 'View Full Response' : 'Watch Generation' }}</span>
              </button>
            </div>
            
            <!-- Regular Post Header (only for non-AI posts) -->
            <div v-if="post.post_type !== 'ai_app'" class="post-header">
              <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="post-avatar" />
              <div class="post-meta">
                <span class="post-author">@{{ post.author_username }}</span>
                <span class="post-time">{{ formatDate(post.created_at) }}</span>
              </div>
            </div>
            
            <!-- Regular Post Content (only for non-AI posts) -->
            <div v-if="post.post_type !== 'ai_app'" class="post-content">
              <div v-if="post.content_html" v-html="post.content_html"></div>
              <p v-else>{{ post.content_text }}</p>
            </div>
            
            <!-- AI Generated Content Preview (if completed) -->
            <div v-if="post.post_type === 'ai_app' && post.ai_status === 'completed'" class="ai-content-preview">
              <div class="ai-preview-header">
                <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="preview-avatar" />
                <h4>@{{ post.author_username }}</h4>
              </div>
              
              <!-- Status and Bricks Info -->
              <div class="ai-generation-info">
                <div class="info-item status-completed">
                  <strong>Status:</strong> 
                  <span class="status-badge">✓ Completed</span>
                </div>
                <div v-if="post.ai_content?.bricks_cost" class="info-item bricks-spent">
                  <strong>Bricks spent:</strong> 
                  <span class="bricks-amount">{{ post.ai_content.bricks_cost }} 🧱</span>
                </div>
                <div v-if="post.ai_content?.generation_model" class="info-item model-used">
                  <strong>Model:</strong> 
                  <span class="model-name">{{ post.ai_content.generation_model }}</span>
                </div>
              </div>
              
              <!-- Generation Stats -->
              <div class="ai-generation-stats" v-if="post.ai_content?.generation_time || post.ai_content?.total_tokens">
                <div v-if="post.ai_content?.generation_time" class="stat-item">
                  <strong>Time:</strong> 
                  <span class="stat-value">{{ (post.ai_content.generation_time / 1000).toFixed(2) }}s</span>
                </div>
                <div v-if="post.ai_content?.total_tokens" class="stat-item">
                  <strong>Total Tokens:</strong> 
                  <span class="stat-value">{{ post.ai_content.total_tokens.toLocaleString() }}</span>
                </div>
                <div v-if="getAverageSpeed(post) > 0" class="stat-item">
                  <strong>Avg Speed:</strong> 
                  <span class="stat-value">{{ getAverageSpeed(post).toFixed(2) }} tok/s</span>
                </div>
              </div>
              
              <div v-if="post.ai_content?.user_prompt" class="ai-preview-prompt">
                <strong>Prompt:</strong> {{ truncateText(post.ai_content.user_prompt, 150) }}
              </div>
              <div v-if="post.ai_content?.html_content" class="ai-preview-response">
                <strong>Response:</strong>
              </div>
              <div v-if="post.ai_content?.html_content" class="ai-preview-text" v-html="getPreviewHtml(post.ai_content.html_content)"></div>
              <button @click="openAIModal(post)" class="btn-open-ai">
                <span class="icon">👁️</span>
                <span>View Full Response</span>
              </button>
            </div>
            
            <!-- AI Content In Progress (only show if no job_id for progress component) -->
            <div v-if="post.post_type === 'ai_app' && (post.ai_status === 'queued' || post.ai_status === 'processing') && !post.ai_job_id" class="ai-content-preview">
              <div class="ai-preview-header">
                <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="preview-avatar" />
                <h4>@{{ post.author_username }}</h4>
              </div>
              
              <!-- Status and Model Info -->
              <div class="ai-generation-info">
                <div class="info-item status-processing">
                  <strong>Status:</strong> 
                  <span class="status-badge processing">{{ post.ai_status === 'queued' ? '⏳ Queued' : '⏳ Processing' }}</span>
                </div>
                <div v-if="post.ai_content?.generation_model" class="info-item model-used">
                  <strong>Model:</strong> 
                  <span class="model-name">{{ post.ai_content.generation_model }}</span>
                </div>
              </div>
              
              <div v-if="post.ai_content?.user_prompt" class="ai-preview-prompt">
                <strong>Prompt:</strong> {{ truncateText(post.ai_content.user_prompt, 150) }}
              </div>
              <!-- Show partial response if available during generation -->
              <div v-if="post.ai_content?.html_content" class="ai-preview-response">
                <strong>Response:</strong>
              </div>
              <div v-if="post.ai_content?.html_content" class="ai-preview-text ai-generating" v-html="getPreviewHtml(post.ai_content.html_content)"></div>
              
              <!-- Button to open modal even during generation -->
              <button v-if="post.ai_content?.html_content" @click="openAIModal(post)" class="btn-open-ai">
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
              <button class="action-btn">
                <span class="icon">📖</span>
                <span>{{ post.open_count || 0 }}</span>
              </button>
              <span class="post-time-footer">{{ formatDate(post.post_type === 'ai_app' ? post.updated_at : post.created_at) }}</span>
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
          
          <div class="ai-modal-section" v-if="selectedAIPost.ai_content?.html_content || selectedAIPost.ai_status === 'processing'">
            <h3>🧠 Response</h3>
            <div v-if="selectedAIPost.ai_content?.html_content" class="ai-response-content" :class="{ 'ai-generating': selectedAIPost.ai_status === 'processing' }" v-html="renderedAIContent"></div>
            <div v-else-if="selectedAIPost.ai_status === 'processing'" class="ai-response-content ai-generating">
              <p style="color: var(--color-text-secondary); font-style: italic;">Waiting for generation to begin...</p>
            </div>
          </div>
          
          <div class="ai-modal-section ai-stats" v-if="selectedAIPost.ai_content">
            <h3>📊 Generation Stats</h3>
            
            <!-- Progress bar for generating posts -->
            <div v-if="selectedAIPost.ai_status === 'processing'" class="generation-progress-bar">
              <div class="progress-bar-container">
                <div class="progress-bar-fill" :style="{ width: (selectedAIPost.ai_content.progress_percentage || 0) + '%' }"></div>
              </div>
              <div class="progress-text">{{ (selectedAIPost.ai_content.progress_percentage || 0) }}%</div>
            </div>
            
            <div class="stats-grid">
              <div class="stat-item">
                <span class="stat-label">Model:</span>
                <span class="stat-value">{{ selectedAIPost.ai_content.generation_model || selectedAIPost.ai_model || 'Unknown' }}</span>
              </div>
              
              <!-- Real-time stats during generation -->
              <template v-if="selectedAIPost.ai_status === 'processing'">
                <div class="stat-item" v-if="selectedAIPost.ai_content.elapsed_time">
                  <span class="stat-label">Elapsed:</span>
                  <span class="stat-value">{{ (selectedAIPost.ai_content.elapsed_time / 1000).toFixed(1) }}s</span>
                </div>
                <div class="stat-item" v-if="selectedAIPost.ai_content.estimated_remaining">
                  <span class="stat-label">Remaining:</span>
                  <span class="stat-value">{{ (selectedAIPost.ai_content.estimated_remaining / 1000).toFixed(1) }}s</span>
                </div>
                <div class="stat-item" v-if="selectedAIPost.ai_content.current_tokens">
                  <span class="stat-label">Current Tokens:</span>
                  <span class="stat-value">{{ selectedAIPost.ai_content.current_tokens.toLocaleString() }}</span>
                </div>
                <div class="stat-item" v-if="selectedAIPost.ai_content.tokens_per_second">
                  <span class="stat-label">Speed:</span>
                  <span class="stat-value">{{ selectedAIPost.ai_content.tokens_per_second.toFixed(2) }} tok/s</span>
                </div>
                <div class="stat-item" v-if="selectedAIPost.ai_content.content_length">
                  <span class="stat-label">Generated:</span>
                  <span class="stat-value">{{ selectedAIPost.ai_content.content_length.toLocaleString() }} chars</span>
                </div>
                <div class="stat-item" v-if="selectedAIPost.ai_content.chars_per_second">
                  <span class="stat-label">Char Speed:</span>
                  <span class="stat-value">{{ selectedAIPost.ai_content.chars_per_second.toFixed(2) }} char/s</span>
                </div>
              </template>
              
              <!-- Final stats after completion -->
              <template v-else>
                <div class="stat-item" v-if="selectedAIPost.ai_content.generation_time">
                  <span class="stat-label">Time:</span>
                  <span class="stat-value">{{ (selectedAIPost.ai_content.generation_time / 1000).toFixed(2) }}s</span>
                </div>
                <div class="stat-item" v-if="selectedAIPost.ai_content.bricks_cost">
                  <span class="stat-label">Bricks Cost:</span>
                  <span class="stat-value bricks-value">{{ selectedAIPost.ai_content.bricks_cost }} 🧱</span>
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
                <div class="stat-item" v-if="averageTokensPerSecond > 0">
                  <span class="stat-label">Avg Speed:</span>
                  <span class="stat-value">{{ averageTokensPerSecond.toFixed(2) }} tok/s</span>
                </div>
              </template>
            </div>
          </div>
          
          <!-- Post Counters -->
          <div class="ai-modal-section" v-if="selectedAIPost">
            <h3>📊 Post Metrics</h3>
            <div class="post-counters">
              <div class="counter-item">
                <button class="counter-button" @click="toggleLike(selectedAIPost)">
                  <span class="counter-icon">{{ selectedAIPost.user_liked ? '❤️' : '🤍' }}</span>
                  <span class="counter-label">{{ selectedAIPost.user_liked ? 'Liked' : 'Like' }}</span>
                  <span class="counter-value">{{ selectedAIPost.reaction_count || 0 }}</span>
                </button>
              </div>
              <div class="counter-item">
                <span class="counter-icon">💬</span>
                <span class="counter-label">Comments</span>
                <span class="counter-value">{{ selectedAIPost.comment_count || 0 }}</span>
              </div>
              <div class="counter-item">
                <span class="counter-icon">👁</span>
                <span class="counter-label">Views</span>
                <span class="counter-value">{{ selectedAIPost.view_count || 0 }}</span>
              </div>
              <div class="counter-item">
                <span class="counter-icon">📖</span>
                <span class="counter-label">Opens</span>
                <span class="counter-value">{{ selectedAIPost.open_count || 0 }}</span>
              </div>
            </div>
          </div>
          
          <!-- Comment Section -->
          <div class="ai-modal-section" v-if="selectedAIPost">
            <h3>💬 Comments</h3>
            <CommentSection 
              :post-id="selectedAIPost.post_id" 
              :max-depth="3"
              @comment-created="handleCommentCreated"
              @comment-deleted="handleCommentDeleted"
              @comment-updated="handleCommentUpdated"
            />
          </div>
        </div>
        
        <div class="modal-footer">
          <div class="action-buttons-group">
            <button @click="shareAIResponse" class="btn-share">
              <span class="icon">🔗</span>
              <span>Share Link</span>
            </button>
            <button @click="copyToClipboard(selectedAIPost.ai_content.html_content, 'Response')" class="btn-copy">
              <span class="icon">📋</span>
              <span>Copy</span>
            </button>
          </div>
          <button @click="closeAIModal" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Toast Notification -->
  <Teleport to="body">
    <div v-if="showToast" class="toast-notification">
      {{ toastMessage }}
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api/client'
import postsAPI from '@/services/api/posts'
import AIGenerationProgress from '@/components/ai/AIGenerationProgress.vue'
import CommentSection from '@/components/social/CommentSection.vue'
import { marked } from 'marked'

// Configure marked options
marked.setOptions({
  breaks: true, // Convert line breaks to <br>
  gfm: true, // GitHub Flavored Markdown
})

const props = defineProps<{ wallId?: string }>()

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loading = ref(true)
const loadingPosts = ref(false)
const isLoadingPostsRequest = ref(false) // Prevents multiple simultaneous requests
const error = ref<string | null>(null)
const wall = ref<any>(null)
const posts = ref<any[]>([])
const page = ref(1)
const limit = 20
const hasMorePosts = ref(true)
let pollInterval: any = null
let contentEventSources: Map<number, EventSource> = new Map() // Track SSE connections per job
let progressEventSources: Map<number, EventSource> = new Map() // Track progress SSE connections

// Add this ref to track viewed posts
const viewedPosts = new Set<number>()
// Add this ref to track posts that have been batch processed
const batchProcessedPosts = new Set<number>()

const isOwnWall = computed(() => {
  return wall.value && authStore.user && wall.value.user_id === authStore.user.id
})

const bannerStyle = computed(() => {
  if (wall.value?.theme) {
    return { backgroundColor: wall.value.theme }
  }
  return { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
})

// Filter out failed AI posts
const visiblePosts = computed(() => {
  return posts.value.filter((post: any) => {
    // Hide failed AI posts
    if (post.post_type === 'ai_app' && post.ai_status === 'failed') {
      return false
    }
    return true
  })
})

// Add this method to increment view count for a post
const incrementPostViewCount = async (postId: number) => {
  // Only increment view count once per post per session
  if (viewedPosts.has(postId)) {
    return
  }
  
  try {
    await postsAPI.incrementViewCount(postId)
    viewedPosts.add(postId)
    console.log(`View count incremented for post ${postId}`)
  } catch (error: any) {
    console.error(`Failed to increment view count for post ${postId}:`, error)
  }
}

// Add this method to batch increment view counts
const batchIncrementViewCounts = async () => {
  // Get all post IDs that haven't been batch processed yet
  const postIdsToProcess: number[] = []
  posts.value.forEach((post: any) => {
    if (!batchProcessedPosts.has(post.post_id) && !viewedPosts.has(post.post_id)) {
      postIdsToProcess.push(post.post_id)
      batchProcessedPosts.add(post.post_id)
    }
  })
  
  // Only process if we have posts to process
  if (postIdsToProcess.length > 0) {
    try {
      await postsAPI.batchIncrementViewCounts(postIdsToProcess)
      postIdsToProcess.forEach(id => viewedPosts.add(id))
      console.log(`Batch view counts incremented for ${postIdsToProcess.length} posts`)
    } catch (error: any) {
      console.error('Failed to batch increment view counts:', error)
      // If batch fails, fall back to individual increments
      postIdsToProcess.forEach(id => {
        batchProcessedPosts.delete(id) // Remove from batch processed so individual increment can try
        incrementPostViewCount(id)
      })
    }
  }
}

// Add this method to increment open count for a post
const incrementPostOpenCount = async (postId: number) => {
  try {
    await postsAPI.incrementOpenCount(postId)
    console.log(`Open count incremented for post ${postId}`)
  } catch (error: any) {
    console.error(`Failed to increment open count for post ${postId}:`, error)
  }
}

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

    let response: any;
    
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
  
  // Prevent multiple simultaneous loadPosts requests
  if (isLoadingPostsRequest.value) {
    console.log('[WallView] Skipping loadPosts - request already in progress')
    return
  }
  
  try {
    isLoadingPostsRequest.value = true
    
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
          // Use batch processing for view counts
          batchIncrementViewCounts()
        }
      } else {
        posts.value = [...posts.value, ...newPosts]
        // Use batch processing for view counts on newly loaded posts
        batchIncrementViewCounts()
      }
      
      hasMorePosts.value = newPosts.length === limit
      
      // Start polling if there are pending AI posts
      checkForPendingAIPosts()
    }
  } catch (err: any) {
    console.error('Error loading posts:', err)
  } finally {
    isLoadingPostsRequest.value = false
    if (!isPolling) {
      loadingPosts.value = false
    }
  }
}

const updatePostsData = (newPosts: any[]) => {
  // Create a map of new posts by post_id for quick lookup
  const newPostsMap = new Map(newPosts.map((post: any) => [post.post_id, post]))
  
  // Update existing posts with new data WITHOUT causing re-render
  posts.value.forEach((post: any, index: number) => {
    const updatedPost = newPostsMap.get(post.post_id)
    if (updatedPost) {
      // Update ALL fields, not just status
      // Use Object.assign to update in-place and avoid re-render
      Object.assign(posts.value[index], updatedPost)
      newPostsMap.delete(post.post_id)
    }
  })
  
  // Add any new posts that weren't in the existing list
  if (newPostsMap.size > 0) {
    posts.value = [...Array.from(newPostsMap.values()), ...posts.value]
  }
}

const checkForPendingAIPosts = () => {
  const pendingPosts = posts.value.filter(
    (post: any) => post.post_type === 'ai_app' && 
           (post.ai_status === 'queued' || post.ai_status === 'processing')
  )
  
  console.log('[WallView] Checking pending AI posts:', pendingPosts.length)
  pendingPosts.forEach((post: any) => {
    console.log('[WallView] Pending post:', {
      post_id: post.post_id,
      ai_status: post.ai_status,
      ai_job_id: post.ai_job_id,
      has_content: !!post.ai_content,
      html_content_length: post.ai_content?.html_content?.length || 0
    })
  })
  
  // DISABLE POLLING - SSE handles all real-time updates
  // Only start content streaming for processing posts
  if (pendingPosts.length > 0) {
    pendingPosts.forEach((post: any) => {
      if (post.ai_status === 'processing' && post.ai_job_id) {
        startContentStream(post)
      }
    })
  } else {
    stopAllContentStreams()
  }
}

const startPolling = () => {
  if (pollInterval) return
  
  // Poll every 30 seconds - SSE handles real-time updates
  pollInterval = setInterval(async () => {
    console.log('[WallView] Polling for post updates...')
    await loadPosts(true) // Pass true to indicate polling
  }, 30000) // Poll every 30 seconds
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
  // Stop streaming for this job
  stopContentStream(post.ai_job_id)
  // SSE 'complete' event already updated the post data - no need to reload
}

// Handle AI generation error
const handleGenerationError = (post: any, errorMsg: string) => {
  console.error('Generation failed for post:', post.post_id, errorMsg)
  // Stop streaming for this job
  stopContentStream(post.ai_job_id)
  // Mark post as failed in UI
  const postIndex = posts.value.findIndex(p => p.post_id === post.post_id)
  if (postIndex !== -1) {
    posts.value[postIndex].ai_status = 'failed'
  }
}

// Start streaming content for a generating post
const startContentStream = (post: any) => {
  if (!post.ai_job_id) return
  
  // Check if already streaming for this job
  if (contentEventSources.has(post.ai_job_id)) {
    console.log('[ContentStream] Already streaming for job:', post.ai_job_id)
    return
  }
  
  // Use relative URL for production, absolute URL for development
  const apiUrl = import.meta.env.VITE_API_URL || ''
  const streamUrl = `${apiUrl}/api/v1/ai/generation/${post.ai_job_id}/content`
  
  console.log('[ContentStream] Starting NEW SSE for job:', post.ai_job_id, 'URL:', streamUrl)
  
  const eventSource = new EventSource(streamUrl)
  contentEventSources.set(post.ai_job_id, eventSource)
  
  eventSource.addEventListener('content', (event) => {
    const data = JSON.parse(event.data)
    console.log('[ContentStream] Received content chunk:', {
      jobId: post.ai_job_id,
      contentLength: data.content?.length || 0,
      model: data.model,
      modalOpen: showAIModal.value,
      isSelectedPost: selectedAIPost.value?.post_id === post.post_id
    })
    
    // Update post in list
    const postIndex = posts.value.findIndex(p => p.post_id === post.post_id)
    if (postIndex !== -1) {
      // Initialize ai_content if it doesn't exist
      if (!posts.value[postIndex].ai_content) {
        posts.value[postIndex].ai_content = {}
      }
      
      posts.value[postIndex] = {
        ...posts.value[postIndex],
        ai_content: {
          ...posts.value[postIndex].ai_content,
          html_content: data.content || '',
          generation_model: data.model || posts.value[postIndex].ai_content?.generation_model
        }
      }
      console.log('[ContentStream] Updated post in list, content length:', data.content?.length || 0)
    }
    
    // Update modal if open for this post
    if (selectedAIPost.value?.post_id === post.post_id) {
      console.log('[ContentStream] Updating modal content')
      // Force reactivity by creating new object
      selectedAIPost.value = {
        ...selectedAIPost.value,
        ai_content: {
          ...selectedAIPost.value.ai_content,
          html_content: data.content || ''
        }
      }
      console.log('[ContentStream] Modal updated, new content length:', selectedAIPost.value.ai_content.html_content?.length || 0)
    }
  })
  
  eventSource.addEventListener('complete', (event) => {
    console.log('[ContentStream] Generation complete')
    stopContentStream(post.ai_job_id)
    // Update post status to completed
    const postIndex = posts.value.findIndex(p => p.post_id === post.post_id)
    if (postIndex !== -1) {
      posts.value[postIndex].ai_status = 'completed'
    }
  })
  
  eventSource.addEventListener('error', (event) => {
    console.error('[ContentStream] SSE error for job:', post.ai_job_id)
    stopContentStream(post.ai_job_id)
  })
  
  eventSource.onerror = (error) => {
    console.error('[ContentStream] Connection error:', error)
    // Close the connection on error to prevent hanging
    stopContentStream(post.ai_job_id)
  }
}

// Stop streaming content for a job
const stopContentStream = (jobId: number) => {
  if (!jobId) return
  
  const eventSource = contentEventSources.get(jobId)
  if (eventSource) {
    eventSource.close()
    contentEventSources.delete(jobId)
    console.log('[ContentStream] Stopped SSE for job:', jobId)
  }
}

// Stop all content streams
const stopAllContentStreams = () => {
  contentEventSources.forEach((eventSource, jobId) => {
    eventSource.close()
    console.log('[ContentStream] Closed SSE for job:', jobId)
  })
  contentEventSources.clear()
}

// Start streaming progress for a generating post
const startProgressStream = (post: any) => {
  if (!post.ai_job_id) return
  
  // Check if already streaming for this job
  if (progressEventSources.has(post.ai_job_id)) {
    console.log('[ProgressStream] Already streaming for job:', post.ai_job_id)
    return
  }
  
  // Use relative URL for production, absolute URL for development
  const apiUrl = import.meta.env.VITE_API_URL || ''
  const streamUrl = `${apiUrl}/api/v1/ai/generation/${post.ai_job_id}/progress`
  
  console.log('[ProgressStream] Starting NEW SSE for job:', post.ai_job_id, 'URL:', streamUrl)
  
  const eventSource = new EventSource(streamUrl)
  progressEventSources.set(post.ai_job_id, eventSource)
  
  eventSource.addEventListener('progress', (event) => {
    const data = JSON.parse(event.data)
    console.log('[ProgressStream] Received progress update:', data)
    
    // Update selectedAIPost if this is the post in the modal
    if (selectedAIPost.value?.post_id === post.post_id) {
      selectedAIPost.value = {
        ...selectedAIPost.value,
        ai_content: {
          ...selectedAIPost.value.ai_content,
          // Real-time stats
          current_tokens: data.current_tokens || 0,
          tokens_per_second: data.tokens_per_second || 0,
          elapsed_time: data.elapsed_time || 0,
          estimated_remaining: data.estimated_remaining || 0,
          progress_percentage: data.progress || 0,
          content_length: data.content_length || 0,
          chars_per_second: data.chars_per_second || 0,
          // Token stats
          input_tokens: data.prompt_tokens || 0,
          output_tokens: data.completion_tokens || 0,
          total_tokens: data.total_tokens || 0
        }
      }
    }
  })
  
  eventSource.addEventListener('complete', (event) => {
    console.log('[ProgressStream] Generation complete, reloading data')
    stopProgressStream(post.ai_job_id)
    
    // Reload post data to get final stats
    if (selectedAIPost.value?.post_id === post.post_id) {
      apiClient.get(`/ai/apps/${post.ai_app_id}`).then((response: any) => {
        if (response?.success && response?.data?.app) {
          selectedAIPost.value = {
            ...selectedAIPost.value,
            ai_status: 'completed',
            ai_content: {
              ...response.data.app,
              bricks_cost: response.data.app.bricks_cost || 0
            }
          }
        }
      }).catch(err => {
        console.error('Failed to reload final data:', err)
      })
    }
    
    // Reload posts list
    loadPosts()
  })
  
  eventSource.addEventListener('error', (event) => {
    console.error('[ProgressStream] SSE error for job:', post.ai_job_id)
    stopProgressStream(post.ai_job_id)
  })
  
  eventSource.onerror = (error) => {
    console.error('[ProgressStream] Connection error:', error)
    // Close the connection on error to prevent hanging
    stopProgressStream(post.ai_job_id)
  }
}

// Stop streaming progress for a job
const stopProgressStream = (jobId: number) => {
  if (!jobId) return
  
  const eventSource = progressEventSources.get(jobId)
  if (eventSource) {
    eventSource.close()
    progressEventSources.delete(jobId)
    console.log('[ProgressStream] Stopped SSE for job:', jobId)
  }
}

// Stop all progress streams
const stopAllProgressStreams = () => {
  progressEventSources.forEach((eventSource, jobId) => {
    eventSource.close()
    console.log('[ProgressStream] Closed SSE for job:', jobId)
  })
  progressEventSources.clear()
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

// Function to render preview with markdown formatting
const getPreviewHtml = (content: string) => {
  if (!content) return ''
  
  // Strip HTML tags first, then truncate, then parse markdown
  const plainText = content.replace(/<[^>]*>/g, '')
  const truncated = truncateText(plainText, 200)
  
  // Parse markdown to HTML for preview
  const html = marked.parse(truncated, { async: false })
  return html
}

const selectedAIPost = ref<any>(null)
const showAIModal = ref(false)
const toastMessage = ref('')
const showToast = ref(false)

const displayToast = (message: string) => {
  toastMessage.value = message
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 2000) // Hide after 2 seconds
}

// Computed property for rendered markdown content
const renderedAIContent = computed(() => {
  if (!selectedAIPost.value?.ai_content?.html_content) return ''
  
  // Parse markdown to HTML
  let rawHtml = String(marked.parse(selectedAIPost.value.ai_content.html_content))
  
  // Replace ALL <hr> tags (with or without attributes) with clean version with class
  rawHtml = rawHtml.replace(/<hr\s*\/?>/gi, '<hr class="content-divider">')
  rawHtml = rawHtml.replace(/<hr\s+[^>]*>/gi, '<hr class="content-divider">')
  
  return rawHtml
})

// Computed property for average tokens per second
const averageTokensPerSecond = computed(() => {
  if (!selectedAIPost.value?.ai_content) return 0
  
  const outputTokens = selectedAIPost.value.ai_content.output_tokens || 0
  const generationTime = selectedAIPost.value.ai_content.generation_time || 0
  
  if (outputTokens > 0 && generationTime > 0) {
    // generation_time is in milliseconds, convert to seconds
    return outputTokens / (generationTime / 1000)
  }
  
  return 0
})

// Helper function to get average speed for any post
const getAverageSpeed = (post: any) => {
  if (!post?.ai_content) return 0
  
  // Try using stored tokens_per_second from database first
  if (post.ai_content.tokens_per_second && post.ai_content.tokens_per_second > 0) {
    return post.ai_content.tokens_per_second
  }
  
  // Otherwise calculate from output_tokens and generation_time
  const outputTokens = post.ai_content.output_tokens || 0
  const generationTime = post.ai_content.generation_time || 0
  
  if (outputTokens > 0 && generationTime > 0) {
    // generation_time is in milliseconds, convert to seconds
    return outputTokens / (generationTime / 1000)
  }
  
  return 0
}

// Function to render bio with markdown formatting and auto-linked URLs
const renderBio = (bio: string) => {
  if (!bio) return ''
  
  // Auto-link URLs in the bio text
  const urlRegex = /(https?:\/\/[^\s]+)/g
  const linkedBio = bio.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>')
  
  // Parse markdown to HTML
  const html = marked.parse(linkedBio, { async: false })
  return html
}

const openAIModal = async (post: any) => {
  try {
    // Increment open count when post is fully opened
    await incrementPostOpenCount(post.post_id)
    // For generating posts, use data from the post itself
    if (post.ai_status === 'queued' || post.ai_status === 'processing') {
      console.log('Opening modal for generating post, using post data:', {
        post_id: post.post_id,
        ai_job_id: post.ai_job_id,
        has_content: !!post.ai_content?.html_content,
        content_length: post.ai_content?.html_content?.length || 0
      })
      
      selectedAIPost.value = {
        ...post,
        ai_content: {
          user_prompt: post.ai_content?.user_prompt || null,
          html_content: post.ai_content?.html_content || '', // Initialize as empty string, not null
          css_content: post.ai_content?.css_content || null,
          js_content: post.ai_content?.js_content || null,
          generation_model: post.ai_content?.generation_model || post.ai_model || null,
          generation_time: null,
          input_tokens: 0,
          output_tokens: 0,
          total_tokens: 0,
          bricks_cost: post.ai_content?.bricks_cost || 0,
          // Initialize progress tracking fields
          current_tokens: 0,
          tokens_per_second: 0,
          elapsed_time: 0,
          estimated_remaining: 0,
          progress_percentage: 0,
          content_length: 0,
          chars_per_second: 0
        }
      }
      showAIModal.value = true
      document.body.style.overflow = 'hidden'
      
      // Start streaming content and progress updates
      if (post.ai_status === 'processing' && post.ai_job_id) {
        startContentStream(post)
        startProgressStream(post)
      }
      return
    }
    
    // For completed posts, fetch full data from API
    const response: any = await apiClient.get(`/ai/apps/${post.ai_app_id}`)
    
    // Check if response has the expected structure
    if (response?.success && response?.data?.app) {
      console.log('AI App Data:', response.data.app)
      selectedAIPost.value = {
        ...post,
        ai_content: {
          ...response.data.app,
          bricks_cost: response.data.app.bricks_cost || post.ai_content?.bricks_cost || 0,
          input_tokens: response.data.app.input_tokens || post.ai_content?.input_tokens || 0,
          output_tokens: response.data.app.output_tokens || post.ai_content?.output_tokens || 0,
          total_tokens: response.data.app.total_tokens || post.ai_content?.total_tokens || 0
        }
      }
      showAIModal.value = true
      document.body.style.overflow = 'hidden'
    }
  } catch (err: any) {
    console.error('Error loading AI content:', err)
    // Even if API call fails, try to open modal with post data
    if (post.ai_content) {
      selectedAIPost.value = post
      showAIModal.value = true
      document.body.style.overflow = 'hidden'
    }
  }
}

const closeAIModal = () => {
  // Stop streaming if modal was showing a generating post
  if (selectedAIPost.value?.ai_job_id) {
    stopContentStream(selectedAIPost.value.ai_job_id)
    stopProgressStream(selectedAIPost.value.ai_job_id)
  }
  
  showAIModal.value = false
  selectedAIPost.value = null
  // Restore body scroll
  document.body.style.overflow = ''
}

const shareAIResponse = () => {
  if (!selectedAIPost.value) return
  
  const wallId = wall.value?.wall_id || route.params.wallId
  const appId = selectedAIPost.value.ai_app_id
  
  // Use port 8080 (production) instead of 3000 (dev)
  const baseUrl = window.location.origin.replace(':3000', ':8080')
  const shareUrl = `${baseUrl}/wall/${wallId}?ai=${appId}`
  
  navigator.clipboard.writeText(shareUrl).then(() => {
    console.log('Share link copied to clipboard:', shareUrl)
    displayToast('✓ Link copied!')
  }).catch(err => {
    console.error('Failed to copy share link:', err)
    displayToast('✗ Failed to copy link')
  })
}

const copyToClipboard = (text: string, label: string) => {
  navigator.clipboard.writeText(text).then(() => {
    console.log(`${label} copied to clipboard`)
    displayToast(`✓ ${label} copied!`)
  }).catch(err => {
    console.error('Failed to copy:', err)
    displayToast('✗ Failed to copy')
  })
}

const toggleLike = async (post: any) => {
  try {
    if (post.user_liked) {
      // Remove like
      await apiClient.delete(`/posts/${post.post_id}/reactions`)
      post.user_liked = false
      post.reaction_count = Math.max(0, (post.reaction_count || 0) - 1)
      
      // Update in posts list if it exists there
      const postIndex = posts.value.findIndex((p: any) => p.post_id === post.post_id)
      if (postIndex !== -1) {
        posts.value[postIndex].user_liked = false
        posts.value[postIndex].reaction_count = Math.max(0, (posts.value[postIndex].reaction_count || 0) - 1)
      }
    } else {
      // Add like
      await apiClient.post(`/posts/${post.post_id}/reactions`, { reaction_type: 'like' })
      post.user_liked = true
      post.reaction_count = (post.reaction_count || 0) + 1
      
      // Update in posts list if it exists there
      const postIndex = posts.value.findIndex((p: any) => p.post_id === post.post_id)
      if (postIndex !== -1) {
        posts.value[postIndex].user_liked = true
        posts.value[postIndex].reaction_count = (posts.value[postIndex].reaction_count || 0) + 1
      }
    }
  } catch (error) {
    console.error('Failed to toggle like:', error)
    displayToast('✗ Failed to update like')
  }
}

const handleCommentCreated = (comment: any) => {
  // Update comment count in the selected post
  if (selectedAIPost.value) {
    selectedAIPost.value.comment_count = (selectedAIPost.value.comment_count || 0) + 1
  }
  
  // Update comment count in the posts list
  if (selectedAIPost.value) {
    const postIndex = posts.value.findIndex((p: any) => p.post_id === selectedAIPost.value.post_id)
    if (postIndex !== -1) {
      posts.value[postIndex].comment_count = (posts.value[postIndex].comment_count || 0) + 1
    }
  }
  
  displayToast('✓ Comment added')
}

const handleCommentDeleted = (commentId: number) => {
  // Update comment count in the selected post
  if (selectedAIPost.value) {
    selectedAIPost.value.comment_count = Math.max(0, (selectedAIPost.value.comment_count || 0) - 1)
  }
  
  // Update comment count in the posts list
  if (selectedAIPost.value) {
    const postIndex = posts.value.findIndex((p: any) => p.post_id === selectedAIPost.value.post_id)
    if (postIndex !== -1) {
      posts.value[postIndex].comment_count = Math.max(0, (posts.value[postIndex].comment_count || 0) - 1)
    }
  }
  
  displayToast('✓ Comment deleted')
}

const handleCommentUpdated = (comment: any) => {
  displayToast('✓ Comment updated')
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

onMounted(async () => {
  await loadWall()
  
  // Check if there's an AI app ID in the URL query params
  const aiAppId = route.query.ai
  if (aiAppId) {
    // Find the post with this AI app ID and open the modal
    const post = posts.value.find(p => p.ai_app_id === parseInt(String(aiAppId)))
    if (post) {
      await openAIModal(post)
    }
  }
})

// Watch for route changes to reload wall (e.g., after AI generation redirect)
watch(() => route.params.wallId, async (newWallId, oldWallId) => {
  if (newWallId !== oldWallId) {
    console.log('[WallView] Route changed, reloading wall:', newWallId)
    await loadWall()
  }
})

// Watch for route query changes (e.g., ?ai=123)
watch(() => route.query.ai, async (newAiAppId) => {
  if (newAiAppId && posts.value.length > 0) {
    const post = posts.value.find(p => p.ai_app_id === parseInt(String(newAiAppId)))
    if (post) {
      await openAIModal(post)
    }
  }
})

onUnmounted(() => {
  stopPolling()
  stopAllContentStreams()
  stopAllProgressStreams()
  // Restore body scroll in case modal was open
  document.body.style.overflow = ''
})
</script>

<style scoped>
.wall-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-4);
  overflow-x: hidden;
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
  line-height: 1.6;
}

.wall-description :deep(a) {
  color: var(--color-primary);
  text-decoration: none;
  border-bottom: 1px solid transparent;
  transition: border-color 0.2s;
}

.wall-description :deep(a:hover) {
  border-bottom-color: var(--color-primary);
}

.wall-description :deep(p) {
  margin: 0.25rem 0;
}

.wall-description :deep(strong) {
  font-weight: 600;
  color: var(--color-text-primary);
}

.wall-description :deep(em) {
  font-style: italic;
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
  max-width: 100%;
  overflow: hidden;
}

.post-card {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  transition: box-shadow 0.2s;
  overflow: hidden;
  max-width: 100%;
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
  align-items: center;
}

.post-time-footer {
  margin-left: auto;
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  opacity: 0.7;
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
  overflow: hidden;
}

/* Same styles for ai-content-info */
.ai-content-info {
  margin-top: var(--spacing-4);
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
  border: 2px solid var(--color-primary);
  overflow: hidden;
}

.ai-preview-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-3);
}

.preview-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--color-primary);
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

.ai-generation-info {
  display: flex;
  gap: var(--spacing-3);
  margin-bottom: var(--spacing-3);
  padding: var(--spacing-2) 0;
  flex-wrap: wrap;
}

.info-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  font-size: 0.875rem;
}

.info-item strong {
  color: var(--color-text-secondary);
  font-weight: 500;
}

.status-badge {
  color: #10b981;
  font-weight: 600;
  background: rgba(16, 185, 129, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
}

.bricks-amount {
  color: #f59e0b;
  font-weight: 600;
  background: rgba(245, 158, 11, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
}

.model-name {
  color: #6366f1;
  font-weight: 600;
  background: rgba(99, 102, 241, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
}

.status-badge.processing {
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

/* AI Generation Stats */
.ai-generation-stats {
  display: flex;
  gap: var(--spacing-3);
  margin-bottom: var(--spacing-3);
  padding: var(--spacing-3);
  background: rgba(99, 102, 241, 0.05);
  border-radius: var(--radius-md);
  border: 1px solid rgba(99, 102, 241, 0.1);
  flex-wrap: wrap;
}

.ai-generation-stats .stat-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
  min-width: 120px;
}

.ai-generation-stats .stat-item strong {
  color: var(--color-text-secondary);
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.ai-generation-stats .stat-value {
  color: var(--color-text-primary);
  font-weight: 700;
  font-size: 1rem;
  font-family: 'Courier New', monospace;
}

.ai-preview-prompt {
  padding: 0;
  background: transparent;
  border-radius: var(--radius-sm);
  margin-bottom: var(--spacing-4);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
}

.ai-preview-response {
  padding: 0;
  background: transparent;
  margin-bottom: var(--spacing-2);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
}

.ai-preview-code,
.ai-preview-text {
  margin-bottom: var(--spacing-3);
  padding: 0;
  background: transparent;
  border-radius: var(--radius-sm);
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  line-height: 1.6;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

/* Animating text while generating */
.ai-preview-text.ai-generating {
  position: relative;
}

.ai-preview-text.ai-generating::after {
  content: '▊';
  display: inline-block;
  animation: blink 1s infinite;
  margin-left: 2px;
  color: var(--color-primary);
}

@keyframes blink {
  0%, 50% { opacity: 1; }
  51%, 100% { opacity: 0; }
}

/* Formatting for preview markdown */
.ai-preview-text :deep(p) {
  margin: 0.5rem 0;
}

.ai-preview-text :deep(p:first-child) {
  margin-top: 0;
}

.ai-preview-text :deep(p:last-child) {
  margin-bottom: 0;
}

.ai-preview-text :deep(strong) {
  font-weight: 700;
  color: var(--color-text-primary);
}

.ai-preview-text :deep(code) {
  background: rgba(0, 0, 0, 0.1);
  padding: 0.125rem 0.25rem;
  border-radius: 3px;
  font-family: 'Courier New', monospace;
  font-size: 0.85em;
  word-break: break-all;
}

.ai-preview-text :deep(pre) {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: 0.75rem;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.8125rem;
  margin: 0.5rem 0 0 0;
  max-width: 100%;
  white-space: pre-wrap;
  word-wrap: break-word;
  line-height: 1.5;
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
  word-wrap: break-word;
  overflow-wrap: break-word;
}

/* Animating cursor while generating in modal */
.ai-response-content.ai-generating::after {
  content: '▊';
  display: inline-block;
  animation: blink 1s infinite;
  margin-left: 2px;
  color: var(--color-primary);
  font-weight: bold;
}

/* Markdown basic elements */
.ai-response-content p {
  margin: var(--spacing-2) 0;
  white-space: normal;
  word-wrap: break-word;
}

.ai-response-content p:first-child {
  margin-top: 0;
}

.ai-response-content p:last-child {
  margin-bottom: 0;
}

.ai-response-content h1,
.ai-response-content h2,
.ai-response-content h3,
.ai-response-content h4 {
  margin-top: var(--spacing-4);
  margin-bottom: var(--spacing-2);
  font-weight: 600;
  color: var(--color-text-primary);
  line-height: 1.3;
}

.ai-response-content h1:first-child,
.ai-response-content h2:first-child,
.ai-response-content h3:first-child,
.ai-response-content h4:first-child {
  margin-top: 0;
}

.ai-response-content h1 {
  font-size: 1.75rem;
  border-bottom: 2px solid var(--color-border);
  padding-bottom: var(--spacing-2);
}

.ai-response-content h2 {
  font-size: 1.5rem;
}

.ai-response-content h3 {
  font-size: 1.25rem;
}

.ai-response-content h4 {
  font-size: 1.125rem;
}

/* Lists */
.ai-response-content ul,
.ai-response-content ol {
  margin: var(--spacing-2) 0;
  padding-left: var(--spacing-5);
}

.ai-response-content ul:first-child,
.ai-response-content ol:first-child {
  margin-top: 0;
}

.ai-response-content ul:last-child,
.ai-response-content ol:last-child {
  margin-bottom: 0;
}

.ai-response-content li {
  margin: var(--spacing-1) 0;
}

/* Blockquotes */
.ai-response-content blockquote {
  border-left: 4px solid var(--color-primary);
  padding: var(--spacing-2) var(--spacing-3);
  margin: var(--spacing-3) 0;
  color: var(--color-text-secondary);
  font-style: italic;
  background: rgba(102, 126, 234, 0.05);
  border-radius: var(--radius-sm);
}

.ai-response-content blockquote:first-child {
  margin-top: 0;
}

.ai-response-content blockquote:last-child {
  margin-bottom: 0;
}

/* Inline code */
.ai-response-content code {
  background: rgba(0, 0, 0, 0.08);
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
  font-family: 'Courier New', Consolas, Monaco, monospace;
  font-size: 0.875em;
  color: #e83e8c;
}

/* Code blocks */
.ai-response-content pre {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: var(--spacing-3);
  border-radius: var(--radius-sm);
  overflow-x: auto;
  font-family: 'Courier New', Consolas, Monaco, monospace;
  font-size: 0.875rem;
  line-height: 1.6;
  margin: var(--spacing-3) 0;
  border: 1px solid rgba(0, 0, 0, 0.1);
  white-space: pre-wrap;
  word-wrap: break-word;
}

.ai-response-content pre:first-child {
  margin-top: 0;
}

.ai-response-content pre:last-child {
  margin-bottom: 0;
}

.ai-response-content pre code {
  background: transparent;
  padding: 0;
  color: inherit;
  font-size: inherit;
  white-space: pre-wrap;
}

/* Tables */
.ai-response-content table {
  width: 100%;
  border-collapse: collapse;
  margin: var(--spacing-4) 0;
  font-size: 0.9rem;
}

.ai-response-content table thead {
  background: var(--color-bg-primary);
}

.ai-response-content table th,
.ai-response-content table td {
  padding: var(--spacing-2) var(--spacing-3);
  border: 1px solid var(--color-border);
  text-align: left;
}

.ai-response-content table th {
  font-weight: 600;
  color: var(--color-text-primary);
}

.ai-response-content table tr:nth-child(even) {
  background: rgba(0, 0, 0, 0.02);
}

/* Links */
.ai-response-content a {
  color: var(--color-primary);
  text-decoration: none;
  border-bottom: 1px solid transparent;
  transition: border-color 0.2s;
}

.ai-response-content a:hover {
  border-bottom-color: var(--color-primary);
}

/* Horizontal rule */
.ai-response-content :deep(hr),
.ai-response-content :deep(.content-divider) {
  border: none !important;
  border-top: 2px solid var(--color-border) !important;
  margin-top: 10px !important;
  margin-bottom: 10px !important;
  padding: 0 !important;
  display: block !important;
  clear: both !important;
  width: 100% !important;
  height: 0 !important;
}

/* Headings (h3) */
.ai-response-content :deep(h3) {
  font-weight: 700;
  color: var(--color-text-primary);
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  font-size: 1.125rem;
  line-height: 1.4;
}

/* Paragraphs */
.ai-response-content :deep(p) {
  margin-top: 0.75rem;
  margin-bottom: 0.75rem;
  line-height: 1.6;
}

.ai-response-content :deep(p:first-child) {
  margin-top: 0;
}

.ai-response-content :deep(p:last-child) {
  margin-bottom: 0;
}

/* Strong/Bold */
.ai-response-content :deep(strong) {
  font-weight: 700;
  color: var(--color-text-primary);
  display: inline;
}

/* Emphasis/Italic */
.ai-response-content :deep(em) {
  font-style: italic;
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-5);
  border-top: 1px solid var(--color-border);
}

.action-buttons-group {
  display: flex;
  gap: var(--spacing-2);
  align-items: center;
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

.post-counters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: var(--spacing-3);
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.counter-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-1);
  padding: var(--spacing-2);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-sm);
}

.counter-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-1);
  padding: var(--spacing-2);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-sm);
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
}

.counter-button:hover {
  background: var(--color-bg-secondary);
  transform: translateY(-2px);
}

.ai-modal-section h3 {
  margin-top: var(--spacing-6);
  padding-top: var(--spacing-4);
  border-top: 1px solid var(--color-border);
}

.counter-icon {
  font-size: 1.5rem;
}

.counter-label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.counter-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary);
}

/* Progress bar in modal */
.generation-progress-bar {
  margin-bottom: var(--spacing-4);
  padding: var(--spacing-3);
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  border-radius: var(--radius-md);
  border: 2px solid var(--color-primary);
}

.progress-bar-container {
  width: 100%;
  height: 12px;
  background: rgba(0, 0, 0, 0.1);
  border-radius: var(--radius-full);
  overflow: hidden;
  margin-bottom: var(--spacing-2);
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--color-primary) 0%, #667eea 100%);
  border-radius: var(--radius-full);
  transition: width 0.5s ease;
  box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
  animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
  }
  50% {
    box-shadow: 0 0 20px rgba(102, 126, 234, 0.8);
  }
}

.progress-text {
  text-align: center;
  font-weight: 700;
  color: var(--color-primary);
  font-size: 0.875rem;
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

.stat-value.bricks-value {
  color: #f59e0b;
}

/* Share and Copy Buttons */
.btn-share,
.btn-copy {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-4);
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9375rem;
}

.btn-share {
  background: linear-gradient(135deg, var(--color-primary) 0%, #667eea 100%);
  color: white;
}

.btn-share:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-copy {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}

.btn-copy:hover {
  background: var(--color-bg-primary);
  border-color: var(--color-primary);
}

@media (max-width: 768px) {
  .ai-stats .stats-grid {
    grid-template-columns: 1fr;
  }
  
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
  
  /* Mobile modal layout */
  .modal-overlay {
    padding: 0 !important;
    align-items: flex-start !important;
  }
  
  .modal-content {
    max-height: 100vh !important;
    height: 100vh !important;
    max-width: 100% !important;
    border-radius: 0 !important;
    touch-action: pan-y !important;
  }
  
  .modal-body {
    -webkit-overflow-scrolling: touch;
    touch-action: pan-y;
    overflow-y: scroll !important;
  }
  
  /* Mobile modal footer layout */
  .modal-footer {
    flex-direction: column;
    gap: var(--spacing-2);
    padding: var(--spacing-3);
  }
  
  .action-buttons-group {
    width: 100%;
    gap: var(--spacing-2);
  }
  
  .btn-share,
  .btn-copy {
    flex: 1;
    padding: var(--spacing-3);
    font-size: 0.875rem;
    white-space: nowrap;
    touch-action: manipulation;
  }
  
  .modal-footer .btn-secondary {
    width: 100%;
    padding: var(--spacing-3);
    touch-action: manipulation;
  }
  
  .btn-close {
    touch-action: manipulation;
  }
  
  /* Mobile toast positioning - top center */
  .toast-notification {
    top: 1rem;
    left: 50%;
    transform: translateX(-50%);
    right: auto;
    bottom: auto;
    width: auto;
    max-width: calc(100% - 3rem);
    min-height: auto; /* No min height */
    max-height: 2.5rem; /* Limit height */
    padding: 0.4rem 0.75rem; /* Very tight padding */
    font-size: 0.75rem; /* Even smaller font (12px) */
    line-height: 1.4; /* Tighter line height */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    animation: slideInDown 0.3s ease-out, fadeOut 0.3s ease-in 1.7s;
  }
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  padding: 1rem 1.5rem;
  border-radius: var(--radius-md);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  font-weight: 600;
  font-size: 0.9375rem;
  z-index: 10100;
  animation: slideInUp 0.3s ease-out, fadeOut 0.3s ease-in 1.7s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  pointer-events: none; /* Don't block clicks */
}

@keyframes slideInUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes slideInDown {
  from {
    transform: translateX(-50%) translateY(-100%);
    opacity: 0;
  }
  to {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
  }
}

@keyframes fadeOut {
  from {
    opacity: 1;
  }
  to {
    opacity: 0;
  }
}
</style>
