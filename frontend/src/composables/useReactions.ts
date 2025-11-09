import { ref, Ref, computed } from 'vue'
import apiClient from '@/services/api/client'

export type ReactionType = 'like' | 'love' | 'haha' | 'wow' | 'sad' | 'angry'

export interface Reaction {
  reaction_id: number
  user_id: number
  username: string
  avatar_url: string
  reactable_type: 'post' | 'comment'
  reactable_id: number
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
  const reactions = ref<Reaction[]>([])
  const stats = ref<ReactionStats>({ total: 0, by_type: {} as Record<ReactionType, number> })
  const currentUserReaction = ref<ReactionType | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const getReactableType = () => typeof reactableType === 'string' ? reactableType : reactableType.value
  const getReactableId = () => typeof reactableId === 'number' ? reactableId : reactableId.value

  const reactionIcons: Record<ReactionType, string> = {
    like: '👍',
    love: '❤️',
    haha: '😂',
    wow: '😮',
    sad: '😢',
    angry: '😠'
  }

  const reactionColors: Record<ReactionType, string> = {
    like: '#3b82f6',
    love: '#ef4444',
    haha: '#eab308',
    wow: '#f97316',
    sad: '#64748b',
    angry: '#dc2626'
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
        stats.value = response.stats || { total: 0, by_type: {} as Record<ReactionType, number> }
        
        // Find current user's reaction
        const userReaction = reactions.value.find(r => r.user_id === getCurrentUserId())
        currentUserReaction.value = userReaction ? userReaction.reaction_type : null
      } else if (Array.isArray(response)) {
        // Direct array format
        reactions.value = response
        stats.value = { total: response.length, by_type: {} as Record<ReactionType, number> }
      } else {
        // Unexpected format
        reactions.value = []
        stats.value = { total: 0, by_type: {} as Record<ReactionType, number> }
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
      stats.value.by_type[reactionType] = (stats.value.by_type[reactionType] || 0) + 1
      
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
      if (stats.value.by_type[previousReaction]) {
        stats.value.by_type[previousReaction]--
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

  const getCurrentUserId = (): number => {
    // Get from auth store
    const userStr = localStorage.getItem('user')
    if (userStr) {
      const user = JSON.parse(userStr)
      return user.user_id
    }
    return 0
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
