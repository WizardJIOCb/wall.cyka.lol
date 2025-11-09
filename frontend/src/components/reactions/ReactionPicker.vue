<template>
  <div class="reaction-picker" @click.stop v-if="show">
    <div class="reaction-picker-content">
      <button
        v-for="reaction in reactions"
        :key="reaction.type"
        @click="selectReaction(reaction.type)"
        class="reaction-button"
        :class="{ selected: currentReaction === reaction.type }"
        :title="reaction.label"
      >
        <span class="reaction-emoji">{{ reaction.emoji }}</span>
        <span class="reaction-label">{{ reaction.label }}</span>
      </button>
    </div>
    <button @click="close" class="close-button" :title="t('reactions.close')">
      ✕
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

interface Reaction {
  type: string
  emoji: string
  label: string
}

interface Props {
  show: boolean
  currentReaction?: string | null
}

interface Emits {
  (e: 'select', reactionType: string): void
  (e: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const { t } = useI18n()

const reactions = ref<Reaction[]>([
  { type: 'like', emoji: '👍', label: 'Like' },
  { type: 'dislike', emoji: '👎', label: 'Dislike' },
  { type: 'love', emoji: '❤️', label: 'Love' },
  { type: 'haha', emoji: '😂', label: 'Laugh' },
  { type: 'wow', emoji: '😮', label: 'Wow' },
  { type: 'sad', emoji: '😢', label: 'Sad' },
  { type: 'angry', emoji: '😠', label: 'Angry' }
])

const selectReaction = (type: string) => {
  emit('select', type)
}

const close = () => {
  emit('close')
}
</script>

<style scoped>
.reaction-picker {
  position: absolute;
  z-index: 1000;
  background: var(--bg-primary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  padding: var(--spacing-sm);
  margin-top: var(--spacing-xs);
  animation: slideUp 0.2s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.reaction-picker-content {
  display: flex;
  gap: var(--spacing-xs);
  align-items: center;
}

.reaction-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  background: transparent;
  border: 2px solid transparent;
  padding: var(--spacing-xs);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 60px;
}

.reaction-button:hover {
  background: var(--bg-secondary);
  transform: scale(1.15);
  border-color: var(--border-color);
}

.reaction-button.selected {
  background: var(--primary-light);
  border-color: var(--primary);
}

.reaction-emoji {
  font-size: 1.8rem;
  line-height: 1;
}

.reaction-label {
  font-size: 0.7rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.close-button {
  position: absolute;
  top: 4px;
  right: 4px;
  background: var(--bg-secondary);
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 0.9rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-button:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

@media (max-width: 768px) {
  .reaction-picker-content {
    flex-wrap: wrap;
    max-width: 320px;
  }
  
  .reaction-button {
    min-width: 50px;
  }
  
  .reaction-emoji {
    font-size: 1.5rem;
  }
  
  .reaction-label {
    font-size: 0.65rem;
  }
}
</style>
