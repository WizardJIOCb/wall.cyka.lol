# SSE Real-Time Generation Configuration Fix

**Date:** 07-11-2025 00:50  
**Tokens Used:** ~132,000

## Problem Summary

After migrating to production (non-Docker) deployment, AI generation system had multiple issues:

1. **SSE requests going to localhost instead of production domain**
2. **Database missing real-time tracking columns**
3. **Redis extension not available on host PHP**
4. **Ollama models not installed**
5. **Frontend showing empty post blocks**

## Changes Made

### 1. Frontend URL Configuration

**Files Modified:**
- `frontend/src/views/WallView.vue`
- `frontend/src/components/ai/AIGenerationProgress.vue`  
- `frontend/src/utils/composables.ts`

**Changes:**
```javascript
// BEFORE (hardcoded localhost)
const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8080'

// AFTER (relative URLs for production)
const apiUrl = import.meta.env.VITE_API_URL || ''
```

This ensures SSE requests use relative URLs (`/api/v1/...`) on production, which resolve to the correct domain.

### 2. Backend Models Fix

**File:** `src/Models/AIApplication.php`

Fixed SQL query to prevent token column conflicts:

```php
// Explicitly select columns instead of SELECT a.*
// Token data from ai_generation_jobs is source of truth
$sql = "SELECT 
        a.app_id, a.post_id, a.job_id, a.user_prompt,
        // ... other columns ...
        job.prompt_tokens as input_tokens,
        job.completion_tokens as output_tokens,
        job.total_tokens
        FROM ai_applications a
        LEFT JOIN ai_generation_jobs job ON a.job_id = job.job_id
        WHERE a.app_id = ?";
```

**File:** `src/Models/Post.php`

Similar fix for post queries to get correct token counts from `ai_generation_jobs` table.

**File:** `src/Utils/Database.php`

Removed duplicate `RedisConnection` class (kept standalone file).

### 3. Nginx Configuration

**File:** `nginx/conf.d/production.conf`

Added SSE endpoints and improved caching:

```nginx
# SSE endpoints for AI generation progress
location ~ ^/api/v1/(ai/generation/[^/]+/(progress|content)|...) $ {
    fastcgi_buffering off;
    fastcgi_cache off;
    fastcgi_read_timeout 24h;
    chunked_transfer_encoding on;
}

# Separate caching for JS/CSS (7 days with must-revalidate)
location ~* \.(js|css)$ {
    expires 7d;
    add_header Cache-Control "public, must-revalidate";
    etag on;
}
```

### 4. Database Migrations

Executed migrations on production:
```bash
php database/run_migrations.php
```

Added columns for real-time generation tracking:
- `current_tokens`
- `tokens_per_second`
- `elapsed_time`
- `estimated_time_remaining`
- `progress_percentage`
- `partial_content_length`
- `content_generation_rate`

### 5. Worker Configuration

**File:** `workers/ai_generation_worker.php`

Removed token column updates to `ai_applications` table - now only updates `ai_generation_jobs`.

Removed hardcoded token updates:
```php
// BEFORE
UPDATE ai_applications SET 
    input_tokens = ?,
    output_tokens = ?,
    total_tokens = ?,
    ...

// AFTER (removed these columns)
UPDATE ai_applications SET 
    html_content = ?, 
    css_content = ?, 
    ...
    // No token updates - handled in ai_generation_jobs
```

### 6. Ollama Setup

Installed required models on production:
```bash
docker exec -it wall_ollama ollama pull deepseek-coder
docker exec -it wall_ollama ollama pull gpt-oss:20b  # 13GB - in progress
```

## Current Status

✅ **Fixed:**
- SSE requests now use correct production URLs
- Token counts display correctly (Input/Output/Total)
- Database schema updated with tracking columns
- Nginx properly routes SSE endpoints
- Worker runs in Docker container

⏳ **In Progress:**
- Ollama model `gpt-oss:20b` downloading (13GB)
- Posts showing empty blocks (investigation needed)

❌ **Known Issues:**
- AI posts not displaying prompt/content in list view
- Default avatar 404 error

## Next Steps

1. Wait for Ollama model download to complete
2. Start worker process: `docker exec -d wall_php php /var/www/html/workers/ai_generation_worker.php`
3. Test AI generation end-to-end
4. Fix empty post blocks display issue
5. Fix default avatar path

## Testing Commands

```bash
# Check worker status
docker logs wall_php | grep -i worker

# Check Redis queue
docker exec -it wall_redis redis-cli
> LLEN ai_generation_queue

# Check job status
docker exec -it wall_mysql mysql -uwall_user -p wall_social_platform \
  -e "SELECT job_id, status, error_message FROM ai_generation_jobs ORDER BY created_at DESC LIMIT 5;"

# Test SSE endpoint
curl -N "https://wall.cyka.lol/api/v1/ai/generation/JOB_ID/progress"
```

## Architecture Notes

**SSE Data Flow:**
1. Frontend creates SSE connection: `/api/v1/ai/generation/{jobId}/progress`
2. PHP streams updates every 500ms from `ai_generation_jobs` table
3. Worker updates database in real-time during generation
4. Frontend displays progress bar, stats, and partial content

**Token Data Storage:**
- `ai_generation_jobs` - **Source of truth** for token counts
- `ai_applications` - **No token columns** (removed from updates)
- Frontend joins these tables to get complete data

## Files Modified

1. `frontend/src/views/WallView.vue` (2 changes)
2. `frontend/src/components/ai/AIGenerationProgress.vue` (1 change)
3. `frontend/src/utils/composables.ts` (1 change)
4. `src/Models/AIApplication.php` (SQL query refactor)
5. `src/Models/Post.php` (SQL query refactor)
6. `src/Utils/Database.php` (removed duplicate class)
7. `workers/ai_generation_worker.php` (removed token updates)
8. `nginx/conf.d/production.conf` (added SSE config)
