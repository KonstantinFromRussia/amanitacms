# 06. Безопасность

**Проект:** AmanitaCMS · **Версия CMS:** 2.5 · **Актуально на:** 22 ноября 2026

**Демонстрационные сайты:** `muhomor.spb.ru`, `amanitalove.ru`

---

## 1. Обзор

- **Защита файлов и директорий** — `/data/`, `/uploads/`, `/includes/`.
- **Аутентификация** — bcrypt, `password_verify()`, `hash_equals()`, `session_regenerate_id()`.
- **XSS** — `htmlspecialchars()`, `escapeHtml()`.
- **CSRF** — `credentials: 'same-origin'`.
- **Спам-защита отзывов** — Honeypot + арифметическая капча + rate-limit.
- **Блокировка сканеров** — двойная защита (`.htaccess` + `counter.php`).
- **Защита счётчика от race condition** — `flock()`.
- **Брутфорс** — задержка 300 мс при неверном пароле.
- **Session fixation** — `session_regenerate_id(true)`.

---

## 2. Защита файлов и директорий

### 2.1. `/data/`

Внутри: `settings.json` (с `admin_password_hash`), `products.json`, `orders.json`, `reviews.json`, `counter.json` и др.

**Защита:** в корневом `.htaccess` — `RedirectMatch 403 ^/data/`. Прямой доступ к `/data/settings.json` → 403.

### 2.2. `/uploads/`

Внутри: изображения (`.jpg`, `.png`, `.webp`).

**Защита:** `/uploads/.htaccess` — `RewriteRule` (запрет PHP), `php_flag engine off`, `RemoveHandler`, `Require all denied` для не-изображений. Загрузка `.php` в `/uploads/` → 403.

### 2.3. `/includes/`

Внутри: `config.php`, `helpers.php`, `counter.php`, `mail-config.php`, `email-templates.php`, PHPMailer и др.

**Защита:** `/includes/.htaccess` — запрет прямого доступа. `/includes/mail-config.php` → 403.

---

## 3. Аутентификация

### 3.1. Хранение пароля

- Логин и bcrypt-хеш — в `data/settings.json` (`admin_login`, `admin_password_hash`).
- **Пароль в открытом виде не хранится никогда.**
- `admin_password_hash` и `admin_login` **скрыты** из публичного `GET /api/settings`.

### 3.2. Проверка пароля

- `password_verify()` — сравнение с bcrypt-хешем.
- `hash_equals()` — защита от timing-атак.
- **Задержка 300 мс** при неверном пароле — против брутфорса.

### 3.3. Сессия

- `session_regenerate_id(true)` при входе — защита от session fixation.
- `logout.php` — полная очистка сессии.

### 3.4. Смена пароля

- Endpoint: `POST /api/change-password`.
- Проверка старого пароля → запись нового bcrypt-хеша. Старый перестаёт действовать.

### 3.5. Дефолтный пароль

- `secret123` активен **только до первой смены**.
- Если `admin_password_hash` отсутствует — система разрешает вход с `secret123`.
- **Смените сразу после установки** (см. `02-install.md`).

---

## 4. Защита от XSS

- **PHP:** вывод через `htmlspecialchars()` (в `helpers.php`, шаблонах).
- **JS:** вывод через `escapeHtml()` в `script.js`.
- **Summernote:** HTML-поля для админского контента (админ — доверенный источник).
- **Отзывы:** пользовательский ввод санитизируется перед сохранением и выводом.

---

## 5. Защита от CSRF

- **Cookie сессии** + `credentials: 'same-origin'` в fetch-запросах.
- **API** (`api.php`) проверяет авторизацию для чувствительных действий (`change-password`, `PUT /api/settings`, удаление и т.п.).
- **Публичные endpoints** (`reviews-submit`) — защищены капчей и rate-limit (см. ниже).

---

## 6. Спам-защита отзывов

Форма отзывов (`page.php` → `POST /api/reviews-submit`) защищена **тремя уровнями**:

- **Honeypot:** скрытое поле (`website`); если заполнено — отзыв отклоняется.
- **Арифметическая капча:** простой пример («3 + 5?»); бот без OCR не решит.
- **Rate-limit:** 1 отзыв в час с одного IP; хранится в `data/reviews.json` (или отдельном логе); при превышении — отказ.

**Модерация:** все отзывы с формы → `status: 0`; админ публикует в 1 клик (см. `03-admin.md`).

---

## 7. Блокировка сканеров уязвимостей

