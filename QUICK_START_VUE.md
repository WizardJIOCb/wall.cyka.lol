# 🚀 Quick Start Guide - Vue.js Frontend

## Доступ к приложению

### 🌐 Основное приложение (Vue.js)
**URL**: `http://localhost:8080`

**Страницы**:
- **Login**: `http://localhost:8080/login`
- **Register**: `http://localhost:8080/register`
- **Home Feed**: `http://localhost:8080/` (требуется авторизация)
- **My Wall**: `http://localhost:8080/wall`
- **Profile**: `http://localhost:8080/profile`
- **AI Generate**: `http://localhost:8080/ai`
- **Messages**: `http://localhost:8080/messages`
- **Settings**: `http://localhost:8080/settings`

### 🔌 Backend API
**URL**: `http://localhost:8080/api/v1/`  
**Health Check**: `http://localhost:8080/health`

---

## 📦 Что установлено

### Frontend (Vue.js 3)
- ✅ **Framework**: Vue 3 с Composition API
- ✅ **Language**: TypeScript
- ✅ **Build Tool**: Vite 5.4
- ✅ **Router**: Vue Router 4
- ✅ **State**: Pinia
- ✅ **HTTP**: Axios
- ✅ **Utils**: VueUse, Day.js
- ✅ **Themes**: 6 тем (Light, Dark, Green, Cream, Blue, High Contrast)

### Backend (PHP 8.2+)
- ✅ **77 API endpoints**
- ✅ **MySQL 8.0** database
- ✅ **Redis** для кеширования
- ✅ **Ollama** для AI генерации
- ✅ **Nginx** web server

---

## 🛠 Разработка

### Запуск Dev Server (Hot Reload)
```bash
cd C:\Projects\wall.cyka.lol\frontend
npm run dev
```
**Откроется**: `http://localhost:3000`  
**Преимущества**:
- Мгновенное обновление при изменениях
- Source maps для отладки
- API proxy на `:8080`

### Production Build
```bash
cd C:\Projects\wall.cyka.lol\frontend
npm run build
```
**Результат**: Собирается в `C:\Projects\wall.cyka.lol\public`

### Перезапуск Nginx после сборки
```bash
cd C:\Projects\wall.cyka.lol
docker-compose restart nginx
```

---

## 🐳 Docker Commands

### Запуск всех сервисов
```bash
cd C:\Projects\wall.cyka.lol
docker-compose up -d
```

### Просмотр статуса
```bash
docker-compose ps
```

### Просмотр логов
```bash
# Все логи
docker-compose logs -f

# Только nginx
docker-compose logs -f nginx

# Только PHP
docker-compose logs -f php
```

### Перезапуск сервиса
```bash
docker-compose restart nginx
docker-compose restart php
```

### Остановка всех сервисов
```bash
docker-compose down
```

### Полная пересборка
```bash
docker-compose down
docker-compose build
docker-compose up -d
```

---

## 🔧 Структура проекта

```
C:\Projects\wall.cyka.lol\
│
├── frontend/              # ← Vue.js source code (разработка)
│   ├── src/
│   │   ├── components/   # Vue компоненты
│   │   ├── views/        # Страницы
│   │   ├── stores/       # Pinia stores
│   │   ├── router/       # Роутинг
│   │   └── services/     # API сервисы
│   ├── package.json
│   └── vite.config.ts
│
├── public/               # ← Production build (работающее приложение)
│   ├── index.html        # Vue SPA entry point
│   ├── api.php           # Backend API
│   └── assets/           # Собранные файлы Vue
│
├── src/                  # PHP backend
├── config/               # Конфигурация
├── nginx/conf.d/         # Nginx config
└── docker-compose.yml    # Docker setup
```

---

## 📝 Типичные задачи

### 1. Изменить код Vue компонента
```bash
1. Открыть файл в frontend/src/
2. Изменить код
3. Сохранить (если dev server запущен, изменения применятся автоматически)
4. Если нужно в production: npm run build
```

