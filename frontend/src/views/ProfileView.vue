<template>
  <div class="profile-view">
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>{{ $t('common.labels.loading') }}</p>
    </div>

    <div v-else-if="error" class="error-container">
      <h2>{{ $t('common.labels.error') }}</h2>
      <p>{{ error }}</p>
      <button @click="$router.push('/')" class="btn-primary">Go Home</button>
    </div>

    <div v-else-if="profile" class="profile-content">
      <!-- Cover Image -->
      <div class="profile-cover" :style="coverStyle"></div>

      <!-- Profile Header -->
      <div class="profile-header">
        <div class="profile-header-content">
          <div class="profile-avatar-section">
            <img :src="profile.avatar_url || '/assets/images/default-avatar.svg'" :alt="profile.username" class="profile-avatar" />
          </div>

          <div class="profile-info">
            <div class="profile-names">
              <h1 class="profile-username">@{{ profile.username }}</h1>
              <p v-if="profile.display_name" class="profile-display-name">{{ profile.display_name }}</p>
            </div>

            <p v-if="profile.bio" class="profile-bio">{{ profile.bio }}</p>

            <div class="profile-meta">
              <span v-if="profile.location" class="meta-item">
                <span class="icon">📍</span>
                <span>{{ profile.location }}</span>
              </span>
              <span class="meta-item">
                <span class="icon">📅</span>
                <span>Joined {{ formatDate(profile.created_at) }}</span>
              </span>
            </div>

            <!-- Social Links -->
            <div v-if="profile.social_links && profile.social_links.length > 0" class="social-links">
              <a
                v-for="link in profile.social_links"
                :key="link.link_id"
                :href="link.url"
                target="_blank"
                rel="noopener noreferrer"
                class="social-link"
                :title="link.platform"
              >
                <span class="icon">🔗</span>
                <span>{{ link.platform }}</span>
              </a>
            </div>
          </div>

          <div class="profile-actions">
            <button v-if="isOwnProfile" @click="$router.push('/settings')" class="btn-secondary">
              <span class="icon">⚙️</span>
              <span>Edit Profile</span>
            </button>
            <template v-else>
              <button @click="toggleFollow" :class="['btn-primary', { following: isFollowing }]">
                <span class="icon">{{ isFollowing ? '✓' : '+' }}</span>
                <span>{{ isFollowing ? $t('common.actions.unfollow') : $t('common.actions.follow') }}</span>
              </button>
              <button @click="sendMessage" class="btn-secondary">
                <span class="icon">💬</span>
                <span>Message</span>
              </button>
            </template>
          </div>
        </div>

        <!-- Stats -->
        <div class="profile-stats">
          <div v-if="isOwnProfile" class="stat-item">
            <span class="stat-value">🧱 {{ profile.bricks_balance?.toLocaleString() || 0 }}</span>
            <span class="stat-label">Bricks</span>
          </div>
          <div class="stat-item" @click="activeTab = 'walls'">
            <span class="stat-value">{{ stats.wallsCount || 0 }}</span>
            <span class="stat-label">{{ $t('walls.stats.posts') }}</span>
          </div>
          <div class="stat-item" @click="showFollowers">
            <span class="stat-value">{{ stats.followersCount || 0 }}</span>
            <span class="stat-label">{{ $t('walls.stats.followers') }}</span>
          </div>
          <div class="stat-item" @click="showFollowing">
            <span class="stat-value">{{ stats.followingCount || 0 }}</span>
            <span class="stat-label">Following</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ stats.postsCount || 0 }}</span>
            <span class="stat-label">{{ $t('walls.stats.posts') }}</span>
          </div>
        </div>
      </div>

      <!-- Content Tabs -->
      <div class="content-tabs">
        <div class="tabs-nav">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            :class="['tab-button', { active: activeTab === tab.id }]"
            @click="activeTab = tab.id"
          >
            <span class="tab-icon">{{ tab.icon }}</span>
            <span class="tab-label">{{ tab.label }}</span>
          </button>
        </div>

        <!-- Posts Tab -->
        <div v-if="activeTab === 'posts'" class="tab-content">
          <div v-if="loadingPosts" class="loading-posts">
            <div class="spinner"></div>
            <p>{{ $t('common.labels.loading') }}</p>
          </div>

          <div v-else-if="posts.length === 0" class="empty-state">
            <p>{{ $t('common.labels.noResults') }}</p>
          </div>

          <div v-else class="posts-grid">
            <div v-for="post in posts" :key="post.post_id" class="post-card">
              <div class="post-header">
                <img :src="post.author_avatar || '/assets/images/default-avatar.svg'" :alt="post.author_username" class="post-avatar" />
                <div class="post-meta">
                  <span class="post-author">@{{ post.author_username }}</span>
                  <span class="post-time">{{ formatDate(post.created_at) }}</span>
                </div>
              </div>
              <div class="post-content">
                <div v-if="post.content_html" v-html="post.content_html"></div>
                <p v-else>{{ post.content_text }}</p>
              </div>
              <div class="post-actions">
                <button class="action-btn">
                  <span class="icon">👍</span>
                  <span>{{ post.reaction_count || 0 }}</span>
                </button>
                <button class="action-btn">
                  <span class="icon">💬</span>
                  <span>{{ post.comment_count || 0 }}</span>
                </button>
              </div>
            </div>
          </div>

          <div v-if="hasMorePosts && !loadingPosts" class="load-more">
            <button @click="loadMorePosts" class="btn-secondary">{{ $t('common.buttons.loadMore') }}</button>
          </div>
        </div>

        <!-- Walls Tab -->
        <div v-if="activeTab === 'walls'" class="tab-content">
          <div v-if="loadingWalls" class="loading-posts">
            <div class="spinner"></div>
            <p>{{ $t('common.labels.loading') }}</p>
          </div>

          <div v-else-if="walls.length === 0" class="empty-state">
            <p>No walls yet</p>
          </div>

          <div v-else class="walls-grid">
            <div v-for="wall in walls" :key="wall.wall_id" class="wall-card" @click="$router.push(`/wall/${wall.wall_id}`)">
              <div class="wall-banner" :style="{ background: wall.theme || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }"></div>
              <div class="wall-info">
                <h3 class="wall-title">{{ wall.display_name }}</h3>
                <p v-if="wall.description" class="wall-description">{{ wall.description }}</p>
                <div class="wall-stats">
                  <span>{{ wall.post_count || 0 }} posts</span>
                  <span>{{ wall.follower_count || 0 }} followers</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Media Tab -->
        <div v-if="activeTab === 'media'" class="tab-content">
          <div class="empty-state">
            <p>Media gallery coming soon</p>
          </div>
        </div>

        <!-- Likes Tab -->
        <div v-if="activeTab === 'likes'" class="tab-content">
          <div class="empty-state">
            <p>Liked posts coming soon</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api/client'

