<template>
  <div class="messages-view">
    <div class="messages-container">
      <!-- Conversations List Panel -->
      <div class="conversations-panel">
        <div class="conversations-header">
          <h2>{{ $t('navigation.messages') }}</h2>
          <button @click="showNewMessageModal = true" class="btn-new-message">
            <span class="icon">✉️</span>
          </button>
        </div>

        <div class="search-bar">
          <input
            v-model="conversationSearch"
            type="text"
            :placeholder="$t('common.actions.search') + ' conversations...'"
            class="search-input"
          />
        </div>

        <div v-if="loadingConversations" class="loading-container">
          <div class="spinner"></div>
        </div>

        <div v-else-if="filteredConversations.length === 0" class="empty-conversations">
          <p>No conversations yet</p>
        </div>

        <div v-else class="conversations-list">
          <div
            v-for="conversation in filteredConversations"
            :key="conversation.conversation_id"
            :class="['conversation-item', { active: activeConversationId === conversation.conversation_id }]"
            @click="selectConversation(conversation.conversation_id)"
          >
            <div class="conversation-avatar">
              <img
                :src="conversation.participant_avatar || '/assets/images/default-avatar.svg'"
                :alt="conversation.participant_name"
              />
              <span v-if="conversation.is_online" class="online-indicator"></span>
            </div>

            <div class="conversation-info">
              <div class="conversation-name">{{ conversation.participant_name }}</div>
              <div class="conversation-preview">{{ conversation.last_message || 'No messages' }}</div>
            </div>

            <div class="conversation-meta">
              <span class="conversation-time">{{ formatTime(conversation.updated_at) }}</span>
              <span v-if="conversation.unread_count > 0" class="unread-badge">
                {{ conversation.unread_count }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Conversation Panel -->
      <div class="conversation-panel">
        <div v-if="!activeConversation" class="no-conversation">
          <div class="empty-icon">💬</div>
          <h3>Select a conversation</h3>
          <p>Choose a conversation from the list to start messaging</p>
        </div>

        <template v-else>
          <!-- Conversation Header -->
          <div class="conversation-header">
            <div class="header-info">
              <img
                :src="activeConversation.participant_avatar || '/assets/images/default-avatar.svg'"
                :alt="activeConversation.participant_name"
                class="participant-avatar"
              />
              <div class="participant-details">
                <div class="participant-name">{{ activeConversation.participant_name }}</div>
                <div class="participant-status">
                  {{ activeConversation.is_online ? 'Online' : 'Offline' }}
                  <span v-if="isTyping" class="typing-indicator"> - typing...</span>
                </div>
              </div>
            </div>

            <div class="header-actions">
              <button @click="viewProfile" class="btn-icon" title="View Profile">
                <span>👤</span>
              </button>
              <button @click="deleteConversation" class="btn-icon" title="Delete Conversation">
                <span>🗑️</span>
              </button>
            </div>
          </div>

          <!-- Messages Thread -->
          <div ref="messagesContainer" class="messages-thread">
            <div v-if="loadingMessages" class="loading-container">
              <div class="spinner"></div>
            </div>

            <div v-else-if="messages.length === 0" class="empty-messages">
              <p>No messages yet. Start the conversation!</p>
            </div>

            <div v-else class="messages-list">
              <div
                v-for="message in messages"
                :key="message.message_id"
                :class="['message-item', { own: message.is_own }]"
              >
                <img
                  v-if="!message.is_own"
                  :src="activeConversation.participant_avatar || '/assets/images/default-avatar.svg'"
                  :alt="activeConversation.participant_name"
                  class="message-avatar"
                />

                <div class="message-bubble">
                  <div class="message-text">{{ message.message_text }}</div>
                  <div v-if="message.attachment_url" class="message-attachment">
                    <img :src="message.attachment_url" alt="Attachment" class="attachment-image" />
                  </div>
                  <div class="message-time">
                    {{ formatMessageTime(message.created_at) }}
                    <span v-if="message.edited_at" class="edited-label">(edited)</span>
                  </div>
                </div>
              </div>

              <div v-if="isTyping" class="typing-bubble">
                <div class="typing-dots">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Message Input -->
          <div class="message-input-container">
            <button @click="attachFile" class="btn-attach" title="Attach File">
              <span>📎</span>
            </button>

            <textarea
              v-model="messageText"
              @keydown.enter.exact.prevent="sendMessage"
              @input="handleTyping"
              placeholder="Type a message..."
              class="message-input"
              rows="1"
            ></textarea>

            <button @click="sendMessage" :disabled="!messageText.trim()" class="btn-send">
              <span>📤</span>
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- New Message Modal -->
    <div v-if="showNewMessageModal" class="modal-overlay" @click="showNewMessageModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>New Message</h3>
          <button @click="showNewMessageModal = false" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <input
            v-model="newMessageUsername"
            type="text"
            placeholder="Enter username..."
            class="username-input"
          />
        </div>
        <div class="modal-footer">
          <button @click="showNewMessageModal = false" class="btn-secondary">Cancel</button>
          <button @click="startNewConversation" class="btn-primary">Start</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/services/api/client'

const props = defineProps<{ conversationId?: string }>()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const conversations = ref<any[]>([])
const messages = ref<any[]>([])
const activeConversationId = ref<number | null>(null)
const conversationSearch = ref('')
const messageText = ref('')
const loadingConversations = ref(true)
const loadingMessages = ref(false)
const isTyping = ref(false)
const showNewMessageModal = ref(false)
const newMessageUsername = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
let typingTimeout: any = null

const filteredConversations = computed(() => {
  if (!conversationSearch.value) return conversations.value
  
  const search = conversationSearch.value.toLowerCase()
  return conversations.value.filter(c =>
    c.participant_name.toLowerCase().includes(search) ||
    c.last_message?.toLowerCase().includes(search)
  )
})

const activeConversation = computed(() => {
  if (!activeConversationId.value) return null
  return conversations.value.find(c => c.conversation_id === activeConversationId.value)
})

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'now'
  if (diffMins < 60) return `${diffMins}m`
  if (diffHours < 24) return `${diffHours}h`
  if (diffDays === 1) return 'yesterday'
  if (diffDays < 7) return `${diffDays}d`
  return date.toLocaleDateString()
}

const formatMessageTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const loadConversations = async () => {
  try {
    loadingConversations.value = true
    const response = await apiClient.get('/conversations')
    
    if (response.data.success && response.data.data.conversations) {
      conversations.value = response.data.data.conversations
    }
  } catch (err) {
    console.error('Error loading conversations:', err)
  } finally {
    loadingConversations.value = false
  }
}

const loadMessages = async () => {
  if (!activeConversationId.value) return

  try {
    loadingMessages.value = true
    const response = await apiClient.get(`/conversations/${activeConversationId.value}/messages`)
    
    if (response.data.success && response.data.data.messages) {
      messages.value = response.data.data.messages.map((msg: any) => ({
        ...msg,
        is_own: msg.sender_id === authStore.user?.user_id
      }))
      
      await nextTick()
      scrollToBottom()
      markAsRead()
    }
  } catch (err) {
    console.error('Error loading messages:', err)
  } finally {
    loadingMessages.value = false
  }
}

const selectConversation = (conversationId: number) => {
  activeConversationId.value = conversationId
  router.push(`/messages/${conversationId}`)
  loadMessages()
}

const sendMessage = async () => {
  if (!messageText.value.trim() || !activeConversationId.value) return

  const optimisticMessage = {
    message_id: Date.now(),
    message_text: messageText.value.trim(),
    is_own: true,
    created_at: new Date().toISOString(),
    sending: true
  }

  try {
    // Optimistic update
    messages.value.push(optimisticMessage)
    const textToSend = messageText.value.trim()
    messageText.value = ''
    
    await nextTick()
    scrollToBottom()

    const response = await apiClient.post(`/conversations/${activeConversationId.value}/messages`, {
      message_text: textToSend
    })

    if (response.data.success && response.data.data.message) {
      // Replace optimistic message with real one
      const index = messages.value.findIndex(m => m.message_id === optimisticMessage.message_id)
      if (index !== -1) {
        messages.value[index] = {
          ...response.data.data.message,
          is_own: true
        }
      }
      
      // Update conversation last message
      const conv = conversations.value.find(c => c.conversation_id === activeConversationId.value)
      if (conv) {
        conv.last_message = textToSend
        conv.updated_at = response.data.data.message.created_at
      }
    }
  } catch (err: any) {
    // Remove optimistic message on error
    messages.value = messages.value.filter(m => m.message_id !== optimisticMessage.message_id)
    messageText.value = optimisticMessage.message_text
    toast.error(err.message || 'Failed to send message')
  }
}

