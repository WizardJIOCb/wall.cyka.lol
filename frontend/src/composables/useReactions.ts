import { ref, Ref, computed } from 'vue'
import apiClient from '@/services/api/client'

// Updated to match the actual API response format
export type ReactionType = 'like' | 'dislike' | 'love' | 'haha' | 'wow' | 'sad' | 'angry'

export interface Reaction {
  reaction_id: number
  user_id: number
  username: string
  avatar_url: string
  target_type: 'post' | 'comment'
  target_id: number
  reaction_type: ReactionType
  created_at: string
}

export interface ReactionStats {
  total: number
  by_type: Record<ReactionType, number>
}

export function useReactions(
  reactableType: Ref<'post' | 'comment'> | 'post' | 'comment',
  reactableId: Ref<number> | number
) {
  // Create a helper function to initialize by_type with all reaction types
  const createDefaultByType = (): Record<ReactionType, number> => ({
    like: 0,
    dislike: 0,
    love: 0,
    haha: 0,
    wow: 0,
    sad: 0,
    angry: 0
  })

  const reactions = ref<Reaction[]>([])
  const stats = ref<ReactionStats>({ 
    total: 0, 
    by_type: createDefaultByType()
  })
  const currentUserReaction = ref<ReactionType | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const getReactableType = () => typeof reactableType === 'string' ? reactableType : reactableType.value
  const getReactableId = () => typeof reactableId === 'number' ? reactableId : reactableId.value

  const reactionIcons: Record<ReactionType, string> = {
    like: '👍',
    dislike: '👎',
    love: '❤️',
    haha: '😂',
    wow: '😮',
    sad: '😢',
    angry: '😠'
  }

  const reactionColors: Record<ReactionType, string> = {
    like: '#3b82f6',
    dislike: '#94a3b8',
    love: '#ef4444',
    haha: '#eab308',
    wow: '#f97316',
    sad: '#64748b',
    angry: '#dc2626'
  }

  const getCurrentUserId = (): number => {
    // Get from auth store
    const userStr = localStorage.getItem('user')
    console.log('User string from localStorage:', userStr)
    if (userStr) {
      try {
        const user = JSON.parse(userStr)
        console.log('Parsed user object:', user)
        // Fix: Use 'id' instead of 'user_id' to match the User type definition
        const userId = user.id || 0
        console.log('User ID:', userId)
        return userId
      } catch (e) {
        console.error('Error parsing user from localStorage:', e)
        return 0
      }
    }
    console.log('No user found in localStorage')
    return 0
  }

  const loadReactions = async () => {
    try {
      loading.value = true
      error.value = null
      
      const response = await apiClient.get(
        `/reactions/${getReactableType()}/${getReactableId()}`
      )
      
      // The API client already unwraps the response, so we check the unwrapped data directly
      if (response && typeof response === 'object' && 'reactions' in response) {
        // Standard format: { reactions: [...], stats: { total: number, by_type: {...} } }
        reactions.value = Array.isArray(response.reactions) ? response.reactions : []
        const defaultByType = {
          like: 0,
          love: 0,
          haha: 0,
          wow: 0,
          sad: 0,
          angry: 0
        } as Record<ReactionType, number>

        // Safely merge stats, filtering out any keys not in our ReactionType
        const apiStats = response.stats?.by_type || {}
        const filteredStats: Record<ReactionType, number> = { ...defaultByType }
        
        // Only copy over values for valid reaction types
        Object.keys(defaultByType).forEach(key => {
          if (key in apiStats) {
            filteredStats[key as ReactionType] = Number(apiStats[key]) || 0
          }
        })

        stats.value = {
          total: response.stats?.total || 0,
          by_type: filteredStats
        }
        
        // Find current user's reaction
        const currentUserId = getCurrentUserId()
        console.log('Current user ID:', currentUserId)
        console.log('Reactions array:', reactions.value)
        const userReaction = reactions.value.find((r: Reaction) => r.user_id === currentUserId)
        console.log('User reaction found:', userReaction)
        currentUserReaction.value = userReaction ? userReaction.reaction_type : null
        console.log('Current user reaction set to:', currentUserReaction.value)
      } else if (Array.isArray(response)) {
        // Direct array format
        reactions.value = response
        stats.value = { 
          total: response.length, 
          by_type: createDefaultByType()
        }
      } else {
        // Unexpected format
        reactions.value = []
        stats.value = { 
          total: 0, 
          by_type: createDefaultByType()
        }
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to load reactions'
      console.error('Error loading reactions:', err)
    } finally {
      loading.value = false
    }
  }

  const addReaction = async (reactionType: ReactionType): Promise<void> => {
    const previousReaction = currentUserReaction.value
    const previousTotal = stats.value.total
    const previousByType = { ...stats.value.by_type }
    
    try {
      // Optimistic update
      currentUserReaction.value = reactionType
      stats.value.total++
      // Safe access to by_type properties
      stats.value.by_type[reactionType] = (stats.value.by_type[reactionType] ?? 0) + 1
    
      await apiClient.post('/reactions', {
        reactable_type: getReactableType(),
        reactable_id: getReactableId(),
        reaction_type: reactionType
      })
      
      // Reload to get accurate data
      await loadReactions()
    } catch (err: any) {
      // Rollback on error
      currentUserReaction.value = previousReaction
      stats.value.total = previousTotal
      stats.value.by_type = previousByType
      
      error.value = err.message || 'Failed to add reaction'
      throw err
    }
  }

  const removeReaction = async (): Promise<void> => {
    const previousReaction = currentUserReaction.value
    const previousTotal = stats.value.total
    const previousByType = { ...stats.value.by_type }
    
    if (!previousReaction) return
    
    try {
      // Optimistic update
      currentUserReaction.value = null
      stats.value.total--
      // Safe access to by_type properties
      if (stats.value.by_type[previousReaction] !== undefined) {
        stats.value.by_type[previousReaction] = Math.max(0, stats.value.by_type[previousReaction] - 1)
      }
      
      await apiClient.delete(
        `/reactions/${getReactableType()}/${getReactableId()}`
      )
      
      // Reload to get accurate data
      await loadReactions()
    } catch (err: any) {
      // Rollback on error
      currentUserReaction.value = previousReaction
      stats.value.total = previousTotal
      stats.value.by_type = previousByType
      
      error.value = err.message || 'Failed to remove reaction'
      throw err
    }
  }

  const toggleReaction = async (reactionType: ReactionType): Promise<void> => {
    if (currentUserReaction.value === reactionType) {
      await removeReaction()
    } else if (currentUserReaction.value) {
      // Change reaction type
      await removeReaction()
      await addReaction(reactionType)
    } else {
      await addReaction(reactionType)
    }
  }

  const displayIcon = computed(() => {
    if (currentUserReaction.value) {
      return reactionIcons[currentUserReaction.value]
    }
    return '🤍'
  })

  const displayColor = computed(() => {
    if (currentUserReaction.value) {
      return reactionColors[currentUserReaction.value]
    }
    return '#94a3b8'
  })

  return {
    reactions,
    stats,
    currentUserReaction,
    loading,
    error,
    reactionIcons,
    reactionColors,
    displayIcon,
    displayColor,
    loadReactions,
    addReaction,
    removeReaction,
    toggleReaction
  }
}