const props = defineProps<{ username?: string }>()

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loading = ref(true)
const loadingPosts = ref(false)
const loadingWalls = ref(false)
const error = ref<string | null>(null)
const profile = ref<any>(null)
const posts = ref<any[]>([])
const walls = ref<any[]>([])
const stats = ref({
  wallsCount: 0,
  followersCount: 0,
  followingCount: 0,
  postsCount: 0
})
const activeTab = ref('posts')
const isFollowing = ref(false)
const postsPage = ref(1)
const hasMorePosts = ref(true)

const tabs = [
  { id: 'posts', icon: '📝', label: 'Posts' },
  { id: 'walls', icon: '🧱', label: 'Walls' },
  { id: 'media', icon: '🖼️', label: 'Media' },
  { id: 'likes', icon: '❤️', label: 'Likes' }
]

const isOwnProfile = computed(() => {
  return profile.value && authStore.user && profile.value.user_id === authStore.user.user_id
})

const coverStyle = computed(() => {
  if (profile.value?.cover_image_url) {
    return { backgroundImage: `url(${profile.value.cover_image_url})` }
  }
  return { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const loadProfile = async () => {
  try {
    loading.value = true
    error.value = null

    const username = props.username || authStore.user?.username
    if (!username) {
      throw new Error('Username is required')
    }

    // Fetch profile data
    const response = await apiClient.get(`/users/${username}`)
    if (response.data.success && response.data.data.user) {
      profile.value = response.data.data.user
      
      // Load follow status if not own profile
      if (!isOwnProfile.value) {
        await loadFollowStatus()
      }
      
      // Load posts and walls
      await Promise.all([loadPosts(), loadWalls(), loadStats()])
    } else {
      throw new Error(response.data.message || 'Profile not found')
    }
  } catch (err: any) {
    console.error('Error loading profile:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load profile'
  } finally {
    loading.value = false
  }
}

const loadFollowStatus = async () => {
  try {
    const response = await apiClient.get(`/users/${profile.value.user_id}/follow-status`)
    if (response.data.success) {
      isFollowing.value = response.data.data.is_following || false
    }
  } catch (err) {
    console.error('Error loading follow status:', err)
  }
}

const loadPosts = async () => {
  try {
    loadingPosts.value = true
    const limit = 20
    const offset = (postsPage.value - 1) * limit
    
    const response = await apiClient.get(`/users/${profile.value.user_id}/posts?limit=${limit}&offset=${offset}`)
    if (response.data.success && response.data.data.posts) {
      if (postsPage.value === 1) {
        posts.value = response.data.data.posts
      } else {
        posts.value = [...posts.value, ...response.data.data.posts]
      }
      hasMorePosts.value = response.data.data.posts.length === limit
    }
  } catch (err) {
    console.error('Error loading posts:', err)
  } finally {
    loadingPosts.value = false
  }
}

const loadWalls = async () => {
  try {
    loadingWalls.value = true
    const response = await apiClient.get(`/users/${profile.value.user_id}/walls`)
    if (response.data.success && response.data.data.walls) {
      walls.value = response.data.data.walls
    }
  } catch (err) {
    console.error('Error loading walls:', err)
  } finally {
    loadingWalls.value = false
  }
}

const loadStats = async () => {
  // Stats would come from profile data or separate endpoint
  stats.value = {
    wallsCount: walls.value.length,
    followersCount: profile.value.followers_count || 0,
    followingCount: profile.value.following_count || 0,
    postsCount: posts.value.length
  }
}

const loadMorePosts = () => {
  postsPage.value++
  loadPosts()
}

const toggleFollow = async () => {
  try {
    if (isFollowing.value) {
      await apiClient.delete(`/users/${profile.value.user_id}/follow`)
      isFollowing.value = false
      stats.value.followersCount--
    } else {
      await apiClient.post(`/users/${profile.value.user_id}/follow`)
      isFollowing.value = true
      stats.value.followersCount++
    }
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to update follow status')
  }
}

const sendMessage = () => {
  router.push(`/messages?user=${profile.value.username}`)
}

const showFollowers = () => {
  console.log('Show followers modal')
}

const showFollowing = () => {
  console.log('Show following modal')
}

onMounted(() => {
  loadProfile()
})
</script>

<style scoped>
.profile-view {
  max-width: 1200px;
  margin: 0 auto;
}

.loading-container,
.error-container {
  text-align: center;
  padding: var(--spacing-8);
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto var(--spacing-4);
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.profile-cover {
  height: 300px;
  background-size: cover;
  background-position: center;
}

.profile-header {
  background: var(--color-bg-elevated);
  padding: var(--spacing-6);
  margin-top: -80px;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  box-shadow: var(--shadow-lg);
}

.profile-header-content {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: var(--spacing-6);
  align-items: start;
}

.profile-avatar {
  width: 160px;
  height: 160px;
  border-radius: 50%;
  border: 6px solid var(--color-bg-elevated);
  object-fit: cover;
  box-shadow: var(--shadow-md);
}

.profile-info {
  padding-top: var(--spacing-4);
}

.profile-username {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: var(--spacing-1);
  color: var(--color-text-primary);
}

.profile-display-name {
  font-size: 1.25rem;
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-3);
}

.profile-bio {
  font-size: 1rem;
  line-height: 1.6;
  margin-bottom: var(--spacing-3);
  color: var(--color-text-primary);
}

.profile-meta {
  display: flex;
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-3);
}

.meta-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  color: var(--color-text-secondary);
}

.social-links {
  display: flex;
  gap: var(--spacing-3);
  flex-wrap: wrap;
}

.social-link {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2) var(--spacing-4);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-full);
  text-decoration: none;
  color: var(--color-text-primary);
  transition: all 0.2s;
}

