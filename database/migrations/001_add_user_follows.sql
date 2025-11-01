-- Migration: Add user follows system
-- Created: 2024
-- Description: Add table for user follow relationships

CREATE TABLE IF NOT EXISTS user_follows (
  follow_id INT AUTO_INCREMENT PRIMARY KEY,
  follower_id INT NOT NULL,
  following_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_follow (follower_id, following_id),
  FOREIGN KEY (follower_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (following_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_follower (follower_id),
  INDEX idx_following (following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add follower/following counts to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS followers_count INT DEFAULT 0 NOT NULL AFTER reactions_given_count,
ADD COLUMN IF NOT EXISTS following_count INT DEFAULT 0 NOT NULL AFTER followers_count;
