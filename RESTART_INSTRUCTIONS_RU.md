# 🔄 Инструкция по перезапуску сервера

## Проблема

Вы видите **старый vanilla JavaScript frontend**, потому что браузер **кэшировал** старые файлы. Несмотря на то, что мы удалили старые файлы и собрали новое Vue.js приложение, браузер всё ещё показывает кэшированную версию.

---

## ✅ Решение (3 простых шага)

### Шаг 1: Полная остановка всех сервисов

```bash
cd C:\Projects\wall.cyka.lol
docker-compose down
```

**Результат**: Все контейнеры (nginx, php, mysql, redis, ollama) остановлены.

---

### Шаг 2: Запуск всех сервисов

```bash
cd C:\Projects\wall.cyka.lol
docker-compose up -d
```

**Ожидайте**: 10-15 секунд для полного запуска.

**Проверка статуса**:
```bash
docker-compose ps
```

**Все контейнеры должны быть**: `Up` или `Healthy`

---

### Шаг 3: Очистить кэш браузера

#### 🚀 Метод 1: Жёсткая перезагрузка (Быстрый)

Откройте браузер на `http://localhost:8080` и нажмите:

**Windows/Linux**:
```
Ctrl + Shift + R
```

**macOS**:
```
Cmd + Shift + R
```

---

#### 🔥 Метод 2: Полная очистка кэша (Рекомендуется)

**Google Chrome / Microsoft Edge**:
1. Нажмите `Ctrl + Shift + Delete`
2. Выберите **"Всё время"** (All time)
3. Отметьте **"Изображения и файлы, сохранённые в кеше"**
4. Нажмите **"Удалить данные"**
5. Обновите страницу: `F5`

**Firefox**:
1. Нажмите `Ctrl + Shift + Delete`
2. Выберите **"Всё"** (Everything)
3. Отметьте **"Кэш"**
4. Нажмите **"Удалить сейчас"**
5. Обновите: `F5`

---

#### 👻 Метод 3: Режим Инкогнито (Самый надёжный)

**Chrome/Edge**:
```
Ctrl + Shift + N
```

**Firefox**:
```
Ctrl + Shift + P
```

Затем откройте: `http://localhost:8080`

---

## 🎯 Как проверить что загружается Vue.js

### ❌ Старый frontend (Vanilla JS):
- Форма входа с заголовком "Welcome Back"
- Кнопки OAuth с иконками (G, Я, ✈️)
- Старый дизайн

### ✅ Новый frontend (Vue.js):
- **Современная форма входа**
- **Более чистый дизайн**
- В консоли браузера (F12): НЕ должно быть "Initializing Wall Social Platform..."
- В Network (F12): Должны загружаться файлы:
  - `index-CCsWTdYX.js`
  - `vendor-DTOKxEoQ.js`
  - `utils-i9HKr1_Q.js`

---

## 🔍 Проверка в браузере

1. **Откройте** `http://localhost:8080`
2. **Нажмите** `F12` (Инструменты разработчика)
3. **Перейдите** на вкладку **Console**
4. **Обновите** страницу `Ctrl + R`
5. **Проверьте** что загружаются Vue файлы

### В Console должно быть:
- ❌ НЕТ: "Initializing Wall Social Platform..."
- ✅ ДА: Сообщения от Vue или пусто

### Во вкладке Network:
- Кликните на `index.html`
- Проверьте Response
- Должно быть: `<div id="app"></div>`

---

## 🐳 Команды Docker

### Полный перезапуск:
```bash
cd C:\Projects\wall.cyka.lol
docker-compose down
docker-compose up -d
```

### Проверить статус:
```bash
docker-compose ps
```

### Посмотреть логи:
```bash
# Все логи
docker-compose logs -f

# Только nginx
docker-compose logs -f nginx

# Только PHP
docker-compose logs -f php
```

### Перезапустить только nginx:
```bash
docker-compose restart nginx
```

---

## 🛠️ Если всё ещё видите старый frontend

### 1. Проверьте файлы
```bash
cd C:\Projects\wall.cyka.lol\public
Get-ChildItem -Name
```

**Должно быть**:
- ✅ `index.html` (Vue SPA)
- ✅ `api.php` (Backend)
- ✅ `assets/` (Vue bundles)
- ❌ НЕТ `app.html` (старый файл)
- ❌ НЕТ `login.html` (старый файл)

### 2. Пересоберите Vue приложение
```bash
cd C:\Projects\wall.cyka.lol\frontend
npm run build
cd ..
docker-compose restart nginx
```

### 3. Очистите DNS кэш
```bash
ipconfig /flushdns
```

### 4. Полностью закройте браузер
- Закройте ВСЕ окна браузера
- Откройте заново
- Попробуйте `http://localhost:8080`

---

## 📋 Чек-лист проверки

После очистки кэша проверьте:

- [ ] Браузер в режиме Инкогнито ИЛИ кэш полностью очищен
- [ ] URL точно `http://localhost:8080`
- [ ] Все Docker контейнеры запущены (`docker-compose ps`)
- [ ] F12 → Console НЕ показывает "Initializing Wall Social Platform"
- [ ] F12 → Network показывает загрузку Vue файлов
- [ ] Страница входа выглядит по-другому/современнее
- [ ] Нет файлов `app.html` или старых JS в `/public`

---

## 🎯 Быстрая команда для полного перезапуска

```bash
cd C:\Projects\wall.cyka.lol; `
docker-compose down; `
Start-Sleep -Seconds 3; `
docker-compose up -d; `
Write-Host "Waiting for services to start..."; `
Start-Sleep -Seconds 10; `
docker-compose ps; `
Write-Host "`n✅ Services started!`n"; `
Write-Host "Now open http://localhost:8080 in INCOGNITO mode and press Ctrl+Shift+R"
```

---

## 📞 Если проблема не решилась

1. **Сделайте скриншот** того, что видите в браузере
2. **Откройте F12** → Console → сделайте скриншот
3. **Проверьте** `docker-compose ps` → скопируйте вывод
4. **Проверьте** содержимое `/public` → покажите список файлов

---

## ✅ Успешный результат

После правильного перезапуска вы должны увидеть:

1. **Vue.js приложение** на `http://localhost:8080`
2. **Современный дизайн** страницы входа
3. **Нет старых сообщений** в консоли
4. **Все контейнеры работают** (`docker-compose ps`)
5. **API доступен** на `http://localhost:8080/api/v1/`

---

**Последнее обновление**: 2025-11-01  
**Статус**: ✅ Vue.js 3 Production Ready  
**Порт**: 8080
