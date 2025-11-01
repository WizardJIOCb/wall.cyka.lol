<template>
  <div class="comment-input-container">
    <div class="comment-input-wrapper">
      <img 
        :src="userAvatar" 
        :alt="username"
        class="user-avatar"
      />
      
      <div class="input-area">
        <textarea
          v-model="commentText"
          ref="textareaRef"
          :placeholder="placeholder"
          @keydown.enter.exact.prevent="handleSubmit"
          @input="adjustHeight"
          class="comment-textarea"
          rows="1"
        ></textarea>
        
        <div class="input-actions">
          <button 
            @click="handleSubmit" 
            :disabled="!commentText.trim() || isSubmitting"
            class="btn-submit"
          >
            {{ isSubmitting ? 'Posting...' : 'Post' }}
          </button>
          
          <button 
            v-if="onCancel"
            @click="handleCancel" 
            class="btn-cancel"
            :disabled="isSubmitting"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  postId: number
  parentId?: number | null
  placeholder?: string
  onCancel?: () => void
  initialValue?: string
}>()

const emit = defineEmits<{
  (e: 'submit', text: string): void
  (e: 'cancel'): void
}>()

const authStore = useAuthStore()
const commentText = ref(props.initialValue || '')
const isSubmitting = ref(false)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const userAvatar = computed(() => {
  return authStore.user?.avatar_url || '/assets/images/default-avatar.svg'
})

const username = computed(() => {
  return authStore.user?.username || 'User'
})

const adjustHeight = () => {
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
      textareaRef.value.style.height = `${textareaRef.value.scrollHeight}px`
    }
  })
}

const handleSubmit = async () => {
  if (!commentText.value.trim() || isSubmitting.value) return
  
  isSubmitting.value = true
  try {
    emit('submit', commentText.value.trim())
    commentText.value = ''
    adjustHeight()
  } finally {
    isSubmitting.value = false
  }
}

const handleCancel = () => {
  commentText.value = ''
  adjustHeight()
  if (props.onCancel) {
    props.onCancel()
  }
  emit('cancel')
}

const focus = () => {
  nextTick(() => {
    textareaRef.value?.focus()
  })
}

defineExpose({
  focus
})
</script>

<style scoped>
.comment-input-container {
  padding: var(--spacing-4);
}

.comment-input-wrapper {
  display: flex;
  gap: var(--spacing-3);
  align-items: flex-start;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.input-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

.comment-textarea {
  width: 100%;
  min-height: 40px;
  max-height: 200px;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-elevated);
  color: var(--color-text-primary);
  font-family: inherit;
  font-size: 0.938rem;
  line-height: 1.5;
  resize: none;
  transition: all 0.2s;
}

.comment-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
}

.comment-textarea::placeholder {
  color: var(--color-text-secondary);
}

.input-actions {
  display: flex;
  gap: var(--spacing-2);
  justify-content: flex-end;
}

.btn-submit,
.btn-cancel {
  padding: var(--spacing-2) var(--spacing-4);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-submit {
  background: var(--color-primary);
  color: white;
}

.btn-submit:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-cancel {
  background: transparent;
  color: var(--color-text-secondary);
  border: 2px solid var(--color-border);
}

.btn-cancel:hover:not(:disabled) {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}
</style>
