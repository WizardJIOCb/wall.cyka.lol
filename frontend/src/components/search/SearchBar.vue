<template>
  <div class="search-bar" :class="{'search-bar--focused': isFocused}">
    <div class="search-bar__input-wrapper">
      <span class="search-icon">🔍</span>
      
      <input
        ref="searchInput"
        v-model="query"
        type="text"
        class="search-input"
        :placeholder="placeholder"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter="handleSubmit"
        @keydown.down.prevent="navigateResults(1)"
        @keydown.up.prevent="navigateResults(-1)"
        @keydown.esc="handleEscape"
        :maxlength="200"
      />

      <button
        v-if="query.length > 0"
        class="clear-button"
        @click="clearSearch"
        aria-label="Clear search"
      >
        ✕
      </button>
    </div>

    <!-- Auto-suggest dropdown -->
    <div v-if="showSuggestions && suggestions.length > 0" class="suggestions-dropdown">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        class="suggestion-item"
        :class="{'suggestion-item--active': index === activeIndex}"
        @click="selectSuggestion(suggestion)"
        @mouseenter="activeIndex = index"
      >
        <span class="suggestion-icon">{{ getSuggestionIcon(suggestion.type) }}</span>
        <span class="suggestion-text">{{ suggestion.text }}</span>
        <span class="suggestion-type">{{ suggestion.type }}</span>
      </div>
    </div>

    <!-- Recent searches -->
    <div v-if="showRecent && recentSearches.length > 0 && query.length === 0" class="recent-dropdown">
      <div class="recent-header">
        <span>Recent Searches</span>
        <button @click="clearRecentSearches" class="clear-recent-button">Clear</button>
      </div>
      <div
        v-for="(recent, index) in recentSearches"
        :key="index"
        class="recent-item"
        @click="selectRecent(recent)"
      >
        <span class="recent-icon">🕐</span>
        <span class="recent-text">{{ recent }}</span>
        <button 
          @click.stop="removeRecent(index)"
          class="remove-recent-button"
          aria-label="Remove"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- Keyboard shortcut hint -->
    <div v-if="showShortcutHint && !isFocused" class="shortcut-hint">
      <kbd>{{ shortcutKey }}</kbd> + <kbd>K</kbd>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDebounceFn } from '@vueuse/core'

interface Suggestion {
  text: string
  type: 'post' | 'wall' | 'user' | 'ai-app' | 'query'
  id?: number
}

interface Props {
  placeholder?: string
  showSuggestions?: boolean
  showRecent?: boolean
  showShortcutHint?: boolean
  autofocus?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Search posts, walls, users...',
  showSuggestions: true,
  showRecent: true,
  showShortcutHint: true,
  autofocus: false
})

const emit = defineEmits<{
  'search': [query: string]
  'suggestion-select': [suggestion: Suggestion]
}>()

const router = useRouter()

// State
const query = ref('')
const isFocused = ref(false)
const suggestions = ref<Suggestion[]>([])
const recentSearches = ref<string[]>([])
const activeIndex = ref(-1)
const searchInput = ref<HTMLInputElement | null>(null)

// Computed
const shortcutKey = computed(() => {
  return navigator.platform.toLowerCase().includes('mac') ? '⌘' : 'Ctrl'
})

// Methods
const handleInput = useDebounceFn(() => {
  if (query.value.length >= 2) {
    fetchSuggestions()
  } else {
    suggestions.value = []
  }
}, 300)

const fetchSuggestions = async () => {
  // Simplified - in real implementation, call API
  // For now, just create mock suggestions
  suggestions.value = [
    { text: query.value, type: 'query' }
  ]
}

const handleFocus = () => {
  isFocused.value = true
}

const handleBlur = () => {
  // Delay to allow click on suggestion
  setTimeout(() => {
    isFocused.value = false
  }, 200)
}

const handleSubmit = () => {
  if (query.value.trim().length >= 2) {
    performSearch(query.value.trim())
  }
}

const handleEscape = () => {
  if (suggestions.value.length > 0) {
    suggestions.value = []
    activeIndex.value = -1
  } else {
    query.value = ''
    searchInput.value?.blur()
  }
}

const performSearch = (searchQuery: string) => {
  // Add to recent searches
  addRecentSearch(searchQuery)
  
  // Emit search event
  emit('search', searchQuery)
  
  // Navigate to search results page
  router.push({
    path: '/search',
    query: { q: searchQuery }
  })
  
  // Clear suggestions
  suggestions.value = []
  activeIndex.value = -1
}

const selectSuggestion = (suggestion: Suggestion) => {
  query.value = suggestion.text
  emit('suggestion-select', suggestion)
  performSearch(suggestion.text)
}

const selectRecent = (recent: string) => {
  query.value = recent
  performSearch(recent)
}

const clearSearch = () => {
  query.value = ''
  suggestions.value = []
  activeIndex.value = -1
  searchInput.value?.focus()
}

