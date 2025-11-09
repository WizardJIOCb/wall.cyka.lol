<template>
  <div class="discover-view">
    <div class="discover-header">
      <h1>{{ $t('navigation.discover') }}</h1>
    </div>

    <!-- Search Bar -->
    <div class="search-section">
      <div class="search-container">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('common.actions.search') + '...'"
          @keyup.enter="performSearch"
          class="search-input"
        />
      </div>
    </div>

    <!-- Trending Walls Section -->
    <section class="content-section">
      <div class="section-header">
        <h2>🔥 Trending Walls</h2>
        <select v-model="trendingTimeframe" @change="loadTrendingWalls" class="timeframe-select">
          <option value="24h">{{ $t('discover.timeframes.24h') }}</option>
          <option value="7d">{{ $t('discover.timeframes.7d') }}</option>
          <option value="30d">{{ $t('discover.timeframes.30d') }}</option>
        </select>
      </div>

      <div v-if="loadingTrending" class="loading-container">
        <div class="spinner"></div>
      </div>

      <div v-else-if="trendingWalls.length === 0" class="empty-state">
        <p>{{ $t('discover.noTrendingWalls') }}</p>
      </div>

      <div v-else class="walls-carousel">
        <div
          v-for="wall in trendingWalls"
          :key="wall.wall_id"
          class="wall-card"
          @click="$router.push(`/wall/${wall.wall_id}`)"
        >
          <div class="wall-banner" :style="{ background: wall.theme || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }"></div>
          <div class="wall-content">
            <h3>{{ wall.display_name }}</h3>
            <p class="wall-owner">@{{ wall.owner_username }}</p>
            <p v-if="wall.description" class="wall-desc">{{ wall.description }}</p>
            <div class="wall-stats">
              <span>📝 {{ wall.post_count || 0 }} {{ $t('common.posts') }}</span>
              <span>👥 {{ wall.follower_count || 0 }} {{ $t('common.followers') }}</span>
              <span>👁️ {{ wall.view_count || 0 }} {{ $t('common.views') }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Popular Posts Section -->
    <section class="content-section">
      <div class="section-header">
        <h2>⭐ {{ $t('discover.popularPosts') }}</h2>
        <div class="posts-controls">
          <select v-model="postsTimeframe" @change="loadPopularPosts" class="timeframe-select">
            <option value="24h">{{ $t('discover.timeframes.24h') }}</option>
            <option value="7d">{{ $t('discover.timeframes.7d') }}</option>
            <option value="30d">{{ $t('discover.timeframes.30d') }}</option>
          </select>
          <select v-model="postsSortBy" @change="loadPopularPosts" class="sort-select">
            <option value="popularity">{{ $t('discover.sort.popularity') }}</option>
            <option value="reactions">{{ $t('discover.sort.reactions') }}</option>
            <option value="comments">{{ $t('discover.sort.comments') }}</option>
            <option value="views">{{ $t('discover.sort.views') }}</option>
          </select>
        </div>
      </div>

      <div v-if="loadingPosts" class="loading-container">
        <div class="spinner"></div>
      </div>

      <div v-else-if="popularPosts.length === 0" class="empty-state">
        <p>{{ $t('discover.noPopularPosts') }}</p>
      </div>

      <div v-else class="posts-grid">
        <div v-for="post in popularPosts" :key="post.post_id" class="post-card">
          <div class="post-header">
            <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="post-avatar" />
            <div class="post-meta">
              <span class="post-author">@{{ post.author_username }}</span>
              <span class="post-time">{{ formatDate(post.created_at) }}</span>
            </div>
          </div>
          <div class="post-content" @click="$router.push(`/wall/${post.wall_id}/post/${post.post_id}`)">
            <div v-if="post.content_html" v-html="post.content_html"></div>
            <p v-else>{{ post.content_text }}</p>
          </div>
          <div class="post-actions">
            <button class="action-btn" @click="toggleReaction(post, 'like')" :class="{ active: post.user_liked }">
              <span>👍</span>
              <span>{{ post.reaction_count || 0 }}</span>
            </button>
            <button class="action-btn" @click="openComments(post)">
              <span>💬</span>
              <span>{{ post.comment_count || 0 }}</span>
            </button>
            <button class="action-btn">
              <span>👁️</span>
              <span>{{ post.view_count || 0 }}</span>
            </button>
            <button class="action-btn">
              <span>🔄</span>
              <span>{{ post.share_count || 0 }}</span>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Suggested Users Section -->
    <section class="content-section">
      <div class="section-header">
        <h2>👥 {{ $t('discover.suggestedUsers') }}</h2>
      </div>

      <div v-if="loadingUsers" class="loading-container">
        <div class="spinner"></div>
      </div>

      <div v-else-if="suggestedUsers.length === 0" class="empty-state">
        <p>{{ $t('discover.noUserSuggestions') }}</p>
      </div>

      <div v-else class="users-grid">
        <div v-for="user in suggestedUsers" :key="user.user_id" class="user-card">
          <img :src="user.avatar_url || '/assets/images/default-avatar.svg'" :alt="user.username" class="user-avatar" />
          <h3 class="user-username">@{{ user.username }}</h3>
          <p v-if="user.bio" class="user-bio">{{ user.bio }}</p>
          <div class="user-stats">
            <span>{{ user.followers_count || 0 }} {{ $t('common.followers') }}</span>
            <span>{{ user.posts_count || 0 }} {{ $t('common.posts') }}</span>
          </div>
          <button @click="followUser(user.user_id)" class="btn-follow">
            {{ user.is_following ? $t('common.actions.unfollow') : $t('common.actions.follow') }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDebounce } from '@/composables/useDebounce'
import { useToast } from '@/composables/useToast'
import apiClient from '@/services/api/client'

const router = useRouter()
const toast = useToast()

const { value: searchQuery, debouncedValue: debouncedSearch } = useDebounce('', 300)
const trendingTimeframe = ref('7d')
const postsTimeframe = ref('7d')
const postsSortBy = ref('popularity')
const trendingWalls = ref<any[]>([])
const popularPosts = ref<any[]>([])
const suggestedUsers = ref<any[]>([])
const loadingTrending = ref(false)
const loadingPosts = ref(false)
const loadingUsers = ref(false)

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffHours < 1) return 'just now'
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