const handleTyping = () => {
  if (!activeConversationId.value) return

  // Send typing indicator
  apiClient.post(`/conversations/${activeConversationId.value}/typing`)

  // Clear existing timeout
  if (typingTimeout) {
    clearTimeout(typingTimeout)
  }

  // Set new timeout
  typingTimeout = setTimeout(() => {
    // Typing stopped
  }, 2000)
}

const markAsRead = async () => {
  if (!activeConversationId.value) return

  try {
    await apiClient.patch(`/conversations/${activeConversationId.value}/read`)
    
    // Update local state
    const conv = conversations.value.find(c => c.conversation_id === activeConversationId.value)
    if (conv) {
      conv.unread_count = 0
    }
  } catch (err) {
    console.error('Error marking as read:', err)
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const startNewConversation = async () => {
  if (!newMessageUsername.value.trim()) return

  try {
    const response = await apiClient.post('/conversations', {
      participant_username: newMessageUsername.value.trim()
    })

    if (response.data.success && response.data.data.conversation) {
      const newConv = response.data.data.conversation
      conversations.value.unshift(newConv)
      selectConversation(newConv.conversation_id)
      showNewMessageModal.value = false
      newMessageUsername.value = ''
    }
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to start conversation')
  }
}

const deleteConversation = async () => {
  if (!activeConversationId.value || !confirm('Delete this conversation?')) return

  try {
    await apiClient.delete(`/conversations/${activeConversationId.value}`)
    
    conversations.value = conversations.value.filter(c => c.conversation_id !== activeConversationId.value)
    activeConversationId.value = null
    messages.value = []
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to delete conversation')
  }
}

const viewProfile = () => {
  if (activeConversation.value) {
    router.push(`/profile/${activeConversation.value.participant_username}`)
  }
}

const attachFile = () => {
  console.log('File attachment coming soon')
}

const startPolling = () => {
  if (pollInterval) return

  pollInterval = setInterval(async () => {
    if (activeConversationId.value) {
      // Poll for new messages
      const lastMessageId = messages.value[messages.value.length - 1]?.message_id
      try {
        const response = await apiClient.get(
          `/conversations/${activeConversationId.value}/messages?after=${lastMessageId || 0}`
        )
        
        if (response.data.success && response.data.data.messages) {
          const newMessages = response.data.data.messages.map((msg: any) => ({
            ...msg,
            is_own: msg.sender_id === authStore.user?.user_id
          }))
          
          if (newMessages.length > 0) {
            messages.value.push(...newMessages)
            await nextTick()
            scrollToBottom()
            markAsRead()
          }
        }
      } catch (err) {
        console.error('Error polling messages:', err)
      }

      // Check typing status
      try {
        const response = await apiClient.get(`/conversations/${activeConversationId.value}/typing`)
        if (response.data.success && response.data.data.typing_users) {
          isTyping.value = response.data.data.typing_users.length > 0
        }
      } catch (err) {
        console.error('Error checking typing status:', err)
      }
    }
  }, 3000)
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

watch(() => props.conversationId, (newId) => {
  if (newId) {
    activeConversationId.value = parseInt(newId)
    loadMessages()
  }
})

onMounted(() => {
  loadConversations()
  
  if (props.conversationId) {
    activeConversationId.value = parseInt(props.conversationId)
    loadMessages()
  }
  
  messagePoller.start()
})

onUnmounted(() => {
  messagePoller.stop()
  if (typingTimeout) {
    clearTimeout(typingTimeout)
  }
})
</script>

<style scoped>
.messages-view {
  height: calc(100vh - 80px);
  padding: 0;
}

.messages-container {
  display: grid;
  grid-template-columns: 350px 1fr;
  height: 100%;
  background: var(--color-bg-primary);
}

.conversations-panel {
  background: var(--color-bg-elevated);
  border-right: 2px solid var(--color-border);
  display: flex;
  flex-direction: column;
  height: 100%;
}

.conversations-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-4);
  border-bottom: 2px solid var(--color-border);
}

.conversations-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.btn-new-message {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  transition: all 0.2s;
}

.btn-new-message:hover {
  transform: scale(1.1);
}

.search-bar {
  padding: var(--spacing-4);
  border-bottom: 1px solid var(--color-border);
}

.search-input {
  width: 100%;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: var(--spacing-3);
  padding: var(--spacing-4);
  border-bottom: 1px solid var(--color-border);
  cursor: pointer;
  transition: background 0.2s;
}

.conversation-item:hover {
  background: var(--color-bg-secondary);
}

.conversation-item.active {
  background: var(--color-primary);
  color: white;
}

.conversation-item.active .conversation-name,
.conversation-item.active .conversation-preview,
.conversation-item.active .conversation-time {
  color: white;
}

.conversation-avatar {
  position: relative;
  width: 48px;
  height: 48px;
}

.conversation-avatar img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.online-indicator {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 12px;
  height: 12px;
  background: #10b981;
  border: 2px solid var(--color-bg-elevated);
  border-radius: 50%;
}

.conversation-info {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
  min-width: 0;
}

.conversation-name {
  font-weight: 600;
  color: var(--color-text-primary);
}

.conversation-preview {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conversation-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: var(--spacing-2);
}

.conversation-time {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.unread-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  background: var(--color-primary);
  color: white;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 700;
}

.conversation-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.no-conversation {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: var(--spacing-8);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: var(--spacing-4);
}

.conversation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-4);
  background: var(--color-bg-elevated);
  border-bottom: 2px solid var(--color-border);
}

