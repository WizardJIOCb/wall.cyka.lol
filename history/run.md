# Wall Social Platform - Run Instructions

## Quick Reference

### Start Application
```bash
cd C:\Projects\wall.cyka.lol
docker-compose up -d
cd frontend
npm run dev
```

### Stop Application
```bash
cd C:\Projects\wall.cyka.lol
cd frontend
# Press Ctrl+C in frontend terminal
docker-compose down
```

### Restart Application
```bash
cd C:\Projects\wall.cyka.lol
docker-compose restart
cd frontend
# Press Ctrl+C, then npm run dev
```

---

## Detailed Instructions

### Prerequisites

Before running the application, ensure you have:
- Docker Desktop installed and running
- Node.js 18+ installed
- npm or yarn package manager
- Git (for version control)

### First-Time Setup

#### 1. Install Frontend Dependencies
```bash
cd C:\Projects\wall.cyka.lol\frontend
npm install
```

#### 2. Configure Environment Variables

**Frontend (.env.development)**
```
VITE_API_URL=http://localhost:8080/api/v1
```

**Backend (.env)**
Create if not exists at project root:
```
DB_HOST=mysql
DB_PORT=3306
DB_NAME=wall_social_platform
DB_USER=wall_user
DB_PASSWORD=wall_secure_password_123

REDIS_HOST=redis
REDIS_PORT=6379

OLLAMA_HOST=http://ollama:11434

SESSION_SECRET=your_session_secret_here
JWT_SECRET=your_jwt_secret_here
```

#### 3. Initialize Database
```bash
cd C:\Projects\wall.cyka.lol
docker-compose up -d mysql

# Wait 30 seconds for MySQL to be ready, then:
docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/schema.sql
```

---

## Starting the Application

### Backend Services (Docker)

Start all backend services:
```bash
cd C:\Projects\wall.cyka.lol
docker-compose up -d
```

This starts:
- **Nginx** (port 8080) - Web server and reverse proxy
- **PHP-FPM** (port 9000) - PHP application server
- **MySQL** (port 3306) - Database
- **Redis** (port 6379) - Cache and job queue
- **Ollama** (port 11434) - AI model service

Verify services are running:
```bash
docker-compose ps
```

Expected output:
```
NAME                          STATUS
wall.cyka.lol-mysql-1         Up
wall.cyka.lol-nginx-1         Up
wall.cyka.lol-ollama-1        Up
wall.cyka.lol-php-1           Up
wall.cyka.lol-redis-1         Up
```

### Frontend Development Server

In a new terminal:
```bash
cd C:\Projects\wall.cyka.lol\frontend
npm run dev
```

Frontend will be available at: **http://localhost:3000**

Backend API will be available at: **http://localhost:8080/api/v1**

### AI Generation Worker

To enable AI generation processing with **real-time progress tracking**, you have two options:

**Option 1: Run worker in foreground (for debugging):**
```bash
cd C:\Projects\wall.cyka.lol
docker-compose exec php php workers/ai_generation_worker.php
```
Keep this terminal open while processing AI generation jobs. You'll see **real-time token counts** and **generation progress**. Press Ctrl+C to stop.

**Option 2: Run worker as a service (automatic with docker-compose):**

The worker runs automatically as the `wall_queue_worker` container when you start the application:
```bash
docker-compose up -d
```

The worker now supports:
- **Real-time streaming** from Ollama API
- **Live token count updates** (updates every 0.5 seconds)
- **Tokens per second** speed tracking
- **Elapsed time** tracking in milliseconds
- **Estimated time remaining** calculation
- **Progress percentage** (0-100%)

**Check worker status:**
```bash
docker logs wall_queue_worker --tail 50 -f
```

**Restart worker to pick up code changes:**
```bash
docker restart wall_queue_worker
```

---

## Stopping the Application

### Stop Frontend
In the terminal running `npm run dev`:
- Press **Ctrl+C** to stop the Vite development server

