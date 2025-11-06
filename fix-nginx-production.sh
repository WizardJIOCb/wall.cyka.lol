#!/bin/bash
# Fix Nginx crashing on production server
# Run this on the Ubuntu server: bash fix-nginx-production.sh

cd /var/www/wall.cyka.lol

# Disable production.conf (it uses Unix socket instead of Docker network)
mv nginx/conf.d/production.conf nginx/conf.d/production.conf.disabled 2>/dev/null || true

# Verify only default.conf is active
echo "Active Nginx configs:"
ls -la nginx/conf.d/*.conf

# Restart Nginx
docker-compose restart nginx

# Wait for services to stabilize
sleep 5

# Check status
echo -e "\n=== Service Status ==="
docker-compose ps

# Check Nginx logs
echo -e "\n=== Recent Nginx Logs ==="
docker logs wall_nginx --tail 20

echo -e "\n=== Testing Application ==="
curl -s http://localhost/health || echo "Health check failed"
