/**
 * Validation utility functions
 */

import { USERNAME_PATTERN, MIN_PASSWORD_LENGTH } from './constants'

export const validateEmail = (email: string): boolean => {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailPattern.test(email)
}

export const validateUsername = (username: string): boolean => {
  return USERNAME_PATTERN.test(username)
}

export const validatePassword = (password: string): { valid: boolean; strength: number } => {
  const valid = password.length >= MIN_PASSWORD_LENGTH
  let strength = 0

  // Length check
  if (password.length >= 8) strength++
  if (password.length >= 12) strength++

  // Complexity checks
  if (/[a-z]/.test(password)) strength++
  if (/[A-Z]/.test(password)) strength++
  if (/[0-9]/.test(password)) strength++
  if (/[^a-zA-Z0-9]/.test(password)) strength++

  return {
    valid,
    strength: Math.min(strength, 5) // Max 5 (0-5 scale)
  }
}

export const validateUrl = (url: string): boolean => {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

export const validateFileSize = (file: File, maxSize: number): boolean => {
  return file.size <= maxSize
}

export const validateFileType = (file: File, allowedTypes: string[]): boolean => {
  return allowedTypes.includes(file.type)
}
