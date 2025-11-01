-- Add Reactions Table
-- This table stores all reactions (likes, love, etc.) for posts and comments

CREATE TABLE IF NOT EXISTS reactions (
  reaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reactable_type ENUM('post', 'comment') NOT NULL,
  reactable_id INT NOT NULL,
  reaction_type ENUM('like', 'love', 'laugh', 'wow', 'sad', 'angry') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_reaction (user_id, reactable_type, reactable_id),
  INDEX idx_reactable (reactable_type, reactable_id),
  INDEX idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
CREATE INDEX idx_reaction_type ON reactions(reaction_type);
CREATE INDEX idx_created_at ON reactions(created_at);
