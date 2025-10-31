# Local Project Setup Documentation

## Overview

This document provides comprehensive instructions for setting up and running the Wall Social Platform project locally on both Windows and Ubuntu Server environments. The project uses a Docker-based architecture with Nginx, PHP 8.2+, MySQL 8.0+, Redis, and Ollama AI services.

## Target Environments

- **Windows**: Development environment using Docker Desktop
- **Ubuntu Server**: Production or staging deployment environment

---

## Windows Local Setup

### Prerequisites

#### Required Software

| Software | Minimum Version | Download Link |
|----------|----------------|---------------|
| Docker Desktop | 4.0+ | https://www.docker.com/products/docker-desktop |
| Git | 2.30+ | https://git-scm.com/download/win |
| Windows Terminal | Latest | Windows Store (optional but recommended) |

#### System Requirements

- Windows 10 version 2004 or higher, or Windows 11
- WSL 2 enabled (Windows Subsystem for Linux)
- At least 8GB RAM (16GB recommended)
- 20GB free disk space minimum
- Internet connection for downloading dependencies

### Initial Setup Process

#### Step 1: Enable WSL 2

Open PowerShell as Administrator and execute:

```
wsl --install
```

Restart your computer after installation completes.

Verify WSL 2 is the default version:

```
wsl --set-default-version 2
```

#### Step 2: Install Docker Desktop

- Download Docker Desktop from the official website
- Run the installer with default settings
- Ensure "Use WSL 2 instead of Hyper-V" option is selected
- Start Docker Desktop after installation
- Verify Docker is running by checking the system tray icon

Verify installation by opening a terminal:

```
docker --version
docker-compose --version
```

Expected output should show Docker version 20.10+ and Docker Compose version 2.0+.

#### Step 3: Clone the Project Repository

Open Windows Terminal or Command Prompt:

```
cd C:\Projects
git clone <repository-url> wall.cyka.lol
cd wall.cyka.lol
```

If the project directory already exists, navigate to it:

```
cd C:\Projects\wall.cyka.lol
```

#### Step 4: Configure Environment Variables

Create the environment configuration file:

```
copy .env.example .env
```

Open the `.env` file in a text editor (Notepad, VS Code, etc.) and review the configuration. Default values are suitable for local development, but you may customize:

| Parameter | Description | Default Value |
|-----------|-------------|---------------|
| APP_URL | Application base URL | http://localhost:8080 |
| DB_PASSWORD | Database password | wall_secure_password_123 |
| JWT_SECRET | Secret key for authentication | Change in production |
| OLLAMA_MODEL | AI model name | deepseek-coder:6.7b |

For development, default values can be used without modification.

#### Step 5: Start Docker Services

Launch all services using Docker Compose:

```
docker-compose up -d
```

This command will:
- Download required Docker images (Nginx, PHP, MySQL, Redis, Ollama)
- Build the custom PHP container
- Create Docker volumes for persistent data
- Initialize the database with the schema
- Start all services in detached mode

Wait for all containers to start (approximately 2-5 minutes on first run).

#### Step 6: Verify Container Status

Check that all containers are running:

```
docker-compose ps
```

Expected output should show six containers with "Up" status:

| Container Name | Status | Ports |
|----------------|--------|-------|
| wall_nginx | Up | 0.0.0.0:8080->80/tcp |
| wall_php | Up | 9000/tcp |
| wall_mysql | Up | 0.0.0.0:3306->3306/tcp |
| wall_redis | Up | 0.0.0.0:6379->6379/tcp |
| wall_ollama | Up | 0.0.0.0:11434->11434/tcp |
| wall_queue_worker | Up | - |

#### Step 7: Install PHP Dependencies

Install Composer dependencies inside the PHP container:

```
docker exec -it wall_php composer install
```

This will download all required PHP packages defined in composer.json.

#### Step 8: Download AI Model

