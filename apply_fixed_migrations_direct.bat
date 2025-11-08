@echo off
REM Script to apply only the fixed migrations directly through Docker

echo Applying fixed migrations directly through Docker...

REM Apply migration 014 (fixed)
echo Applying 014_fix_search_columns_final.sql...
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/014_fix_search_columns_final.sql

REM Apply migration 015 (fixed)
echo Applying 015_add_remaining_search_indexes.sql...
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/015_add_remaining_search_indexes.sql

REM Apply migration 018 (fixed)
echo Applying 018_fix_name_column_default.sql...
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/018_fix_name_column_default.sql

REM Apply migration 021 (fixed)
echo Applying 021_add_reaction_counts_to_posts_and_comments.sql...
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/021_add_reaction_counts_to_posts_and_comments.sql

REM Apply migration 022 (should be fine)
echo Applying 022_add_open_count_to_posts.sql...
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/migrations/022_add_open_count_to_posts.sql

echo Fixed migrations applied successfully!
pause