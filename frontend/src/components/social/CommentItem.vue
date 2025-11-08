<template>
  <div v-if="comment && comment.comment_id" :class="['comment-item', { 'is-reply': depth > 0 }]" :style="{ marginLeft: `${depth * 40}px` }">
    <!-- Comment Header -->
    <div class="comment-header">
      <img 
        :src="comment.author_avatar || '/assets/images/default-avatar.svg'" 
        :alt="comment.author_username"
        class="comment-avatar"
      />
      
      <div class="comment-meta">
        <span class="comment-author">{{ comment.author_username }}</span>
        <span class="comment-time">{{ formatTime(comment.created_at) }}</span>
        <span v-if="comment.edited_at" class="comment-edited">(edited)</span>
      </div>
    </div>

    <!-- Comment Body -->
    <div class="comment-body">
      <div v-if="isEditing" class="comment-edit">
        <CommentInput
          :post-id="comment.post_id"
          :initial-value="comment.content_text"
          :placeholder="'Edit your comment...'"
          @submit="handleEditSubmit"
          @cancel="cancelEdit"
        />
      </div>
      
      <div v-else class="comment-content">
        <div v-if="comment.content_html" v-html="comment.content_html"></div>
        <p v-else>{{ comment.content_text }}</p>
      </div>
    </div>

    <!-- Comment Actions -->
    <div v-if="!isEditing" class="comment-actions">
      <ReactionPicker
        :reactable-type="'comment'"
        :reactable-id="comment.comment_id"
      />
      
      <button 
        v-if="canReply"
        @click="toggleReply" 
        class="action-btn"
      >
        <span class="icon">💬</span>
        <span>Reply</span>
        <span v-if="comment.reply_count > 0">({{ comment.reply_count }})</span>
      </button>
      
      <button 
        v-if="canEdit"
        @click="startEdit" 
        class="action-btn"
      >
        <span class="icon">✏️</span>
        <span>Edit</span>
      </button>
      
      <button 
        v-if="canDelete"
        @click="handleDelete" 
        class="action-btn delete"
      >
        <span class="icon">🗑️</span>
        <span>Delete</span>
      </button>
    </div>

    <!-- Reply Input -->
    <div v-if="showReplyInput" class="reply-input">
      <CommentInput
        :post-id="comment.post_id"
        :parent-id="comment.comment_id"
        :placeholder="'Write a reply...'"
        @submit="handleReplySubmit"
        @cancel="toggleReply"
      />
    </div>

    <!-- Nested Replies -->
    <div v-if="showReplies && replies.length > 0" class="comment-replies">
      <CommentItem
        v-for="reply in replies"
        :key="reply.comment_id"
        :comment="reply"
        :post-id="postId"
        :depth="depth + 1"
        :max-depth="maxDepth"
        @reply-added="handleReplyAdded"
        @comment-updated="$emit('comment-updated', $event)"
        @comment-deleted="$emit('comment-deleted', $event)"
      />
    </div>

    <!-- Load Replies Button -->
    <div v-if="comment.reply_count > 0 && !showReplies && !loadingReplies" class="load-replies">
      <button @click="loadReplies" class="btn-load-replies">
        <span class="icon">↳</span>
        <span>Show {{ comment.reply_count }} {{ comment.reply_count === 1 ? 'reply' : 'replies' }}</span>
      </button>
    </div>

    <!-- Loading Replies -->
    <div v-if="loadingReplies" class="loading-replies">
      <div class="spinner"></div>
      <span>Loading replies...</span>
    </div>

    <!-- View Full Thread Link (if max depth reached) -->
    <div v-if="depth >= maxDepth && comment.reply_count > 0" class="view-full-thread">
      <button @click="viewFullThread" class="btn-view-thread">
        View full thread →
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useComments, type Comment } from '@/composables/useComments'
import CommentInput from './CommentInput.vue'
import ReactionPicker from './ReactionPicker.vue'

const props = defineProps<{
  comment: Comment
  postId: number
  depth?: number
  maxDepth?: number
}>()

const emit = defineEmits<{
  (e: 'reply-added', reply: Comment): void
  (e: 'comment-updated', comment: Comment): void
  (e: 'comment-deleted', commentId: number): void
}>()

// Add a safety check for the comment prop
if (!props.comment) {
  console.warn('CommentItem: comment prop is required')
}

const authStore = useAuthStore()
const { addComment, updateComment, deleteComment, loadReplies: loadCommentReplies } = useComments(ref(props.postId))

