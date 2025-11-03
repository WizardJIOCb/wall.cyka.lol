# Nginx Configuration Design for wall.cyka.lol Production Server

## Overview

This document outlines the configuration strategy for deploying the Wall Social Platform on a production server with Nginx as the web server. The application will be served from `/var/www/wall.cyka.lol` and accessible via the domain `wall.cyka.lol`.

## Architecture Context

The Wall Social Platform is a hybrid application combining:
- **Frontend**: Vue.js 3 Single Page Application (SPA)
- **Backend**: PHP-based REST API with FastCGI processing
- **Real-time Features**: Server-Sent Events (SSE) for AI generation progress
- **Static Assets**: Images, fonts, stylesheets, and JavaScript bundles
- **User Uploads**: Media files and AI-generated applications

## Server Environment

### Directory Structure

```
/var/www/wall.cyka.lol/
├── public/                      # Web root (document root)
│   ├── index.html              # Vue SPA entry point
│   ├── api.php                 # PHP backend entry point
│   ├── assets/                 # Compiled frontend assets
│   ├── uploads/                # User-uploaded media
│   └── ai-apps/                # AI-generated applications
├── src/                        # PHP backend source code
├── config/                     # Application configuration
├── database/                   # Database schemas and migrations
└── workers/                    # Background workers
```

### Key Requirements

1. **Document Root**: `/var/www/wall.cyka.lol/public`
2. **Domain**: `wall.cyka.lol`
3. **PHP Processing**: FastCGI via PHP-FPM
4. **SPA Routing**: All non-API routes serve `index.html` for client-side routing
5. **API Routing**: Requests to `/api/*` route to `api.php`
6. **Security**: Headers, file access restrictions, and sandboxing for user-generated content

## Nginx Configuration Strategy

### Server Block Configuration

#### Basic Server Settings

| Parameter | Value | Rationale |
|-----------|-------|-----------|
| Listen Port | 80 (HTTP), 443 (HTTPS) | Standard web ports; HTTPS required for production |
| Server Name | wall.cyka.lol | Primary domain for the application |
| Document Root | /var/www/wall.cyka.lol/public | Public-facing directory containing compiled assets |
| Default Index Files | index.html, api.php | Prioritize Vue SPA, fallback to API |

#### PHP-FPM Integration

The application requires PHP 8.1+ with FastCGI Process Manager (PHP-FPM) for backend processing.

**Connection Method**:
- **Socket**: Unix socket (`unix:/run/php/php8.1-fpm.sock`) for better performance on same-server deployment
- **TCP**: `127.0.0.1:9000` as alternative if socket unavailable

**PHP Script Processing**:
- All `.php` files processed through FastCGI
- Script filename resolved via document root + request URI
- Increased timeouts for AI generation endpoints (300 seconds)

### Routing Logic

#### Frontend SPA Routes

**Pattern**: All requests except API and static assets  
**Behavior**: Serve `/var/www/wall.cyka.lol/public/index.html`  
**Implementation Strategy**: `try_files $uri $uri/ /index.html`

This ensures Vue Router handles client-side navigation for routes like:
- `/profile/[username]`
- `/wall/[wall_id]`
- `/conversations`
- `/ai-apps`

#### API Routes

**Pattern**: `/api/*`  
**Behavior**: Route to `api.php` with query string preservation  
**Implementation Strategy**: `try_files $uri $uri/ /api.php?$query_string`

Examples:
- `/api/v1/auth/login` → `api.php?$query_string`
- `/api/v1/posts/123` → `api.php?$query_string`

#### Real-time SSE Endpoints

**Pattern**: Specific endpoints for Server-Sent Events  
**Endpoints**:
- `/api/v1/ai/jobs/[job_id]/status`
- `/api/v1/queue/live`
- `/api/v1/conversations/[id]/live`

**Special Requirements**:
- Proxy buffering disabled
- Long-lived connections (24-hour timeout)
- Chunked transfer encoding enabled
- Connection upgrade header handling

### Security Configuration

#### HTTP Security Headers

| Header | Value | Purpose |
|--------|-------|---------|
| X-Frame-Options | SAMEORIGIN | Prevent clickjacking attacks |
| X-Content-Type-Options | nosniff | Prevent MIME type sniffing |
| X-XSS-Protection | 1; mode=block | Enable browser XSS protection |
| Referrer-Policy | strict-origin-when-cross-origin | Control referrer information |
| Content-Security-Policy | (see below) | Restrict resource loading |

**Content Security Policy**:
- Default source: `'self'` (same-origin only)
- Scripts: `'self' 'unsafe-inline' 'unsafe-eval'` (required for Vue.js)
- Styles: `'self' 'unsafe-inline'` (required for dynamic styles)
- Images: `'self' data: https:` (allow external images)
- Connections: `'self'` (API requests to same origin)

#### AI-Generated Content Sandboxing

