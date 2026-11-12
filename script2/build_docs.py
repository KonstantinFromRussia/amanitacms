# build_docs.py
# Конвертер MD-документации AmanitaCMS в HTML-лендинг.
# Запуск: python build_docs.py
# Требования: pip install markdown
# Идемпотентный: перезапускается, doc/*.md не трогает, пишет doc/*.html и doc/index.html.
# Правило проекта: старые комментарии не удаляем, новые добавляем.

import os
import re
import markdown

# --- Конфигурация ---
DOC_DIR = "doc"
CSS_PATH = "doc/assets/docs.css"       # стили документации (отдельный файл)
TITLE_SUFFIX = " — AmanitaCMS"          # суффикс <title>
SITE_URL = "https://amanitacms.ru"      # для canonical и ссылок

# --- Список страниц документации (№ + название + файл) ---
# Источник: таблица из контекста проекта. Порядок сохраняем.
DOC_PAGES = [
    ("01", "Архитектура",     "01-architecture.md"),
    ("02", "Установка",       "02-install.md"),
    ("03", "Админ-панель",    "03-admin.md"),
    ("04", "Почта",           "04-mail.md"),
    ("05", "SEO-2026",        "05-seo.md"),
    ("06", "Безопасность",    "06-security.md"),
    ("07", "Изображения",     "07-images.md"),
    ("08", "Аналитика",       "08-analytics.md"),
    ("09", "Производительность", "09-performance.md"),
    ("10", "Тестирование",    "10-testing.md"),
    ("11", "Roadmap",         "11-roadmap.md"),
    ("12", "Кастомизация",    "12-customization.md"),
    ("13", "Правила работы",  "13-rules.md"),
    ("14", "Перенос в чат",   "14-transfer.md"),
    ("15", "Лицензия",        "15-license.md"),
]

# --- Расширения markdown ---
MD_EXTENSIONS = ["tables", "fenced_code", "toc", "sane_lists", "nl2br"]


def read_text(path):
    """Чтение файла в UTF-8 с мягкой обработкой ошибок."""
    with open(path, "r", encoding="utf-8", errors="replace") as f:
        return f.read()


def write_text(path, content):
    """Запись файла в UTF-8, создаём папку при необходимости."""
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)


def strip_first_h1(html):
    """Убираем первый <h1> из тела: заголовок страницы выводим в шаблоне.
    Остальные h1 (если есть) не трогаем — это осознанно."""
    m = re.search(r"<h1[^>]*>.*?</h1>", html, flags=re.DOTALL)
    if m:
        html = html[:m.start()] + html[m.end():]
    return html


def build_sidebar(current_file):
    """Сайдбар: список всех 15 страниц. Текущая подсвечивается.
    Отсутствующие файлы помечаются как «нет файла», но ссылка остаётся."""
    items = []
    for num, title, fname in DOC_PAGES:
        html_name = fname.replace(".md", ".html")
        exists = os.path.exists(os.path.join(DOC_DIR, fname))
        cls = "doc-nav__link"
        if fname == current_file:
            cls += " doc-nav__link--active"
        note = "" if exists else ' <span class="doc-nav__missing">(нет файла)</span>'
        items.append(
            f'<li><a class="{cls}" href="{html_name}">'
            f'<span class="doc-nav__num">{num}</span>{title}{note}</a></li>'
        )
    return (
        '<nav class="doc-nav" aria-label="Документация">'
        '<a class="doc-nav__home" href="../index.html">← На главную</a>'
        '<ol class="doc-nav__list">' + "".join(items) + "</ol></nav>"
    )


def page_template(title, body_html, sidebar_html, canonical):
    """Единый HTML-шаблон страницы документации."""
    return f"""<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title}{TITLE_SUFFIX}</title>
<link rel="canonical" href="{SITE_URL}/{canonical}">
<link rel="stylesheet" href="assets/docs.css">
</head>
<body>
<div class="doc-layout">
{sidebar_html}
<main class="doc-content">
{body_html}
</main>
</div>
</body>
</html>
"""


