import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/models'
import type { LoginRequest, RegisterRequest } from '@/types/api'
import { STORAGE_KEYS } from '@/utils/constants'
import authAPI from '@/services/api/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const initialized = ref(false)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Actions
  const init = async () => {
    // Load token and user from localStorage
    const savedToken = localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN)
    const savedUser = localStorage.getItem(STORAGE_KEYS.USER)

    if (savedToken && savedUser) {
      token.value = savedToken
      try {
        user.value = JSON.parse(savedUser)
      } catch (e) {
        console.error('Failed to parse saved user:', e)
        logout()
      }
    }

    initialized.value = true
  }

  const setAuth = (newUser: User, newToken: string) => {
    user.value = newUser
    token.value = newToken
    
    // Persist to localStorage
    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, newToken)
    localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(newUser))
  }

  const updateUser = (updates: Partial<User>) => {
    if (user.value) {
      user.value = { ...user.value, ...updates }
      localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user.value))
    }
  }

  const login = async (credentials: LoginRequest) => {
    try {
      const response = await authAPI.login(credentials)
      if (response.success && response.data) {
        setAuth(response.data.user, response.data.token)
        router.push('/')
        return response
      }
      throw new Error(response.message || 'Login failed')
    } catch (error: any) {
      throw error
    }
  }

  const register = async (userData: RegisterRequest) => {
    try {
      const response = await authAPI.register(userData)
      if (response.success && response.data) {
        setAuth(response.data.user, response.data.token)
        router.push('/')
        return response
      }
      throw new Error(response.message || 'Registration failed')
    } catch (error: any) {
      throw error
    }
  }

  const logout = async () => {
    try {
      await authAPI.logout()
    } catch (error) {
      // Ignore errors on logout
    } finally {
      user.value = null
      token.value = null
      
      // Clear localStorage
      localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
      localStorage.removeItem(STORAGE_KEYS.USER)
      
      // Redirect to login
      router.push('/login')
    }
  }

  return {
    // State
    user,
    token,
    initialized,
    
    // Getters
    isAuthenticated,
    
    // Actions
    init,
    login,
    register,
    setAuth,
    updateUser,
    logout
  }
})
