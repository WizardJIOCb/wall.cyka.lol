@echo off
echo Wall Social Platform - Docker Database Structure Check
echo ======================================================

echo Running database structure check...
docker-compose exec php php /var/www/html/check_database_structure.php

echo.
echo Database structure check completed!