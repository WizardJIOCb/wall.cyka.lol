@echo off
REM Final script to check and apply open_count column when Docker is running

echo Checking if open_count column exists in posts table...

REM Check if open_count column exists
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SHOW COLUMNS FROM posts LIKE 'open_count';" > %TEMP%\open_count_check.txt 2>&1

findstr /C:"open_count" %TEMP%\open_count_check.txt >nul
if %errorlevel% == 0 (
    echo ✓ open_count column already exists in posts table
) else (
    echo ✗ open_count column does not exist. Adding it now...
    
    REM Apply the open_count migration
    docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "ALTER TABLE posts ADD COLUMN open_count INT DEFAULT 0 NOT NULL AFTER view_count;"
    
    REM Add index for performance
    docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "ALTER TABLE posts ADD INDEX idx_open_count (open_count);"
    
    echo ✓ Added open_count column and index to posts table
    
    REM Verify it was added
    docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SHOW COLUMNS FROM posts LIKE 'open_count';"
)

REM Clean up
del %TEMP%\open_count_check.txt

echo Open count migration check and apply complete!
pause