Pull the DeepSeek-Coder model for AI generation:

```
docker exec -it wall_ollama ollama pull deepseek-coder:6.7b
```

This download is approximately 3.8GB and may take 10-30 minutes depending on internet speed.

Verify the model is installed:

```
docker exec -it wall_ollama ollama list
```

#### Step 9: Verify Database Initialization

Check that the database schema was created successfully:

```
docker exec -it wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform -e "SHOW TABLES;"
```

Expected output should list all database tables (users, walls, posts, comments, reactions, etc.).

#### Step 10: Test Application Access

Open a web browser and navigate to:

- **Main Application**: http://localhost:8080
- **Health Check**: http://localhost:8080/health

Expected response from health check: "healthy"

### Docker Desktop Configuration

#### Resource Allocation

Recommended Docker Desktop resource settings for optimal performance:

| Resource | Recommended Value |
|----------|-------------------|
| CPUs | 4 cores |
| Memory | 6 GB |
| Swap | 2 GB |
| Disk Image Size | 60 GB |

To adjust these settings:
- Open Docker Desktop
- Navigate to Settings > Resources
- Adjust sliders to recommended values
- Click "Apply & Restart"

#### WSL Integration

Ensure WSL integration is enabled:
- Open Docker Desktop Settings
- Navigate to Resources > WSL Integration
- Enable integration with your WSL 2 distribution
- Click "Apply & Restart"

### Port Configuration

If default ports are already in use, modify `docker-compose.yml` port mappings:

| Service | Default Port | Alternative Port Mapping |
|---------|--------------|-------------------------|
| Nginx | 8080:80 | 8081:80 |
| MySQL | 3306:3306 | 3307:3306 |
| Redis | 6379:6379 | 6380:6379 |
| Ollama | 11434:11434 | 11435:11434 |

After modifying ports, recreate containers:

```
docker-compose down
docker-compose up -d
```

### Common Operations

#### Starting the Project

```
cd C:\Projects\wall.cyka.lol
docker-compose up -d
```

#### Stopping the Project

```
docker-compose down
```

To also remove volumes (database data):

```
docker-compose down -v
```

#### Viewing Logs

All services:
```
docker-compose logs -f
```

Specific service:
```
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f mysql
```

#### Restarting a Service

```
docker-compose restart php
docker-compose restart nginx
```

#### Rebuilding After Code Changes

```
docker-compose up -d --build
```

#### Accessing Container Shell

PHP container:
```
docker exec -it wall_php bash
```

MySQL container:
```
docker exec -it wall_mysql bash
```

#### Database Operations

MySQL shell access:
```
docker exec -it wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform
```

Database backup:
```
docker exec wall_mysql mysqldump -uwall_user -pwall_secure_password_123 wall_social_platform > backup.sql
```

Database restore:
```
type backup.sql | docker exec -i wall_mysql mysql -uwall_user -pwall_secure_password_123 wall_social_platform
```

#### Redis Operations

Redis CLI access:
```
docker exec -it wall_redis redis-cli
```

Check Redis connection:
```
docker exec -it wall_redis redis-cli ping
```

View all keys:
```
docker exec -it wall_redis redis-cli KEYS '*'
```

Clear all Redis data:
```
docker exec -it wall_redis redis-cli FLUSHALL
```

### Troubleshooting Windows Setup

#### Docker Desktop Not Starting

**Symptoms**: Docker Desktop shows error on startup

**Solutions**:
- Ensure Hyper-V and WSL 2 are enabled in Windows Features
- Restart Docker Desktop service from Services panel
- Restart computer
- Reinstall Docker Desktop if issues persist

#### Port Already in Use

**Symptoms**: Error message "port is already allocated"

**Solutions**:
- Identify process using the port:
  ```
  netstat -ano | findstr "8080"
  ```
- Terminate the process using Task Manager
- Or modify port mappings in docker-compose.yml

#### Containers Failing to Start

