# 01. Архитектура и ключевые решения

## 1. Общие сведения

**Название проекта:** AmanitaCMS

**Тип:** Flat-file CMS (движок для сайтов) с поддержкой магазина, блога, новостей, акций, отзывов и FAQ.

**Архитектура:** PHP 7.1+ + JSON (без базы данных).

**Хостинг:** DirectAdmin, Windows (пути `W:\domains\...`), PHP 7.1, LiteSpeed, гипервизор `ha005`.

**Домен CMS:** `amanitacms.ru`

**Сайты на базе CMS:** `muhomor.spb.ru`, `amanitalove.ru`

**Версия CMS:** 2.5

**Владелец:** дядя Костя Грибоман (владелец магазина).

**GitHub (основной):** https://github.com/KonstantinFromRussia/amanitacms — актуальный код CMS.

**GitHub (устаревший):** https://github.com/KonstantinFromRussia/amanitalove — версия магазина от 7 мая 2026, не использовать.

AmanitaCMS — это полностью самодостаточная система управления содержимым, предназначенная для создания сайтов и магазинов. Все данные хранятся в JSON-файлах, что делает сайт лёгким, быстрым и переносимым на любой хостинг с поддержкой PHP.

Ключевая особенность текущей версии — упор на SEO-2026 (canonical, OG, Schema.org расширенная, `llms.txt`, ARD), оптимизацию PageSpeed (WebP, инлайн `themes.css`, отказ от Font Awesome), безопасность (`flock()`-счётчик, блокировка сканеров на уровне `.htaccess`), рабочую почту (SMTP + шаблоны) и уникализацию CMS под разные регионы (скрипт `customize_cms.py`).

Проект представляет собой полностью самодостаточную систему управления содержимым, предназначенную для продажи сушёных мухоморов и грибов. Все данные хранятся в JSON-файлах, что делает сайт лёгким, быстрым и переносимым на любой хостинг с поддержкой PHP.

Ключевая особенность текущей версии — упор на SEO-2026 (canonical, OG, Schema.org расширенная, llms.txt, ARD), оптимизацию PageSpeed (WebP, инлайн themes.css, отказ от Font Awesome), безопасность (flock-счётчик, блокировка сканеров на уровне .htaccess), рабочую почту (SMTP + шаблоны) и уникализацию CMS под разные регионы (скрипт customize_cms.py).

---

Проект представляет собой полностью самодостаточную систему управления содержимым, предназначенную для продажи сушёных мухоморов и грибов. Все данные хранятся в JSON-файлах, что делает сайт лёгким, быстрым и переносимым на любой хостинг с поддержкой PHP.

Ключевая особенность текущей версии — упор на SEO-2026 (canonical, OG, Schema.org расширенная, llms.txt, ARD), оптимизацию PageSpeed (WebP, инлайн themes.css, отказ от Font Awesome), безопасность (flock-счётчик, блокировка сканеров на уровне .htaccess), рабочую почту (SMTP + шаблоны) и уникализацию CMS под разные регионы (скрипт customize_cms.py).

---

## 2. Команда проекта

| Роль | Имя / Ответственность |
|------|----------------------|
| Владелец / автор идей | Владелец магазина Muhomor.spb.ru (дядя Костя Грибоман) |
| Разработчик (внешний) | Помощь в доработке CMS, консультации (Deepseek) |
| Тестировщик | Проверка 404, редиректов, SEO-выдачи, корректности писем |

Проект развивается при участии внешнего разработчика, который помогает с технической реализацией, отладкой и внедрением новых функций. Все правки согласуются с владельцем. Комментарии в коде не удаляются никогда (см. `13-rules.md`).

---

## 3. Используемые технологии

