# 05. SEO-2026 и GEO

**Проект:** Muhomor.spb.ru (AmanitaLove.ru) · **Версия CMS:** 2.5 · **Актуально на:** 24 октября 2026

---

## 1. Обзор

SEO-блок проекта закрывает:

- **Классическое SEO:** canonical, OG, Schema.org, sitemap, robots, 301-редиректы, 404.
- **GEO (Generative Engine Optimization)** — оптимизацию под AI-агентов (ChatGPT, Perplexity, Claude, Google AI): `llms.txt`, `ai-catalog.json` (ARD), разрешения для AI-краулеров в robots.
- **E-E-A-T** — через `about.php` с микроразметкой `Person` (опыт, экспертиза, авторитет, доверие).
- **Техническое SEO** — `Options -MultiViews`, исправление `sitemap.xml`, код 404.

---

## 2. Метатеги и canonical

### 2.1. Self-referencing canonical

- В `includes/head.php` выводится `<link rel="canonical" href="...">` — **self-referencing** (указывает на текущую страницу).
- **UTM-метки вырезаются** из canonical — чтобы не дублировать страницы.
- Для фильтров каталога (`?filter=...`) — canonical на «чистую» версию без параметров.

### 2.2. Open Graph и Twitter

В `head.php` выводятся:
- `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `og:locale`.
- `twitter:card`, `twitter:image`, `twitter:title`, `twitter:description`.

**Типы `og:type`:**
- `product` — страница товара.
- `article` — блог.
- `news` — новости.
- `website` — главная и разделы.

### 2.3. Два поля title

- **`site_name`** — короткое название (например, «Muhomor»).
- **`site_seo_title`** — длинный `<title>` (например, «Muhomor.spb.ru — сушёные мухоморы с доставкой»).

**Где используется:**
- `<title>` на главной — `site_seo_title`.
- `og:site_name` — `site_name`.
- В админке — оба поля.

### 2.4. `seo_title` у товаров

- Поле `seo_title` в `products.json`.
- **Не меняет URL/slug.**
- Используется в `<title>` страницы товара.
- В админке — отдельное поле.

### 2.5. `noindex` для фильтров и служебных страниц

- **Фильтры каталога** (`?filter=...`, `?sort=...`, `?page=...` кроме первой) — `noindex, follow`.
- **`auth.php`** и **`admin.php`** — `noindex, nofollow`.
- **`robots.php`** закрывает: `/auth.php`, `/logout.php`, `/backup.php`, `/admin.php`, `/admin`.

---

## 3. Schema.org (структурированные данные)

В `head.php` и на страницах выводятся:

| Тип | Где |
|-----|-----|
| `Product` | Страница товара (цена, наличие, отзывы) |
| `Article` | Статья блога |
| `NewsArticle` | Новость |
| `FAQPage` | FAQ |
| `BreadcrumbList` | Хлебные крошки на всех страницах |
| `ItemList` | Списки (каталог, блог, новости) |
| `OnlineStore` | Главная (org-разметка магазина) |
| `Person` | `about.php` (E-E-A-T) |

**`about.php`** — страница «О Грибомане»:
- Микроразметка `Person`.
- Фото `/uploads/griboman.webp`.
- Ссылки в `header.php` и `footer.php`.
- Задача — усилить E-E-A-T для Google (опыт, экспертиза, авторитет, доверие).

---

## 4. Sitemap

**Файл:** `sitemap.php`
**URL:** `/sitemap.xml`

**Что включено:**
- Главная, каталог, блог, новости, акции, FAQ, отзывы, контакты, about.
- Все товары (`/product/slug.html`).
- Все статьи блога (`/blog/slug.html`).
- Все новости (`/news/slug.html`).
- Все акции (`/promotion/slug.html`).
- Пагинация разделов.

**Атрибуты:**
- `lastmod` — дата последнего изменения.
- `priority` — приоритет (главная 1.0, разделы 0.8, товары 0.7, статьи 0.6).
- `changefreq` — частота изменений.

**Исправленная ошибка (19.10):**
- Была: `Fatal error: Cannot redeclare fileMtime()`.
- Причина: функция `fileMTime()` конфликтовала со встроенной `filemtime()` (PHP нечувствителен к регистру).
- Решение: переименована в `getFileMtime()`, все вызовы обновлены.

**`Options -MultiViews`** в `.htaccess` — обязателен, иначе `sitemap.xml` не открывается на Apache/LiteSpeed.

---

## 5. Robots.txt

**Файл:** `robots.php`
**URL:** `/robots.txt`

**Что настроено:**

- **Разрешения для AI-краулеров:** GPTBot, PerplexityBot, Google-Extended, ClaudeBot, CCBot.
- **Запреты для служебных страниц:** `/auth.php`, `/logout.php`, `/backup.php`, `/admin.php`, `/admin`, `/api/`, `/data/`.
- **Sitemap:** `Sitemap: https://muhomor.spb.ru/sitemap.xml`.

