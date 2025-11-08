# Server Migration Fix Instructions

## Overview
This document provides step-by-step instructions for applying the fixed migrations on your server.

## Prerequisites
1. SSH access to your server
2. Docker and Docker Compose installed
3. Access to the project directory

## Step 1: Update the Codebase

First, update your codebase to get the latest changes:

```bash
cd /var/www/wall.cyka.lol
git fetch origin
git reset --hard origin/main
```

## Step 2: Verify Current Migration Status

Check which migrations have already been applied:

```bash
docker-compose exec php php /var/www/html/database/run_migrations.php
```

Note any failed migrations, particularly:
- 014_fix_search_columns_final.sql
- 015_add_remaining_search_indexes.sql
- 018_fix_name_column_default.sql
- 021_add_reaction_counts_to_posts_and_comments.sql

## Step 3: Apply the Fixed Migrations

There are two approaches to apply the fixes:

### Approach 1: Replace Migration Files and Re-run All Migrations

1. Replace the content of the four problematic migration files with the fixed versions:
   - `database/migrations/014_fix_search_columns_final.sql`
   - `database/migrations/015_add_remaining_search_indexes.sql`
   - `database/migrations/018_fix_name_column_default.sql`
   - `database/migrations/021_add_reaction_counts_to_posts_and_comments.sql`

2. Run all migrations again:
   ```bash
   docker-compose exec php php /var/www/html/database/run_migrations.php
   ```

### Approach 2: Apply Only the Fixed Migrations (Recommended)

1. Copy the fixed migration files to your server, or update them directly on the server.

2. Apply each fixed migration individually:

   ```bash
   # Apply migration 014 (fixed)
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/014_fix_search_columns_final.sql
   
   # Apply migration 015 (fixed)
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/015_add_remaining_search_indexes.sql
   
   # Apply migration 018 (fixed)
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/018_fix_name_column_default.sql
   
   # Apply migration 021 (fixed)
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/021_add_reaction_counts_to_posts_and_comments.sql
   ```

## Step 4: Verify the Fixes

Run the verification script to ensure all fixes were applied correctly:

```bash
docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < verify_migrations_fixed.sql
```

## Step 5: Test the Application

1. Test that the application functions correctly:
   - Check that posts are displayed properly
   - Verify that the open_count functionality works
   - Test search functionality
   - Verify that reaction counts are working

2. Check the logs for any errors:
   ```bash
   docker-compose logs php
   docker-compose logs mysql
   ```

## Troubleshooting

### If You Still Get "Duplicate Key Name" Errors

This is expected if the indexes already exist. The important thing is that the migrations complete without critical errors.

### If You Get "Column Already Exists" Errors

This is expected if the columns already exist. The important thing is that the migrations complete without critical errors.

### If You Get Connection Errors

1. Verify that your database credentials are correct:
   ```bash
   cat .env | grep DB_
   ```

2. Test database connection:
   ```bash
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform
   ```

### If the open_count Column Is Still Missing

1. Manually add the column:
   ```bash
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "ALTER TABLE posts ADD COLUMN open_count INT DEFAULT 0 NOT NULL AFTER view_count;"
   ```

2. Add the index:
   ```bash
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "ALTER TABLE posts ADD INDEX idx_open_count (open_count);"
   ```

## Final Verification

After applying all fixes, run the full migration script one more time to ensure everything is consistent:

```bash
docker-compose exec php php /var/www/html/database/run_migrations.php
```

You should see all migrations marked as "Success" with no errors.

## Additional Notes

1. The `open_count` column that was the primary goal of the recent work should now be properly installed.

2. All reaction count columns (`like_count`, `dislike_count`, `reaction_count`) should be properly installed in both posts and comments tables.

3. All search indexes should be properly configured for full-text search functionality.

4. The fixes are designed to be safe to run multiple times without causing issues.