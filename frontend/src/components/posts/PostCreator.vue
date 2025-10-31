<template>
  <AppModal v-model="isOpen" title="Create Post" size="md">
    <form @submit.prevent="handleSubmit">
      <textarea v-model="content" placeholder="What's on your mind?" rows="6" class="post-input"></textarea>
      <div class="form-actions">
        <input type="file" ref="fileInput" @change="handleFileSelect" accept="image/*,video/*" multiple hidden />
        <AppButton type="button" variant="ghost" @click="fileInput?.click()">📎 Add Media</AppButton>
        <AppButton type="submit" variant="primary" :loading="loading">Post</AppButton>
      </div>
    </form>
  </AppModal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps<{ modelValue: boolean; wallId: number }>()
const emit = defineEmits<{ 'update:modelValue': [value: boolean]; submit: [data: any] }>()

const isOpen = ref(props.modelValue)
const content = ref('')
const files = ref<File[]>([])
const fileInput = ref<HTMLInputElement>()
const loading = ref(false)

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files) files.value = Array.from(target.files)
}

const handleSubmit = () => {
  if (!content.value.trim()) return
  emit('submit', { wall_id: props.wallId, content: content.value, media_files: files.value })
  content.value = ''
  files.value = []
  emit('update:modelValue', false)
}
</script>

<style scoped>
.post-input { width: 100%; padding: var(--spacing-3); border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: 1rem; resize: vertical; }
.form-actions { display: flex; justify-content: space-between; margin-top: var(--spacing-4); }
</style>
