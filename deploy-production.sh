#!/bin/bash
# Production Deployment Script for Wall Social Platform
# Run on Ubuntu server: bash deploy-production.sh

set -e  # Exit on error

echo "=========================================="
echo "Wall Social Platform - Production Deploy"
echo "=========================================="
echo ""

cd /var/www/wall.cyka.lol

# Step 1: Stop all services
echo "[1/7] Stopping services..."
docker-compose down
echo "✓ Services stopped"
echo ""

# Step 2: Clean up old images (optional)
echo "[2/7] Removing old images..."
docker image prune -f || true
echo "✓ Old images removed"
echo ""

# Step 3: Rebuild PHP images with Redis extension
echo "[3/7] Building PHP images with Redis extension..."
echo "⚠ This will take 5-10 minutes - DO NOT CANCEL!"
echo ""
docker-compose build --no-cache php queue_worker
echo ""
echo "✓ Images built successfully"
echo ""

# Step 4: Start all services
echo "[4/7] Starting all services..."
docker-compose up -d
echo "✓ Services started"
echo ""

# Step 5: Wait for services to initialize
echo "[5/7] Waiting for services to initialize (40 seconds)..."
for i in {40..1}; do
    echo -ne "\r  Waiting... $i seconds remaining  "
    sleep 1
done
echo -e "\r✓ Services initialized                    "
echo ""

# Step 6: Verify Redis extension
echo "[6/7] Verifying Redis extension..."
if docker-compose exec -T php php -m | grep -q redis; then
    echo "✓ Redis extension installed successfully"
else
    echo "✗ Redis extension NOT found - rebuild may have failed"
    echo ""
    echo "Checking PHP modules:"
    docker-compose exec -T php php -m
    exit 1
fi
echo ""

# Step 7: Check service status
echo "[7/7] Checking service status..."
docker-compose ps
echo ""

# Final verification
echo "=========================================="
echo "Testing Application..."
echo "=========================================="

# Test health endpoint
echo "Health check (internal):"
if curl -s http://localhost:8080/health | grep -q "healthy"; then
    echo "✓ Health check passed"
else
    echo "⚠ Health check failed - checking response:"
    curl -s http://localhost:8080/health || true
fi
echo ""

# Test main page
echo "Main page status:"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/)
if [ "$HTTP_CODE" = "200" ]; then
    echo "✓ Main page accessible (HTTP $HTTP_CODE)"
else
    echo "⚠ Main page returned HTTP $HTTP_CODE"
fi
echo ""

echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Application URLs:"
echo "  - Internal: http://localhost:8080"
echo "  - External: http://wall.cyka.lol"
echo ""
echo "Service Status:"
docker-compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"
echo ""
echo "Next steps:"
echo "  1. Test registration: https://wall.cyka.lol/api/v1/auth/register"
echo "  2. Monitor logs: docker-compose logs -f php"
echo "  3. Check Nginx logs: docker-compose logs -f nginx"
echo ""
