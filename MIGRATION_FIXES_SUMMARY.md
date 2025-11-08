# Migration Fixes Summary

We've fixed the following migration issues:

## 1. Migration 018: Fix name column default
- **Issue**: SQL syntax error with NULL values
- **Fix**: Updated the migration to properly handle NULL values in the UPDATE statement
- **Change**: `UPDATE users SET name = display_name WHERE name = ''` → `UPDATE users SET name = display_name WHERE name = '' OR name IS NULL`

## 2. Migration 021: Add reaction counts to posts and comments
- **Issue**: Duplicate column error for `like_count`
- **Fix**: Added checks to see if columns exist before trying to create them
- **Approach**: Used `INFORMATION_SCHEMA.COLUMNS` to check for column existence and conditional SQL execution

## 3. Migration 014: Fix search columns final
- **Issue**: Duplicate key name error for `idx_posts_search` and `idx_walls_search`
- **Fix**: Replaced `DROP INDEX IF EXISTS` with a more compatible approach using `INFORMATION_SCHEMA.STATISTICS` to check for index existence
- **Approach**: Used conditional SQL execution to drop indexes only if they exist

## 4. Migration 015: Add remaining search indexes
- **Issue**: Duplicate key name error for `idx_walls_search` and `idx_ai_apps_search`
- **Fix**: Applied the same fix as migration 014
- **Approach**: Used conditional SQL execution to drop indexes only if they exist

## Key Improvements Made

1. **Compatibility**: Replaced `DROP INDEX IF EXISTS` with a more compatible approach that works across different MySQL versions
2. **Safety**: Added checks for column and index existence before attempting to create them
3. **Robustness**: Used conditional SQL execution to prevent duplicate column and index errors

## How to Apply These Fixes

1. Replace the content of the following migration files with the updated versions:
   - `database/migrations/018_fix_name_column_default.sql`
   - `database/migrations/021_add_reaction_counts_to_posts_and_comments.sql`
   - `database/migrations/014_fix_search_columns_final.sql`
   - `database/migrations/015_add_remaining_search_indexes.sql`

2. Run the migrations again:
   ```bash
   docker-compose exec php php /var/www/html/database/run_migrations.php
   ```

## Expected Results

After applying these fixes, the migrations should run successfully without:
- SQL syntax errors
- Duplicate column errors
- Duplicate index errors

The `open_count` column that was the primary goal of the recent work should be properly installed, along with all other schema changes.