const depth = computed(() => props.depth || 0)
const maxDepth = computed(() => props.maxDepth || 3)
const isEditing = ref(false)
const showReplyInput = ref(false)
const showReplies = ref(false)
const loadingReplies = ref(false)
const replies = ref<Comment[]>([])

const canEdit = computed(() => {
  return props.comment && authStore.user?.user_id === props.comment.author_id
})

const canDelete = computed(() => {
  return props.comment && authStore.user?.user_id === props.comment.author_id
})

const canReply = computed(() => {
  return props.comment && depth.value < maxDepth.value
})

const formatTime = (dateString: string) => {
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

const startEdit = () => {
  isEditing.value = true
}

const cancelEdit = () => {
  isEditing.value = false
}

const handleEditSubmit = async (text: string) => {
  try {
    if (!props.comment) return
    const updated = await updateComment(props.comment.comment_id, {
      content_text: text
    })
    if (updated) {
      isEditing.value = false
      emit('comment-updated', updated)
    }
  } catch (error) {
    console.error('Failed to update comment:', error)
  }
}

const handleDelete = async () => {
  if (!props.comment || !confirm('Delete this comment?')) return
  
  try {
    await deleteComment(props.comment.comment_id)
    emit('comment-deleted', props.comment.comment_id)
  } catch (error) {
    console.error('Failed to delete comment:', error)
  }
}

const toggleReply = () => {
  showReplyInput.value = !showReplyInput.value
}

const handleReplySubmit = async (text: string) => {
  try {
    if (!props.comment) return
    const reply = await addComment({
      post_id: props.postId,
      content_text: text,
      parent_id: props.comment.comment_id
    })
    
    if (reply) {
      showReplyInput.value = false
      replies.value.unshift(reply)
      showReplies.value = true
      emit('reply-added', reply)
    }
  } catch (error) {
    console.error('Failed to add reply:', error)
  }
}

const loadReplies = async () => {
  if (!props.comment) return
  loadingReplies.value = true
  try {
    replies.value = await loadCommentReplies(props.comment.comment_id)
    showReplies.value = true
  } catch (error) {
    console.error('Failed to load replies:', error)
  } finally {
    loadingReplies.value = false
  }
}

const handleReplyAdded = (reply: Comment) => {
  emit('reply-added', reply)
}

const viewFullThread = () => {
  if (!props.comment) return
  // Navigate to full thread view
  console.log('View full thread for comment:', props.comment.comment_id)
}
</script>

<style scoped>
.comment-item {
  padding: var(--spacing-4);
  border-radius: var(--radius-md);
  transition: background 0.2s;
}

.comment-item:hover {
  background: var(--color-bg-secondary);
}

.comment-item.is-reply {
  padding-top: var(--spacing-3);
  padding-bottom: var(--spacing-3);
}

.comment-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  margin-bottom: var(--spacing-3);
}

.comment-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.comment-item.is-reply .comment-avatar {
  width: 28px;
  height: 28px;
}

.comment-meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  flex-wrap: wrap;
}

.comment-author {
  font-weight: 600;
  color: var(--color-text-primary);
}

.comment-time,
.comment-edited {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.comment-body {
  margin-bottom: var(--spacing-3);
  padding-left: calc(36px + var(--spacing-3));
}

.comment-item.is-reply .comment-body {
  padding-left: calc(28px + var(--spacing-3));
}

.comment-content {
  color: var(--color-text-primary);
  line-height: 1.6;
  word-wrap: break-word;
}

.comment-actions {
  display: flex;
  gap: var(--spacing-2);
  padding-left: calc(36px + var(--spacing-3));
  flex-wrap: wrap;
}

.comment-item.is-reply .comment-actions {
  padding-left: calc(28px + var(--spacing-3));
}

.action-btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-1);
  padding: var(--spacing-1) var(--spacing-2);
  background: transparent;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
}

.action-btn.delete:hover {
  background: #fee2e2;
  color: #dc2626;
}

.reply-input,
.comment-edit {
  margin-top: var(--spacing-3);
}

.comment-replies {
  margin-top: var(--spacing-2);
}

.load-replies,
.view-full-thread {
  margin-top: var(--spacing-2);
  padding-left: calc(36px + var(--spacing-3));
}

.btn-load-replies,
.btn-view-thread {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2);
  background: transparent;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  color: var(--color-primary);
  font-weight: 600;
  transition: all 0.2s;
}

.btn-load-replies:hover,
.btn-view-thread:hover {
  background: rgba(99, 102, 241, 0.1);
}

.loading-replies {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3);
  padding-left: calc(36px + var(--spacing-3));
  color: var(--color-text-secondary);
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(0, 0, 0, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>