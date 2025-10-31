<template>
  <div class="home-view">
    <div class="feed-header">
      <h1>Home Feed</h1>
      <AppButton variant="primary" @click="showCreatePost = true">+ Create Post</AppButton>
    </div>
    <PostList :posts="postsStore.feedPosts" :loading="postsStore.loading" :has-more="postsStore.hasMore" @react="handleReact" @comment="handleComment" @share="handleShare" @edit="handleEdit" @delete="handleDelete" />
    <PostCreator v-model="showCreatePost" :wall-id="currentWallId" @submit="handleCreatePost" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePostsStore } from '@/stores/posts'
import PostList from '@/components/posts/PostList.vue'
import PostCreator from '@/components/posts/PostCreator.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'

const postsStore = usePostsStore()
const showCreatePost = ref(false)
const currentWallId = ref(1) // Default wall ID

onMounted(() => {
  postsStore.fetchFeed()
})

useInfiniteScroll(() => {
  if (postsStore.hasMore && !postsStore.loading) {
    postsStore.loadMore()
  }
})

const handleCreatePost = async (data: any) => {
  await postsStore.createPost(data)
}

const handleReact = (postId: number, type: string) => {
  postsStore.reactToPost(postId, type)
}

const handleComment = (postId: number) => {
  console.log('Comment on post', postId)
}

const handleShare = (postId: number) => {
  console.log('Share post', postId)
}

const handleEdit = (postId: number) => {
  console.log('Edit post', postId)
}

const handleDelete = async (postId: number) => {
  if (confirm('Delete this post?')) {
    await postsStore.deletePost(postId)
  }
}
</script>

<style scoped>
.home-view { max-width: 800px; margin: 0 auto; padding: var(--spacing-4); }
.feed-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-6); }
.feed-header h1 { margin: 0; }
</style>
