<template>
  <form @submit.prevent="submitComment" class="comment-form">
    <div class="form-group">
      <textarea
        ref="textareaRef"
        v-model="content"
        :placeholder="placeholder || t('comments.write')"
        :maxlength="2000"
        :rows="rows"
        @input="autoResize"
        @keydown.meta.enter="submitComment"
        @keydown.ctrl.enter="submitComment"
        class="comment-textarea"
      ></textarea>
      
      <div class="form-footer">
        <span 
          class="char-count" 
          :class="{ warning: charCount > 1800, error: charCount > 2000 }"
        >
          {{ charCount }} / 2000
        </span>
        
        <div class="form-actions">
          <button 
            v-if="showCancel"
            type="button"
            @click="cancel"
            class="btn btn-secondary"
            :disabled="isSubmitting"
          >
            {{ t('comments.cancel') }}
          </button>
          <button 
            type="submit"
            :disabled="!canSubmit || isSubmitting"
            class="btn btn-primary"
          >
            {{ isSubmitting ? t('comments.posting') : submitButtonText }}
          </button>
        </div>
      </div>
    </div>
    
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

interface Props {
  postId?: number
  parentCommentId?: number
  commentId?: number
  initialContent?: string
  placeholder?: string
  rows?: number
  showCancel?: boolean
}

interface Emits {
  (e: 'comment-created', comment: any): void
  (e: 'comment-updated', comment: any): void
  (e: 'cancel'): void
}

const props = withDefaults(defineProps<Props>(), {
  rows: 3,
  showCancel: true
})

const emit = defineEmits<Emits>()
const { t } = useI18n()

// State
const content = ref(props.initialContent || '')
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

// Computed
const charCount = computed(() => content.value.length)

const canSubmit = computed(() => {
  const trimmed = content.value.trim()
  return trimmed.length > 0 && trimmed.length <= 2000 && !isSubmitting.value
})

const submitButtonText = computed(() => {
  if (props.commentId) return t('comments.updateComment')
  if (props.parentCommentId) return t('comments.reply')
  return t('comments.comment')
})

// Methods
const autoResize = (event?: Event) => {
  const textarea = textareaRef.value
  if (!textarea) return
  
  textarea.style.height = 'auto'
  textarea.style.height = textarea.scrollHeight + 'px'
}

const submitComment = async () => {
  if (!canSubmit.value) return
  
  isSubmitting.value = true
  error.value = null
  
  try {
    let response
    
    if (props.commentId) {
      // Edit existing comment
      response = await api.patch(`/comments/${props.commentId}`, {
        content: content.value.trim()
      })
      emit('comment-updated', response.data.data)
    } else if (props.parentCommentId) {
      // Create reply
      response = await api.post(
        `/comments/${props.parentCommentId}/replies`,
        { content: content.value.trim() }
      )
      emit('comment-created', response.data.data)
    } else if (props.postId) {
      // Create top-level comment
      response = await api.post(`/posts/${props.postId}/comments`, {
        content: content.value.trim()
      })
      emit('comment-created', response.data.data)
    }
    
    // Clear form
    content.value = ''
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
    }
    
  } catch (err: any) {
    error.value = err.response?.data?.message || t('comments.errorPosting')
  } finally {
    isSubmitting.value = false
  }
}

const cancel = () => {
  content.value = props.initialContent || ''
  error.value = null
  emit('cancel')
}

// Watch for initial content changes
watch(() => props.initialContent, (newVal) => {
  content.value = newVal || ''
})

// Auto-resize on mount
onMounted(() => {
  autoResize()
})
</script>

<style scoped>
.comment-form {
  width: 100%;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.comment-textarea {
  width: 100%;
  min-height: 60px;
  max-height: 300px;
  padding: var(--spacing-sm);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-family: inherit;
  font-size: 0.95rem;
  line-height: 1.5;
  background: var(--bg-primary);
  color: var(--text-primary);
  resize: vertical;
  transition: border-color 0.2s;
}

.comment-textarea:focus {
  outline: none;
  border-color: var(--primary);
}

.comment-textarea::placeholder {
  color: var(--text-tertiary);
}

.form-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.char-count {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.char-count.warning {
  color: var(--warning);
}

.char-count.error {
  color: var(--error);
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: var(--spacing-xs);
}

.btn {
  padding: var(--spacing-xs) var(--spacing-md);
  border: none;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--bg-tertiary);
}

.error-message {
  margin-top: var(--spacing-xs);
  padding: var(--spacing-xs) var(--spacing-sm);
  background: var(--error-bg);
  color: var(--error);
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .comment-textarea {
    font-size: 16px; /* Prevent zoom on iOS */
  }
}
</style>
