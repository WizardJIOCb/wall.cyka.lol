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
