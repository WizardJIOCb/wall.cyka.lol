/**
 * Application constants
 */

// API endpoints
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1'
export const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:8080'

// Local storage keys
export const STORAGE_KEYS = {
  AUTH_TOKEN: 'wall_auth_token',
  USER: 'wall_user',
  THEME: 'wall_theme',
  LANGUAGE: 'wall_language'
} as const

// Pagination
export const DEFAULT_PAGE_SIZE = 20
export const MAX_PAGE_SIZE = 100

// File upload limits
export const MAX_IMAGE_SIZE = 10 * 1024 * 1024 // 10MB
export const MAX_VIDEO_SIZE = 100 * 1024 * 1024 // 100MB
export const MAX_ATTACHMENTS = 4

// Supported file types
export const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
export const SUPPORTED_VIDEO_TYPES = ['video/mp4', 'video/webm']

// Validation rules
export const USERNAME_PATTERN = /^[a-zA-Z0-9_]{3,50}$/
export const MIN_PASSWORD_LENGTH = 8
export const MAX_POST_LENGTH = 10000
export const MAX_COMMENT_LENGTH = 5000

// Theme names
export const THEMES = ['light', 'dark', 'green', 'cream', 'blue', 'high-contrast'] as const

// Reaction types
export const REACTION_TYPES = ['like', 'dislike', 'love', 'haha', 'wow', 'sad', 'angry'] as const

// Notification types
export const NOTIFICATION_TYPES = [
  'post_reaction',
  'comment',
  'mention',
  'follow',
  'friend_request',
  'ai_completion',
  'message',
  'system'
] as const

// Toast duration
export const TOAST_DURATION = {
  SHORT: 2000,
  MEDIUM: 3000,
  LONG: 5000
} as const

// Debounce delays
export const DEBOUNCE_DELAYS = {
  SEARCH: 300,
  INPUT: 500,
  SCROLL: 100
} as const

// Route names for type safety
export const ROUTE_NAMES = {
  HOME: 'home',
  LOGIN: 'login',
  REGISTER: 'register',
  PROFILE: 'profile',
  WALL: 'wall',
  DISCOVER: 'discover',
  MESSAGES: 'messages',
  NOTIFICATIONS: 'notifications',
  SETTINGS: 'settings',
  AI_GENERATE: 'ai',
  NOT_FOUND: 'not-found'
} as const
