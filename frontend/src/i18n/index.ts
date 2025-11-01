import { createI18n } from 'vue-i18n'
import messages from './locales'

// Get saved locale from localStorage or use browser language
const getSavedLocale = (): string => {
  const saved = localStorage.getItem('wall_app_locale')
  if (saved && ['en', 'ru'].includes(saved)) {
    return saved
  }
  
  // Try to detect from browser
  const browserLang = navigator.language.split('-')[0]
  return ['en', 'ru'].includes(browserLang) ? browserLang : 'en'
}

const i18n = createI18n({
  legacy: false, // Use Composition API mode
  locale: getSavedLocale(),
  fallbackLocale: 'en',
  messages,
  globalInjection: true
})

export default i18n

// Helper function to change locale
export const setLocale = (locale: string) => {
  if (['en', 'ru'].includes(locale)) {
    i18n.global.locale.value = locale as 'en' | 'ru'
    localStorage.setItem('wall_app_locale', locale)
    document.documentElement.lang = locale
  }
}

// Helper function to get current locale
export const getLocale = (): string => {
  return i18n.global.locale.value
}
