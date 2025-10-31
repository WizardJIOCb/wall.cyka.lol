<template>
  <aside :class="sidebarClasses">
    <nav class="sidebar-nav" aria-label="Main navigation">
      <router-link
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        class="nav-item"
        :class="{ active: isActive(item.path) }"
      >
        <span class="nav-icon">{{ item.icon }}</span>
        <span class="nav-text">{{ item.label }}</span>
        <span v-if="item.badge" class="badge">{{ item.badge }}</span>
      </router-link>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUIStore } from '@/stores/ui'

const route = useRoute()
const uiStore = useUIStore()

const navItems = [
  { path: '/', label: 'Home', icon: '🏠' },
  { path: '/wall', label: 'My Wall', icon: '🧱' },
  { path: '/ai', label: 'AI Generate', icon: '🤖' },
  { path: '/messages', label: 'Messages', icon: '💬', badge: 0 },
  { path: '/discover', label: 'Discover', icon: '🔍' },
  { path: '/notifications', label: 'Notifications', icon: '🔔' },
  { path: '/settings', label: 'Settings', icon: '⚙️' }
]

const sidebarClasses = computed(() => [
  'sidebar',
  {
    'sidebar-open': uiStore.sidebarOpen
  }
])

const isActive = (path: string) => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<style scoped>
.sidebar {
  width: var(--sidebar-width, 260px);
  background: var(--surface);
  border-right: 1px solid var(--border);
  padding: var(--spacing-4);
  overflow-y: auto;
  position: sticky;
  top: var(--header-height, 64px);
  height: calc(100vh - var(--header-height, 64px));
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
}

.nav-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-3);
  border-radius: var(--radius-md);
  text-decoration: none;
  color: var(--text-primary);
  font-weight: 500;
  transition: background 0.2s ease;
  position: relative;
}

.nav-item:hover {
  background: var(--surface-hover);
}

.nav-item.active {
  background: var(--primary-light);
  color: var(--primary);
}

.nav-icon {
  font-size: 1.25rem;
}

.nav-text {
  flex: 1;
}

.badge {
  background: var(--danger);
  color: white;
  font-size: 0.7rem;
  padding: 2px 6px;
  border-radius: var(--radius-full);
  font-weight: 600;
  min-width: 18px;
  text-align: center;
}

@media (max-width: 1024px) {
  .sidebar {
    width: 80px;
  }

  .nav-text {
    display: none;
  }

  .nav-item {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    top: var(--header-height, 64px);
    left: 0;
    width: 260px;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: 90;
  }

  .sidebar-open {
    transform: translateX(0);
  }

  .nav-text {
    display: block;
  }

  .nav-item {
    justify-content: flex-start;
  }
}
</style>
