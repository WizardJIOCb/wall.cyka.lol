-- Migration 007: Update existing walls to have proper default values
-- This migration ensures all existing walls have values for the new boolean columns

UPDATE walls 
SET allow_comments = TRUE 
WHERE allow_comments IS NULL;

UPDATE walls 
SET allow_reactions = TRUE 
WHERE allow_reactions IS NULL;

UPDATE walls 
SET allow_reposts = TRUE 
WHERE allow_reposts IS NULL;

-- Add a message to indicate completion
SELECT 'Wall defaults updated successfully' as migration_result;