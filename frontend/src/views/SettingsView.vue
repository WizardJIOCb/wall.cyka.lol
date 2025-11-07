<<<<<<< Local
<template>
  <div class="settings-view">
    <div class="settings-header">
      <h1>{{ $t('settings.title') }}</h1>
    </div>

    <div class="settings-container">
      <!-- Tab Navigation -->
      <div class="tabs-nav">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="['tab-button', { active: activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ $t(`settings.tabs.${tab.id}`) }}</span>
        </button>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Account Tab -->
        <div v-if="activeTab === 'account'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.account') }}</h2>
          
          <div class="form-section">
            <label class="form-label">{{ $t('settings.account.username') }}</label>
            <input
              v-model="formData.username"
              type="text"
              class="form-input"
              :placeholder="$t('settings.account.username')"
            />
          </div>

          <div class="form-section">
            <label class="form-label">{{ $t('settings.account.email') }}</label>
            <input
              v-model="formData.email"
              type="email"
              class="form-input"
              :placeholder="$t('settings.account.email')"
            />
          </div>

          <div class="form-section">
            <h3>{{ $t('settings.account.changePassword') }}</h3>
            <input
              v-model="passwordData.current"
              type="password"
              class="form-input"
              :placeholder="$t('settings.account.currentPassword')"
            />
            <input
              v-model="passwordData.new"
              type="password"
              class="form-input"
              :placeholder="$t('settings.account.newPassword')"
            />
            <input
              v-model="passwordData.confirm"
              type="password"
              class="form-input"
              :placeholder="$t('settings.account.confirmNewPassword')"
            />
            <button @click="changePassword" class="btn-primary">{{ $t('common.buttons.update') }}</button>
          </div>

          <div class="form-section danger-zone">
            <h3>{{ $t('settings.account.deleteAccount') }}</h3>
            <p class="warning-text">{{ $t('settings.account.deleteAccountWarning') }}</p>
            <button @click="confirmDeleteAccount" class="btn-danger">{{ $t('common.buttons.delete') }}</button>
          </div>
        </div>

        <!-- Profile Tab -->
        <div v-if="activeTab === 'profile'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.profile') }}</h2>
          
          <div class="form-section">
            <label class="form-label">Bio</label>
            <textarea
              v-model="formData.bio"
              class="form-textarea"
              placeholder="Tell us about yourself..."
              rows="4"
              maxlength="500"
            ></textarea>
            <span class="char-count">{{ formData.bio?.length || 0 }}/500</span>
          </div>

          <div class="form-section">
            <label class="form-label">Avatar</label>
            <div class="avatar-upload">
              <img :src="localAvatarPreview" alt="Avatar" class="avatar-preview" />
              <input 
                ref="avatarFileInput" 
                type="file" 
                accept="image/jpeg,image/png,image/webp" 
                @change="handleAvatarSelect" 
                style="display: none;"
              />
              <button @click="triggerAvatarUpload" class="btn-secondary" :disabled="uploadingAvatar">
                {{ uploadingAvatar ? 'Uploading...' : $t('common.actions.upload') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Privacy Tab -->
        <div v-if="activeTab === 'privacy'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.privacy') }}</h2>
          
          <div class="form-section">
            <label class="form-label">Default Wall Privacy</label>
            <select v-model="formData.defaultWallPrivacy" class="form-select">
              <option value="public">{{ $t('walls.public') }}</option>
              <option value="private">{{ $t('walls.private') }}</option>
              <option value="followers">{{ $t('walls.followers') }}</option>
            </select>
          </div>
        </div>

        <!-- Notifications Tab -->
        <div v-if="activeTab === 'notifications'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.notifications') }}</h2>
          
          <div class="form-section">
            <label class="checkbox-label">
              <input v-model="formData.emailNotifications" type="checkbox" />
              <span>Email Notifications</span>
            </label>
          </div>

          <div class="form-section">
            <label class="checkbox-label">
              <input v-model="formData.pushNotifications" type="checkbox" />
              <span>Push Notifications</span>
            </label>
          </div>
        </div>

        <!-- Appearance Tab -->
        <div v-if="activeTab === 'appearance'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.appearance') }}</h2>
          
          <div class="form-section">
            <label class="form-label">{{ $t('settings.appearance.theme') }}</label>
            <div class="theme-selector">
              <button
                v-for="theme in themes"
                :key="theme.id"
                :class="['theme-option', { active: selectedTheme === theme.id }]"
                @click="selectTheme(theme.id)"
              >
                <span class="theme-preview" :style="{ background: theme.color }"></span>
                <span>{{ theme.name }}</span>
              </button>
            </div>
          </div>

          <div class="form-section">
            <label class="form-label">{{ $t('settings.appearance.language') }}</label>
            <div class="language-selector">
              <button
                v-for="lang in languages"
                :key="lang.code"
                :class="['language-option', { active: currentLocale === lang.code }]"
                @click="changeLanguage(lang.code)"
              >
                <span class="language-flag">{{ lang.flag }}</span>
                <span class="language-name">{{ lang.name }}</span>
              </button>
            </div>
          </div>

          <div class="form-section">
            <label class="form-label">{{ $t('settings.appearance.fontSize') }}</label>
            <select v-model="formData.fontSize" class="form-select">
              <option value="small">Small</option>
              <option value="medium">Medium</option>
              <option value="large">Large</option>
            </select>
          </div>
        </div>

        <!-- Bricks Tab -->
        <div v-if="activeTab === 'bricks'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.bricks') }}</h2>
          
          <div class="bricks-balance">
            <div class="balance-display">
              <span class="balance-label">Current Balance</span>
              <span class="balance-value">{{ bricksBalance }} 🧱</span>
            </div>
            <button @click="claimDailyBricks" :disabled="!canClaimDaily" class="btn-primary">
              {{ canClaimDaily ? 'Claim Daily Bricks' : `Next claim in ${timeUntilClaim}` }}
            </button>
          </div>

          <div class="transactions-section">
            <h3>Recent Transactions</h3>
            <div v-if="transactions.length === 0" class="empty-state">
              No transactions yet
            </div>
            <div v-else class="transactions-list">
              <div v-for="tx in transactions" :key="tx.id" class="transaction-item">
                <span>{{ tx.description }}</span>
                <span :class="['transaction-amount', tx.amount > 0 ? 'positive' : 'negative']">
                  {{ tx.amount > 0 ? '+' : '' }}{{ tx.amount }} 🧱
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Button (sticky bottom) -->
    <div class="settings-footer">
      <button @click="saveSettings" class="btn-primary btn-large" :disabled="saving">
        {{ saving ? 'Saving...' : $t('common.buttons.save') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { setLocale, getLocale } from '@/i18n'
import apiClient from '@/services/api/client'
import { useThemeStore } from '@/stores/theme'

const authStore = useAuthStore()
const themeStore = useThemeStore()

const activeTab = ref('appearance') // Default to appearance tab to show language selector
const saving = ref(false)
const currentLocale = ref(getLocale())

const tabs = [
  { id: 'account', icon: '👤', label: 'Account' },
  { id: 'profile', icon: '📝', label: 'Profile' },
  { id: 'privacy', icon: '🔒', label: 'Privacy' },
  { id: 'notifications', icon: '🔔', label: 'Notifications' },
  { id: 'appearance', icon: '🎨', label: 'Appearance' },
  { id: 'bricks', icon: '🧱', label: 'Bricks' }
]

const languages = [
  { code: 'en', name: 'English', flag: '🇬🇧' },
  { code: 'ru', name: 'Русский', flag: '🇷🇺' }
]

const themes = [
  { id: 'light', name: 'Light', color: '#ffffff' },
  { id: 'dark', name: 'Dark', color: '#1a1a1a' },
  { id: 'green', name: 'Green', color: '#10b981' },
  { id: 'blue', name: 'Blue', color: '#3b82f6' },
  { id: 'cream', name: 'Cream', color: '#fef3c7' },
  { id: 'high-contrast', name: 'High Contrast', color: '#000000' }
]

const formData = ref({
  username: authStore.user?.username || '',
  email: authStore.user?.email || '',
  bio: authStore.user?.bio || '',
  defaultWallPrivacy: 'public',
  emailNotifications: true,
  pushNotifications: true,
  fontSize: 'medium'
})

const passwordData = ref({
  current: '',
  new: '',
  confirm: ''
})

const selectedTheme = ref(themeStore.currentTheme || 'light')
const avatarPreview = computed(() => authStore.user?.avatar_url || '/assets/images/default-avatar.svg')
const localAvatarPreview = ref(avatarPreview.value)
const uploadingAvatar = ref(false)
const avatarFileInput = ref<HTMLInputElement | null>(null)

const bricksBalance = ref(authStore.user?.bricks_balance || 0)
const canClaimDaily = ref(true)
const timeUntilClaim = ref('')
const transactions = ref<any[]>([])

const changeLanguage = async (locale: string) => {
  try {
    // Update i18n locale immediately
    setLocale(locale)
    currentLocale.value = locale
    
    // Save to backend
    await apiClient.patch('/users/me/settings', {
      language: locale
    })
    
    // Show success message
    console.log('Language changed to:', locale)
  } catch (error) {
    console.error('Failed to save language preference:', error)
  }
}

const selectTheme = (themeId: string) => {
  selectedTheme.value = themeId
  themeStore.setTheme(themeId)
}

const changePassword = async () => {
  if (passwordData.value.new !== passwordData.value.confirm) {
    alert('Passwords do not match')
    return
  }
  
  try {
    await apiClient.post('/users/me/change-password', {
      current_password: passwordData.value.current,
      new_password: passwordData.value.new
    })
    
    // Clear form
    passwordData.value = { current: '', new: '', confirm: '' }
    alert('Password changed successfully')
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to change password')
  }
}

const triggerAvatarUpload = () => {
  avatarFileInput.value?.click()
}

const handleAvatarSelect = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  // Validate file type
  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    alert('Please select a valid image file (JPEG, PNG, or WebP)')
    return
  }
  
  // Validate file size (5MB)
  const maxSize = 5 * 1024 * 1024 // 5MB in bytes
  if (file.size > maxSize) {
    alert('Image must be smaller than 5MB')
    return
  }
  
  try {
    uploadingAvatar.value = true
    
    // Update preview immediately with local URL
    localAvatarPreview.value = URL.createObjectURL(file)
    
    // Create form data
    const formData = new FormData()
    formData.append('avatar', file)
    
    // Upload to server
    const response = await fetch(import.meta.env.VITE_API_URL + '/upload/avatar', {
      method: 'POST',
      credentials: 'include',
      body: formData
    })
    
    const result = await response.json()
    
    if (!result.success) {
      throw new Error(result.message || 'Upload failed')
    }
    
    // Update auth store with new avatar URL
    authStore.updateUser({ avatar_url: result.data.avatar_url })
    localAvatarPreview.value = result.data.avatar_url
    
    alert('Avatar uploaded successfully!')
  } catch (error: any) {
    // Revert preview on error
    localAvatarPreview.value = avatarPreview.value
    alert(error.message || 'Failed to upload avatar. Please try again.')
  } finally {
    uploadingAvatar.value = false
    // Reset file input
    if (target) target.value = ''
  }
}

const uploadAvatar = () => {
  // TODO: Implement avatar upload
  console.log('Avatar upload coming soon')
}

const confirmDeleteAccount = () => {
  if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
    deleteAccount()
  }
}

const deleteAccount = async () => {
  try {
    await apiClient.delete('/users/me/account')
    authStore.logout()
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to delete account')
  }
}

const claimDailyBricks = async () => {
  try {
    const response = await apiClient.post('/bricks/claim')
    if (response.data.success) {
      bricksBalance.value = response.data.data.new_balance
      canClaimDaily.value = false
    }
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to claim daily bricks')
  }
}

const loadBricksData = async () => {
  try {
    const [balanceRes, transactionsRes] = await Promise.all([
      apiClient.get('/bricks/balance'),
      apiClient.get('/bricks/transactions?limit=10')
    ])
    
    if (balanceRes.success) {
      bricksBalance.value = balanceRes.data.balance
      // Update auth store with fresh balance
      authStore.updateUser({ bricks_balance: balanceRes.data.balance })
    }
    
    if (transactionsRes.success) {
      transactions.value = transactionsRes.data.transactions || []
    }
  } catch (error) {
    console.error('Failed to load bricks data:', error)
  }
}

const saveSettings = async () => {
  try {
    saving.value = true
    
    await apiClient.patch('/users/me/settings', {
      theme: selectedTheme.value,
      language: currentLocale.value,
      default_wall_privacy: formData.value.defaultWallPrivacy,
      email_notifications: formData.value.emailNotifications,
      push_notifications: formData.value.pushNotifications,
      font_size: formData.value.fontSize
    })
    
    // Update user data if username or email changed
    if (formData.value.username !== authStore.user?.username || 
        formData.value.email !== authStore.user?.email) {
      await apiClient.patch('/users/me', {
        username: formData.value.username,
        email: formData.value.email,
        bio: formData.value.bio
      })
    }
    
    alert('Settings saved successfully')
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to save settings')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  // Refresh user data from backend to get latest bricks balance
  try {
    const response = await apiClient.get('/auth/me')
    if (response.success && response.data.user) {
      authStore.updateUser(response.data.user)
    }
  } catch (error) {
    console.error('Failed to refresh user data:', error)
  }
  
  loadBricksData()
})
</script>

<style scoped>
.settings-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-6);
  padding-bottom: 160px; /* Safe zone for sticky footer */
}

