-- Verification script to check if migration fixes work correctly

-- Check if open_count column exists in posts table
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'posts' 
AND COLUMN_NAME = 'open_count';

-- Check if like_count column exists in posts table
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'posts' 
AND COLUMN_NAME = 'like_count';

-- Check if idx_posts_search index exists
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'posts' 
AND INDEX_NAME = 'idx_posts_search'
ORDER BY SEQ_IN_INDEX;

-- Check if idx_walls_search index exists
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'walls' 
AND INDEX_NAME = 'idx_walls_search'
ORDER BY SEQ_IN_INDEX;

-- Check if idx_ai_apps_search index exists
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'ai_applications' 
AND INDEX_NAME = 'idx_ai_apps_search'
ORDER BY SEQ_IN_INDEX;

-- Show all indexes on posts table
SHOW INDEX FROM posts;

-- Show all indexes on walls table
SHOW INDEX FROM walls;

-- Show all indexes on ai_applications table
SHOW INDEX FROM ai_applications;