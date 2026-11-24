# 01. Архитектура и ключевые решения

**Проект:** AmanitaCMS · **Версия CMS:** 2.5 · **Актуально на:** 22 ноября 2026

**Демонстрационные сайты:** `muhomor.spb.ru`, `amanitalove.ru`

---

## 1. Общие сведения

- **Название:** AmanitaCMS.
- **Тип:** Flat-file CMS (движок для сайтов) с поддержкой магазина, блога, новостей, акций, отзывов и FAQ.
- **Архитектура:** PHP 7.1+ + JSON (без базы данных).
- **Хостинг:** DirectAdmin, Windows, PHP 7.1, LiteSpeed, гипервизор `ha005`.
- **Домен CMS:** `amanitacms.ru`.
- **Демо-сайты:** `muhomor.spb.ru`, `amanitalove.ru`.
- **GitHub (основной):** https://github.com/KonstantinFromRussia/amanitacms — актуальный код CMS.
- **GitHub (устаревший):** https://github.com/KonstantinFromRussia/amanitalove — версия магазина от 7 мая 2026, не использовать.

AmanitaCMS — самодостаточная система управления содержимым для сайтов и магазинов. Все данные хранятся в JSON-файлах: сайт лёгкий, быстрый и переносимый на любой хостинг с PHP.

Ключевая особенность версии — упор на SEO-2026 (canonical, OG, Schema.org, `llms.txt`, ARD), оптимизацию PageSpeed (WebP, инлайн `themes.css`, отказ от Font Awesome), безопасность (`flock()`-счётчик, блокировка сканеров в `.htaccess`), рабочую почту (SMTP + шаблоны) и уникализацию CMS под регионы (`customize_cms.py`).

---

## 2. Команда проекта

| Роль | Ответственность |
|------|-----------------|
| Владелец / автор идей | Владелец AmanitaCMS |
| Внешний разработчик (AI-ассистент) | Доработка CMS, консультации |
| Тестировщик | Проверка 404, редиректов, SEO-выдачи, корректности писем |

Все правки согласуются с владельцем. Комментарии в коде не удаляются никогда (см. `13-rules.md`).

---

## 3. Используемые технологии

| Технология | Назначение |
|------------|------------|
| PHP 7.1+ | Серверная логика, генерация страниц, API, админка |
| JSON | Хранение всех данных (товары, статьи, настройки и т.д.) |
| JavaScript (ES6) | Динамическая подгрузка, корзина, фильтры, пагинация |
| Tailwind CSS | Адаптивная вёрстка (`tailwind-output.css`) |
| Custom CSS | Дополнительные стили (переменные тем, анимации, модалки) |
| Summernote | Визуальный редактор админки (исключён из кастомизации) |
| jQuery | Только для Summernote (в публичной части не используется) |
| Inline-SVG | Иконки соцсетей и интерфейса (заменили Font Awesome) |
| Apache + mod_rewrite | ЧПУ, кеширование, сжатие, защита `/uploads/` |
| LiteSpeed | Веб-сервер на хостинге (совместим с `.htaccess` Apache) |
| localStorage | Корзина, выбор темы, возрастное подтверждение |
| GD (php-gd) с WebP | Автоматическая конвертация изображений в WebP |
| bcrypt (password_hash) | Хеширование пароля администратора |
| ZipArchive | Резервное копирование данных |
| Cookie + сессия | Счётчик уникальных посетителей |
| Canvas API | Favicon-бейдж с числом непрочитанных событий |
| flock() | Атомарная блокировка файлов счётчика (защита от race condition) |
| PHPMailer | Отправка почты через SMTP (STARTTLS, порт 587) |
| SMTP `mail.example.com` | Внешний почтовый сервер для уведомлений |
| Google Analytics 4 | Аналитика (ID `G-LSRBZ45NLB`) |
| Яндекс.Метрика | Аналитика для РФ-аудитории (**подключена, 22.11.2026**) |
| Python 3.7+ / PyYAML | Уникализация CMS (`customize_cms.py`) |
| Python 3.7+ | Сборка бандла (`bundle_project.py`) |
| Python 3.11+ / markdown | Сборка документации (`build_docs.py`) |

---

## 4. Структура проекта (актуальная на 22 ноября 2026)