User-generated AI applications require strict isolation:

**Sandbox Policy for `/ai-apps/` directory**:
- Allow scripts, forms, modals, and popups
- Restrict access to parent frame
- Apply strict CSP to prevent malicious code execution

#### File Access Restrictions

**Denied Patterns**:
- Hidden files: `/.*` (e.g., `.env`, `.git`)
- Configuration files: `.env`, `.htaccess`, `.htpasswd`
- Source code directories outside `/public`

### Performance Optimization

#### Gzip Compression

**Enabled for**:
- Text files: plain, CSS, XML, JavaScript
- Application data: JSON, RSS/XML feeds
- Fonts: TrueType, OpenType, WOFF
- Images: SVG

**Settings**:
- Minimum file size: 1024 bytes
- Vary header: Enabled for proper caching

#### Static Asset Caching

**Long-term Caching (30 days)**:
- Images: JPG, PNG, GIF, ICO, SVG
- Fonts: WOFF, WOFF2, TTF, EOT
- Stylesheets and Scripts: CSS, JS

**Cache Headers**:
- `Cache-Control: public, immutable`
- `Expires: 30d`
- Access logs disabled for performance

#### Upload Directory Caching

**Path**: `/uploads/`  
**Cache Duration**: 7 days  
**Headers**: `Cache-Control: public`

### File Upload Configuration

**Maximum Client Body Size**: 50 MB  
**Rationale**: Support media uploads (images, videos) while preventing abuse

### SSL/TLS Configuration (Production)

#### Certificate Requirements

**Recommended**: Let's Encrypt free SSL certificate via Certbot

**Certificate Paths** (typical):
- Certificate: `/etc/letsencrypt/live/wall.cyka.lol/fullchain.pem`
- Private Key: `/etc/letsencrypt/live/wall.cyka.lol/privkey.pem`

#### HTTPS Settings

| Setting | Value | Purpose |
|---------|-------|---------|
| Listen Port | 443 ssl http2 | HTTPS with HTTP/2 support |
| SSL Protocols | TLSv1.2 TLSv1.3 | Modern, secure protocols only |
| SSL Ciphers | HIGH:!aNULL:!MD5 | Strong encryption ciphers |
| SSL Session Cache | shared:SSL:10m | Improve SSL handshake performance |
| SSL Session Timeout | 10m | Balance security and performance |

#### HTTP to HTTPS Redirect

All HTTP traffic on port 80 should redirect to HTTPS:
- Redirect status: 301 (permanent)
- Preserve request URI and query string

### Logging Configuration

#### Access Logs

**Path**: `/var/log/nginx/wall_access.log`  
**Format**: Combined (default)  
**Purpose**: Track all HTTP requests for analytics and debugging

**Exceptions** (logs disabled):
- Static assets (performance)
- Health check endpoint (noise reduction)

#### Error Logs

**Path**: `/var/log/nginx/wall_error.log`  
**Level**: warn (production), info (debugging)  
**Purpose**: Capture server errors, PHP errors, and configuration issues

### Monitoring and Health Checks

#### Application Health Endpoint

**Path**: `/health`  
**Response**: `200 OK` with "healthy" text  
**Purpose**: External monitoring and load balancer health checks

#### Nginx Status Endpoint

**Path**: `/nginx_status`  
**Access**: Restricted to `127.0.0.1` only  
**Purpose**: Internal monitoring of Nginx performance metrics

### Error Page Handling

#### Client Errors (404)

**Strategy**: Serve Vue SPA (`/index.html`)  
**Rationale**: Allow Vue Router to handle "not found" pages with branded UI

#### Server Errors (500, 502, 503, 504)

**Fallback Page**: `/50x.html`  
**Location**: Nginx default error page directory  
**Purpose**: User-friendly error message when application is down

## Deployment Considerations

### File Permissions

| Path | Owner | Permissions | Rationale |
|------|-------|-------------|-----------|
| /var/www/wall.cyka.lol | www-data:www-data | 755 (directories) | Nginx read access |
| /var/www/wall.cyka.lol/public | www-data:www-data | 755 (directories), 644 (files) | Web server read access |
| /var/www/wall.cyka.lol/public/uploads | www-data:www-data | 775 (directory) | PHP write access for uploads |
| /var/www/wall.cyka.lol/public/ai-apps | www-data:www-data | 775 (directory) | PHP write access for generated apps |

### PHP-FPM Configuration

**User/Group**: `www-data` (must match Nginx worker user)  
**Socket/Port**: Consistent with Nginx `fastcgi_pass` directive  
**Process Manager**: Dynamic (pm = dynamic)  
**Max Children**: Based on server resources (start with 5-10 for small deployments)

### DNS Configuration

**Required DNS Records**:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | wall.cyka.lol | [Server IP Address] | 3600 |
| A | www.wall.cyka.lol | [Server IP Address] | 3600 |

