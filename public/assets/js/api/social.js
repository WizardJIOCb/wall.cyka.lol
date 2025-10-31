/**
 * Social API
 * Social features API calls (reactions, comments)
 */

import apiClient from './client.js';

export const socialAPI = {
    /**
     * Add reaction
     */
    async addReaction(data) {
        return await apiClient.post('/reactions', data);
    },

    /**
     * Remove reaction
     */
    async removeReaction(reactableType, reactableId) {
        return await apiClient.delete(`/reactions/${reactableType}/${reactableId}`);
    },

    /**
     * Get reactions
     */
    async getReactions(reactableType, reactableId) {
        return await apiClient.get(`/reactions/${reactableType}/${reactableId}`);
    },

    /**
     * Create comment
     */
    async createComment(commentData) {
        return await apiClient.post('/comments', commentData);
    },

    /**
     * Get post comments
     */
    async getPostComments(postId, options = {}) {
        const params = new URLSearchParams(options);
        return await apiClient.get(`/posts/${postId}/comments?${params}`);
    },

    /**
     * Get comment
     */
    async getComment(commentId) {
        return await apiClient.get(`/comments/${commentId}`);
    },

    /**
     * Update comment
     */
    async updateComment(commentId, updates) {
        return await apiClient.patch(`/comments/${commentId}`, updates);
    },

    /**
     * Delete comment
     */
    async deleteComment(commentId) {
        return await apiClient.delete(`/comments/${commentId}`);
    }
};

export default socialAPI;
