# 📚 окументация по развертыванию Wall Social Platform

## Содержание

### 1. [Полное руководство по развертыванию](./DEPLOYMENT_GUIDE_RU.md)
одробная пошаговая инструкция по развертыванию приложения на Ubuntu Server для домена wall.cyka.lol

**ключает:**
- Системные требования
- одготовка сервера
- становка зависимостей (Docker, Node.js, Certbot)
- астройка домена и SSL сертификатов
- азвертывание приложения
- правление и мониторинг
- странение неполадок
- езопасность

### 2. [ыстрый старт](./QUICK_START.md)
раткая инструкция для быстрого развертывания

**сновные шаги:**
- одготовка сервера
- становка Docker и зависимостей
- лонирование и настройка проекта
- апуск приложения
- астройка SSL

---

## рхитектура приложения

### Технологический стек

- **Backend**: PHP 8.2+ (PHP-FPM)
- **Frontend**: Vue.js 3 + TypeScript + Vite
- **Database**: MySQL 8.0
- **Cache & Queue**: Redis
- **Web Server**: Nginx
- **AI Engine**: Ollama + DeepSeek Coder
- **Containerization**: Docker + Docker Compose

### Сервисы

1. **nginx** - еб-сервер (порты 80, 443)
2. **php** - PHP-FPM для обработки API запросов
3. **mysql** - аза данных
4. **redis** - эш и очереди задач
5. **ollama** - AI сервер для генерации контента
6. **queue_worker** - бработчик очереди задач

---

## ыстрые команды

### правление контейнерами

```bash
# Статус
docker compose ps

# апуск
docker compose up -d

# становка
docker compose down

# ерезапуск
docker compose restart

# оги
docker compose logs -f
```

### бслуживание

```bash
# экап 
docker compose exec mysql mysqldump -u root -p wall_social_platform > backup.sql

# чистка Docker
docker system prune -a

# птимизация MySQL
docker compose exec mysql mysqlcheck -u root -p --optimize --all-databases
```

### ониторинг

```bash
# спользование ресурсов
docker stats

# исковое пространство
df -h

# амять
free -h

# оги Nginx
docker compose logs nginx --tail=100
```

---

## Структура проекта

```
wall-social-platform/
├── config/              # онфигурация приложения
│   ├── config.php
│   └── database.php
├── database/            # аза данных
│   ├── migrations/      # играции
│   ├── schema.sql       # сновная схема
│   └── run_migrations.php
├── docker/              # Docker конфигурация
│   └── php/
│       ├── Dockerfile
│       └── php.ini
├── frontend/            # Vue.js приложение
│   ├── src/
│   ├── .env.production
│   └── vite.config.ts
├── nginx/               # Nginx конфигурация
│   └── conf.d/
│       └── default.conf
├── public/              # убличная директория
│   ├── assets/          # Скомпилированные ассеты
│   ├── uploads/         # агруженные файлы
│   ├── ai-apps/         # AI-генерированные приложения
│   └── index.html
├── src/                 # Backend PHP код
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Utils/
├── workers/             # оркеры
│   └── ai_generation_worker.php
├── ssl/                 # SSL сертификаты
└── docker-compose.yml   # Docker Compose конфигурация
```

---

## Системные требования

### инимальные
- Ubuntu Server 20.04+
- 2 CPU ядра
- 4 GB RAM
- 40 GB SSD
- Статический IP

### екомендуемые
- Ubuntu Server 22.04 LTS
- 4 CPU ядра
- 16 GB RAM
- 100 GB SSD
- омен с настроенным DNS

---

## орты

| орт | Сервис | оступ |
|------|--------|--------|
| 80 | HTTP | убличный |
| 443 | HTTPS | убличный |
| 3306 | MySQL | Localhost |
| 6379 | Redis | Localhost |
| 9000 | PHP-FPM | Internal |
| 11434 | Ollama | Localhost |

---

## езопасность

### еклист

- [ ] зменены все пароли по умолчанию
- [ ] астроен файрвол (UFW)
- [ ] становлен SSL сертификат
- [ ] астроено автообновление сертификатов
- [ ] астроены регулярные бэкапы
- [ ] становлен fail2ban
- [ ] граничен доступ к портам  и Redis
- [ ] ключена ротация логов Docker

---

## оддержка

### роверка статуса

- **риложение**: https://wall.cyka.lol
- **API Health**: https://wall.cyka.lol/api/v1/health
- **Health Check**: https://wall.cyka.lol/health

### оги

```bash
# се логи
docker compose logs -f

# онкретный сервис
docker compose logs -f nginx
docker compose logs -f php
docker compose logs -f mysql
docker compose logs -f ollama
```

### странение неполадок

См. раздел "странение неполадок" в [полном руководстве](./DEPLOYMENT_GUIDE_RU.md)

---

## бновления

### роцесс обновления

1. Создание бэкапа
2. олучение нового кода (git pull)
3. ересборка фронтенда (если нужно)
4. бновление контейнеров
5. апуск миграций (если есть)
6. роверка работоспособности

етальная инструкция в [полном руководстве](./DEPLOYMENT_GUIDE_RU.md)

---

**оследнее обновление:** 04.11.2025
**ерсия документации:** 1.0

