-- Add reaction count columns to posts table
ALTER TABLE posts ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER comment_count;
ALTER TABLE posts ADD COLUMN like_count INT DEFAULT 0 NOT NULL AFTER reaction_count;
ALTER TABLE posts ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL AFTER like_count;

-- Add indexes for performance
ALTER TABLE posts ADD INDEX idx_reaction_count (reaction_count);
ALTER TABLE posts ADD INDEX idx_like_count (like_count);
ALTER TABLE posts ADD INDEX idx_dislike_count (dislike_count);

-- Add reaction count columns to comments table
ALTER TABLE comments ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER reply_count;
ALTER TABLE comments ADD COLUMN like_count INT DEFAULT 0 NOT NULL AFTER reaction_count;
ALTER TABLE comments ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL AFTER like_count;

-- Add indexes for performance
ALTER TABLE comments ADD INDEX idx_comment_reaction_count (reaction_count);
ALTER TABLE comments ADD INDEX idx_comment_like_count (like_count);
ALTER TABLE comments ADD INDEX idx_comment_dislike_count (dislike_count);