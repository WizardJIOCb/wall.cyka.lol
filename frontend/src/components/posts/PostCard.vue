<template>
  <article class="post-card">
    <div class="post-header">
      <AppAvatar :src="post.user?.avatar_url" :name="post.user?.display_name || post.user?.username" size="md" />
      <div class="post-author">
        <router-link :to="`/profile/${post.user?.username}`" class="author-name">
          {{ post.user?.display_name || post.user?.username }}
        </router-link>
        <span class="post-time">{{ formatTime(post.created_at) }}</span>
      </div>
      <AppDropdown v-if="canEdit" position="bottom-right">
        <template #trigger>
          <button class="btn-icon">⋮</button>
        </template>
        <button class="dropdown-item" @click="$emit('edit')">Edit</button>
        <button class="dropdown-item danger" @click="$emit('delete')">Delete</button>
      </AppDropdown>
    </div>

    <div class="post-content" v-html="post.content_html || post.content"></div>

    <div v-if="post.media && post.media.length" class="post-media">
      <img v-for="media in post.media" :key="media.id" :src="media.file_path" :alt="'Media'" />
    </div>

    <div class="post-actions">
      <button class="action-btn" @click="toggleReaction">
        <span>{{ hasReacted ? '❤️' : '🤍' }}</span>
        <span v-if="post.reactions_count">{{ post.reactions_count }}</span>
      </button>
      <button class="action-btn" @click="$emit('comment')">
        <span>💬</span>
        <span v-if="post.comments_count">{{ post.comments_count }}</span>
      </button>
      <button class="action-btn" @click="$emit('share')">
        <span>🔄</span>
        <span v-if="post.shares_count">{{ post.shares_count }}</span>
      </button>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Post } from '@/types/models'
import AppAvatar from '@/components/common/AppAvatar.vue'
import AppDropdown from '@/components/common/AppDropdown.vue'
import { formatRelativeTime } from '@/utils/formatting'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{ post: Post }>()
const emit = defineEmits<{
  edit: []
  delete: []
  comment: []
  share: []
  react: [type: string]
}>()

const authStore = useAuthStore()
const hasReacted = computed(() => false) // TODO: Track user reactions
const canEdit = computed(() => authStore.user?.id === props.post.user_id)

const formatTime = (date: string) => formatRelativeTime(date)
const toggleReaction = () => emit('react', 'like')
</script>

<style scoped>
.post-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: var(--spacing-4); margin-bottom: var(--spacing-4); }
.post-header { display: flex; align-items: center; gap: var(--spacing-3); margin-bottom: var(--spacing-3); }
.post-author { flex: 1; }
.author-name { font-weight: 600; color: var(--text-primary); text-decoration: none; }
.author-name:hover { text-decoration: underline; }
.post-time { display: block; font-size: 0.85rem; color: var(--text-secondary); }
.post-content { margin-bottom: var(--spacing-3); line-height: 1.6; }
.post-media { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-2); margin-bottom: var(--spacing-3); }
.post-media img { width: 100%; border-radius: var(--radius-md); }
.post-actions { display: flex; gap: var(--spacing-4); padding-top: var(--spacing-3); border-top: 1px solid var(--border); }
.action-btn { background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: var(--spacing-2); color: var(--text-secondary); font-size: 0.9rem; padding: var(--spacing-2); border-radius: var(--radius-md); transition: all 0.2s; }
.action-btn:hover { background: var(--surface-hover); color: var(--text-primary); }
.dropdown-item.danger { color: var(--danger); }
</style>