**При смене домена** — зайдите в админку → «Настройки» → сохраните (обновит `robots.txt`).

---

## 6. GEO — оптимизация под AI-агентов

### 6.1. `llms.txt`

**Файл:** `llms.txt` (в корне).

**Назначение:** описание сайта для AI-агентов (что за сайт, какие разделы, какие товары, как связаться). Стандарт `llms.txt` — аналог `robots.txt`, но для LLM.

**Что внутри:**
- Название сайта, описание.
- Ссылки на ключевые разделы.
- Краткая информация о товарах.
- Контакты.

### 6.2. `ai-catalog.json` (ARD)

**Файлы:** `/.well-known/ai-catalog.json`, `/.well-known/ard.json`.

**Назначение:** ARD (AI Resource Directory) — каталог ресурсов для AI-агентов. Дубликат для новой спецификации.

**Что внутри:**
- Список ресурсов (страницы, API, данные).
- Типы контента (товары, статьи, FAQ).
- Метаданные (язык, регион, валюта).

### 6.3. Разрешения в robots.txt

AI-краулеры **явно разрешены** в `robots.php`:
- `GPTBot` (OpenAI).
- `PerplexityBot` (Perplexity).
- `Google-Extended` (Google AI).
- `ClaudeBot` (Anthropic).
- `CCBot` (Common Crawl).

Это позволяет AI-агентам индексировать сайт и отвечать на запросы пользователей со ссылкой на источник.

---

## 7. Техническое SEO

### 7.1. 301-редиректы

В `.htaccess`:
- HTTP → HTTPS (301).
- WWW → без WWW (301).

### 7.2. 404

- `404.php` — красивая страница 404.
- **Отдаёт код 404** (не 200). Тестировщик подтвердил.
- В `index.php` добавлена проверка на мусорные URL → отдаёт `404.php`.

### 7.3. ЧПУ

- Все URL — человекочитаемые (`/product/nazvanie.html`).
- `mod_rewrite` в `.htaccess`.

### 7.4. `Options -MultiViews`

- В `.htaccess` — обязательно для корректной работы `sitemap.xml` и ЧПУ на Apache/LiteSpeed.

---

## 8. Контент

- **3 SEO-лонгрида** для блога — тексты написаны и переданы.
- **HTML-шаблон подробного описания товара** — с составом, дозировкой, доставкой. Написан.
- **Страница «О Грибомане»** (`about.php`) — Schema.org Person, фото, E-E-A-T.

---

## 9. Чек-лист SEO

- [ ] `site_seo_title` заполнен в настройках
- [ ] `seo_title` у товаров заполнен
- [ ] Canonical self-referencing работает (UTM вырезаются)
- [ ] OG-теги выводятся (`og:title`, `og:image`, `og:type`, `og:locale`)
- [ ] `twitter:image` выводится
- [ ] Schema.org: `Product`, `Article`, `NewsArticle`, `FAQPage`, `BreadcrumbList`, `ItemList`, `OnlineStore`, `Person`
- [ ] Sitemap открывается (`/sitemap.xml`), `lastmod` и `priority` корректны
- [ ] Robots.txt открывается (`/robots.txt`), AI-краулеры разрешены, служебные закрыты
- [ ] `llms.txt` доступен
- [ ] `/.well-known/ai-catalog.json` доступен
- [ ] `/.well-known/ard.json` доступен
- [ ] 301-редиректы HTTP→HTTPS, WWW→без WWW работают
- [ ] 404 отдаёт код 404 (не 200)
- [ ] `about.php` открывается, Schema.org Person выводится
- [ ] 3 SEO-лонгрида опубликованы в блоге
- [ ] HTML-шаблон описания товара применён
- [ ] `Options -MultiViews` в `.htaccess`

---

## 10. Что осталось (Roadmap)

- **Яндекс.Метрика** — не подключена (см. `08-analytics.md`).
- **Внешние ссылки (E-E-A-T)** — ВК, YouTube (см. `11-roadmap.md`, средний приоритет).
- **Больше лонгридов** — по мере необходимости.