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
    // Check if comment is null or undefined
    if (comment === null || comment === undefined) {
      return false;
    }
    
    // Check if comment is an object
    if (typeof comment !== 'object') {
      return false;
    }
    
    // Check for ID field (be flexible with field names)
    const id = comment.comment_id || comment.id || comment._id;
    
    // If we can't find any ID field, check if it has the basic structure
    if (id === undefined) {
      return comment.content_text && comment.author_username;
    }
    
    // If we have an ID, validate it
    if (typeof id !== 'number') {
      // Try to convert to number
      const numericId = Number(id);
      if (isNaN(numericId) || numericId <= 0) {
        return false;
      }
    }
    
    // Check for all required fields in the Comment type
    const requiredFields = [
      'comment_id', 
      'post_id', 
      'author_id', 
      'author_username', 
      'author_name', 
      'content_text', 
      'content_html', 
      'depth_level', 
      'reply_count', 
      'reaction_count', 
      'like_count', 
      'dislike_count', 
      'is_edited', 
      'is_hidden', 
      'created_at', 
      'updated_at'
    ];
    
    const missingFields = requiredFields.filter(field => !(field in comment));
    if (missingFields.length > 0) {
      // In development mode, log what fields are missing
      if (import.meta.env.DEV) {
        console.warn('Comment missing required fields:', missingFields, 'Comment:', comment);
      }
      return false;
    }
    
    // Validate field types
    if (typeof comment.comment_id !== 'number' || comment.comment_id <= 0) return false;
    if (typeof comment.post_id !== 'number' || comment.post_id <= 0) return false;
    if (typeof comment.author_id !== 'number' || comment.author_id <= 0) return false;
    if (typeof comment.author_username !== 'string' || !comment.author_username) return false;
    if (typeof comment.author_name !== 'string' || !comment.author_name) return false;
    if (typeof comment.content_text !== 'string') return false;
    if (typeof comment.content_html !== 'string') return false;
    if (typeof comment.depth_level !== 'number') return false;
    if (typeof comment.reply_count !== 'number') return false;
    if (typeof comment.reaction_count !== 'number') return false;
    if (typeof comment.like_count !== 'number') return false;
    if (typeof comment.dislike_count !== 'number') return false;
    if (typeof comment.is_edited !== 'boolean') return false;
    if (typeof comment.is_hidden !== 'boolean') return false;
    if (typeof comment.created_at !== 'string' || !comment.created_at) return false;
    if (typeof comment.updated_at !== 'string' || !comment.updated_at) return false;
    
    // Validate optional fields if present
    if (comment.parent_comment_id !== undefined && comment.parent_comment_id !== null) {
      if (typeof comment.parent_comment_id !== 'number') return false;
    }
    
    if (comment.author_avatar !== undefined && comment.author_avatar !== null) {
      if (typeof comment.author_avatar !== 'string') return false;
    }
    
    if (comment.user_reaction !== undefined && comment.user_reaction !== null) {
      if (typeof comment.user_reaction !== 'string') return false;
    }
    
    return true;
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
      
      console.log('Making API request for comments with postId:', getPostId());
      const response = await apiClient.get(`/posts/${getPostId()}/comments`, {
        params: {
          sort: sortParam
        }
      })
      
      // Handle different response formats
      let commentsData = [];
      
      // Check if response has the expected structure
      if (response && typeof response === 'object') {
        if ('comments' in response && Array.isArray(response.comments)) {
          // Standard format: { comments: [...], count: 6, has_more: false }
          commentsData = response.comments;
        } else if (Array.isArray(response)) {
          // Direct array format
          commentsData = response;
        }
      }
      
      if (Array.isArray(commentsData)) {
        // Filter out any invalid comments and ensure each comment is a valid object
        const validComments = commentsData.filter(isValidComment)
        comments.value = validComments;
        
        // Also show what was filtered out in development mode
        if (import.meta.env.DEV) {
          const invalidComments = commentsData.filter(comment => !isValidComment(comment));
          if (invalidComments.length > 0) {
            console.warn('Invalid comments filtered out:', invalidComments);
          }
        }
      } else {
        comments.value = [];
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
      comments.value = comments.value.filter((c: Comment) => c && c.comment_id !== commentId)
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