# Deployment Instructions for Wall Social Platform Fixes

This document provides step-by-step instructions for deploying the fixes for view count, comments, and reactions issues.

## Summary of Issues Fixed

1. **Property name mismatch**: Fixed `comment_count` to `comments_count` in Post model
2. **Missing explicit column selection**: Added explicit `view_count` selection in SQL queries
3. **Missing database columns**: Added migrations for `comment_count`, `reaction_count`, `like_count`, and `dislike_count` columns
4. **Database schema inconsistencies**: Created verification script to ensure all columns exist

## Files to Deploy

### Core Application Files

1. `src/Models/Post.php` - Contains fixes for property name mapping and SQL query improvements

### Database Migrations

1. `database/migrations/020_add_comment_count_to_posts.sql` - Migration to add comment_count column
2. `database/migrations/021_add_reaction_counts_to_posts_and_comments.sql` - Migration to add reaction count columns

### Verification Tools (Optional)

1. `verify_database_fixes.php` - Script to verify and apply missing database columns

## Deployment Steps

### Step 1: Backup Your Database

Before applying any changes, create a backup of your database:

```bash
mysqldump -u username -p database_name > backup_before_fixes.sql
```

### Step 2: Copy Updated Files

Copy the following files to your server:

```bash
# Copy the updated Post model
cp src/Models/Post.php /path/to/your/server/src/Models/Post.php

# Copy the new migration files
cp database/migrations/020_add_comment_count_to_posts.sql /path/to/your/server/database/migrations/
cp database/migrations/021_add_reaction_counts_to_posts_and_comments.sql /path/to/your/server/database/migrations/

# Optional: Copy the verification script
cp verify_database_fixes.php /path/to/your/server/
```

### Step 3: Apply Database Migrations

Run the new migrations to add the missing columns:

```bash
# Apply migration 020 (comment_count)
mysql -u username -p database_name < database/migrations/020_add_comment_count_to_posts.sql

# Apply migration 021 (reaction counts)
mysql -u username -p database_name < database/migrations/021_add_reaction_counts_to_posts_and_comments.sql
```

### Alternative: Use the Verification Script

Instead of manually applying migrations, you can run the verification script which will check for missing columns and apply the necessary migrations:

```bash
php verify_database_fixes.php
```

### Step 4: Restart Services

Restart your web server to ensure all changes are loaded:

```bash
# For Apache
sudo systemctl restart apache2

# For Nginx with PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php-fpm

# Or use your preferred method to restart services
```

### Step 5: Test the Fixes

Test the API endpoints to verify that the issues are resolved:

1. **View Count Testing**:
   - Visit the wall posts endpoint: `GET /api/v1/walls/9/posts?limit=20&offset=0`
   - Verify that `view_count` values are not always 0
   - View individual posts and verify that view counts increment

2. **Comments Testing**:
   - Create a new comment: `POST /api/v1/comments`
   - Retrieve post comments: `GET /api/v1/posts/{postId}/comments`
   - Verify that `comments_count` increments on posts

3. **Reactions Testing**:
   - Add a reaction: `POST /api/v1/reactions`
   - Remove a reaction: `DELETE /api/v1/reactions/post/{postId}`
   - Verify that `reaction_count`, `like_count`, and `dislike_count` update correctly

### Step 6: Monitor for Issues

After deployment, monitor your application logs for any errors:

```bash
# Check web server error logs
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log

# Check application logs if you have them
tail -f /path/to/your/app/storage/logs/app.log
```

## Troubleshooting

### If View Counts Still Show 0

1. Verify the `view_count` column exists in the posts table:
   ```sql
   DESCRIBE posts;
   ```

2. Check that the column is being selected in SQL queries by reviewing the Post model.

### If Comments Don't Work

1. Verify the `comment_count` column exists in the posts table:
   ```sql
   DESCRIBE posts;
   ```

2. Check the Comment model implementation and ensure it's properly updating the counter.

### If Reactions Don't Work

1. Verify the `reaction_count`, `like_count`, and `dislike_count` columns exist in both posts and comments tables:
   ```sql
   DESCRIBE posts;
   DESCRIBE comments;
   ```

2. Check the Reaction model implementation and ensure it's properly updating the counters.

## Rollback Procedure

If you need to rollback the changes:

1. Restore your database from the backup:
   ```bash
   mysql -u username -p database_name < backup_before_fixes.sql
   ```

2. Revert the Post.php file to the previous version if you have it.

3. Restart your services.

## Additional Notes

- The fixes should resolve all issues with view counts always showing 0
- Comments functionality should now work properly with correct counters
- Reactions (likes, dislikes, etc.) should now work with proper counters
- The verification script can be run periodically to ensure database schema consistency

For any issues or questions, please refer to the FIXES_SUMMARY.md document for detailed information about the changes made.