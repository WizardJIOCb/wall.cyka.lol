/**
 * Formatting utility functions
 */

import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

/**
 * Format date to relative time (e.g., "2 hours ago")
 */
export const formatRelativeTime = (date: string | Date): string => {
  return dayjs(date).fromNow()
}

/**
 * Format date to human-readable format
 */
export const formatDate = (date: string | Date, format = 'MMM D, YYYY'): string => {
  return dayjs(date).format(format)
}

/**
 * Format number with thousands separator
 */
export const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('en-US').format(num)
}

/**
 * Format number to compact form (e.g., 1.2K, 3.4M)
 */
export const formatCompactNumber = (num: number): string => {
  if (num < 1000) return num.toString()
  if (num < 1000000) return `${(num / 1000).toFixed(1)}K`
  return `${(num / 1000000).toFixed(1)}M`
}

/**
 * Format file size to human-readable format
 */
export const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${(bytes / Math.pow(k, i)).toFixed(2)} ${sizes[i]}`
}

/**
 * Truncate text with ellipsis
 */
export const truncateText = (text: string, maxLength: number): string => {
  if (text.length <= maxLength) return text
  return `${text.slice(0, maxLength)}...`
}

/**
 * Extract hashtags from text
 */
export const extractHashtags = (text: string): string[] => {
  const hashtagPattern = /#(\w+)/g
  const matches = text.match(hashtagPattern)
  return matches ? matches.map(tag => tag.slice(1)) : []
}

/**
 * Extract mentions from text
 */
export const extractMentions = (text: string): string[] => {
  const mentionPattern = /@(\w+)/g
  const matches = text.match(mentionPattern)
  return matches ? matches.map(mention => mention.slice(1)) : []
}
