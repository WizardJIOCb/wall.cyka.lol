-- Migration 015: Add remaining search indexes
-- Add FULLTEXT indexes for walls and ai_applications tables

-- ============================================================================
-- Add FULLTEXT indexes for search functionality
-- ============================================================================

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
-- Verification Queries (commented out - these should be run manually if needed)
-- ============================================================================

-- Verify FULLTEXT indexes were created
-- SHOW INDEX FROM walls WHERE Key_name LIKE 'idx_walls_search';
-- SHOW INDEX FROM ai_applications WHERE Key_name LIKE 'idx_ai_apps_search';

-- Test FULLTEXT search
-- SELECT * FROM walls WHERE MATCH(name, description) AGAINST('test' IN NATURAL LANGUAGE MODE) LIMIT 5;
-- SELECT * FROM ai_applications WHERE MATCH(title, description, tags) AGAINST('test' IN NATURAL LANGUAGE MODE) LIMIT 5;