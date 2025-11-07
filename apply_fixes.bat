@echo off
echo Applying database migration fix for name column...
php database/apply_fix_name_migration.php

echo Restarting PHP service...
docker-compose restart php

echo Fixes applied successfully! You can now test the registration endpoint.
pause