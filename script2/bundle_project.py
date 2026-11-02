#!/usr/bin/env python3
"""
bundle_project.py — склеивает значимые файлы проекта в один TXT для передачи в чат.

Использование:
    python bundle_project.py
    python bundle_project.py --src ./muhomor-spb --output ./bundle
    python bundle_project.py --max-size 2  # максимум 2 МБ на часть
"""

import argparse
import json
import os
import re
import sys
from pathlib import Path

# ============================================================
# СПИСОК ЗНАЧИМЫХ ФАЙЛОВ
# ============================================================
FILES = [
    # --- Документация (в начале — чтобы модель сразу поняла контекст) ---
    'README.md',

    # --- Корень: главные страницы ---
    'index.php',
    'about.php',
    'catalog.php',
    'blog.php',
    'news.php',
    'faq.php',
    'promotions.php',
    'reviews.php',
    'search.php',
    'contacts.php',
    'page.php',

    # --- Корень: служебные ---
    '404.php',
    'admin.php',
    'auth.php',
    'logout.php',
    'api.php',
    'upload.php',
    'backup.php',
    'sitemap.php',
    'robots.php',

    # --- Корень: конфиги ---
    '.htaccess',
    'llms.txt',

    # --- CSS ---
    'css/styles.css',
    'css/themes.css',
    'css/tailwind-output.css',

    # --- Includes ---
    'includes/config.php',
    'includes/helpers.php',
    'includes/icons.php',
    'includes/counter.php',
    'includes/head.php',
    'includes/header.php',
    'includes/footer.php',
    'includes/email-templates.php',
    'includes/mail-config.php',
    'includes/mailer.php',
    '.htaccess',

    # --- Includes/blocks ---
    'includes/blocks/promotions.php',
    'includes/blocks/reviews.php',

    # --- JS ---
    'js/script.js',
    'js/admin.js',

    # --- Скрипт уникализации ---
    'script1/customize_cms.py',
    'script1/config.yaml',

    # --- Скрипт переноса ---
    'script2/bundle_project.py',
]

# Файлы, которые нужно специально обработать (очистить от паролей)
SANITIZE_FILES = {
    'data/settings.json': ['admin_password_hash', 'admin_login'],
}

# Файлы, которые добавляются В КОНЕЦ (после кода)
APPEND_FILES = [
    'data/settings.json',  # будет очищен
]

# ============================================================
# ЛОГИКА
# ============================================================

def read_file_safe(path: Path) -> str | None:
    """Читает файл, пробует utf-8, потом cp1251."""
    for enc in ('utf-8', 'cp1251'):
        try:
            return path.read_text(encoding=enc)
        except UnicodeDecodeError:
            continue
        except Exception as e:
            print(f"⚠️  Не удалось прочитать {path}: {e}")
            return None
    print(f"⚠️  Не удалось определить кодировку {path}")
    return None


def sanitize_json(content: str, keys_to_remove: list) -> str:
    """
    Удаляет указанные ключи из JSON-строки.
    Возвращает отформатированный JSON (или исходную строку, если не удалось).
    """
    try:
        data = json.loads(content)
    except json.JSONDecodeError:
        return content

    if not isinstance(data, dict):
        return content

    for key in keys_to_remove:
        data.pop(key, None)

    return json.dumps(data, ensure_ascii=False, indent=2)


def make_separator(filename: str) -> str:
    """Разделитель между файлами."""
    line = '=' * 60
    return f"\n{line}\n===== FILE: {filename} =====\n{line}\n\n"


def make_header(part: int, total: int, total_size: int) -> str:
    """Заголовок для части (если файлов несколько)."""
    nav = []
    if part > 1:
        nav.append(f"← Предыдущая: part{part - 1}.txt")
    if part < total:
        nav.append(f"Следующая: part{part + 1}.txt →")
    nav_line = ' | '.join(nav) if nav else '(единственная часть)'

    return (
        f"╔══════════════════════════════════════════════════════════╗\n"
        f"║  PROJECT BUNDLE — Часть {part} из {total}                          \n"
        f"║  Всего текста: {total_size / 1024 / 1024:.2f} МБ                              \n"
        f"║  Навигация: {nav_line}\n"
        f"║                                                          \n"
        f"║  ВАЖНО: файл data/settings.json в конце — БЕЗ паролей.   \n"
        f"║  admin_password_hash и admin_login удалены автоматически.\n"
        f"╚══════════════════════════════════════════════════════════╝\n\n"
    )


