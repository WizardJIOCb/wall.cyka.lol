-- Migration 008: Add missing columns to walls table
-- This migration ensures all walls have the proper column structure

-- Add cover_image_url column if it doesn't exist
ALTER TABLE walls ADD COLUMN IF NOT EXISTS cover_image_url VARCHAR(500) NULL AFTER description;

-- Add theme_settings column if it doesn't exist
ALTER TABLE walls ADD COLUMN IF NOT EXISTS theme_settings JSON NULL AFTER cover_image_url;

-- Add posts_count column if it doesn't exist
ALTER TABLE walls ADD COLUMN IF NOT EXISTS posts_count INT DEFAULT 0 NOT NULL AFTER allow_reposts;

-- Add subscribers_count column if it doesn't exist
ALTER TABLE walls ADD COLUMN IF NOT EXISTS subscribers_count INT DEFAULT 0 NOT NULL AFTER posts_count;

-- Update existing walls to have default values for new columns
UPDATE walls 
SET cover_image_url = COALESCE(cover_image_url, NULL),
    theme_settings = COALESCE(theme_settings, NULL),
    posts_count = COALESCE(posts_count, 0),
    subscribers_count = COALESCE(subscribers_count, 0)
WHERE wall_id > 0;

-- Add a message to indicate completion
SELECT 'Wall columns updated successfully' as migration_result;