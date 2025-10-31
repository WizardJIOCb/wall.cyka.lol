/**
 * Users API
 * User-related API calls
 */

import apiClient from './client.js';

export const usersAPI = {
    /**
     * Get current user profile
     */
    async getMyProfile() {
        return await apiClient.get('/users/me');
    },

    /**
     * Get user profile by ID
     */
    async getUserProfile(userId) {
        return await apiClient.get(`/users/${userId}`);
    },

    /**
     * Update current user profile
     */
    async updateMyProfile(updates) {
        return await apiClient.patch('/users/me', updates);
    },

    /**
     * Update bio
     */
    async updateBio(bioData) {
        return await apiClient.patch('/users/me/bio', bioData);
    },

    /**
     * Get user's social links
     */
    async getMyLinks() {
        return await apiClient.get('/users/me/links');
    },

    /**
     * Get user's public links
     */
    async getUserLinks(userId) {
        return await apiClient.get(`/users/${userId}/links`);
    },

    /**
     * Add social link
     */
    async addLink(linkData) {
        return await apiClient.post('/users/me/links', linkData);
    },

    /**
     * Update social link
     */
    async updateLink(linkId, updates) {
        return await apiClient.patch(`/users/me/links/${linkId}`, updates);
    },

    /**
     * Delete social link
     */
    async deleteLink(linkId) {
        return await apiClient.delete(`/users/me/links/${linkId}`);
    },

    /**
     * Reorder social links
     */
    async reorderLinks(orderData) {
        return await apiClient.post('/users/me/links/reorder', orderData);
    }
};

export default usersAPI;
