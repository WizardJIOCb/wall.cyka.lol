-- Fix name column in users table to have proper default value
-- This migration ensures the name column exists and has the correct properties

-- First, check if the name column exists
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'name'
);

-- If the column doesn't exist, add it
SET @sql = IF(
    @columnExists = 0,
    'ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT \'\' AFTER display_name',
    'SELECT \'Column already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure the column has the correct properties
ALTER TABLE users MODIFY COLUMN name VARCHAR(100) NOT NULL DEFAULT '';

-- Add index if it doesn't exist
SET @indexExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND INDEX_NAME = 'idx_name'
);

SET @sql = IF(
    @indexExists = 0,
    'ALTER TABLE users ADD INDEX idx_name (name)',
    'SELECT \'Index already exists\' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Populate name column with display_name values where name is empty
UPDATE users SET name = display_name WHERE name = '';