**Symptoms**: Container exits immediately after starting

**Solutions**:
- Check container logs:
  ```
  docker-compose logs <service_name>
  ```
- Verify no duplicate container names:
  ```
  docker ps -a
  ```
- Remove old containers:
  ```
  docker-compose down
  docker-compose up -d
  ```

#### Database Connection Errors

**Symptoms**: Application cannot connect to MySQL

**Solutions**:
- Verify MySQL container is running:
  ```
  docker-compose ps mysql
  ```
- Check MySQL health:
  ```
  docker exec -it wall_mysql mysqladmin ping -h localhost
  ```
- Verify credentials in .env file match docker-compose.yml
- Restart MySQL container:
  ```
  docker-compose restart mysql
  ```

#### Ollama Model Download Fails

**Symptoms**: Model download stalls or fails

**Solutions**:
- Check internet connection
- Restart Ollama container:
  ```
  docker-compose restart ollama
  ```
- Retry download:
  ```
  docker exec -it wall_ollama ollama pull deepseek-coder:6.7b
  ```
- Check disk space availability

#### Slow Performance

**Symptoms**: Containers running slowly

**Solutions**:
- Increase Docker Desktop resource allocation
- Close unnecessary applications
- Move project to faster disk (SSD recommended)
- Disable antivirus real-time scanning for project directory

---

## Ubuntu Server Setup

### Prerequisites

#### System Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| Ubuntu Version | 20.04 LTS | 22.04 LTS or newer |
| RAM | 4 GB | 8 GB+ |
| Disk Space | 40 GB | 100 GB+ |
| CPU Cores | 2 | 4+ |
| Network | Internet access | Static IP for production |

#### Required Packages

- Docker Engine (not Docker Desktop)
- Docker Compose V2
- Git
- Curl or Wget

### Initial Setup Process

#### Step 1: Update System Packages

Connect to your Ubuntu server via SSH and update the system:

```bash
sudo apt update
sudo apt upgrade -y
```

#### Step 2: Install Docker Engine

Remove any old Docker installations:

```bash
sudo apt remove docker docker-engine docker.io containerd runc
```

Install dependencies:

```bash
sudo apt install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release
```

Add Docker's official GPG key:

```bash
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
```

Set up the Docker repository:

```bash
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

Install Docker Engine:

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Verify Docker installation:

```bash
docker --version
docker compose version
```

Expected output: Docker version 20.10+ and Docker Compose version 2.0+

#### Step 3: Configure Docker Permissions

Add your user to the docker group to run Docker without sudo:

```bash
sudo usermod -aG docker $USER
```

Log out and log back in for the group change to take effect, or run:

```bash
newgrp docker
```

Verify you can run Docker without sudo:

```bash
docker ps
```

#### Step 4: Install Git

```bash
sudo apt install -y git
```

Verify installation:

```bash
git --version
```

#### Step 5: Clone Project Repository

Create a directory for the project:

```bash
sudo mkdir -p /var/www
sudo chown $USER:$USER /var/www
cd /var/www
```

Clone the repository:

```bash
git clone <repository-url> wall.cyka.lol
cd wall.cyka.lol
```

If deploying from a local copy, use SCP or SFTP to transfer files to `/var/www/wall.cyka.lol`.

#### Step 6: Configure Environment Variables

Create the environment file from template:

```bash
cp .env.example .env
```

Edit the .env file with production values:

```bash
nano .env
```

Important parameters to configure for production:

| Parameter | Production Value | Notes |
|-----------|-----------------|-------|
| APP_ENV | production | Disables debug mode |
| APP_DEBUG | false | Must be false in production |
| APP_URL | https://yourdomain.com | Your actual domain |
| DB_PASSWORD | <strong-password> | Use secure password generator |
| JWT_SECRET | <random-64-char-string> | Generate with openssl rand -hex 32 |
| SESSION_SECURE | true | Requires HTTPS |
| RATE_LIMIT_ENABLED | true | Enable rate limiting |

Generate a secure JWT secret:

```bash
openssl rand -hex 32
```

#### Step 7: Configure Docker Compose for Production

For production deployment, you may want to modify `docker-compose.yml` to:

- Change Nginx port from 8080 to 80 and 443 (HTTPS)
- Set restart policies to "always" for all services
- Add resource limits for containers
- Configure log rotation

Example modification for Nginx ports (if not using reverse proxy):

Change from:
```
ports:
  - "8080:80"
