-- Add open_count column to posts table
ALTER TABLE posts ADD COLUMN open_count INT DEFAULT 0 NOT NULL AFTER view_count;

-- Add index for performance
ALTER TABLE posts ADD INDEX idx_open_count (open_count);