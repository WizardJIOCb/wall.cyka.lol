-- Add reaction count columns to posts table if they don't exist
ALTER TABLE posts ADD COLUMN IF NOT EXISTS reaction_count INT DEFAULT 0 NOT NULL AFTER comment_count;
ALTER TABLE posts ADD COLUMN IF NOT EXISTS like_count INT DEFAULT 0 NOT NULL AFTER reaction_count;
ALTER TABLE posts ADD COLUMN IF NOT EXISTS dislike_count INT DEFAULT 0 NOT NULL AFTER like_count;

-- Add reaction count columns to comments table if they don't exist
ALTER TABLE comments ADD COLUMN IF NOT EXISTS reaction_count INT DEFAULT 0 NOT NULL AFTER reply_count;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS like_count INT DEFAULT 0 NOT NULL AFTER reaction_count;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS dislike_count INT DEFAULT 0 NOT NULL AFTER like_count;

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_reaction_count ON posts (reaction_count);
CREATE INDEX IF NOT EXISTS idx_like_count ON posts (like_count);
CREATE INDEX IF NOT EXISTS idx_dislike_count ON posts (dislike_count);
CREATE INDEX IF NOT EXISTS idx_comment_reaction_count ON comments (reaction_count);
CREATE INDEX IF NOT EXISTS idx_comment_like_count ON comments (like_count);
CREATE INDEX IF NOT EXISTS idx_comment_dislike_count ON comments (dislike_count);