**Проблема:** сканеры (Palo Alto, Semrush) с UA `Mozilla/5.0` и запросы к `/phpinfo.php`, `/.env`, `/.aws` проходили фильтр и попадали в счётчик.

**Решение — двойная защита:**

### 7.1. `.htaccess` (корень)

    RewriteCond %{REQUEST_URI} ^/(phpinfo|info\.php|test\.php|debug|shell|webshell|\.env|\.aws|\.git|wp\-|config\.(php|json|js|yaml|env)|credentials|secrets|\.npmrc|\.boto|\.s3cfg|Dockerfile|composer\.json|database\.sql|appsettings|_profiler) [NC]
    RewriteRule ^ - [F,L]

**Результат:** запросы к этим путям → 403.

### 7.2. `counter.php`

- Расширенный regex ботов: `semrush|palo alto|scanner|nmap|sqlmap|...`.
- Блокировка по URI (те же паттерны, что в `.htaccess`).
- **`strpos` вместо `preg_match`** — быстрее и надёжнее для простых проверок.

**Результат:** сканеры не попадают в счётчик.

---

## 8. Защита счётчика от race condition

**Проблема:** `counter.json` читался/писался одновременно несколькими запросами (бот). На Windows `rename()` **не атомарен** → файл пустел → `readCounter()` возвращал `1000` → счётчик сбрасывался.

**Решение (в `includes/counter.php`):**
- **`flock()`** — эксклюзивная блокировка на 5 секунд.
- **`counter.lock`** — отдельный lock-файл.
- **Чтение + запись под блокировкой.**
- **Cookie `visitor_id`** ставится **после** успешной записи.

**Проверка:** `data/counter.log` — при сбросе покажет причину; наблюдение 1–2 дня.

---

## 9. Кеширование (защита от утечек)

В `.htaccess`:
- **`no-store`** для `/api/` — API-ответы не кешируются.
- **`no-cache`** для HTML/PHP — страницы не кешируются браузером.
- **`immutable`** для статики (CSS, JS, изображения) — кешируются надолго (с версионированием через query-string).

**Зачем:** после смены пароля или настроек браузер не показывает старые данные.

---

## 10. Листинг директорий

- **`Options -Indexes`** в `.htaccess` — запрет листинга.
- **Результат:** `/uploads/` без файла → 403 (не список файлов).

---

## 11. Защита от брутфорса

- **Задержка 300 мс** при неверном пароле (в `auth.php`).
- **Нет блокировки по IP** (пока) — при необходимости можно добавить.
- **Дефолтный пароль** `secret123` — только до первой смены.
- **Рекомендация:** надёжный пароль (12+ символов, буквы+цифры+спецсимволы).

---

## 12. Защита почтовых паролей

- **Пароли SMTP** — только в `includes/mail-config.php`.
- **`mail-config.php` защищён** `.htaccess` в `/includes/`.
- **Не коммитьте `mail-config.php` в GitHub** — добавьте в `.gitignore`.
- **Не перезаписывайте `mail-config.php`** при обновлениях (см. `02-install.md`, п. 7).

---

## 13. Защита `settings.json`

- **`admin_password_hash`** — только bcrypt-хеш, не пароль.
- `admin_password_hash` и `admin_login` **скрыты** из публичного `GET /api/settings` и **не перезаписываются** через `PUT /api/settings` (защита от подмены).
- **Права на `data/settings.json`** — `644` или `664` (не `777`).

---

## 14. Чек-лист безопасности

- [ ] `/data/` → 403 при прямом доступе
- [ ] `/uploads/*.php` → 403
- [ ] `/includes/mail-config.php` → 403
- [ ] `admin.php` и `auth.php` — `noindex, nofollow`, закрыты в `robots.php`
- [ ] Пароль сменён с `secret123` на надёжный
- [ ] `admin_password_hash` сохранён отдельно (для восстановления)
- [ ] `mail-config.php` не в GitHub
- [ ] `mail-config.php` не перезаписывается при обновлениях
- [ ] `Options -Indexes` в `.htaccess`
- [ ] `Options -MultiViews` в `.htaccess`
- [ ] Блокировка сканеров работает (`/.env` → 403)
- [ ] `counter.log` не показывает сбросов
- [ ] Rate-limit отзывов работает (2-й отзыв за час → отказ)
- [ ] Honeypot работает (заполненное скрытое поле → отказ)
- [ ] Капча работает (неверный ответ → отказ)
- [ ] `data/settings.json` — права `644` или `664`
- [ ] Бэкап сделан и сохранён в двух местах