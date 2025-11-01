<template>
  <button
    class="follow-button"
    :class="buttonClass"
    :disabled="isLoading || disabled"
    @click="toggleFollow"
    :aria-label="ariaLabel"
  >
    <span v-if="isLoading" class="loading-spinner"></span>
    <template v-else>
      <span class="button-icon">{{ buttonIcon }}</span>
      <span class="button-text">{{ buttonText }}</span>
    </template>
  </button>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { api } from '@/services/api'
import { useToast } from '@/composables/useToast'

interface Props {
  userId: number
  initialFollowState?: boolean
  size?: 'small' | 'medium' | 'large'
  variant?: 'primary' | 'secondary' | 'outline'
  showIcon?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialFollowState: false,
  size: 'medium',
  variant: 'primary',
  showIcon: true,
  disabled: false
})

const emit = defineEmits<{
  'follow': [userId: number]
  'unfollow': [userId: number]
  'update:followState': [isFollowing: boolean]
}>()

const { showToast } = useToast()

// State
const isFollowing = ref(props.initialFollowState)
const isLoading = ref(false)

// Computed
const buttonClass = computed(() => ({
  [`follow-button--${props.size}`]: true,
  [`follow-button--${props.variant}`]: true,
  'follow-button--following': isFollowing.value,
  'follow-button--loading': isLoading.value
}))

const buttonText = computed(() => {
  if (isLoading.value) return ''
  return isFollowing.value ? 'Following' : 'Follow'
})

const buttonIcon = computed(() => {
  if (!props.showIcon) return ''
  return isFollowing.value ? '✓' : '+'
})

const ariaLabel = computed(() => {
  return isFollowing.value 
    ? `Unfollow user ${props.userId}`
    : `Follow user ${props.userId}`
})

// Methods
const toggleFollow = async () => {
  if (isLoading.value || props.disabled) return

  const previousState = isFollowing.value
  
  // Optimistic update
  isFollowing.value = !isFollowing.value
  isLoading.value = true

  try {
    if (previousState) {
      // Unfollow
      await api.delete(`/users/${props.userId}/unfollow`)
      emit('unfollow', props.userId)
      emit('update:followState', false)
      showToast('Unfollowed successfully', 'success')
    } else {
      // Follow
      await api.post(`/users/${props.userId}/follow`)
      emit('follow', props.userId)
      emit('update:followState', true)
      showToast('Followed successfully', 'success')
    }
  } catch (error: any) {
    // Rollback on error
    isFollowing.value = previousState
    
    const errorMessage = error.response?.data?.message || 'Failed to update follow status'
    showToast(errorMessage, 'error')
    
    console.error('Follow toggle error:', error)
  } finally {
    isLoading.value = false
  }
}

// Expose methods for parent component
defineExpose({
  toggleFollow,
  isFollowing: computed(() => isFollowing.value),
  isLoading: computed(() => isLoading.value)
})
</script>

<style scoped>
.follow-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 600;
  border-radius: 0.5rem;
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.follow-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.follow-button:active:not(:disabled) {
  transform: translateY(0);
}

.follow-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Size variants */
.follow-button--small {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

.follow-button--medium {
  padding: 0.5rem 1rem;
  font-size: 1rem;
}

.follow-button--large {
  padding: 0.75rem 1.5rem;
  font-size: 1.125rem;
}

/* Style variants */
.follow-button--primary {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.follow-button--primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
}

.follow-button--primary.follow-button--following {
  background: var(--color-success);
  border-color: var(--color-success);
}

.follow-button--primary.follow-button--following:hover:not(:disabled) {
  background: var(--color-danger);
  border-color: var(--color-danger);
}

.follow-button--primary.follow-button--following:hover:not(:disabled) .button-text::after {
  content: ' (Click to unfollow)';
  font-size: 0.75em;
  opacity: 0.9;
}

.follow-button--secondary {
  background: var(--color-secondary);
  color: white;
  border-color: var(--color-secondary);
}

.follow-button--secondary:hover:not(:disabled) {
  background: var(--color-secondary-dark);
  border-color: var(--color-secondary-dark);
}

.follow-button--outline {
  background: transparent;
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.follow-button--outline:hover:not(:disabled) {
  background: var(--color-primary);
  color: white;
}

.follow-button--outline.follow-button--following {
  color: var(--color-success);
  border-color: var(--color-success);
}

.follow-button--outline.follow-button--following:hover:not(:disabled) {
  background: var(--color-danger);
  color: white;
  border-color: var(--color-danger);
}

/* Loading state */
.loading-spinner {
  display: inline-block;
  width: 1em;
  height: 1em;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.button-icon {
  font-size: 1.2em;
  line-height: 1;
}

.button-text {
  line-height: 1;
}

/* Responsive */
@media (max-width: 640px) {
  .follow-button--medium {
    padding: 0.4rem 0.8rem;
    font-size: 0.9rem;
  }

  .follow-button--large {
    padding: 0.6rem 1.2rem;
    font-size: 1rem;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .follow-button--outline {
    border-color: var(--color-primary-light);
    color: var(--color-primary-light);
  }

  .follow-button--outline:hover:not(:disabled) {
    background: var(--color-primary-light);
    color: var(--color-dark);
  }
}
</style>
