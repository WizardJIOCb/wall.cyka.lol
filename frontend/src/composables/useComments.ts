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
    // Add detailed logging to see what's happening
    console.log('Validating comment:', comment);
    
    // Check if comment is null or undefined
    if (comment === null || comment === undefined) {
      console.log('Comment is null or undefined');
      return false;
    }
    
    // Check if comment is an object
    if (typeof comment !== 'object') {
      console.log('Comment is not an object, type:', typeof comment);
      return false;
    }
    
    // Log all available keys to see what we have
    console.log('Comment keys:', Object.keys(comment));
    
    // Check for ID field (be flexible with field names)
    const id = comment.comment_id || comment.id || comment._id;
    console.log('Found ID field:', id, 'Type:', typeof id);
    
    // If we can't find any ID field, log the entire comment for debugging
    if (id === undefined) {
      console.log('No ID field found in comment. Full comment structure:', JSON.stringify(comment, null, 2));
      // Even without ID, let's check if it has the basic structure
      if (comment.content_text && comment.author_username) {
        console.log('Comment has basic structure, allowing it through');
        return true;
      }
      return false;
    }
    
    // If we have an ID, validate it
    if (typeof id !== 'number') {
      console.log('ID is not a number, type:', typeof id, 'value:', id);
      // Try to convert to number
      const numericId = Number(id);
      if (isNaN(numericId) || numericId <= 0) {
        console.log('ID cannot be converted to valid number');
        return false;
      }
      console.log('Converted ID to valid number:', numericId);
    }
    
    // Check for required fields
    const requiredFields = ['content_text', 'author_username'];
    const missingFields = requiredFields.filter(field => !(field in comment));
    if (missingFields.length > 0) {
      console.log('Missing required fields:', missingFields);
      return false;
    }
    
    console.log('Comment passed validation');
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
      
      // Debug: Log the raw response
      console.log('Comments API Response:', response)
      console.log('Response type:', typeof response)
      console.log('Response keys:', Object.keys(response))
      
      // Handle different response formats
      let commentsData = [];
      
      // Check if response has the expected structure
      if (response && typeof response === 'object') {
        if ('comments' in response && Array.isArray(response.comments)) {
          // Standard format: { comments: [...], count: 6, has_more: false }
          commentsData = response.comments;
          console.log('Found comments array in response:', commentsData);
        } else if (Array.isArray(response)) {
          // Direct array format
          commentsData = response;
          console.log('Response is directly an array of comments:', commentsData);
        } else {
          // Unexpected format
          console.log('Unexpected response format:', response);
        }
      } else {
        console.log('Invalid response format:', response);
      }
      
      console.log('Processing comments data:', commentsData);
      console.log('Comments data type:', typeof commentsData);
      console.log('Is comments data array?', Array.isArray(commentsData));
      
      if (Array.isArray(commentsData)) {
        // Filter out any invalid comments and ensure each comment is a valid object
        const validComments = commentsData.filter(isValidComment)
        comments.value = validComments
        console.log('Filtered comments:', validComments)
        console.log('Valid comments count:', validComments.length)
        
        // Also show what was filtered out
        const invalidComments = commentsData.filter(comment => !isValidComment(comment));
        if (invalidComments.length > 0) {
          console.log('Invalid comments that were filtered out:', invalidComments);
        }
      } else {
        console.log('Comments data is not an array')
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