```

To:
```
ports:
  - "80:80"
  - "443:443"
```

#### Step 8: Set File Permissions

Ensure proper ownership of application files:

```bash
sudo chown -R $USER:$USER /var/www/wall.cyka.lol
```

Create and set permissions for storage directories:

```bash
mkdir -p storage/logs
mkdir -p public/uploads
mkdir -p public/ai-apps
chmod -R 775 storage
chmod -R 775 public/uploads
chmod -R 775 public/ai-apps
```

#### Step 9: Start Docker Services

Launch all services:

```bash
cd /var/www/wall.cyka.lol
docker compose up -d
```

Wait for all containers to start and initialize (2-5 minutes on first run).

#### Step 10: Verify Container Status

```bash
docker compose ps
```

All six containers should show "Up" status.

#### Step 11: Install PHP Dependencies

```bash
docker exec -it wall_php composer install --no-dev --optimize-autoloader
```

The `--no-dev` flag excludes development dependencies for production.

#### Step 12: Download AI Model

```bash
docker exec -it wall_ollama ollama pull deepseek-coder:6.7b
```

Monitor download progress (approximately 3.8GB).

#### Step 13: Verify Database Initialization

```bash
docker exec -it wall_mysql mysql -uwall_user -p<your-db-password> wall_social_platform -e "SHOW TABLES;"
```

#### Step 14: Test Application

From the server:

```bash
curl http://localhost/health
```

Expected response: "healthy"

If accessible via public IP or domain:

```bash
curl http://<server-ip>/health
```

### Firewall Configuration

Configure UFW (Uncomplicated Firewall) to allow necessary ports:

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS (if using SSL)
sudo ufw enable
sudo ufw status
```

For development/testing, you may temporarily allow additional ports:

```bash
sudo ufw allow 3306/tcp  # MySQL (only if remote access needed)
sudo ufw allow 6379/tcp  # Redis (only if remote access needed)
```

**Security Warning**: Do not expose MySQL and Redis ports to public internet in production.

### SSL/HTTPS Configuration

For production deployment, SSL/HTTPS is mandatory for secure authentication.

#### Option 1: Using Certbot with Nginx

Install Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Obtain SSL certificate:

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Certbot will automatically configure Nginx and set up auto-renewal.

#### Option 2: Using Reverse Proxy

If using a reverse proxy (Nginx or Apache) in front of Docker containers:

- Configure SSL on the host Nginx/Apache
- Proxy requests to localhost:8080
- Update APP_URL in .env to use HTTPS

Example Nginx reverse proxy configuration:

```
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### System Service Configuration

Create a systemd service to auto-start containers on boot.

Create service file:

```bash
sudo nano /etc/systemd/system/wall-platform.service
```

Add the following content:

```
[Unit]
Description=Wall Social Platform Docker Containers
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/var/www/wall.cyka.lol
ExecStart=/usr/bin/docker compose up -d
ExecStop=/usr/bin/docker compose down
User=<your-username>

[Install]
WantedBy=multi-user.target
```

Replace `<your-username>` with your actual username.

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable wall-platform.service
sudo systemctl start wall-platform.service
```

Check service status:

```bash
sudo systemctl status wall-platform.service
```

### Common Operations

#### Starting the Application

```bash
cd /var/www/wall.cyka.lol
docker compose up -d
```