const navigateResults = (direction: number) => {
  if (suggestions.value.length === 0) return
  
  activeIndex.value += direction
  
  if (activeIndex.value < 0) {
    activeIndex.value = suggestions.value.length - 1
  } else if (activeIndex.value >= suggestions.value.length) {
    activeIndex.value = 0
  }
  
  // Update input with selected suggestion
  query.value = suggestions.value[activeIndex.value].text
}

const getSuggestionIcon = (type: string): string => {
  const icons = {
    post: '📝',
    wall: '🖼️',
    user: '👤',
    'ai-app': '🤖',
    query: '🔍'
  }
  return icons[type as keyof typeof icons] || '🔍'
}

const addRecentSearch = (search: string) => {
  // Remove if already exists
  const index = recentSearches.value.indexOf(search)
  if (index > -1) {
    recentSearches.value.splice(index, 1)
  }
  
  // Add to beginning
  recentSearches.value.unshift(search)
  
  // Keep only last 5
  if (recentSearches.value.length > 5) {
    recentSearches.value.pop()
  }
  
  // Save to localStorage
  localStorage.setItem('recentSearches', JSON.stringify(recentSearches.value))
}

const removeRecent = (index: number) => {
  recentSearches.value.splice(index, 1)
  localStorage.setItem('recentSearches', JSON.stringify(recentSearches.value))
}

const clearRecentSearches = () => {
  recentSearches.value = []
  localStorage.removeItem('recentSearches')
}

const handleGlobalShortcut = (e: KeyboardEvent) => {
  const isCtrlK = (e.ctrlKey || e.metaKey) && e.key === 'k'
  if (isCtrlK) {
    e.preventDefault()
    searchInput.value?.focus()
  }
}

// Lifecycle
onMounted(() => {
  // Load recent searches from localStorage
  const stored = localStorage.getItem('recentSearches')
  if (stored) {
    try {
      recentSearches.value = JSON.parse(stored)
    } catch (e) {
      console.error('Failed to parse recent searches', e)
    }
  }
  
  // Register global keyboard shortcut
  window.addEventListener('keydown', handleGlobalShortcut)
  
  // Autofocus if requested
  if (props.autofocus) {
    searchInput.value?.focus()
  }
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalShortcut)
})

// Expose for parent
defineExpose({
  focus: () => searchInput.value?.focus(),
  clear: clearSearch
})
</script>

<style scoped>
.search-bar {
  position: relative;
  width: 100%;
  max-width: 600px;
}

.search-bar__input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: var(--color-background);
  border: 2px solid var(--color-border);
  border-radius: 0.75rem;
  transition: all 0.2s;
}

.search-bar--focused .search-bar__input-wrapper {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.search-icon {
  padding: 0 0.75rem;
  font-size: 1.25rem;
  color: var(--color-text-secondary);
}

.search-input {
  flex: 1;
  padding: 0.75rem 0.5rem;
  border: none;
  background: transparent;
  font-size: 1rem;
  color: var(--color-text-primary);
  outline: none;
}

.search-input::placeholder {
  color: var(--color-text-secondary);
}

.clear-button {
  padding: 0.5rem 0.75rem;
  background: none;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  font-size: 1.25rem;
  transition: color 0.2s;
}

.clear-button:hover {
  color: var(--color-danger);
}

/* Suggestions dropdown */
.suggestions-dropdown,
.recent-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 0;
  right: 0;
  background: var(--color-background);
  border: 2px solid var(--color-border);
  border-radius: 0.75rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  max-height: 400px;
  overflow-y: auto;
  z-index: 1000;
}

.suggestion-item,
.recent-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background 0.2s;
}

.suggestion-item:hover,
.recent-item:hover,
.suggestion-item--active {
  background: var(--color-background-hover);
}

.suggestion-icon,
.recent-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.suggestion-text,
.recent-text {
  flex: 1;
  color: var(--color-text-primary);
  font-size: 0.95rem;
}

.suggestion-type {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  padding: 0.25rem 0.5rem;
  background: var(--color-background-secondary);
  border-radius: 0.25rem;
}

/* Recent searches */
.recent-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-border);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.clear-recent-button,
.remove-recent-button {
  background: none;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  font-size: 0.875rem;
  transition: color 0.2s;
}

.clear-recent-button:hover,
.remove-recent-button:hover {
  color: var(--color-danger);
}

/* Keyboard shortcut hint */
.shortcut-hint {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  pointer-events: none;
}

kbd {
  padding: 0.2rem 0.4rem;
  background: var(--color-background-secondary);
  border: 1px solid var(--color-border);
  border-radius: 0.25rem;
  font-size: 0.7rem;
  font-family: monospace;
}

/* Responsive */
@media (max-width: 640px) {
  .shortcut-hint {
    display: none;
  }
  
  .search-bar {
    max-width: 100%;
  }
}
</style>
