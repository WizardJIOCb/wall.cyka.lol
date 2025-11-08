# Fixes for View Count, Comments, and Reactions Issues

## Issues Identified

1. **Property name mismatch in Post model**: The backend was returning `comment_count` but the frontend expected `comments_count`.

2. **Missing explicit view_count selection**: In several SQL queries in the Post model, the view_count column wasn't being explicitly selected.

3. **Database schema inconsistency**: The view_count, comment_count, and reaction count columns were added in migrations but may not have been applied to the actual database.

4. **View count increment logic**: The view count is only incremented when viewing a single post, not when fetching lists of posts.

5. **Missing comment_count column**: The posts table was missing the comment_count column that the Comment model was trying to update.

6. **Missing reaction count columns**: The posts and comments tables were missing the reaction_count, like_count, and dislike_count columns that the Reaction model was trying to update.

## Fixes Applied

### 1. Fixed Property Name Mismatch in Post.php

**File**: `src/Models/Post.php`
**Line**: 246
**Change**: Fixed the property name mapping from `comment_count` to `comments_count` to match frontend expectations.

```php
// Before
'comments_count' => (int)($post['comment_count'] ?? 0),

// After
'comments_count' => (int)($post['comments_count'] ?? 0),
```

### 2. Added Explicit view_count Selection in SQL Queries

**File**: `src/Models/Post.php`
**Methods**: `getWallPosts`, `getUserPosts`, `getFeedPosts`
**Changes**: Added explicit `p.view_count` selection in SELECT statements.

```php
// In getWallPosts method
$sql = "SELECT p.*, p.view_count, u.username, u.display_name as author_name, u.avatar_url as author_avatar,
        ai.status as ai_status, ai.app_id, ai.job_id, ai.queue_position, ai.user_prompt,
        ai.html_content, ai.css_content, ai.js_content, ai.generation_model,
        ai.generation_time,
        job.actual_bricks_cost,
        job.prompt_tokens as input_tokens,
        job.completion_tokens as output_tokens,
        job.total_tokens,
        job.tokens_per_second,
        job.elapsed_time
        FROM posts p
        JOIN users u ON p.author_id = u.user_id
        LEFT JOIN ai_applications ai ON p.post_id = ai.post_id
        LEFT JOIN ai_generation_jobs job ON ai.job_id = job.job_id
        WHERE p.wall_id = ? AND p.is_deleted = FALSE
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

// Similar changes in getUserPosts and getFeedPosts methods
```

### 3. Added Migration for comment_count Column

**File**: `database/migrations/020_add_comment_count_to_posts.sql`
**Purpose**: Added the missing comment_count column to the posts table.

```sql
-- Add comment_count column to posts table
ALTER TABLE posts ADD COLUMN comment_count INT DEFAULT 0 NOT NULL AFTER view_count;

-- Add index for performance
ALTER TABLE posts ADD INDEX idx_comment_count (comment_count);
```

### 4. Added Migration for Reaction Count Columns

**File**: `database/migrations/021_add_reaction_counts_to_posts_and_comments.sql`
**Purpose**: Added the missing reaction_count, like_count, and dislike_count columns to the posts and comments tables.

```sql
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
```

### 5. Database Verification Script

**File**: `verify_database_fixes.php`
**Purpose**: A script that can be run on the server to verify if all the required columns exist and apply the migrations if needed.

## Additional Recommendations

1. **Apply Migrations**: Run the database migrations to ensure all required columns exist in the posts and comments tables:
   - Migration 019: Add view_count column
   - Migration 020: Add comment_count column
   - Migration 021: Add reaction count columns

2. **Increment View Count for Lists**: Consider implementing view count increments when users view lists of posts, not just individual posts.

3. **Test Reactions and Comments**: Verify that the reactions and comments functionality is working properly by testing the API endpoints:
   - POST /api/v1/reactions (add reaction)
   - DELETE /api/v1/reactions/{reactableType}/{reactableId} (remove reaction)
   - POST /api/v1/comments (create comment)
   - GET /api/v1/posts/{postId}/comments (get post comments)

## Files Modified

1. `src/Models/Post.php` - Fixed property name mapping and added explicit view_count selection
2. `database/migrations/020_add_comment_count_to_posts.sql` - Added migration for comment_count column
3. `database/migrations/021_add_reaction_counts_to_posts_and_comments.sql` - Added migration for reaction count columns
4. `verify_database_fixes.php` - Created database verification script

## How to Deploy

1. Copy the updated files to your server:
   - `src/Models/Post.php`
   - `database/migrations/020_add_comment_count_to_posts.sql`
   - `database/migrations/021_add_reaction_counts_to_posts_and_comments.sql`
   - `verify_database_fixes.php` (optional, for verification)

2. Run the database migrations:
   ```bash
   # Apply migration 019 (if not already applied)
   mysql -u username -p database_name < database/migrations/019_add_view_count_to_posts.sql
   
   # Apply migration 020
   mysql -u username -p database_name < database/migrations/020_add_comment_count_to_posts.sql
   
   # Apply migration 021
   mysql -u username -p database_name < database/migrations/021_add_reaction_counts_to_posts_and_comments.sql
   ```

3. Alternatively, run the database verification script on your server:
   ```bash
   php verify_database_fixes.php
   ```

4. Test the API endpoints to ensure view counts, comments, and reactions are working properly.