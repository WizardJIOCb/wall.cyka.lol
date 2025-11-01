<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="isOpen" class="modal-overlay" @click="handleClose">
        <div class="modal-content" @click.stop>
          <!-- Modal Header -->
          <div class="modal-header">
            <h3>{{ title }}</h3>
            <button @click="handleClose" class="btn-close">×</button>
          </div>

          <!-- Search Bar -->
          <div class="modal-search">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search users..."
              class="search-input"
            />
          </div>

          <!-- Users List -->
          <div class="modal-body">
            <div v-if="loading" class="loading-container">
              <SkeletonLoader type="user" v-for="i in 3" :key="i" />
            </div>

            <div v-else-if="error" class="error-container">
              <p>{{ error }}</p>
              <button @click="loadUsers" class="btn-retry">Retry</button>
            </div>

            <div v-else-if="filteredUsers.length === 0" class="empty-state">
              <p>No users found</p>
            </div>

            <div v-else class="users-list">
              <div
                v-for="user in filteredUsers"
                :key="user.user_id"
                class="user-item"
              >
                <img
                  :src="user.avatar_url || '/assets/images/default-avatar.svg'"
                  :alt="user.username"
                  class="user-avatar"
                  @click="goToProfile(user.username)"
                />
                
                <div class="user-info" @click="goToProfile(user.username)">
                  <div class="user-username">@{{ user.username }}</div>
                  <div v-if="user.display_name" class="user-display-name">
                    {{ user.display_name }}
                  </div>
                  <div v-if="user.bio" class="user-bio">{{ user.bio }}</div>
                </div>

                <button
                  v-if="!isOwnUser(user.user_id)"
                  @click="toggleFollow(user)"
                  :class="['btn-follow', { following: user.is_following }]"
                  :disabled="user.isLoading"
                >
                  {{ user.isLoading ? '...' : (user.is_following ? 'Following' : 'Follow') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api/client'
import { useToast } from '@/composables/useToast'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'

interface User {
  user_id: number
  username: string
  display_name?: string
  avatar_url?: string
  bio?: string
  is_following?: boolean
  isLoading?: boolean
}

const props = defineProps<{
  isOpen: boolean
  userId: number
  type: 'followers' | 'following'
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'followChanged'): void
}>()

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const users = ref<User[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const searchQuery = ref('')

const title = computed(() => {
  return props.type === 'followers' ? 'Followers' : 'Following'
})

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value
  
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(user => 
    user.username.toLowerCase().includes(query) ||
    user.display_name?.toLowerCase().includes(query)
  )
})

const isOwnUser = (userId: number) => {
  return authStore.user?.user_id === userId
}

const loadUsers = async () => {
  loading.value = true
  error.value = null
  
  try {
    const endpoint = props.type === 'followers'
      ? `/users/${props.userId}/followers`
      : `/users/${props.userId}/following`
    
    const response = await apiClient.get(endpoint)
    
    if (response.data.success) {
      users.value = (response.data.data.users || []).map((user: User) => ({
        ...user,
        isLoading: false
      }))
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to load users'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}

const toggleFollow = async (user: User) => {
  const previousState = user.is_following
  user.isLoading = true
  
  try {
    // Optimistic update
    user.is_following = !previousState
    
    if (previousState) {
      await apiClient.delete(`/users/${user.user_id}/follow`)
    } else {
      await apiClient.post(`/users/${user.user_id}/follow`)
    }
    
    emit('followChanged')
  } catch (err: any) {
    // Rollback on error
    user.is_following = previousState
    toast.error(err.message || 'Failed to update follow status')
  } finally {
    user.isLoading = false
  }
}

const goToProfile = (username: string) => {
  router.push(`/profile/${username}`)
  handleClose()
}

const handleClose = () => {
  emit('close')
}

// Load users when modal opens
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    loadUsers()
    searchQuery.value = ''
  }
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: var(--spacing-4);
}

.modal-content {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  max-width: 500px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: var(--shadow-xl);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-4);
  border-bottom: 2px solid var(--color-border);
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.btn-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.5rem;
  color: var(--color-text-secondary);
  transition: all 0.2s;
}

.btn-close:hover {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.modal-search {
  padding: var(--spacing-4);
  border-bottom: 1px solid var(--color-border);
}

.search-input {
  width: 100%;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  font-size: 0.938rem;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-4);
}

.loading-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}

.error-container {
  text-align: center;
  padding: var(--spacing-8);
}

.error-container p {
  color: #dc2626;
  margin-bottom: var(--spacing-4);
}

.btn-retry {
  padding: var(--spacing-2) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
}

.empty-state {
  text-align: center;
  padding: var(--spacing-8);
  color: var(--color-text-secondary);
}

.users-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}

.user-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-3);
  border-radius: var(--radius-md);
  transition: background 0.2s;
}

.user-item:hover {
  background: var(--color-bg-secondary);
}

.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  cursor: pointer;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  cursor: pointer;
  min-width: 0;
}

.user-username {
  font-weight: 600;
  color: var(--color-text-primary);
}

.user-display-name {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.user-bio {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin-top: var(--spacing-1);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.btn-follow {
  padding: var(--spacing-2) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-follow:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.btn-follow.following {
  background: transparent;
  color: var(--color-text-primary);
  border: 2px solid var(--color-border);
}

.btn-follow.following:hover:not(:disabled) {
  background: #fee2e2;
  color: #dc2626;
  border-color: #dc2626;
}

.btn-follow:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .modal-content,
.modal-fade-leave-active .modal-content {
  transition: transform 0.3s;
}

.modal-fade-enter-from .modal-content,
.modal-fade-leave-to .modal-content {
  transform: scale(0.9);
}
</style>
