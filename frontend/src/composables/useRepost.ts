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
      
      if (response.data.success) {
        return response.data.data.post
      } else {
        throw new Error(response.data.message || 'Failed to repost post')
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