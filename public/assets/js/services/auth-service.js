/**
 * Authentication Service
 * Manage authentication state
 */

import authAPI from '../api/auth.js';

class AuthService {
    constructor() {
        this.currentUser = null;
        this.isAuthenticated = false;
    }

    /**
     * Initialize auth service
     */
    async init() {
        const token = this.getToken();
        
        if (token) {
            try {
                const response = await authAPI.getCurrentUser();
                if (response.success) {
                    this.currentUser = response.data.user;
                    this.isAuthenticated = true;
                    return true;
                }
            } catch (error) {
                console.error('Auth initialization failed:', error);
                this.logout();
            }
        }
        
        return false;
    }

    /**
     * Login user
     */
    async login(credentials) {
        try {
            const response = await authAPI.login(credentials);
            
            if (response.success) {
                this.setToken(response.data.session_token);
                this.currentUser = response.data.user;
                this.isAuthenticated = true;
                return { success: true, user: this.currentUser };
            }
            
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Register new user
     */
    async register(userData) {
        try {
            const response = await authAPI.register(userData);
            
            if (response.success) {
                this.setToken(response.data.session_token);
                this.currentUser = response.data.user;
                this.isAuthenticated = true;
                return { success: true, user: this.currentUser };
            }
            
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            await authAPI.logout();
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.removeToken();
            this.currentUser = null;
            this.isAuthenticated = false;
            window.location.href = '/login.html';
        }
    }

    /**
     * Get stored token
     */
    getToken() {
        return localStorage.getItem('session_token');
    }

    /**
     * Set token in localStorage
     */
    setToken(token) {
        localStorage.setItem('session_token', token);
    }

    /**
     * Remove token from localStorage
     */
    removeToken() {
        localStorage.removeItem('session_token');
    }

    /**
     * Check if user is authenticated
     */
    isLoggedIn() {
        return this.isAuthenticated && this.currentUser !== null;
    }

    /**
     * Get current user
     */
    getCurrentUser() {
        return this.currentUser;
    }

    /**
     * Require authentication (redirect if not logged in)
     */
    requireAuth() {
        if (!this.isLoggedIn()) {
            window.location.href = '/login.html';
            return false;
        }
        return true;
    }
}

// Create singleton instance
const authService = new AuthService();

export default authService;
