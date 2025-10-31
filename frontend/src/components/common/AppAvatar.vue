<template>
  <div :class="avatarClasses">
    <img
      v-if="src"
      :src="src"
      :alt="alt"
      class="avatar-image"
      @error="handleImageError"
    />
    <span v-else class="avatar-placeholder">
      {{ initials }}
    </span>
    <span v-if="online" class="avatar-status"></span>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  src?: string
  alt?: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  online?: boolean
  name?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  alt: 'Avatar',
  online: false
})

const imageError = ref(false)

const avatarClasses = computed(() => [
  'avatar',
  `avatar-${props.size}`,
  {
    'avatar-online': props.online
  }
])

const initials = computed(() => {
  if (!props.name) return '?'
  
  const parts = props.name.trim().split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return props.name.slice(0, 2).toUpperCase()
})

const handleImageError = () => {
  imageError.value = true
}
</script>

<style scoped>
.avatar {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  overflow: hidden;
  background: var(--surface-hover);
  flex-shrink: 0;
}

/* Sizes */
.avatar-xs {
  width: 24px;
  height: 24px;
  font-size: 0.625rem;
}

.avatar-sm {
  width: 32px;
  height: 32px;
  font-size: 0.75rem;
}

.avatar-md {
  width: 40px;
  height: 40px;
  font-size: 0.875rem;
}

.avatar-lg {
  width: 56px;
  height: 56px;
  font-size: 1rem;
}

.avatar-xl {
  width: 80px;
  height: 80px;
  font-size: 1.5rem;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  font-weight: 600;
  color: var(--text-secondary);
}

.avatar-status {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 25%;
  height: 25%;
  border-radius: 50%;
  background: var(--success);
  border: 2px solid var(--surface);
}

.avatar-xs .avatar-status {
  width: 8px;
  height: 8px;
  border-width: 1px;
}

.avatar-sm .avatar-status {
  width: 10px;
  height: 10px;
  border-width: 2px;
}
</style>
