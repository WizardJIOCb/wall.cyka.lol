-- Migration: Add notifications system
-- Created: 2024
-- Description: Add table for user notifications

CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL COMMENT 'Notification recipient',
  actor_id INT NULL COMMENT 'User who triggered the notification',
  notification_type ENUM('follow', 'reaction', 'comment', 'reply', 'mention', 'bricks', 'ai_complete') NOT NULL,
  target_type VARCHAR(50) NOT NULL COMMENT 'post, comment, wall, user, ai_app',
  target_id INT NOT NULL,
  content JSON NULL COMMENT 'Additional notification data',
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (actor_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_user_read (user_id, is_read),
  INDEX idx_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
