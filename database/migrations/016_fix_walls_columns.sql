-- Migration 016: Fix walls columns for search
-- Add missing name column and FULLTEXT index

-- ============================================================================
-- Add missing columns to walls table
-- ============================================================================

-- Add name column to walls table (handle if exists)
ALTER TABLE walls ADD COLUMN name VARCHAR(100) NULL AFTER user_id;

-- Update existing walls to copy display_name to name if name is null
UPDATE walls SET name = display_name WHERE name IS NULL;

-- Make name column NOT NULL after update
ALTER TABLE walls MODIFY COLUMN name VARCHAR(100) NOT NULL;

-- ============================================================================
-- Add FULLTEXT indexes for search functionality
-- ============================================================================

-- Walls table FULLTEXT index on name and description
ALTER TABLE walls ADD FULLTEXT INDEX idx_walls_search (name, description);

-- ============================================================================
-- Verification Queries (commented out - these should be run manually if needed)
-- ============================================================================

-- Verify columns were added
-- DESCRIBE walls;

-- Verify FULLTEXT indexes were created
-- SHOW INDEX FROM walls WHERE Key_name LIKE 'idx_walls_search';

-- Test FULLTEXT search
-- SELECT * FROM walls WHERE MATCH(name, description) AGAINST('test' IN NATURAL LANGUAGE MODE) LIMIT 5;