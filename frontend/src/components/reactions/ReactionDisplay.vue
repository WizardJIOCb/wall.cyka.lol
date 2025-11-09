<template>
  <div class="reaction-display">
    <button
      v-for="reaction in displayedReactions"
      :key="reaction.type"
      @click="handleReactionClick(reaction.type)"
      class="reaction-count-button"
      :class="{ 'user-reacted': reaction.type === userReaction }"
      :title="getReactionTitle(reaction)"
    >
      <span class="emoji">{{ getReactionEmoji(reaction.type) }}</span>
      <span class="count">{{ formatCount(reaction.count) }}</span>
    </button>
    
    <button
      v-if="totalCount > 0"
      @click="$emit('show-all')"
      class="total-count-button"
      :title="t('reactions.viewAll')"
    >
      <span class="total-icon">👁️</span>
      <span class="count">{{ formatCount(totalCount) }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

interface ReactionSummary {
  type: string
  count: number
}

interface Props {
  reactions: ReactionSummary[]
  totalCount: number
  userReaction?: string | null
  maxDisplay?: number
}

interface Emits {
  (e: 'reaction-click', reactionType: string): void
  (e: 'show-all'): void
}

const props = withDefaults(defineProps<Props>(), {
  maxDisplay: 3
})

const emit = defineEmits<Emits>()
const { t } = useI18n()

// Updated to match the actual API response format
const reactionEmojis: Record<string, string> = {
  like: '👍',
  dislike: '👎',
  love: '❤️',
  haha: '😂',
  wow: '😮',
  sad: '😢',
  angry: '😠'
}

const displayedReactions = computed(() => {
  // Sort by count descending and take top N
  return [...props.reactions]
    .sort((a, b) => b.count - a.count)
    .slice(0, props.maxDisplay)
    .filter(r => r.count > 0)
})

const getReactionEmoji = (type: string): string => {
  return reactionEmojis[type] || '👍'
}

const formatCount = (count: number): string => {
  if (count >= 1000000) return (count / 1000000).toFixed(1) + 'M'
  if (count >= 1000) return (count / 1000).toFixed(1) + 'K'
  return count.toString()
}

const getReactionTitle = (reaction: ReactionSummary): string => {
  const emoji = getReactionEmoji(reaction.type)
  return `${emoji} ${reaction.count} ${reaction.count === 1 ? 'reaction' : 'reactions'}`
}

const handleReactionClick = (type: string) => {
  emit('reaction-click', type)
}
</script>

<style scoped>
.reaction-display {
  display: flex;
  gap: var(--spacing-xs);
  align-items: center;
  flex-wrap: wrap;
}

.reaction-count-button,
.total-count-button {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-full);
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.reaction-count-button:hover,
.total-count-button:hover {
  background: var(--bg-tertiary);
  border-color: var(--primary);
  transform: scale(1.05);
}

.reaction-count-button.user-reacted {
  background: var(--primary-light);
  border-color: var(--primary);
  font-weight: 600;
}

.emoji,
.total-icon {
  font-size: 1rem;
  line-height: 1;
}

.count {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-primary);
  min-width: 16px;
  text-align: center;
}

.user-reacted .count {
  color: var(--primary-dark);
}

@keyframes pop {
  0% { transform: scale(1); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.reaction-count-button.animating {
  animation: pop 0.3s ease-out;
}

@media (max-width: 768px) {
  .reaction-count-button,
  .total-count-button {
    padding: 3px 6px;
    font-size: 0.8rem;
  }
  
  .emoji,
  .total-icon {
    font-size: 0.9rem;
  }
  
  .count {
    font-size: 0.75rem;
  }
}
</style>