<template>
  <div class="not-found">
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>The page you're looking for doesn't exist.</p>
    <AppButton variant="primary" @click="goHome">Go to Home</AppButton>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import AppButton from '@/components/common/AppButton.vue'

const router = useRouter()

const goHome = () => {
  router.push('/')
}
</script>

<style scoped>
.not-found {
  text-align: center;
  padding: var(--spacing-8);
}

.not-found h1 {
  font-size: 6rem;
  font-weight: 700;
  color: var(--primary);
  margin: 0;
}

.not-found h2 {
  font-size: 2rem;
  margin: var(--spacing-4) 0;
}

.not-found p {
  color: var(--text-secondary);
  margin-bottom: var(--spacing-6);
}
</style>
