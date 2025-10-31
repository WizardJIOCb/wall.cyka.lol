<template>
  <div class="post-list">
    <PostCard v-for="post in posts" :key="post.id" :post="post" @react="handleReact(post.id, $event)" @comment="handleComment(post.id)" @share="handleShare(post.id)" @edit="handleEdit(post.id)" @delete="handleDelete(post.id)" />
    <div v-if="loading" class="loading"><div class="spinner"></div><p>Loading posts...</p></div>
    <div v-if="!loading && !hasMore" class="end-message">No more posts</div>
  </div>
</template>

<script setup lang="ts">
import type { Post } from '@/types/models'
import PostCard from './PostCard.vue'

defineProps<{ posts: Post[]; loading: boolean; hasMore: boolean }>()
const emit = defineEmits<{
  react: [postId: number, type: string]
  comment: [postId: number]
  share: [postId: number]
  edit: [postId: number]
  delete: [postId: number]
}>()

const handleReact = (id: number, type: string) => emit('react', id, type)
const handleComment = (id: number) => emit('comment', id)
const handleShare = (id: number) => emit('share', id)
const handleEdit = (id: number) => emit('edit', id)
const handleDelete = (id: number) => emit('delete', id)
</script>

<style scoped>
.post-list { max-width: 800px; margin: 0 auto; }
.loading, .end-message { text-align: center; padding: var(--spacing-6); color: var(--text-secondary); }
.spinner { width: 40px; height: 40px; border: 3px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto var(--spacing-3); }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
