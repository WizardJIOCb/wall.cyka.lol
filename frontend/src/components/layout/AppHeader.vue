<template>
  <header class="app-header">
    <div class="header-content">
      <div class="header-left">
        <button
          class="btn-icon mobile-menu-toggle"
          aria-label="Toggle menu"
          @click="toggleSidebar"
        >
          <span class="hamburger-icon">☰</span>
        </button>
        <router-link to="/" class="logo">
          <span class="logo-icon">🧱</span>
          <span class="logo-text">Wall</span>
        </router-link>
      </div>

      <div class="header-center">
        <div class="search-container">
          <span class="search-icon">🔍</span>
          <input
            v-model="headerSearchQuery"
            type="search"
            placeholder="Search posts, walls, users..."
            class="search-input"
            aria-label="Search"
            @keyup.enter="handleHeaderSearch"
            maxlength="200"
          />
        </div>
      </div>

      <div class="header-right">
        <AppButton variant="primary" size="sm" class="create-btn" @click="handleCreate">
          <span class="icon">+</span>
          <span class="text">Create</span>
        </AppButton>

        <button class="btn-icon" aria-label="Notifications" @click="goToNotifications">
          <span class="icon">🔔</span>
          <span v-if="unreadCount > 0" class="badge">{{ formatCount(unreadCount) }}</span>
        </button>

        <AppDropdown position="bottom-right">
          <template #trigger>
            <AppAvatar :src="currentUser?.avatar_url" :name="currentUser?.display_name" size="sm" />
          </template>
          
          <router-link to="/profile" class="dropdown-item">
            <span class="icon">👤</span>
            <span>My Profile</span>
          </router-link>
          <router-link to="/settings" class="dropdown-item">
            <span class="icon">⚙️</span>
            <span>Settings</span>
          </router-link>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item" @click="handleLogout">
            <span class="icon">🚪</span>
            <span>Logout</span>
          </button>
        </AppDropdown>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUIStore } from '@/stores/ui'
import { useNotifications } from '@/composables/useNotifications'
import { useToast } from '@/composables/useToast'
import AppButton from '@/components/common/AppButton.vue'
import AppAvatar from '@/components/common/AppAvatar.vue'
import AppDropdown from '@/components/common/AppDropdown.vue'

const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUIStore()
const toast = useToast()
const { unreadCount } = useNotifications()

const currentUser = computed(() => authStore.user)
const headerSearchQuery = ref('')

const formatCount = (count: number) => {
  if (count > 99) return '99+'
  return count.toString()
}

const goToNotifications = () => {
  router.push('/notifications')
}

const toggleSidebar = () => {
  uiStore.toggleSidebar()
}

const handleCreate = () => {
  uiStore.openCreatePostModal()
}

const handleLogout = async () => {
  await authStore.logout()
}

// Header search functionality
const handleHeaderSearch = () => {
  const query = headerSearchQuery.value.trim()
  if (query.length >= 2) {
    router.push({ path: '/search', query: { q: query } })
    headerSearchQuery.value = ''
  } else if (query.length > 0) {
    toast.warning('Please enter at least 2 characters to search')
  }
}
</script>

<style scoped>
.app-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  height: var(--header-height, 64px);
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  padding: 0 var(--spacing-4);
  max-width: 1920px;
  margin: 0 auto;
}

.header-left,
.header-right {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
}

.header-center {
  flex: 1;
  max-width: 600px;
  margin: 0 var(--spacing-4);
}

.mobile-menu-toggle {
  display: none;
}

.logo {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  text-decoration: none;
  color: var(--text-primary);
  font-weight: 700;
  font-size: 1.25rem;
}

.logo-icon {
  font-size: 1.5rem;
}

.search-container {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
}

.search-input {
  width: 100%;
  height: 40px;
  padding: var(--spacing-2) var(--spacing-3);
  padding-left: 38px;
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius-full);
  font-size: 0.95rem;
  color: var(--text-primary);
  line-height: 1.4;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary);
}

.search-icon {
  position: absolute;
  left: var(--spacing-3);
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.1rem;
  color: var(--text-primary);
  opacity: 0.5;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  height: 1.1rem;
}

.btn-icon {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  padding: var(--spacing-2);
  color: var(--text-primary);
  font-size: 1.25rem;
  border-radius: var(--radius-md);
  transition: background 0.2s ease;
}

.btn-icon:hover {
  background: var(--surface-hover);
}

.badge {
  position: absolute;
  top: 0;
  right: 0;
  background: var(--danger);
  color: white;
  font-size: 0.7rem;
  padding: 2px 6px;
  border-radius: var(--radius-full);
  font-weight: 600;
  min-width: 18px;
  text-align: center;
}

.create-btn .text {
  display: inline;
}

@media (max-width: 1024px) {
  .header-center {
    max-width: 400px;
  }
}

@media (max-width: 768px) {
  .mobile-menu-toggle {
    display: block;
  }

  .header-center {
    display: none;
  }

  .create-btn .text {
    display: none;
  }
}
</style>