| Технология | Назначение |
|------------|------------|
| PHP 7.1 или выше | Серверная логика, генерация страниц, API, админка |
| JSON | Хранение всех данных (товары, статьи, настройки и т.д.) |
| JavaScript (ES6) | Динамическая подгрузка, корзина, фильтры, пагинация |
| Tailwind CSS | Адаптивная вёрстка (скомпилирован в `tailwind-output.css`) |
| Custom CSS | Дополнительные стили (переменные тем, анимации, модалки) |
| Summernote | Визуальный редактор для админки (исключён из кастомизации) |
| jQuery | Только для Summernote (в публичной части не используется) |
| Inline-SVG | Иконки соцсетей и интерфейса (заменили Font Awesome) |
| Apache + mod_rewrite | ЧПУ-ссылки, кеширование, сжатие, защита `/uploads/` |
| LiteSpeed | Веб-сервер на хостинге (совместим с .htaccess Apache) |
| localStorage | Корзина, выбор темы, возрастное подтверждение |
| GD (php-gd) с WebP | Автоматическая конвертация изображений в WebP |
| bcrypt (password_hash) | Хеширование пароля администратора |
| ZipArchive | Резервное копирование данных |
| Cookie + сессия | Счётчик уникальных посетителей |
| Canvas API | Favicon-бейдж с числом непрочитанных событий |
| flock() | Атомарная блокировка файлов счётчика (защита от race condition) |
| PHPMailer | Отправка почты через SMTP (STARTTLS, порт 587) |
| SMTP mail.muhomor.spb.ru | Внешний почтовый сервер для уведомлений |
| Google Analytics 4 | Аналитика (ID `G-LSRBZ45NLB`) |
| Яндекс.Метрика | **НЕ подключена** (поле для ID в админке готово, ждём восстановления ДЦ Яндекса) |
| Python 3.7+ / PyYAML | Скрипт уникализации CMS (`customize_cms.py`) |
| Python 3.7+ | Скрипт сборки бандла (`bundle_project.py`) |

---

## 4. Структура проекта (актуальная на 24.10.2026)

