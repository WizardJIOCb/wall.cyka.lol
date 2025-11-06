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
echo "[1/6] Stopping services..."
docker-compose down
echo "✓ Services stopped"
echo ""

# Step 2: Rebuild PHP images with Redis extension
echo "[2/6] Building PHP images with Redis extension..."
echo "⚠ This will take 5-10 minutes..."
docker-compose build --no-cache php queue_worker
echo "✓ Images built successfully"
echo ""

# Step 3: Start all services
echo "[3/6] Starting all services..."
docker-compose up -d
echo "✓ Services started"
echo ""

# Step 4: Wait for services to initialize
echo "[4/6] Waiting for services to initialize (30 seconds)..."
sleep 30
echo "✓ Services initialized"
echo ""

# Step 5: Verify Redis extension
echo "[5/6] Verifying Redis extension..."
if docker-compose exec -T php php -m | grep -q redis; then
    echo "✓ Redis extension installed successfully"
else
    echo "✗ Redis extension NOT found - rebuild may have failed"
    exit 1
fi
echo ""

# Step 6: Check service status
echo "[6/6] Checking service status..."
docker-compose ps
echo ""

# Final verification
echo "=========================================="
echo "Testing Application..."
echo "=========================================="

# Test health endpoint
echo "Health check:"
curl -s http://localhost:8080/health | head -5 || echo "⚠ Health check failed"
echo ""

# Test main page
echo "Main page:"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:8080/

echo ""
echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Application URLs:"
echo "  - Internal: http://localhost:8080"
echo "  - External: http://wall.cyka.lol"
echo ""
echo "Next steps:"
echo "  1. Configure host Nginx reverse proxy (if not done)"
echo "  2. Test registration at https://wall.cyka.lol"
echo "  3. Monitor logs: docker-compose logs -f"
echo ""
