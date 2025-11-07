# Database Migration Fix for User Registration Issue

## Problem
The user registration was failing with the error:
"Failed to create user: SQLSTATE[HY000]: General error: 1364 Field 'name' doesn't have a default value"

## Root Causes Identified
1. The `name` column in the users table didn't have a default value
2. Configuration mismatch between database names in different files
3. Outdated schema.sql file that didn't reflect the current database structure

## Fixes Applied

### 1. Database Migration (018_fix_name_column_default.sql)
- Ensured the `name` column exists in the users table
- Set a default value of empty string ('') for the `name` column
- Added an index on the `name` column
- Populated existing records with values from `display_name` where needed

### 2. Configuration Fixes
- Updated config/config.php to use the correct database name: `wall_social_platform`
- Updated schema.sql to reflect the current database structure with the `name` column

### 3. Debugging Enhancements
- Created a debug version of User.php with detailed logging
- Created test scripts to verify database connection and User::create functionality

## How to Apply the Fixes

1. Apply the new migration:
   ```bash
   cd /var/www/wall.cyka.lol
   php database/apply_fix_name_migration.php
   ```

2. Restart the PHP service to ensure configuration changes take effect:
   ```bash
   docker-compose restart php
   ```

3. Test the registration endpoint again:
   ```bash
   curl -X POST https://wall.cyka.lol/api/v1/auth/register \
     -H "Content-Type: application/json" \
     -d '{"username":"testuser","email":"test@example.com","password":"password123","password_confirm":"password123"}'
   ```

## Verification Steps

1. Check that the `name` column exists with the correct properties:
   ```bash
   docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "DESCRIBE users;"
   ```

2. Run the test scripts to verify the database connection and User::create method:
   ```bash
   docker-compose exec php php /var/www/html/test_db_connection.php
   docker-compose exec php php /var/www/html/test_debug_user.php
   ```

This should resolve the registration issue and allow new users to be created successfully.