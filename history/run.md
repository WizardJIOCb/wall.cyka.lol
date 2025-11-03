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

## Production Deployment

For deploying to a production server (e.g., wall.cyka.lol), see the comprehensive deployment guide:

**Documentation**: `documentation/PRODUCTION_DEPLOYMENT.md`

### Production Quick Reference

**Server Requirements**:
- Ubuntu Server 20.04+ or Debian 11+
- Nginx, PHP 8.1+, MySQL 8.0+, Redis
- Domain with DNS configured
- SSL certificate (Let's Encrypt)

**Key Configuration Files**:
- `nginx/conf.d/production.conf` - Production Nginx config
- `frontend/.env.production` - Production frontend environment
- `config/database.php` - Production database config

**Deployment Steps Summary**:
1. Install required software (Nginx, PHP, MySQL, Redis, Certbot)
2. Deploy application files to `/var/www/wall.cyka.lol`
3. Set file permissions (www-data:www-data)
4. Build frontend with production environment
5. Configure database and run migrations
6. Copy and enable Nginx configuration
7. Obtain SSL certificate with Certbot
8. Start background workers as systemd services
9. Configure firewall (ports 22, 80, 443)
10. Verify and test deployment

**Production URLs**:
- Main Application: `https://wall.cyka.lol`
- API Endpoint: `https://wall.cyka.lol/api/v1`
- Health Check: `https://wall.cyka.lol/health`

**Maintenance Commands**:
```bash
# Reload Nginx
sudo systemctl reload nginx

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Check logs
sudo tail -f /var/log/nginx/wall_error.log

# Test SSL renewal
sudo certbot renew --dry-run
```

For complete production deployment instructions, refer to `documentation/PRODUCTION_DEPLOYMENT.md`.

---

**Last Updated:** 2025-11-04  
**Project:** Wall Social Platform  
**Version:** 1.0.0
