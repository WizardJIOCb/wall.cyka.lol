<template>
  <div 
    class="comment-item" 
    :class="`depth-${depth}`"
    :style="{ marginLeft: indentPixels }"
  >
    <div class="comment-header">
      <img 
        :src="comment.author_avatar || '/default-avatar.png'" 
        :alt="comment.author_name"
        class="avatar" 
      />
      <div class="comment-meta">
        <router-link 
          :to="`/users/${comment.author_id}`"
          class="author-name"
        >
          {{ comment.author_name }}
        </router-link>
        <span class="timestamp">{{ formatTime(comment.created_at) }}</span>
        <span v-if="comment.is_edited" class="edited-badge">
          {{ t('comments.edited') }}
        </span>
      </div>
      
      <div class="comment-actions" v-if="isOwner">
        <button 
          @click="startEdit"
          :disabled="!canEdit"
          :title="canEdit ? t('comments.edit') : t('comments.editExpired')"
          class="action-btn"
        >
          <span>✏️</span>
        </button>
        <button 
          @click="deleteComment"
          :title="t('comments.delete')"
          class="action-btn"
        >
          <span>🗑️</span>
        </button>
      </div>
    </div>
    
    <div class="comment-content">
      <p v-if="!isEditing" v-html="comment.content_html"></p>
      <CommentForm
        v-else
        :comment-id="comment.comment_id"
        :initial-content="comment.content_text"
        :show-cancel="true"
        :rows="2"
        @comment-updated="onUpdated"
        @cancel="isEditing = false"
      />
    </div>
    
    <div class="comment-footer">
      <button 
        class="reaction-btn"
        @click="toggleReactionPicker"
        :class="{ active: comment.user_reaction }"
      >
        <span v-if="comment.user_reaction" class="user-reaction">
          {{ getReactionEmoji(comment.user_reaction) }}
        </span>
        <span v-else>👍</span>
        <span v-if="comment.reaction_count > 0" class="count">
          {{ formatCount(comment.reaction_count) }}
        </span>
      </button>
      
      <button 
        v-if="depth < maxDepth"
        @click="toggleReply"
        class="reply-btn"
      >
        {{ t('comments.reply') }}
        <span v-if="comment.reply_count > 0" class="count">
          ({{ comment.reply_count }})
        </span>
      </button>
      
      <button 
        v-if="comment.reply_count > 0 && comment.replies"
        @click="toggleReplies"
        class="collapse-btn"
      >
        {{ repliesExpanded ? t('comments.hideReplies') : t('comments.showReplies') }}
      </button>
    </div>
    
    <!-- Reaction Picker -->
    <div 
      v-if="showReactionPicker"
      class="reaction-picker"
      @click.stop
    >
      <button
        v-for="reaction in reactionTypes"
        :key="reaction.type"
        @click="reactToComment(reaction.type)"
        class="reaction-option"
        :class="{ selected: comment.user_reaction === reaction.type }"
        :title="reaction.label"
      >
        {{ reaction.emoji }}
      </button>
      <button @click="showReactionPicker = false" class="close-picker">
        ✕
      </button>
    </div>
    
    <!-- Reply Form -->
    <div v-if="showReplyForm" class="reply-form">
      <CommentForm
        :post-id="postId"
        :parent-comment-id="comment.comment_id"
        :placeholder="t('comments.writeReply')"
        :rows="2"
        @comment-created="onReplyCreated"
        @cancel="showReplyForm = false"
      />
    </div>
    
    <!-- Nested Replies -->
    <div v-if="repliesExpanded && comment.replies && comment.replies.length > 0" class="replies">
      <CommentItem
        v-for="reply in comment.replies"
        :key="reply.comment_id"
        :comment="reply"
        :post-id="postId"
        :depth="depth + 1"
        :max-depth="maxDepth"
        @reply-created="$emit('reply-created', $event)"
        @comment-updated="$emit('comment-updated', $event)"
        @comment-deleted="$emit('comment-deleted', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api/client'
import CommentForm from './CommentForm.vue'

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
  comment: Comment
  postId: number
  depth?: number
  maxDepth?: number
}

interface Emits {
  (e: 'reply-created', comment: Comment): void
  (e: 'comment-updated', comment: Comment): void
  (e: 'comment-deleted', commentId: number): void
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
  maxDepth: 4
})

const emit = defineEmits<Emits>()
const { t } = useI18n()
const authStore = useAuthStore()

// State
const isEditing = ref(false)
const showReplyForm = ref(false)
const showReactionPicker = ref(false)
const repliesExpanded = ref(true)

// Reaction types
const reactionTypes = [
  { type: 'like', emoji: '👍', label: 'Like' },
  { type: 'dislike', emoji: '👎', label: 'Dislike' },
  { type: 'heart', emoji: '❤️', label: 'Love' },
  { type: 'laugh', emoji: '😂', label: 'Laugh' },
  { type: 'wow', emoji: '😮', label: 'Wow' },
  { type: 'sad', emoji: '😢', label: 'Sad' },
  { type: 'angry', emoji: '😠', label: 'Angry' }
]

// Computed
const isOwner = computed(() => {
  return authStore.user?.user_id === props.comment.author_id
})

const canEdit = computed(() => {
  const createdTime = new Date(props.comment.created_at).getTime()
  const now = Date.now()
  const minutesPassed = (now - createdTime) / 1000 / 60
  return minutesPassed < 15
})