### Stop Backend Services
```bash
cd C:\Projects\wall.cyka.lol
docker-compose down
```

To stop services but keep containers:
```bash
docker-compose stop
```

To stop and remove all containers, networks, and volumes:
```bash
docker-compose down -v
```

---

## Restarting the Application

### Restart All Services
```bash
cd C:\Projects\wall.cyka.lol
docker-compose restart
```

### Restart Individual Service
```bash
docker-compose restart nginx
docker-compose restart php
docker-compose restart mysql
docker-compose restart redis
docker-compose restart ollama
```

### Restart Frontend
In frontend terminal:
- Press **Ctrl+C**
- Run `npm run dev` again

### Full Restart (Clean)
```bash
cd C:\Projects\wall.cyka.lol

# Stop everything
docker-compose down

# Start everything fresh
docker-compose up -d

# In new terminal, restart frontend
cd frontend
npm run dev
```

---

## Service Health Checks

### Check Docker Services
```bash
docker-compose ps
```

### Check Backend Health
```bash
curl http://localhost:8080/health
```

Expected response:
```json
{
  "status": "healthy",
  "database": "connected",
  "redis": "connected",
  "ollama": "connected"
}
```

### Check Frontend
Open browser: **http://localhost:3000**

### Check API
```bash
curl http://localhost:8080/api/v1
```

### Check Database Connection
```bash
docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 -e "SHOW DATABASES;"
```

### Check Redis
```bash
docker-compose exec redis redis-cli PING
```

Expected response: `PONG`

### Check Ollama
```bash
curl http://localhost:11434/api/tags
```

Should list available models (e.g., deepseek-coder)

---

## Viewing Logs

### All Services Logs
```bash
docker-compose logs -f
```

### Specific Service Logs
```bash
docker-compose logs -f nginx
docker-compose logs -f php
docker-compose logs -f mysql
docker-compose logs -f redis
docker-compose logs -f ollama
```

### Frontend Logs
Frontend logs appear in the terminal running `npm run dev`

### Application Logs
Backend logs are in:
- `storage/logs/app.log`
- `storage/logs/error.log`

---

## Troubleshooting

### Services Won't Start

**Check Docker Desktop is running:**
- Open Docker Desktop application
- Ensure it's running and not in error state

**Check port conflicts:**
```bash
netstat -ano | findstr :8080
netstat -ano | findstr :3000
netstat -ano | findstr :3306
```

**Recreate containers:**
```bash
docker-compose down -v
docker-compose up -d --force-recreate
```

### Database Connection Errors

**Verify MySQL is ready:**
```bash
docker-compose exec mysql mysqladmin ping -u wall_user -pwall_secure_password_123
```

**Reimport schema:**
```bash
docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < database/schema.sql
```

### Frontend Won't Connect to Backend

**Check API URL in .env.development:**
```
VITE_API_URL=http://localhost:8080/api/v1
```

**Verify Nginx is running:**
```bash
curl http://localhost:8080/health
```

**Check CORS settings** in `nginx/conf.d/default.conf`

### Redis Connection Issues

**Flush Redis cache:**
```bash
docker-compose exec redis redis-cli FLUSHALL
```

**Restart Redis:**
```bash
docker-compose restart redis
```

### Ollama Not Responding

**Check Ollama status:**
```bash
curl http://localhost:11434/api/tags
```

**Pull model again:**
```bash
docker-compose exec ollama ollama pull deepseek-coder
```

**Restart Ollama:**
```bash
docker-compose restart ollama
```

### Frontend Build Errors

**Clear node_modules and reinstall:**
```bash
cd C:\Projects\wall.cyka.lol\frontend
Remove-Item -Recurse -Force node_modules
Remove-Item -Force package-lock.json
npm install
```

**Clear Vite cache:**
```bash
Remove-Item -Recurse -Force .vite
npm run dev
```

