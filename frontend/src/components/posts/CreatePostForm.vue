<template>
  <form @submit.prevent="handleSubmit" class="create-post-form">
    <!-- Content Input -->
    <div class="form-group">
      <textarea 
        v-model="formData.content"
        placeholder="What's on your mind?"
        rows="6"
        class="post-textarea"
        maxlength="5000"
        required
      ></textarea>
      <div class="char-count">{{ formData.content.length }}/5000</div>
    </div>

    <!-- Title Input (Optional) -->
    <div class="form-group">
      <input 
        v-model="formData.title"
        type="text"
        placeholder="Title (optional)"
        class="post-title-input"
        maxlength="200"
      />
    </div>

    <!-- Media Preview -->
    <div v-if="mediaFiles.length > 0" class="media-preview">
      <div v-for="(file, index) in mediaFiles" :key="index" class="media-item">
        <img v-if="file.type.startsWith('image/')" :src="getFilePreview(file)" :alt="`Preview ${index + 1}`" />
        <video v-else-if="file.type.startsWith('video/')" :src="getFilePreview(file)" controls></video>
        <button type="button" @click="removeMedia(index)" class="remove-media-btn" aria-label="Remove media">
          ✕
        </button>
      </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
      <div class="action-buttons">
        <input 
          type="file" 
          ref="fileInputRef" 
          @change="handleFileSelect" 
          accept="image/*,video/*" 
          multiple 
          hidden 
        />
        <button type="button" @click="fileInputRef?.click()" class="action-btn" aria-label="Add media">
          📎 Media
        </button>
        <button type="button" @click="showEmojiPicker = !showEmojiPicker" class="action-btn" aria-label="Add emoji">
          😊 Emoji
        </button>
      </div>
      
      <div class="submit-actions">
        <AppButton type="submit" variant="primary" :loading="isSubmitting" :disabled="!isFormValid">
          Post
        </AppButton>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import apiClient from '@/services/api/client'
import AppButton from '@/components/common/AppButton.vue'

const emit = defineEmits<{
  success: []
}>()

const authStore = useAuthStore()
const toast = useToast()

// State
const formData = ref({
  title: '',
  content: '',
  visibility: 'public'
})

const mediaFiles = ref<File[]>([])
const fileInputRef = ref<HTMLInputElement>()
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const showEmojiPicker = ref(false)

// Computed
const isFormValid = computed(() => {
  return formData.value.content.trim().length > 0
})

// Methods
const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files) {
    const newFiles = Array.from(target.files)
    
    // Validate file size (max 10MB per file)
    const maxSize = 10 * 1024 * 1024
    const validFiles = newFiles.filter(file => {
      if (file.size > maxSize) {
        toast.warning(`File ${file.name} is too large (max 10MB)`)
        return false
      }
      return true
    })
    
    mediaFiles.value.push(...validFiles)
  }
}

const removeMedia = (index: number) => {
  mediaFiles.value.splice(index, 1)
}

const getFilePreview = (file: File): string => {
  return URL.createObjectURL(file)
}

const handleSubmit = async () => {
  if (!isFormValid.value) return
  
  try {
    isSubmitting.value = true
    error.value = null

    // Get user's default wall
    const wallResponse = await apiClient.get('/walls/me')
    const wallId = wallResponse?.data?.wall?.id

    if (!wallId) {
      throw new Error('No wall found. Please create a wall first.')
    }

    // Create FormData for file upload
    const formDataToSend = new FormData()
    formDataToSend.append('wall_id', wallId.toString())
    formDataToSend.append('content', formData.value.content.trim())
    
    if (formData.value.title) {
      formDataToSend.append('title', formData.value.title.trim())
    }
    
    formDataToSend.append('visibility', formData.value.visibility)
    formDataToSend.append('content_type', 'text')

    // Add media files
    mediaFiles.value.forEach((file, index) => {
      formDataToSend.append(`media_${index}`, file)
    })

    // Submit post
    await apiClient.post('/posts', formDataToSend, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    // Reset form
    formData.value = {
      title: '',
      content: '',
      visibility: 'public'
    }
    mediaFiles.value = []

    emit('success')
    
  } catch (err: any) {
    console.error('Create post error:', err)
    error.value = err.response?.data?.message || 'Failed to create post'
    toast.error(error.value)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.create-post-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.form-group {
  position: relative;
}

.post-textarea {
  width: 100%;
  padding: var(--spacing-3);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  font-family: inherit;
  font-size: 1rem;
  line-height: 1.6;
  resize: vertical;
  min-height: 120px;
  background: var(--background);
  color: var(--text-primary);
  transition: border-color 0.2s;
}

.post-textarea:focus {
  outline: none;
  border-color: var(--primary);
}

.char-count {
  position: absolute;
  bottom: var(--spacing-2);
  right: var(--spacing-2);
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.post-title-input {
  width: 100%;
  padding: var(--spacing-3);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  font-family: inherit;
  font-size: 1.125rem;
  font-weight: 600;
  background: var(--background);
  color: var(--text-primary);
  transition: border-color 0.2s;
}

.post-title-input:focus {
  outline: none;
  border-color: var(--primary);
}

.media-preview {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: var(--spacing-2);
}

.media-item {
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  aspect-ratio: 1;
}

.media-item img,
.media-item video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-media-btn {
  position: absolute;
  top: var(--spacing-1);
  right: var(--spacing-1);
  width: 24px;
  height: 24px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  transition: background 0.2s;
}

.remove-media-btn:hover {
  background: rgba(0, 0, 0, 0.9);
}

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: var(--spacing-2);
  border-top: 1px solid var(--border);
}

.action-buttons {
  display: flex;
  gap: var(--spacing-2);
}

.action-btn {
  padding: var(--spacing-2) var(--spacing-3);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  color: var(--text-primary);
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--surface-hover);
  border-color: var(--primary);
}

.submit-actions {
  display: flex;
  gap: var(--spacing-2);
}

.error-message {
  padding: var(--spacing-3);
  background: var(--danger-light, #fee);
  color: var(--danger);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
}

@media (max-width: 640px) {
  .form-actions {
    flex-direction: column;
    gap: var(--spacing-3);
  }

  .action-buttons,
  .submit-actions {
    width: 100%;
  }

  .submit-actions {
    justify-content: flex-end;
  }
}
</style>
