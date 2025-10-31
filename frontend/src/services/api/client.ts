import axios, { type AxiosInstance, type AxiosError, type InternalAxiosRequestConfig } from 'axios'
import type { ApiResponse, ApiError } from '@/types/api'
import { API_BASE_URL, STORAGE_KEYS } from '@/utils/constants'
import router from '@/router'

class APIClient {
  private client: AxiosInstance

  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json'
      }
    })

    this.setupInterceptors()
  }

  private setupInterceptors() {
    // Request interceptor - add auth token
    this.client.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        const token = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN)
        if (token && config.headers) {
          config.headers.Authorization = `Bearer ${token}`
        }
        return config
      },
      (error) => Promise.reject(error)
    )

    // Response interceptor - handle errors
    this.client.interceptors.response.use(
      (response) => response.data,
      async (error: AxiosError<ApiResponse>) => {
        const apiError: ApiError = {
          status: error.response?.status || 500,
          message: error.response?.data?.message || 'An error occurred',
          errors: error.response?.data?.errors
        }

        // Handle specific error codes
        switch (apiError.status) {
          case 401:
            // Unauthorized - clear auth and redirect to login
            localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
            localStorage.removeItem(STORAGE_KEYS.USER)
            router.push('/login')
            break
          
          case 403:
            // Forbidden
            apiError.message = 'You do not have permission to perform this action'
            break
          
          case 404:
            // Not found
            apiError.message = 'The requested resource was not found'
            break
          
          case 422:
            // Validation error
            apiError.message = error.response?.data?.message || 'Validation error'
            break
          
          case 500:
            // Server error
            apiError.message = 'Internal server error. Please try again later.'
            break
        }

        return Promise.reject(apiError)
      }
    )
  }

  // HTTP methods
  async get<T = any>(url: string, params?: Record<string, any>): Promise<T> {
    return this.client.get(url, { params })
  }

  async post<T = any>(url: string, data?: any): Promise<T> {
    return this.client.post(url, data)
  }

  async put<T = any>(url: string, data?: any): Promise<T> {
    return this.client.put(url, data)
  }

  async patch<T = any>(url: string, data?: any): Promise<T> {
    return this.client.patch(url, data)
  }

  async delete<T = any>(url: string): Promise<T> {
    return this.client.delete(url)
  }

  // File upload with progress tracking
  async upload<T = any>(
    url: string,
    formData: FormData,
    onProgress?: (progress: number) => void
  ): Promise<T> {
    return this.client.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        if (onProgress && progressEvent.total) {
          const progress = (progressEvent.loaded / progressEvent.total) * 100
          onProgress(Math.round(progress))
        }
      }
    })
  }
}

// Export singleton instance
export const apiClient = new APIClient()

export default apiClient
