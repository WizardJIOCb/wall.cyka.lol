-- Migration 014: Final fix for search columns and indexes
-- Directly add missing columns and FULLTEXT indexes for search functionality

-- ============================================================================
-- 1. Add missing columns to posts table
-- ============================================================================

-- Add title column to posts table (handle if exists)
ALTER TABLE posts ADD COLUMN title VARCHAR(255) NULL AFTER post_id;

-- Add visibility column to posts table (handle if exists)
ALTER TABLE posts ADD COLUMN visibility ENUM('public', 'unlisted', 'private') DEFAULT 'public' NOT NULL AFTER content_html;

-- Add reaction_count column to posts table (handle if exists)
ALTER TABLE posts ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER visibility;

-- Add comment_count column to posts table (handle if exists)
ALTER TABLE posts ADD COLUMN comment_count INT DEFAULT 0 NOT NULL AFTER reaction_count;

-- Add share_count column to posts table (handle if exists)
ALTER TABLE posts ADD COLUMN share_count INT DEFAULT 0 NOT NULL AFTER comment_count;

-- Add view_count column to posts table (handle if exists)
ALTER TABLE posts ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER share_count;

-- ============================================================================
-- 2. Add missing columns to walls table
-- ============================================================================

-- Add name column to walls table (handle if exists)
ALTER TABLE walls ADD COLUMN name VARCHAR(100) NULL AFTER user_id;

-- Add subscribers_count column to walls table (handle if exists)
ALTER TABLE walls ADD COLUMN subscribers_count INT DEFAULT 0 NOT NULL AFTER posts_count;

-- Update existing walls to copy display_name to name if name is null
UPDATE walls SET name = display_name WHERE name IS NULL;

-- Make name column NOT NULL after update
ALTER TABLE walls MODIFY COLUMN name VARCHAR(100) NOT NULL;

-- ============================================================================
-- 3. Add missing columns to ai_applications table
-- ============================================================================

-- Add title column to ai_applications table (handle if exists)
ALTER TABLE ai_applications ADD COLUMN title VARCHAR(255) NULL AFTER user_id;

-- Add description column to ai_applications table (handle if exists)
ALTER TABLE ai_applications ADD COLUMN description TEXT NULL AFTER title;

-- Add tags column to ai_applications table (handle if exists)
ALTER TABLE ai_applications ADD COLUMN tags TEXT NULL AFTER description;

-- Add view_count column to ai_applications table (handle if exists)
ALTER TABLE ai_applications ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER fork_count;

-- Update existing ai_applications to set title from user_prompt if title is null
UPDATE ai_applications SET title = LEFT(COALESCE(user_prompt, 'Untitled'), 255) WHERE title IS NULL;

-- Update existing ai_applications to set description from user_prompt if description is null
UPDATE ai_applications SET description = LEFT(COALESCE(user_prompt, ''), 500) WHERE description IS NULL;

-- ============================================================================
-- 4. Add FULLTEXT indexes for search functionality
-- ============================================================================

-- Posts table FULLTEXT index on title and content_text
-- First drop the index if it exists to avoid conflicts
ALTER TABLE posts DROP INDEX IF EXISTS idx_posts_search;
ALTER TABLE posts ADD FULLTEXT INDEX idx_posts_search (title, content_text);

-- Walls table FULLTEXT index on name and description
ALTER TABLE walls DROP INDEX IF EXISTS idx_walls_search;
ALTER TABLE walls ADD FULLTEXT INDEX idx_walls_search (name, description);

-- AI Applications table FULLTEXT index on title, description, and tags
ALTER TABLE ai_applications DROP INDEX IF EXISTS idx_ai_apps_search;
ALTER TABLE ai_applications ADD FULLTEXT INDEX idx_ai_apps_search (title, description, tags);

-- ============================================================================
-- 5. Update existing data to ensure consistency
-- ============================================================================

-- Set default titles for posts where title is NULL
UPDATE posts SET title = LEFT(COALESCE(content_text, 'Untitled'), 100) WHERE title IS NULL;

-- ============================================================================
-- Verification Queries (commented out - these should be run manually if needed)
-- ============================================================================

-- Verify columns were added
-- DESCRIBE posts;
-- DESCRIBE walls;
-- DESCRIBE ai_applications;

-- Verify FULLTEXT indexes were created
-- SHOW INDEX FROM posts WHERE Key_name LIKE 'idx_posts_search';
-- SHOW INDEX FROM walls WHERE Key_name LIKE 'idx_walls_search';
-- SHOW INDEX FROM ai_applications WHERE Key_name LIKE 'idx_ai_apps_search';

-- Test FULLTEXT search
-- SELECT * FROM posts WHERE MATCH(title, content_text) AGAINST('test' IN NATURAL LANGUAGE MODE) LIMIT 5;
-- SELECT * FROM walls WHERE MATCH(name, description) AGAINST('test' IN NATURAL LANGUAGE MODE) LIMIT 5;
-- SELECT * FROM ai_applications WHERE MATCH(title, description, tags) AGAINST('test' IN NATURAL LANGUAGE MODE) LIMIT 5;