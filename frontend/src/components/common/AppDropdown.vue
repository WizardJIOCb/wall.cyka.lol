<template>
  <div class="dropdown" ref="dropdownRef">
    <button
      class="dropdown-trigger"
      :aria-expanded="isOpen"
      @click="toggle"
    >
      <slot name="trigger" />
    </button>

    <Transition name="dropdown">
      <div v-if="isOpen" :class="dropdownClasses">
        <slot />
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Props {
  position?: 'bottom-left' | 'bottom-right' | 'top-left' | 'top-right'
}

const props = withDefaults(defineProps<Props>(), {
  position: 'bottom-right'
})

const isOpen = ref(false)
const dropdownRef = ref<HTMLElement>()

const dropdownClasses = computed(() => [
  'dropdown-menu',
  `dropdown-${props.position}`
])

const toggle = () => {
  isOpen.value = !isOpen.value
}

const close = () => {
  isOpen.value = false
}

const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    close()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

defineExpose({
  isOpen,
  toggle,
  close
})
</script>

<style scoped>
.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-trigger {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}

.dropdown-menu {
  position: absolute;
  z-index: 100;
  min-width: 200px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  padding: var(--spacing-2) 0;
  margin-top: var(--spacing-2);
}

.dropdown-bottom-right {
  right: 0;
  top: 100%;
}

.dropdown-bottom-left {
  left: 0;
  top: 100%;
}

.dropdown-top-right {
  right: 0;
  bottom: 100%;
  margin-bottom: var(--spacing-2);
  margin-top: 0;
}

.dropdown-top-left {
  left: 0;
  bottom: 100%;
  margin-bottom: var(--spacing-2);
  margin-top: 0;
}

/* Transitions */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