def bundle_project(src_dir: Path, output_dir: Path, max_size_mb: float = 2.0):
    """Основная функция."""
    print(f"📁 Источник: {src_dir}")
    print(f"📁 Куда:    {output_dir}")
    print(f"📦 Лимит:   {max_size_mb} МБ на часть\n")

    if not src_dir.exists():
        print(f"❌ Папка {src_dir} не найдена")
        sys.exit(1)

    output_dir.mkdir(parents=True, exist_ok=True)

    # --- 1. Читаем основные файлы ---
    chunks = []
    missing = []
    total_size = 0

    for rel_path in FILES:
        full_path = src_dir / rel_path
        if not full_path.exists():
            missing.append(rel_path)
            continue

        content = read_file_safe(full_path)
        if content is None:
            missing.append(rel_path)
            continue

        # Специальная обработка (очистка JSON от паролей)
        if rel_path in SANITIZE_FILES:
            content = sanitize_json(content, SANITIZE_FILES[rel_path])

        chunks.append((rel_path, content))
        total_size += len(content.encode('utf-8'))

    # --- 2. Читаем дополнительные файлы (в конец) ---
    for rel_path in APPEND_FILES:
        full_path = src_dir / rel_path
        if not full_path.exists():
            missing.append(rel_path)
            continue

        content = read_file_safe(full_path)
        if content is None:
            missing.append(rel_path)
            continue

        # Очистка JSON
        if rel_path in SANITIZE_FILES:
            content = sanitize_json(content, SANITIZE_FILES[rel_path])

        chunks.append((rel_path + ' (очищен от паролей)', content))
        total_size += len(content.encode('utf-8'))

    print(f"✅ Прочитано файлов: {len(chunks)}")
    print(f"📊 Общий размер: {total_size / 1024 / 1024:.2f} МБ")

    if missing:
        print(f"\n⚠️  Не найдены ({len(missing)}):")
        for m in missing:
            print(f"   - {m}")

    # --- 3. Считаем части ---
    max_bytes = int(max_size_mb * 1024 * 1024)
    total_parts = max(1, (total_size + max_bytes - 1) // max_bytes)

    print(f"\n✂️  Частей: {total_parts} (по {max_size_mb} МБ)\n")

    # --- 4. Разбиваем ---
    if total_parts == 1:
        out_file = output_dir / 'project_bundle.txt'
        with open(out_file, 'w', encoding='utf-8') as f:
            f.write(make_header(1, 1, total_size))
            for filename, content in chunks:
                f.write(make_separator(filename))
                f.write(content)
                if not content.endswith('\n'):
                    f.write('\n')
        print(f"✅ Готово: {out_file}")
        print(f"   Размер: {out_file.stat().st_size / 1024 / 1024:.2f} МБ")
        print(f"\n📌 Загрузите {out_file.name} в новый чат — и модель увидит весь код.")
        print(f"   Пароли из data/settings.json удалены.")
        return

    # --- 5. Несколько частей ---
    parts = [[]]
    current_size = 0
    current_part = 0

    for filename, content in chunks:
        file_size = len(make_separator(filename).encode('utf-8')) + len(content.encode('utf-8'))

        if current_size + file_size > max_bytes and parts[current_part]:
            current_part += 1
            parts.append([])
            current_size = 0

        parts[current_part].append((filename, content))
        current_size += file_size

    total_parts = len(parts)

    for i, part in enumerate(parts, 1):
        out_file = output_dir / f'project_bundle_part{i}.txt'
        with open(out_file, 'w', encoding='utf-8') as f:
            f.write(make_header(i, total_parts, total_size))
            for filename, content in part:
                f.write(make_separator(filename))
                f.write(content)
                if not content.endswith('\n'):
                    f.write('\n')

        size_mb = out_file.stat().st_size / 1024 / 1024
        print(f"✅ {out_file.name}: {len(part)} файлов, {size_mb:.2f} МБ")

    print(f"\n📌 Загрузите все {total_parts} частей в новый чат по порядку:")
    for i in range(1, total_parts + 1):
        print(f"   {i}. project_bundle_part{i}.txt")


def main():
    parser = argparse.ArgumentParser(description='Собрать проект в TXT для чата')
    parser.add_argument('--src', default='.', help='Корень проекта')
    parser.add_argument('--output', default='./bundle', help='Куда сохранить')
    parser.add_argument('--max-size', type=float, default=2.0, help='Максимум МБ на часть')
    args = parser.parse_args()

    src_dir = Path(args.src).resolve()
    output_dir = Path(args.output).resolve()

    bundle_project(src_dir, output_dir, args.max_size)


if __name__ == '__main__':
    main()