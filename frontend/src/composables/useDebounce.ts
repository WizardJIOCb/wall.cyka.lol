import { ref, watch } from 'vue'

export function useDebounce<T = any>(initialValue: T, delay: number = 300) {
  const value = ref<T>(initialValue)
  const debouncedValue = ref<T>(initialValue)
  let timeout: NodeJS.Timeout | null = null

  watch(value, (newValue) => {
    if (timeout) {
      clearTimeout(timeout)
    }

    timeout = setTimeout(() => {
      debouncedValue.value = newValue
    }, delay)
  })

  const clearDebounce = () => {
    if (timeout) {
      clearTimeout(timeout)
      timeout = null
    }
  }

  return {
    value,
    debouncedValue,
    clearDebounce
  }
}