Or via systemd:

```bash
sudo systemctl start wall-platform
```

#### Stopping the Application

```bash
docker compose down
```

Or via systemd:

```bash
sudo systemctl stop wall-platform
```

#### Viewing Logs

Container logs:
```bash
docker compose logs -f
docker compose logs -f php
docker compose logs -f nginx
```

System logs:
```bash
sudo journalctl -u wall-platform -f
```

#### Updating the Application

Pull latest code:

```bash
cd /var/www/wall.cyka.lol
git pull origin main
```

Rebuild and restart containers:

```bash
docker compose down
docker compose up -d --build
```

Update PHP dependencies:

```bash
docker exec -it wall_php composer install --no-dev --optimize-autoloader
```

#### Database Backup

Create backup script:

```bash
nano ~/backup-wall-db.sh
```

Add content:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/wall-platform"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

docker exec wall_mysql mysqldump -uwall_user -p<password> wall_social_platform > $BACKUP_DIR/wall_db_$DATE.sql

# Keep only last 7 days of backups
find $BACKUP_DIR -name "wall_db_*.sql" -mtime +7 -delete
```

Make executable:

```bash
chmod +x ~/backup-wall-db.sh
```

Schedule with cron (daily at 2 AM):

```bash
crontab -e
```

Add line:

```
0 2 * * * /home/<username>/backup-wall-db.sh
```

#### Monitoring Resources

Container resource usage:

```bash
docker stats
```

System resource usage:

```bash
htop
```

Install htop if not available:

```bash
sudo apt install -y htop
```

Disk usage:

```bash
df -h
docker system df
```

### Troubleshooting Ubuntu Setup

#### Docker Service Not Starting

**Symptoms**: Docker daemon fails to start

**Solutions**:

Check Docker service status:
```bash
sudo systemctl status docker
```

View detailed logs:
```bash
sudo journalctl -u docker -f
```

Restart Docker service:
```bash
sudo systemctl restart docker
```

#### Permission Denied Errors

**Symptoms**: Cannot run Docker commands without sudo

**Solutions**:

Ensure user is in docker group:
```bash
groups $USER
```

If docker group is missing, add it:
```bash
sudo usermod -aG docker $USER
newgrp docker
```

#### Container Memory Issues

**Symptoms**: Containers crashing due to OOM (Out of Memory)

**Solutions**:

Check available memory:
```bash
free -h
```

Add swap space if needed:
```bash
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

Make swap permanent by adding to `/etc/fstab`:
```
/swapfile none swap sw 0 0
```

#### Network Connectivity Issues

**Symptoms**: Containers cannot reach internet

**Solutions**:

Check Docker network:
```bash
docker network ls
docker network inspect bridge
```

Restart Docker networking:
```bash
sudo systemctl restart docker
```

Check DNS resolution inside container:
```bash
docker exec -it wall_php ping google.com
```

#### SSL Certificate Renewal Fails

**Symptoms**: Certbot auto-renewal fails

**Solutions**:

Test renewal manually:
```bash
sudo certbot renew --dry-run
```

Check Certbot timer:
```bash
sudo systemctl status certbot.timer
```

Manually renew:
```bash
sudo certbot renew
```

#### Disk Space Issues

**Symptoms**: Out of disk space errors

**Solutions**:

Check disk usage:
```bash
df -h
docker system df
```

Clean Docker resources:
```bash
docker system prune -a
docker volume prune
```

Remove old logs:
```bash
sudo journalctl --vacuum-time=7d
```

---

## Testing and Validation

### Health Check Endpoints

| Endpoint | Expected Response | Purpose |
|----------|------------------|---------|
| http://localhost:8080/health | "healthy" | Application health |
| http://localhost:8080/ | HTML page | Frontend loaded |

### Service Validation

#### Database Connection Test

