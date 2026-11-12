# Уникализация flat-file CMS

Два независимых скрипта для создания уникальной копии сайта: свои имена CSS-классов, удалённые комментарии, минифицированный CSS, изменённые URL разделов. Каждый скрипт — со своим конфигом.

## Состав папки

| Файл | Назначение |
|---|---|
| `customize_urls.py` | **Вариант 1.** Меняет URL разделов (`/blog` → `/blogmsk`), файлы не переименовывает. |
| `config_urls.yaml` | Конфиг для Варианта 1. |
| `customize_files.py` | **Вариант 2.** Переименовывает файлы (`blog.php` → `blogmsk.php`) и делает подстрочную замену по всем текстовым файлам. |
| `config_files.yaml` | Конфиг для Варианта 2. |

Все четыре файла — в одной папке. Пути `source` и `destination` в конфигах относительные, от папки скриптов.

## Какой вариант выбрать

**Вариант 1 — `customize_urls.py`**
- Префиксует CSS-классы, удаляет комментарии, минифицирует CSS, перемешивает блоки на главной, меняет URL разделов в `.htaccess` и HTML.
- Файлы **не** переименовываются. Правило в `.htaccess`: `^blogmsk$ → blog.php`.
- Минимальный риск, детальные ЧПУ (`/blog/slug.html`) работают без изменений.
- В исходном коде (`Ctrl-U`) имена файлов остаются старыми.

**Вариант 2 — `customize_files.py`**
- Всё то же плюс переименование файлов разделов и подстрочная замена `blog` → `blogmsk` и т.д. во всех текстовых файлах.
- Сторонние библиотеки (`PHPMailer`, `summernote`, `jquery`, `tailwind-output.css`) не трогаются.
- В исходном коде видны новые имена. Более инвазивно.

**Рекомендация:** для первого опыта — Вариант 1. Для максимальной «непохожести» — Вариант 2.

## Требования

- Python 3.8+
- PyYAML: `pip install pyyaml`

## Быстрый старт

1. Скопировать исходный сайт в `./muhomor-spb` (или указать путь в `source.path`).
2. Установить PyYAML.
3. Открыть командную строку в папке скриптов.
4. Проверочный прогон:
   ```
   python customize_urls.py --dry-run
   ```
   или
   ```
   python customize_files.py --dry-run
   ```
5. Реальный прогон:
   ```
   python customize_urls.py
   ```
   или
   ```
   python customize_files.py
   ```
6. **Перезапустить OpenServer.** Обязательно: PHP держит старые версии файлов в OPcache, без перезапуска возможны «мистические» 404 и `Failed to fetch`.
7. Открыть сайт, проверить разделы и админку.

## Параметры командной строки

| Ключ | Назначение |
|---|---|
| `--dry-run` | Ничего не записывает, показывает план. Использовать первым. |
| `--config <путь>` | Другой конфиг. |
| `--prefix <слово>` | Переопределить префикс; `destination.path` тоже меняется (`./muhomor-msk` → `./muhomor-ekb`). |
| `--dest <путь>` | Переопределить папку назначения. Перекрывает `--prefix`. |
| `--seed <число>` | Фиксирует случайность (перемешивание блоков). |

Примеры:
```
python customize_urls.py --prefix ekb
python customize_files.py --dry-run
python customize_urls.py --dest ./test-output
```

## Структура конфига

Конфиги почти одинаковые. Отличие — последняя секция: `urls` (Вариант 1) или `files_rename` (Вариант 2).

### `source`

```yaml
source:
  path: "./muhomor-spb"
  exclude:
    - ".git"
    - "node_modules"
    - "*.log"
  skip_process:
    - "admin.php"
    - "api.php"
```

- `exclude` — не копировать вообще.
- `skip_process` — копировать как есть, **без** префиксации CSS-классов. Пути к CSS/JS в них всё равно заменяются (см. `renames`) — иначе админка получит 404.

### `destination`

```yaml
destination:
  path: "./muhomor-msk"
  overwrite: false
  backup_before_overwrite: true
```

Бэкап: `muhomor-msk_backup_YYYYMMDD_HHMMSS`.

### `unique`

```yaml
unique:
  prefix: "msk"
  mode: "safe"   # или "aggressive"
```

### `files`

```yaml
files:
  extensions: [".php", ".css", ".js", ".html", ".htm"]
  encoding:
    read: "utf-8"
    fallback: "cp1251"
    write: "utf-8"
```

### `renames`

```yaml
renames:
  - from: "/css/tailwind-output.css"
    to: "/css/tailwind-outputmsk.css"
    file_rename: "css/tailwind-output.css -> css/tailwind-outputmsk.css"
    files: [".php", ".html"]
```

- `file_rename` — физическое переименование.
- `from` / `to` — замена пути в файлах из `files`.
- Пути заменяются **всегда**, включая `skip_process`-файлы.

### `html`, `css`, `cleanup`

