# 🍄 AmanitaCMS

**AmanitaCMS** — лёгкая flat-file CMS без базы данных. Быстрая, безопасная и простая в установке.

**Домен:** [amanitacms.ru](https://amanitacms.ru) · **GitHub:** [KonstantinFromRussia/amanitacms](https://github.com/KonstantinFromRussia/amanitacms) · **Версия:** 2.5 · **Обновлено:** 22 ноября 2026

---

## 📖 О проекте

AmanitaCMS — это движок для создания сайтов, который хранит все данные в обычных JSON-файлах. Никаких MySQL, никаких сложных настроек. Скопировал файлы на хостинг — и сайт работает.

Создан на базе реального интернет-магазина [muhomor.spb.ru](https://muhomor.spb.ru) и доказал свою надёжность в ежедневной работе. Ещё один пример — [amanitalove.ru](https://amanitalove.ru).

---

## ✨ Возможности

- 🛒 **Каталог и магазин:** Товары, корзина (localStorage), оформление заказов.
- 🏷 **Статусы товаров и цены:** Старая цена (зачёркнутая), статус наличия (`in_stock` / `out_of_stock` / `on_order`), Schema.org `availability`. На главной — только товары в наличии. **22.11.2026**
- 📄 **Произвольные страницы:** Создание страниц в админке без правки PHP (О нас, Доставка, Политика). Короткие URL `/<slug>`, меню, sitemap, Schema.org `WebPage`. **04.11.2026**
- 📝 **Блог и новости:** Полноценные разделы с пагинацией и HTML-редактором.
- ⭐ **Отзывы:** Модерация, капча, защита от спама.
- 🔐 **Админ-панель:** bcrypt-хеширование, защита от брутфорса, смена пароля.
- 📧 **Почта:** SMTP (PHPMailer), уведомления о заказах и отзывах.
- 📊 **SEO-2026:** Schema.org, sitemap, `llms.txt`, AI-краулеры.
- 🖼 **WebP:** Автоматическая конвертация изображений при загрузке. **11.11.2026:** WebP создаётся из уменьшенной копии (макс. 1200px), JPG-оригинал сжимается и остаётся как fallback.
- 🎨 **Темы:** Тёмная, светлая, лесная (переключение в localStorage).

---

## 📚 Документация

Полное описание проекта — в папке [`doc/`](doc/). Исходники — в Markdown, для чтения в браузере — HTML-версии (генерируются `build_docs.py`).

| № | Раздел | Markdown | HTML |
|---|--------|----------|------|
| 01 | Архитектура | [MD](doc/01-architecture.md) | [HTML](doc/01-architecture.html) |
| 02 | Установка | [MD](doc/02-install.md) | [HTML](doc/02-install.html) |
| 03 | Админ-панель | [MD](doc/03-admin.md) | [HTML](doc/03-admin.html) |
| 04 | Почта | [MD](doc/04-mail.md) | [HTML](doc/04-mail.html) |
| 05 | SEO-2026 | [MD](doc/05-seo.md) | [HTML](doc/05-seo.html) |
| 06 | Безопасность | [MD](doc/06-security.md) | [HTML](doc/06-security.html) |
| 07 | Изображения | [MD](doc/07-images.md) | [HTML](doc/07-images.html) |
| 08 | Аналитика | [MD](doc/08-analytics.md) | [HTML](doc/08-analytics.html) |
| 09 | Производительность | [MD](doc/09-performance.md) | [HTML](doc/09-performance.html) |
| 10 | Тестирование | [MD](doc/10-testing.md) | [HTML](doc/10-testing.html) |
| 11 | Roadmap | [MD](doc/11-roadmap.md) | [HTML](doc/11-roadmap.html) |
| 12 | Кастомизация | [MD](doc/12-customization.md) | [HTML](doc/12-customization.html) |
| 13 | Правила работы | [MD](doc/13-rules.md) | [HTML](doc/13-rules.html) |
| 14 | Перенос в чат | [MD](doc/14-transfer.md) | [HTML](doc/14-transfer.html) |
| 15 | Лицензия | [MD](doc/15-license.md) | [HTML](doc/15-license.html) |

**Оглавление HTML-версии:** [`doc/index.html`](doc/index.html).

---

## 🚀 Установка

**Требования:** PHP 7.1+, Apache/LiteSpeed, `mod_rewrite`, GD с WebP.

1. Скопируйте файлы на хостинг.
2. Проверьте `.htaccess` в корне и в `/uploads/`.
3. Установите права: `chmod -R 755 data uploads`.
4. Откройте сайт — готово.

**Первый вход:** `/admin.php` → логин `admin`, пароль `secret123` → **сразу смените пароль**.

### Сборка документации (опционально)

Для генерации HTML-версий документации (для лендинга):

```bash
pip install markdown
python build_docs.py
```

Результат: `doc/*.html` + `doc/index.html` + `doc/assets/docs.css`.

### Оптимизация старых изображений (опционально)

Если изображения были загружены до версии 2.5, можно принудительно создать WebP и ужать JPG:

```bash
php scripts/optimize-uploads.php
```

Скрипт создаёт/пересоздаёт `.webp` и уменьшает JPG-оригиналы. **11.11.2026**

---

## 📂 Структура

```text
/
├── index.html                  # Лендинг amanitacms.ru
├── index.php, catalog.php, blog.php, page.php...
├── admin.php, api.php, upload.php
├── build_docs.py               # MD → HTML для документации
├── scripts/optimize-uploads.php # Принудительная оптимизация старых картинок
├── /.well-known/ (ai-catalog.json)
├── /css/ (tailwind-output.css, styles.css, themes.css)
├── /includes/ (mailer.php, PHPMailer/)
├── /data/ (JSON-файлы с контентом: products.json, pages.json, ...)
├── /uploads/ (Изображения + .webp)
└── /doc/ (Документация: *.md, *.html, assets/docs.css)
```

---

## ⚠️ Безопасность

- **Не выкладывайте** `includes/mail-config.php` и `data/settings.json` в публичный доступ.
- **Смените** пароль по умолчанию сразу после установки.
- **Используйте** HTTPS.

---

## 📄 Лицензия

**Proprietary** (проприетарная, с публичным исходным кодом). Подробнее — [`doc/15-license.md`](doc/15-license.md).

Для личного использования и изучения — код доступен. Для коммерческого использования — согласование с владельцем.