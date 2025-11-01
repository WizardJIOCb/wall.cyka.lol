import { ref, onUnmounted } from 'vue'

export interface PollingConfig {
  interval: number
  endpoint?: string
  onUpdate?: (data: any) => void
  onError?: (error: any) => void
  enableVisibilityControl?: boolean
  backgroundInterval?: number
}

export function usePolling(config: PollingConfig) {
  const {
    interval = 10000,
    onUpdate,
    onError,
    enableVisibilityControl = true,
    backgroundInterval = 60000
  } = config

  const isActive = ref(false)
  const isPaused = ref(false)
  const currentInterval = ref(interval)
  let timerId: NodeJS.Timeout | null = null
  let visibilityHandler: (() => void) | null = null

  const start = () => {
    if (isActive.value) return
    
    isActive.value = true
    poll()
  }

  const stop = () => {
    if (timerId) {
      clearTimeout(timerId)
      timerId = null
    }
    isActive.value = false
  }

  const pause = () => {
    isPaused.value = true
    if (timerId) {
      clearTimeout(timerId)
      timerId = null
    }
  }

  const resume = () => {
    isPaused.value = false
    if (isActive.value) {
      poll()
    }
  }

  const setInterval = (newInterval: number) => {
    currentInterval.value = newInterval
    if (isActive.value) {
      stop()
      start()
    }
  }

  const poll = async () => {
    if (!isActive.value || isPaused.value) return

    try {
      if (onUpdate) {
        await onUpdate(null)
      }
    } catch (error) {
      if (onError) {
        onError(error)
      }
    }

    if (isActive.value && !isPaused.value) {
      timerId = setTimeout(poll, currentInterval.value)
    }
  }

  // Setup visibility control
  if (enableVisibilityControl && typeof document !== 'undefined') {
    visibilityHandler = () => {
      if (document.hidden) {
        currentInterval.value = backgroundInterval
        if (isActive.value) {
          stop()
          start()
        }
      } else {
        currentInterval.value = interval
        if (isActive.value) {
          stop()
          start()
        }
      }
    }
    document.addEventListener('visibilitychange', visibilityHandler)
  }

  // Cleanup on unmount
  onUnmounted(() => {
    stop()
    if (visibilityHandler && typeof document !== 'undefined') {
      document.removeEventListener('visibilitychange', visibilityHandler)
    }
  })

  return {
    isActive,
    isPaused,
    currentInterval,
    start,
    stop,
    pause,
    resume,
    setInterval
  }
}
