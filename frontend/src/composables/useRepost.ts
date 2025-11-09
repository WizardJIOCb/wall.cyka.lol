import { ref, Ref } from 'vue'
import apiClient from '@/services/api/client'

export interface RepostData {
  wall_id?: number
  commentary?: string
}

export function useRepost() {
  const loading = ref(false)
  const error = ref<string | null>(null)

  const repostPost = async (postId: number, data?: RepostData): Promise<any> => {
    try {
      loading.value = true
      error.value = null
      
      const response = await apiClient.post(`/posts/${postId}/repost`, data || {})
      
      // The API client already unwraps the response, so we check the unwrapped data directly
      if (response && typeof response === 'object') {
        if ('post' in response) {
          // Standard format: { post: {...} }
          return response.post
        } else if ('message' in response) {
          // Error format: { message: string }
          throw new Error(response.message)
        } else {
          // Direct data format
          return response
        }
      } else {
        // Direct data format
        return response
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to repost post'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    repostPost
  }
}