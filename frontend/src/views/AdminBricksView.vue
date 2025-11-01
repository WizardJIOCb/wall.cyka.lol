<template>
  <div class="admin-bricks-view">
    <div class="admin-header">
      <h1>Bricks Management</h1>
      <p class="subtitle">Manage user bricks balance</p>
    </div>

    <div class="admin-panel">
      <div class="form-card">
        <h2>Add Bricks to User</h2>
        
        <form @submit.prevent="handleAddBricks" class="bricks-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input
              id="username"
              v-model="form.username"
              type="text"
              placeholder="Enter username"
              required
            />
          </div>

          <div class="form-group">
            <label for="amount">Amount</label>
            <input
              id="amount"
              v-model.number="form.amount"
              type="number"
              min="1"
              placeholder="Enter bricks amount"
              required
            />
          </div>

          <div class="form-group">
            <label for="reason">Reason (optional)</label>
            <input
              id="reason"
              v-model="form.reason"
              type="text"
              placeholder="Enter reason"
            />
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading">Loading...</span>
              <span v-else>Add Bricks</span>
            </button>
          </div>
        </form>

        <div v-if="message" class="message" :class="messageType">
          {{ message }}
        </div>

        <div v-if="result" class="result-card">
          <h3>Result</h3>
          <p><strong>New Balance:</strong> {{ result.new_balance?.toLocaleString() || 0 }}</p>
          <p><strong>Amount Added:</strong> {{ result.amount?.toLocaleString() || 0 }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { apiClient } from '@/services/api/client'

const form = ref({
  username: '',
  amount: 0,
  reason: ''
})

const loading = ref(false)
const message = ref('')
const messageType = ref<'success' | 'error'>('success')
const result = ref<{ new_balance: number; amount: number } | null>(null)

async function handleAddBricks() {
  if (!form.value.username || !form.value.amount) {
    message.value = 'Please fill in all required fields'
    messageType.value = 'error'
    return
  }

  loading.value = true
  message.value = ''
  result.value = null

  try {
    // First get user by username
    const usersResponse = await apiClient.get(`/api/v1/users/search?q=${form.value.username}`)
    
    if (!usersResponse.data.success || !usersResponse.data.data.users?.length) {
      throw new Error('User not found')
    }

    const user = usersResponse.data.data.users[0]
    const userId = user.user_id

    // Now add bricks
    const response = await apiClient.post('/api/v1/bricks/admin/add', {
      user_id: userId,
      amount: form.value.amount,
      reason: form.value.reason
    })

    if (response.data.success) {
      result.value = response.data.data
      message.value = response.data.message || 'Bricks added successfully!'
      messageType.value = 'success'
      
      // Reset form
      form.value = {
        username: '',
        amount: 0,
        reason: ''
      }
    } else {
      throw new Error(response.data.message || 'Failed to add bricks')
    }
  } catch (error: any) {
    console.error('Error adding bricks:', error)
    message.value = error.response?.data?.message || error.message || 'Error adding bricks'
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.admin-bricks-view {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;
}

.admin-header {
  margin-bottom: 2rem;
}

.admin-header h1 {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #6b7280;
  font-size: 1rem;
}

.admin-panel {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-card {
  padding: 2rem;
}

.form-card h2 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
}

.bricks-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #374151;
}

.form-group input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-actions {
  display: flex;
  justify-content: flex-start;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.message {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 6px;
  font-weight: 500;
}

.message.success {
  background-color: #d1fae5;
  color: #065f46;
  border: 1px solid #6ee7b7;
}

.message.error {
  background-color: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.result-card {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background-color: #f3f4f6;
  border-radius: 6px;
}

.result-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.result-card p {
  margin-bottom: 0.5rem;
  color: #374151;
}

.result-card strong {
  font-weight: 600;
  color: #111827;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .admin-panel {
    background: #1f2937;
  }

  .form-card h2,
  .form-group label {
    color: #f9fafb;
  }

  .form-group input {
    background: #374151;
    border-color: #4b5563;
    color: #f9fafb;
  }

  .result-card {
    background-color: #374151;
  }

  .result-card p {
    color: #d1d5db;
  }

  .result-card strong {
    color: #f9fafb;
  }
}
</style>