```bash
docker exec -it wall_php php -r "try { new PDO('mysql:host=mysql;dbname=wall_social_platform', 'wall_user', '<password>'); echo 'Database connected'; } catch (Exception \$e) { echo 'Failed: ' . \$e->getMessage(); }"
```

#### Redis Connection Test

```bash
docker exec -it wall_redis redis-cli ping
```

Expected output: "PONG"

#### Ollama Model Test

```bash
docker exec -it wall_ollama ollama run deepseek-coder:6.7b "Hello world"
```

Expected: Model responds with text output.

#### Queue Worker Status

```bash
docker compose logs queue_worker
```

Expected: No error messages, worker running.

### Performance Baseline

After successful setup, verify baseline performance:

| Metric | Acceptable Range |
|--------|-----------------|
| Home page load | < 2 seconds |
| Database query response | < 100ms |
| Redis ping latency | < 5ms |
| Container memory usage | < 4GB total |

Use browser developer tools or curl to measure response times.

---

## Maintenance and Operations

### Regular Maintenance Tasks

#### Daily Tasks

- Monitor container status
- Review application logs for errors
- Check disk space availability

#### Weekly Tasks

- Database backup verification
- Review security logs
- Update Docker images if security patches available

#### Monthly Tasks

- Review and rotate logs
- Analyze performance metrics
- Plan capacity upgrades if needed
- Update dependencies (Composer packages)

### Log Management

#### Log Locations

| Component | Log Path | Description |
|-----------|----------|-------------|
| Nginx Access | /var/log/nginx/wall_access.log | HTTP request logs |
| Nginx Error | /var/log/nginx/wall_error.log | Nginx error logs |
| PHP Application | storage/logs/app.log | Application logs |
| Docker Container | docker compose logs | Container stdout/stderr |

#### Log Rotation

Configure logrotate for application logs:

```bash
sudo nano /etc/logrotate.d/wall-platform
```

Add configuration:

