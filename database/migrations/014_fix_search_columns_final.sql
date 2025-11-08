-- Migration 014: Final fix for search columns and indexes
-- Directly add missing columns and FULLTEXT indexes for search functionality

-- ============================================================================
-- 1. Add missing columns to posts table
-- ============================================================================

-- Add title column to posts table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'title'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN title VARCHAR(255) NULL AFTER post_id',
    'SELECT \'Column title already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add visibility column to posts table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'visibility'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN visibility ENUM(\'public\', \'unlisted\', \'private\') DEFAULT \'public\' NOT NULL AFTER content_html',
    'SELECT \'Column visibility already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add reaction_count column to posts table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'reaction_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER visibility',
    'SELECT \'Column reaction_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add comment_count column to posts table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'comment_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN comment_count INT DEFAULT 0 NOT NULL AFTER reaction_count',
    'SELECT \'Column comment_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add share_count column to posts table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'share_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN share_count INT DEFAULT 0 NOT NULL AFTER comment_count',
    'SELECT \'Column share_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add view_count column to posts table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'view_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER share_count',
    'SELECT \'Column view_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 2. Add missing columns to walls table
-- ============================================================================

-- Add name column to walls table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'walls' 
    AND COLUMN_NAME = 'name'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE walls ADD COLUMN name VARCHAR(100) NULL AFTER user_id',
    'SELECT \'Column name already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add subscribers_count column to walls table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'walls' 
    AND COLUMN_NAME = 'subscribers_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE walls ADD COLUMN subscribers_count INT DEFAULT 0 NOT NULL AFTER posts_count',
    'SELECT \'Column subscribers_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing walls to copy display_name to name if name is null
UPDATE walls SET name = display_name WHERE name IS NULL;

-- Make name column NOT NULL after update
ALTER TABLE walls MODIFY COLUMN name VARCHAR(100) NOT NULL;

-- ============================================================================
-- 3. Add missing columns to ai_applications table
-- ============================================================================

-- Add title column to ai_applications table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ai_applications' 
    AND COLUMN_NAME = 'title'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE ai_applications ADD COLUMN title VARCHAR(255) NULL AFTER user_id',
    'SELECT \'Column title already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add description column to ai_applications table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ai_applications' 
    AND COLUMN_NAME = 'description'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE ai_applications ADD COLUMN description TEXT NULL AFTER title',
    'SELECT \'Column description already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add tags column to ai_applications table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ai_applications' 
    AND COLUMN_NAME = 'tags'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE ai_applications ADD COLUMN tags TEXT NULL AFTER description',
    'SELECT \'Column tags already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add view_count column to ai_applications table (handle if exists)
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ai_applications' 
    AND COLUMN_NAME = 'view_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE ai_applications ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER fork_count',
    'SELECT \'Column view_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing ai_applications to set title from user_prompt if title is null
UPDATE ai_applications SET title = LEFT(COALESCE(user_prompt, 'Untitled'), 255) WHERE title IS NULL;

-- Update existing ai_applications to set description from user_prompt if description is null
UPDATE ai_applications SET description = LEFT(COALESCE(user_prompt, ''), 500) WHERE description IS NULL;

-- ============================================================================
-- 4. Add FULLTEXT indexes for search functionality
-- ============================================================================

-- Posts table FULLTEXT index on title and content_text
-- First check if the index exists
SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND INDEX_NAME = 'idx_posts_search'
);

-- Drop the index if it exists
SET @sql = IF(
    @indexExists > 0,
    'ALTER TABLE posts DROP INDEX idx_posts_search',
    'SELECT \'Index idx_posts_search does not exist\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the FULLTEXT index
ALTER TABLE posts ADD FULLTEXT INDEX idx_posts_search (title, content_text);

-- Walls table FULLTEXT index on name and description
-- First check if the index exists
SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'walls' 
    AND INDEX_NAME = 'idx_walls_search'
);

-- Drop the index if it exists
SET @sql = IF(
    @indexExists > 0,
    'ALTER TABLE walls DROP INDEX idx_walls_search',
    'SELECT \'Index idx_walls_search does not exist\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the FULLTEXT index
ALTER TABLE walls ADD FULLTEXT INDEX idx_walls_search (name, description);

-- AI Applications table FULLTEXT index on title, description, and tags
-- First check if the index exists
SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ai_applications' 
    AND INDEX_NAME = 'idx_ai_apps_search'
);

-- Drop the index if it exists
SET @sql = IF(
    @indexExists > 0,
    'ALTER TABLE ai_applications DROP INDEX idx_ai_apps_search',
    'SELECT \'Index idx_ai_apps_search does not exist\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the FULLTEXT index
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