### 2. Добавить новую страницу
```bash
1. Создать файл в frontend/src/views/NewView.vue
2. Добавить роут в frontend/src/router/index.ts
3. npm run build (для production)
```

### 3. Изменить API endpoint
```bash
1. Открыть public/api.php
2. Добавить новый route
3. Перезапустить: docker-compose restart php
```

### 4. Добавить новый Vue компонент
```bash
1. Создать файл в frontend/src/components/
2. Импортировать в нужной view
3. npm run build (для production)
```

### 5. Изменить стили/тему
```bash
1. Открыть frontend/src/assets/styles/
2. Изменить нужный файл
3. npm run build
```

---

## 🎨 Темы

Доступно 6 тем:
1. **Light** (по умолчанию) - светлая
2. **Dark** - тёмная
3. **Green** - зелёная
4. **Cream** - кремовая
5. **Blue** - синяя
6. **High Contrast** - высокая контрастность

**Переключение темы**: В настройках (Settings) → Appearance

---

## 🔐 Тестовый доступ

### Создание тестового пользователя
1. Открыть `http://localhost:8080/register`
2. Заполнить форму регистрации
3. Войти в систему

### Или через API
```bash
curl -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123",
    "display_name": "Test User"
  }'
```

---

## 🐛 Устранение проблем

### Проблема: "Cannot connect to backend"
**Решение**:
```bash
# Проверить, что все контейнеры запущены
docker-compose ps

# Перезапустить nginx
docker-compose restart nginx

# Проверить логи
docker-compose logs nginx
```

### Проблема: "Page not found" на Vue роутах
**Решение**:
```bash
# Проверить nginx конфигурацию
cat nginx/conf.d/default.conf | grep "try_files"
# Должно быть: try_files $uri $uri/ /index.html;

# Перезапустить nginx
docker-compose restart nginx
```

### Проблема: Изменения не применяются
**Решение**:
```bash
# Очистить кеш браузера (Ctrl+Shift+R)

# Пересобрать Vue
cd frontend
npm run build
cd ..
docker-compose restart nginx
```

### Проблема: API возвращает 404
**Решение**:
```bash
# Проверить, что api.php существует
ls public/api.php

# Проверить права доступа
docker-compose exec php ls -la /var/www/html/public/api.php

# Перезапустить PHP
docker-compose restart php
```

---

## 📊 Мониторинг

### Проверка здоровья системы
```bash
# Health check
curl http://localhost:8080/health

# API info
curl http://localhost:8080/api/v1

# Nginx status (внутри контейнера)
docker-compose exec nginx curl http://localhost/nginx_status
```

### Просмотр логов в реальном времени
```bash
# Все сервисы
docker-compose logs -f

# Только ошибки
docker-compose logs -f | grep -i error
```

---

## 📚 Дополнительные ресурсы

### Документация
- **Vue 3**: https://vuejs.org
- **Vue Router**: https://router.vuejs.org
- **Pinia**: https://pinia.vuejs.org
- **Vite**: https://vitejs.dev

### Файлы документации проекта
- `VUE_FRONTEND_COMPLETE.md` - Полная документация миграции
- `.qoder/quests/vue-frontend-development.md` - Design document

---

## ✅ Checklist для запуска

- [x] Docker Desktop запущен
- [x] Все контейнеры работают (`docker-compose ps`)
- [x] Nginx перезапущен после последнего билда
- [x] Открыт `http://localhost:8080`
- [x] Vue приложение загружается
- [x] API доступен на `/api/v1/`

---

## 🎯 Быстрый старт за 3 шага

```bash
# 1. Убедиться что Docker запущен
docker-compose ps

# 2. Если нужен rebuild Vue
cd frontend && npm run build && cd ..

# 3. Перезапустить nginx
docker-compose restart nginx

# 4. Открыть браузер
start http://localhost:8080
```

**Готово!** 🎉

---

**Последнее обновление**: 2025-11-01  
**Статус**: ✅ Production Ready  
**Версия**: 1.0.0
