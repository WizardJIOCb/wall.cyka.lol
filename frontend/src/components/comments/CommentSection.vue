<template>
  <div class="comment-section">
    <div class="comment-section-header">
      <h3 class="section-title">
        {{ t('comments.title') }} ({{ totalCount }})
      </h3>
      
      <div class="comment-controls">
        <select v-model="sortBy" class="sort-select" @change="fetchComments">
          <option value="created_asc">{{ t('comments.sortOldest') }}</option>
          <option value="created_desc">{{ t('comments.sortNewest') }}</option>
          <option value="reactions">{{ t('comments.sortReactions') }}</option>
        </select>
      </div>
    </div>
    
    <div v-if="allowComments" class="comment-form-container">
      <CommentForm
        :post-id="postId"
        :show-cancel="false"
        @comment-created="onCommentCreated"
      />
    </div>
    
    <div v-if="loading && comments.length === 0" class="loading-state">
      <div class="spinner"></div>
      <p>{{ t('comments.loading') }}</p>
    </div>
    
    <div v-else-if="comments.length === 0" class="empty-state">
      <p>{{ t('comments.noComments') }}</p>
    </div>
    
    <div v-else class="comments-list">
      <CommentItem
        v-for="comment in topLevelComments"
        :key="comment.comment_id"
        :comment="comment"
        :post-id="postId"
        :depth="0"
        :max-depth="4"
        @reply-created="onReplyCreated"
        @comment-updated="onCommentUpdated"
        @comment-deleted="onCommentDeleted"
      />
    </div>
    
    <button 
      v-if="hasMore && !loading"
      @click="loadMore"
      class="load-more-btn"
    >
      {{ t('comments.loadMore') }}
    </button>
    
    <div v-if="loading && comments.length > 0" class="loading-more">
      <div class="spinner-small"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import apiClient from '@/services/api/client'
import CommentForm from './CommentForm.vue'
import CommentItem from './CommentItem.vue'

interface Comment {
  comment_id: number
  post_id: number
  author_id: number
  author_username: string
  author_name: string
  author_avatar: string | null
  parent_comment_id: number | null
  content_text: string
  content_html: string
  depth_level: number
  reply_count: number
  reaction_count: number
  like_count: number
  dislike_count: number
  is_edited: boolean
  is_hidden: boolean
  replies?: Comment[]
  created_at: string
  updated_at: string
  user_reaction?: string | null
}

interface Props {
  postId: number
  initialComments?: Comment[]
  allowComments?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  allowComments: true,
  initialComments: () => []
})

const { t } = useI18n()

// State
const comments = ref<Comment[]>(props.initialComments)
const loading = ref(false)
const hasMore = ref(false)
const sortBy = ref('created_asc')
const totalCount = ref(0)
const pollInterval = ref<number | null>(null)

// Computed
const topLevelComments = computed(() => {
  return comments.value.filter(c => c && !c.parent_comment_id)
})

// Methods
const fetchComments = async () => {
  loading.value = true
  
  try {
    const response = await apiClient.get(`/posts/${props.postId}/comments`, {
      params: {
        sort: sortBy.value,
        limit: 50
      }
    })
    
    // The API client interceptor already unwraps the response
    // response is already the data part: { comments: [...], count: 6, has_more: false }
    // Make sure we have valid data before accessing properties
    if (response && response.comments && Array.isArray(response.comments)) {
      comments.value = response.comments.filter((comment: any) => 
        comment !== null && 
        typeof comment === 'object' && 
        comment.hasOwnProperty('comment_id')
      )
      hasMore.value = response.has_more || false
      totalCount.value = response.count || 0
    } else {
      comments.value = []
      hasMore.value = false
      totalCount.value = 0
    }
    
    // Build comment tree
    buildCommentTree()
    
  } catch (error) {
    console.error('Failed to fetch comments:', error)
    comments.value = []
    hasMore.value = false
    totalCount.value = 0
  } finally {
    loading.value = false
  }
}

const buildCommentTree = () => {
  // Create a map for quick lookup
  const commentMap = new Map<number, Comment>()
  comments.value.forEach(comment => {
    if (comment) {
      comment.replies = []
      commentMap.set(comment.comment_id, comment)
    }
  })
  
  // Build tree structure
  comments.value.forEach(comment => {
    if (comment && comment.parent_comment_id) {
      const parent = commentMap.get(comment.parent_comment_id)
      if (parent && parent.replies) {
        parent.replies.push(comment)
      }
    }
  })
}

const loadMore = async () => {
  // In a real implementation, this would use offset/cursor pagination
  // For now, just a placeholder
  console.log('Load more comments')
}

const onCommentCreated = (comment: Comment) => {
  // Add new comment to the beginning
  if (comment) {
    comments.value.unshift(comment)
    totalCount.value++
    buildCommentTree()
  }
}

const onReplyCreated = (reply: Comment) => {
  // Reply is already added to parent in CommentItem
  if (reply) {
    totalCount.value++
  }
}

const onCommentUpdated = (updatedComment: Comment) => {
  if (updatedComment) {
    const index = comments.value.findIndex(c => c && c.comment_id === updatedComment.comment_id)
    if (index !== -1) {
      comments.value[index] = { ...comments.value[index], ...updatedComment }
      buildCommentTree()
    }
  }
}

const onCommentDeleted = (commentId: number) => {
  comments.value = comments.value.filter(c => c && c.comment_id !== undefined && c.comment_id !== commentId)
  totalCount.value--
  buildCommentTree()
}

const startPolling = () => {
  // Poll for new comments every 10 seconds
  pollInterval.value = window.setInterval(() => {
    if (!loading.value) {
      fetchComments()
    }
  }, 10000)
}

const stopPolling = () => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
}

// Lifecycle
onMounted(() => {
  if (comments.value.length === 0) {
    fetchComments()
  } else {
    buildCommentTree()
    totalCount.value = comments.value.length
  }
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.comment-section {
  width: 100%;
  background: var(--bg-primary);
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
  margin-top: var(--spacing-md);
}

.comment-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
  flex-wrap: wrap;
  gap: var(--spacing-sm);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.comment-controls {
  display: flex;
  gap: var(--spacing-sm);
  align-items: center;
}

.sort-select {
  padding: var(--spacing-xs) var(--spacing-sm);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  background: var(--bg-primary);
  color: var(--text-primary);
  font-size: 0.9rem;
  cursor: pointer;
}

.sort-select:focus {
  outline: none;
  border-color: var(--primary);
}

.comment-form-container {
  margin-bottom: var(--spacing-md);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
}

.comments-list {
  display: flex;
  flex-direction: column;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
  color: var(--text-secondary);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border-color);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner-small {
  width: 24px;
  height: 24px;
  border: 2px solid var(--border-color);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: var(--spacing-md) auto;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state p {
  margin-top: var(--spacing-sm);
  font-size: 1rem;
}

.load-more-btn {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  margin-top: var(--spacing-md);
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.load-more-btn:hover {
  background: var(--bg-tertiary);
  border-color: var(--primary);
}

.loading-more {
  display: flex;
  justify-content: center;
  padding: var(--spacing-md);
}

@media (max-width: 768px) {
  .comment-section {
    padding: var(--spacing-sm);
  }
  
  .section-title {
    font-size: 1.1rem;
  }
  
  .comment-section-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .comment-controls {
    width: 100%;
  }
  
  .sort-select {
    width: 100%;
  }
}
</style>