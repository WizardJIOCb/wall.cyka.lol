-- Migration 013: Fix search columns and indexes
-- Add missing columns and proper FULLTEXT indexes for search functionality

-- ============================================================================
-- 1. Add missing columns to posts table
-- ============================================================================

-- Add title column to posts table
ALTER TABLE posts ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL AFTER post_id;

-- Add visibility column to posts table (if not exists)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS visibility ENUM('public', 'unlisted', 'private') DEFAULT 'public' NOT NULL AFTER content_html;

-- Add reaction_count column to posts table (if not exists)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS reaction_count INT DEFAULT 0 NOT NULL AFTER visibility;

-- Add comment_count column to posts table (if not exists)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS comment_count INT DEFAULT 0 NOT NULL AFTER reaction_count;

-- Add share_count column to posts table (if not exists)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS share_count INT DEFAULT 0 NOT NULL AFTER comment_count;

-- Add view_count column to posts table (if not exists)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 NOT NULL AFTER share_count;

-- ============================================================================
-- 2. Add missing columns to walls table
-- ============================================================================

-- Fix column name from wall_slug to name (if needed)
-- Note: This is a complex change, so we'll just ensure the correct columns exist
ALTER TABLE walls ADD COLUMN IF NOT EXISTS name VARCHAR(100) NULL AFTER user_id;

-- Add subscribers_count column to walls table (if not exists)
ALTER TABLE walls ADD COLUMN IF NOT EXISTS subscribers_count INT DEFAULT 0 NOT NULL AFTER posts_count;

-- Update existing walls to copy display_name to name if name is null
UPDATE walls SET name = display_name WHERE name IS NULL;

-- Make name column NOT NULL after update
ALTER TABLE walls MODIFY COLUMN name VARCHAR(100) NOT NULL;

-- ============================================================================
-- 3. Add missing columns to ai_applications table
-- ============================================================================

-- Add title column to ai_applications table
ALTER TABLE ai_applications ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL AFTER user_id;

-- Add description column to ai_applications table
ALTER TABLE ai_applications ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER title;

-- Add tags column to ai_applications table
ALTER TABLE ai_applications ADD COLUMN IF NOT EXISTS tags TEXT NULL AFTER description;

-- Add view_count column to ai_applications table (if not exists)
ALTER TABLE ai_applications ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0 NOT NULL AFTER fork_count;

-- Update existing ai_applications to set title from user_prompt if title is null
UPDATE ai_applications SET title = LEFT(user_prompt, 255) WHERE title IS NULL;

-- ============================================================================
-- 4. Add FULLTEXT indexes for search functionality (with idempotency checks)
-- ============================================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS add_index_if_not_exists(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT,
    IN p_is_fulltext BOOLEAN
)
BEGIN
    DECLARE index_count INT;
    
    SELECT COUNT(*) INTO index_count
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name = p_table
      AND index_name = p_index;
    
    IF index_count = 0 THEN
        IF p_is_fulltext THEN
            SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD FULLTEXT INDEX ', p_index, ' (', p_columns, ')');
        ELSE
            SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD INDEX ', p_index, ' (', p_columns, ')');
        END IF;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Posts table FULLTEXT index on title and content_text
CALL add_index_if_not_exists('posts', 'idx_posts_search', 'title, content_text', TRUE);

-- Walls table FULLTEXT index on name and description
CALL add_index_if_not_exists('walls', 'idx_walls_search', 'name, description', TRUE);

-- Users table FULLTEXT index (should already exist from migration 003)
CALL add_index_if_not_exists('users', 'idx_users_search', 'display_name, bio, username', TRUE);

-- AI Applications table FULLTEXT index on title, description, and tags
CALL add_index_if_not_exists('ai_applications', 'idx_ai_apps_search', 'title, description, tags', TRUE);

-- Clean up the procedure
DROP PROCEDURE IF EXISTS add_index_if_not_exists;

-- ============================================================================
-- 5. Update existing data to ensure consistency
-- ============================================================================

-- Set default titles for posts where title is NULL
UPDATE posts SET title = LEFT(content_text, 100) WHERE title IS NULL AND content_text IS NOT NULL;

-- Set default descriptions for ai_applications where description is NULL
UPDATE ai_applications SET description = LEFT(user_prompt, 500) WHERE description IS NULL AND user_prompt IS NOT NULL;

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

-- ============================================================================
-- Rollback (if needed)
-- ============================================================================

/*
-- Remove added columns from posts table
ALTER TABLE posts 
DROP COLUMN title,
DROP COLUMN visibility,
DROP COLUMN reaction_count,
DROP COLUMN comment_count,
DROP COLUMN share_count,
DROP COLUMN view_count;

-- Remove added columns from walls table
ALTER TABLE walls 
DROP COLUMN name,
DROP COLUMN subscribers_count;

-- Remove added columns from ai_applications table
ALTER TABLE ai_applications 
DROP COLUMN title,
DROP COLUMN description,
DROP COLUMN tags,
DROP COLUMN view_count;

-- Remove FULLTEXT indexes
ALTER TABLE posts DROP INDEX idx_posts_search;
ALTER TABLE walls DROP INDEX idx_walls_search;
ALTER TABLE ai_applications DROP INDEX idx_ai_apps_search;
*/

-- ============================================================================
-- Notes
-- ============================================================================

/*
Migration Notes:
- This migration adds missing columns needed for the search functionality
- It also ensures proper FULLTEXT indexes exist for all searchable content
- The migration is designed to be idempotent and safe to run multiple times
- Existing data is updated to maintain consistency

Column Changes:
- posts: Added title, visibility, reaction_count, comment_count, share_count, view_count
- walls: Added name (copied from display_name), subscribers_count
- ai_applications: Added title (from user_prompt), description (from user_prompt), tags, view_count

Performance Considerations:
- Adding FULLTEXT indexes will improve search performance but may slow down writes
- Consider running this migration during low-traffic periods
- Monitor disk space usage after migration as indexes can be large
*/