.settings-header {
  margin-bottom: var(--spacing-6);
}

.settings-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.settings-container {
  display: grid;
  grid-template-columns: 250px 1fr;
  gap: var(--spacing-6);
  margin-bottom: var(--spacing-8);
}

.tabs-nav {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
  position: sticky;
  top: var(--spacing-4);
  height: fit-content;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-bg-elevated);
  border: 2px solid transparent;
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
  font-size: 1rem;
  color: var(--color-text-secondary);
}

.tab-button:hover {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.tab-button.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.tab-icon {
  font-size: 1.25rem;
}

.tab-content {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-md);
}

.tab-panel {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.panel-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: var(--spacing-6);
  color: var(--color-text-primary);
}

.form-section {
  margin-bottom: var(--spacing-6);
}

.form-section h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: var(--spacing-3);
  color: var(--color-text-primary);
}

.form-label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 1rem;
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
  transition: border-color 0.2s;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: var(--color-primary);
}

.form-input + .form-input {
  margin-top: var(--spacing-3);
}

.form-textarea {
  resize: vertical;
  font-family: inherit;
}

.char-count {
  display: block;
  margin-top: var(--spacing-2);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  text-align: right;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.theme-selector,
.language-selector {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: var(--spacing-3);
}

.theme-option,
.language-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border: 2px solid transparent;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s;
}

