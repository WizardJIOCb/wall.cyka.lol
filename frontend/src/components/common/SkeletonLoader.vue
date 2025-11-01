<template>
  <div class="skeleton-loader">
    <div v-if="type === 'post'" class="skeleton-post">
      <div class="skeleton-header">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-text-group">
          <div class="skeleton-text skeleton-text-lg"></div>
          <div class="skeleton-text skeleton-text-sm"></div>
        </div>
      </div>
      <div class="skeleton-content">
        <div class="skeleton-text"></div>
        <div class="skeleton-text"></div>
        <div class="skeleton-text skeleton-text-md"></div>
      </div>
      <div class="skeleton-actions">
        <div class="skeleton-button"></div>
        <div class="skeleton-button"></div>
        <div class="skeleton-button"></div>
      </div>
    </div>

    <div v-else-if="type === 'user'" class="skeleton-user">
      <div class="skeleton-avatar skeleton-avatar-lg"></div>
      <div class="skeleton-text-group">
        <div class="skeleton-text skeleton-text-md"></div>
        <div class="skeleton-text skeleton-text-sm"></div>
      </div>
      <div class="skeleton-button"></div>
    </div>

    <div v-else-if="type === 'wall'" class="skeleton-wall">
      <div class="skeleton-banner"></div>
      <div class="skeleton-wall-content">
        <div class="skeleton-text skeleton-text-lg"></div>
        <div class="skeleton-text skeleton-text-sm"></div>
        <div class="skeleton-stats">
          <div class="skeleton-text skeleton-text-xs"></div>
          <div class="skeleton-text skeleton-text-xs"></div>
        </div>
      </div>
    </div>

    <div v-else-if="type === 'comment'" class="skeleton-comment">
      <div class="skeleton-avatar skeleton-avatar-sm"></div>
      <div class="skeleton-comment-content">
        <div class="skeleton-text skeleton-text-sm"></div>
        <div class="skeleton-text"></div>
        <div class="skeleton-text skeleton-text-md"></div>
      </div>
    </div>

    <div v-else-if="type === 'notification'" class="skeleton-notification">
      <div class="skeleton-icon"></div>
      <div class="skeleton-notification-content">
        <div class="skeleton-text"></div>
        <div class="skeleton-text skeleton-text-xs"></div>
      </div>
    </div>

    <div v-else class="skeleton-default">
      <div class="skeleton-text"></div>
      <div class="skeleton-text skeleton-text-md"></div>
      <div class="skeleton-text skeleton-text-lg"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  type?: 'post' | 'user' | 'wall' | 'comment' | 'notification' | 'default'
  count?: number
}>()
</script>

<style scoped>
.skeleton-loader {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.skeleton-post,
.skeleton-user,
.skeleton-wall,
.skeleton-comment,
.skeleton-notification,
.skeleton-default {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-4);
}

.skeleton-post {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.skeleton-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
}

.skeleton-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-bg-secondary);
}

.skeleton-avatar-lg {
  width: 80px;
  height: 80px;
}

.skeleton-avatar-sm {
  width: 32px;
  height: 32px;
}

.skeleton-text-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

.skeleton-text {
  height: 16px;
  background: var(--color-bg-secondary);
  border-radius: var(--radius-sm);
}

.skeleton-text-xs {
  width: 80px;
}

.skeleton-text-sm {
  width: 120px;
}

.skeleton-text-md {
  width: 60%;
}

.skeleton-text-lg {
  width: 80%;
}

.skeleton-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

.skeleton-actions {
  display: flex;
  gap: var(--spacing-3);
}

.skeleton-button {
  width: 80px;
  height: 32px;
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
}

.skeleton-user {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-3);
  text-align: center;
}

.skeleton-wall {
  overflow: hidden;
  padding: 0;
}

.skeleton-banner {
  height: 120px;
  background: var(--color-bg-secondary);
}

.skeleton-wall-content {
  padding: var(--spacing-4);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

.skeleton-stats {
  display: flex;
  gap: var(--spacing-4);
  margin-top: var(--spacing-2);
}

.skeleton-comment {
  display: flex;
  gap: var(--spacing-3);
}

.skeleton-comment-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

.skeleton-notification {
  display: flex;
  gap: var(--spacing-3);
  align-items: center;
}

.skeleton-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--color-bg-secondary);
  flex-shrink: 0;
}

.skeleton-notification-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

.skeleton-default {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}
</style>
