<template>
  <div class="comment-section">
    <!-- Comment Section Header -->
    <div class="comments-header">
      <h3 class="comments-title">
        <span class="icon">💬</span>
        <span>Comments ({{ comments.length }})</span>
      </h3>
      
      <select v-model="sortBy" @change="handleSortChange" class="sort-select">
        <option value="newest">Newest First</option>
        <option value="oldest">Oldest First</option>
        <option value="popular">Most Popular</option>
      </select>
    </div>

    <!-- Add Comment Input -->
    <div class="add-comment">
      <CommentInput
        :post-id="postId"
        :placeholder="'Add a comment...'"
        @submit="handleCommentSubmit"
      />
    </div>

    <!-- Comments List -->
    <div class="comments-list">
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p>Loading comments...</p>
      </div>

      <div v-else-if="error" class="error-container">
        <p>{{ error }}</p>
        <button @click="loadComments(sortBy)" class="btn-retry">Retry</button>
      </div>

      <div v-else class="comments">
        <!-- Show all comments with debugging info -->
        <div v-for="(comment, index) in comments" :key="comment.comment_id || comment.id || index" class="comment-debug-wrapper">
          <div class="comment-debug-info">
            Comment #{{ index + 1 }}:
            <div>ID: {{ comment.comment_id || comment.id || 'N/A' }}</div>
            <div>Author: {{ comment.author_username || 'N/A' }}</div>
            <div>Content preview: {{ (comment.content_text || comment.content || 'N/A').substring(0, 50) }}...</div>
          </div>
          <CommentItem
            v-if="comment && (comment.comment_id || comment.id)"
            :comment="comment"
            :post-id="postId"
            :depth="0"
            :max-depth="maxDepth"
            @reply-added="handleReplyAdded"
            @comment-updated="handleCommentUpdated"
            @comment-deleted="handleCommentDeleted"
          />
        </div>
        
        <!-- Show message if no comments passed validation -->
        <div v-if="comments.length === 0" class="empty-state">
          <span class="empty-icon">💬</span>
          <p>No valid comments to display</p>
          <p class="empty-hint">Comments may be filtered due to validation</p>
          <!-- Show raw comments for debugging -->
          <div v-if="rawComments && rawComments.length > 0" class="debug-raw-comments">
            <h4>Raw Comments Data:</h4>
            <pre>{{ JSON.stringify(rawComments, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useComments } from '@/composables/useComments'
import type { Comment } from '@/types/comment'
import CommentInput from './CommentInput.vue'
import CommentItem from './CommentItem.vue'

const props = defineProps<{
  postId: number
  maxDepth?: number
}>()

const emit = defineEmits<{
  (e: 'comment-created', comment: Comment): void
  (e: 'comment-deleted', commentId: number): void
  (e: 'comment-updated', comment: Comment): void
}>()

// Add raw comments for debugging
const rawComments = ref<any[]>([])

const maxDepth = ref(props.maxDepth || 3)
const sortBy = ref<'newest' | 'oldest' | 'popular'>('newest')

const {
  comments,
  loading,
  error,
  loadComments,
  addComment
} = useComments(ref(props.postId))

// Watch for raw comments data
watch(comments, (newComments: Comment[]) => {
  rawComments.value = [...newComments] // Store a copy for debugging
  console.log('Comments updated in CommentSection:', newComments)
  console.log('Comments length in CommentSection:', newComments.length)
  if (newComments.length > 0) {
    console.log('First comment details:', {
      id: newComments[0].comment_id,
      type: typeof newComments[0].comment_id,
      content: newComments[0].content_text?.substring(0, 50)
    })
  }
  console.log('All comments:', JSON.stringify(newComments, null, 2))
})

const handleSortChange = () => {
  loadComments(sortBy.value)
}

const handleCommentSubmit = async (text: string) => {
  try {
    const newComment = await addComment({
      post_id: props.postId,
      content_text: text
    })
    
    if (newComment) {
      emit('comment-created', newComment)
    }
  } catch (error) {
    console.error('Failed to add comment:', error)
  }
}

const handleReplyAdded = (reply: Comment) => {
  emit('comment-created', reply)
}

const handleCommentUpdated = (comment: Comment) => {
  emit('comment-updated', comment)
}

const handleCommentDeleted = (commentId: number) => {
  emit('comment-deleted', commentId)
}

onMounted(() => {
  console.log('CommentSection mounted with postId:', props.postId)
  console.log('Initializing comments loading...')
  loadComments(sortBy.value)
})
</script>

<style scoped>
.comment-section {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.comments-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-4);
  border-bottom: 2px solid var(--color-border);
}

.comments-title {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.comments-title .icon {
  font-size: 1.5rem;
}

.sort-select {
  padding: var(--spacing-2) var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  cursor: pointer;
  font-weight: 600;
}

.add-comment {
  border-bottom: 2px solid var(--color-border);
}

.comments-list {
  min-height: 200px;
}

.loading-container,
.error-container,
.empty-state {
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

.error-container p {
  color: #dc2626;
  margin-bottom: var(--spacing-4);
}

.btn-retry {
  padding: var(--spacing-2) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-retry:hover {
  background: var(--color-primary-dark);
}

.empty-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: var(--spacing-3);
  opacity: 0.5;
}

.empty-state p {
  color: var(--color-text-secondary);
  margin: 0;
}

.empty-hint {
  font-size: 0.875rem;
  margin-top: var(--spacing-2);
}

.comments {
  display: flex;
  flex-direction: column;
}

.debug-raw-comments {
  background: #ffeb3b;
  color: #000;
  padding: 10px;
  border-radius: 4px;
  margin-top: 10px;
}

.debug-raw-comments h4 {
  margin: 0 0 10px 0;
}

.debug-raw-comments pre {
  background: #fff;
  padding: 10px;
  border-radius: 3px;
  max-height: 200px;
  overflow: auto;
  white-space: pre-wrap;
  font-size: 12px;
}

.comment-debug-wrapper {
  border: 1px solid #ff9800;
  margin: 5px 0;
  padding: 5px;
}

.comment-debug-info {
  background: #fff3e0;
  color: #e65100;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 5px;
}

.comment-debug-info pre {
  background: #fff;
  padding: 5px;
  border-radius: 3px;
  margin-top: 5px;
  white-space: pre-wrap;
}
</style>