**Clear browser cache (full refresh):**
- Press **Ctrl+Shift+Delete** in browser
- Or press **Ctrl+F5** for hard refresh
- Or open DevTools (F12) → Network tab → Check "Disable cache"

### PHP Code Changes Not Taking Effect

**Problem:** Code changes in PHP files don't appear after modification.

**Solution:** Restart PHP container to clear opcache:
```powershell
cd C:\Projects\wall.cyka.lol
docker-compose restart php
```

**Alternative:** Disable opcache in development (edit `docker/php/php.ini`):
```ini
opcache.enable=0
```

---

## Development Workflow

### Daily Development Start
```bash
# Terminal 1: Backend
cd C:\Projects\wall.cyka.lol
docker-compose up -d

# Terminal 2: Frontend
cd C:\Projects\wall.cyka.lol\frontend
npm run dev

# Terminal 3: Worker (if testing AI features)
cd C:\Projects\wall.cyka.lol
docker-compose exec php php workers/ai_generation_worker.php
```

### Daily Development Stop
```bash
# Terminal 2: Stop frontend (Ctrl+C)
# Terminal 3: Stop worker (Ctrl+C)

# Terminal 1: Stop backend
cd C:\Projects\wall.cyka.lol
docker-compose down
```

---

## Production Build

### Build Frontend for Production
```bash
cd C:\Projects\wall.cyka.lol\frontend
npm run build
```

This outputs to `C:\Projects\wall.cyka.lol\public`

### Access Production Build
After building, access via Nginx at: **http://localhost:8080**

---

## Useful Commands

### Database Management

**Access MySQL shell:**
```bash
docker-compose exec mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform
```

**Backup database:**
```bash
docker-compose exec mysql mysqldump -u wall_user -pwall_secure_password_123 wall_social_platform > backup.sql
```

**Restore database:**
```bash
docker-compose exec -T mysql mysql -u wall_user -pwall_secure_password_123 wall_social_platform < backup.sql
```

**Run migrations:**
```bash
docker-compose exec php php database/run_migrations.php
```

**Important**: Migrations must be run to create required tables like `user_preferences`. Without this, settings will not save.

### Redis Management

**Access Redis CLI:**
```bash
docker-compose exec redis redis-cli
```

**View queue:**
```bash
docker-compose exec redis redis-cli LRANGE ai_generation_queue 0 -1
```

**Monitor Redis:**
```bash
docker-compose exec redis redis-cli MONITOR
```

### PHP/Composer

**Install PHP dependencies:**
```bash
docker-compose exec php composer install
```

**Update dependencies:**
```bash
docker-compose exec php composer update
```

**Run PHP script:**
```bash
docker-compose exec php php path/to/script.php
```

### Testing

**Run backend tests (when implemented):**
```bash
docker-compose exec php vendor/bin/phpunit
```

**Run frontend tests:**
```bash
cd C:\Projects\wall.cyka.lol\frontend
npm run test:unit
npm run test:e2e
```

---

## Environment URLs

| Service | URL | Purpose |
|---------|-----|---------|
| Frontend Dev | http://localhost:3000 | Vue development server with HMR |
| Backend API | http://localhost:8080/api/v1 | REST API endpoints |
| Production App | http://localhost:8080 | Nginx serving built frontend |
| MySQL | localhost:3306 | Database connection |
| Redis | localhost:6379 | Cache and queue |
| Ollama | http://localhost:11434 | AI model API |

---

## Status Indicators

### ✅ Everything Running Correctly
- Docker shows 5 services "Up"
- http://localhost:8080/health returns healthy
- http://localhost:3000 shows frontend app
- No error logs in `docker-compose logs`

### ⚠️ Partial Issues
- Some services up, some down (check logs)
- Frontend shows but API calls fail (check Nginx/PHP)
- Database connection errors (check MySQL status)

