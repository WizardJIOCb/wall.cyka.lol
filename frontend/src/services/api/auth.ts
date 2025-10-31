import apiClient from './client'
import type { LoginRequest, RegisterRequest, AuthResponse } from '@/types/api'

export const authAPI = {
  /**
   * Register a new user
   */
  async register(data: RegisterRequest): Promise<AuthResponse> {
    return apiClient.post('/auth/register', data)
  },

  /**
   * Login with username/email and password
   */
  async login(data: LoginRequest): Promise<AuthResponse> {
    return apiClient.post('/auth/login', data)
  },

  /**
   * Logout current user
   */
  async logout(): Promise<void> {
    return apiClient.post('/auth/logout')
  },

  /**
   * Get current authenticated user
   */
  async getCurrentUser(): Promise<AuthResponse> {
    return apiClient.get('/auth/me')
  },

  /**
   * Verify session is still valid
   */
  async verifySession(): Promise<{ valid: boolean }> {
    return apiClient.get('/auth/verify')
  },

  /**
   * Get OAuth initiation URL
   */
  getOAuthURL(provider: 'google' | 'yandex' | 'telegram'): string {
    return `${apiClient['client'].defaults.baseURL}/auth/oauth/${provider}/initiate`
  },

  /**
   * Handle OAuth callback
   */
  async handleOAuthCallback(provider: string, code: string): Promise<AuthResponse> {
    return apiClient.post(`/auth/oauth/${provider}/callback`, { code })
  }
}

export default authAPI
