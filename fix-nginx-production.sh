#!/bin/bash
# Fix Nginx and Queue Worker issues on production server
# Run this on the Ubuntu server: bash fix-nginx-production.sh

cd /var/www/wall.cyka.lol

echo "=== Fixing Wall Social Platform Configuration ==="
echo ""

# Step 1: Disable production.conf (it uses Unix socket instead of Docker network)
echo "[1/5] Disabling production.conf..."
mv nginx/conf.d/production.conf nginx/conf.d/production.conf.disabled 2>/dev/null || true
echo "✓ Only default.conf is now active"

# Step 2: Update environment file to ensure proper DB settings
echo ""
echo "[2/5] Checking environment configuration..."
if [ -f .env ]; then
    echo "✓ .env file exists"
    grep "DB_HOST" .env || echo "⚠ DB_HOST not set in .env"
else
    echo "⚠ .env file not found - using defaults from docker-compose.yml"
fi

# Step 3: Restart all services
echo ""
echo "[3/5] Restarting Docker services..."
docker-compose restart

# Step 4: Wait for services to stabilize
echo ""
echo "[4/5] Waiting for services to start (15 seconds)..."
sleep 15

# Step 5: Check status
echo ""
echo "[5/5] Checking service status..."
docker-compose ps

echo ""
echo "=== Checking Logs ==="
echo ""

echo "--- Nginx (last 10 lines) ---"
docker logs wall_nginx --tail 10 2>&1

echo ""
echo "--- Queue Worker (last 10 lines) ---"
docker logs wall_queue_worker --tail 10 2>&1

echo ""
echo "--- PHP-FPM (last 10 lines) ---"
docker logs wall_php --tail 10 2>&1

echo ""
echo "=== Testing Application ==="
curl -s http://localhost:80/health | head -20 || echo "⚠ Health check failed"

echo ""
echo "=== Deployment Complete ==="
echo "Access your site at: http://wall.cyka.lol or https://wall.cyka.lol"
echo ""