```yaml
html:
  remove_comments: true
  remove_css_comments_in_style: true
  minify_css: true
  shuffle_index_blocks: true
  fixed_first_block: "Hero"
  shuffle_blocks: [...]

css:
  remove_comments: true
  minify_css: true

cleanup:
  enabled: true
  remove_extra_blank_lines: true
  strip_trailing_spaces: true
```

### `tailwind_patterns`

Регулярные выражения. Классы, совпавшие с паттерном, **не** префиксуются. По умолчанию — Tailwind-утилиты и служебные классы.

### `skip_classes`

Явный белый список классов, которые **не** префиксуются (например, `btn`, `modal`, `cart-sidebar`). Добавляйте сюда всё, что завязано на JS.

### `urls` — только в `config_urls.yaml`

```yaml
urls:
  enabled: true
  renames:
    - ["blog", "blogmsk"]
    - ["news", "newsmsk"]
```

- `.htaccess`: `^blog$ → blog.php` становится `^blogmsk$ → blog.php` (файл не переименовывается).
- HTML: `"/blog"` → `"/blogmsk"`, `'/blog?page=2'` → `'/blogmsk?page=2'`.
- `type=blog` в детальных ЧПУ и JSON-ключах **не трогается**.

### `files_rename` — только в `config_files.yaml`

```yaml
files_rename:
  enabled: true
  pairs:
    - ["blog", "blogmsk"]
    - ["news", "newsmsk"]
  text_exts: [".php", ".html", ".htm", ".js", ".css", ".json", ".xml", ".txt"]
  extra_files: [".htaccess"]
  skip_dirs: ["PHPMailer", "summernote"]
  skip_files: ["jquery-3.6.0.min.js", "tailwind-output.css"]
  skip_exact: ["bundle/project_bundle.txt"]
```

- Переименовываются все файлы, у которых имя без расширения точно совпадает со старым (`blog.php`, `blog.json`, `includes/blocks/blog.php` → все `blogmsk.*`).
- Подстрока `<старое>` → `<новое>` заменяется во всех файлах из `text_exts` и `extra_files`, **включая** `admin.php`, `api.php`, `admin.js`.
- `skip_dirs`, `skip_files`, `skip_exact` — что не трогать.

### `logging`, `report`

```yaml
logging:
  enabled: true
  level: "INFO"
  file: "logs/customize_urls_{date}.log"
  console: true

report:
  enabled: true
  file: "reports/report_urls_{prefix}_{date}.json"
  include_replaced: true
```

## Результат

- Папка назначения — готовый сайт.
- `logs/customize_*_YYYY-MM-DD.log` — полный лог.
- `reports/report_*_YYYY-MM-DD.json` — отчёт со статистикой и списком замен.
- Бэкап предыдущей версии `muhomor-msk_backup_*`, если папка уже была.

## Частые проблемы

**404 на `styles.css` / `tailwind-output.css` в админке.**
Причина: в `admin.php` оставались старые пути. В актуальной версии исправлено — пути заменяются всегда, включая `skip_process`-файлы.

**«Не удалось загрузить каталог» на всех страницах, кроме главной.**
Причины:
1. OPcache держит старый `api.php` — перезапустить OpenServer.
2. HTTPS с самоподписанным сертификатом блокирует `fetch()`. Лечится тестом по `http://`, доверенным сертификатом в OpenServer или принятием сертификата для всего домена.
3. Рассинхрон `admin.js` ↔ `api.php` — проверить поиском `blog` / `blogmsk` в обоих.

**`Failed to fetch` в админке.**
То же, что выше. В первую очередь — перезапуск OpenServer.

**Ошибки «исчезают через несколько переходов».**
OPcache или кеш браузера. `Ctrl+Shift+Del` чистит только браузер. После правки `.php` — перезапуск OpenServer. После `.js`/`.css` — `Ctrl+F5` или «Disable cache» в DevTools.

**Изменения не видны в браузере.**
`.htaccess` кеширует `.js`/`.css` на 30 дней. `Ctrl+F5`, «Disable cache» или `script.js?nocache=123`.

**Бэкап не создался.**
Права на запись в родительскую папку относительно `destination.path`.

**«Префикс невалиден».**
Формат: `^[a-z][a-z0-9]{1,9}$` — строчная латинская буква, затем до 9 строчных букв/цифр.

**Кракозябры вместо кириллицы.**
Файл не UTF-8. Скрипт пробует `read` → `fallback`. Если нужна другая кодировка — поменяйте `files.encoding.fallback`.

## Рекомендуемый порядок работы

1. Сайт в `./muhomor-spb`.
2. `--dry-run` → проверить лог.
3. Реальный прогон.
4. Перезапустить OpenServer.
5. Проверить: главная, разделы, детальные страницы, админка.
6. При проблемах — откат на `muhomor-msk_backup_*`, правка конфига, повтор.

## Что нельзя делать

Не запускать `customize_urls.py` и `customize_files.py` на одну и ту же папку назначения. По умолчанию они пишут в разные (`./muhomor-msk` и `./muhomor-msk-files`) — так и оставьте.