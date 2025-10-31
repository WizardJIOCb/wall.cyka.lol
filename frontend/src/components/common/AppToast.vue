<template>
  <Teleport to="#toast-container">
    <TransitionGroup name="toast" tag="div">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['toast', `toast-${toast.type}`]"
        role="alert"
      >
        <div class="toast-icon">{{ getIcon(toast.type) }}</div>
        <div class="toast-message">{{ toast.message }}</div>
        <button class="toast-close" @click="removeToast(toast.id)">✕</button>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { ToastMessage, ToastType } from '@/types/components'
import { generateId } from '@/utils/helpers'
import { TOAST_DURATION } from '@/utils/constants'

const toasts = ref<ToastMessage[]>([])

const getIcon = (type: ToastType): string => {
  const icons = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  }
  return icons[type]
}

const addToast = (message: string, type: ToastType = 'info', duration: number = TOAST_DURATION.MEDIUM) => {
  const id = generateId()
  const toast: ToastMessage = { id, message, type, duration }
  
  toasts.value.push(toast)

  if (duration > 0) {
    setTimeout(() => {
      removeToast(id)
    }, duration)
  }

  return id
}

const removeToast = (id: string) => {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    toasts.value.splice(index, 1)
  }
}

const success = (message: string, duration: number = TOAST_DURATION.MEDIUM) => addToast(message, 'success', duration)
const error = (message: string, duration: number = TOAST_DURATION.MEDIUM) => addToast(message, 'error', duration)
const warning = (message: string, duration: number = TOAST_DURATION.MEDIUM) => addToast(message, 'warning', duration)
const info = (message: string, duration: number = TOAST_DURATION.MEDIUM) => addToast(message, 'info', duration)

defineExpose({
  success,
  error,
  warning,
  info,
  addToast,
  removeToast
})
</script>

<style scoped>
.toast {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--surface);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  min-width: 300px;
  max-width: 500px;
  margin-bottom: var(--spacing-3);
  border-left: 4px solid;
}

.toast-success {
  border-color: var(--success);
}

.toast-error {
  border-color: var(--danger);
}

.toast-warning {
  border-color: var(--warning);
}

.toast-info {
  border-color: var(--info);
}

.toast-icon {
  font-size: 1.25rem;
  font-weight: bold;
  flex-shrink: 0;
}

.toast-success .toast-icon {
  color: var(--success);
}

.toast-error .toast-icon {
  color: var(--danger);
}

.toast-warning .toast-icon {
  color: var(--warning);
}

.toast-info .toast-icon {
  color: var(--info);
}

.toast-message {
  flex: 1;
  color: var(--text-primary);
  font-size: 0.95rem;
}

.toast-close {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  font-size: 1.25rem;
  padding: 0;
  line-height: 1;
  flex-shrink: 0;
  transition: color 0.2s ease;
}

.toast-close:hover {
  color: var(--text-primary);
}

/* Transitions */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>

<style>
/* Global toast container styles */
#toast-container,
.toast-container {
  position: fixed;
  top: var(--spacing-4);
  right: var(--spacing-4);
  z-index: 9999;
  pointer-events: none;
}

#toast-container > *,
.toast-container > * {
  pointer-events: all;
}

@media (max-width: 640px) {
  #toast-container,
  .toast-container {
    left: var(--spacing-4);
    right: var(--spacing-4);
  }

  .toast {
    min-width: auto;
    max-width: none;
  }
}
</style>
