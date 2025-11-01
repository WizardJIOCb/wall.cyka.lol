<template>
  <div class="user-list">
    <!-- Header -->
    <div class="user-list__header">
      <h3 class="user-list__title">{{ title }}</h3>
      <span v-if="totalCount !== null" class="user-list__count">
        {{ formatCount(totalCount) }}
      </span>
    </div>

    <!-- Search/Filter -->
    <div v-if="showSearch" class="user-list__search">
      <input
        v-model="searchQuery"
        type="text"
        class="search-input"
        :placeholder="searchPlaceholder"
        @input="handleSearchInput"
      />
    </div>

    <!-- Loading state -->
    <div v-if="isLoading && users.length === 0" class="user-list__loading">
      <div v-for="i in 5" :key="i" class="user-skeleton">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-content">
          <div class="skeleton-line skeleton-line--title"></div>
          <div class="skeleton-line skeleton-line--subtitle"></div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!isLoading && users.length === 0" class="user-list__empty">
      <p class="empty-message">{{ emptyMessage }}</p>
    </div>

    <!-- User list -->
    <div v-else class="user-list__items">
      <div
        v-for="user in filteredUsers"
        :key="user.user_id"
        class="user-item"
      >
        <!-- Avatar -->
        <router-link :to="`/users/${user.user_id}`" class="user-item__avatar-link">
          <img
            :src="user.avatar_url || '/default-avatar.png'"
            :alt="user.display_name || user.username"
            class="user-item__avatar"
          />
        </router-link>

        <!-- User info -->
        <div class="user-item__info">
          <router-link :to="`/users/${user.user_id}`" class="user-item__name">
            {{ user.display_name || user.username }}
          </router-link>
          <p v-if="user.username" class="user-item__username">@{{ user.username }}</p>
          <p v-if="user.bio" class="user-item__bio">{{ truncate(user.bio, 100) }}</p>

          <!-- Stats -->
          <div class="user-item__stats">
            <span class="stat">
              <strong>{{ formatCount(user.followers_count || 0) }}</strong> followers
            </span>
            <span class="stat">
              <strong>{{ formatCount(user.following_count || 0) }}</strong> following
            </span>
          </div>

          <!-- Badges -->
          <div v-if="showBadges" class="user-item__badges">
            <span v-if="user.is_mutual" class="badge badge--mutual">Mutual</span>
            <span v-if="user.is_followed_by_you" class="badge badge--following">Following</span>
          </div>
        </div>

        <!-- Follow button -->
        <div v-if="showFollowButton && user.user_id !== currentUserId" class="user-item__action">
          <FollowButton
            :user-id="user.user_id"
            :initial-follow-state="user.is_followed_by_you || false"
            size="small"
            variant="outline"
            @follow="handleUserFollowed(user.user_id)"
            @unfollow="handleUserUnfollowed(user.user_id)"
          />
        </div>
      </div>
    </div>

    <!-- Load more -->
    <div v-if="hasMore && !isLoading" class="user-list__load-more">
      <button @click="loadMore" class="load-more-button">
        Load More
      </button>
    </div>

    <!-- Loading more indicator -->
    <div v-if="isLoadingMore" class="user-list__loading-more">
      <span class="loading-spinner"></span>
      <span>Loading more...</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'
import FollowButton from './FollowButton.vue'

interface User {
  user_id: number
  username: string
  display_name?: string
  avatar_url?: string
  bio?: string
  followers_count?: number
  following_count?: number
  is_followed_by_you?: boolean
  is_following_you?: boolean
  is_mutual?: boolean
  followed_at?: string
}

interface Props {
  userId: number
  type: 'followers' | 'following'
  title?: string
  showSearch?: boolean
  showFollowButton?: boolean
  showBadges?: boolean
  initialLimit?: number
}

const props = withDefaults(defineProps<Props>(), {
  showSearch: true,
  showFollowButton: true,
  showBadges: true,
  initialLimit: 20
})

const authStore = useAuthStore()

// State
const users = ref<User[]>([])
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(user => {
    const name = (user.display_name || user.username).toLowerCase()
    const username = user.username.toLowerCase()
    const bio = (user.bio || '').toLowerCase()
    return name.includes(query) || username.includes(query) || bio.includes(query)
  })
})
const searchQuery = ref('')
const isLoading = ref(false)
const isLoadingMore = ref(false)
const hasMore = ref(false)
const currentPage = ref(1)
const totalCount = ref<number | null>(null)

// Computed
const currentUserId = computed(() => authStore.user?.id)

const title = computed(() => {
  if (props.title) return props.title
  return props.type === 'followers' ? 'Followers' : 'Following'
})

const emptyMessage = computed(() => {
  if (props.type === 'followers') {
    return props.userId === currentUserId.value 
      ? 'No followers yet. Share your profile to gain followers!'
      : 'This user has no followers yet.'
  } else {
    return props.userId === currentUserId.value
      ? 'Not following anyone yet. Discover users to follow!'
      : 'This user is not following anyone yet.'
  }
})

