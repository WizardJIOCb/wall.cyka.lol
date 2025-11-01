import { ref, Ref } from 'vue'

export interface OptimisticOptions<T> {
  onSuccess?: (data: any) => void
  onError?: (error: any) => void
  showErrorToast?: boolean
}

export function useOptimistic<T>() {
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const execute = async <R = any>(
    state: Ref<T>,
    optimisticChange: (currentState: T) => T,
    apiCall: () => Promise<R>,
    rollback: (previousState: T) => T,
    options: OptimisticOptions<T> = {}
  ): Promise<R | null> => {
    const previousState = JSON.parse(JSON.stringify(state.value))
    error.value = null
    
    try {
      // Apply optimistic update
      state.value = optimisticChange(state.value)
      
      // Execute API call
      isLoading.value = true
      const result = await apiCall()
      
      // On success
      if (options.onSuccess) {
        options.onSuccess(result)
      }
      
      return result
    } catch (err: any) {
      // Rollback on error
      state.value = rollback(previousState)
      error.value = err.message || 'An error occurred'
      
      if (options.onError) {
        options.onError(err)
      }
      
      if (options.showErrorToast !== false) {
        // Show error toast (will be implemented in toast system)
        console.error('Optimistic update failed:', error.value)
      }
      
      return null
    } finally {
      isLoading.value = false
    }
  }

  return {
    isLoading,
    error,
    execute
  }
}
