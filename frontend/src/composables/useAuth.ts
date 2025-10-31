import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import type { LoginRequest, RegisterRequest } from '@/types/api'

/**
 * Authentication composable
 * Provides convenient access to auth state and methods
 */
export const useAuth = () => {
  const authStore = useAuthStore()

  // Computed properties
  const user = computed(() => authStore.user)
  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const isLoading = computed(() => !authStore.initialized)

  // Methods
  const login = async (credentials: LoginRequest) => {
    return authStore.login(credentials)
  }

  const register = async (userData: RegisterRequest) => {
    return authStore.register(userData)
  }

  const logout = async () => {
    return authStore.logout()
  }

  const updateProfile = (updates: any) => {
    return authStore.updateUser(updates)
  }

  return {
    // State
    user,
    isAuthenticated,
    isLoading,
    
    // Methods
    login,
    register,
    logout,
    updateProfile
  }
}

export default useAuth
