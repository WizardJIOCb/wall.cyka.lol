# Fixed Migrations Changes

## Overview
This document summarizes the changes made to fix the problematic database migrations.

## Migration 018: Fix name column default
**File**: `database/migrations/018_fix_name_column_default.sql`

### Issues Fixed:
- SQL syntax error with NULL values in UPDATE statement

### Changes Made:
- Updated the UPDATE statement to handle both empty strings and NULL values:
  ```sql
  -- Before:
  UPDATE users SET name = display_name WHERE name = '';
  
  -- After:
  UPDATE users SET name = display_name WHERE name = '' OR name IS NULL;
  ```

## Migration 021: Add reaction counts to posts and comments
**File**: `database/migrations/021_add_reaction_counts_to_posts_and_comments.sql`

### Issues Fixed:
- Duplicate column error for `like_count`, `dislike_count`, and `reaction_count` columns

### Changes Made:
- Added checks for column existence before attempting to create them
- Used conditional SQL execution with `INFORMATION_SCHEMA.COLUMNS`
- Added checks for index existence before attempting to create them
- Used conditional SQL execution with `INFORMATION_SCHEMA.STATISTICS`

## Migration 014: Fix search columns final
**File**: `database/migrations/014_fix_search_columns_final.sql`

### Issues Fixed:
- Duplicate key name error for `idx_posts_search`, `idx_walls_search`, and `idx_ai_apps_search` indexes

### Changes Made:
- Replaced `DROP INDEX IF EXISTS` with a more compatible approach
- Added checks for index existence using `INFORMATION_SCHEMA.STATISTICS`
- Used conditional SQL execution to drop indexes only if they exist

## Migration 015: Add remaining search indexes
**File**: `database/migrations/015_add_remaining_search_indexes.sql`

### Issues Fixed:
- Duplicate key name error for `idx_walls_search` and `idx_ai_apps_search` indexes

### Changes Made:
- Applied the same fix as migration 014
- Added checks for index existence using `INFORMATION_SCHEMA.STATISTICS`
- Used conditional SQL execution to drop indexes only if they exist

## Verification Files Created

### 1. Migration Fixes Summary
**File**: `MIGRATION_FIXES_SUMMARY.md`
- Detailed explanation of all fixes made
- Instructions for applying the fixes
- Expected results after applying the fixes

### 2. Verification SQL Script
**File**: `verify_migrations_fixed.sql`
- SQL queries to verify that the fixes work correctly
- Checks for column existence
- Checks for index existence

### 3. Apply Fixed Migrations Scripts
**Files**: 
- `apply_fixed_migrations.sh` (Linux/Mac)
- `apply_fixed_migrations.bat` (Windows)

- Scripts to apply only the fixed migrations
- Can be used instead of running all migrations

## Key Technical Improvements

1. **Compatibility**: 
   - Replaced MySQL-specific `DROP INDEX IF EXISTS` with a more universally compatible approach
   - Used standard SQL with `INFORMATION_SCHEMA` to check for object existence

2. **Safety**:
   - Added conditional execution to prevent duplicate column errors
   - Added conditional execution to prevent duplicate index errors
   - Used prepared statements for dynamic SQL execution

3. **Robustness**:
   - Added proper error handling in SQL scripts
   - Used consistent patterns for checking object existence
   - Maintained backward compatibility with existing database schemas

## How to Use These Fixes

1. Replace the content of the four migration files with the updated versions
2. Run the verification script to check current state:
   ```sql
   source verify_migrations_fixed.sql
   ```
3. Apply the fixed migrations using the provided scripts:
   - On Linux/Mac: `./apply_fixed_migrations.sh`
   - On Windows: `apply_fixed_migrations.bat`
4. Verify the fixes worked correctly by running the verification script again

## Expected Results

After applying these fixes:
- Migration 018 should run without SQL syntax errors
- Migration 021 should run without duplicate column errors
- Migration 014 should run without duplicate index errors
- Migration 015 should run without duplicate index errors
- The `open_count` column should be properly installed in the posts table
- All reaction count columns should be properly installed
- All search indexes should be properly configured