/**
 * Authentication API
 * Authentication related API calls
 */

import apiClient from './client.js';

export const authAPI = {
    /**
     * Register new user
     */
    async register(userData) {
        return await apiClient.post('/auth/register', userData);
    },

    /**
     * Login user
     */
    async login(credentials) {
        return await apiClient.post('/auth/login', credentials);
    },

    /**
     * Logout user
     */
    async logout() {
        return await apiClient.post('/auth/logout');
    },

    /**
     * Get current user
     */
    async getCurrentUser() {
        return await apiClient.get('/auth/me');
    },

    /**
     * Verify session
     */
    async verifySession() {
        return await apiClient.get('/auth/verify');
    },

    /**
     * Get Google OAuth URL
     */
    async getGoogleAuthUrl() {
        return await apiClient.get('/auth/google/url');
    },

    /**
     * Get Yandex OAuth URL
     */
    async getYandexAuthUrl() {
        return await apiClient.get('/auth/yandex/url');
    }
};

export default authAPI;