def convert_file(num, title, fname):
    """Конвертация одного MD-файла в HTML. Возвращает True/False."""
    src = os.path.join(DOC_DIR, fname)
    if not os.path.exists(src):
        print(f"[skip] нет файла: {src}")
        return False

    md_text = read_text(src)
    # Важно: markdown сам экранирует, но fenced_code нужен до tables.
    body = markdown.markdown(md_text, extensions=MD_EXTENSIONS)
    body = strip_first_h1(body)

    sidebar = build_sidebar(fname)
    canonical = f"doc/{fname.replace('.md', '.html')}"
    html = page_template(title, body, sidebar, canonical)

    dst = os.path.join(DOC_DIR, fname.replace(".md", ".html"))
    write_text(dst, html)
    print(f"[ok] {src} -> {dst}")
    return True


def build_index():
    """Страница-оглавление документации doc/index.html."""
    rows = []
    for num, title, fname in DOC_PAGES:
        html_name = fname.replace(".md", ".html")
        exists = os.path.exists(os.path.join(DOC_DIR, fname))
        note = "" if exists else ' <span class="doc-nav__missing">(нет файла)</span>'
        rows.append(
            f"<tr><td>{num}</td>"
            f'<td><a href="{html_name}">{title}</a>{note}</td></tr>'
        )
    table = (
        '<table class="doc-index"><thead><tr><th>№</th><th>Файл</th></tr></thead>'
        "<tbody>" + "".join(rows) + "</tbody></table>"
    )
    body = (
        '<h1>Документация AmanitaCMS</h1>'
        '<p>Полное описание проекта. Выберите раздел:</p>' + table
    )
    sidebar = (
        '<nav class="doc-nav" aria-label="Документация">'
        '<a class="doc-nav__home" href="../index.html">← На главную</a>'
        '<p class="doc-nav__hint">Оглавление</p></nav>'
    )
    html = page_template("Документация", body, sidebar, "doc/index.html")
    write_text(os.path.join(DOC_DIR, "index.html"), html)
    print("[ok] doc/index.html")


def ensure_css():
    """Создаём doc/assets/docs.css, если его нет. Не перезаписываем —
    стили можно править вручную."""
    if os.path.exists(CSS_PATH):
        print(f"[keep] {CSS_PATH}")
        return
    css = """/* AmanitaCMS — стили документации */
:root{--bg:#1a1a1a;--fg:#e8e8e8;--muted:#9a9a9a;--accent:#c0392b;--panel:#242424;--border:#333}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--fg);font:16px/1.6 -apple-system,Segoe UI,Roboto,sans-serif}
a{color:#e07a5f}
.doc-layout{display:grid;grid-template-columns:280px 1fr;min-height:100vh}
.doc-nav{position:sticky;top:0;align-self:start;height:100vh;overflow:auto;padding:20px;background:var(--panel);border-right:1px solid var(--border)}
.doc-nav__home{display:block;margin-bottom:16px;text-decoration:none;font-weight:600}
.doc-nav__hint{color:var(--muted);font-size:14px}
.doc-nav__list{list-style:none;margin:0;padding:0}
.doc-nav__link{display:flex;gap:8px;padding:6px 8px;border-radius:6px;text-decoration:none;color:var(--fg);font-size:14px}
.doc-nav__link:hover{background:var(--border)}
.doc-nav__link--active{background:var(--accent);color:#fff}
.doc-nav__num{color:var(--muted);min-width:22px}
.doc-nav__link--active .doc-nav__num{color:#fff}
.doc-nav__missing{color:#b06a6a;font-size:12px}
.doc-content{max-width:820px;padding:32px 40px}
.doc-content h1{margin-top:0}
.doc-content table{width:100%;border-collapse:collapse;margin:16px 0}
.doc-content th,.doc-content td{border:1px solid var(--border);padding:8px 10px;text-align:left;vertical-align:top}
.doc-content pre{background:#111;padding:14px;border-radius:8px;overflow:auto}
.doc-content code{background:#111;padding:2px 5px;border-radius:4px}
.doc-content pre code{padding:0;background:none}
.doc-content blockquote{border-left:3px solid var(--accent);margin:0;padding-left:14px;color:var(--muted)}
@media(max-width:800px){
  .doc-layout{grid-template-columns:1fr}
  .doc-nav{position:static;height:auto;border-right:0;border-bottom:1px solid var(--border)}
  .doc-content{padding:20px}
}
"""
    write_text(CSS_PATH, css)
    print(f"[ok] {CSS_PATH}")


def main():
    ensure_css()
    ok = 0
    for num, title, fname in DOC_PAGES:
        if convert_file(num, title, fname):
            ok += 1
    build_index()
    print(f"\nГотово: {ok}/{len(DOC_PAGES)} страниц сконвертировано.")


if __name__ == "__main__":
    main()