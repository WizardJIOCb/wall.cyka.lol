<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['toast', `toast-${toast.type}`]"
        >
          <div class="toast-content">
            <span class="toast-icon">{{ getIcon(toast.type) }}</span>
            <span class="toast-message">{{ toast.message }}</span>
          </div>
          
          <div class="toast-actions">
            <button
              v-if="toast.action"
              @click="handleAction(toast)"
              class="toast-action-btn"
            >
              {{ toast.action.label }}
            </button>
            
            <button
              @click="remove(toast.id)"
              class="toast-close-btn"
              aria-label="Close"
            >
              ×
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useToast, type Toast, type ToastType } from '@/composables/useToast'

const { toasts, remove } = useToast()

const getIcon = (type: ToastType): string => {
  const icons: Record<ToastType, string> = {
    success: '✓',
    error: '✕',
    warning: '⚠',
    info: 'ℹ'
  }
  return icons[type]
}

const handleAction = (toast: Toast) => {
  if (toast.action) {
    toast.action.handler()
    remove(toast.id)
  }
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: var(--spacing-4);
  right: var(--spacing-4);
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
  max-width: 400px;
}

.toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-3);
  padding: var(--spacing-4);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border-left: 4px solid;
  min-width: 300px;
}

.toast-success {
  border-left-color: #10b981;
  background: #f0fdf4;
}

.toast-error {
  border-left-color: #ef4444;
  background: #fef2f2;
}

.toast-warning {
  border-left-color: #f59e0b;
  background: #fffbeb;
}

.toast-info {
  border-left-color: #3b82f6;
  background: #eff6ff;
}

.toast-content {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  flex: 1;
}

.toast-icon {
  font-size: 1.25rem;
  font-weight: 700;
  flex-shrink: 0;
}

.toast-success .toast-icon {
  color: #10b981;
}

.toast-error .toast-icon {
  color: #ef4444;
}

.toast-warning .toast-icon {
  color: #f59e0b;
}

.toast-info .toast-icon {
  color: #3b82f6;
}

.toast-message {
  color: var(--color-text-primary);
  font-size: 0.938rem;
  line-height: 1.5;
}

.toast-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.toast-action-btn {
  padding: var(--spacing-1) var(--spacing-3);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.toast-action-btn:hover {
  background: var(--color-primary-dark);
}

.toast-close-btn {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.5rem;
  color: var(--color-text-secondary);
  transition: all 0.2s;
}

.toast-close-btn:hover {
  background: rgba(0, 0, 0, 0.1);
  color: var(--color-text-primary);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

@media (max-width: 768px) {
  .toast-container {
    left: var(--spacing-4);
    right: var(--spacing-4);
    max-width: none;
  }
  
  .toast {
    min-width: auto;
  }
}
</style>
