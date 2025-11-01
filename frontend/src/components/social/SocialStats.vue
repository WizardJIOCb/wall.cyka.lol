<template>
  <div class="social-stats" :class="{'social-stats--compact': compact}">
    <!-- Followers -->
    <div 
      class="stat-item stat-item--followers"
      :class="{'stat-item--clickable': clickable}"
      @click="handleClick('followers')"
    >
      <div class="stat-value">{{ formatCount(followersCount) }}</div>
      <div class="stat-label">{{ followersLabel }}</div>
    </div>

    <!-- Following -->
    <div 
      class="stat-item stat-item--following"
      :class="{'stat-item--clickable': clickable}"
      @click="handleClick('following')"
    >
      <div class="stat-value">{{ formatCount(followingCount) }}</div>
      <div class="stat-label">{{ followingLabel }}</div>
    </div>

    <!-- Optional: Mutual follows -->
    <div 
      v-if="showMutual && mutualCount !== null"
      class="stat-item stat-item--mutual"
      :class="{'stat-item--clickable': clickable}"
      @click="handleClick('mutual')"
    >
      <div class="stat-value">{{ formatCount(mutualCount) }}</div>
      <div class="stat-label">Mutual</div>
    </div>

    <!-- Optional: Posts count -->
    <div 
      v-if="showPosts && postsCount !== null"
      class="stat-item stat-item--posts"
      :class="{'stat-item--clickable': clickable}"
      @click="handleClick('posts')"
    >
      <div class="stat-value">{{ formatCount(postsCount) }}</div>
      <div class="stat-label">{{ postsLabel }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  followersCount: number
  followingCount: number
  mutualCount?: number | null
  postsCount?: number | null
  compact?: boolean
  clickable?: boolean
  showMutual?: boolean
  showPosts?: boolean
  followersLabel?: string
  followingLabel?: string
  postsLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  mutualCount: null,
  postsCount: null,
  compact: false,
  clickable: true,
  showMutual: false,
  showPosts: false,
  followersLabel: 'Followers',
  followingLabel: 'Following',
  postsLabel: 'Posts'
})

const emit = defineEmits<{
  'click:followers': []
  'click:following': []
  'click:mutual': []
  'click:posts': []
}>()

// Methods
const formatCount = (count: number): string => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1).replace(/\.0$/, '') + 'M'
  }
  if (count >= 1000) {
    return (count / 1000).toFixed(1).replace(/\.0$/, '') + 'K'
  }
  return count.toString()
}

const handleClick = (type: 'followers' | 'following' | 'mutual' | 'posts') => {
  if (!props.clickable) return
  
  switch (type) {
    case 'followers':
      emit('click:followers')
      break
    case 'following':
      emit('click:following')
      break
    case 'mutual':
      emit('click:mutual')
      break
    case 'posts':
      emit('click:posts')
      break
  }
}
</script>

<style scoped>
.social-stats {
  display: flex;
  gap: 2rem;
  padding: 1.5rem;
  background: var(--color-background);
  border-radius: 0.75rem;
  border: 1px solid var(--color-border);
}

.social-stats--compact {
  gap: 1rem;
  padding: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  transition: all 0.2s ease;
}

.stat-item--clickable {
  cursor: pointer;
}

.stat-item--clickable:hover {
  transform: translateY(-2px);
}

.stat-item--clickable:hover .stat-value {
  color: var(--color-primary);
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-text-primary);
  line-height: 1;
  transition: color 0.2s;
}

.social-stats--compact .stat-value {
  font-size: 1.5rem;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  font-weight: 500;
  text-transform: capitalize;
}

.social-stats--compact .stat-label {
  font-size: 0.75rem;
}

/* Specific stat colors (optional) */
.stat-item--followers:hover .stat-value {
  color: var(--color-primary);
}

.stat-item--following:hover .stat-value {
  color: var(--color-secondary);
}

.stat-item--mutual:hover .stat-value {
  color: var(--color-success);
}

.stat-item--posts:hover .stat-value {
  color: var(--color-info);
}

/* Responsive */
@media (max-width: 640px) {
  .social-stats {
    gap: 1rem;
    padding: 1rem;
    justify-content: space-around;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .stat-label {
    font-size: 0.75rem;
  }

  .social-stats--compact .stat-value {
    font-size: 1.25rem;
  }

  .social-stats--compact .stat-label {
    font-size: 0.7rem;
  }
}

/* Animation for count updates */
@keyframes countUpdate {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

.stat-value.updating {
  animation: countUpdate 0.3s ease;
}
</style>
