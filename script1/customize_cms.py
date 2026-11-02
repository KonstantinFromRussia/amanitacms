#!/usr/bin/env python3
"""
customize_cms.py — уникализация flat-file CMS.

Что делает:
    1. Копирует папку CMS в новую.
    2. Заменяет CSS-классы на уникальные с префиксом.
    3. Удаляет HTML-комментарии (кроме условных).
    4. Удаляет CSS-комментарии (кроме /*! ... */).
    5. Перемешивает блоки на главной внутри <main>...</main> (кроме фиксированных).
    6. Сохраняет результат + лог + отчёт.

Использование:
    python customize_cms.py
    python customize_cms.py --dry-run
    python customize_cms.py --config config-msk.yaml
    python customize_cms.py --prefix ekb
    python customize_cms.py --seed 12345

Изменения в этой версии:
    - source.exclude        — файлы/папки, которые НЕ копировать вообще;
    - source.skip_process   — файлы/папки, которые КОПИРОВАТЬ КАК ЕСТЬ,
                              но НЕ кастомизировать (admin.php, admin.js,
                              jquery, summernote и т.п.).
    - skip_process теперь проверяется и по имени файла, и по любому
      компоненту относительного пути (папка summernote исключается целиком).
"""

import argparse
import json
import logging
import os
import random
import re
import shutil
import sys
from datetime import datetime
from pathlib import Path

try:
    import yaml
except ImportError:
    print("❌ PyYAML не установлен. Установите: pip install -r requirements.txt")
    sys.exit(1)


# ============================================================
# РЕГУЛЯРНЫЕ ВЫРАЖЕНИЯ
# ============================================================
CLASS_ATTR_PATTERN = re.compile(
    r'(class(?:Name)?\s*=\s*["\'])([^"\']+)(["\'])'
)
CSS_SELECTOR_PATTERN = re.compile(
    r'\.([a-zA-Z_][a-zA-Z0-9_-]*)'
)
JS_SELECTOR_PATTERN = re.compile(
    r'(querySelector(?:All)?\s*\(\s*["\'])([^"\']+)(["\'])'
)
JS_CLASSLIST_PATTERN = re.compile(
    r'(classList\.(?:add|remove|toggle|contains)\s*\(\s*["\'])([^"\']+)(["\'])'
)
HTML_COMMENT_PATTERN = re.compile(
    r'<!--(?!\[if)[\s\S]*?-->'
)
CSS_COMMENT_PATTERN = re.compile(
    r'/\*(?!!)[\s\S]*?\*/'
)