Локальная копия находится в `/path/to/site/` (структура полностью совпадает с серверной).

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
    ├── page.php                      # Универсальная страница: type=product/blog/news/promotion/page, форма отзыва, seo_title
    ├── 404.php                       # Страница 404 (отдаёт код 404, не 200)
    ├── admin.php                     # Панель управления (исключён из кастомизации)
    ├── auth.php                      # Вход (bcrypt, локальный Roboto, noindex)
    ├── logout.php                    # Полная очистка сессии
    ├── api.php                       # API (GET, PUT, POST, change-password, reviews-submit, pages, отправка почты)
    ├── upload.php                    # Загрузка + WebP (из уменьшенной) + сжатие JPG-оригинала + транслит кириллицы
    ├── backup.php                    # Генерация ZIP-архива
    ├── sitemap.php                   # sitemap.xml (+ пагинация, + pages.json, исправлен fileMtime)
    ├── robots.php                    # robots.txt (+ AI-краулеры, + закрытие admin/api/data/auth/logout/backup)
    ├── rename-all-images.php         # Одноразовый скрипт SEO-переименования старых картинок
    ├── batch-webp.php                # Одноразовый скрипт WebP-конвертации (исторический, см. scripts/optimize-uploads.php)
    ├── build_docs.py                 # Конвертер MD-документации в HTML для лендинга
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
    │   ├── header.php                # Шапка (меню, соцсети, логотип, ссылка на /about из pages.json)
    │   ├── footer.php                # Подвал, корзина, модалки, счётчик, ссылка на /about
    │   ├── mailer.php                # Обёртка над PHPMailer (отправка писем)
    │   ├── mail-config.php           # SMTP-настройки (mail.example.com:587, STARTTLS, noreply@) — ПАРОЛИ ЗДЕСЬ
    │   ├── email-templates.php       # Шаблоны писем (новый заказ, новый отзыв, смена статуса)
    │   ├── PHPMailer/                # Библиотека PHPMailer (PHPMailer.php, SMTP.php, Exception.php)
    │   ├── .htaccess                 # Защита includes/
    │   └── /blocks/
    │       ├── promotions.php        # Блок акций
    │       └── reviews.php           # Блок отзывов
    │
    ├── /data/                        # Данные (JSON)
    │   ├── settings.json             # Настройки + admin_login + admin_password_hash + max + site_seo_title + GA4 ID + Метрика ID
    │   ├── products.json             # + seo_title, old_price, stock_status (22.11.2026)
    │   ├── pages.json                # Произвольные страницы (04.11.2026): slug, title, content, image, seo_title, seo_description, status, show_in_menu, sort_order, created_at, updated_at
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
    ├── /scripts/
    │   └── optimize-uploads.php      # Принудительная оптимизация старых картинок (WebP из уменьшенной + сжатие JPG, 11.11.2026)
    ├── /doc/                         # Документация (эти файлы)
    │   ├── assets/docs.css           # Стили документации (создаётся build_docs.py)
    │   └── *.html                    # Сгенерированные HTML-страницы документации
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
- Простое развёртывание, бэкап, перенос. Не нужен MySQL.
- Минус — нет транзакций; защита от race condition — `flock()` (см. 5.10).

### 5.2. Маршрутизация (ЧПУ)

| URL | Обработчик |
|-----|------------|
| `/catalog`, `/blog`, `/news`, `/contacts`, `/faq`, `/promotions`, `/reviews`, `/search` | соответствующие `.php` |
| `/<slug>` | `page.php?type=page&slug=<slug>` (произвольные страницы из `pages.json`, 04.11.2026) |
| `/product/nazvanie.html` | `page.php?type=product&slug=nazvanie` |
| `/blog/nazvanie.html` | `page.php?type=blog&slug=nazvanie` |
| `/news/nazvanie.html` | `page.php?type=news&slug=nazvanie` |
| `/promotion/nazvanie.html` | `page.php?type=promotion&slug=nazvanie` |
| `/api/products?search=...` | `api.php?action=products` |
| `/api/pages` | `api.php?action=pages` (GET/PUT, 04.11.2026) |
| `/api/change-password` | `api.php?action=change-password` (POST) |
| `/api/reviews-submit` | `api.php?action=reviews-submit` (POST) |
| `/backup.php` | Генерация ZIP (требует авторизации) |
| `/sitemap.xml` | `sitemap.php` (`Options -MultiViews` обязателен) |
| `/robots.txt` | `robots.php` |