### ❌ Critical Errors
- Docker services won't start (check Docker Desktop)
- Port conflicts (check netstat)
- Frontend build errors (check npm install)

---

---

## Production Deployment (Docker)

### Deploy on Ubuntu Server with Docker Compose

**Server**: wall.cyka.lol (62.109.9.134)

#### Prerequisites
- Ubuntu Server 20.04+ or Debian 11+
- Docker and Docker Compose installed
- Domain DNS configured (wall.cyka.lol → 62.109.9.134)
- Ports 80, 443 open in firewall

#### Deployment Steps

**1. Install Docker and Docker Compose**
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo apt update
sudo apt install docker-compose -y
```

**2. Clone/Upload Project to Server**
```bash
cd /var/www
git clone <repository-url> wall.cyka.lol
# OR upload via SCP/SFTP

cd /var/www/wall.cyka.lol
```

**3. Configure Environment**
```bash
# Copy and edit .env file
cp .env.example .env
nano .env
```

Key settings for production:
```env
DB_HOST=mysql
DB_PORT=3306
DB_NAME=wall_social_platform
DB_USER=wall_user
DB_PASSWORD=<strong-password-here>

REDIS_HOST=redis
REDIS_PORT=6379

OLLAMA_HOST=ollama
OLLAMA_PORT=11434
```

**4. Build Frontend for Production**
```bash
cd frontend
npm install
npm run build
cd ..
```

**5. Configure Nginx for Docker**
```bash
# Ensure only default.conf is active (for Docker network)
mv nginx/conf.d/production.conf nginx/conf.d/production.conf.disabled 2>/dev/null || true

# Verify default.conf uses 'fastcgi_pass php:9000'
grep "fastcgi_pass" nginx/conf.d/default.conf
```

**6. Update docker-compose.yml ports for production**

Edit `docker-compose.yml` to expose standard HTTP/HTTPS ports:
```yaml
nginx:
  ports:
    - "80:80"      # HTTP
    - "443:443"    # HTTPS
```

**7. Start All Services**
```bash
# Start in detached mode
docker-compose up -d

# Check services are running
docker-compose ps

# Check logs for any errors
docker-compose logs -f
```

**8. Initialize Database**
```bash
# Wait for MySQL to be ready (30 seconds)
sleep 30

# Import schema
docker-compose exec -T mysql mysql -u wall_user -p<password> wall_social_platform < database/schema.sql

# Run migrations
docker-compose exec php php database/run_migrations.php
```

**9. Set Up SSL with Let's Encrypt (Optional)**

For HTTPS, you can either:

**Option A: Use Nginx on host** (recommended for SSL)
```bash
# Install certbot on host
sudo apt install certbot python3-certbot-nginx -y

# Get certificate
sudo certbot --nginx -d wall.cyka.lol