# ============================================================
# ЛОГИРОВАНИЕ
# ============================================================
def setup_logging(config: dict) -> logging.Logger:
    """Настраивает логирование."""
    log_config = config.get('logging', {})
    logger = logging.getLogger('customize')

    if not log_config.get('enabled', True):
        logger.addHandler(logging.NullHandler())
        return logger

    logger.setLevel(getattr(logging, log_config.get('level', 'INFO').upper()))
    logger.handlers = []

    formatter = logging.Formatter(
        '%(asctime)s [%(levelname)s] %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )

    log_file = log_config.get('file', 'logs/customize_{date}.log')
    log_file = log_file.replace('{date}', datetime.now().strftime('%Y-%m-%d'))
    Path(log_file).parent.mkdir(parents=True, exist_ok=True)

    file_handler = logging.FileHandler(log_file, encoding='utf-8')
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    if log_config.get('console', True):
        console_handler = logging.StreamHandler()
        console_handler.setFormatter(formatter)
        logger.addHandler(console_handler)

    return logger


# ============================================================
# УДАЛЕНИЕ КОММЕНТАРИЕВ
# ============================================================
def remove_html_comments(content: str, logger: logging.Logger, file_path: str) -> str:
    """Удаляет HTML-комментарии, кроме условных."""
    def replacer(match):
        comment = match.group(0)
        if comment.strip().startswith('<!--[if'):
            return comment
        return ''

    original_len = len(content)
    content = HTML_COMMENT_PATTERN.sub(replacer, content)
    removed = original_len - len(content)
    if removed > 0:
        logger.debug(f"  [{file_path}] Удалено HTML-комментариев: ~{removed} символов")
    return content


def remove_css_comments(content: str, logger: logging.Logger, file_path: str) -> str:
    """Удаляет CSS-комментарии, кроме /*! ... */."""
    original_len = len(content)
    content = CSS_COMMENT_PATTERN.sub('', content)
    removed = original_len - len(content)
    if removed > 0:
        logger.debug(f"  [{file_path}] Удалено CSS-комментариев: ~{removed} символов")
    return content


# ============================================================
# ПЕРЕМЕШИВАНИЕ БЛОКОВ НА ГЛАВНОЙ (ВНУТРИ <main>)
# ============================================================
def shuffle_index_blocks(content: str, fixed_first: str, shuffle_blocks: list,
                         logger: logging.Logger, file_path: str) -> str:
    """
    Перемешивает блоки в index.php по HTML-комментариям ВНУТРИ <main>...</main>.

    Важно: </main> и всё, что после него (footer, скрипты) — НЕ перемешивается.
    """
    all_blocks = [fixed_first] + shuffle_blocks if fixed_first else shuffle_blocks

    # 1. Находим границы <main>...</main>
    main_start_match = re.search(r'<main[^>]*>', content)
    main_end_match = re.search(r'</main>', content)

    if not main_start_match or not main_end_match:
        logger.warning(f"  [{file_path}] Не найден <main>...</main> — перемешивание пропущено")
        return content

    main_start = main_start_match.end()  # позиция ПОСЛЕ <main>
    main_end = main_end_match.start()    # позиция ПЕРЕД </main>

    # 2. Разделяем контент на три части: до, внутри, после
    before_main = content[:main_start]
    inside_main = content[main_start:main_end]
    after_main = content[main_end:]

    # 3. Ищем блоки ТОЛЬКО внутри <main>
    pattern = re.compile(r'<!--\s*(' + '|'.join(re.escape(b) for b in all_blocks) + r')\s*-->')
    matches = list(pattern.finditer(inside_main))

    if len(matches) < 2:
        logger.warning(f"  [{file_path}] Найдено меньше 2 блоков внутри <main> — перемешивание пропущено")
        return content

    # 4. Извлекаем блоки
    blocks_data = []
    for i, match in enumerate(matches):
        start = match.start()
        end = matches[i + 1].start() if i + 1 < len(matches) else len(inside_main)
        blocks_data.append({
            'name': match.group(1),
            'content': inside_main[start:end],
            'is_first': match.group(1) == fixed_first,
        })

    # 5. Разделяем на фиксированные и перемешиваемые
    fixed = [b for b in blocks_data if b['is_first']]
    shuffle = [b for b in blocks_data if not b['is_first']]

    # 6. Перемешиваем
    random.shuffle(shuffle)

    # 7. Собираем: до <main> + fixed + shuffle + после </main>
    new_inside = ''.join(b['content'] for b in fixed + shuffle)

    new_content = before_main + new_inside + after_main

    # 8. Логируем
    order = [b['name'] for b in fixed + shuffle]
    logger.info(f"  [{file_path}] Новый порядок блоков: {' → '.join(order)}")

    return new_content


# ============================================================
# ПРОВЕРКА SKIP_PROCESS ПО ПУТИ
# ============================================================
def is_in_skip_process(filename: str, rel_path: Path, skip_process: set) -> bool:
    """
    Проверяет, нужно ли пропустить кастомизацию для файла.

    Совпадение считается, если:
      - имя файла есть в skip_process;
      - любой компонент относительного пути (папка) есть в skip_process.

    Пример: skip_process = {'summernote'} →
      - js/summernote/summernote-lite.css  → True (папка summernote)
      - js/summernote/plugin/.../x.js      → True (папка summernote)
      - js/admin.js                        → True (имя admin.js)
    """
    if filename in skip_process:
        return True
    for part in rel_path.parts:
        if part in skip_process:
            return True
    return False


# ============================================================
# ЛОГИКА УНИКАЛИЗАЦИИ КЛАССОВ
# ============================================================
def is_skippable_class(cls: str, skip_classes: set, prefix: str, aggressive: bool = False) -> bool:
    if not cls or not cls.strip():
        return True
    if cls.startswith(prefix + '-'):
        return True
    if ':' in cls or '/' in cls or '[' in cls or ']' in cls:
        return True
    if cls in skip_classes:
        return True
    if cls[0].isdigit():
        return True
    if not aggressive:
        if '-' not in cls and '__' not in cls:
            return True
    return False


def unique_class(cls: str, prefix: str) -> str:
    if '__' in cls:
        block, rest = cls.split('__', 1)
        return f"{prefix}-{block}__{rest}"
    if '--' in cls:
        block, mod = cls.split('--', 1)
        return f"{prefix}-{block}--{mod}"
    return f"{prefix}-{cls}"


def process_class_string(class_str: str, prefix: str, skip_classes: set, aggressive: bool,
                          logger: logging.Logger, file_path: str, replaced: list) -> str:
    classes = class_str.split()
    new_classes = []
    for cls in classes:
        if is_skippable_class(cls, skip_classes, prefix, aggressive):
            new_classes.append(cls)
        else:
            new_cls = unique_class(cls, prefix)
            new_classes.append(new_cls)
            replaced.append({'from': cls, 'to': new_cls, 'file': file_path})
    return ' '.join(new_classes)


def process_html_attributes(content: str, prefix: str, skip_classes: set, aggressive: bool,
                             logger: logging.Logger, file_path: str, replaced: list) -> str:
    def replacer(match):
        prefix_str, classes_str, suffix_str = match.groups()
        new_classes = process_class_string(
            classes_str, prefix, skip_classes, aggressive, logger, file_path, replaced
        )
        return prefix_str + new_classes + suffix_str
    return CLASS_ATTR_PATTERN.sub(replacer, content)


def process_css_selectors(content: str, prefix: str, skip_classes: set, aggressive: bool,
                           logger: logging.Logger, file_path: str, replaced: list) -> str:
    def replacer(match):
        cls = match.group(1)
        if is_skippable_class(cls, skip_classes, prefix, aggressive):
            return match.group(0)
        new_cls = unique_class(cls, prefix)
        replaced.append({'from': '.' + cls, 'to': '.' + new_cls, 'file': file_path})
        return '.' + new_cls
    return CSS_SELECTOR_PATTERN.sub(replacer, content)


def process_js_selectors(content: str, prefix: str, skip_classes: set, aggressive: bool,
                          logger: logging.Logger, file_path: str, replaced: list) -> str:
    def replacer_query(match):
        prefix_str, selector, suffix_str = match.groups()
        parts = selector.split()
        new_parts = []
        for part in parts:
            if part.startswith('.'):
                cls = part[1:]
                if is_skippable_class(cls, skip_classes, prefix, aggressive):
                    new_parts.append(part)
                else:
                    new_cls = unique_class(cls, prefix)
                    new_parts.append('.' + new_cls)
                    replaced.append({'from': part, 'to': '.' + new_cls, 'file': file_path})
            else:
                new_parts.append(part)
        return prefix_str + ' '.join(new_parts) + suffix_str

    def replacer_classlist(match):
        prefix_str, cls, suffix_str = match.groups()
        if is_skippable_class(cls, skip_classes, prefix, aggressive):
            return match.group(0)
        new_cls = unique_class(cls, prefix)
        replaced.append({'from': cls, 'to': new_cls, 'file': file_path})
        return prefix_str + new_cls + suffix_str

    content = JS_SELECTOR_PATTERN.sub(replacer_query, content)
    content = JS_CLASSLIST_PATTERN.sub(replacer_classlist, content)
    return content


def process_file(src_path: Path, dst_path: Path, prefix: str, skip_classes: set,
                  aggressive: bool, extensions: set, encoding_read: str,
                  encoding_fallback: str, encoding_write: str,
                  html_config: dict, css_config: dict,
                  logger: logging.Logger, replaced: list,
                  dry_run: bool = False) -> bool:
    """
    Обрабатывает один файл.
    Возвращает True, если файл изменён (в dry-run — «был бы изменён»).
    """
    ext = src_path.suffix.lower()

    # Файлы, которые не обрабатываем — копируем как есть (кроме dry-run)
    if ext not in extensions:
        if not dry_run:
            dst_path.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(src_path, dst_path)
        return False

    # Чтение
    content = None
    for enc in [encoding_read, encoding_fallback]:
        try:
            content = src_path.read_text(encoding=enc)
            break
        except UnicodeDecodeError:
            continue

    if content is None:
        logger.warning(f"⚠️ Не удалось прочитать {src_path}")
        return False

    original = content
    file_str = str(src_path)
    filename = src_path.name

    # 1. Перемешивание блоков в index.php
    if filename == 'index.php' and html_config.get('shuffle_index_blocks', False):
        fixed_first = html_config.get('fixed_first_block', 'Hero')
        shuffle_blocks = html_config.get('shuffle_blocks', [])
        content = shuffle_index_blocks(content, fixed_first, shuffle_blocks, logger, file_str)

    # 2. Уникализация классов
    content = process_html_attributes(content, prefix, skip_classes, aggressive, logger, file_str, replaced)
    content = process_js_selectors(content, prefix, skip_classes, aggressive, logger, file_str, replaced)

    if ext == '.css':
        content = process_css_selectors(content, prefix, skip_classes, aggressive, logger, file_str, replaced)

    # 3. Удаление комментариев
    if ext in ('.php', '.html', '.htm') and html_config.get('remove_comments', False):
        content = remove_html_comments(content, logger, file_str)

    if ext == '.css' and css_config.get('remove_comments', False):
        content = remove_css_comments(content, logger, file_str)

    # 4. Запись
    if content != original:
        if not dry_run:
            dst_path.parent.mkdir(parents=True, exist_ok=True)
            dst_path.write_text(content, encoding=encoding_write)
        return True

    # 5. Не изменился — копируем как есть (кроме dry-run)
    if not dry_run:
        dst_path.parent.mkdir(parents=True, exist_ok=True)
        if not dst_path.exists():
            shutil.copy2(src_path, dst_path)

    return False


# ============================================================
# MAIN
# ============================================================
def main():
    parser = argparse.ArgumentParser(description='Уникализация flat-file CMS')
    parser.add_argument('--config', default='config.yaml', help='Путь к config.yaml')
    parser.add_argument('--dry-run', action='store_true', help='Не сохранять, только показать')
    parser.add_argument('--prefix', help='Переопределить префикс')
    parser.add_argument('--seed', type=int, help='Seed для random (воспроизводимость)')
    args = parser.parse_args()

    if args.seed is not None:
        random.seed(args.seed)

    # Загрузка конфига
    config_path = Path(args.config)
    if not config_path.exists():
        print(f"❌ Конфиг не найден: {config_path}")
        sys.exit(1)

    with open(config_path, 'r', encoding='utf-8') as f:
        config = yaml.safe_load(f)

    if args.prefix:
        config['unique']['prefix'] = args.prefix

    logger = setup_logging(config)
    logger.info("=" * 60)
    logger.info("Уникализация CMS")
    logger.info("=" * 60)

    # Параметры
    src = Path(config['source']['path']).resolve()
    dst = Path(config['destination']['path']).resolve()
    prefix = config['unique']['prefix'].strip().lower().replace('-', '')
    mode = config['unique'].get('mode', 'safe')
    aggressive = (mode == 'aggressive')
    skip_classes = set(config.get('skip_classes', []))
    extensions = set(config.get('files', {}).get('extensions', ['.php', '.css', '.js', '.html']))

    # ВАЖНО: два разных списка
    exclude = set(config['source'].get('exclude', []))            # не копировать
    skip_process = set(config['source'].get('skip_process', []))  # копировать как есть

    overwrite = config['destination'].get('overwrite', False)
    backup = config['destination'].get('backup_before_overwrite', True)

    enc_read = config.get('files', {}).get('encoding', {}).get('read', 'utf-8')
    enc_fallback = config.get('files', {}).get('encoding', {}).get('fallback', 'cp1251')
    enc_write = config.get('files', {}).get('encoding', {}).get('write', 'utf-8')

    html_config = config.get('html', {})
    css_config = config.get('css', {})

    # Валидация
    if not src.exists() or not src.is_dir():
        logger.error(f"❌ Исходная папка не найдена: {src}")
        sys.exit(1)

    if not re.match(r'^[a-z][a-z0-9]{1,9}$', prefix):
        logger.error(f"❌ Префикс '{prefix}' невалиден.")
        sys.exit(1)

    logger.info(f"Источник:  {src}")
    logger.info(f"Назначение: {dst}")
    logger.info(f"Префикс:   {prefix}")
    logger.info(f"Режим:     {mode}")
    logger.info(f"HTML comments: {'удалять' if html_config.get('remove_comments') else 'оставить'}")
    logger.info(f"CSS comments:  {'удалять' if css_config.get('remove_comments') else 'оставить'}")
    logger.info(f"Shuffle index: {'да' if html_config.get('shuffle_index_blocks') else 'нет'}")
    logger.info(f"Не копировать:  {sorted(exclude)}")
    logger.info(f"Копировать без кастомизации: {sorted(skip_process)}")
    logger.info(f"Dry-run:   {args.dry_run}")
    logger.info("")

    # Обработка назначения
    if dst.exists() and not args.dry_run:
        if not overwrite:
            answer = input(f"⚠️ Папка {dst} существует. Перезаписать? (y/N): ")
            if answer.lower() != 'y':
                logger.info("Отменено.")
                sys.exit(0)
        if backup:
            backup_path = dst.parent / f"{dst.name}_backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
            logger.info(f"📦 Резервная копия: {backup_path}")
            shutil.move(str(dst), str(backup_path))
        else:
            shutil.rmtree(dst)

    # Копирование (только если не dry-run)
    if not args.dry_run:
        logger.info(f"📁 Копирую {src} → {dst}")

        def ignore_func(dir, files):
            # Исключаем ТОЛЬКО exclude (не skip_process!)
            return [f for f in files if f in exclude or any(f.endswith(e) for e in exclude if e.startswith('*'))]

        shutil.copytree(src, dst, ignore=ignore_func)

    # Статистика
    replaced = []
    stats = {'total': 0, 'changed': 0, 'skipped': 0, 'errors': 0, 'copied_as_is': 0}

    logger.info(f"\n🔍 Сканирую {src}...\n")

    for root, dirs, files in os.walk(src):
        # Исключаем из обхода только exclude (папки)
        dirs[:] = [d for d in dirs if d not in exclude and not d.startswith('.')]
        rel_path = Path(root).relative_to(src)

        for filename in files:
            file_path = Path(root) / filename
            if filename in exclude:
                continue

            stats['total'] += 1
            dst_file = dst / rel_path / filename

            # ВАЖНО: skip_process — копируем как есть, без кастомизации
            # (проверка и по имени файла, и по компонентам относительного пути)
            if is_in_skip_process(filename, rel_path, skip_process):
                stats['copied_as_is'] += 1
                logger.info(f"  📄 (без кастомизации) {rel_path / filename}")
                if not args.dry_run:
                    dst_file.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copy2(file_path, dst_file)
                continue

            try:
                changed = process_file(
                    file_path, dst_file, prefix, skip_classes, aggressive,
                    extensions, enc_read, enc_fallback, enc_write,
                    html_config, css_config, logger, replaced,
                    dry_run=args.dry_run
                )
                if changed:
                    stats['changed'] += 1
                    logger.info(f"  ✏️  {rel_path / filename}")
                else:
                    stats['skipped'] += 1
            except Exception as e:
                stats['errors'] += 1
                logger.error(f"  ❌ {rel_path / filename}: {e}")

    # Статистика
    logger.info("")
    logger.info("=" * 60)
    logger.info("📊 СТАТИСТИКА")
    logger.info("=" * 60)
    logger.info(f"Всего файлов:      {stats['total']}")
    logger.info(f"Изменено:          {stats['changed']}")
    logger.info(f"Без изменений:     {stats['skipped']}")
    logger.info(f"Скопировано как есть (skip_process): {stats['copied_as_is']}")
    logger.info(f"Ошибок:            {stats['errors']}")
    logger.info(f"Всего замен:       {len(replaced)}")

    # Отчёт (только если не dry-run)
    if config.get('report', {}).get('enabled', True) and not args.dry_run:
        report_file = config['report'].get('file', 'reports/report_{prefix}_{date}.json')
        report_file = report_file.replace('{prefix}', prefix)
        report_file = report_file.replace('{date}', datetime.now().strftime('%Y-%m-%d'))
        Path(report_file).parent.mkdir(parents=True, exist_ok=True)

        report = {
            'date': datetime.now().isoformat(),
            'source': str(src),
            'destination': str(dst),
            'prefix': prefix,
            'mode': mode,
            'stats': stats,
            'options': {
                'remove_html_comments': html_config.get('remove_comments', False),
                'remove_css_comments': css_config.get('remove_comments', False),
                'shuffle_index_blocks': html_config.get('shuffle_index_blocks', False),
                'exclude': sorted(exclude),
                'skip_process': sorted(skip_process),
            }
        }
        if config['report'].get('include_replaced', True):
            report['replaced'] = replaced

        with open(report_file, 'w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)

        logger.info(f"\n📄 Отчёт: {report_file}")

    if args.dry_run:
        logger.info("\n⚠️  Dry-run. Ничего не сохранено.")
    else:
        logger.info(f"\n✅ Готово! Результат в: {dst}")


if __name__ == '__main__':
    main()