.theme-option:hover,
.language-option:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

.theme-option.active,
.language-option.active {
  border-color: var(--color-primary);
  background: var(--color-primary-light);
}

.theme-preview {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 3px solid var(--color-border);
}

.language-flag {
  font-size: 3rem;
}

.language-name {
  font-weight: 600;
  color: var(--color-text-primary);
}

.avatar-upload {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
}

.avatar-preview {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--color-border);
}

.danger-zone {
  padding: var(--spacing-4);
  background: rgba(239, 68, 68, 0.1);
  border: 2px solid rgba(239, 68, 68, 0.3);
  border-radius: var(--radius-md);
}

.warning-text {
  color: #ef4444;
  margin-bottom: var(--spacing-3);
}

.bricks-balance {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: var(--spacing-6);
  border-radius: var(--radius-lg);
  color: white;
  margin-bottom: var(--spacing-6);
}

.balance-display {
  display: flex;
  flex-direction: column;
  margin-bottom: var(--spacing-4);
}

.balance-label {
  font-size: 0.875rem;
  opacity: 0.9;
}

.balance-value {
  font-size: 2.5rem;
  font-weight: 700;
}

.transactions-section h3 {
  margin-bottom: var(--spacing-4);
}

.transactions-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}

.transaction-item {
  display: flex;
  justify-content: space-between;
  padding: var(--spacing-3);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
}

