@echo off
echo Wall Social Platform - Docker Database Structure Fix
echo ===================================================

echo Running database structure fix...
docker-compose exec php php /var/www/html/fix_database_structure.php

echo.
echo Database structure fix completed!
echo Restarting services may be required for changes to take effect