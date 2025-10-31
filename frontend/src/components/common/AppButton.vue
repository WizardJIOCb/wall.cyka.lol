<template>
  <button
    :type="type"
    :class="buttonClasses"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <span v-if="loading" class="btn-loader"></span>
    <slot v-else />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ButtonVariant, ButtonSize } from '@/types/components'

interface Props {
  variant?: ButtonVariant
  size?: ButtonSize
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  loading?: boolean
  block?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  disabled: false,
  loading: false,
  block: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const buttonClasses = computed(() => [
  'btn',
  `btn-${props.variant}`,
  `btn-${props.size}`,
  {
    'btn-block': props.block,
    'btn-loading': props.loading
  }
])

const handleClick = (event: MouseEvent) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-2);
  font-weight: 600;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: inherit;
  text-decoration: none;
  white-space: nowrap;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Sizes */
.btn-sm {
  padding: var(--spacing-1) var(--spacing-3);
  font-size: 0.875rem;
}

.btn-md {
  padding: var(--spacing-2) var(--spacing-4);
  font-size: 1rem;
}

.btn-lg {
  padding: var(--spacing-3) var(--spacing-6);
  font-size: 1.125rem;
}

/* Variants */
.btn-primary {
  background: var(--primary);
  color: var(--primary-text);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
}

.btn-secondary {
  background: var(--secondary);
  color: var(--secondary-text);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--secondary-hover);
}

.btn-ghost {
  background: transparent;
  color: var(--text-primary);
}

.btn-ghost:hover:not(:disabled) {
  background: var(--surface-hover);
}

.btn-danger {
  background: var(--danger);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: var(--danger-hover);
}

.btn-success {
  background: var(--success);
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: var(--success-hover);
}

/* Block */
.btn-block {
  width: 100%;
}

/* Loading */
.btn-loader {
  width: 16px;
  height: 16px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