**Редиректы** (`.htaccess`): HTTP → HTTPS (301), WWW → без WWW (301).
**404:** в `index.php` проверка мусорных URL → `404.php` (код 404). В `robots.php` закрыты `/auth.php`, `/logout.php`, `/backup.php`, `/admin.php`, `/admin`. В `auth.php`/`admin.php` — `noindex, nofollow`.

### 5.3. Автоматическое определение домена

В `includes/config.php`: `SITE_URL` (по протоколу и `HTTP_HOST`), `session_start()`, подключение `helpers.php`, `icons.php`, `counter.php`, вызов `trackVisitor()`.

### 5.4. Динамическая подгрузка и пагинация

- Товары, блог, новости, отзывы, акции — через `script.js` (fetch к API).
- Пагинация — `?page=N` + `history.pushState`.
- Корзина — `localStorage`.

### 5.5. Темы оформления

Три темы: **тёмная** (по умолчанию), **светлая**, **лесная**. Переключение в `localStorage` (ключ `amanita_theme`).

### 5.6. Админ-панель

Кратко: аутентификация (bcrypt + `password_verify` + `hash_equals` + `session_regenerate_id`), Summernote (исключён из кастомизации), загрузка изображений через `upload.php`, тосты. **Подробно — см. `03-admin.md`.**

### 5.7. Изображения и WebP

- **Загрузка** через `upload.php`: `finfo` + `getimagesize()`, транслит кириллицы, проверка дубликатов.
- **11.11.2026:** WebP создаётся **из уменьшенной копии** (макс. 1200px, качество 82); JPG-оригинал **сжимается** (качество 75, макс. 1200px) и остаётся fallback.
- **PHP-вывод:** `renderPicture()` → `<picture>` с WebP. **JS-вывод:** `buildPicture()`.
- **Fallback:** браузеры без WebP — оригинал. OG/Schema — на JPEG.
- **Старые картинки:** `rename-all-images.php` (SEO-имена), `scripts/optimize-uploads.php` (WebP + сжатие JPG).
- **Подробно — см. `07-images.md`.**

### 5.8. Безопасность

Кратко: защита `/data/`, `/uploads/`, `/includes/`; MIME + `getimagesize()`; `htmlspecialchars()`; CSRF через `same-origin`; спам-защита отзывов (honeypot + капча + rate-limit); `Options -Indexes`; кеширование; двойная блокировка сканеров (`.htaccess` + `counter.php`); пароли почты в `mail-config.php`. **Подробно — см. `06-security.md`.**

### 5.9. SEO-2026 и GEO

Кратко: canonical (self-referencing, UTM вырезаются), OG/Twitter, `noindex` для фильтров и `auth/admin`, Schema.org (`Product`, `Article`, `NewsArticle`, `FAQPage`, `BreadcrumbList`, `ItemList`, `OnlineStore`, `WebPage` для `/<slug>`, `Organization` для `/about`), sitemap с `pages.json`, robots с AI-краулерами, `llms.txt`, `ai-catalog.json` (ARD), 301, `404.php` (код 404), `Options -MultiViews`. **Подробно — см. `05-seo.md`.**

### 5.10. Счётчик уникальных посетителей

- `trackVisitor()` в `includes/counter.php`.
- Cookie `visitor_id` на 1 год — только после успешной записи.
- Фильтры: админы, боты, API/AJAX, не-GET. Защита от многократного вызова: `static $alreadyTracked`.
- Хранение: `data/counter.json`. Начальное значение: 1000. Вывод: `getVisitorCount()` в футере.
- **КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ (19.10): race condition.**
  - Причина: `counter.json` читался/писался одновременно (бот `35.240.58.49`). На Windows `rename()` не атомарен → файл пустел → `readCounter()` возвращал 1000 → сброс.
  - Решение: `flock()` — эксклюзивная блокировка на 5 секунд + отдельный `counter.lock`; чтение и запись под блокировкой; cookie — после успешной записи.
  - Проверка: `counter.log` — при сбросе покажет причину.

### 5.11. Резервное копирование

`backup.php` — ZIP с `/data/` + `/uploads/` (требует авторизации). Имя: `<домен>-backup-YYYY-MM-DD-HHMMSS.zip`. В архив: все `*.json`, все файлы из `/uploads/` (кроме `.htaccess`), `README.txt`.

### 5.12. Статусы заказов

