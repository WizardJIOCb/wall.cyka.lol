-- Migration 003: Search System
-- Add FULLTEXT indexes and search logging table

-- ============================================================================
-- 1. Add FULLTEXT indexes for search functionality (with idempotency checks)
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

-- Posts table FULLTEXT index
CALL add_index_if_not_exists('posts', 'idx_posts_search', 'title, content', TRUE);

-- Walls table FULLTEXT index
CALL add_index_if_not_exists('walls', 'idx_walls_search', 'name, description', TRUE);

-- Users table FULLTEXT index
CALL add_index_if_not_exists('users', 'idx_users_search', 'display_name, bio, username', TRUE);

-- AI Applications table FULLTEXT index
CALL add_index_if_not_exists('ai_applications', 'idx_ai_apps_search', 'title, description, tags', TRUE);

-- ============================================================================
-- 2. Create search_logs table for analytics and trending searches
-- ============================================================================

CREATE TABLE IF NOT EXISTS search_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    query VARCHAR(200) NOT NULL,
    search_type VARCHAR(20) NOT NULL DEFAULT 'all', -- all, post, wall, user, ai-app
    results_count INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_query (query),
    INDEX idx_created (created_at),
    INDEX idx_type (search_type),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. Add indexes for better search performance (with idempotency checks)
-- ============================================================================

-- Posts: Additional indexes for sorting
CALL add_index_if_not_exists('posts', 'idx_posts_reaction_count', 'reaction_count', FALSE);
CALL add_index_if_not_exists('posts', 'idx_posts_comment_count', 'comment_count', FALSE);
CALL add_index_if_not_exists('posts', 'idx_posts_created', 'created_at', FALSE);

-- Walls: Additional indexes for sorting
CALL add_index_if_not_exists('walls', 'idx_walls_subscriber_count', 'subscriber_count', FALSE);
CALL add_index_if_not_exists('walls', 'idx_walls_post_count', 'post_count', FALSE);

-- Users: Additional indexes for sorting
CALL add_index_if_not_exists('users', 'idx_users_followers_count', 'followers_count', FALSE);

-- Clean up the procedure
DROP PROCEDURE IF EXISTS add_index_if_not_exists;

-- ============================================================================
-- 4. Create view for popular searches (optional optimization)
-- ============================================================================

CREATE OR REPLACE VIEW popular_searches AS
SELECT 
    query,
    COUNT(*) as search_count,
    MAX(created_at) as last_searched,
    DATE(created_at) as search_date
FROM search_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY query, DATE(created_at)
ORDER BY search_count DESC;

-- ============================================================================
-- Verification Queries (commented out - these should be run manually if needed)
-- ============================================================================

-- Verify FULLTEXT indexes were created
-- SHOW INDEX FROM posts WHERE Key_name = 'idx_posts_search';
-- SHOW INDEX FROM walls WHERE Key_name = 'idx_walls_search';
-- SHOW INDEX FROM users WHERE Key_name = 'idx_users_search';

-- Verify search_logs table was created
-- DESCRIBE search_logs;

-- Test FULLTEXT search on posts
-- SELECT * FROM posts WHERE MATCH(title, content) AGAINST('test' IN NATURAL LANGUAGE MODE);

-- Test search logs
-- INSERT INTO search_logs (query, search_type) VALUES ('test query', 'all');
-- SELECT * FROM search_logs ORDER BY created_at DESC LIMIT 10;

-- ============================================================================
-- Rollback (if needed)
-- ============================================================================

/*
-- Remove FULLTEXT indexes
ALTER TABLE posts DROP INDEX idx_posts_search;
ALTER TABLE walls DROP INDEX idx_walls_search;
ALTER TABLE users DROP INDEX idx_users_search;
ALTER TABLE ai_applications DROP INDEX idx_ai_apps_search;

-- Drop search_logs table
DROP TABLE IF EXISTS search_logs;

-- Drop view
DROP VIEW IF EXISTS popular_searches;

-- Remove additional indexes
ALTER TABLE posts 
DROP INDEX idx_posts_reaction_count,
DROP INDEX idx_posts_comment_count,
DROP INDEX idx_posts_created;

ALTER TABLE walls
DROP INDEX idx_walls_subscriber_count,
DROP INDEX idx_walls_post_count;

ALTER TABLE users
DROP INDEX idx_users_followers_count;
*/

-- ============================================================================
-- Notes
-- ============================================================================

/*
FULLTEXT Search Notes:
- Minimum word length: 4 characters (MySQL default ft_min_word_len)
- Stop words are ignored (common words like 'the', 'and', etc.)
- For 2-3 character searches, consider using LIKE fallback
- FULLTEXT indexes increase write time but dramatically improve search performance

Performance Considerations:
- FULLTEXT indexes can be large (20-30% of table size)
- Rebuilding indexes can take time on large tables
- Consider running this migration during low-traffic periods
- Monitor disk space usage after migration

Search Logs Table:
- Set up a cron job to delete old logs (>30 days) to prevent growth
- Consider partitioning by month for large-scale deployments
- results_count can be populated by application after search completes
*/