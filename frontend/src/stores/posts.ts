import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Post, PaginatedResponse, PostFilters } from '@/types/models'
import postsAPI from '@/services/api/posts'

export const usePostsStore = defineStore('posts', () => {
  // State
  const posts = ref<Post[]>([])
  const feedPosts = ref<Post[]>([])
  const currentPage = ref(1)
  const totalPages = ref(1)
  const hasMore = ref(true)
  const loading = ref(false)
  const filters = ref<PostFilters>({
    content_type: 'all',
    time_range: 'all',
    sort_by: 'recent'
  })

  // Getters
  const getPostById = computed(() => {
    return (postId: number) => posts.value.find(p => p.id === postId)
  })

  // Actions
  const fetchFeed = async (page = 1, reset = false) => {
    if (loading.value) return
    
    loading.value = true
    try {
      const response: PaginatedResponse<Post> = await postsAPI.getFeed({
        page,
        per_page: 20,
        content_type: filters.value.content_type !== 'all' ? filters.value.content_type : undefined,
        time_range: filters.value.time_range !== 'all' ? filters.value.time_range : undefined,
        sort_by: filters.value.sort_by
      })

      if (reset) {
        feedPosts.value = response.data
      } else {
        feedPosts.value.push(...response.data)
      }

      currentPage.value = response.pagination.current_page
      totalPages.value = response.pagination.total_pages
      hasMore.value = response.pagination.has_more

      // Also add to posts cache
      response.data.forEach(post => {
        const existingIndex = posts.value.findIndex(p => p.id === post.id)
        if (existingIndex >= 0) {
          posts.value[existingIndex] = post
        } else {
          posts.value.push(post)
        }
      })
    } catch (error) {
      console.error('Failed to fetch feed:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  const loadMore = async () => {
    if (hasMore.value && !loading.value) {
      await fetchFeed(currentPage.value + 1, false)
    }
  }

  const refreshFeed = async () => {
    currentPage.value = 1
    await fetchFeed(1, true)
  }

  const createPost = async (postData: any) => {
    try {
      const newPost = await postsAPI.createPost(postData)
      
      // Add to beginning of feed
      feedPosts.value.unshift(newPost)
      posts.value.unshift(newPost)
      
      return newPost
    } catch (error) {
      console.error('Failed to create post:', error)
      throw error
    }
  }

  const updatePost = async (postId: number, updates: any) => {
    try {
      const updatedPost = await postsAPI.updatePost(postId, updates)
      
      // Update in cache
      const feedIndex = feedPosts.value.findIndex(p => p.id === postId)
      if (feedIndex >= 0) {
        feedPosts.value[feedIndex] = updatedPost
      }
      
      const cacheIndex = posts.value.findIndex(p => p.id === postId)
      if (cacheIndex >= 0) {
        posts.value[cacheIndex] = updatedPost
      }
      
      return updatedPost
    } catch (error) {
      console.error('Failed to update post:', error)
      throw error
    }
  }

  const deletePost = async (postId: number) => {
    try {
      await postsAPI.deletePost(postId)
      
      // Remove from feed
      feedPosts.value = feedPosts.value.filter(p => p.id !== postId)
      posts.value = posts.value.filter(p => p.id !== postId)
    } catch (error) {
      console.error('Failed to delete post:', error)
      throw error
    }
  }

  const reactToPost = async (postId: number, reactionType: string) => {
    try {
      await postsAPI.reactToPost(postId, reactionType)
      
      // Update reaction count optimistically
      const post = posts.value.find(p => p.id === postId)
      if (post) {
        post.reactions_count = (post.reactions_count || 0) + 1
      }
    } catch (error) {
      console.error('Failed to react to post:', error)
      throw error
    }
  }

  const removeReaction = async (postId: number) => {
    try {
      await postsAPI.removeReaction(postId)
      
      // Update reaction count optimistically
      const post = posts.value.find(p => p.id === postId)
      if (post && post.reactions_count > 0) {
        post.reactions_count--
      }
    } catch (error) {
      console.error('Failed to remove reaction:', error)
      throw error
    }
  }

  const setFilters = (newFilters: Partial<PostFilters>) => {
    filters.value = { ...filters.value, ...newFilters }
    refreshFeed()
  }

  const clearFilters = () => {
    filters.value = {
      content_type: 'all',
      time_range: 'all',
      sort_by: 'recent'
    }
    refreshFeed()
  }

  return {
    // State
    posts,
    feedPosts,
    currentPage,
    totalPages,
    hasMore,
    loading,
    filters,
    
    // Getters
    getPostById,
    
    // Actions
    fetchFeed,
    loadMore,
    refreshFeed,
    createPost,
    updatePost,
    deletePost,
    reactToPost,
    removeReaction,
    setFilters,
    clearFilters
  }
})
