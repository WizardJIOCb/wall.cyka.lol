<template>
  <aside class="widgets">
    <div class="widget">
      <h3 class="widget-title">Trending Topics</h3>
      <div class="widget-content">
        <div v-for="topic in trendingTopics" :key="topic.tag" class="topic-item">
          <span class="topic-tag">#{{ topic.tag }}</span>
          <span class="topic-count">{{ formatCount(topic.count) }} posts</span>
        </div>
      </div>
    </div>

    <div class="widget">
      <h3 class="widget-title">Suggested Users</h3>
      <div class="widget-content">
        <div v-for="user in suggestedUsers" :key="user.id" class="user-item">
          <AppAvatar :src="user.avatar_url" :name="user.display_name" size="sm" />
          <div class="user-info">
            <span class="user-name">{{ user.display_name }}</span>
            <span class="user-username">@{{ user.username }}</span>
          </div>
          <AppButton variant="ghost" size="sm">Follow</AppButton>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppAvatar from '@/components/common/AppAvatar.vue'
import AppButton from '@/components/common/AppButton.vue'
import { formatCompactNumber } from '@/utils/formatting'

// Mock data - will be replaced with real API calls
const trendingTopics = ref([
  { tag: 'AI', count: 1234 },
  { tag: 'WebDev', count: 987 },
  { tag: 'Design', count: 654 }
])

const suggestedUsers = ref([
  { id: 1, username: 'john_doe', display_name: 'John Doe', avatar_url: '' },
  { id: 2, username: 'jane_smith', display_name: 'Jane Smith', avatar_url: '' }
])

const formatCount = (count: number) => formatCompactNumber(count)
</script>

<style scoped>
.widgets {
  width: var(--widgets-width, 320px);
  padding: var(--spacing-4);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
  position: sticky;
  top: var(--header-height, 64px);
  height: calc(100vh - var(--header-height, 64px));
  overflow-y: auto;
}

.widget {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.widget-title {
  padding: var(--spacing-3);
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  border-bottom: 1px solid var(--border);
}

.widget-content {
  padding: var(--spacing-2);
}

.topic-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-2);
  border-radius: var(--radius-md);
  transition: background 0.2s ease;
  cursor: pointer;
}

.topic-item:hover {
  background: var(--surface-hover);
}

.topic-tag {
  font-weight: 600;
  color: var(--text-primary);
}

.topic-count {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.user-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2);
  border-radius: var(--radius-md);
  transition: background 0.2s ease;
}

.user-item:hover {
  background: var(--surface-hover);
}

.user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-username {
  font-size: 0.8rem;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 1280px) {
  .widgets {
    display: none;
  }
}
</style>
