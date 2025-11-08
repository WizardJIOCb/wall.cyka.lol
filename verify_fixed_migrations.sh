#!/bin/bash
# Script to verify that the fixed migrations were applied correctly

echo "Verifying fixed migrations..."

# Check if open_count column exists in posts table
echo "Checking if open_count column exists in posts table..."
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'wall_social_platform' AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'open_count';"

# Check if like_count column exists in posts table
echo "Checking if like_count column exists in posts table..."
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'wall_social_platform' AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'like_count';"

# Check if idx_posts_search index exists
echo "Checking if idx_posts_search index exists..."
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'wall_social_platform' AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_posts_search' ORDER BY SEQ_IN_INDEX;"

# Check if idx_walls_search index exists
echo "Checking if idx_walls_search index exists..."
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = 'wall_social_platform' AND TABLE_NAME = 'walls' AND INDEX_NAME = 'idx_walls_search' ORDER BY SEQ_IN_INDEX;"

# Show all indexes on posts table
echo "Showing all indexes on posts table..."
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SHOW INDEX FROM posts;"

# Show all indexes on walls table
echo "Showing all indexes on walls table..."
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SHOW INDEX FROM walls;"

echo "Verification complete!"