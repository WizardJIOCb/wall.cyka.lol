/**
 * Walls API
 * Wall-related API calls
 */

import apiClient from './client.js';

export const wallsAPI = {
    /**
     * Get wall by ID or slug
     */
    async getWall(wallIdOrSlug) {
        return await apiClient.get(`/walls/${wallIdOrSlug}`);
    },

    /**
     * Get user's wall
     */
    async getUserWall(userId) {
        return await apiClient.get(`/users/${userId}/wall`);
    },

    /**
     * Get current user's wall
     */
    async getMyWall() {
        return await apiClient.get('/walls/me');
    },

    /**
     * Create wall
     */
    async createWall(wallData) {
        return await apiClient.post('/walls', wallData);
    },

    /**
     * Update wall
     */
    async updateWall(wallId, updates) {
        return await apiClient.patch(`/walls/${wallId}`, updates);
    },

    /**
     * Delete wall
     */
    async deleteWall(wallId) {
        return await apiClient.delete(`/walls/${wallId}`);
    },

    /**
     * Check slug availability
     */
    async checkSlug(slug) {
        return await apiClient.get(`/walls/check-slug/${slug}`);
    }
};

export default wallsAPI;
