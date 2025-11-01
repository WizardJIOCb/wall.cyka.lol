import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/services/api'

interface FollowStatus {
  user_id: number
  is_following: boolean
  is_followed_by: boolean
  is_mutual: boolean
  follower_count: number
  following_count: number
}

interface User {
  user_id: number
  username: string
  display_name?: string
  avatar_url?: string
  bio?: string
  followers_count?: number
  following_count?: number
  is_followed_by_you?: boolean
  is_mutual?: boolean
}

export const useSocialStore = defineStore('social', () => {
  // State
  const followStatusCache = ref<Map<number, FollowStatus>>(new Map())
  const followersCache = ref<Map<number, User[]>>(new Map())
  const followingCache = ref<Map<number, User[]>>(new Map())
  const suggestedUsers = ref<User[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const getFollowStatus = computed(() => {
    return (userId: number): FollowStatus | null => {
      return followStatusCache.value.get(userId) || null
    }
  })

  const getFollowers = computed(() => {
    return (userId: number): User[] => {
      return followersCache.value.get(userId) || []
    }
  })

  const getFollowing = computed(() => {
    return (userId: number): User[] => {
      return followingCache.value.get(userId) || []
    }
  })

  const isFollowing = computed(() => {
    return (userId: number): boolean => {
      const status = followStatusCache.value.get(userId)
      return status?.is_following ?? false
    }
  })

  const isMutual = computed(() => {
    return (userId: number): boolean => {
      const status = followStatusCache.value.get(userId)
      return status?.is_mutual ?? false
    }
  })

  // Actions
  const fetchFollowStatus = async (userId: number): Promise<FollowStatus | null> => {
    try {
      loading.value = true
      error.value = null

      const response = await api.get(`/users/${userId}/follow-status`)
      const status = response.data.data as FollowStatus

      // Cache the status
      followStatusCache.value.set(userId, status)

      return status
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch follow status'
      console.error('Fetch follow status error:', err)
      return null
    } finally {
      loading.value = false
    }
  }

  const followUser = async (userId: number): Promise<boolean> => {
    try {
      loading.value = true
      error.value = null

      await api.post(`/users/${userId}/follow`)

      // Update cached status
      const currentStatus = followStatusCache.value.get(userId)
      if (currentStatus) {
        followStatusCache.value.set(userId, {
          ...currentStatus,
          is_following: true,
          is_mutual: currentStatus.is_followed_by,
          follower_count: currentStatus.follower_count + 1
        })
      }

      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to follow user'
      console.error('Follow user error:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  const unfollowUser = async (userId: number): Promise<boolean> => {
    try {
      loading.value = true
      error.value = null

      await api.delete(`/users/${userId}/unfollow`)

      // Update cached status
      const currentStatus = followStatusCache.value.get(userId)
      if (currentStatus) {
        followStatusCache.value.set(userId, {
          ...currentStatus,
          is_following: false,
          is_mutual: false,
          follower_count: Math.max(0, currentStatus.follower_count - 1)
        })
      }

      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to unfollow user'
      console.error('Unfollow user error:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  const fetchFollowers = async (
    userId: number, 
    page = 1, 
    limit = 20
  ): Promise<{ followers: User[], hasMore: boolean, total: number }> => {
    try {
      loading.value = true
      error.value = null

      const response = await api.get(`/users/${userId}/followers`, {
        params: { page, limit }
      })

      const data = response.data.data
      const followers = data.followers as User[]

      // Cache the first page
      if (page === 1) {
        followersCache.value.set(userId, followers)
      }

      return {
        followers,
        hasMore: data.pagination.has_more,
        total: data.pagination.total
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch followers'
      console.error('Fetch followers error:', err)
      return { followers: [], hasMore: false, total: 0 }
    } finally {
      loading.value = false
    }
  }

  const fetchFollowing = async (
    userId: number, 
    page = 1, 
    limit = 20
  ): Promise<{ following: User[], hasMore: boolean, total: number }> => {
    try {
      loading.value = true
      error.value = null

      const response = await api.get(`/users/${userId}/following`, {
        params: { page, limit }
      })

      const data = response.data.data
      const following = data.following as User[]

      // Cache the first page
      if (page === 1) {
        followingCache.value.set(userId, following)
      }

      return {
        following,
        hasMore: data.pagination.has_more,
        total: data.pagination.total
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch following'
      console.error('Fetch following error:', err)
      return { following: [], hasMore: false, total: 0 }
    } finally {
      loading.value = false
    }
  }

  const fetchMutualFollowers = async (
    userId: number, 
    limit = 20
  ): Promise<User[]> => {
    try {
      loading.value = true
      error.value = null

      const response = await api.get(`/users/${userId}/mutual-followers`, {
        params: { limit }
      })

      return response.data.data.mutual_followers as User[]
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch mutual followers'
      console.error('Fetch mutual followers error:', err)
      return []
    } finally {
      loading.value = false
    }
  }

  const fetchSuggestedUsers = async (limit = 10): Promise<User[]> => {
    try {
      loading.value = true
      error.value = null

      // This endpoint would need to be implemented in DiscoverController
      // For now, we'll create a placeholder
      const response = await api.get('/discover/suggested-users', {
        params: { limit }
      })

      suggestedUsers.value = response.data.data.users as User[]
      return suggestedUsers.value
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch suggested users'
      console.error('Fetch suggested users error:', err)
      return []
    } finally {
      loading.value = false
    }
  }

  const clearCache = () => {
    followStatusCache.value.clear()
    followersCache.value.clear()
    followingCache.value.clear()
    suggestedUsers.value = []
    error.value = null
  }

  const clearUserCache = (userId: number) => {
    followStatusCache.value.delete(userId)
    followersCache.value.delete(userId)
    followingCache.value.delete(userId)
  }

  return {
    // State
    loading,
    error,
    suggestedUsers,

    // Getters
    getFollowStatus,
    getFollowers,
    getFollowing,
    isFollowing,
    isMutual,

    // Actions
    fetchFollowStatus,
    followUser,
    unfollowUser,
    fetchFollowers,
    fetchFollowing,
    fetchMutualFollowers,
    fetchSuggestedUsers,
    clearCache,
    clearUserCache
  }
})
