import { ref, onMounted, onUnmounted } from 'vue'
import apiClient from '@/services/api/client'
import { usePolling } from './usePolling'

export interface Notification {
  notification_id: number
  user_id: number
  actor_id: number
  actor_username: string
  actor_avatar: string
  notification_type: string
  target_type: string
  target_id: number
  target_preview: string
  content: any
  is_read: boolean
  created_at: string
}

export function useNotifications() {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const loadNotifications = async (limit = 20, offset = 0) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await apiClient.get('/notifications', {
        limit,
        offset
      })
      
      if (response.data.success) {
        notifications.value = response.data.data.notifications || []
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to load notifications'
      console.error('Error loading notifications:', err)
    } finally {
      loading.value = false
    }
  }

  const loadUnreadCount = async () => {
    try {
      const response = await apiClient.get('/notifications/unread-count')
      
      if (response.data.success) {
        unreadCount.value = response.data.data.unread_count || 0
      }
    } catch (err: any) {
      console.error('Error loading unread count:', err)
    }
  }

  const markAsRead = async (notificationId: number) => {
    try {
      await apiClient.patch(`/notifications/${notificationId}/read`)
      
      // Update local state
      const notification = notifications.value.find(n => n.notification_id === notificationId)
      if (notification && !notification.is_read) {
        notification.is_read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err: any) {
      console.error('Error marking notification as read:', err)
    }
  }

  const markAllAsRead = async () => {
    try {
      await apiClient.post('/notifications/mark-all-read')
      
      // Update local state
      notifications.value.forEach(n => {
        n.is_read = true
      })
      unreadCount.value = 0
    } catch (err: any) {
      error.value = err.message || 'Failed to mark all as read'
      throw err
    }
  }

  // Setup polling
  const poller = usePolling({
    interval: 10000, // 10 seconds
    backgroundInterval: 60000, // 60 seconds when tab not active
    onUpdate: async () => {
      await loadUnreadCount()
    },
    onError: (err) => {
      console.error('Notification polling error:', err)
    },
    enableVisibilityControl: true
  })

  onMounted(() => {
    loadUnreadCount()
    poller.start()
  })

  onUnmounted(() => {
    poller.stop()
  })

  return {
    notifications,
    unreadCount,
    loading,
    error,
    loadNotifications,
    loadUnreadCount,
    markAsRead,
    markAllAsRead,
    startPolling: poller.start,
    stopPolling: poller.stop
  }
}
