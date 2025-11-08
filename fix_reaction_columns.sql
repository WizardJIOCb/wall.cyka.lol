-- Add reaction count columns to posts table if they don't exist
ALTER TABLE posts ADD COLUMN IF NOT EXISTS reaction_count INT DEFAULT 0 NOT NULL AFTER comment_count;
ALTER TABLE posts ADD COLUMN IF NOT EXISTS like_count INT DEFAULT 0 NOT NULL AFTER reaction_count;
ALTER TABLE posts ADD COLUMN IF NOT EXISTS dislike_count INT DEFAULT 0 NOT NULL AFTER like_count;

-- Add reaction count columns to comments table if they don't exist
ALTER TABLE comments ADD COLUMN IF NOT EXISTS reaction_count INT DEFAULT 0 NOT NULL AFTER reply_count;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS like_count INT DEFAULT 0 NOT NULL AFTER reaction_count;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS dislike_count INT DEFAULT 0 NOT NULL AFTER like_count;