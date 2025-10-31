# Local Setup Guide - Quick Reference

> **Full Documentation:** See `.qoder/quests/local-project-setup.md` for comprehensive setup instructions for both Windows and Ubuntu Server.

## Windows Quick Start

### Prerequisites
✅ Docker Desktop 4.0+ (with WSL 2)  
✅ Git 2.30+  
✅ 8GB RAM minimum (16GB recommended)  
✅ 20GB free disk space

### 5-Minute Setup

```powershell
# 1. Navigate to project
cd C:\Projects\wall.cyka.lol

# 2. Configure environment
copy .env.example .env

# 3. Start all services
docker-compose up -d

# 4. Install dependencies
docker exec -it wall_php composer install

# 5. Download AI model (3.8GB, 10-30 min)
docker exec -it wall_ollama ollama pull deepseek-coder:6.7b
```

### Verify Installation

```powershell
# Check containers
docker-compose ps

# Expected: 6 containers running (nginx, php, mysql, redis, ollama, queue_worker)
```

**Access Application:**
- Main: http://localhost:8080
- Health: http://localhost:8080/health

---

## Common Commands

### Start/Stop Services
```powershell
docker-compose up -d          # Start
docker-compose down           # Stop
docker-compose restart        # Restart all
docker-compose restart php    # Restart specific service
```

### View Logs
```powershell
docker-compose logs -f        # All services
docker-compose logs -f php    # PHP only
docker-compose logs -f nginx  # Nginx only
```

### Database Access
```powershell
# MySQL shell
docker exec -it wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform

# Show tables
docker exec -it wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform -e "SHOW TABLES;"

# Backup
docker exec wall_mysql mysqldump -uwall_user -pwall_secure_password_123 wall_social_platform > backup.sql

# Restore
type backup.sql | docker exec -i wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform
```

### Redis Access
```powershell
# Redis CLI
docker exec -it wall_redis redis-cli

# Test connection
docker exec -it wall_redis redis-cli ping

# View keys
docker exec -it wall_redis redis-cli KEYS '*'

# Clear data
docker exec -it wall_redis redis-cli FLUSHALL
```

### Container Shell Access
```powershell
docker exec -it wall_php bash      # PHP container
docker exec -it wall_mysql bash    # MySQL container
```

---

## Troubleshooting

### Port Already in Use
```powershell
# Find process using port
netstat -ano | findstr "8080"

# Solution: Kill process or modify docker-compose.yml ports
```

### Containers Won't Start
```powershell
docker-compose down
docker-compose up -d
docker-compose logs  # Check for errors
```

### Database Connection Failed
```powershell
# Restart MySQL
docker-compose restart mysql

# Verify
docker exec -it wall_mysql mysqladmin ping -h localhost
```

### Slow Performance
- Increase Docker Desktop resources: Settings > Resources
  - CPUs: 4 cores
  - Memory: 6GB
  - Swap: 2GB

---

## Default Configuration

### Services & Ports
| Service | Port | Purpose |
|---------|------|---------|
| Nginx | 8080 | Web server |
| MySQL | 3306 | Database |
| Redis | 6379 | Cache/Queue |
| Ollama | 11434 | AI API |
| PHP-FPM | 9000 | Application (internal) |

### Database Credentials
- **Host:** localhost:3306
- **Database:** wall_social_platform
- **User:** wall_user
- **Password:** wall_secure_password_123

### Container Names
- wall_nginx
- wall_php
- wall_mysql
- wall_redis
- wall_ollama
- wall_queue_worker

---

## Ubuntu Server Deployment

For Ubuntu Server setup instructions including:
- Docker Engine installation
- SSL/HTTPS configuration
- Firewall setup
- Systemd service configuration
- Production security hardening
- Backup strategies

**See comprehensive guide:** `.qoder/quests/local-project-setup.md`

---

## Documentation Resources

| Document | Location | Description |
|----------|----------|-------------|
| **Complete Setup Guide** | `.qoder/quests/local-project-setup.md` | Full Windows & Ubuntu instructions (33KB) |
| Quick Start | `QUICKSTART.md` | Basic setup overview |
| Project Docs | `PROJECT_README.md` | Complete project documentation |
| Design Spec | `README.md` | Detailed design document |
| Database Schema | `database/schema.sql` | Database structure |

---

## Next Steps

After successful setup:

1. ✅ Verify all 6 containers running: `docker-compose ps`
2. ✅ Check database initialized: See database commands above
3. ✅ Access application: http://localhost:8080
4. ✅ Review application logs: `docker-compose logs -f`
5. 📖 Read `.qoder/quests/local-project-setup.md` for production deployment

---

**Questions or Issues?**
1. Check container logs: `docker-compose logs <service>`
2. Review full documentation: `.qoder/quests/local-project-setup.md`
3. Check Docker status: `docker ps` and `docker stats`
