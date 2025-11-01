-- Migration: Add user preferences
-- Created: 2024
-- Description: Add table for user settings and preferences

CREATE TABLE IF NOT EXISTS user_preferences (
  preference_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  theme VARCHAR(50) DEFAULT 'light',
  language VARCHAR(5) DEFAULT 'en',
  email_notifications BOOLEAN DEFAULT TRUE,
  push_notifications BOOLEAN DEFAULT TRUE,
  notification_frequency ENUM('instant', 'hourly', 'daily') DEFAULT 'instant',
  privacy_default_wall ENUM('public', 'followers', 'private') DEFAULT 'public',
  privacy_can_follow ENUM('everyone', 'mutual', 'nobody') DEFAULT 'everyone',
  privacy_can_message ENUM('everyone', 'followers', 'mutual') DEFAULT 'everyone',
  accessibility_font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add preferred language to users table for quick access
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS preferred_language VARCHAR(5) DEFAULT 'en' AFTER theme_preference;
