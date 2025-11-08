-- Add view_count column to posts table
ALTER TABLE posts ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER is_deleted;

-- Add indexes for performance
ALTER TABLE posts ADD INDEX idx_view_count (view_count);