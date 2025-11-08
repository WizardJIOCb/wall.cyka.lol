import apiClient from './client'
import type { Post, PaginatedResponse } from '@/types/models'
import type { CreatePostRequest } from '@/types/api'

export const postsAPI = {
  /**
   * Get posts feed
   */
  async getFeed(params?: {
    page?: number
    per_page?: number
    content_type?: string
    time_range?: string
    sort_by?: string
  }): Promise<PaginatedResponse<Post>> {
    return apiClient.get('/posts/feed', params)
  },

  /**
   * Get posts from a specific wall
   */
  async getWallPosts(wallId: number, params?: {
    page?: number
    per_page?: number
  }): Promise<PaginatedResponse<Post>> {
    return apiClient.get(`/walls/${wallId}/posts`, params)
  },

  /**
   * Get a single post by ID
   */
  async getPost(postId: number): Promise<Post> {
    return apiClient.get(`/posts/${postId}`)
  },

  /**
   * Create a new post
   */
  async createPost(data: CreatePostRequest): Promise<Post> {
    const formData = new FormData()
    
    formData.append('wall_id', data.wall_id.toString())
    formData.append('content', data.content)
    
    if (data.post_type) {
      formData.append('post_type', data.post_type)
    }
    
    if (data.visibility) {
      formData.append('visibility', data.visibility)
    }
    
    if (data.ai_application_id) {
      formData.append('ai_application_id', data.ai_application_id.toString())
    }
    
    if (data.media_files) {
      data.media_files.forEach((file, index) => {
        formData.append(`media_files[${index}]`, file)
      })
    }
    
    return apiClient.upload('/posts', formData)
  },

  /**
   * Update a post
   */
  async updatePost(postId: number, data: {
    content?: string
    visibility?: string
  }): Promise<Post> {
    return apiClient.patch(`/posts/${postId}`, data)
  },

  /**
   * Delete a post
   */
  async deletePost(postId: number): Promise<void> {
    return apiClient.delete(`/posts/${postId}`)
  },

  /**
   * React to a post
   */
  async reactToPost(postId: number, reactionType: string): Promise<void> {
    return apiClient.post(`/posts/${postId}/reactions`, { reaction_type: reactionType })
  },

  /**
   * Remove reaction from a post
   */
  async removeReaction(postId: number): Promise<void> {
    return apiClient.delete(`/posts/${postId}/reactions`)
  },

  /**
   * Share a post
   */
  async sharePost(postId: number, wallId: number, content?: string): Promise<Post> {
    return apiClient.post(`/posts/${postId}/share`, { wall_id: wallId, content })
  },

  /**
   * Increment post view count
   */
  async incrementViewCount(postId: number): Promise<void> {
    return apiClient.post(`/posts/${postId}/view`)
  }
}

export default postsAPI