```
/var/www/wall.cyka.lol/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

### Backup Strategy

#### What to Backup

- Database (MySQL dumps)
- Uploaded media files (public/uploads)
- AI-generated applications (public/ai-apps)
- Environment configuration (.env)
- Custom configuration files

#### Backup Frequency

| Data Type | Frequency | Retention |
|-----------|-----------|-----------|
| Database | Daily | 30 days |
| Media Files | Weekly | 90 days |
| Configuration | On change | Indefinitely |

#### Backup Storage

Store backups in a separate location:
- External storage server
- Cloud storage (S3, Backblaze, etc.)
- Network-attached storage (NAS)

Never store backups only on the same server as the application.

### Disaster Recovery

#### Recovery Time Objective (RTO)

Target: Application restored within 4 hours of failure.

#### Recovery Point Objective (RPO)

Target: Maximum 24 hours of data loss (daily backup schedule).

#### Recovery Procedure

In case of complete system failure:

1. Provision new server with same specifications
2. Install Docker and dependencies (Step 1-4 of Ubuntu setup)
3. Restore application files from backup
4. Restore database from latest backup
5. Restore media files from backup
6. Update DNS records to point to new server (if IP changed)
7. Verify all services are running
8. Test critical functionality

Estimated recovery time: 2-4 hours

---

## Security Considerations

### Production Security Checklist

Before deploying to production, ensure:

- [ ] Changed all default passwords (database, admin account)
- [ ] Generated secure JWT secret
- [ ] Enabled HTTPS/SSL with valid certificate
- [ ] Configured firewall to block unnecessary ports
- [ ] Set SESSION_SECURE=true in .env
- [ ] Disabled debug mode (APP_DEBUG=false)
- [ ] Configured rate limiting
- [ ] Set up automated security updates
- [ ] Configured database backup encryption
- [ ] Implemented intrusion detection (fail2ban, etc.)
- [ ] Reviewed and hardened Nginx configuration
- [ ] Disabled directory listing
- [ ] Configured Content Security Policy headers

### Network Security

- Use private Docker networks for inter-container communication
- Do not expose database and Redis ports to public internet
- Use VPN or SSH tunneling for administrative access
- Implement IP whitelisting for administrative endpoints

### Data Security

- Encrypt sensitive data at rest
- Use secure password hashing (bcrypt with 10+ rounds)
- Implement proper session management
- Sanitize user inputs to prevent XSS and SQL injection
- Validate and scan uploaded files for malware

### Access Control

- Implement principle of least privilege
- Use separate credentials for different environments
- Rotate credentials regularly
- Enable two-factor authentication for administrative accounts
- Audit access logs regularly

---

## Performance Optimization

### Database Optimization

- Enable query caching in MySQL configuration
- Create appropriate indexes for frequently queried columns
- Analyze slow query log regularly
- Consider read replicas for high-traffic scenarios

### Caching Strategy

- Use Redis for session storage
- Cache frequently accessed data (user profiles, wall metadata)
- Implement HTTP caching headers for static assets
- Use CDN for media delivery in production

### Resource Allocation

Recommended Docker resource limits for production:

| Service | CPU Limit | Memory Limit |
|---------|-----------|--------------|
| Nginx | 1 core | 512 MB |
| PHP-FPM | 2 cores | 2 GB |
| MySQL | 2 cores | 4 GB |
| Redis | 1 core | 1 GB |
| Ollama | 4 cores | 8 GB |
| Queue Worker | 1 core | 1 GB |

Adjust based on actual usage patterns and available resources.

### Monitoring and Metrics

Consider implementing monitoring tools:

- **Prometheus + Grafana**: For metrics collection and visualization
- **ELK Stack**: For centralized logging
- **Netdata**: For real-time system monitoring
- **Uptime monitoring**: Services like UptimeRobot or Pingdom

Key metrics to monitor:

- Request response times
- Error rates (4xx, 5xx)
- Database query performance
- Queue processing times
- Container resource usage
- Disk I/O and network throughput

---

## Additional Resources

### Documentation References

| Document | Location | Description |
|----------|----------|-------------|
| Quick Start Guide | QUICKSTART.md | Basic setup instructions |
| Full Documentation | PROJECT_README.md | Complete project documentation |
| Design Specification | README.md | Detailed design document |
| Database Schema | database/schema.sql | Database structure |
| API Documentation | Not yet available | API endpoints reference |

### Useful Commands Reference

#### Docker Commands

| Command | Description |
|---------|-------------|
| docker compose up -d | Start all services |
| docker compose down | Stop all services |
| docker compose ps | List running containers |
| docker compose logs -f | Follow all logs |
| docker compose restart | Restart all services |
| docker system prune -a | Clean up unused resources |

#### MySQL Commands

| Command | Description |
|---------|-------------|
| docker exec -it wall_mysql mysql -u<user> -p | Access MySQL CLI |
| SHOW DATABASES; | List all databases |
| SHOW TABLES; | List all tables |
| DESCRIBE <table>; | Show table structure |

#### Redis Commands

| Command | Description |
|---------|-------------|
| docker exec -it wall_redis redis-cli | Access Redis CLI |
| PING | Test connection |
| KEYS * | List all keys |
| GET <key> | Get key value |
| FLUSHALL | Clear all data |

### Support and Troubleshooting

For issues not covered in this document:

1. Check container logs: `docker compose logs <service>`
2. Review application logs: `storage/logs/app.log`
3. Verify environment configuration: `.env` file
4. Check Docker daemon status: `systemctl status docker`
5. Review GitHub issues for known problems
6. Consult project documentation in PROJECT_README.md

---

## Appendix

### Environment Variable Reference

Complete list of environment variables and their purposes:

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| APP_NAME | String | Wall Social Platform | Application name |
| APP_ENV | String | development | Environment (development/production) |
| APP_DEBUG | Boolean | true | Enable debug mode |
| APP_URL | URL | http://localhost:8080 | Application base URL |
| DB_HOST | String | mysql | Database host |
| DB_PORT | Integer | 3306 | Database port |
| DB_NAME | String | wall_social_platform | Database name |
| DB_USER | String | wall_user | Database username |
| DB_PASSWORD | String | - | Database password |
| REDIS_HOST | String | redis | Redis host |
| REDIS_PORT | Integer | 6379 | Redis port |
| OLLAMA_HOST | String | ollama | Ollama service host |
| OLLAMA_PORT | Integer | 11434 | Ollama service port |
| OLLAMA_MODEL | String | deepseek-coder:6.7b | AI model name |
| JWT_SECRET | String | - | Secret key for JWT tokens |
| SESSION_SECURE | Boolean | false | Use secure cookies (HTTPS only) |
| UPLOAD_MAX_SIZE | Integer | 52428800 | Max upload size in bytes (50MB) |

### Docker Compose Service Reference

Detailed service configurations:

#### Nginx Service

- **Image**: nginx:alpine
- **Purpose**: Web server and reverse proxy
- **Exposed Ports**: 8080 (maps to container port 80)
- **Volumes**: Configuration and web root
- **Dependencies**: PHP service

#### PHP Service

- **Build**: Custom Dockerfile (docker/php/Dockerfile)
- **Purpose**: Application runtime and PHP-FPM
- **PHP Version**: 8.2+
- **Extensions**: PDO, Redis, GD, Zip, Mbstring
- **Volumes**: Application code
- **Dependencies**: MySQL, Redis

#### MySQL Service

- **Image**: mysql:8.0
- **Purpose**: Relational database
- **Exposed Ports**: 3306
- **Volumes**: Persistent data storage, schema initialization
- **Health Check**: mysqladmin ping

#### Redis Service

- **Image**: redis:alpine
- **Purpose**: Caching and queue management
- **Exposed Ports**: 6379
- **Volumes**: Persistent data storage
- **Health Check**: redis-cli ping

#### Ollama Service

- **Image**: ollama/ollama:latest
- **Purpose**: AI model inference
- **Exposed Ports**: 11434
- **Volumes**: Model storage
- **Resource Requirements**: High CPU and memory

#### Queue Worker Service

- **Build**: Same as PHP service
- **Purpose**: Background job processing
- **Command**: php workers/ai_generation_worker.php
- **Dependencies**: MySQL, Redis, Ollama
- **Restart Policy**: unless-stopped

### Port Mapping Reference

| Service | Container Port | Host Port (Default) | Protocol | Purpose |
|---------|----------------|---------------------|----------|---------|
| Nginx | 80 | 8080 | HTTP | Web traffic |
| Nginx | 443 | 443 | HTTPS | Secure web traffic |
| PHP-FPM | 9000 | - | FastCGI | Internal only |
| MySQL | 3306 | 3306 | TCP | Database access |
| Redis | 6379 | 6379 | TCP | Cache/Queue access |
| Ollama | 11434 | 11434 | HTTP | AI API |

### Volume Mapping Reference

| Volume Name | Container Path | Purpose |
|-------------|----------------|---------|
| mysql_data | /var/lib/mysql | MySQL data persistence |
| redis_data | /data | Redis data persistence |
| ollama_data | /root/.ollama | AI model storage |

### System Requirements Summary

#### Minimum Requirements

- **CPU**: 2 cores
- **RAM**: 4 GB
- **Disk**: 40 GB
- **Network**: 10 Mbps

#### Recommended Requirements

- **CPU**: 4+ cores
- **RAM**: 8+ GB
- **Disk**: 100+ GB SSD
- **Network**: 100 Mbps

#### Production Requirements

- **CPU**: 8+ cores
- **RAM**: 16+ GB
- **Disk**: 500+ GB SSD with RAID
- **Network**: 1 Gbps with redundancy
