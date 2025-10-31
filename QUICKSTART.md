# Wall Social Platform - Quick Start Guide

## 🚀 One-Command Setup

```bash
cd C:\Projects\wall.cyka.lol
docker-compose up -d
docker exec -it wall_php composer install
docker exec -it wall_ollama ollama pull deepseek-coder:6.7b
```

## 🌐 Access Points

- **Web Interface**: http://localhost:8080
- **API Base**: http://localhost:8080/api/v1
- **Health Check**: http://localhost:8080/health

## 🔑 Default Credentials

**Admin Account**:
- Username: `admin`
- Password: `admin123` ⚠️ **Change immediately!**

**Database**:
- Host: `localhost:3306`
- Database: `wall_social_platform`
- User: `wall_user`
- Password: `wall_secure_password_123`

**Redis**:
- Host: `localhost:6379`
- No password (development)

## 📦 Docker Services

| Service | Container | Port | Status |
|---------|-----------|------|--------|
| Nginx | wall_nginx | 8080 | ✅ |
| PHP-FPM | wall_php | 9000 | ✅ |
| MySQL | wall_mysql | 3306 | ✅ |
| Redis | wall_redis | 6379 | ✅ |
| Ollama | wall_ollama | 11434 | ✅ |
| Queue Worker | wall_queue_worker | - | ✅ |

## 🛠️ Common Commands

### Docker Management
```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f [service_name]

# Restart service
docker-compose restart [service_name]

# Rebuild and start
docker-compose up -d --build
```

### Database Management
```bash
# Access MySQL shell
docker exec -it wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform

# View all tables
docker exec -it wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform -e "SHOW TABLES;"

# Backup database
docker exec wall_mysql mysqldump -uwall_user -pwall_secure_password_123 wall_social_platform > backup.sql

# Restore database
cat backup.sql | docker exec -i wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform
```

### Redis Management
```bash
# Access Redis CLI
docker exec -it wall_redis redis-cli

# Check connection
docker exec -it wall_redis redis-cli ping

# View all keys
docker exec -it wall_redis redis-cli KEYS '*'

# Clear all data
docker exec -it wall_redis redis-cli FLUSHALL
```

### PHP Management
```bash
# Access PHP container shell
docker exec -it wall_php bash

# Install Composer dependencies
docker exec -it wall_php composer install

# Update dependencies
docker exec -it wall_php composer update

# Run PHP script
docker exec -it wall_php php /var/www/html/your-script.php

# Check PHP version
docker exec -it wall_php php -v

# View PHP configuration
docker exec -it wall_php php -i
```

### Ollama Management
```bash
# Pull AI model
docker exec -it wall_ollama ollama pull deepseek-coder:6.7b

# List installed models
docker exec -it wall_ollama ollama list

# Test model
docker exec -it wall_ollama ollama run deepseek-coder:6.7b "Write a simple HTML page"

# Remove model
docker exec -it wall_ollama ollama rm deepseek-coder:6.7b
```

## 🗂️ Project Structure

```
C:\Projects\wall.cyka.lol\
├── config/          - Configuration files
├── database/        - Database schema
├── docker/          - Docker configurations
├── nginx/           - Nginx configurations
├── public/          - Web root (index.php, uploads, ai-apps)
├── src/             - PHP application code
│   ├── Controllers/ - Request handlers
│   ├── Models/      - Data models
│   ├── Services/    - Business logic
│   └── Utils/       - Helper functions
├── storage/         - Logs and cache
└── workers/         - Background workers
```

## 🔍 Troubleshooting

### Services Won't Start
```bash
# Check Docker is running
docker --version

# Check port conflicts
netstat -ano | findstr "8080"
netstat -ano | findstr "3306"

# Remove old containers
docker-compose down -v
docker-compose up -d
```

### Database Connection Failed
```bash
# Check MySQL is running
docker exec -it wall_mysql mysqladmin ping -h localhost

# Verify credentials in .env file
# Check database exists
docker exec -it wall_mysql mysql -uroot -proot_password_change_in_production -e "SHOW DATABASES;"
```

### Redis Connection Failed
```bash
# Check Redis is running
docker exec -it wall_redis redis-cli ping

# Restart Redis
docker-compose restart redis
```

### Ollama Not Responding
```bash
# Check Ollama is running
docker exec -it wall_ollama curl http://localhost:11434/api/tags

# Restart Ollama
docker-compose restart ollama

# Check model is installed
docker exec -it wall_ollama ollama list
```

### Application Errors
```bash
# Check PHP logs
docker-compose logs -f php

# Check Nginx logs
docker-compose logs -f nginx

# Check application logs
cat storage/logs/app.log
```

## 📊 Health Checks

```bash
# Overall system health
curl http://localhost:8080/health

# Check all containers
docker-compose ps

# Check container resource usage
docker stats

# Verify database connectivity
docker exec -it wall_php php -r "try { new PDO('mysql:host=mysql;dbname=wall_social_platform', 'wall_user', 'wall_secure_password_123'); echo 'Connected'; } catch (Exception \$e) { echo 'Failed: ' . \$e->getMessage(); }"
```

## 🎯 API Testing

### Using curl
```bash
# API info
curl http://localhost:8080/api/v1

# Health check
curl http://localhost:8080/health

# Test authentication (not yet implemented)
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

## 📝 Configuration Files

- **Docker**: `docker-compose.yml`
- **Environment**: `.env.example` (copy to `.env`)
- **Nginx**: `nginx/conf.d/default.conf`
- **PHP**: `docker/php/php.ini`
- **Database**: `config/database.php`
- **Dependencies**: `composer.json`

## 🎓 Learning Resources

- **Full Documentation**: `PROJECT_README.md`
- **Phase 1 Summary**: `PHASE1_COMPLETE.md`
- **Design Specification**: `.qoder/quests/wall-social-platform.md`
- **Database Schema**: `database/schema.sql`

## 🐛 Known Issues

1. **AI Generation**: Not yet implemented (Phase 4)
2. **Authentication**: Placeholder endpoints return 501
3. **Frontend**: No UI yet (Phase 8)
4. **File Uploads**: Directories ready but no processing

## 🔐 Security Reminders

⚠️ **Before Production**:
- [ ] Change default admin password
- [ ] Update database passwords
- [ ] Configure HTTPS/SSL
- [ ] Set JWT secret
- [ ] Configure OAuth providers
- [ ] Enable rate limiting
- [ ] Set up backups

## 💡 Tips

1. **Development Mode**: `APP_DEBUG=true` in `.env`
2. **Logs Location**: `storage/logs/` and Docker logs
3. **Cache Clear**: Restart PHP container
4. **Database Reset**: `docker-compose down -v` then `up -d`
5. **Fresh Start**: Remove all volumes and rebuild

## 📞 Support

- **Issues**: GitHub Issues
- **Docs**: PROJECT_README.md
- **Design**: .qoder/quests/wall-social-platform.md

---

**Version**: 1.0  
**Phase**: 1 Complete ✅  
**Updated**: 2025-10-31