Внутренние метки, видны только администратору: 🆕 Новый / ⏳ В обработке / 📦 Отправлен / ✅ Выполнен. «Новый» — красная подсветка слева + бейдж. При смене статуса бейдж уменьшается/исчезает. **Подробно — см. `03-admin.md`, раздел 6.**

### 5.13. Оптимизация PageSpeed

Кратко: WebP (−32%), инлайн `themes.css` (−450 мс), удалён `all.min.css` (−750 мс, −25 КБ), Font Awesome → inline-SVG (−25 КБ), логотип `fetchpriority` + `width`/`height` (LCP ↓, CLS = 0), логотип сжат (50 КБ → 16 КБ), preload шрифтов (−150 мс). CSS-блокировка: 1960 мс → ~600 мс. Осталось: LCP ~2 сек (окно 18+), `tailwind-output.css` внешний. **Подробно — см. `09-performance.md`.**

### 5.14. Индикаторы событий (для админа)

Бейджи на вкладках «⭐ Отзывы» и «📦 Заказы», общий счётчик в заголовке, favicon-бейдж, подсветка карточек, авто-проверка каждые 60 секунд, всплывающие уведомления. Отзыв «новый» при `status: 0`, заказ — при `status: "Новый"`. **Подробно — см. `03-admin.md`, раздел 7.**

### 5.15. Почта и уведомления

- Аккаунт `noreply@example.com` разблокирован (была ошибка `550 User account is not allowed to send emails`).
- `includes/mail-config.php`: SMTP `mail.example.com:587`, STARTTLS, отправитель `noreply@example.com`. **Пароли здесь** (не в публичных файлах).
- Шаблоны писем — `includes/email-templates.php`. Обёртка — `includes/mailer.php`.
- Уведомления: новый заказ, новый отзыв, смена статуса заказа.
- **Внимание:** `pcweb@mail.ru` отправляет в спам (проблема Mail.ru). Для уведомлений админа используем Яндекс.
- Внешние SMTP (Яндекс.Почта для домена, Gmail) — резерв на будущее.
- **Подробно — см. `04-mail.md`.**

### 5.16. Аналитика (GA4 и Метрика)

- **GA4:** ID `G-LSRBZ45NLB` вписан, подключён. GTM не грузится напрямую (`ERR_NAME_NOT_RESOLVED` для `fonts.googleapis.com`), обход — файл `hosts` (`142.250.74.163 fonts.googleapis.com`).
- **Яндекс.Метрика:** **подключена (22.11.2026)**, ID вписан, визиты видны в «Realtime».
- **Подробно — см. `08-analytics.md`.**

### 5.17. Уникализация CMS (`customize_cms.py`)

Копирует CMS, заменяет CSS-классы, удаляет HTML/CSS-комментарии, перемешивает блоки внутри `<main>`. Расположение: `script1/customize_cms.py`. Требования: Python 3.7+, PyYAML. Конфигурация: `script1/config.yaml`.

**КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ (23.10):** `admin.php`, `admin.js`, `jquery`, `summernote` **исключены** (добавлен `skip_process`). До этого админка и Summernote ломались после кастомизации.

**Dry-run:** `python customize_cms.py --dry-run --seed 12345`. Результат последнего dry-run: 1892 замены, 0 ошибок.

**Roadmap:** `--prefix ekb`, `--prefix nsk` (Екатеринбург, Новосибирск).

### 5.18. Bundle-скрипт (`bundle_project.py`)

Собирает значимые файлы в `bundle/project_bundle.txt` (413 КБ). Автоматически чистит `admin_password_hash` и `admin_login` из `settings.json`. Расположение: `script2/bundle_project.py`. Запуск: `python bundle_project.py`.

### 5.19. Скрипт сборки документации (`build_docs.py`)

Конвертирует MD-файлы из `doc/` в HTML для лендинга `amanitacms.ru`. Расположение: `build_docs.py` (в корне). Требования: Python 3.7+, `pip install markdown`. Результат: `doc/*.html`, `doc/index.html`, `doc/assets/docs.css`.

**Особенности:** идемпотентный (`doc/*.md` не трогает), единый шаблон (шапка, сайдбар по 15 страницам, подсветка кода), при отсутствии MD — ссылка «(нет файла)», CSS создаётся один раз (можно править вручную). Запуск: `python build_docs.py` из корня.