.transaction-amount {
  font-weight: 600;
}

.transaction-amount.positive {
  color: #10b981;
}

.transaction-amount.negative {
  color: #ef4444;
}

.settings-footer {
  position: sticky;
  bottom: 0;
  background: var(--color-bg-primary);
  padding: var(--spacing-4) 0;
  border-top: 2px solid var(--color-border);
  z-index: 100;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
  will-change: transform;
}

.btn-primary,
.btn-secondary,
.btn-danger {
  padding: var(--spacing-3) var(--spacing-6);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 1rem;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
}

.btn-secondary:hover {
  background: var(--color-primary);
  color: white;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
}

.btn-large {
  width: 100%;
  padding: var(--spacing-4) var(--spacing-6);
  font-size: 1.125rem;
}

.empty-state {
  text-align: center;
  padding: var(--spacing-6);
  color: var(--color-text-secondary);
}

@media (max-width: 768px) {
  .settings-view {
    padding-bottom: 180px; /* Larger safe zone for mobile */
  }
  
  .settings-container {
    grid-template-columns: 1fr;
  }
  
  .tabs-nav {
    flex-direction: row;
    overflow-x: auto;
    position: static;
  }
  
  .tab-button {
    flex-shrink: 0;
  }
  
  .tab-label {
    display: none;
  }
  
  .theme-selector,
  .language-selector {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
=======
<template>
  <div class="settings-view">
    <div class="settings-header">
      <h1>{{ $t('settings.title') }}</h1>
    </div>

    <div class="settings-container">
      <!-- Tab Navigation -->
      <div class="tabs-nav">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="['tab-button', { active: activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ $t(`settings.tabs.${tab.id}`) }}</span>
        </button>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Account Tab -->
        <div v-if="activeTab === 'account'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.account') }}</h2>
          
          <div class="form-section">
            <label class="form-label">{{ $t('settings.account.username') }}</label>
            <input
              v-model="formData.username"
              type="text"
              class="form-input"
              :placeholder="$t('settings.account.username')"
            />
          </div>

          <div class="form-section">
            <label class="form-label">{{ $t('settings.account.email') }}</label>
            <input
              v-model="formData.email"
              type="email"
              class="form-input"
              :placeholder="$t('settings.account.email')"
            />
          </div>

          <div class="form-section">
            <h3>{{ $t('settings.account.changePassword') }}</h3>
            <input
              v-model="passwordData.current"
              type="password"
              class="form-input"
              :placeholder="$t('settings.account.currentPassword')"
            />
            <input
              v-model="passwordData.new"
              type="password"
              class="form-input"
              :placeholder="$t('settings.account.newPassword')"
            />
            <input
              v-model="passwordData.confirm"
              type="password"
              class="form-input"
              :placeholder="$t('settings.account.confirmNewPassword')"
            />
            <button @click="changePassword" class="btn-primary">{{ $t('common.buttons.update') }}</button>
          </div>

          <div class="form-section danger-zone">
            <h3>{{ $t('settings.account.deleteAccount') }}</h3>
            <p class="warning-text">{{ $t('settings.account.deleteAccountWarning') }}</p>
            <button @click="confirmDeleteAccount" class="btn-danger">{{ $t('common.buttons.delete') }}</button>
          </div>
        </div>

        <!-- Profile Tab -->
        <div v-if="activeTab === 'profile'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.profile') }}</h2>
          
          <div class="form-section">
            <label class="form-label">Bio</label>
            <textarea
              v-model="formData.bio"
              class="form-textarea"
              placeholder="Tell us about yourself..."
              rows="4"
              maxlength="500"
            ></textarea>
            <span class="char-count">{{ formData.bio?.length || 0 }}/500</span>
          </div>

          <div class="form-section">
            <label class="form-label">Avatar</label>
            <div class="avatar-upload">
              <img :src="localAvatarPreview" alt="Avatar" class="avatar-preview" />
              <input 
                ref="avatarFileInput" 
                type="file" 
                accept="image/jpeg,image/png,image/webp" 
                @change="handleAvatarSelect" 
                style="display: none;"
              />
              <button @click="triggerAvatarUpload" class="btn-secondary" :disabled="uploadingAvatar">
                {{ uploadingAvatar ? 'Uploading...' : $t('common.actions.upload') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Privacy Tab -->
        <div v-if="activeTab === 'privacy'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.privacy') }}</h2>
          
          <div class="form-section">
            <label class="form-label">Default Wall Privacy</label>
            <select v-model="formData.defaultWallPrivacy" class="form-select">
              <option value="public">{{ $t('walls.public') }}</option>
              <option value="private">{{ $t('walls.private') }}</option>
              <option value="followers">{{ $t('walls.followers') }}</option>
            </select>
          </div>
        </div>

        <!-- Notifications Tab -->
        <div v-if="activeTab === 'notifications'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.notifications') }}</h2>
          
          <div class="form-section">
            <label class="checkbox-label">
              <input v-model="formData.emailNotifications" type="checkbox" />
              <span>Email Notifications</span>
            </label>
          </div>

          <div class="form-section">
            <label class="checkbox-label">
              <input v-model="formData.pushNotifications" type="checkbox" />
              <span>Push Notifications</span>
            </label>
          </div>
        </div>

        <!-- Appearance Tab -->
        <div v-if="activeTab === 'appearance'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.appearance') }}</h2>
          
          <div class="form-section">
            <label class="form-label">{{ $t('settings.appearance.theme') }}</label>
            <div class="theme-selector">
              <button
                v-for="theme in themes"
                :key="theme.id"
                :class="['theme-option', { active: selectedTheme === theme.id }]"
                @click="selectTheme(theme.id)"
              >
                <span class="theme-preview" :style="{ background: theme.color }"></span>
                <span>{{ theme.name }}</span>
              </button>
            </div>
          </div>

          <div class="form-section">
            <label class="form-label">{{ $t('settings.appearance.language') }}</label>
            <div class="language-selector">
              <button
                v-for="lang in languages"
                :key="lang.code"
                :class="['language-option', { active: currentLocale === lang.code }]"
                @click="changeLanguage(lang.code)"
              >
                <span class="language-flag">{{ lang.flag }}</span>
                <span class="language-name">{{ lang.name }}</span>
              </button>
            </div>
          </div>

          <div class="form-section">
            <label class="form-label">{{ $t('settings.appearance.fontSize') }}</label>
            <select v-model="formData.fontSize" class="form-select">
              <option value="small">Small</option>
              <option value="medium">Medium</option>
              <option value="large">Large</option>
            </select>
          </div>
        </div>

        <!-- Bricks Tab -->
        <div v-if="activeTab === 'bricks'" class="tab-panel">
          <h2 class="panel-title">{{ $t('settings.tabs.bricks') }}</h2>
          
          <div class="bricks-balance">
            <div class="balance-display">
              <span class="balance-label">Current Balance</span>
              <span class="balance-value">{{ bricksBalance }} 🧱</span>
            </div>
            <button @click="claimDailyBricks" :disabled="!canClaimDaily" class="btn-primary">
              {{ canClaimDaily ? 'Claim Daily Bricks' : `Next claim in ${timeUntilClaim}` }}
            </button>
          </div>

          <div class="transactions-section">
            <h3>Recent Transactions</h3>
            <div v-if="transactions.length === 0" class="empty-state">
              No transactions yet
            </div>
            <div v-else class="transactions-list">
              <div v-for="tx in transactions" :key="tx.id" class="transaction-item">
                <span>{{ tx.description }}</span>
                <span :class="['transaction-amount', tx.amount > 0 ? 'positive' : 'negative']">
                  {{ tx.amount > 0 ? '+' : '' }}{{ tx.amount }} 🧱
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Button (sticky bottom) -->
    <div class="settings-footer">
      <button @click="saveSettings" class="btn-primary btn-large" :disabled="saving">
        {{ saving ? 'Saving...' : $t('common.buttons.save') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { setLocale, getLocale } from '@/i18n'
import apiClient from '@/services/api/client'
import { useThemeStore } from '@/stores/theme'

const authStore = useAuthStore()
const themeStore = useThemeStore()

const activeTab = ref('appearance') // Default to appearance tab to show language selector
const saving = ref(false)
const currentLocale = ref(getLocale())

const tabs = [
  { id: 'account', icon: '👤', label: 'Account' },
  { id: 'profile', icon: '📝', label: 'Profile' },
  { id: 'privacy', icon: '🔒', label: 'Privacy' },
  { id: 'notifications', icon: '🔔', label: 'Notifications' },
  { id: 'appearance', icon: '🎨', label: 'Appearance' },
  { id: 'bricks', icon: '🧱', label: 'Bricks' }
]

const languages = [
  { code: 'en', name: 'English', flag: '🇬🇧' },
  { code: 'ru', name: 'Русский', flag: '🇷🇺' }
]

const themes = [
  { id: 'light', name: 'Light', color: '#ffffff' },
  { id: 'dark', name: 'Dark', color: '#1a1a1a' },
  { id: 'green', name: 'Green', color: '#10b981' },
  { id: 'blue', name: 'Blue', color: '#3b82f6' },
  { id: 'cream', name: 'Cream', color: '#fef3c7' },
  { id: 'high-contrast', name: 'High Contrast', color: '#000000' }
]

const formData = ref({
  username: authStore.user?.username || '',
  email: authStore.user?.email || '',
  bio: authStore.user?.bio || '',
  defaultWallPrivacy: 'public',
  emailNotifications: true,
  pushNotifications: true,
  fontSize: 'medium'
})

const passwordData = ref({
  current: '',
  new: '',
  confirm: ''
})

const selectedTheme = ref(themeStore.currentTheme || 'light')
const avatarPreview = computed(() => authStore.user?.avatar_url || '/assets/images/default-avatar.svg')
const localAvatarPreview = ref(avatarPreview.value)
const uploadingAvatar = ref(false)
const avatarFileInput = ref<HTMLInputElement | null>(null)

const bricksBalance = ref(authStore.user?.bricks_balance || 0)
const canClaimDaily = ref(true)
const timeUntilClaim = ref('')
const transactions = ref<any[]>([])

const changeLanguage = async (locale: string) => {
  try {
    // Update i18n locale immediately
    setLocale(locale)
    currentLocale.value = locale
    
    // Save to backend
    await apiClient.patch('/users/me/settings', {
      language: locale
    })
    
    // Show success message
    console.log('Language changed to:', locale)
  } catch (error) {
    console.error('Failed to save language preference:', error)
  }
}

const selectTheme = (themeId: string) => {
  selectedTheme.value = themeId
  themeStore.setTheme(themeId)
}

const changePassword = async () => {
  if (passwordData.value.new !== passwordData.value.confirm) {
    alert('Passwords do not match')
    return
  }
  
  try {
    await apiClient.post('/users/me/change-password', {
      current_password: passwordData.value.current,
      new_password: passwordData.value.new
    })
    
    // Clear form
    passwordData.value = { current: '', new: '', confirm: '' }
    alert('Password changed successfully')
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to change password')
  }
}

const triggerAvatarUpload = () => {
  avatarFileInput.value?.click()
}

const handleAvatarSelect = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  // Validate file type
  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    alert('Please select a valid image file (JPEG, PNG, or WebP)')
    return
  }
  
  // Validate file size (5MB)
  const maxSize = 5 * 1024 * 1024 // 5MB in bytes
  if (file.size > maxSize) {
    alert('Image must be smaller than 5MB')
    return
  }
  
  try {
    uploadingAvatar.value = true
    
    // Update preview immediately with local URL
    localAvatarPreview.value = URL.createObjectURL(file)
    
    // Create form data
    const formData = new FormData()
    formData.append('avatar', file)
    
    // Upload using API client
    const result = await apiClient.upload('/upload/avatar', formData)
    
    if (!result.success) {
      throw new Error(result.message || 'Upload failed')
    }
    
    // Update auth store with new avatar URL
    authStore.updateUser({ avatar_url: result.data.avatar_url })
    localAvatarPreview.value = result.data.avatar_url
    
    alert('Avatar uploaded successfully!')
  } catch (error: any) {
    // Revert preview on error
    localAvatarPreview.value = avatarPreview.value
    alert(error.message || 'Failed to upload avatar. Please try again.')
  } finally {
    uploadingAvatar.value = false
    // Reset file input
    if (target) target.value = ''
  }
}

const uploadAvatar = () => {
  // TODO: Implement avatar upload
  console.log('Avatar upload coming soon')
}

const confirmDeleteAccount = () => {
  if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
    deleteAccount()
  }
}

const deleteAccount = async () => {
  try {
    await apiClient.delete('/users/me/account')
    authStore.logout()
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to delete account')
  }
}

const claimDailyBricks = async () => {
  try {
    const response = await apiClient.post('/bricks/claim')
    if (response.data.success) {
      bricksBalance.value = response.data.data.new_balance
      canClaimDaily.value = false
    }
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to claim daily bricks')
  }
}

const loadBricksData = async () => {
  try {
    const [balanceRes, transactionsRes] = await Promise.all([
      apiClient.get('/bricks/balance'),
      apiClient.get('/bricks/transactions?limit=10')
    ])
    
    if (balanceRes.success) {
      bricksBalance.value = balanceRes.data.balance
      // Update auth store with fresh balance
      authStore.updateUser({ bricks_balance: balanceRes.data.balance })
    }
    
    if (transactionsRes.success) {
      transactions.value = transactionsRes.data.transactions || []
    }
  } catch (error) {
    console.error('Failed to load bricks data:', error)
  }
}

const saveSettings = async () => {
  try {
    saving.value = true
    
    await apiClient.patch('/users/me/settings', {
      theme: selectedTheme.value,
      language: currentLocale.value,
      default_wall_privacy: formData.value.defaultWallPrivacy,
      email_notifications: formData.value.emailNotifications,
      push_notifications: formData.value.pushNotifications,
      font_size: formData.value.fontSize
    })
    
    // Update user data if username or email changed
    if (formData.value.username !== authStore.user?.username || 
        formData.value.email !== authStore.user?.email) {
      await apiClient.patch('/users/me', {
        username: formData.value.username,
        email: formData.value.email,
        bio: formData.value.bio
      })
    }
    
    alert('Settings saved successfully')
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to save settings')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  // Refresh user data from backend to get latest bricks balance
  try {
    const response = await apiClient.get('/auth/me')
    if (response.success && response.data.user) {
      authStore.updateUser(response.data.user)
    }
  } catch (error) {
    console.error('Failed to refresh user data:', error)
  }
  
  loadBricksData()
})
</script>

<style scoped>
.settings-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-6);
  padding-bottom: 160px; /* Safe zone for sticky footer */
}

.settings-header {
  margin-bottom: var(--spacing-6);
}

.settings-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.settings-container {
  display: grid;
  grid-template-columns: 250px 1fr;
  gap: var(--spacing-6);
  margin-bottom: var(--spacing-8);
}

.tabs-nav {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
  position: sticky;
  top: var(--spacing-4);
  height: fit-content;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-bg-elevated);
  border: 2px solid transparent;
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
  font-size: 1rem;
  color: var(--color-text-secondary);
}

