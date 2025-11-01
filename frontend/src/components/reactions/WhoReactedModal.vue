<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click="close">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ t('reactions.whoReacted') }}</h3>
          <button @click="close" class="close-btn" :title="t('common.close')">
            ✕
          </button>
        </div>
        
        <div class="reaction-tabs">
          <button
            @click="selectedTab = 'all'"
            class="tab-button"
            :class="{ active: selectedTab === 'all' }"
          >
            {{ t('reactions.all') }} ({{ totalCount }})
          </button>
          <button
            v-for="reaction in reactionSummary"
            :key="reaction.type"
            @click="selectedTab = reaction.type"
            class="tab-button"
            :class="{ active: selectedTab === reaction.type }"
          >
            {{ getReactionEmoji(reaction.type) }} {{ reaction.count }}
          </button>
        </div>
        
        <div class="users-list">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>{{ t('common.loading') }}</p>
          </div>
          
          <div v-else-if="users.length === 0" class="empty-state">
            <p>{{ t('reactions.noReactions') }}</p>
          </div>
          
          <div v-else class="user-items">
            <div
              v-for="user in users"
              :key="user.user_id"
              class="user-item"
            >
              <img
                :src="user.avatar_url || '/default-avatar.png'"
                :alt="user.display_name"
                class="user-avatar"
              />
              <div class="user-info">
                <router-link
                  :to="`/users/${user.user_id}`"
                  class="user-name"
                >
                  {{ user.display_name }}
                </router-link>
                <span class="user-username">@{{ user.username }}</span>
              </div>
              <span class="reaction-emoji">
                {{ getReactionEmoji(user.reaction_type) }}
              </span>
            </div>
          </div>
          
          <div v-if="hasMore && !loading" class="load-more">
            <button @click="loadMore" class="load-more-btn">
              {{ t('common.loadMore') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

interface ReactionSummary {
  type: string
  count: number
}

interface ReactionUser {
  user_id: number
  username: string
  display_name: string
  avatar_url: string | null
  reaction_type: string
  created_at: string
}

interface Props {
  show: boolean
  targetType: 'post' | 'comment'
  targetId: number
  reactionSummary: ReactionSummary[]
  totalCount: number
}

interface Emits {
  (e: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const { t } = useI18n()

const selectedTab = ref('all')
const users = ref<ReactionUser[]>([])
const loading = ref(false)
const hasMore = ref(false)
const offset = ref(0)
const limit = 20

const reactionEmojis: Record<string, string> = {
  like: '👍',
  dislike: '👎',
  heart: '❤️',
  laugh: '😂',
  wow: '😮',
  sad: '😢',
  angry: '😠'
}

const getReactionEmoji = (type: string): string => {
  return reactionEmojis[type] || '👍'
}

const close = () => {
  emit('close')
}

const fetchUsers = async (reset = true) => {
  if (reset) {
    offset.value = 0
    users.value = []
  }
  
  loading.value = true
  
  try {
    const endpoint = props.targetType === 'post'
      ? `/posts/${props.targetId}/reactions/users`
      : `/comments/${props.targetId}/reactions/users`
    
    const response = await api.get(endpoint, {
      params: {
        reaction_type: selectedTab.value === 'all' ? undefined : selectedTab.value,
        limit,
        offset: offset.value
      }
    })
    
    const newUsers = response.data.data.users || []
    
    if (reset) {
      users.value = newUsers
    } else {
      users.value.push(...newUsers)
    }
    
    hasMore.value = response.data.data.has_more || false
    offset.value += newUsers.length
    
  } catch (error) {
    console.error('Failed to fetch reaction users:', error)
  } finally {
    loading.value = false
  }
}

const loadMore = () => {
  fetchUsers(false)
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    selectedTab.value = 'all'
    fetchUsers()
  }
})

watch(selectedTab, () => {
  if (props.show) {
    fetchUsers()
  }
})
</script>

<style scoped>
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
  z-index: 2000;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  width: 90%;
  max-width: 500px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: var(--text-secondary);
  cursor: pointer;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.reaction-tabs {
  display: flex;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm) var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
  overflow-x: auto;
}

.tab-button {
  padding: var(--spacing-xs) var(--spacing-sm);
  background: transparent;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-full);
  font-size: 0.9rem;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
  color: var(--text-secondary);
}

.tab-button:hover {
  background: var(--bg-secondary);
  border-color: var(--primary);
}

.tab-button.active {
  background: var(--primary);
  border-color: var(--primary);
  color: white;
  font-weight: 600;
}

.users-list {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-md);
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
  color: var(--text-secondary);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border-color);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: var(--spacing-sm);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.user-items {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.user-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm);
  border-radius: var(--radius-md);
  transition: background 0.2s;
}

.user-item:hover {
  background: var(--bg-secondary);
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
  text-decoration: none;
  font-size: 0.95rem;
}

.user-name:hover {
  color: var(--primary);
}

.user-username {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.reaction-emoji {
  font-size: 1.2rem;
}

.load-more {
  display: flex;
  justify-content: center;
  padding-top: var(--spacing-md);
}

.load-more-btn {
  padding: var(--spacing-xs) var(--spacing-lg);
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.load-more-btn:hover {
  background: var(--bg-tertiary);
  border-color: var(--primary);
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    max-height: 90vh;
  }
  
  .modal-header h3 {
    font-size: 1.1rem;
  }
  
  .user-avatar {
    width: 36px;
    height: 36px;
  }
}
</style>
