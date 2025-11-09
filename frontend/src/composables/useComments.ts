import { ref, Ref } from 'vue'
import apiClient from '@/services/api/client'
import type { Comment } from '@/types/comment'

export interface CreateCommentData {
  post_id: number
  content_text: string
  content_html?: string
  parent_id?: number | null
}

export function useComments(postId: Ref<number> | number) {
  const comments = ref<Comment[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const getPostId = () => typeof postId === 'number' ? postId : postId.value

  const isValidComment = (comment: any): comment is Comment => {
    return (
      comment !== null &&
      comment !== undefined &&
      typeof comment === 'object' &&
      typeof comment.comment_id === 'number' &&
      comment.comment_id > 0
    )
  }

  const loadComments = async (sortBy: 'newest' | 'oldest' | 'popular' = 'newest') => {
    try {
      loading.value = true
      error.value = null
      
      // Convert sortBy to the format expected by the backend
      let sortParam = 'created_asc'
      if (sortBy === 'newest') {
        sortParam = 'created_desc'
      } else if (sortBy === 'popular') {
        sortParam = 'reactions'
      }
      
      const response = await apiClient.get(`/posts/${getPostId()}/comments`, {
        params: {
          sort: sortParam
        }
      })
      
      // Debug: Log the raw response
      console.log('Comments API Response:', response)
      
      // The API client interceptor already unwraps the response
      // response is already the data part: { comments: [...], count: 6, has_more: false }
      // Make sure we have a valid array of comments
      if (response && Array.isArray(response.comments)) {
        // Filter out any invalid comments and ensure each comment is a valid object
        comments.value = response.comments.filter(isValidComment)
        console.log('Filtered comments:', comments.value)
      } else {
        console.log('Invalid response format or no comments array found')
        comments.value = []
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to load comments'
      console.error('Error loading comments:', err)
      comments.value = [] // Ensure we have an empty array on error
    } finally {
      loading.value = false
    }
  }

  const addComment = async (commentData: CreateCommentData): Promise<Comment | null> => {
    try {
      const response = await apiClient.post('/comments', commentData)
      
      // The API client interceptor already unwraps the response
      // response is already the data part: { comment: {...} }
      const newComment = response.comment
      
      // Make sure we have a valid comment object
      if (isValidComment(newComment)) {
        // Add to comments list
        if (!commentData.parent_id) {
          comments.value.unshift(newComment)
        } else {
          // Add as reply (will be handled by component)
          return newComment
        }
        
        return newComment
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'Failed to add comment'
      throw err
    }
  }

  const updateComment = async (commentId: number, data: Partial<Comment>): Promise<Comment | null> => {
    try {
      const response = await apiClient.patch(`/comments/${commentId}`, data)
      
      // The API client interceptor already unwraps the response
      // response is already the data part: { comment: {...} }
      const updatedComment = response.comment
      
      // Make sure we have a valid comment object
      if (isValidComment(updatedComment)) {
        // Update in comments list
        const index = comments.value.findIndex((c: Comment) => c && c.comment_id === commentId)
        if (index !== -1) {
          comments.value[index] = { ...comments.value[index], ...updatedComment }
        }
        
        return updatedComment
      }
      return null
    } catch (err: any) {
      error.value = err.message || 'Failed to update comment'
      throw err
    }
  }

  const deleteComment = async (commentId: number): Promise<void> => {
    try {
      await apiClient.delete(`/comments/${commentId}`)
      
      // Remove from comments list
      comments.value = comments.value.filter((c: Comment) => c && c.comment_id !== undefined && c.comment_id !== commentId)
    } catch (err: any) {
      error.value = err.message || 'Failed to delete comment'
      throw err
    }
  }

  const loadReplies = async (parentId: number): Promise<Comment[]> => {
    try {
      const response = await apiClient.get(`/posts/${getPostId()}/comments`, {
        params: {
          parent_id: parentId
        }
      })
      
      // The API client interceptor already unwraps the response
      // response is already the data part: { comments: [...] }
      if (response && Array.isArray(response.comments)) {
        // Filter out any invalid comments and ensure each comment is a valid object
        return response.comments.filter(isValidComment)
      }
      return []
    } catch (err: any) {
      error.value = err.message || 'Failed to load replies'
      return []
    }
  }

  return {
    comments,
    loading,
    error,
    loadComments,
    addComment,
    updateComment,
    deleteComment,
    loadReplies
  }
}