.tab-button:hover {
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
}

.tab-button.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.tab-icon {
  font-size: 1.25rem;
}

.tab-content {
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-md);
}

.tab-panel {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.panel-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: var(--spacing-6);
  color: var(--color-text-primary);
}

.form-section {
  margin-bottom: var(--spacing-6);
}

.form-section h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: var(--spacing-3);
  color: var(--color-text-primary);
}

.form-label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--spacing-2);
  color: var(--color-text-primary);
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: var(--spacing-3);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 1rem;
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
  transition: border-color 0.2s;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: var(--color-primary);
}

.form-input + .form-input {
  margin-top: var(--spacing-3);
}

.form-textarea {
  resize: vertical;
  font-family: inherit;
}

.char-count {
  display: block;
  margin-top: var(--spacing-2);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  text-align: right;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
}

.theme-selector,
.language-selector {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: var(--spacing-3);
}

.theme-option,
.language-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-4);
  background: var(--color-bg-secondary);
  border: 2px solid transparent;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.2s;
}

.theme-option:hover,
.language-option:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

.theme-option.active,
.language-option.active {
  border-color: var(--color-primary);
  background: var(--color-primary-light);
}

.theme-preview {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 3px solid var(--color-border);
}

.language-flag {
  font-size: 3rem;
}