const searchPlaceholder = computed(() => {
  return props.type === 'followers' 
    ? 'Search followers...'
    : 'Search following...'
})

// Methods
const fetchUsers = async (reset = true) => {
  if (reset) {
    isLoading.value = true
    currentPage.value = 1
    users.value = []
  } else {
    isLoadingMore.value = true
  }

  try {
    const endpoint = props.type === 'followers'
      ? `/users/${props.userId}/followers`
      : `/users/${props.userId}/following`

    const response = await api.get(endpoint, {
      params: {
        page: currentPage.value,
        limit: props.initialLimit
      }
    })

    const data = response.data.data
    const newUsers = props.type === 'followers' ? data.followers : data.following

    if (reset) {
      users.value = newUsers
    } else {
      users.value = [...users.value, ...newUsers]
    }

    hasMore.value = data.pagination.has_more
    totalCount.value = data.pagination.total

  } catch (error: any) {
    console.error(`Failed to fetch ${props.type}:`, error)
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

const loadMore = async () => {
  if (!hasMore.value || isLoadingMore.value) return
  
  currentPage.value++
  await fetchUsers(false)
}

const handleSearchInput = () => {
  // Debouncing is handled by the computed filteredUsers
  // which filters the already-loaded users client-side
}

const handleUserFollowed = (userId: number) => {
  // Update the user's follow state in the list
  const user = users.value.find(u => u.user_id === userId)
  if (user) {
    user.is_followed_by_you = true
    // If this is mutual now
    if (user.is_following_you) {
      user.is_mutual = true
    }
  }
}

const handleUserUnfollowed = (userId: number) => {
  const user = users.value.find(u => u.user_id === userId)
  if (user) {
    user.is_followed_by_you = false
    user.is_mutual = false
  }
}

const formatCount = (count: number): string => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  }
  if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}

const truncate = (text: string, maxLength: number): string => {
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

// Lifecycle
onMounted(() => {
  fetchUsers()
})

// Expose for parent
defineExpose({
  refresh: () => fetchUsers(true)
})
</script>

<style scoped>
.user-list {
  width: 100%;
}

.user-list__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--color-border);
}

.user-list__title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  color: var(--color-text-primary);
}

.user-list__count {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  background: var(--color-background-secondary);
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
}

.user-list__search {
  margin-bottom: 1.5rem;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid var(--color-border);
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.user-list__loading,
.user-list__empty {
  padding: 2rem;
  text-align: center;
}

.user-skeleton {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  margin-bottom: 0.5rem;
  animation: pulse 1.5s ease-in-out infinite;
}

.skeleton-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--color-skeleton);
}

.skeleton-content {
  flex: 1;
}

.skeleton-line {
  height: 12px;
  background: var(--color-skeleton);
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.skeleton-line--title {
  width: 40%;
}

.skeleton-line--subtitle {
  width: 60%;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.empty-message {
  color: var(--color-text-secondary);
  font-size: 1rem;
}

.user-list__items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.user-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  border-radius: 0.5rem;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  transition: all 0.2s;
}

.user-item:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary-light);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.user-item__avatar-link {
  flex-shrink: 0;
}

.user-item__avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--color-border);
}

.user-item__info {
  flex: 1;
  min-width: 0;
}

.user-item__name {
  display: block;
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-text-primary);
  text-decoration: none;
  margin-bottom: 0.25rem;
}

.user-item__name:hover {
  color: var(--color-primary);
}

.user-item__username {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin: 0 0 0.5rem 0;
}

.user-item__bio {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
  margin: 0.5rem 0;
  line-height: 1.4;
}

.user-item__stats {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.stat {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.stat strong {
  color: var(--color-text-primary);
  font-weight: 600;
}

.user-item__badges {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge--mutual {
  background: var(--color-success-light);
  color: var(--color-success-dark);
}

.badge--following {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
}

.user-item__action {
  flex-shrink: 0;
}

.user-list__load-more {
  margin-top: 1.5rem;
  text-align: center;
}

.load-more-button {
  padding: 0.75rem 1.5rem;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.load-more-button:hover {
  background: var(--color-primary-dark);
  transform: translateY(-2px);
}

.user-list__loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem;
  color: var(--color-text-secondary);
}

.loading-spinner {
  display: inline-block;
  width: 1.2em;
  height: 1.2em;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .user-item {
    flex-direction: column;
    gap: 0.75rem;
  }

  .user-item__avatar {
    width: 50px;
    height: 50px;
  }

  .user-item__action {
    width: 100%;
  }

  .user-item__action :deep(.follow-button) {
    width: 100%;
  }

  .user-item__stats {
    flex-wrap: wrap;
  }
}
</style>
