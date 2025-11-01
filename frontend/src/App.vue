<template>
  <component :is="layout">
    <router-view v-slot="{ Component, route }">
      <component :is="Component" :key="route.name === 'wall' ? route.fullPath : route.name" />
    </router-view>
  </component>

  <!-- Global Toast Component -->
  <ToastContainer />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useThemeStore } from '@/stores/theme'
import { useAuthStore } from '@/stores/auth'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import MinimalLayout from '@/layouts/MinimalLayout.vue'
import ToastContainer from '@/components/common/ToastContainer.vue'

const route = useRoute()
const themeStore = useThemeStore()
const authStore = useAuthStore()

// Determine which layout to use based on route meta
const layout = computed(() => {
  const layoutName = route.meta.layout as string
  
  switch (layoutName) {
    case 'auth':
      return AuthLayout
    case 'minimal':
      return MinimalLayout
    default:
      return DefaultLayout
  }
})

// Initialize app
onMounted(async () => {
  themeStore.init()
  await authStore.init()
})


</script>

<style>
/* Page transitions */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.page-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