.language-name {
  font-weight: 600;
  color: var(--color-text-primary);
}

.avatar-upload {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
}

.avatar-preview {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--color-border);
}

.danger-zone {
  padding: var(--spacing-4);
  background: rgba(239, 68, 68, 0.1);
  border: 2px solid rgba(239, 68, 68, 0.3);
  border-radius: var(--radius-md);
}

.warning-text {
  color: #ef4444;
  margin-bottom: var(--spacing-3);
}

.bricks-balance {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: var(--spacing-6);
  border-radius: var(--radius-lg);
  color: white;
  margin-bottom: var(--spacing-6);
}

.balance-display {
  display: flex;
  flex-direction: column;
  margin-bottom: var(--spacing-4);
}

.balance-label {
  font-size: 0.875rem;
  opacity: 0.9;
}

.balance-value {
  font-size: 2.5rem;
  font-weight: 700;
}

.transactions-section h3 {
  margin-bottom: var(--spacing-4);
}

.transactions-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}

.transaction-item {
  display: flex;
  justify-content: space-between;
  padding: var(--spacing-3);
  background: var(--color-bg-secondary);
  border-radius: var(--radius-md);
}

.transaction-amount {
  font-weight: 600;
}

.transaction-amount.positive {
  color: #10b981;
}

.transaction-amount.negative {
  color: #ef4444;
}