Локальная копия находится в `c:\10\`, структура полностью совпадает с серверной.

    /
    ├── index.php                     # Главная страница + обработка 404
    ├── catalog.php                   # Каталог (фильтры, поиск, сортировка, пагинация)
    ├── blog.php                      # Блог (пагинация)
    ├── news.php                      # Новости (пагинация)
    ├── faq.php                       # FAQ (Schema.org FAQPage)
    ├── promotions.php                # Акции (пагинация)
    ├── reviews.php                   # Отзывы (пагинация)
    ├── search.php                    # Результаты поиска
    ├── contacts.php                  # Контакты с быстрыми ссылками
    ├── page.php                      # Универсальная страница + форма отзыва, вывод seo_title
    ├── about.php                     # Страница «О Грибомане» (Schema.org Person, фото griboman.webp)
    ├── 404.php                       # Страница 404 (отдаёт код 404, не 200)
    ├── admin.php                     # Панель управления (исключён из кастомизации)
    ├── auth.php                      # Вход (bcrypt, локальный Roboto, noindex)
    ├── logout.php                    # Полная очистка сессии
    ├── api.php                       # API (GET, PUT, POST, change-password, reviews-submit, отправка почты)
    ├── upload.php                    # Загрузка + WebP-конвертация + транслит кириллицы
    ├── backup.php                    # Генерация ZIP-архива
    ├── sitemap.php                   # sitemap.xml (+ пагинация, + about.php, исправлен fileMtime)
    ├── robots.php                    # robots.txt (+ AI-краулеры, + закрытие admin/api/data/auth/logout/backup)
    ├── rename-all-images.php         # Одноразовый скрипт SEO-переименования старых картинок
    ├── batch-webp.php                # Одноразовый скрипт WebP-конвертации старых картинок
    ├── .htaccess                     # ЧПУ, сжатие, кеширование, защита, редиректы, блокировка сканеров, Options -MultiViews
    ├── llms.txt                      # Описание сайта для AI-агентов
    ├── README.md                     # Краткое описание проекта
    │
    ├── /.well-known/                 # Служебные файлы для AI-агентов
    │   ├── ai-catalog.json           # ARD-каталог ресурсов
    │   └── ard.json                  # Дубликат для новой спецификации
    │
    ├── /css/
    │   ├── tailwind-output.css       # НЕ УДАЛЯТЬ (внешний файл, инлайн ломал вёрстку на Windows-хостинге)
    │   ├── styles.css
    │   └── themes.css                # Инлайнится в head.php (экономия ~450 мс)
    │
    ├── /js/
    │   ├── script.js                 # Публичные скрипты (buildPicture, корзина, темы, пагинация)
    │   ├── admin.js                  # Админка (тосты, фильтры, Summernote-инициализация, бейджи) — исключён из кастомизации
    │   ├── jquery-3.6.0.min.js       # Только для Summernote — исключён из кастомизации
    │   └── summernote/               # Визуальный редактор — исключён из кастомизации
    │
    ├── /includes/
    │   ├── config.php                # SITE_URL + session + helpers + icons + counter
    │   ├── helpers.php               # renderPicture(), getMaxIcon(), socialUrl()
    │   ├── icons.php                 # SVG-иконки соцсетей
    │   ├── counter.php               # trackVisitor(), getVisitorCount() (flock, защита от ботов, strpos)
    │   ├── head.php                  # Метатеги, OG, canonical, Schema.org, site_seo_title
    │   ├── header.php                # Шапка (меню, соцсети, логотип, ссылка на about.php)
    │   ├── footer.php                # Подвал, корзина, модалки, счётчик, ссылка на about.php
    │   ├── mailer.php                # Обёртка над PHPMailer (отправка писем)
    │   ├── mail-config.php           # SMTP-настройки (mail.muhomor.spb.ru:587, STARTTLS, noreply@) — ПАРОЛИ ЗДЕСЬ
    │   ├── email-templates.php       # Шаблоны писем (новый заказ, новый отзыв, смена статуса)
    │   ├── PHPMailer/                # Библиотека PHPMailer (PHPMailer.php, SMTP.php, Exception.php)
    │   ├── .htaccess                 # Защита includes/
    │   └── /blocks/
    │       ├── promotions.php        # Блок акций
    │       └── reviews.php           # Блок отзывов
    │
    ├── /data/                        # Данные (JSON)
    │   ├── settings.json             # Настройки + admin_login + admin_password_hash + max + site_seo_title + GA4 ID + Метрика ID
    │   ├── products.json             # + seo_title у товаров
    │   ├── blog.json
    │   ├── news.json
    │   ├── faq.json
    │   ├── promotions.json
    │   ├── reviews.json              # status: 0 — модерация, 1 — опубликован
    │   ├── orders.json               # Статусы: Новый / В обработке / Отправлен / Выполнен
    │   ├── counter.json              # Счётчик посетителей
    │   ├── counter.lock              # Lock-файл для flock()
    │   └── counter.log               # Лог счётчика (проверять при сбросах!)
    │
    ├── /uploads/                     # Изображения (.jpg/.png + .webp), SEO-имена после rename-all-images.php
    │   └── .htaccess                 # Запрет исполнения PHP
    ├── /fonts/                       # Roboto (локально, для auth.php и публичной части)
    ├── /webfonts/                    # Web-шрифты
    ├── /favicon/                     # Иконки сайта (+ max-icon.svg, site.webmanifest)
    ├── /doc/                         # Документация (эти файлы)
    ├── /script1/                     # Скрипт уникализации
    │   ├── customize_cms.py          # Уникализация CMS (skip_process для admin.php, admin.js, jquery, summernote)
    │   ├── config.yaml               # Конфигурация кастомизации
    │   ├── requirements.txt          # PyYAML
    │   ├── custom.cmd                # Обёртка запуска
    │   ├── readme.md
    │   └── .gitignore.txt
    ├── /script2/                     # Скрипт сборки бандла
    │   └── bundle_project.py         # Собирает значимые файлы в bundle/project_bundle.txt (413 КБ), чистит admin_login/admin_password_hash
    └── /bundle/
        └── project_bundle.txt        # Бандл проекта для переноса в чат

---

## 5. Архитектура и ключевые решения

### 5.1. Отсутствие базы данных

- Вся информация в JSON-файлах.
- Простое развёртывание, бэкап, перенос.
- Не нужен MySQL.
- Минус — нет транзакций; для защиты от race condition используется `flock()` (см. 5.10).

### 5.2. Маршрутизация (ЧПУ)

| URL | Обработчик |
|-----|------------|
| `/catalog`, `/blog`, `/news`, `/contacts`, `/faq`, `/promotions`, `/reviews`, `/search`, `/about` | соответствующие `.php` |
| `/product/nazvanie.html` | `page.php?type=product&slug=nazvanie` |
| `/blog/nazvanie.html` | `page.php?type=blog&slug=nazvanie` |
| `/news/nazvanie.html` | `page.php?type=news&slug=nazvanie` |
| `/promotion/nazvanie.html` | `page.php?type=promotion&slug=nazvanie` |
| `/api/products?search=...` | `api.php?action=products` |
| `/api/change-password` | `api.php?action=change-password` (POST) |
| `/api/reviews-submit` | `api.php?action=reviews-submit` (POST) |
| `/backup.php` | Генерация ZIP (требует авторизации) |
| `/sitemap.xml` | `sitemap.php` (`Options -MultiViews` в `.htaccess` обязателен) |
| `/robots.txt` | `robots.php` |

**Редиректы** (в `.htaccess`):
- HTTP → HTTPS (301)
- WWW → без WWW (301)

**404:**
- В `index.php` добавлена проверка на мусорные URL.
- Отдаётся `404.php` с кодом 404 (не 200). Тестировщик подтвердил.
- В `robots.php` закрыты `/auth.php`, `/logout.php`, `/backup.php`, `/admin.php`, `/admin`.
- В `auth.php` и `admin.php` добавлен `meta robots noindex, nofollow`.

### 5.3. Автоматическое определение домена

В `includes/config.php`:
- `SITE_URL` — на основе протокола и `HTTP_HOST`.
- `session_start()` — если ещё не запущена.
- Подключение `helpers.php`, `icons.php`, `counter.php`.
- Вызов `trackVisitor()`.

### 5.4. Динамическая подгрузка и пагинация

- Товары, блог, новости, отзывы, акции — через `script.js` (fetch к API).
- Пагинация — `?page=N` + `history.pushState`.
- Корзина — `localStorage`.

### 5.5. Темы оформления

Три темы: **тёмная** (по умолчанию), **светлая**, **лесная**.
Переключение в `localStorage` (ключ `amanita_theme`).

**Известная проблема:** после кастомизации (`customize_cms.py`) слетели цвета иконок — Tailwind не сгенерировал arbitrary values (`text-[var(--accent)]`, `bg-[var(--bg-card)]`). Иконки белые. Решение отложено (нужна пересборка `tailwind-output.css`). Пока игнорируем.

### 5.6. Админ-панель (актуальное состояние)

**Аутентификация:**
- Логин и bcrypt-хеш пароля хранятся в `data/settings.json` (`admin_login`, `admin_password_hash`).
- Проверка через `password_verify()`, защита от timing-атак через `hash_equals()`.
- `session_regenerate_id(true)` при входе.
- Задержка 300 мс при неверном пароле.
- Дефолтный пароль (`secret123`) активен только **до** первой смены.
- Смена пароля — «Настройки» → «Смена пароля» (`POST /api/change-password`).
- Поля `admin_login` и `admin_password_hash` **скрыты** из публичного `GET /api/settings`.

**Возможности:**
- Управление товарами, блогом, новостями, заказами, FAQ, акциями, отзывами и настройками сайта.
- Редактор Summernote для HTML-полей. Summernote, jquery, admin.js, admin.php **исключены** из `customize_cms.py` (`skip_process`) — после кастомизации работают корректно.
- Загрузка изображений через `upload.php` (авто-WebP, транслит кириллицы, проверка дубликатов).
- Модалки — класс `.open` (унифицировано с публичной частью).
- Всплывающие уведомления (тосты) при сохранении — в `admin.js`.

**SEO-поля в админке:**
- `site_seo_title` — длинный заголовок для `<title>` (короткое `site_name` отдельно).
- `seo_title` у товаров — не меняет URL/slug.
- GA4 ID (`G-LSRBZ45NLB`) — вписан.
- Поле для Яндекс.Метрики — готово, ID не вставлен (ждём восстановления ДЦ Яндекса).

**Модерация отзывов:**
- `status: 0` — вверху списка.
- Бейдж «🆕 Новый» и красная подсветка.
- Кнопка «✅ Опубликовать» — 1 клик → `status: 1`.

**Заказы:**
- Развёрнутые карточки — клик по шапке → детали (email, адрес, состав, комментарий).
- Статус — выпадающий список (Новый / В обработке / Отправлен / Выполнен).
- Кнопка 🗑 — удаление.
- Фильтр по статусу, поиск, сортировка — **в разработке** (см. `11-roadmap.md`).

**Индикаторы событий:**
- Красные бейджи на вкладках «⭐ Отзывы» и «📦 Заказы».
- Общий счётчик в заголовке «(N новых)».
- Favicon-бейдж с числом непрочитанных.
- Авто-проверка каждые 60 секунд.
- Всплывающие уведомления.

> ⚠️ **Важно:** пароль **нельзя восстановить** — хранится только bcrypt-хеш. Если забыт — см. `03-admin.md`.

### 5.7. Изображения и WebP

- **Загрузка** через `upload.php`:
  - `finfo` + `getimagesize()` (двойная валидация).
  - Транслитерация кириллицы в имени файла.
  - Проверка на дубликаты.
  - Уникальное имя `img_<timestamp>_<random>.<ext>`.
  - Создание `.webp`-копии (качество 82), если GD с WebP.
  - Если WebP больше оригинала — удаляется.
- **PHP-вывод:** `renderPicture()` → `<picture>` с WebP.
- **JS-вывод:** `buildPicture()` в `script.js`.
- **Fallback:** браузеры без WebP — оригинал.
- **OG/Schema:** остаются на JPEG.
- **Старые картинки:**
  - `rename-all-images.php` — переименованы в SEO-имена (латиница, дефисы).
  - `batch-webp.php` — одноразовая WebP-конвертация.

### 5.8. Безопасность

- **Защита `/data/`:** `RedirectMatch 403`.
- **Защита `/uploads/`:** `RewriteRule` + `php_flag engine off` + `RemoveHandler` + `Require all denied`.
- **Защита `/includes/`:** `.htaccess`.
- **Загрузка файлов:** MIME + `getimagesize()`.
- **XSS:** `htmlspecialchars()`, `escapeHtml()`.
- **CSRF:** `credentials: 'same-origin'`.
- **Спам-защита отзывов:** Honeypot + арифметическая капча + rate-limit (1 в час).
- **Листинг директорий:** `Options -Indexes`.
- **Кеширование:** `no-store` для `/api/`, `no-cache` для HTML/PHP, `immutable` для статики.
- **Блокировка сканеров уязвимостей (двойная защита):**
  - `.htaccess`: `RewriteCond %{REQUEST_URI} ^/(phpinfo|info\.php|test\.php|debug|shell|webshell|\.env|\.aws|\.git|wp\-|config\.(php|json|js|yaml|env)|credentials|secrets|\.npmrc|\.boto|\.s3cfg|Dockerfile|composer\.json|database\.sql|appsettings|_profiler) [NC]` + `RewriteRule ^ - [F,L]`.
  - `counter.php`: расширенный regex ботов (`semrush|palo alto|scanner|nmap|sqlmap|...`) + блокировка по URI.
  - Результат: сканеры получают 403.
- **Пароли почты** вынесены в `includes/mail-config.php` (не в публичных файлах).

### 5.9. SEO-2026 и GEO (актуальное состояние)

- **`canonical`** — self-referencing, UTM вырезаются.
- **`og:image`, `og:type`, `og:locale`, `twitter:image`.**
- **`meta robots`:** `noindex, follow` для фильтров; `noindex, nofollow` для `auth.php` и `admin.php`.
- **Schema.org:**
  - `Product`, `Article`, `NewsArticle`, `FAQPage`, `BreadcrumbList`, `ItemList`, `OnlineStore`.
  - `Person` (E-E-A-T) на `about.php`.
- **`site_seo_title`** в `settings.json` — длинный title, отдельно от `site_name`.
- **`seo_title` у товаров** в `products.json` — не меняет URL/slug.
- **`about.php`** — страница «О Грибомане» с микроразметкой `Person`, фото `/uploads/griboman.webp`, ссылки в header и footer.
- **Sitemap:** с пагинацией, датами `lastmod`, `priority`, + `about.php`. Исправлена ошибка `Cannot redeclare fileMtime` (переименовано в `getFileMtime`, конфликт с встроенной `filemtime`).
- **Robots.txt:** явные разрешения для AI-краулеров (GPTBot, PerplexityBot, Google-Extended, ClaudeBot, CCBot); закрыты `admin/api/data/auth/logout/backup`.
- **`llms.txt`** — описание сайта для AI-агентов.
- **`ai-catalog.json`** (ARD) — каталог ресурсов для AI-агентов.
- **3 SEO-лонгрида** для блога — тексты написаны и переданы.
- **HTML-шаблон подробного описания товара** (состав, дозировка, доставка) — написан.
- **301-редиректы** HTTP→HTTPS и WWW→без WWW.
- **`404.php`** отдаёт код 404.
- **`Options -MultiViews`** в `.htaccess` — починил `sitemap.xml`.

### 5.10. Счётчик уникальных посетителей (после переработки)

- **`trackVisitor()`** в `includes/counter.php`.
- **Cookie `visitor_id`** на 1 год, ставится **только после** успешной записи.
- **Фильтры:** админы, боты, API/AJAX, не-GET.
- **Защита от многократного вызова:** `static $alreadyTracked`.
- **Хранение:** `data/counter.json`.
- **Начальное значение:** 1000.
- **Вывод:** в футере через `getVisitorCount()`.
- **КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ (19.10): race condition.**
  - **Причина:** `counter.json` читался/писался одновременно несколькими запросами (бот `35.240.58.49`). На Windows `rename()` не атомарен → файл пустел → `readCounter()` возвращал 1000 → сброс.
  - **Решение:** `flock()` — эксклюзивная блокировка на 5 секунд + отдельный `counter.lock`; чтение и запись **под блокировкой**; cookie ставится после успешной записи.
  - **Проверка:** `counter.log` — если снова сброс, он покажет причину.

### 5.11. Резервное копирование

- **`backup.php`** — ZIP с `/data/` + `/uploads/`.
- **Требует авторизации.**
- **Имя файла:** `<домен>-backup-YYYY-MM-DD-HHMMSS.zip`.
- **Скачивается сразу** в браузер.
- **В архив:** все `*.json`, все файлы из `/uploads/` (кроме `.htaccess`), `README.txt`.

### 5.12. Статусы заказов

**Статусы заказов** — внутренние метки, видны только администратору в админ-панели.

| Статус | Что значит | Кто ставит |
|--------|-----------|-----------|
| 🆕 Новый | Заказ только что поступил | Автоматически при оформлении |
| ⏳ В обработке | Принят, собирается, проверяется | Вручную в админке |
| 📦 Отправлен | Передан в доставку | Вручную |
| ✅ Выполнен | Доставлен, клиент получил товар | Вручную |

**Зачем нужно:**
1. Не забыть про заказ.
2. Отчётность.
3. Готовность к уведомлениям — SMTP уже работает, шаблоны писем готовы, автоматические письма клиенту при смене статуса — в Roadmap (высокий приоритет).

**Цветовая индикация:** «Новый» — красная подсветка слева + бейдж. Остальные — без подсветки (цветовая индикация всех статусов — в Roadmap).

**Автоматическое снятие бейджа:** при смене статуса с «Новый» на любой другой красный бейдж уменьшается/исчезает.

### 5.13. Оптимизация PageSpeed

| Что | Экономия |
|-----|----------|
| WebP для всех изображений | −32% размера |
| `themes.css` инлайн | −450 мс блокировки |
| `all.min.css` удалён (Font Awesome) | −750 мс, −25 КБ |
| Font Awesome → inline-SVG | −25 КБ |
| Логотип: `fetchpriority="high"` + `width`/`height` | LCP ↓, CLS = 0 |
| Логотип сжат (50 КБ → 16 КБ) | −34 КБ |
| Шрифты preload (опционально) | −150 мс |

**Результат:** CSS-блокировка снизилась с **1960 мс** до **~600 мс**.

**Осталось (низкий приоритет):**
- LCP ~2 сек — из-за окна 18+ (юридический компромисс).
- `tailwind-output.css` — оставлен внешним (инлайн ломал вёрстку на Windows-хостинге).

### 5.14. Индикаторы событий (для админа)

**Что это:** система визуальных уведомлений о новых отзывах и заказах.

**Компоненты:**
1. **Бейдж на вкладке** — красный кружок с числом на «⭐ Отзывы» и «📦 Заказы».
2. **Общий счётчик** — «(N новых)» рядом с заголовком «⚙️ Админ-панель сайта».
3. **Favicon-бейдж** — цифра непрочитанных на иконке сайта в браузере.
4. **Подсветка карточек** — красная полоса слева + бейдж «🆕 Новый».
5. **Авто-проверка** — каждые 60 секунд.
6. **Всплывающие уведомления** — «🆕 Новый отзыв!», «🆕 Новый заказ!».

**Как работает:**
- Отзыв «новый», если `status: 0`.
- Заказ «новый», если `status: "Новый"`.
- После публикации отзыва или смены статуса заказа — счётчик уменьшается.

### 5.15. Почта и уведомления (НОВОЕ с 23.10)

- Инженеры **разблокировали** аккаунт `noreply@muhomor.spb.ru` (была ошибка `550 User account (u14445mo3) is not allowed to send emails`).
- Настроен `includes/mail-config.php`:
  - SMTP-хост: `mail.muhomor.spb.ru`
  - Порт: `587`
  - Шифрование: `STARTTLS`
  - Отправитель: `noreply@muhomor.spb.ru`
  - **Пароли хранятся здесь** (не в публичных файлах).
- Созданы шаблоны писем в `includes/email-templates.php`.
- Обёртка над PHPMailer — `includes/mailer.php`.
- Уведомления приходят на `kon.severov2016@yandex.ru` (тестовый ящик).
- Настроены уведомления: новый заказ, новый отзыв, смена статуса заказа.
- **Внимание:** `pcweb@mail.ru` по-прежнему отправляет письма в спам (проблема Mail.ru, не наша). Для уведомлений админа используем Яндекс.
- Внешние SMTP (Яндекс.Почта для домена, Gmail) — резервный вариант на будущее, если `mail.muhomor.spb.ru` перестанет работать.

### 5.16. Аналитика (GA4 и Метрика)

**GA4:**
- ID `G-LSRBZ45NLB` вписан в админку.
- Подключён и работает.
- Google Tag Manager не грузится напрямую — ошибка `ERR_NAME_NOT_RESOLVED` для `fonts.googleapis.com`.
- Обход: добавлен IP в файл `hosts` (`142.250.74.163 fonts.googleapis.com`).

**Яндекс.Метрика:**
- **НЕ подключена** (дата-центры Яндекса не восстановились, статистика не грузится).
- Поле для ID в админке готово.
- Как только заработает — вставить ID и проверить (см. `11-roadmap.md`).

### 5.17. Уникализация CMS (`customize_cms.py`)

**Назначение:** копирует CMS, заменяет CSS-классы, удаляет HTML/CSS-комментарии, перемешивает блоки внутри `<main>`. Позволяет создавать уникальные версии под разные регионы/домены.

**Расположение:** `script1/customize_cms.py`
**Требования:** Python 3.7+, PyYAML.
**Конфигурация:** `script1/config.yaml`.

**КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ (23.10):** `admin.php`, `admin.js`, `jquery`, `summernote` **исключены** из скрипта (добавлен `skip_process`). До этого админка и Summernote ломались после кастомизации.

**Dry-run** (проверка без записи): `python customize_cms.py --dry-run --seed 12345`.
**Результат последнего dry-run:** 1892 замены, 0 ошибок.

**Roadmap:** добавить `--prefix ekb`, `--prefix nsk` (уникализация под Екатеринбург, Новосибирск).

### 5.18. Bundle-скрипт (`bundle_project.py`)

**Назначение:** собирает значимые файлы в один текстовый файл для переноса в чат.

**Расположение:** `script2/bundle_project.py`
**Результат:** `bundle/project_bundle.txt` (413 КБ).
**Автоматически чистит** `admin_password_hash` и `admin_login` из `settings.json` перед сборкой.
**Запуск:** `python bundle_project.py`.
