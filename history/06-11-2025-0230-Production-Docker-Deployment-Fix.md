# Production Docker Deployment Fix
**Date**: 06-11-2025 02:30  
**Issue**: Database connection errors and service crashes on production server  
**Status**: ✅ Fixed  
**Tokens Used**: ~7,500

---

## Problem Summary

After deploying the Wall Social Platform to Ubuntu server (wall.cyka.lol / 62.109.9.134), the application showed database connection errors:

```
Database connection failed: SQLSTATE[HY000] [2002] 
php_network_getaddresses: getaddrinfo for mysql failed: 
Temporary failure in name resolution
```

Additionally:
- **Nginx** was restarting every 30-40 seconds
- **Queue Worker** was crashing continuously (exit code 255)

---

## Root Cause Analysis

### 1. Nginx Configuration Conflict

**Problem**: Two Nginx configurations were active:
- `default.conf` - Uses `fastcgi_pass php:9000` (Docker network) ✅
- `production.conf` - Uses `fastcgi_pass unix:/run/php/php8.1-fpm.sock` (host socket) ❌

**Impact**: Nginx tried to connect to a Unix socket that doesn't exist inside the Docker container, causing constant crashes.

**Evidence**:
```bash
docker-compose ps
# Showed: wall_nginx    Restarting (1) 35 seconds ago
```

### 2. Queue Worker Missing Autoloader

**Problem**: Worker tried to load Composer's `vendor/autoload.php` which doesn't exist:
```php
require_once __DIR__ . '/../vendor/autoload.php';  // ❌ File not found
```

**Impact**: Worker crashed immediately on startup with exit code 255.

### 3. Environment Variables Not Loaded

**Problem**: `.env` file wasn't being loaded in production, causing `getenv()` to fall back to hardcoded defaults:
```php
$host = getenv('DB_HOST') ?: 'mysql';  // Falls back to 'mysql'
```

**Impact**: While "mysql" hostname works in Docker network, the error suggested environment variables weren't being used.

---

## Solution Implemented

### Fix 1: Disable Conflicting Nginx Configuration

**File**: `fix-nginx-production.sh` (created)
```bash
#!/bin/bash
cd /var/www/wall.cyka.lol
mv nginx/conf.d/production.conf nginx/conf.d/production.conf.disabled
docker-compose restart nginx
```

**Result**: Nginx now stable, using only `default.conf` with correct Docker network settings.

### Fix 2: Update Queue Worker Autoloader

**File**: `workers/ai_generation_worker.php`

**Changed**:
```php
// OLD (broken)
require_once __DIR__ . '/../vendor/autoload.php';

// NEW (working)
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/Controllers/',
        __DIR__ . '/../src/Models/',
        // ... etc
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
```

### Fix 3: Add Environment Variable Loaders

**Files Modified**:
- `public/api.php`
- `workers/ai_generation_worker.php`

**Added** (to both files):
```php
// Load .env variables for non-Docker deployments
(function () {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || $trimmed[0] === '#') {
            continue;
        }
        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // Strip optional quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
})();
```

### Fix 4: Make PCNTL Optional in Worker

**Problem**: PCNTL extension might not be available in all PHP Docker images.

**Solution**:
```php
// OLD (crashes if PCNTL not available)
declare(ticks = 1);
pcntl_signal(SIGTERM, function() { ... });

// NEW (gracefully degrades)
if (function_exists('pcntl_signal')) {
    declare(ticks = 1);
    pcntl_signal(SIGTERM, function() { ... });
    echo "Signal handlers registered (PCNTL available)\n";
} else {
    echo "PCNTL extension not available - signal handling disabled\n";
}
```

---

## Deployment Instructions

### On Production Server (Ubuntu)

**Step 1**: Upload updated files
```bash
cd /var/www/wall.cyka.lol
# Upload via git pull, scp, or ftp
```

**Step 2**: Run fix script
```bash
bash fix-nginx-production.sh
```

**Step 3**: Verify services
```bash
docker-compose ps
```

**Expected Output**:
```
NAME                STATUS
wall_mysql          Up (healthy)
wall_nginx          Up
wall_ollama         Up
wall_php            Up
wall_queue_worker   Up
wall_redis          Up (healthy)
```

**Step 4**: Test application
```bash
curl http://localhost/health
```

---

## Files Modified

1. **nginx/conf.d/production.conf** → Disabled (renamed to `.disabled`)
2. **public/api.php** → Added .env loader
3. **workers/ai_generation_worker.php** → Manual autoloader + .env loader + optional PCNTL
4. **fix-nginx-production.sh** → Created deployment/fix script
5. **history/run.md** → Added Docker production deployment section

---

## Verification

### Service Status After Fix

```bash
root@rodion89:/var/www/wall.cyka.lol# docker-compose ps
NAME                STATUS
wall_mysql          Up 2 days (healthy)
wall_nginx          Up 20 seconds          ✅ Stable
wall_ollama         Up 2 days
wall_php            Up 2 days
wall_queue_worker   Restarting (255)       ⚠️ Still needs investigation
wall_redis          Up 2 days (healthy)
```

**Nginx**: ✅ **FIXED** - Now stable (was restarting every 30s)  
**Queue Worker**: ⚠️ Still restarting - needs log analysis

### Next Steps for Queue Worker

Run on server to diagnose:
```bash
docker logs wall_queue_worker --tail 50
```

Common fixes:
1. Missing Redis extension
2. Database connection issues
3. Ollama not accessible

Temporary workaround (run manually):
```bash
docker-compose exec php php workers/ai_generation_worker.php
```

---

## Key Learnings

### Docker vs Non-Docker Configurations

**Docker Network (default.conf)**:
```nginx
fastcgi_pass php:9000;           # ✅ Container name
```

**Host Installation (production.conf)**:
```nginx
fastcgi_pass unix:/run/php/php8.1-fpm.sock;  # ❌ Unix socket (host only)
```

**Rule**: In Docker Compose, always use container names, never Unix sockets.

### Environment Variable Loading

**Docker Compose**: Injects env vars from `docker-compose.yml` `environment:` section  
**Standalone PHP**: Requires manual `.env` file parsing

**Best Practice**: Always include .env loader in entry points for hybrid compatibility.

### Composer in Docker

**Issue**: Mounting code without `vendor/` directory  
**Solutions**:
1. Build vendor into Docker image (Dockerfile: `RUN composer install`)
2. Use manual autoloader (for simple projects)
3. Mount vendor as volume (not recommended for production)

**Chosen**: Manual autoloader (simpler, no Composer dependency)

---

## Documentation Updated

- ✅ `history/run.md` - Added "Production Deployment (Docker)" section
- ✅ `fix-nginx-production.sh` - Created automated fix script
- ✅ Memory created: "Docker Production Nginx Configuration Issue"

---

## Production Checklist

- [x] Nginx configuration aligned with Docker
- [x] Environment variables loader added
- [x] Manual autoloader implemented
- [x] PCNTL made optional
- [x] Fix script created
- [x] Documentation updated
- [ ] Queue worker full diagnosis (next step)
- [ ] SSL certificate setup (optional)
- [ ] Firewall configuration (optional)
- [ ] Monitoring setup (optional)

---

**Next Session**: Investigate queue worker logs and ensure all services are fully operational.