.settings-footer {
  position: sticky;
  bottom: 0;
  background: var(--color-bg-primary);
  padding: var(--spacing-4) 0;
  border-top: 2px solid var(--color-border);
  z-index: 100;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
  will-change: transform;
}

.btn-primary,
.btn-secondary,
.btn-danger {
  padding: var(--spacing-3) var(--spacing-6);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 1rem;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: transparent;
  color: var(--color-primary);
  border: 2px solid var(--color-primary);
}

.btn-secondary:hover {
  background: var(--color-primary);
  color: white;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
}

.btn-large {
  width: 100%;
  padding: var(--spacing-4) var(--spacing-6);
  font-size: 1.125rem;
}

.empty-state {
  text-align: center;
  padding: var(--spacing-6);
  color: var(--color-text-secondary);
}

@media (max-width: 768px) {
  .settings-view {
    padding-bottom: 180px; /* Larger safe zone for mobile */
  }
  
  .settings-container {
    grid-template-columns: 1fr;
  }
  
  .tabs-nav {
    flex-direction: row;
    overflow-x: auto;
    position: static;
  }
  
  .tab-button {
    flex-shrink: 0;
  }
  
  .tab-label {
    display: none;
  }
  
  .theme-selector,
  .language-selector {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
>>>>>>> Remote
