# Исправление API поиска и выравнивание элементов интерфейса поиска

**Дата:** 07-11-2025 13:44  
**Затрачено токенов:** ~49,000

## Проблемы

1. **Ошибка API поиска:**
   - URL: `https://wall.cyka.lol/api/v1/search?params%5Bq%5D=123&params%5Btype%5D=all`
   - Ошибка: `Fatal error: Uncaught Error: Class "SearchController" not found in /var/www/html/public/api.php:765`

2. **Проблема UI:**
   - Лупа поиска и текст смещены в полях ввода поиска
   - Проявляется в общем поиске и в Discover

## Внесенные изменения

### 1. Исправление API поиска (public/api.php)

**Файл:** `public/api.php`

Изменены строки 764-770 для использования полного пространства имен класса:

```php
// Было:
route('GET', 'api/v1/search', function() {
    SearchController::unifiedSearch();
});

route('GET', 'api/v1/search/trending', function() {
    SearchController::getTrendingSearches();
});

// Стало:
route('GET', 'api/v1/search', function() {
    \App\Controllers\SearchController::unifiedSearch();
});

route('GET', 'api/v1/search/trending', function() {
    \App\Controllers\SearchController::getTrendingSearches();
});
```

### 2. Поддержка вложенных параметров (src/Controllers/SearchController.php)

**Файл:** `src/Controllers/SearchController.php`

Добавлена поддержка обоих форматов параметров согласно спецификации в памяти:
- Прямые параметры: `?q=test&type=all`
- Вложенные параметры: `?params[q]=test&params[type]=all`

```php
public static function unifiedSearch()
{
    // Support both direct params (?q=test) and nested params (?params[q]=test)
    $params = isset($_GET['params']) && is_array($_GET['params']) ? $_GET['params'] : $_GET;
    
    $query = isset($params['q']) ? trim($params['q']) : '';
    $type = isset($params['type']) ? $params['type'] : 'all';
    $sort = isset($params['sort']) ? $params['sort'] : 'relevance';
    $limit = isset($params['limit']) ? min(50, max(1, (int)$params['limit'])) : 20;
    $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
    // ...
}
```

### 3. Выравнивание иконки и текста поиска в AppHeader

**Файл:** `frontend/src/components/layout/AppHeader.vue`

Исправлено выравнивание иконки лупы и placeholder текста:

```css
.search-icon {
  position: absolute;
  left: var(--spacing-3);
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.1rem;  /* Было: 1.25rem */
  /* ... */
  height: 1.1rem;  /* Добавлено для точного выравнивания */
}

.search-input {
  /* ... */
  padding-left: 38px;  /* Было: 40px */
  line-height: 1.4;    /* Было: 1.5 */
}
```

### 4. Выравнивание в SearchView

**Файл:** `frontend/src/views/SearchView.vue`

Аналогичные изменения для страницы поиска:

```css
.search-icon {
  /* ... */
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.1rem;
  height: 1.1rem;
}

.search-input {
  padding-left: 38px;  /* Было: 44px */
  line-height: 1.4;    /* Было: 1.5 */
}
```

### 5. Выравнивание в DiscoverView

**Файл:** `frontend/src/views/DiscoverView.vue`

Исправления для страницы Discover:

```css
.search-icon {
  /* ... */
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.1rem;
  height: 1.1rem;
}

.search-input {
  padding-left: 46px;  /* Было: 50px */
  line-height: 1.4;    /* Было: 1.5 */
}
```

## Технические детали

### Причина ошибки SearchController
PHP автозагрузчик корректно настроен, но в маршрутах использовалось короткое имя класса без пространства имен. В контексте closure-функций необходимо использовать полное пространство имен с обратным слешем `\App\Controllers\SearchController`.

### Выравнивание иконки
Проблема заключалась в:
1. Слишком большом размере шрифта иконки (1.25rem)
2. Отсутствии явной высоты для контейнера иконки
3. Несоответствии padding-left и положения иконки
4. Завышенном line-height в поле ввода

Решение:
- Уменьшили размер иконки до 1.1rem
- Добавили `height: 1.1rem` для точного контроля
- Добавили `top: 50%` и `transform: translateY(-50%)` для вертикального центрирования
- Скорректировали padding-left для соответствия позиции иконки
- Уменьшили line-height до 1.4 для лучшего выравнивания

## Деплой

Изменения внесены в worktree:
```
C:\Users\Rodion\.qoder\worktree\wall.cyka.lol\qoder\api-search-error-fix-1762504929
```

### Для применения изменений на production сервере необходимо:

1. **Бэкенд (PHP):**
   ```bash
   # Копировать файлы на production сервер
   scp public/api.php user@wall.cyka.lol:/var/www/html/public/
   scp src/Controllers/SearchController.php user@wall.cyka.lol:/var/www/html/src/Controllers/
   
   # Или через git (если используется)
   git add public/api.php src/Controllers/SearchController.php
   git commit -m "Fix SearchController not found error and add nested params support"
   git push
   # На сервере: git pull
   ```

2. **Фронтенд (Vue):**
   ```bash
   # В директории frontend
   npm run build
   
   # Копировать собранные файлы на production
   scp -r dist/* user@wall.cyka.lol:/var/www/html/public/
   ```

## Проверка работы

После деплоя проверить:

1. **API поиска:**
   ```bash
   curl "https://wall.cyka.lol/api/v1/search?q=test&type=all"
   curl "https://wall.cyka.lol/api/v1/search?params[q]=test&params[type]=all"
   ```

2. **UI выравнивание:**
   - Открыть https://wall.cyka.lol
   - Проверить выравнивание иконки и текста в верхней строке поиска
   - Перейти на /search и проверить выравнивание
   - Перейти на /discover и проверить выравнивание

## Примечания

- Все изменения обратно совместимы
- Поддержка вложенных параметров не ломает существующие запросы
- UI изменения минимальны и затрагивают только выравнивание