const performSearch = () => {
  if (debouncedSearch.value.trim().length >= 2) {
    router.push(`/search?q=${encodeURIComponent(debouncedSearch.value)}`)
  } else if (searchQuery.value.trim().length > 0 && searchQuery.value.trim().length < 2) {
    toast.warning('Please enter at least 2 characters to search')
  }
}

// Watch debounced search value
watch(debouncedSearch, (newValue) => {
  if (newValue.trim().length >= 2) {
    performSearch()
  }
})

const loadTrendingWalls = async () => {
  try {
    loadingTrending.value = true
    const response = await apiClient.get(`/discover/trending-walls?timeframe=${trendingTimeframe.value}&limit=6`)
    if (response && response.data) {
      trendingWalls.value = response.data.walls || response.data.data?.walls || []
    } else {
      trendingWalls.value = []
    }
  } catch (err) {
    console.error('Error loading trending walls:', err)
    trendingWalls.value = []
  } finally {
    loadingTrending.value = false
  }
}

const loadPopularPosts = async () => {
  try {
    loadingPosts.value = true
    const response = await apiClient.get(`/discover/popular-posts?timeframe=${postsTimeframe.value}&sort=${postsSortBy.value}&limit=12`)
    if (response && response.data) {
      popularPosts.value = response.data.posts || response.data.data?.posts || []
    } else {
      popularPosts.value = []
    }
  } catch (err) {
    console.error('Error loading popular posts:', err)
    popularPosts.value = []
  } finally {
    loadingPosts.value = false
  }
}

const loadSuggestedUsers = async () => {
  try {
    loadingUsers.value = true
    const response = await apiClient.get('/discover/suggested-users?limit=8')
    if (response && response.data) {
      suggestedUsers.value = response.data.users || response.data.data?.users || []
    } else {
      suggestedUsers.value = []
    }
  } catch (err) {
    console.error('Error loading suggested users:', err)
    suggestedUsers.value = []
  } finally {
    loadingUsers.value = false
  }
}

const followUser = async (userId: number) => {
  try {
    const user = suggestedUsers.value.find(u => u.user_id === userId)
    if (!user) return

    const previousState = user.is_following
    const previousCount = user.followers_count

    // Optimistic update
    user.is_following = !previousState
    user.followers_count += user.is_following ? 1 : -1

    if (previousState) {
      await apiClient.delete(`/users/${userId}/follow`)
      toast.success('Unfollowed successfully')
    } else {
      await apiClient.post(`/users/${userId}/follow`)
      toast.success('Following successfully')
    }
  } catch (err: any) {
    // Rollback on error
    const user = suggestedUsers.value.find(u => u.user_id === userId)
    if (user) {
      user.is_following = !user.is_following
      user.followers_count += user.is_following ? 1 : -1
    }
    toast.error(err.message || 'Failed to update follow status')
  }
}

