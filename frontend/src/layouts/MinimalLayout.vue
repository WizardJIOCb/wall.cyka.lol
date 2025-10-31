<template>
  <div class="minimal-layout">
    <div class="minimal-content">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
// Minimal layout for 404 and other simple pages
</script>

<style scoped>
.minimal-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--background);
  padding: var(--spacing-4);
}

.minimal-content {
  max-width: 600px;
  width: 100%;
  text-align: center;
}
</style>