**Optional**: AAAA records for IPv6 support

### Firewall Configuration

**Open Ports**:
- 80 (HTTP) - for Let's Encrypt verification and HTTPS redirect
- 443 (HTTPS) - primary application access
- 22 (SSH) - server administration (restrict to known IPs if possible)

**Closed Ports**:
- 3306 (MySQL) - database should not be publicly accessible
- 6379 (Redis) - cache should not be publicly accessible
- 11434 (Ollama) - AI service should not be publicly accessible

## Migration from Development to Production

### Environment-Specific Differences

| Aspect | Development (Docker) | Production (Server) |
|--------|---------------------|---------------------|
| Document Root | /var/www/html/public | /var/www/wall.cyka.lol/public |
| PHP-FPM | Container (php:9000) | Unix socket or 127.0.0.1:9000 |
| Port | 8080 | 80/443 |
| SSL | Disabled | Enabled with valid certificate |
| Domain | localhost | wall.cyka.lol |

### Configuration Adjustments Needed

1. **Update Document Root**: Change all paths from `/var/www/html` to `/var/www/wall.cyka.lol`
2. **Update FastCGI Pass**: Change from `php:9000` (Docker service name) to Unix socket or localhost
3. **Enable SSL**: Add SSL certificate configuration and HTTP redirect
4. **Update Server Name**: Change from `localhost` to `wall.cyka.lol`
5. **Adjust Log Paths**: Ensure log directories exist and are writable

### Frontend Build Configuration

**Production Build**:
- Build Vue application: `npm run build` in frontend directory
- Output directory: `frontend/dist`
- Deployment: Copy `dist/*` to `/var/www/wall.cyka.lol/public/`

**Environment Variables**:
- API Base URL: Update `.env.production` to use production domain
- Example: `VITE_API_BASE_URL=https://wall.cyka.lol/api/v1`

## Testing Strategy

### Pre-Deployment Validation

1. **Configuration Syntax**: Test Nginx configuration syntax before reload
2. **SSL Certificate**: Verify certificate installation and chain validity
3. **DNS Propagation**: Confirm DNS records resolve to server IP
4. **File Permissions**: Verify web server can read/write necessary directories

### Post-Deployment Verification

1. **Frontend Access**: Visit `https://wall.cyka.lol` - should load Vue SPA
2. **API Endpoints**: Test `https://wall.cyka.lol/api/v1/health` - should return 200
3. **Static Assets**: Verify images, CSS, and JS load correctly
4. **SPA Routing**: Navigate to `/profile/test` - Vue Router should handle
5. **File Upload**: Test media upload functionality
6. **Real-time Features**: Verify SSE endpoints for AI generation status
7. **SSL Grade**: Run SSL Labs test for A+ rating

### Monitoring Checklist

- [ ] Access logs rotating properly
- [ ] Error logs not showing critical issues
- [ ] Health check endpoint responding
- [ ] SSL certificate auto-renewal configured (Certbot cron job)
- [ ] Server resource usage within normal ranges
- [ ] Database connections stable
- [ ] Queue worker processing jobs

## Rollback Plan

**If deployment fails**:

1. **Immediate**: Revert to previous Nginx configuration (`nginx -s reload` with backup config)
2. **DNS**: If DNS changed, revert A records to previous server
3. **Files**: Restore previous application files from backup
4. **Database**: If migrations applied, restore from pre-deployment snapshot

**Backup Requirements**:
- Nginx configuration files
- Application code (Git commit reference)
- Database dump before migrations
- File uploads directory

## Maintenance Procedures

### Nginx Configuration Updates

1. Edit configuration file: `/etc/nginx/sites-available/wall.cyka.lol`
2. Test syntax: `nginx -t`
3. If valid, reload: `systemctl reload nginx`
4. Monitor error logs: `tail -f /var/log/nginx/wall_error.log`

### SSL Certificate Renewal

**Automatic** (recommended):
- Certbot cron job: `/etc/cron.d/certbot`
- Runs twice daily, renews if <30 days remaining

**Manual** (if needed):
```
certbot renew --nginx -d wall.cyka.lol
```

### Log Rotation

**Configuration**: `/etc/logrotate.d/nginx`  
**Frequency**: Daily  
**Retention**: 14 days (adjustable)  
**Compression**: Enabled after rotation

## Success Criteria

The Nginx configuration is considered successful when:

1. **Accessibility**: Site loads at `https://wall.cyka.lol` with valid SSL
2. **Performance**: Static assets cached, Gzip enabled, response times <200ms
3. **Security**: All security headers present, SSL Labs grade A or higher
4. **Functionality**: Vue SPA routing works, API endpoints respond, file uploads succeed
5. **Reliability**: No 502/504 errors under normal load, uptime >99.9%
6. **Monitoring**: Logs accessible, health checks responding, metrics available