# Configure host Nginx to proxy to Docker
sudo nano /etc/nginx/sites-available/wall.cyka.lol
```

Host Nginx config:
```nginx
server {
    listen 443 ssl http2;
    server_name wall.cyka.lol;
    
    ssl_certificate /etc/letsencrypt/live/wall.cyka.lol/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wall.cyka.lol/privkey.pem;
    
    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

**Option B: Use Docker Nginx with certbot volumes** (mount certificates into container)

#### Common Production Issues & Fixes

**Issue 1: Redis Extension Not Found**

**Error**:
```
Fatal error: Uncaught Error: Class "Redis" not found
OR
"Redis PHP extension is not installed. Please rebuild the Docker image"
```

**Root Cause**: Docker images built before Redis extension was added to Dockerfile, or build was cancelled before completion.

**Complete Fix (Run on server - takes 5-10 minutes):**
```bash
cd /var/www/wall.cyka.lol

# Stop all services
docker-compose down

# Rebuild PHP images with Redis (DO NOT CANCEL - wait 5-10 min)
docker-compose build --no-cache php queue_worker

# Start services
docker-compose up -d

# Wait for initialization
sleep 40

# Verify Redis is installed
docker-compose exec php php -m | grep redis
# Expected output: redis

# Check all services are running
docker-compose ps
# All should show "Up" status
```

**CRITICAL**: The build command downloads base images, compiles extensions, and configures PHP. If you press Ctrl+C during the build, Redis extension will NOT be installed. You must let it complete - you'll see "Successfully tagged wallcykalol-php:latest" when done.

**Quick temporary fix** (lost on container restart):
```bash
docker-compose exec php bash -c "pecl install redis && docker-php-ext-enable redis"
docker-compose restart php queue_worker
```

**Issue 2: Nginx Keeps Restarting**

**Cause**: Conflicting Nginx configurations (production.conf uses Unix socket instead of Docker network)

**Fix**:
```bash
cd /var/www/wall.cyka.lol

# Disable production.conf
mv nginx/conf.d/production.conf nginx/conf.d/production.conf.disabled

# Restart Nginx
docker-compose restart nginx

# Verify it's stable
docker-compose ps
```

**Issue 2: Queue Worker Restarting**

**Cause**: Missing Composer autoloader or PCNTL extension issues

**Fix**: The worker has been updated to:
- Use manual autoloader (same as api.php)
- Make PCNTL signals optional
- Load .env variables properly

```bash
# Check worker logs
docker logs wall_queue_worker --tail 50

# Restart worker
docker-compose restart queue_worker
```

**Issue 3: Database Connection Failed**

**Cause**: .env not loaded or wrong DB_HOST

**Fix**:
```bash
# Verify DB_HOST=mysql in .env (for Docker network)
grep DB_HOST /var/www/wall.cyka.lol/.env

# Test MySQL connectivity from PHP container
docker exec -it wall_php sh
getent hosts mysql  # Should show IP like 172.19.0.3
```

**Issue 4: Frontend Shows 404 or Blank Page**

**Cause**: Frontend not built or wrong Nginx config

**Fix**:
```bash
# Rebuild frontend
cd /var/www/wall.cyka.lol/frontend
npm run build

# Verify files in public/
ls -la ../public/index.html
ls -la ../public/assets/

# Restart Nginx
docker-compose restart nginx
```

#### Automated Fix Script

Use the provided fix script:
```bash
cd /var/www/wall.cyka.lol
bash fix-nginx-production.sh
```

This script:
- Disables production.conf
- Checks environment configuration
- Restarts all services
- Validates service status
- Shows logs for debugging
- Tests health endpoint

#### Production Monitoring

**Check Service Status**:
```bash
docker-compose ps
```

Expected:
```
NAME                STATUS
wall_mysql          Up (healthy)
wall_nginx          Up
wall_ollama         Up
wall_php            Up
wall_queue_worker   Up
wall_redis          Up (healthy)
```

**Check Application Health**:
```bash
curl http://localhost/health
```

**View Logs**:
```bash
# All services
docker-compose logs -f

# Specific service
docker logs wall_nginx -f
docker logs wall_php -f
docker logs wall_queue_worker -f
```

**Restart Services**:
```bash
# All services
docker-compose restart

# Single service
docker-compose restart nginx
docker-compose restart php
```

**Update Application**:
```bash
cd /var/www/wall.cyka.lol
git pull
cd frontend && npm run build && cd ..
docker-compose restart php nginx
```

#### Production URLs
- Main Application: `http://wall.cyka.lol` or `https://wall.cyka.lol` (with SSL)
- API Endpoint: `http://wall.cyka.lol/api/v1`
- Health Check: `http://wall.cyka.lol/health`

---

## Production Deployment (Non-Docker)

For deploying without Docker to a production server, see:

**Documentation**: `documentation/PRODUCTION_DEPLOYMENT.md`

---

**Last Updated:** 2025-11-04  
**Project:** Wall Social Platform  
**Version:** 1.0.0
