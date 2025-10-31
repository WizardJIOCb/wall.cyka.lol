import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import type { Theme } from '@/types/components'
import { STORAGE_KEYS, THEMES } from '@/utils/constants'

export const useThemeStore = defineStore('theme', () => {
  // State
  const currentTheme = ref<Theme>('light')

  // Actions
  const init = () => {
    // Load theme from localStorage or system preference
    const savedTheme = localStorage.getItem(STORAGE_KEYS.THEME) as Theme
    
    if (savedTheme && THEMES.includes(savedTheme)) {
      currentTheme.value = savedTheme
    } else {
      // Check system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      currentTheme.value = prefersDark ? 'dark' : 'light'
    }
    
    applyTheme(currentTheme.value)
  }

  const setTheme = (theme: Theme) => {
    currentTheme.value = theme
    applyTheme(theme)
    localStorage.setItem(STORAGE_KEYS.THEME, theme)
  }

  const toggleDarkMode = () => {
    const newTheme = currentTheme.value === 'dark' ? 'light' : 'dark'
    setTheme(newTheme)
  }

  const applyTheme = (theme: Theme) => {
    // Simply set the data-theme attribute
    // Theme styles are already included in the main CSS via CSS custom properties
    document.documentElement.setAttribute('data-theme', theme)
  }

  // Watch for theme changes
  watch(currentTheme, (newTheme) => {
    applyTheme(newTheme)
  })

  return {
    // State
    currentTheme,
    
    // Actions
    init,
    setTheme,
    toggleDarkMode
  }
})