const toggleReaction = async (post: any, reactionType: string) => {
  try {
    const previousState = post.user_liked
    const previousCount = post.reaction_count

    // Optimistic update
    if (previousState) {
      post.user_liked = false
      post.reaction_count = Math.max(0, post.reaction_count - 1)
      await apiClient.delete(`/posts/${post.post_id}/reactions`)
    } else {
      post.user_liked = true
      post.reaction_count = (post.reaction_count || 0) + 1
      await apiClient.post(`/posts/${post.post_id}/reactions`, { reaction_type: reactionType })
    }
  } catch (err: any) {
    // Rollback on error
    post.user_liked = !post.user_liked
    post.reaction_count = previousCount
    toast.error(err.message || 'Failed to update reaction')
  }
}

const openComments = (post: any) => {
  // Navigate to the post detail page where comments can be viewed
  router.push(`/wall/${post.wall_id}/post/${post.post_id}`)
}

onMounted(() => {
  loadTrendingWalls()
  loadPopularPosts()
  loadSuggestedUsers()
})
</script>

<style scoped>
.discover-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-6);
}

.discover-header h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: var(--spacing-6);
  color: var(--color-text-primary);
}

.search-section {
  width: 100%;
  max-width: 600px;
  margin: 0 auto var(--spacing-8);
}

.search-container {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 13px;
  top: 36%;
  transform: translateY(-50%);
  font-size: 1.1rem;
  color: var(--color-text-secondary);
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  height: 1.1rem;
}

.search-input {
  flex: 1;
  width: 100%;
  height: 48px;
  padding: 14px 18px;
  padding-left: 46px;
  border: 2px solid var(--color-border);
  border-radius: 12px;
  font-size: 1rem;
  background: var(--color-bg-elevated);
  color: var(--color-text-primary);
  transition: border-color 0.2s;
  line-height: 1.4;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.content-section {
  margin-bottom: var(--spacing-10);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
  flex-wrap: wrap;
  gap: var(--spacing-3);
}

.section-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.posts-controls {
  display: flex;
  gap: var(--spacing-3);
}

.timeframe-select,
.sort-select {
  padding: var(--spacing-2) var(--spacing-4);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-elevated);
  color: var(--color-text-primary);
  cursor: pointer;
}

.loading-container {
  text-align: center;
  padding: var(--spacing-8);
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto;
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: var(--spacing-8);
  color: var(--color-text-secondary);
}

.walls-carousel {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--spacing-4);
}

.wall-card {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: var(--shadow-sm);
}

.wall-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.wall-banner {
  height: 120px;
  background-size: cover;
  background-position: center;
}

.wall-content {
  padding: var(--spacing-4);
}

.wall-content h3 {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.wall-owner {
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-2);
}

.wall-desc {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-3);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.wall-stats {
  display: flex;
  gap: var(--spacing-4);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  flex-wrap: wrap;
}

.posts-grid {
  display: grid;
  gap: var(--spacing-4);
}

.post-card {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  transition: box-shadow 0.2s;
}

.post-card:hover {
  box-shadow: var(--shadow-md);
}

.post-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  margin-bottom: var(--spacing-3);
}

.post-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.post-meta {
  display: flex;
  flex-direction: column;
}

.post-author {
  font-weight: 600;
  color: var(--color-text-primary);
}

.post-time {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.post-content {
  margin-bottom: var(--spacing-3);
  line-height: 1.6;
  color: var(--color-text-primary);
  cursor: pointer;
}

.post-content:hover {
  opacity: 0.8;
}

.post-actions {
  display: flex;
  gap: var(--spacing-4);
  padding-top: var(--spacing-3);
  border-top: 1px solid var(--color-border);
}

.action-btn {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2) var(--spacing-3);
  background: transparent;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.action-btn.active {
  color: var(--color-primary);
}

.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--spacing-4);
}

.user-card {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
  text-align: center;
  box-shadow: var(--shadow-sm);
  transition: all 0.2s;
}

.user-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.user-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  margin: 0 auto var(--spacing-3);
}

.user-username {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.user-bio {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-3);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.user-stats {
  display: flex;
  justify-content: center;
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-3);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  flex-wrap: wrap;
}

.btn-follow {
  width: 100%;
  padding: var(--spacing-2) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-follow:hover {
  background: var(--color-primary-dark);
}

@media (max-width: 768px) {
  .walls-carousel,
  .users-grid {
    grid-template-columns: 1fr;
  }
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .posts-controls {
    width: 100%;
    justify-content: space-between;
  }
  
  .timeframe-select,
  .sort-select {
    flex: 1;
  }
}
</style>