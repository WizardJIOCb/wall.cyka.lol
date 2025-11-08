#!/bin/bash
# Final script to check and apply open_count column when Docker is running

echo "Checking if open_count column exists in posts table..."

# Check if open_count column exists
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SHOW COLUMNS FROM posts LIKE 'open_count';" > /tmp/open_count_check.txt 2>&1

if grep -q "open_count" /tmp/open_count_check.txt; then
    echo "✓ open_count column already exists in posts table"
else
    echo "✗ open_count column does not exist. Adding it now..."
    
    # Apply the open_count migration
    docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "ALTER TABLE posts ADD COLUMN open_count INT DEFAULT 0 NOT NULL AFTER view_count;"
    
    # Add index for performance
    docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "ALTER TABLE posts ADD INDEX idx_open_count (open_count);"
    
    echo "✓ Added open_count column and index to posts table"
    
    # Verify it was added
    docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform -e "SHOW COLUMNS FROM posts LIKE 'open_count';"
fi

# Clean up
rm -f /tmp/open_count_check.txt

echo "Open count migration check and apply complete!"