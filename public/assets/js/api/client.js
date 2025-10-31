/**
 * API Client
 * Centralized HTTP request handler
 */

const API_BASE_URL = '/api/v1';

class APIClient {
    constructor(baseURL = API_BASE_URL) {
        this.baseURL = baseURL;
    }

    /**
     * Get authentication token from localStorage
     */
    getToken() {
        return localStorage.getItem('session_token');
    }

    /**
     * Get default headers
     */
    getHeaders(customHeaders = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...customHeaders
        };

        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        return headers;
    }

    /**
     * Handle API response
     */
    async handleResponse(response) {
        const data = await response.json();

        if (!response.ok) {
            // Handle specific error codes
            if (response.status === 401) {
                // Unauthorized - redirect to login
                localStorage.removeItem('session_token');
                window.location.href = '/login.html';
                throw new Error(data.message || 'Unauthorized');
            }

            if (response.status === 403) {
                throw new Error(data.message || 'Forbidden');
            }

            if (response.status === 404) {
                throw new Error(data.message || 'Not found');
            }

            if (response.status === 500) {
                throw new Error(data.message || 'Server error');
            }

            throw new Error(data.message || 'Request failed');
        }

        return data;
    }

    /**
     * GET request
     */
    async get(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'GET',
                headers: this.getHeaders(options.headers),
                ...options
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('GET request failed:', error);
            throw error;
        }
    }

    /**
     * POST request
     */
    async post(endpoint, data = {}, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'POST',
                headers: this.getHeaders(options.headers),
                body: JSON.stringify(data),
                ...options
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('POST request failed:', error);
            throw error;
        }
    }

    /**
     * PATCH request
     */
    async patch(endpoint, data = {}, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'PATCH',
                headers: this.getHeaders(options.headers),
                body: JSON.stringify(data),
                ...options
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('PATCH request failed:', error);
            throw error;
        }
    }

    /**
     * DELETE request
     */
    async delete(endpoint, options = {}) {
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'DELETE',
                headers: this.getHeaders(options.headers),
                ...options
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('DELETE request failed:', error);
            throw error;
        }
    }

    /**
     * Upload file (multipart/form-data)
     */
    async upload(endpoint, formData, options = {}) {
        try {
            const token = this.getToken();
            const headers = {};
            
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const response = await fetch(`${this.baseURL}${endpoint}`, {
                method: 'POST',
                headers,
                body: formData,
                ...options
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('Upload request failed:', error);
            throw error;
        }
    }
}

// Create singleton instance
const apiClient = new APIClient();

export default apiClient;