.social-link:hover {
  background: var(--color-primary);
  color: white;
  transform: translateY(-2px);
}

.profile-actions {
  display: flex;
  gap: var(--spacing-3);
  padding-top: var(--spacing-4);
}

.profile-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: var(--spacing-4);
  margin-top: var(--spacing-6);
  padding-top: var(--spacing-6);
  border-top: 2px solid var(--color-border);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transition: transform 0.2s;
}

.stat-item:hover {
  transform: translateY(-2px);
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-primary);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.content-tabs {
  background: var(--color-bg-elevated);
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
  overflow: hidden;
}

.tabs-nav {
  display: flex;
  border-bottom: 2px solid var(--color-border);
}

.tab-button {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-2);
  padding: var(--spacing-4);
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  color: var(--color-text-secondary);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-button:hover {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.tab-button.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.tab-content {
  padding: var(--spacing-6);
}

.loading-posts {
  text-align: center;
  padding: var(--spacing-8);
}

.empty-state {
  text-align: center;
  padding: var(--spacing-8);
  color: var(--color-text-secondary);
}

.posts-grid {
  display: grid;
  gap: var(--spacing-4);
}

.post-card {
  background: var(--color-bg-secondary);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
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
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
}

.walls-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--spacing-4);
}

.wall-card {
  background: var(--color-bg-secondary);
  border-radius: var(--radius-lg);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
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

.wall-info {
  padding: var(--spacing-4);
}

.wall-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.wall-description {
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
}

.load-more {
  text-align: center;
  margin-top: var(--spacing-6);
}

.btn-primary,
.btn-secondary {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-6);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
}

.btn-primary.following {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  border: 2px solid var(--color-border);
}

.btn-secondary {
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
}

.btn-secondary:hover {
  background: var(--color-primary);
  color: white;
}

@media (max-width: 768px) {
  .profile-cover {
    height: 150px;
  }
  
  .profile-header {
    margin-top: -40px;
  }
  
  .profile-header-content {
    grid-template-columns: 1fr;
    text-align: center;
  }
  
  .profile-avatar {
    width: 100px;
    height: 100px;
    margin: 0 auto;
  }
  
  .profile-actions {
    justify-content: center;
  }
  
  .profile-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .walls-grid {
    grid-template-columns: 1fr;
  }
}
</style>
