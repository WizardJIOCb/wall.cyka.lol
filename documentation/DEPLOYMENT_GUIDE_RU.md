# 🚀 уководство по развертыванию Wall Social Platform на Ubuntu Server

## 📋 главление

1. [Системные требования](#системные-требования)
2. [одготовка сервера](#подготовка-сервера)
3. [становка зависимостей](#установка-зависимостей)
4. [астройка домена и SSL](#настройка-домена-и-ssl)
5. [азвертывание приложения](#развертывание-приложения)
6. [апуск и управление](#запуск-и-управление)
7. [ониторинг и обслуживание](#мониторинг-и-обслуживание)
8. [странение неполадок](#устранение-неполадок)

---

## 🖥️ Системные требования

### инимальные требования

- **С**: Ubuntu Server 20.04 LTS или выше
- **CPU**: 2 ядра
- **RAM**: 4 GB (рекомендуется 8 GB для AI функций)
- **иск**: 40 GB SSD
- **Сеть**: Статический IP адрес

### екомендуемые требования

- **С**: Ubuntu Server 22.04 LTS
- **CPU**: 4 ядра
- **RAM**: 16 GB
- **иск**: 100 GB SSD
- **Сеть**: Статический IP адрес + домен

---

## 🔧 одготовка сервера

### Шаг 1: одключение к серверу

```bash
ssh root@your_server_ip
```

### Шаг 2: бновление системы

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y curl wget git vim ufw software-properties-common
```

### Шаг 3: астройка файрвола

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

## 📦 становка зависимостей

### Docker

```bash
# даление старых версий
sudo apt remove docker docker-engine docker.io containerd runc

# становка Docker
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

sudo usermod -aG docker $USER
newgrp docker

docker --version
docker compose version
```

### Node.js для сборки фронтенда

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

### Certbot для SSL

```bash
sudo apt install -y certbot python3-certbot-nginx
```

---

## 🌐 астройка DNS

 панели управления вашего регистратора доменов добавьте DNS записи:

```
Тип: A
мя: @
начение: YOUR_SERVER_IP

Тип: A
мя: www
начение: YOUR_SERVER_IP

Тип: A
мя: api
начение: YOUR_SERVER_IP
```

роверка:
```bash
nslookup wall.cyka.lol
dig wall.cyka.lol +short
```


---

## 🚀 азвертывание приложения

### Шаг 1: лонирование проекта

```bash
cd ~
git clone https://github.com/yourusername/wall-social-platform.git
cd wall-social-platform
```

### Шаг 2: астройка docker-compose.yml

**: змените пароли!**

```bash
nano docker-compose.yml
```

змените:
- `MYSQL_ROOT_PASSWORD`: укажите надежный пароль
- `MYSQL_PASSWORD`: укажите надежный пароль для wall_user
- бновите эти же пароли в секции `php` и `queue_worker`

### Шаг 3: бновление Nginx конфигурации

```bash
nano nginx/conf.d/default.conf
```

бедитесь что `server_name` указан как: `wall.cyka.lol www.wall.cyka.lol`

### Шаг 4: Сборка фронтенда

```bash
cd frontend
npm install

# бновите .env.production
nano .env.production
```

становите:
```env
VITE_API_BASE_URL=/api/v1
VITE_WS_URL=wss://wall.cyka.lol
VITE_OLLAMA_URL=https://wall.cyka.lol:11434
VITE_ENABLE_ANALYTICS=true
```

Соберите:
```bash
npm run build
cd ..
```

### Шаг 5: Создание директорий

```bash
mkdir -p public/uploads public/ai-apps ssl
chmod -R 755 public/uploads public/ai-apps
```

### Шаг 6: апуск приложения

```bash
docker compose up -d --build
docker compose ps
docker compose logs -f
```

### Шаг 7: нициализация базы данных

играции запускаются автоматически через schema.sql

ля ручного запуска:
```bash
docker compose exec php bash
cd /var/www/html/database
php run_migrations.php
exit
```

### Шаг 8: становка AI модели Ollama

```bash
docker compose exec ollama ollama pull deepseek-coder:6.7b
docker compose exec ollama ollama list
```

### Шаг 9: олучение SSL сертификата

```bash
# становите Nginx
docker compose stop nginx

# олучите сертификат
sudo certbot certonly --standalone -d wall.cyka.lol -d www.wall.cyka.lol

# Скопируйте сертификаты
sudo cp /etc/letsencrypt/live/wall.cyka.lol/fullchain.pem ssl/
sudo cp /etc/letsencrypt/live/wall.cyka.lol/privkey.pem ssl/
sudo chmod 644 ssl/*.pem
```

### Шаг 10: астройка HTTPS в Nginx

```bash
nano nginx/conf.d/default.conf
```

обавьте редирект и SSL конфигурацию:

```nginx
server {
    listen 80;
    server_name wall.cyka.lol www.wall.cyka.lol;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name wall.cyka.lol www.wall.cyka.lol;
    
    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    root /var/www/html/public;
    # ... остальная конфигурация
}
```

ерезапустите:
```bash
docker compose up -d nginx
```

### Шаг 11: втообновление SSL

```bash
sudo crontab -e
```

обавьте:
```
0 3 1 */2 * certbot renew --quiet && cp /etc/letsencrypt/live/wall.cyka.lol/*.pem ~/wall-social-platform/ssl/ && docker compose -f ~/wall-social-platform/docker-compose.yml restart nginx
```


---

## 🎛️ апуск и управление

### сновные команды

```bash
# апуск
docker compose up -d

# становка
docker compose down

# ерезапуск
docker compose restart

# ерезапуск конкретного сервиса
docker compose restart nginx

# оги
docker compose logs -f
docker compose logs -f nginx

# Статус
docker compose ps

# бновление
docker compose pull
docker compose up -d --build
```

### езервное копирование

#### аза данных
```bash
# Создание бэкапа
docker compose exec mysql mysqldump -u root -p wall_social_platform > backup_$(date +%Y%m%d).sql

# осстановление
docker compose exec -T mysql mysql -u root -p wall_social_platform < backup_20241104.sql
```

#### айлы
```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz public/uploads/
```

#### втобэкап скрипт

Создайте `/home/wall/backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/home/wall/backups"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# аза данных
docker compose -f ~/wall-social-platform/docker-compose.yml exec -T mysql \
  mysqldump -u root -pYOUR_ROOT_PASSWORD wall_social_platform > $BACKUP_DIR/db_$DATE.sql

# айлы
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz -C ~/wall-social-platform/public uploads

# даление старых (>30 дней)
find $BACKUP_DIR -mtime +30 -delete

echo "Backup completed: $DATE"
```

Сделайте исполняемым и добавьте в cron:
```bash
chmod +x /home/wall/backup.sh
crontab -e
# обавьте: 0 2 * * * /home/wall/backup.sh >> /home/wall/backup.log 2>&1
```

### бновление приложения

```bash
# 1. экап
./backup.sh

# 2. бновление кода
git pull origin main

# 3. ересборка фронтенда (если нужно)
cd frontend
npm install
npm run build
cd ..

# 4. ерезапуск
docker compose down
docker compose up -d --build

# 5. играции (если есть)
docker compose exec php php /var/www/html/database/run_migrations.php

# 6. роверка
docker compose ps
```


---

## 📊 ониторинг

### роверка здоровья

```bash
# Статус контейнеров
docker compose ps

# спользование ресурсов
docker stats --no-stream

# исковое пространство
df -h

# амять
free -h

# оги
docker compose logs nginx --tail=100
docker compose logs php --tail=100
```

### чистка и оптимизация

```bash
# чистка Docker образов
docker image prune -a

# чистка volumes
docker volume prune

# птимизация MySQL
docker compose exec mysql mysqlcheck -u root -p --optimize --all-databases
```

---

## 🔧 странение неполадок

### онтейнеры не запускаются

```bash
docker compose logs
docker compose config
docker compose down
docker compose build --no-cache
docker compose up -d
```

### шибки 

```bash
docker compose ps mysql
docker compose logs mysql
docker compose exec mysql mysql -u root -p
```

### 502 Bad Gateway

```bash
docker compose ps php
docker compose logs php nginx
docker compose restart php nginx
```

### SSL не работает

```bash
ls -la ssl/
docker compose exec nginx nginx -t
docker compose logs nginx
sudo certbot renew --force-renewal
```

### AI генерация не работает

```bash
docker compose ps ollama
docker compose logs ollama
curl http://localhost:11434/api/tags
docker compose restart ollama
docker compose exec ollama ollama pull deepseek-coder:6.7b
```

### чередь не обрабатывается

```bash
docker compose ps queue_worker
docker compose logs queue_worker
docker compose exec redis redis-cli ping
docker compose restart queue_worker
```

### ысокое использование диска

```bash
# азмер логов
du -sh /var/lib/docker/containers/*/*-json.log

# чистка
sudo sh -c "truncate -s 0 /var/lib/docker/containers/*/*-json.log"

# астройка ротации логов
sudo nano /etc/docker/daemon.json
```

обавьте:
```json
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
```

ерезапустите Docker:
```bash
sudo systemctl restart docker
```

---

## 🔒 езопасность

### екомендации

1. змените все пароли в docker-compose.yml
2. астройте файрвол (ufw)
3. егулярно обновляйте систему
4. спользуйте SSL/TLS
5. астройте регулярные бэкапы
6. граничьте доступ к портам MySQL/Redis

### становка fail2ban

```bash
sudo apt install fail2ban
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local

# ключите [nginx-http-auth] и [nginx-botsearch]

sudo systemctl restart fail2ban
```

---

## 📝 олезная информация

### Ссылки

- **риложение**: https://wall.cyka.lol
- **API**: https://wall.cyka.lol/api/v1
- **Health check**: https://wall.cyka.lol/health

### орты

- **80**: HTTP (→ HTTPS)
- **443**: HTTPS
- **3306**: MySQL (localhost)
- **6379**: Redis (localhost)
- **11434**: Ollama (localhost)

### роверка работы

```bash
# лавная страница
curl -I https://wall.cyka.lol

# API
curl https://wall.cyka.lol/api/v1/health

# се сервисы
docker compose ps
```

---

## ✅ отово!

риложение развернуто и работает на **https://wall.cyka.lol**

сли возникли проблемы, проверьте раздел "странение неполадок" или логи контейнеров.

