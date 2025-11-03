# Production Deployment Guide for wall.cyka.lol

This guide provides step-by-step instructions for deploying the Wall Social Platform to a production server.

## Prerequisites

### Server Requirements

- **Operating System**: Ubuntu Server 20.04+ or Debian 11+
- **RAM**: Minimum 2GB (4GB+ recommended)
- **Storage**: Minimum 20GB (SSD recommended)
- **Domain**: `wall.cyka.lol` with DNS pointing to server IP
- **Root Access**: SSH access with sudo privileges

### Required Software

- Nginx (latest stable)
- PHP 8.1+ with PHP-FPM
- MySQL 8.0+
- Redis
- Certbot (Let's Encrypt)
- Node.js 18+ and npm (for frontend build)
- Composer (for PHP dependencies)
- Git

## Step 1: Server Setup

### 1.1 Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### 1.2 Install Nginx

```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 1.3 Install PHP 8.1 and Extensions

```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis \
    php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd \
    php8.1-bcmath php8.1-intl -y

sudo systemctl enable php8.1-fpm
sudo systemctl start php8.1-fpm
```

### 1.4 Install MySQL

```bash
sudo apt install mysql-server -y
sudo mysql_secure_installation
```

### 1.5 Install Redis

```bash
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 1.6 Install Certbot for SSL

```bash
sudo apt install certbot python3-certbot-nginx -y
```

### 1.7 Install Node.js and npm

```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y
```

### 1.8 Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

## Step 2: Application Deployment

### 2.1 Create Directory Structure

```bash
sudo mkdir -p /var/www/wall.cyka.lol
sudo chown -R $USER:$USER /var/www/wall.cyka.lol
cd /var/www/wall.cyka.lol
```

### 2.2 Clone Repository

```bash
# If using Git
git clone <your-repository-url> .

# Or upload files via SFTP/SCP
```

### 2.3 Set File Permissions

```bash
# Set ownership to www-data (Nginx/PHP-FPM user)
sudo chown -R www-data:www-data /var/www/wall.cyka.lol

# Set directory permissions
sudo find /var/www/wall.cyka.lol -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/wall.cyka.lol -type f -exec chmod 644 {} \;

# Make writable directories for uploads and cache
sudo chmod -R 775 /var/www/wall.cyka.lol/public/uploads
sudo chmod -R 775 /var/www/wall.cyka.lol/public/ai-apps
```

### 2.4 Install PHP Dependencies

```bash
cd /var/www/wall.cyka.lol
composer install --no-dev --optimize-autoloader
```

### 2.5 Build Frontend

```bash
cd /var/www/wall.cyka.lol/frontend

# Update production environment variables
nano .env.production
# Set: VITE_API_BASE_URL=https://wall.cyka.lol/api/v1

# Install dependencies and build
npm install
npm run build

# Copy built files to public directory
cp -r dist/* ../public/
```

## Step 3: Database Setup

### 3.1 Create Database and User

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE wall_social_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wall_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON wall_social_platform.* TO 'wall_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3.2 Configure Database Connection

```bash
cd /var/www/wall.cyka.lol
nano config/database.php
```

Update with production credentials:
```php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'wall_social_platform',
    'username' => 'wall_user',
    'password' => 'your_secure_password_here',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci'
];
```

### 3.3 Run Database Migrations

```bash
cd /var/www/wall.cyka.lol/database
php run_migrations.php
```

## Step 4: Nginx Configuration

### 4.1 Copy Production Configuration

```bash
sudo cp /var/www/wall.cyka.lol/nginx/conf.d/production.conf \
    /etc/nginx/sites-available/wall.cyka.lol
```

### 4.2 Enable Site Configuration

```bash
# Create symbolic link to enable site
sudo ln -s /etc/nginx/sites-available/wall.cyka.lol \
    /etc/nginx/sites-enabled/

# Remove default site if exists
sudo rm -f /etc/nginx/sites-enabled/default
```

### 4.3 Adjust PHP-FPM Socket Path (if needed)

Check PHP-FPM socket location:
```bash
ls -l /run/php/
```

If socket is named differently (e.g., `php8.1-fpm.sock`), update in config:
```bash
sudo nano /etc/nginx/sites-available/wall.cyka.lol
```

Update the `fastcgi_pass` directive:
```nginx
fastcgi_pass unix:/run/php/php8.1-fpm.sock;
```

### 4.4 Test Nginx Configuration

```bash
sudo nginx -t
```

Expected output:
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

## Step 5: SSL Certificate Setup

### 5.1 Obtain SSL Certificate

**IMPORTANT**: Before running Certbot, temporarily comment out SSL directives in Nginx config:

```bash
sudo nano /etc/nginx/sites-available/wall.cyka.lol
```

Comment out the HTTPS server block or SSL certificate lines, then:

```bash
sudo nginx -t && sudo systemctl reload nginx

# Obtain certificate
sudo certbot --nginx -d wall.cyka.lol -d www.wall.cyka.lol
```

Follow the prompts:
- Enter email address for renewal notifications
- Agree to Terms of Service
- Choose whether to redirect HTTP to HTTPS (select Yes)

### 5.2 Restore Full Nginx Configuration

After certificate is obtained, uncomment SSL directives:

```bash
sudo nano /etc/nginx/sites-available/wall.cyka.lol
```

Ensure SSL certificate paths are correct:
```nginx
ssl_certificate /etc/letsencrypt/live/wall.cyka.lol/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/wall.cyka.lol/privkey.pem;
```

### 5.3 Test and Reload Nginx

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 5.4 Verify SSL Auto-Renewal

```bash
# Test renewal process (dry run)
sudo certbot renew --dry-run

# Check Certbot timer
sudo systemctl status certbot.timer
```

## Step 6: Redis Configuration

### 6.1 Configure Redis (Optional)

```bash
sudo nano /etc/redis/redis.conf
```

Recommended settings:
```
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### 6.2 Restart Redis

```bash
sudo systemctl restart redis-server
```

## Step 7: PHP-FPM Optimization

### 7.1 Configure PHP-FPM Pool

```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Recommended settings for production:
```ini
user = www-data
group = www-data
listen = /run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500
```

### 7.2 Configure PHP Settings

```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

Important settings:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

### 7.3 Restart PHP-FPM

```bash
sudo systemctl restart php8.1-fpm
```

## Step 8: Background Worker Setup

### 8.1 Create Systemd Service for AI Worker

```bash
sudo nano /etc/systemd/system/wall-ai-worker.service
```

Add content:
```ini
[Unit]
Description=Wall Social Platform AI Generation Worker
After=network.target mysql.service redis.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/wall.cyka.lol
ExecStart=/usr/bin/php /var/www/wall.cyka.lol/workers/ai_generation_worker.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### 8.2 Enable and Start Worker

```bash
sudo systemctl daemon-reload
sudo systemctl enable wall-ai-worker
sudo systemctl start wall-ai-worker
sudo systemctl status wall-ai-worker
```

## Step 9: Firewall Configuration

### 9.1 Configure UFW (Ubuntu Firewall)

```bash
# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
sudo ufw status
```

## Step 10: Verification and Testing

### 10.1 Check Service Status

```bash
# Check all services
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
sudo systemctl status wall-ai-worker
```

### 10.2 Test Website Access

```bash
# Test HTTP (should redirect to HTTPS)
curl -I http://wall.cyka.lol

# Test HTTPS
curl -I https://wall.cyka.lol

# Test API endpoint
curl https://wall.cyka.lol/api/v1/health
```

### 10.3 Check Nginx Logs

```bash
# Access logs
sudo tail -f /var/log/nginx/wall_access.log

# Error logs
sudo tail -f /var/log/nginx/wall_error.log
```

### 10.4 Check PHP-FPM Logs

```bash
sudo tail -f /var/log/php8.1-fpm.log
```

### 10.5 Browser Testing

1. Visit `https://wall.cyka.lol` - should load Vue SPA
2. Check SSL certificate (green padlock in browser)
3. Test user registration and login
4. Test file upload functionality
5. Test AI generation features
6. Check real-time SSE updates

## Step 11: Monitoring Setup

### 11.1 Set Up Log Rotation

```bash
sudo nano /etc/logrotate.d/nginx
```

Ensure it contains:
```
/var/log/nginx/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 `cat /var/run/nginx.pid`
    endscript
}
```

### 11.2 Monitor Server Resources

```bash
# Install htop for easy monitoring
sudo apt install htop -y

# Check disk usage
df -h

# Check memory usage
free -h

# Monitor processes
htop
```

## Step 12: Backup Configuration

### 12.1 Database Backup Script

```bash
sudo nano /usr/local/bin/backup-wall-db.sh
```

Add content:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/wall"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

mysqldump -u wall_user -p'your_password' wall_social_platform \
    | gzip > $BACKUP_DIR/wall_db_$DATE.sql.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -name "wall_db_*.sql.gz" -mtime +7 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-wall-db.sh
```

### 12.2 Schedule Daily Backups

```bash
sudo crontab -e
```

Add:
```
0 2 * * * /usr/local/bin/backup-wall-db.sh
```

## Troubleshooting

### Common Issues

#### 502 Bad Gateway
- Check PHP-FPM is running: `sudo systemctl status php8.1-fpm`
- Check socket path in Nginx config matches actual socket
- Check PHP-FPM error logs: `sudo tail -f /var/log/php8.1-fpm.log`

#### 404 Errors for Static Assets
- Verify file permissions: `ls -la /var/www/wall.cyka.lol/public`
- Check Nginx error logs: `sudo tail -f /var/log/nginx/wall_error.log`
- Ensure frontend was built correctly

#### Database Connection Errors
- Verify MySQL is running: `sudo systemctl status mysql`
- Check database credentials in `config/database.php`
- Test connection: `mysql -u wall_user -p wall_social_platform`

#### SSL Certificate Issues
- Check certificate files exist: `ls -l /etc/letsencrypt/live/wall.cyka.lol/`
- Test renewal: `sudo certbot renew --dry-run`
- Check Certbot logs: `sudo tail -f /var/log/letsencrypt/letsencrypt.log`

### Useful Commands

```bash
# Reload Nginx without downtime
sudo systemctl reload nginx

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Check Nginx configuration syntax
sudo nginx -t

# Monitor real-time logs
sudo tail -f /var/log/nginx/wall_error.log

# Check disk space
df -h

# Check memory usage
free -h

# List running processes
ps aux | grep php
ps aux | grep nginx
```

## Security Best Practices

1. **Keep software updated**:
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Use strong passwords** for database and system users

3. **Disable root SSH login**:
   ```bash
   sudo nano /etc/ssh/sshd_config
   # Set: PermitRootLogin no
   sudo systemctl restart sshd
   ```

4. **Enable automatic security updates**:
   ```bash
   sudo apt install unattended-upgrades -y
   sudo dpkg-reconfigure -plow unattended-upgrades
   ```

5. **Monitor logs regularly** for suspicious activity

6. **Set up fail2ban** to prevent brute force attacks:
   ```bash
   sudo apt install fail2ban -y
   ```

## Maintenance Schedule

### Daily
- Monitor error logs
- Check service status
- Review system resources

### Weekly
- Review access logs for anomalies
- Check disk space usage
- Verify backups are running

### Monthly
- Update system packages
- Review SSL certificate expiration
- Test backup restoration
- Review and optimize database

## Rollback Procedure

If deployment fails or issues occur:

1. **Restore Nginx config**:
   ```bash
   sudo cp /etc/nginx/sites-available/wall.cyka.lol.backup \
       /etc/nginx/sites-available/wall.cyka.lol
   sudo systemctl reload nginx
   ```

2. **Restore database**:
   ```bash
   gunzip < /var/backups/wall/wall_db_YYYYMMDD_HHMMSS.sql.gz \
       | mysql -u wall_user -p wall_social_platform
   ```

3. **Restore application files**:
   ```bash
   cd /var/www
   sudo mv wall.cyka.lol wall.cyka.lol.failed
   sudo mv wall.cyka.lol.backup wall.cyka.lol
   ```

## Support and Resources

- **Nginx Documentation**: https://nginx.org/en/docs/
- **PHP Documentation**: https://www.php.net/docs.php
- **Let's Encrypt**: https://letsencrypt.org/docs/
- **Ubuntu Server Guide**: https://ubuntu.com/server/docs

## Success Checklist

- [ ] All services running (Nginx, PHP-FPM, MySQL, Redis)
- [ ] Website accessible via HTTPS with valid certificate
- [ ] SSL Labs grade A or higher
- [ ] API endpoints responding correctly
- [ ] Frontend SPA routing working
- [ ] File uploads functional
- [ ] AI generation features working
- [ ] Real-time SSE updates functioning
- [ ] Logs rotating properly
- [ ] Backups configured and tested
- [ ] Firewall configured correctly
- [ ] Monitoring in place
- [ ] Documentation updated
