# Database Structure Fix Scripts

This directory contains scripts to check and fix database structure issues for the Wall Social Platform.

## Scripts

1. `check_database_structure.php` - Checks the current database structure and identifies missing columns
2. `fix_database_structure.php` - Adds missing columns to fix database structure issues
3. `fix_db_docker.bat` - Windows batch script to run the fix script inside Docker container

## How to Use

### For Docker Environments (Recommended)

Run the Windows batch script:
```
fix_db_docker.bat
```

Or manually execute in Docker:
```bash
docker-compose exec php php /var/www/html/check_database_structure.php
docker-compose exec php php /var/www/html/fix_database_structure.php
```

### For Direct Server Access

If you have direct access to the server with PHP and database connectivity:
```bash
php check_database_structure.php
php fix_database_structure.php
```

## What the Fix Script Does

The fix script will add the following missing columns:

### Reactions Table
- `updated_at` - TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### Posts Table
- `reaction_count` - INT DEFAULT 0 NOT NULL
- `like_count` - INT DEFAULT 0 NOT NULL
- `dislike_count` - INT DEFAULT 0 NOT NULL

### Comments Table
- `reaction_count` - INT DEFAULT 0 NOT NULL
- `like_count` - INT DEFAULT 0 NOT NULL
- `dislike_count` - INT DEFAULT 0 NOT NULL

The script also adds appropriate indexes for better query performance.

## After Running the Fix Script

1. Restart your Docker containers:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

2. Test the reactions feature again