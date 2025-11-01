import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
  id: string
  type: ToastType
  message: string
  duration?: number
  action?: {
    label: string
    handler: () => void
  }
}

const toasts = ref<Toast[]>([])
let idCounter = 0

export function useToast() {
  const show = (
    message: string,
    type: ToastType = 'info',
    duration: number = 5000,
    action?: { label: string; handler: () => void }
  ) => {
    const id = `toast-${++idCounter}`
    const toast: Toast = {
      id,
      type,
      message,
      duration,
      action
    }

    toasts.value.push(toast)

    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }

    return id
  }

  const remove = (id: string) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  const success = (message: string, duration?: number) => {
    return show(message, 'success', duration)
  }

  const error = (message: string, duration?: number, retryHandler?: () => void) => {
    const action = retryHandler ? { label: 'Retry', handler: retryHandler } : undefined
    return show(message, 'error', duration, action)
  }

  const warning = (message: string, duration?: number) => {
    return show(message, 'warning', duration)
  }

  const info = (message: string, duration?: number) => {
    return show(message, 'info', duration)
  }

  const clear = () => {
    toasts.value = []
  }

  return {
    toasts,
    show,
    remove,
    success,
    error,
    warning,
    info,
    clear
  }
}
