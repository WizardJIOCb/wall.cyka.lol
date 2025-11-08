-- Add comment_count column to posts table
ALTER TABLE posts ADD COLUMN comment_count INT DEFAULT 0 NOT NULL AFTER view_count;

-- Add index for performance
ALTER TABLE posts ADD INDEX idx_comment_count (comment_count);