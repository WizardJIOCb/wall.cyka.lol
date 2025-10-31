-- Wall Social Platform Database Schema
-- MySQL 8.0+ Required
-- Complete schema with 28 tables

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- Users Table
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NULL,
  display_name VARCHAR(100) NOT NULL,
  avatar_url VARCHAR(500) NULL,
  bio VARCHAR(500) NULL,
  extended_bio TEXT NULL,
  location VARCHAR(255) NULL,
  bricks_balance INT DEFAULT 0 NOT NULL,
  last_daily_claim DATE NULL,
  theme_preference VARCHAR(50) DEFAULT 'light' NOT NULL,
  posts_count INT DEFAULT 0 NOT NULL,
  comments_count INT DEFAULT 0 NOT NULL,
  reactions_given_count INT DEFAULT 0 NOT NULL,
  total_tokens_used BIGINT DEFAULT 0 NOT NULL,
  ai_generations_count INT DEFAULT 0 NOT NULL,
  last_login_at TIMESTAMP NULL,
  login_count INT DEFAULT 0 NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_active BOOLEAN DEFAULT TRUE,
  email_verified BOOLEAN DEFAULT FALSE,
  INDEX idx_username (username),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OAuth Connections Table
CREATE TABLE IF NOT EXISTS oauth_connections (
  connection_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  provider VARCHAR(50) NOT NULL,
  provider_user_id VARCHAR(255) NOT NULL,
  access_token TEXT NULL,
  refresh_token TEXT NULL,
  token_expires_at TIMESTAMP NULL,
  connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_provider_user (provider, provider_user_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Walls Table  
CREATE TABLE IF NOT EXISTS walls (
  wall_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  wall_slug VARCHAR(100) UNIQUE NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  cover_image_url VARCHAR(500) NULL,
  theme_settings JSON NULL,
  privacy_level ENUM('public', 'followers', 'private') DEFAULT 'public' NOT NULL,
  allow_comments BOOLEAN DEFAULT TRUE,
  allow_reactions BOOLEAN DEFAULT TRUE,
  allow_reposts BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts Table
CREATE TABLE IF NOT EXISTS posts (
  post_id INT AUTO_INCREMENT PRIMARY KEY,
  wall_id INT NOT NULL,
  author_id INT NOT NULL,
  post_type ENUM('text', 'media', 'location', 'ai_app', 'mixed') NOT NULL,
  content_text TEXT NULL,
  content_html TEXT NULL,
  is_repost BOOLEAN DEFAULT FALSE,
  original_post_id INT NULL,
  repost_commentary TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_deleted BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (wall_id) REFERENCES walls(wall_id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (original_post_id) REFERENCES posts(post_id) ON DELETE SET NULL,
  INDEX idx_wall_created (wall_id, created_at DESC),
  FULLTEXT INDEX idx_content (content_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Media Attachments Table
CREATE TABLE IF NOT EXISTS media_attachments (
  media_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  media_type ENUM('image', 'video') NOT NULL,
  file_url VARCHAR(500) NOT NULL,
  thumbnail_url VARCHAR(500) NULL,
  file_size INT NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  width INT NULL,
  height INT NULL,
  duration INT NULL,
  display_order INT DEFAULT 0,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Locations Table
CREATE TABLE IF NOT EXISTS locations (
  location_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  location_name VARCHAR(255) NULL,
  location_description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Applications Table
CREATE TABLE IF NOT EXISTS ai_applications (
  app_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  job_id VARCHAR(255) UNIQUE NULL,
  user_prompt TEXT NOT NULL,
  html_content TEXT NULL,
  css_content TEXT NULL,
  js_content TEXT NULL,
  preview_image_url VARCHAR(500) NULL,
  generation_model VARCHAR(100) NULL,
  generation_time INT NULL,
  status ENUM('queued', 'processing', 'completed', 'failed') DEFAULT 'queued' NOT NULL,
  queue_position INT NULL,
  error_message TEXT NULL,
  original_app_id INT NULL,
  remix_type ENUM('original', 'remix', 'fork') DEFAULT 'original' NOT NULL,
  allow_remixing BOOLEAN DEFAULT TRUE,
  remix_count INT DEFAULT 0 NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (original_app_id) REFERENCES ai_applications(app_id) ON DELETE SET NULL,
  FULLTEXT INDEX idx_prompt (user_prompt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prompt Templates Table
CREATE TABLE IF NOT EXISTS prompt_templates (
  template_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  prompt_text TEXT NOT NULL,
  category VARCHAR(50) NULL,
  is_public BOOLEAN DEFAULT TRUE,
  use_count INT DEFAULT 0 NOT NULL,
  rating_average DECIMAL(3,2) DEFAULT 0.00,
  rating_count INT DEFAULT 0,
  tags JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FULLTEXT INDEX idx_template (title, prompt_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template Ratings Table
CREATE TABLE IF NOT EXISTS template_ratings (
  rating_id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  user_id INT NOT NULL,
  rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  review_text TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_template (template_id, user_id),
  FOREIGN KEY (template_id) REFERENCES prompt_templates(template_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- App Collections Table
CREATE TABLE IF NOT EXISTS app_collections (
  collection_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  is_public BOOLEAN DEFAULT TRUE,
  follower_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collection Items Table
CREATE TABLE IF NOT EXISTS collection_items (
  item_id INT AUTO_INCREMENT PRIMARY KEY,
  collection_id INT NOT NULL,
  app_id INT NOT NULL,
  display_order INT DEFAULT 0,
  notes TEXT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_collection_app (collection_id, app_id),
  FOREIGN KEY (collection_id) REFERENCES app_collections(collection_id) ON DELETE CASCADE,
  FOREIGN KEY (app_id) REFERENCES ai_applications(app_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Generation Jobs Table
CREATE TABLE IF NOT EXISTS ai_generation_jobs (
  job_id VARCHAR(255) PRIMARY KEY,
  app_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('queued', 'processing', 'completed', 'failed') DEFAULT 'queued' NOT NULL,
  priority INT DEFAULT 0,
  attempts INT DEFAULT 0,
  max_attempts INT DEFAULT 3,
  progress_percentage INT DEFAULT 0,
  estimated_bricks_cost INT NULL,
  actual_bricks_cost INT NULL,
  prompt_tokens INT NULL,
  completion_tokens INT NULL,
  total_tokens INT NULL,
  estimated_wait_time INT NULL,
  started_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  failed_at TIMESTAMP NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (app_id) REFERENCES ai_applications(app_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactions Table
CREATE TABLE IF NOT EXISTS reactions (
  reaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  target_type ENUM('post', 'comment') NOT NULL,
  target_id INT NOT NULL,
  reaction_type VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_reaction (user_id, target_type, target_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments Table
CREATE TABLE IF NOT EXISTS comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  author_id INT NOT NULL,
  parent_comment_id INT NULL,
  content_text TEXT NOT NULL,
  content_html TEXT NULL,
  reply_count INT DEFAULT 0 NOT NULL,
  reaction_count INT DEFAULT 0 NOT NULL,
  like_count INT DEFAULT 0 NOT NULL,
  dislike_count INT DEFAULT 0 NOT NULL,
  depth_level INT DEFAULT 0 NOT NULL,
  is_hidden BOOLEAN DEFAULT FALSE,
  is_edited BOOLEAN DEFAULT FALSE,
  is_deleted BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
  INDEX idx_post_created (post_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscriptions Table
CREATE TABLE IF NOT EXISTS subscriptions (
  subscription_id INT AUTO_INCREMENT PRIMARY KEY,
  subscriber_id INT NOT NULL,
  wall_id INT NOT NULL,
  notification_level ENUM('all', 'mentions', 'none') DEFAULT 'all' NOT NULL,
  is_muted BOOLEAN DEFAULT FALSE,
  subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_subscription (subscriber_id, wall_id),
  FOREIGN KEY (subscriber_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (wall_id) REFERENCES walls(wall_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Friendships Table
CREATE TABLE IF NOT EXISTS friendships (
  friendship_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id_1 INT NOT NULL,
  user_id_2 INT NOT NULL,
  status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending' NOT NULL,
  initiated_by INT NOT NULL,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  accepted_at TIMESTAMP NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_friendship (user_id_1, user_id_2),
  FOREIGN KEY (user_id_1) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id_2) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (initiated_by) REFERENCES users(user_id) ON DELETE CASCADE,
  CHECK (user_id_1 < user_id_2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  notification_type ENUM('new_follower', 'friend_request', 'friend_accepted', 'new_post', 'new_comment', 'mention', 'reaction') NOT NULL,
  actor_id INT NULL,
  target_type VARCHAR(50) NULL,
  target_id INT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (actor_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversations Table
CREATE TABLE IF NOT EXISTS conversations (
  conversation_id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_type ENUM('direct', 'group') NOT NULL,
  group_name VARCHAR(100) NULL,
  group_description TEXT NULL,
  group_avatar_url VARCHAR(500) NULL,
  creator_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_deleted BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (creator_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversation Participants Table
CREATE TABLE IF NOT EXISTS conversation_participants (
  participant_id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('admin', 'member', 'read_only') DEFAULT 'member' NOT NULL,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  left_at TIMESTAMP NULL,
  last_read_at TIMESTAMP NULL,
  is_archived BOOLEAN DEFAULT FALSE,
  is_muted BOOLEAN DEFAULT FALSE,
  is_pinned BOOLEAN DEFAULT FALSE,
  UNIQUE KEY unique_participant (conversation_id, user_id),
  FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages Table
CREATE TABLE IF NOT EXISTS messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  message_text TEXT NULL,
  message_type ENUM('text', 'media', 'shared_post', 'system') DEFAULT 'text' NOT NULL,
  reply_to_message_id INT NULL,
  shared_post_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL,
  is_edited BOOLEAN DEFAULT FALSE,
  is_deleted BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (reply_to_message_id) REFERENCES messages(message_id) ON DELETE SET NULL,
  FOREIGN KEY (shared_post_id) REFERENCES posts(post_id) ON DELETE SET NULL,
  INDEX idx_conversation_created (conversation_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message Media Table
CREATE TABLE IF NOT EXISTS message_media (
  media_id INT AUTO_INCREMENT PRIMARY KEY,
  message_id INT NOT NULL,
  media_type ENUM('image', 'video') NOT NULL,
  file_url VARCHAR(500) NOT NULL,
  thumbnail_url VARCHAR(500) NULL,
  file_size INT NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  width INT NULL,
  height INT NULL,
  duration INT NULL,
  display_order INT DEFAULT 0,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message Read Status Table
CREATE TABLE IF NOT EXISTS message_read_status (
  read_status_id INT AUTO_INCREMENT PRIMARY KEY,
  message_id INT NOT NULL,
  user_id INT NOT NULL,
  read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_message_user (message_id, user_id),
  FOREIGN KEY (message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table
CREATE TABLE IF NOT EXISTS sessions (
  session_id VARCHAR(255) PRIMARY KEY,
  user_id INT NOT NULL,
  session_token VARCHAR(255) UNIQUE NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bricks Transactions Table
CREATE TABLE IF NOT EXISTS bricks_transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  transaction_type ENUM('earned', 'spent', 'purchased', 'refunded', 'bonus') NOT NULL,
  amount INT NOT NULL,
  balance_after INT NOT NULL,
  source VARCHAR(100) NOT NULL,
  description TEXT NULL,
  job_id VARCHAR(255) NULL,
  payment_id VARCHAR(255) NULL,
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES ai_generation_jobs(job_id) ON DELETE SET NULL,
  INDEX idx_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Social Links Table
CREATE TABLE IF NOT EXISTS user_social_links (
  link_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  link_type VARCHAR(50) NOT NULL,
  link_url VARCHAR(1000) NOT NULL,
  link_label VARCHAR(100) NOT NULL,
  icon_url VARCHAR(500) NULL,
  display_order INT DEFAULT 0 NOT NULL,
  is_visible BOOLEAN DEFAULT TRUE,
  is_verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Activity Log Table
CREATE TABLE IF NOT EXISTS user_activity_log (
  activity_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  activity_type ENUM('post_created', 'comment_created', 'reaction_given', 'repost_created', 'ai_generated', 'profile_updated', 'friend_added', 'follower_gained', 'wall_subscribed') NOT NULL,
  target_type VARCHAR(50) NULL,
  target_id INT NULL,
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Default admin user
INSERT IGNORE INTO users (user_id, username, email, password_hash, display_name, bricks_balance, theme_preference, is_active, email_verified) VALUES (1, 'admin', 'admin@wall.cyka.lol', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 10000, 'dark', TRUE, TRUE);

-- Admin wall
INSERT IGNORE INTO walls (wall_id, user_id, wall_slug, display_name, description, privacy_level) VALUES (1, 1, 'admin', 'Admin Wall', 'Official system administrator wall', 'public');

-- Schema complete: 28 tables created
