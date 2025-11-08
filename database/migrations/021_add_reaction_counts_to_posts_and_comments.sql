-- Add reaction count columns to posts table
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'reaction_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER comment_count',
    'SELECT \'Column reaction_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'like_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN like_count INT DEFAULT 0 NOT NULL AFTER reaction_count',
    'SELECT \'Column like_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND COLUMN_NAME = 'dislike_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE posts ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL AFTER like_count',
    'SELECT \'Column dislike_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for performance
SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND INDEX_NAME = 'idx_reaction_count'
);

SET @sql = IF(
    @indexExists = 0,
    'ALTER TABLE posts ADD INDEX idx_reaction_count (reaction_count)',
    'SELECT \'Index idx_reaction_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND INDEX_NAME = 'idx_like_count'
);

SET @sql = IF(
    @indexExists = 0,
    'ALTER TABLE posts ADD INDEX idx_like_count (like_count)',
    'SELECT \'Index idx_like_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'posts' 
    AND INDEX_NAME = 'idx_dislike_count'
);

SET @sql = IF(
    @indexExists = 0,
    'ALTER TABLE posts ADD INDEX idx_dislike_count (dislike_count)',
    'SELECT \'Index idx_dislike_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add reaction count columns to comments table
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'comments' 
    AND COLUMN_NAME = 'reaction_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE comments ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER reply_count',
    'SELECT \'Column reaction_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'comments' 
    AND COLUMN_NAME = 'like_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE comments ADD COLUMN like_count INT DEFAULT 0 NOT NULL AFTER reaction_count',
    'SELECT \'Column like_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'comments' 
    AND COLUMN_NAME = 'dislike_count'
);

SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE comments ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL AFTER like_count',
    'SELECT \'Column dislike_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for performance
SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'comments' 
    AND INDEX_NAME = 'idx_comment_reaction_count'
);

SET @sql = IF(
    @indexExists = 0,
    'ALTER TABLE comments ADD INDEX idx_comment_reaction_count (reaction_count)',
    'SELECT \'Index idx_comment_reaction_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'comments' 
    AND INDEX_NAME = 'idx_comment_like_count'
);

SET @sql = IF(
    @indexExists = 0,
    'ALTER TABLE comments ADD INDEX idx_comment_like_count (like_count)',
    'SELECT \'Index idx_comment_like_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'comments' 
    AND INDEX_NAME = 'idx_comment_dislike_count'
);

SET @sql = IF(
    @indexExists = 0,
    'ALTER TABLE comments ADD INDEX idx_comment_dislike_count (dislike_count)',
    'SELECT \'Index idx_comment_dislike_count already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;