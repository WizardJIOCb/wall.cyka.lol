<template>
  <div class="notifications-view">
    <div class="notifications-header">
      <h1>{{ $t('navigation.notifications') }}</h1>
      <button 
        v-if="unreadCount > 0" 
        @click="markAllAsRead" 
        class="btn-secondary"
        :disabled="markingAllRead"
      >
        {{ markingAllRead ? 'Marking...' : 'Mark all as read' }}
      </button>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button
        v-for="filter in filters"
        :key="filter.id"
        :class="['filter-tab', { active: activeFilter === filter.id }]"
        @click="activeFilter = filter.id"
      >
        {{ filter.label }}
        <span v-if="filter.id === 'unread' && unreadCount > 0" class="badge">{{ unreadCount }}</span>
      </button>
    </div>

    <!-- Notifications List -->
    <div class="notifications-container">
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p>{{ $t('common.labels.loading') }}</p>
      </div>

      <div v-else-if="filteredNotifications.length === 0" class="empty-state">
        <span class="empty-icon">🔔</span>
        <h2>No notifications</h2>
        <p>{{ activeFilter === 'unread' ? 'You\'re all caught up!' : 'No notifications to show' }}</p>
      </div>

      <div v-else class="notifications-list">
        <div
          v-for="notification in filteredNotifications"
          :key="notification.notification_id"
          :class="['notification-item', { unread: !notification.is_read }]"
          @click="handleNotificationClick(notification)"
        >
          <div class="notification-avatar">
            <img :src="notification.actor_avatar || '/assets/images/default-avatar.svg'" :alt="notification.actor_username" />
            <span class="notification-type-icon">{{ getNotificationIcon(notification.notification_type) }}</span>
          </div>

          <div class="notification-content">
            <div class="notification-text">
              <strong>{{ notification.actor_username }}</strong>
              {{ getNotificationText(notification) }}
            </div>
            <div v-if="notification.target_preview" class="notification-preview">
              {{ notification.target_preview }}
            </div>
            <div class="notification-time">{{ formatTime(notification.created_at) }}</div>
          </div>

          <div class="notification-actions">
            <button
              v-if="!notification.is_read"
              @click.stop="markAsRead(notification.notification_id)"
              class="mark-read-btn"
              title="Mark as read"
            >
              ✓
            </button>
          </div>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMore && !loading" class="load-more">
        <button @click="loadMore" class="btn-secondary">{{ $t('common.buttons.loadMore') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '@/services/api/client'

const router = useRouter()

const loading = ref(true)
const markingAllRead = ref(false)
const notifications = ref<any[]>([])
const unreadCount = ref(0)
const activeFilter = ref('all')
const page = ref(1)
const hasMore = ref(true)

const filters = [
  { id: 'all', label: 'All' },
  { id: 'unread', label: 'Unread' },
  { id: 'mentions', label: 'Mentions' },
  { id: 'reactions', label: 'Reactions' },
  { id: 'comments', label: 'Comments' },
  { id: 'follows', label: 'Follows' }
]

const filteredNotifications = computed(() => {
  if (activeFilter.value === 'all') {
    return notifications.value
  }
  if (activeFilter.value === 'unread') {
    return notifications.value.filter(n => !n.is_read)
  }
  return notifications.value.filter(n => n.notification_type === activeFilter.value.slice(0, -1))
})

const getNotificationIcon = (type: string): string => {
  const icons: Record<string, string> = {
    follow: '👥',
    reaction: '❤️',
    comment: '💬',
    reply: '💬',
    mention: '@',
    bricks: '🧱',
    ai_complete: '🤖'
  }
  return icons[type] || '🔔'
}

const getNotificationText = (notification: any): string => {
  const texts: Record<string, string> = {
    follow: 'started following you',
    reaction: `reacted to your ${notification.target_type}`,
    comment: 'commented on your post',
    reply: 'replied to your comment',
    mention: `mentioned you in a ${notification.target_type}`,
    bricks: `sent you ${notification.content?.amount || 0} bricks`,
    ai_complete: 'Your AI application is ready'
  }
  return texts[notification.notification_type] || 'sent you a notification'
}

const formatTime = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

const loadNotifications = async () => {
  try {
    loading.value = true
    const limit = 20
    const offset = (page.value - 1) * limit
    
    const response = await apiClient.get(`/notifications?limit=${limit}&offset=${offset}`)
    // Add proper error handling for response structure
    if (response && response.data) {
      if (response.data.success && response.data.data && response.data.data.notifications) {
        if (page.value === 1) {
          notifications.value = response.data.data.notifications
        } else {
          notifications.value = [...notifications.value, ...response.data.data.notifications]
        }
        hasMore.value = response.data.data.notifications.length === limit
      } else {
        // Handle case where success is false or data structure is different
        console.warn('Unexpected response structure:', response.data)
        notifications.value = []
        hasMore.value = false
      }
    } else {
      console.error('Invalid response:', response)
      notifications.value = []
      hasMore.value = false
    }
  } catch (err) {
    console.error('Error loading notifications:', err)
    notifications.value = []
    hasMore.value = false
  } finally {
    loading.value = false
  }
}

const loadUnreadCount = async () => {
  try {
    const response = await apiClient.get('/notifications/unread-count')
    // Add proper error handling for response structure
    if (response && response.data && response.data.success) {
      unreadCount.value = response.data.data?.unread_count || 0
    } else {
      console.warn('Unexpected response structure for unread count:', response?.data)
      unreadCount.value = 0
    }
  } catch (err) {
    console.error('Error loading unread count:', err)
    unreadCount.value = 0
  }
}

const markAsRead = async (notificationId: number) => {
  try {
    await apiClient.patch(`/notifications/${notificationId}/read`)
    
    // Update local state
    const notification = notifications.value.find(n => n.notification_id === notificationId)
    if (notification) {
      notification.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (err) {
    console.error('Error marking notification as read:', err)
  }
}

const markAllAsRead = async () => {
  try {
    markingAllRead.value = true
    await apiClient.post('/notifications/mark-all-read')
    
    // Update local state
    notifications.value.forEach(n => {
      n.is_read = true
    })
    unreadCount.value = 0
  } catch (err) {
    console.error('Error marking all as read:', err)
  } finally {
    markingAllRead.value = false
  }
}

const handleNotificationClick = async (notification: any) => {
  // Mark as read
  if (!notification.is_read) {
    await markAsRead(notification.notification_id)
  }

  // Navigate to target
  if (notification.target_type === 'post' && notification.target_id) {
    router.push(`/post/${notification.target_id}`)
  } else if (notification.target_type === 'wall' && notification.target_id) {
    router.push(`/wall/${notification.target_id}`)
  } else if (notification.target_type === 'user' && notification.actor_id) {
    router.push(`/profile/${notification.actor_username}`)
  } else if (notification.target_type === 'ai_app' && notification.target_id) {
    router.push(`/ai/${notification.target_id}`)
  }
}

const loadMore = () => {
  page.value++
  loadNotifications()
}

onMounted(() => {
  loadNotifications()
  loadUnreadCount()
})
</script>

<style scoped>
.notifications-view {
  max-width: 800px;
  margin: 0 auto;
  padding: var(--spacing-6);
}

.notifications-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
}

.notifications-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.filter-tabs {
  display: flex;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-6);
  overflow-x: auto;
  padding-bottom: var(--spacing-2);
}

.filter-tab {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-bg-elevated);
  border: 2px solid transparent;
  border-radius: var(--radius-full);
  cursor: pointer;
  font-weight: 600;
  color: var(--color-text-secondary);
  transition: all 0.2s;
  white-space: nowrap;
}

.filter-tab:hover {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.filter-tab.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background: white;
  color: var(--color-primary);
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
}

.filter-tab.active .badge {
  background: rgba(255, 255, 255, 0.9);
}

.notifications-container {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.loading-container {
  text-align: center;
  padding: var(--spacing-8);
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto var(--spacing-4);
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: var(--spacing-12);
}

.empty-icon {
  font-size: 4rem;
  display: block;
  margin-bottom: var(--spacing-4);
  opacity: 0.5;
}

.empty-state h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.empty-state p {
  color: var(--color-text-secondary);
}

.notifications-list {
  display: flex;
  flex-direction: column;
}

.notification-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: var(--spacing-4);
  padding: var(--spacing-4);
  border-bottom: 1px solid var(--color-border);
  cursor: pointer;
  transition: all 0.2s;
}

.notification-item:hover {
  background: var(--color-bg-secondary);
}

.notification-item.unread {
  background: rgba(99, 102, 241, 0.05);
  border-left: 4px solid var(--color-primary);
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-avatar {
  position: relative;
  width: 48px;
  height: 48px;
}

.notification-avatar img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.notification-type-icon {
  position: absolute;
  bottom: -4px;
  right: -4px;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-elevated);
  border: 2px solid var(--color-bg-elevated);
  border-radius: 50%;
  font-size: 0.75rem;
}

.notification-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

.notification-text {
  color: var(--color-text-primary);
  line-height: 1.5;
}

.notification-text strong {
  font-weight: 600;
}

.notification-preview {
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-time {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.notification-actions {
  display: flex;
  align-items: center;
}

.mark-read-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 1rem;
}

.mark-read-btn:hover {
  background: var(--color-primary-dark);
  transform: scale(1.1);
}

.load-more {
  text-align: center;
  padding: var(--spacing-6);
  border-top: 1px solid var(--color-border);
}

.btn-secondary {
  padding: var(--spacing-3) var(--spacing-6);
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background: var(--color-primary);
  color: white;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .notifications-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-4);
  }
  
  .filter-tabs {
    width: 100%;
  }
  
  .notification-item {
    grid-template-columns: auto 1fr;
  }
  
  .notification-actions {
    grid-column: 1 / -1;
    justify-content: center;
    margin-top: var(--spacing-2);
  }
}
</style>
