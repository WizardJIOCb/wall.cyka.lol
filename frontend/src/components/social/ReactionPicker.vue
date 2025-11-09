<template>
  <div class="reaction-picker-container">
    <!-- Reaction Button -->
    <button 
      @click="togglePicker" 
      @mouseenter="showPickerOnHover"
      @mouseleave="hidePickerOnHover"
      :class="['reaction-button', { active: currentUserReaction }]"
      :style="{ color: displayColor }"
      :disabled="loading"
    >
      <span class="reaction-icon">{{ displayIcon }}</span>
      <span v-if="stats.total > 0" class="reaction-count">{{ stats.total }}</span>
    </button>

    <!-- Reaction Picker Popup -->
    <Transition name="picker-fade">
      <div 
        v-if="showPicker" 
        class="reaction-picker"
        @mouseenter="keepPickerOpen"
        @mouseleave="hidePickerOnHover"
      >
        <button
          v-for="(icon, type) in reactionIcons"
          :key="type"
          @click="handleReactionClick(type as ReactionType)"
          :class="['reaction-option', { selected: currentUserReaction === type }]"
          :title="type"
          :disabled="loading"
        >
          {{ icon }}
        </button>
      </div>
    </Transition>

    <!-- Reaction Details Modal -->
    <Transition name="modal-fade">
      <div v-if="showDetailsModal" class="modal-overlay" @click="showDetailsModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3>Reactions</h3>
            <button @click="showDetailsModal = false" class="btn-close">×</button>
          </div>
          
          <div class="modal-body">
            <div class="reaction-tabs">
              <button
                @click="selectedTab = 'all'"
                :class="['tab-button', { active: selectedTab === 'all' }]"
              >
                All {{ stats.total }}
              </button>
              <button
                v-for="(count, type) in stats.by_type"
                :key="type"
                @click="selectedTab = type"
                :class="['tab-button', { active: selectedTab === type }]"
                v-show="count > 0"
              >
                {{ reactionIcons[type as ReactionType] }} {{ count }}
              </button>
            </div>

            <div class="reaction-users-list">
              <div
                v-for="reaction in filteredReactions"
                :key="reaction.reaction_id"
                class="reaction-user-item"
              >
                <img 
                  :src="reaction.avatar_url || '/assets/images/default-avatar.svg'" 
                  :alt="reaction.username"
                  class="user-avatar"
                />
                <span class="username">{{ reaction.username }}</span>
                <span class="reaction-emoji">{{ reactionIcons[reaction.reaction_type] }}</span>
              </div>
              
              <div v-if="filteredReactions.length === 0" class="empty-state">
                No reactions yet
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useReactions, ReactionType, Reaction } from '@/composables/useReactions'

const props = defineProps<{
  reactableType: 'post' | 'comment'
  reactableId: number
}>()

const {
  reactions,
  stats,
  currentUserReaction,
  loading,
  reactionIcons,
  displayIcon,
  displayColor,
  loadReactions,
  toggleReaction
} = useReactions(ref(props.reactableType), ref(props.reactableId))

// Watch for changes in props and reload reactions if needed
watch(() => [props.reactableType, props.reactableId], () => {
  loadReactions()
})

const showPicker = ref(false)
const showDetailsModal = ref(false)
const selectedTab = ref<string>('all')
let hoverTimeout: any = null

const togglePicker = (event: Event) => {
  event.stopPropagation()
  showPicker.value = !showPicker.value
}

const showPickerOnHover = () => {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout)
  }
  hoverTimeout = setTimeout(() => {
    showPicker.value = true
  }, 150) // Reduced delay for quicker response
}

const hidePickerOnHover = () => {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout)
    hoverTimeout = null
  }
  // Increased delay to allow moving cursor to the picker
  hoverTimeout = setTimeout(() => {
    showPicker.value = false
  }, 300)
}

const keepPickerOpen = () => {
  if (hoverTimeout) {
    clearTimeout(hoverTimeout)
    hoverTimeout = null
  }
}

const handleReactionClick = async (type: ReactionType) => {
  showPicker.value = false
  try {
    await toggleReaction(type)
  } catch (error) {
    console.error('Failed to toggle reaction:', error)
  }
}

const showReactionDetails = () => {
  loadReactions()
  showDetailsModal.value = true
}

const filteredReactions = computed(() => {
  if (selectedTab.value === 'all') {
    return reactions.value
  }
  return reactions.value.filter((r: Reaction) => r.reaction_type === selectedTab.value)
})

// Load reactions on mount
loadReactions()
</script>

<style scoped>
.reaction-picker-container {
  position: relative;
  display: inline-block;
}

.reaction-button {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2) var(--spacing-3);
  background: transparent;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.2s;
  font-size: 1rem;
}

.reaction-button:hover {
  background: var(--color-bg-secondary);
  transform: scale(1.05);
}

.reaction-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.reaction-button.active {
  color: var(--color-primary);
}

.reaction-icon {
  font-size: 1.25rem;
}

.reaction-count {
  font-size: 0.875rem;
  font-weight: 600;
}

.reaction-picker {
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%) translateY(4px);
  display: flex;
  gap: var(--spacing-2);
  padding: var(--spacing-3);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  z-index: 100;
}

.reaction-option {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 2px solid transparent;
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: 1.5rem;
  transition: all 0.2s;
}

.reaction-option:hover {
  transform: scale(1.2);
  background: var(--color-bg-secondary);
}

.reaction-option.selected {
  border-color: var(--color-primary);
  background: rgba(99, 102, 241, 0.1);
}

.reaction-option:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.picker-fade-enter-active,
.picker-fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}

.picker-fade-enter-from,
.picker-fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-4px);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-4);
  border-bottom: 2px solid var(--color-border);
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.btn-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.5rem;
  color: var(--color-text-secondary);
  transition: all 0.2s;
}

.btn-close:hover {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-4);
}

.reaction-tabs {
  display: flex;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-4);
  overflow-x: auto;
}

.tab-button {
  padding: var(--spacing-2) var(--spacing-4);
  background: var(--color-bg-secondary);
  border: 2px solid transparent;
  border-radius: var(--radius-full);
  cursor: pointer;
  font-weight: 600;
  color: var(--color-text-secondary);
  transition: all 0.2s;
  white-space: nowrap;
}

.tab-button:hover {
  background: var(--color-bg-primary);
}

.tab-button.active {
  background: var(--color-primary);
  color: white;
}

.reaction-users-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}

.reaction-user-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-2);
  border-radius: var(--radius-md);
  transition: background 0.2s;
}

.reaction-user-item:hover {
  background: var(--color-bg-secondary);
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
}

.username {
  flex: 1;
  font-weight: 600;
  color: var(--color-text-primary);
}

.reaction-emoji {
  font-size: 1.25rem;
}

.empty-state {
  text-align: center;
  padding: var(--spacing-8);
  color: var(--color-text-secondary);
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .modal-content,
.modal-fade-leave-active .modal-content {
  transition: transform 0.3s;
}

.modal-fade-enter-from .modal-content,
.modal-fade-leave-to .modal-content {
  transform: scale(0.9);
}
</style>