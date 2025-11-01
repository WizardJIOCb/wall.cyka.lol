import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/services/api/client'
import type { 
  Comment, 
  CommentListResponse, 
  CreateCommentData,
  UpdateCommentData,
  CommentReactionResponse
} from '@/types/comment'

export const useCommentsStore = defineStore('comments', () => {
  // State
  const commentsByPost = ref<Map<number, Comment[]>>(new Map())
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  // Getters
  const getCommentsByPost = computed(() => {
    return (postId: number) => commentsByPost.value.get(postId) || []
  })
  
  const getCommentCount = computed(() => {
    return (postId: number) => {
      const comments = commentsByPost.value.get(postId) || []
      return comments.length
    }
  })
  
  // Actions
  const fetchComments = async (
    postId: number,
    options: {
      sort?: 'created_asc' | 'created_desc' | 'reactions'
      limit?: number
      parent_id?: number
    } = {}
  ): Promise<CommentListResponse> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await apiClient.get(`/posts/${postId}/comments`, {
        params: {
          sort: options.sort || 'created_asc',
          limit: options.limit || 50,
          parent_id: options.parent_id
        }
      })
      
      const data: CommentListResponse = response.data.data
      commentsByPost.value.set(postId, data.comments)
      
      return data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch comments'
      throw err
    } finally {
      loading.value = false
    }
  }
  
  const createComment = async (
    postId: number,
    data: CreateCommentData
  ): Promise<Comment> => {
    try {
      const endpoint = data.parent_comment_id
        ? `/comments/${data.parent_comment_id}/replies`
        : `/posts/${postId}/comments`
      
      const response = await apiClient.post(endpoint, { content: data.content })
      const comment: Comment = response.data.data
      
      // Add to local state
      const comments = commentsByPost.value.get(postId) || []
      commentsByPost.value.set(postId, [comment, ...comments])
      
      return comment
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create comment'
      throw err
    }
  }
  
  const updateComment = async (
    commentId: number,
    data: UpdateCommentData
  ): Promise<Comment> => {
    try {
      const response = await apiClient.patch(`/comments/${commentId}`, data)
      const updatedComment: Comment = response.data.data
      
      // Update in local state
      for (const [postId, comments] of commentsByPost.value.entries()) {
        const index = comments.findIndex(c => c.comment_id === commentId)
        if (index !== -1) {
          comments[index] = updatedComment
          commentsByPost.value.set(postId, [...comments])
          break
        }
      }
      
      return updatedComment
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update comment'
      throw err
    }
  }
  
  const deleteComment = async (commentId: number, postId: number): Promise<void> => {
    try {
      await apiClient.delete(`/comments/${commentId}`)
      
      // Remove from local state
      const comments = commentsByPost.value.get(postId) || []
      const filtered = comments.filter(c => c.comment_id !== commentId)
      commentsByPost.value.set(postId, filtered)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete comment'
      throw err
    }
  }
  
  const reactToComment = async (
    commentId: number,
    reactionType: string
  ): Promise<CommentReactionResponse> => {
    try {
      const response = await apiClient.post(`/comments/${commentId}/reactions`, {
        reaction_type: reactionType
      })
      
      const data: CommentReactionResponse = response.data.data
      
      // Update comment reaction in local state
      for (const [postId, comments] of commentsByPost.value.entries()) {
        const updateCommentReaction = (comment: Comment) => {
          if (comment.comment_id === commentId) {
            comment.reaction_count = data.reaction_count
            comment.like_count = data.like_count
            comment.dislike_count = data.dislike_count
            comment.user_reaction = data.action === 'removed' ? null : reactionType
          }
          // Recursively update replies
          if (comment.replies) {
            comment.replies.forEach(updateCommentReaction)
          }
        }
        
        comments.forEach(updateCommentReaction)
      }
      
      return data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to react to comment'
      throw err
    }
  }
  
  const removeReaction = async (commentId: number): Promise<CommentReactionResponse> => {
    try {
      const response = await apiClient.delete(`/comments/${commentId}/reactions`)
      const data: CommentReactionResponse = response.data.data
      
      // Update comment reaction in local state
      for (const [postId, comments] of commentsByPost.value.entries()) {
        const updateCommentReaction = (comment: Comment) => {
          if (comment.comment_id === commentId) {
            comment.reaction_count = data.reaction_count
            comment.like_count = data.like_count
            comment.dislike_count = data.dislike_count
            comment.user_reaction = null
          }
          if (comment.replies) {
            comment.replies.forEach(updateCommentReaction)
          }
        }
        
        comments.forEach(updateCommentReaction)
      }
      
      return data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to remove reaction'
      throw err
    }
  }
  
  const getCommentReactions = async (commentId: number) => {
    try {
      const response = await apiClient.get(`/comments/${commentId}/reactions`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch reactions'
      throw err
    }
  }
  
  const getCommentReactionUsers = async (
    commentId: number,
    options: {
      reaction_type?: string
      limit?: number
      offset?: number
    } = {}
  ) => {
    try {
      const response = await apiClient.get(`/comments/${commentId}/reactions/users`, {
        params: options
      })
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch reaction users'
      throw err
    }
  }
  
  const clearComments = (postId?: number) => {
    if (postId) {
      commentsByPost.value.delete(postId)
    } else {
      commentsByPost.value.clear()
    }
  }
  
  return {
    // State
    commentsByPost,
    loading,
    error,
    
    // Getters
    getCommentsByPost,
    getCommentCount,
    
    // Actions
    fetchComments,
    createComment,
    updateComment,
    deleteComment,
    reactToComment,
    removeReaction,
    getCommentReactions,
    getCommentReactionUsers,
    clearComments
  }
})