const indentPixels = computed(() => {
  if (props.depth === 0) return '0'
  const baseIndent = window.innerWidth > 768 ? 24 : 12
  return `${props.depth * baseIndent}px`
})

// Methods
const formatTime = (timestamp: string) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return t('comments.justNow')
  if (diffMins < 60) return t('comments.minutesAgo', { count: diffMins })
  
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return t('comments.hoursAgo', { count: diffHours })
  
  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return t('comments.daysAgo', { count: diffDays })
  
  return date.toLocaleDateString()
}

const formatCount = (count: number) => {
  if (count >= 1000000) return (count / 1000000).toFixed(1) + 'M'
  if (count >= 1000) return (count / 1000).toFixed(1) + 'K'
  return count.toString()
}

const getReactionEmoji = (type: string) => {
  const reaction = reactionTypes.find(r => r.type === type)
  return reaction?.emoji || '👍'
}

const startEdit = () => {
  if (!canEdit.value) return
  isEditing.value = true
}

const deleteComment = async () => {
  if (!confirm(t('comments.deleteConfirm'))) return
  
  try {
    await apiClient.delete(`/comments/${props.comment.comment_id}`)
    emit('comment-deleted', props.comment.comment_id)
  } catch (error) {
    console.error('Failed to delete comment:', error)
  }
}

const toggleReply = () => {
  showReplyForm.value = !showReplyForm.value
  if (showReplyForm.value) {
    showReactionPicker.value = false
  }
}

const toggleReplies = () => {
  repliesExpanded.value = !repliesExpanded.value
}

const toggleReactionPicker = () => {
  showReactionPicker.value = !showReactionPicker.value
  if (showReactionPicker.value) {
    showReplyForm.value = false
  }
}

const reactToComment = async (reactionType: string) => {
  try {
    const response = await apiClient.post(
      `/comments/${props.comment.comment_id}/reactions`,
      { reaction_type: reactionType }
    )
    
    // Update local state
    props.comment.reaction_count = response.data.data.reaction_count
    props.comment.like_count = response.data.data.like_count
    props.comment.dislike_count = response.data.data.dislike_count
    
    // Update user's reaction
    if (response.data.data.action === 'removed') {
      props.comment.user_reaction = null
    } else {
      props.comment.user_reaction = reactionType
    }
    
    showReactionPicker.value = false
  } catch (error) {
    console.error('Failed to react:', error)
  }
}

const onUpdated = (updatedComment: Comment) => {
  isEditing.value = false
  emit('comment-updated', updatedComment)
}

const onReplyCreated = (reply: Comment) => {
  showReplyForm.value = false
  
  // Add reply to local state
  if (!props.comment.replies) {
    props.comment.replies = []
  }
  props.comment.replies.push(reply)
  props.comment.reply_count++
  
  emit('reply-created', reply)
}
</script>

<style scoped>
.comment-item {
  padding: var(--spacing-sm);
  border-left: 2px solid transparent;
  margin-bottom: var(--spacing-sm);
  transition: background-color 0.2s;
}

.comment-item:hover {
  background: var(--bg-secondary);
  border-left-color: var(--border-color);
}

.comment-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  margin-bottom: var(--spacing-xs);
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.comment-meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  flex: 1;
  flex-wrap: wrap;
}

.author-name {
  font-weight: 600;
  color: var(--text-primary);
  text-decoration: none;
}

.author-name:hover {
  color: var(--primary);
}

.timestamp {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.edited-badge {
  font-size: 0.8rem;
  color: var(--text-tertiary);
  font-style: italic;
}

.comment-actions {
  display: flex;
  gap: var(--spacing-xs);
}

.action-btn {
  background: transparent;
  border: none;
  padding: var(--spacing-xs);
  cursor: pointer;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.action-btn:hover:not(:disabled) {
  opacity: 1;
}

.action-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.comment-content {
  color: var(--text-primary);
  line-height: 1.6;
  margin-bottom: var(--spacing-sm);
  word-wrap: break-word;
}

.comment-content p {
  margin: 0;
}

.comment-footer {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}

.reaction-btn,
.reply-btn,
.collapse-btn {
  background: transparent;
  border: 1px solid var(--border-color);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 4px;
}

.reaction-btn:hover,
.reply-btn:hover,
.collapse-btn:hover {
  background: var(--bg-tertiary);
  border-color: var(--primary);
}

.reaction-btn.active {
  border-color: var(--primary);
  background: var(--primary-light);
}

.count {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.reaction-picker {
  position: absolute;
  margin-top: var(--spacing-xs);
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--spacing-xs);
  display: flex;
  gap: var(--spacing-xs);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10;
}

.reaction-option {
  background: transparent;
  border: 2px solid transparent;
  padding: var(--spacing-xs);
  border-radius: var(--radius-sm);
  font-size: 1.3rem;
  cursor: pointer;
  transition: all 0.2s;
}

.reaction-option:hover {
  background: var(--bg-secondary);
  transform: scale(1.2);
}

.reaction-option.selected {
  border-color: var(--primary);
  background: var(--primary-light);
}

.close-picker {
  background: var(--bg-secondary);
  border: none;
  padding: var(--spacing-xs);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 0.9rem;
}

.reply-form {
  margin-top: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
}

.replies {
  margin-top: var(--spacing-sm);
}

@media (max-width: 768px) {
  .avatar {
    width: 28px;
    height: 28px;
  }
  
  .comment-meta {
    font-size: 0.9rem;
  }
  
  .reaction-picker {
    flex-wrap: wrap;
    max-width: 280px;
  }
}
</style>
