<template>
  <div class="search-view">
    <!-- Search Header -->
    <div class="search-header">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          class="search-input"
          :placeholder="$t('common.actions.search') + '...'"
          @keyup.enter="performSearch"
          @input="handleInput"
          aria-label="Search"
          maxlength="200"
        />
        <button
          v-if="searchQuery.length > 0"
          class="clear-button"
          @click="clearSearch"
          aria-label="Clear search"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- Result Type Tabs -->
    <div class="tabs-container" role="tablist">
      <button
        v-for="tab in tabs"
        :key="tab.type"
        :class="['tab', { 'tab--active': activeType === tab.type }]"
        @click="selectTab(tab.type)"
        role="tab"
        :aria-selected="activeType === tab.type"
      >
        <span class="tab-icon">{{ tab.icon }}</span>
        <span class="tab-label">{{ tab.label }}</span>
        <span v-if="counts[tab.countKey] > 0" class="tab-count">{{ formatCount(counts[tab.countKey]) }}</span>
      </button>
    </div>

    <!-- Sort Controls -->
    <div class="controls-bar">
      <div class="sort-controls">
        <label class="sort-label">Sort by:</label>
        <button
          v-for="option in sortOptions"
          :key="option.value"
          :class="['sort-btn', { 'sort-btn--active': sortBy === option.value }]"
          @click="selectSort(option.value)"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-container">
      <div class="spinner"></div>
      <p>Searching...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <span class="error-icon">⚠️</span>
      <p class="error-message">{{ error }}</p>
      <button @click="performSearch" class="retry-btn">Retry</button>
    </div>

    <!-- Empty State - No Query -->
    <div v-else-if="!searchQuery || searchQuery.length < 2" class="empty-state">
      <span class="empty-icon">🔍</span>
      <h3>Enter a search term to begin</h3>
      <p>Search for posts, walls, users, and AI applications</p>
    </div>

    <!-- Empty State - No Results -->
    <div v-else-if="!isLoading && counts.total === 0" class="empty-state">
      <span class="empty-icon">😕</span>
      <h3>No results found for "{{ searchQuery }}"</h3>
      <p>Try different keywords or check your spelling</p>
    </div>

    <!-- Results Display -->
    <div v-else class="results-container">
      <!-- All Results -->
      <div v-if="activeType === 'all'" class="all-results">
        <!-- Posts Section -->
        <section v-if="searchResults?.posts?.items?.length > 0" class="result-section">
          <h2 class="section-title">📝 Posts ({{ searchResults.posts.total }})</h2>
          <div class="posts-list">
            <div v-for="post in searchResults.posts.items" :key="post.id" class="post-card">
              <div class="post-header">
                <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="post-avatar" />
                <div class="post-meta">
                  <span class="post-author">@{{ post.author_username }}</span>
                  <span class="post-time">{{ formatDate(post.created_at) }}</span>
                </div>
              </div>
              <h3 v-if="post.title" class="post-title">{{ post.title }}</h3>
              <div class="post-content">{{ truncate(post.content, 200) }}</div>
              <div class="post-stats">
                <span>👍 {{ post.reaction_count || 0 }}</span>
                <span>💬 {{ post.comment_count || 0 }}</span>
                <span>👁️ {{ post.view_count || 0 }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Walls Section -->
        <section v-if="searchResults?.walls?.items?.length > 0" class="result-section">
          <h2 class="section-title">🖼️ Walls ({{ searchResults.walls.total }})</h2>
          <div class="walls-grid">
            <div
              v-for="wall in searchResults.walls.items"
              :key="wall.id"
              class="wall-card"
              @click="$router.push(`/wall/${wall.id}`)"
            >
              <div class="wall-banner" :style="{ background: wall.theme || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }"></div>
              <div class="wall-content">
                <h3>{{ wall.name }}</h3>
                <p class="wall-owner">@{{ wall.owner_username }}</p>
                <p v-if="wall.description" class="wall-desc">{{ truncate(wall.description, 100) }}</p>
                <div class="wall-stats">
                  <span>📝 {{ wall.post_count || 0 }}</span>
                  <span>👥 {{ wall.subscriber_count || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Users Section -->
        <section v-if="searchResults?.users?.items?.length > 0" class="result-section">
          <h2 class="section-title">👥 Users ({{ searchResults.users.total }})</h2>
          <div class="users-grid">
            <div v-for="user in searchResults.users.items" :key="user.id" class="user-card">
              <img :src="user.avatar_url || '/assets/images/default-avatar.svg'" :alt="user.username" class="user-avatar" />
              <h3 class="user-name">{{ user.display_name }}</h3>
              <p class="user-username">@{{ user.username }}</p>
              <p v-if="user.bio" class="user-bio">{{ truncate(user.bio, 80) }}</p>
              <div class="user-stats">
                <span>{{ user.followers_count || 0 }} followers</span>
              </div>
            </div>
          </div>
        </section>

        <!-- AI Apps Section -->
        <section v-if="searchResults?.ai_apps?.items?.length > 0" class="result-section">
          <h2 class="section-title">🤖 AI Apps ({{ searchResults.ai_apps.total }})</h2>
          <div class="ai-apps-grid">
            <div v-for="app in searchResults.ai_apps.items" :key="app.id" class="ai-app-card">
              <h3>{{ app.title }}</h3>
              <p class="app-author">by @{{ app.author_username }}</p>
              <p v-if="app.description" class="app-desc">{{ truncate(app.description, 100) }}</p>
              <div class="app-stats">
                <span>🔄 {{ app.remix_count || 0 }}</span>
                <span>👍 {{ app.reaction_count || 0 }}</span>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Filtered Results (Single Type) -->
      <div v-else class="filtered-results">
        <!-- Posts Only -->
        <div v-if="activeType === 'post' && searchResults?.posts?.items?.length > 0" class="posts-list">
          <div v-for="post in searchResults.posts.items" :key="post.id" class="post-card">
            <div class="post-header">
              <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="post-avatar" />
              <div class="post-meta">
                <span class="post-author">@{{ post.author_username }}</span>
                <span class="post-time">{{ formatDate(post.created_at) }}</span>
              </div>
            </div>
            <h3 v-if="post.title" class="post-title">{{ post.title }}</h3>
            <div class="post-content">{{ truncate(post.content, 200) }}</div>
            <div class="post-stats">
              <span>👍 {{ post.reaction_count || 0 }}</span>
              <span>💬 {{ post.comment_count || 0 }}</span>
              <span>👁️ {{ post.view_count || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Walls Only -->
        <div v-if="activeType === 'wall' && searchResults?.walls?.items?.length > 0" class="walls-grid">
          <div
            v-for="wall in searchResults.walls.items"
            :key="wall.id"
            class="wall-card"
            @click="$router.push(`/wall/${wall.id}`)"
          >
            <div class="wall-banner" :style="{ background: wall.theme || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }"></div>
            <div class="wall-content">
              <h3>{{ wall.name }}</h3>
              <p class="wall-owner">@{{ wall.owner_username }}</p>
              <p v-if="wall.description" class="wall-desc">{{ truncate(wall.description, 100) }}</p>
              <div class="wall-stats">
                <span>📝 {{ wall.post_count || 0 }}</span>
                <span>👥 {{ wall.subscriber_count || 0 }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Only -->
        <div v-if="activeType === 'user' && searchResults?.users?.items?.length > 0" class="users-grid">
          <div v-for="user in searchResults.users.items" :key="user.id" class="user-card">
            <img :src="user.avatar_url || '/assets/images/default-avatar.svg'" :alt="user.username" class="user-avatar" />
            <h3 class="user-name">{{ user.display_name }}</h3>
            <p class="user-username">@{{ user.username }}</p>
            <p v-if="user.bio" class="user-bio">{{ truncate(user.bio, 80) }}</p>
            <div class="user-stats">
              <span>{{ user.followers_count || 0 }} followers</span>
            </div>
          </div>
        </div>

        <!-- AI Apps Only -->
        <div v-if="activeType === 'ai-app' && searchResults?.ai_apps?.items?.length > 0" class="ai-apps-grid">
          <div v-for="app in searchResults.ai_apps.items" :key="app.id" class="ai-app-card">
            <h3>{{ app.title }}</h3>
            <p class="app-author">by @{{ app.author_username }}</p>
            <p v-if="app.description" class="app-desc">{{ truncate(app.description, 100) }}</p>
            <div class="app-stats">
              <span>🔄 {{ app.remix_count || 0 }}</span>
              <span>👍 {{ app.reaction_count || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'
import { useToast } from '@/composables/useToast'
import apiClient from '@/services/api/client'

const route = useRoute()
const router = useRouter()
const toast = useToast()

// State
const searchQuery = ref('')
const activeType = ref('all')
const sortBy = ref('relevance')
const searchResults = ref<any>(null)
const counts = ref({
  total: 0,
  posts: 0,
  walls: 0,
  users: 0,
  ai_apps: 0
})
const isLoading = ref(false)
const error = ref<string | null>(null)

// Tabs configuration
const tabs = [
  { type: 'all', label: 'All', icon: '🔍', countKey: 'total' },
  { type: 'post', label: 'Posts', icon: '📝', countKey: 'posts' },
  { type: 'wall', label: 'Walls', icon: '🖼️', countKey: 'walls' },
  { type: 'user', label: 'Users', icon: '👥', countKey: 'users' },
  { type: 'ai-app', label: 'AI Apps', icon: '🤖', countKey: 'ai_apps' }
]

// Sort options
const sortOptions = [
  { value: 'relevance', label: 'Relevance' },
  { value: 'recent', label: 'Recent' },
  { value: 'popular', label: 'Popular' }
]

// Methods
const formatCount = (count: number): string => {
  if (count > 999) return `${Math.floor(count / 1000)}k+`
  return count.toString()
}

const formatDate = (dateString: string): string => {
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

const truncate = (text: string, maxLength: number): string => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const performSearch = async () => {
  const query = searchQuery.value.trim()

  // Validate query
  if (query.length < 2) {
    if (query.length > 0) {
      toast.warning('Please enter at least 2 characters to search')
    }
    return
  }

  if (query.length > 200) {
    toast.warning('Search query too long (max 200 characters)')
    return
  }

  try {
    isLoading.value = true
    error.value = null

    const response = await apiClient.get('/search', {
      params: {
        q: query,
        type: activeType.value,
        sort: sortBy.value,
        limit: 20
      }
    })

    if (response?.data) {
      searchResults.value = response.data.results
      counts.value = response.data.counts || {
        total: 0,
        posts: 0,
        walls: 0,
        users: 0,
        ai_apps: 0
      }
    }
  } catch (err: any) {
    console.error('Search error:', err)
    error.value = err.response?.data?.message || 'Failed to perform search'
    toast.error(error.value)
  } finally {
    isLoading.value = false
  }
}

const debouncedSearch = useDebounceFn(() => {
  performSearch()
}, 300)

const handleInput = () => {
  if (searchQuery.value.trim().length >= 2) {
    debouncedSearch()
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  searchResults.value = null
  counts.value = { total: 0, posts: 0, walls: 0, users: 0, ai_apps: 0 }
  error.value = null
}

const selectTab = (type: string) => {
  activeType.value = type
  router.push({ query: { ...route.query, type } })
  performSearch()
}

const selectSort = (sort: string) => {
  sortBy.value = sort
  router.push({ query: { ...route.query, sort } })
  performSearch()
}

// Watch route changes
watch(() => route.query, (newQuery) => {
  if (newQuery.q && newQuery.q !== searchQuery.value) {
    searchQuery.value = newQuery.q as string
    performSearch()
  }
  if (newQuery.type && newQuery.type !== activeType.value) {
    activeType.value = newQuery.type as string
  }
  if (newQuery.sort && newQuery.sort !== sortBy.value) {
    sortBy.value = newQuery.sort as string
  }
}, { immediate: false })

// Initialize on mount
onMounted(() => {
  // Get query from URL
  if (route.query.q) {
    searchQuery.value = route.query.q as string
  }
  if (route.query.type) {
    activeType.value = route.query.type as string
  }
  if (route.query.sort) {
    sortBy.value = route.query.sort as string
  }

  // Perform initial search if query exists
  if (searchQuery.value.length >= 2) {
    performSearch()
  }
})
</script>

<style scoped>
.search-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-4);
}

/* Search Header */
.search-header {
  margin-bottom: var(--spacing-6);
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--color-bg-elevated);
  border: 2px solid var(--color-border);
  border-radius: 12px;
  height: 48px;
  transition: border-color 0.2s;
}

.search-input-wrapper:focus-within {
  border-color: var(--color-primary);
}

.search-icon {
  position: absolute;
  left: 10px;
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
  padding: var(--spacing-3) var(--spacing-2);
  padding-left: 38px;
  border: none;
  background: transparent;
  font-size: 1rem;
  color: var(--color-text-primary);
  outline: none;
  height: 100%;
  line-height: 1.4;
}

.search-input::placeholder {
  color: var(--color-text-secondary);
}

.clear-button {
  padding: var(--spacing-2) var(--spacing-3);
  background: none;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  font-size: 1.25rem;
  transition: color 0.2s;
}

.clear-button:hover {
  color: var(--color-danger);
}

/* Tabs */
.tabs-container {
  display: flex;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-4);
  overflow-x: auto;
  border-bottom: 2px solid var(--color-border);
}

.tab {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-4);
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 500;
  white-space: nowrap;
  transition: all 0.2s;
  margin-bottom: -2px;
}

.tab:hover {
  color: var(--color-text-primary);
  background: var(--color-bg-hover);
}

.tab--active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.tab-count {
  padding: 2px 8px;
  background: var(--color-primary);
  color: white;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* Controls Bar */
.controls-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
}

.sort-controls {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.sort-label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.sort-btn {
  padding: var(--spacing-2) var(--spacing-3);
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  color: var(--color-text-secondary);
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.sort-btn:hover {
  background: var(--color-bg-hover);
  color: var(--color-text-primary);
}

.sort-btn--active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: white;
}

/* Loading State */
.loading-container {
  text-align: center;
  padding: var(--spacing-10);
}

.spinner {
  width: 48px;
  height: 48px;
  margin: 0 auto var(--spacing-4);
  border: 4px solid var(--color-border);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error State */
.error-container {
  text-align: center;
  padding: var(--spacing-10);
}

.error-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: var(--spacing-4);
}

.error-message {
  color: var(--color-danger);
  margin-bottom: var(--spacing-4);
}

.retry-btn {
  padding: var(--spacing-3) var(--spacing-6);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}

.retry-btn:hover {
  background: var(--color-primary-dark);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: var(--spacing-10);
}

.empty-icon {
  font-size: 4rem;
  display: block;
  margin-bottom: var(--spacing-4);
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 1.5rem;
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-2);
}

.empty-state p {
  color: var(--color-text-secondary);
}

/* Results */
.result-section {
  margin-bottom: var(--spacing-8);
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: var(--spacing-4);
  color: var(--color-text-primary);
}

/* Posts */
.posts-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.post-card {
  background: var(--color-bg-elevated);
  border-radius: 12px;
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

.post-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.post-content {
  color: var(--color-text-primary);
  line-height: 1.6;
  margin-bottom: var(--spacing-3);
}

.post-stats {
  display: flex;
  gap: var(--spacing-4);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  padding-top: var(--spacing-3);
  border-top: 1px solid var(--color-border);
}

/* Walls */
.walls-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--spacing-4);
}

.wall-card {
  background: var(--color-bg-elevated);
  border-radius: 12px;
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
  height: 100px;
  background-size: cover;
  background-position: center;
}

.wall-content {
  padding: var(--spacing-4);
}

.wall-content h3 {
  font-size: 1.125rem;
  font-weight: 700;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.wall-owner {
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-2);
  font-size: 0.875rem;
}

.wall-desc {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-3);
}

.wall-stats {
  display: flex;
  gap: var(--spacing-3);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

/* Users */
.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--spacing-4);
}

.user-card {
  background: var(--color-bg-elevated);
  border-radius: 12px;
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

.user-name {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: var(--spacing-1);
  color: var(--color-text-primary);
}

.user-username {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-2);
}

.user-bio {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-3);
}

.user-stats {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

/* AI Apps */
.ai-apps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: var(--spacing-4);
}

.ai-app-card {
  background: var(--color-bg-elevated);
  border-radius: 12px;
  padding: var(--spacing-4);
  box-shadow: var(--shadow-sm);
  transition: all 0.2s;
}

.ai-app-card:hover {
  box-shadow: var(--shadow-md);
}

.ai-app-card h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.app-author {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-2);
}

.app-desc {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-3);
}

.app-stats {
  display: flex;
  gap: var(--spacing-3);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

/* Responsive */
@media (max-width: 768px) {
  .search-view {
    padding: var(--spacing-2);
  }

  .tabs-container {
    gap: var(--spacing-1);
  }

  .tab {
    padding: var(--spacing-2) var(--spacing-3);
    font-size: 0.875rem;
  }

  .controls-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-3);
  }

  .walls-grid,
  .users-grid,
  .ai-apps-grid {
    grid-template-columns: 1fr;
  }
}
</style>