.header-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
}

.participant-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.participant-name {
  font-weight: 700;
  color: var(--color-text-primary);
}

.participant-status {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.typing-indicator {
  color: var(--color-primary);
  font-style: italic;
}

.header-actions {
  display: flex;
  gap: var(--spacing-2);
}

.btn-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-secondary);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: var(--color-primary);
  transform: scale(1.1);
}

.messages-thread {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-4);
  background: var(--color-bg-primary);
}

.messages-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}

.message-item {
  display: flex;
  gap: var(--spacing-3);
  align-items: flex-start;
}

.message-item.own {
  flex-direction: row-reverse;
}

.message-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.message-bubble {
  max-width: 70%;
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
}

.message-item.own .message-bubble {
  background: var(--color-primary);
  color: white;
}

.message-text {
  line-height: 1.5;
  word-wrap: break-word;
}

.message-attachment {
  margin-top: var(--spacing-2);
}

.attachment-image {
  max-width: 100%;
  border-radius: var(--radius-md);
}

.message-time {
  margin-top: var(--spacing-2);
  font-size: 0.75rem;
  opacity: 0.7;
}

.edited-label {
  font-style: italic;
}

.typing-bubble {
  display: flex;
  gap: var(--spacing-3);
  align-items: center;
}

.typing-dots {
  display: flex;
  gap: 4px;
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
}

.typing-dots span {
  width: 8px;
  height: 8px;
  background: var(--color-text-secondary);
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-dots span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-10px);
  }
}

.message-input-container {
  display: flex;
  gap: var(--spacing-3);
  padding: var(--spacing-4);
  background: var(--color-bg-elevated);
  border-top: 2px solid var(--color-border);
}

.btn-attach,
.btn-send {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-secondary);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  transition: all 0.2s;
}

.btn-send {
  background: var(--color-primary);
}

.btn-send:hover:not(:disabled) {
  transform: scale(1.1);
}

.btn-send:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.message-input {
  flex: 1;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  font-family: inherit;
  font-size: 1rem;
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
  resize: none;
  max-height: 120px;
}

.message-input:focus {
  outline: none;
  border-color: var(--color-primary);
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
  width: 90%;
  max-width: 500px;
  box-shadow: var(--shadow-lg);
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
  font-size: 2rem;
  cursor: pointer;
  color: var(--color-text-secondary);
}

.modal-body {
  padding: var(--spacing-6);
}

.username-input {
  width: 100%;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 1rem;
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-3);
  padding: var(--spacing-4);
  border-top: 2px solid var(--color-border);
}

.btn-primary,
.btn-secondary {
  padding: var(--spacing-3) var(--spacing-6);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-secondary {
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
}

.loading-container {
  text-align: center;
  padding: var(--spacing-8);
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto;
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-conversations,
.empty-messages {
  text-align: center;
  padding: var(--spacing-8);
  color: var(--color-text-secondary);
}

@media (max-width: 768px) {
  .messages-container {
    grid-template-columns: 1fr;
  }
  
  .conversations-panel {
    display: none;
  }
  
  .conversations-panel.show-mobile {
    display: flex;
  }
  
  .message-bubble {
    max-width: 85%;
  }
}
</style>
