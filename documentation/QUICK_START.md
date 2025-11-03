# 🚀 ыстрый старт - азвертывание на Ubuntu Server

## раткая инструкция для wall.cyka.lol

### 1. одготовка сервера

```bash
# одключение
ssh root@your_server_ip

# бновление
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git vim ufw

# айрвол
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. становка Docker

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
newgrp docker
```

### 3. становка Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 4. становка Certbot

```bash
sudo apt install -y certbot
```

### 5. лонирование проекта

```bash
cd ~
git clone YOUR_REPO_URL wall-social-platform
cd wall-social-platform
```

### 6. астройка конфигурации

**docker-compose.yml:**
- змените `MYSQL_ROOT_PASSWORD`
- змените `MYSQL_PASSWORD` (в mysql, php, queue_worker)

**nginx/conf.d/default.conf:**
- `server_name wall.cyka.lol www.wall.cyka.lol;`

**frontend/.env.production:**
```env
VITE_API_BASE_URL=/api/v1
VITE_WS_URL=wss://wall.cyka.lol
VITE_OLLAMA_URL=https://wall.cyka.lol:11434
```

### 7. Сборка фронтенда

```bash
cd frontend
npm install
npm run build
cd ..
```

### 8. апуск приложения

```bash
mkdir -p public/uploads public/ai-apps ssl
docker compose up -d --build
docker compose logs -f
```

### 9. становка AI модели

```bash
docker compose exec ollama ollama pull deepseek-coder:6.7b
```

### 10. SSL сертификат

```bash
# становка Nginx
docker compose stop nginx

# олучение сертификата
sudo certbot certonly --standalone -d wall.cyka.lol -d www.wall.cyka.lol

# опирование
sudo cp /etc/letsencrypt/live/wall.cyka.lol/fullchain.pem ssl/
sudo cp /etc/letsencrypt/live/wall.cyka.lol/privkey.pem ssl/
sudo chmod 644 ssl/*.pem

# бновите nginx/conf.d/default.conf для HTTPS
# апустите: docker compose up -d nginx
```

### 11. роверка

```bash
docker compose ps
curl -I https://wall.cyka.lol
```

## правление

```bash
# апуск
docker compose up -d

# становка
docker compose down

# ерезапуск
docker compose restart

# оги
docker compose logs -f

# экап 
docker compose exec mysql mysqldump -u root -p wall_social_platform > backup.sql
```

## омощь

олная документация: [DEPLOYMENT_GUIDE_RU.md](./DEPLOYMENT_GUIDE_RU.md)

