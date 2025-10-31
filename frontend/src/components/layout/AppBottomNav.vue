<template>
  <nav class="bottom-nav">
    <router-link
      v-for="item in navItems"
      :key="item.path"
      :to="item.path"
      class="bottom-nav-item"
      :class="{ active: isActive(item.path) }"
    >
      <span class="icon">{{ item.icon }}</span>
      <span class="label">{{ item.label }}</span>
    </router-link>
    <button class="bottom-nav-item" @click="$emit('create')">
      <span class="icon icon-large">+</span>
      <span class="label">Create</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()

const navItems = [
  { path: '/', label: 'Home', icon: '🏠' },
  { path: '/discover', label: 'Discover', icon: '🔍' },
  { path: '/messages', label: 'Messages', icon: '💬' },
  { path: '/profile', label: 'Profile', icon: '👤' }
]

defineEmits<{
  create: []
}>()

const isActive = (path: string) => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<style scoped>
.bottom-nav {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--surface);
  border-top: 1px solid var(--border);
  padding: var(--spacing-2) 0;
  z-index: 100;
  height: var(--bottom-nav-height, 60px);
}

.bottom-nav-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-1);
  text-decoration: none;
  color: var(--text-secondary);
  background: none;
  border: none;
  cursor: pointer;
  padding: var(--spacing-1);
  transition: color 0.2s ease;
}

.bottom-nav-item.active {
  color: var(--primary);
}

.bottom-nav-item .icon {
  font-size: 1.25rem;
}

.icon-large {
  font-size: 1.5rem;
}

.bottom-nav-item .label {
  font-size: 0.7rem;
  font-weight: 500;
}

@media (max-width: 768px) {
  .bottom-nav {
    display: flex;
  }
}
</style>
