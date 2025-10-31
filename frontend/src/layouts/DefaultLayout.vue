<template>
  <div class="app-layout">
    <!-- Header -->
    <AppHeader />

    <!-- Main Container -->
    <div class="main-container">
      <!-- Sidebar -->
      <AppSidebar />

      <!-- Main Content -->
      <main class="content" role="main">
        <slot />
      </main>

      <!-- Right Widgets -->
      <AppWidgets />
    </div>

    <!-- Mobile Bottom Navigation -->
    <AppBottomNav />

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container" aria-live="polite"></div>

    <!-- Modal Container - Dynamically created when needed -->
  </div>
</template>

<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppBottomNav from '@/components/layout/AppBottomNav.vue'
import AppWidgets from '@/components/layout/AppWidgets.vue'
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-container {
  flex: 1;
  display: flex;
  max-width: 1920px;
  margin: 0 auto;
  width: 100%;
}

.content {
  flex: 1;
  min-height: calc(100vh - var(--header-height));
  padding: var(--spacing-4);
}

@media (max-width: 768px) {
  .content {
    padding: var(--spacing-2);
    padding-bottom: calc(var(--bottom-nav-height) + var(--spacing-2));
  }
}
</style>
