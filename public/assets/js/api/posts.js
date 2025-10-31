/**
 * Posts API
 * Posts related API calls
 */

import apiClient from './client.js';

export const postsAPI = {
    /**
     * Get wall posts
     */
    async getWallPosts(wallId, options = {}) {
        const params = new URLSearchParams(options);
        return await apiClient.get(`/walls/${wallId}/posts?${params}`);
    },

    /**
     * Get single post
     */
    async getPost(postId) {
        return await apiClient.get(`/posts/${postId}`);
    },

    /**
     * Create post
     */
    async createPost(postData) {
        return await apiClient.post('/posts', postData);
    },

    /**
     * Update post
     */
    async updatePost(postId, updates) {
        return await apiClient.patch(`/posts/${postId}`, updates);
    },

    /**
     * Delete post
     */
    async deletePost(postId) {
        return await apiClient.delete(`/posts/${postId}`);
    },

    /**
     * Toggle pin post
     */
    async togglePin(postId) {
        return await apiClient.post(`/posts/${postId}/pin`);
    },

    /**
     * Get user posts
     */
    async getUserPosts(userId, options = {}) {
        const params = new URLSearchParams(options);
        return await apiClient.get(`/users/${userId}/posts?${params}`);
    }
};

export default postsAPI;
