#!/usr/bin/env python3
"""
customize_files.py — уникализация CMS: классы + <style> + минификация
+ переименование файлов разделов + подстрочная замена.

Логика замены (Вариант 2):
    - Файлы <page>.* переименовываются в <page>msk.* (blog.php → blogmsk.php).
    - Подстрока "<page>" → "<page>msk" заменяется во всех текстовых файлах
      (включая admin.php, api.php, admin.js — их нельзя пропускать,
      иначе API/админка сломаются).
    - skip_process защищает от ПРЕФИКСАЦИИ КЛАССОВ, но НЕ от замены подстрок.
    - Пути к CSS/JS (renames) заменяются ВСЕГДА, даже в skip_process-файлах
      (иначе админка ссылается на старые /css/styles.css → 404).
    - Сторонние библиотеки (PHPMailer, summernote, jquery, tailwind) не трогаются.

Использование:
    python customize_files.py
    python customize_files.py --dry-run
    python customize_files.py --config config_files.yaml
    python customize_files.py --prefix ekb
"""

import argparse
import fnmatch
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
CLASS_ATTR_PATTERN = re.compile(r'(class(?:Name)?\s*=\s*["\'])([^"\']+)(["\'])')
CSS_SELECTOR_PATTERN = re.compile(r'\.([a-zA-Z_][a-zA-Z0-9_-]*)')
JS_SELECTOR_PATTERN = re.compile(r'(querySelector(?:All)?\s*\(\s*["\'])([^"\']+)(["\'])')
JS_CLASSLIST_PATTERN = re.compile(r'(classList\.(?:add|remove|toggle|contains)\s*\(\s*["\'])([^"\']+)(["\'])')
HTML_COMMENT_PATTERN = re.compile(r'<!--(?!\[if)[\s\S]*?-->')
CSS_COMMENT_PATTERN = re.compile(r'/\*(?!!)[\s\S]*?\*/')
STYLE_TAG_PATTERN = re.compile(r'(<style[^>]*>)([\s\S]*?)(</style>)', re.IGNORECASE)


# ============================================================
# ЛОГИРОВАНИЕ
# ============================================================
def setup_logging(config: dict) -> logging.Logger:
    lc = config.get('logging', {})
    logger = logging.getLogger('customize_files')
    if not lc.get('enabled', True):
        logger.addHandler(logging.NullHandler())
        return logger
    logger.setLevel(getattr(logging, lc.get('level', 'INFO').upper()))
    logger.handlers = []
    fmt = logging.Formatter('%(asctime)s [%(levelname)s] %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
    lf = lc.get('file', 'logs/customize_files_{date}.log').replace('{date}', datetime.now().strftime('%Y-%m-%d'))
    Path(lf).parent.mkdir(parents=True, exist_ok=True)
    fh = logging.FileHandler(lf, encoding='utf-8')
    fh.setFormatter(fmt)
    logger.addHandler(fh)
    if lc.get('console', True):
        ch = logging.StreamHandler()
        ch.setFormatter(fmt)
        logger.addHandler(ch)
    return logger


# ============================================================
# КОММЕНТАРИИ / МИНИФИКАЦИЯ
# ============================================================
def remove_html_comments(content, logger, file_path):
    def repl(m):
        if m.group(0).strip().startswith('<!--[if'):
            return m.group(0)
        return ''
    return HTML_COMMENT_PATTERN.sub(repl, content)


def remove_css_comments(content, logger, file_path):
    return CSS_COMMENT_PATTERN.sub('', content)


def minify_css_string(css: str) -> str:
    if not css:
        return css
    placeholders = []
    def _stash(m):
        placeholders.append(m.group(0))
        return f"\x00{len(placeholders) - 1}\x00"
    pat = re.compile(
        r'"(?:\\.|[^"\\])*"'
        r"|'(?:\\.|[^'\\])*'"
        r'|url\(\s*(?!"|\')[^)]*\)'
        r'|/\*![\s\S]*?\*/'
    )
    css = pat.sub(_stash, css)
    css = re.sub(r'\s+', ' ', css)
    css = re.sub(r'\s*([{}:;,])\s*', r'\1', css)
    css = re.sub(r'\s*([>~+])\s*', r'\1', css)
    css = re.sub(r';}', '}', css).strip()
    def _restore(m):
        return placeholders[int(m.group(1))]
    return re.sub(r'\x00(\d+)\x00', _restore, css)


def remove_css_comments_in_style_tags(content, logger, file_path, minify=False):
    def repl(m):
        ot, c, ct = m.group(1), m.group(2), m.group(3)
        c = remove_css_comments(c, logger, file_path)
        if minify:
            c = minify_css_string(c)
        return ot + c + ct
    return STYLE_TAG_PATTERN.sub(repl, content)


def clean_whitespace(content, remove_extra_blank=True, strip_trailing=True, logger=None, file_path=''):
    if strip_trailing:
        content = re.sub(r'[ \t]+$', '', content, flags=re.MULTILINE)
    if remove_extra_blank:
        content = re.sub(r'\n{3,}', '\n\n', content)
    return content


# ============================================================
# ПРОВЕРКИ SKIP_PROCESS
# ============================================================
def is_in_skip_process(filename, rel_path, skip_process):
    if filename in skip_process:
        return True
    for part in rel_path.parts:
        if part in skip_process:
            return True
    return False


# ============================================================
# RENAMES (css/js) — пути заменяются ВСЕГДА, включая skip_process
# ============================================================
def apply_renames(dst, renames, skip_process, logger, dry_run=False, minify_css=False):
    stats = {'renamed_files': 0, 'replaced': 0, 'changed_files': []}
    changed_files = []
    if not renames:
        return stats
    logger.info("\n🔧 renames...")
    for item in renames:
        f, t = item.get('from'), item.get('to')
        fr = item.get('file_rename')
        exts = set(item.get('files', []))
        if not f or not t:
            continue
        if fr:
            try:
                old_rel, new_rel = [s.strip() for s in fr.split('->')]
            except ValueError:
                old_rel, new_rel = None, None
            if old_rel and new_rel:
                op, np_ = dst / old_rel, dst / new_rel
                if op.exists():
                    if not dry_run:
                        np_.parent.mkdir(parents=True, exist_ok=True)
                        op.rename(np_)
                    stats['renamed_files'] += 1
                    logger.info(f"  📦 {old_rel} → {new_rel}")
        if not exts:
            exts = {'.php', '.html', '.htm'}
        for root, dirs, files in os.walk(dst):
            dirs[:] = [d for d in dirs if d not in ('.git', 'node_modules', '__pycache__')]
            for fn in files:
                fp = Path(root) / fn
                if fp.suffix.lower() not in exts:
                    continue
                rel = fp.relative_to(dst)
                # ВАЖНО: skip_process здесь НЕ проверяем —
                # пути к CSS/JS должны заменяться везде, включая admin.php.
                try:
                    c = fp.read_text(encoding='utf-8')
                except (UnicodeDecodeError, OSError):
                    continue
                if f in c:
                    cnt = c.count(f)
                    if not dry_run:
                        fp.write_text(c.replace(f, t), encoding='utf-8')
                    stats['replaced'] += cnt
                    changed_files.append(rel)
                    logger.info(f"    ✏️  {rel}: {cnt}")
    for rel in changed_files:
        fp = dst / rel
        ext = fp.suffix.lower()
        try:
            c = fp.read_text(encoding='utf-8')
            if ext in ('.php', '.html', '.htm'):
                c = remove_html_comments(c, logger, str(fp))
                c = remove_css_comments_in_style_tags(c, logger, str(fp), minify=minify_css)
            elif ext == '.css':
                c = remove_css_comments(c, logger, str(fp))
                if minify_css:
                    c = minify_css_string(c)
            if not dry_run:
                fp.write_text(c, encoding='utf-8')
        except Exception as e:
            logger.warning(f"⚠️ {rel}: {e}")
    stats['changed_files'] = changed_files
    logger.info(f"🔧 Итого renames: файлов={stats['renamed_files']}, замен={stats['replaced']}\n")
    return stats


def build_rename_map(renames):
    m = {}
    for it in renames:
        fr = it.get('file_rename')
        if not fr:
            continue
        try:
            old_rel, new_rel = [s.strip() for s in fr.split('->')]
        except ValueError:
            continue
        m[Path(old_rel).name] = Path(new_rel).name
    return m


# ============================================================
# ПЕРЕМЕШИВАНИЕ
# ============================================================
def shuffle_index_blocks(content, fixed_first, shuffle_blocks, logger, file_path):
    allb = [fixed_first] + shuffle_blocks if fixed_first else shuffle_blocks
    ms = re.search(r'<main[^>]*>', content)
    me = re.search(r'</main>', content)
    if not ms or not me:
        return content
    a, inside, b = content[:ms.end()], content[ms.end():me.start()], content[me.start():]
    pat = re.compile(r'<!--\s*(' + '|'.join(re.escape(x) for x in allb) + r')\s*-->')
    mm = list(pat.finditer(inside))
    if len(mm) < 2:
        return content
    bd = []
    for i, m in enumerate(mm):
        s = m.start()
        e = mm[i + 1].start() if i + 1 < len(mm) else len(inside)
        bd.append({'name': m.group(1), 'content': inside[s:e], 'is_first': m.group(1) == fixed_first})
    fixed = [x for x in bd if x['is_first']]
    sh = [x for x in bd if not x['is_first']]
    random.shuffle(sh)
    logger.info(f"  [{file_path}] Порядок: {' → '.join(x['name'] for x in fixed + sh)}")
    return a + ''.join(x['content'] for x in fixed + sh) + b


# ============================================================
# КЛАССЫ
# ============================================================
def is_tailwind_class(cls, patterns):
    for p in patterns:
        try:
            if re.match(p, cls):
                return True
        except re.error:
            continue
    return False


def is_skippable_class(cls, skip_classes, force_process, prefix, patterns, aggressive):
    if not cls or not cls.strip():
        return True
    if cls.startswith(prefix + '-'):
        return True
    if ':' in cls or '/' in cls or '[' in cls or ']' in cls:
        return True
    if cls in force_process:
        return False
    if cls in skip_classes:
        return True
    if is_tailwind_class(cls, patterns):
        return True
    if cls[0].isdigit():
        return True
    if not aggressive and '-' not in cls and '__' not in cls:
        return True
    return False


def unique_class(cls, prefix):
    if '__' in cls:
        b, r = cls.split('__', 1)
        return f"{prefix}-{b}__{r}"
    if '--' in cls:
        b, m = cls.split('--', 1)
        return f"{prefix}-{b}--{m}"
    return f"{prefix}-{cls}"


def process_class_string(s, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str):
    out = []
    for cls in s.split():
        if is_skippable_class(cls, skip_classes, force_process, prefix, patterns, aggressive):
            out.append(cls)
        else:
            nc = unique_class(cls, prefix)
            out.append(nc)
            replaced.append({'from': cls, 'to': nc, 'file': file_str})
    return ' '.join(out)


def process_html_attributes(content, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str):
    def repl(m):
        a, c, b = m.groups()
        return a + process_class_string(c, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str) + b
    return CLASS_ATTR_PATTERN.sub(repl, content)


def process_css_selectors(content, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str):
    def repl(m):
        cls = m.group(1)
        if is_skippable_class(cls, skip_classes, force_process, prefix, patterns, aggressive):
            return m.group(0)
        nc = unique_class(cls, prefix)
        replaced.append({'from': '.' + cls, 'to': '.' + nc, 'file': file_str})
        return '.' + nc
    return CSS_SELECTOR_PATTERN.sub(repl, content)


def process_js_selectors(content, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str):
    def rq(m):
        a, sel, b = m.groups()
        parts = []
        for p in sel.split():
            if p.startswith('.') and not is_skippable_class(p[1:], skip_classes, force_process, prefix, patterns, aggressive):
                nc = unique_class(p[1:], prefix)
                parts.append('.' + nc)
                replaced.append({'from': p, 'to': '.' + nc, 'file': file_str})
            else:
                parts.append(p)
        return a + ' '.join(parts) + b
    def rc(m):
        a, cls, b = m.groups()
        if is_skippable_class(cls, skip_classes, force_process, prefix, patterns, aggressive):
            return m.group(0)
        nc = unique_class(cls, prefix)
        replaced.append({'from': cls, 'to': nc, 'file': file_str})
        return a + nc + b
    content = JS_SELECTOR_PATTERN.sub(rq, content)
    return JS_CLASSLIST_PATTERN.sub(rc, content)


def process_file(src_path, dst_path, prefix, skip_classes, force_process, patterns, aggressive,
                 extensions, enc_read, enc_fallback, enc_write, html_config, css_config,
                 cleanup_config, logger, replaced, dry_run=False):
    ext = src_path.suffix.lower()
    if ext not in extensions:
        if not dry_run:
            dst_path.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(src_path, dst_path)
        return False
    content = None
    for enc in [enc_read, enc_fallback]:
        try:
            content = src_path.read_text(encoding=enc)
            break
        except UnicodeDecodeError:
            continue
    if content is None:
        return False
    original = content
    file_str = str(src_path)

    if src_path.name == 'index.php' and html_config.get('shuffle_index_blocks', False):
        content = shuffle_index_blocks(content, html_config.get('fixed_first_block', 'Hero'),
                                        html_config.get('shuffle_blocks', []), logger, file_str)

    content = process_html_attributes(content, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str)
    content = process_js_selectors(content, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str)

    if ext == '.css':
        content = process_css_selectors(content, prefix, skip_classes, force_process, patterns, aggressive, replaced, file_str)

    if ext in ('.php', '.html', '.htm') and html_config.get('remove_comments', False):
        content = remove_html_comments(content, logger, file_str)
    if ext in ('.php', '.html', '.htm') and html_config.get('remove_css_comments_in_style', False):
        content = remove_css_comments_in_style_tags(content, logger, file_str, minify=html_config.get('minify_css', False))
    if ext == '.css' and css_config.get('remove_comments', False):
        content = remove_css_comments(content, logger, file_str)
    if ext == '.css' and css_config.get('minify_css', False):
        content = minify_css_string(content)
    if cleanup_config.get('enabled', False):
        content = clean_whitespace(content,
                                    remove_extra_blank=cleanup_config.get('remove_extra_blank_lines', True),
                                    strip_trailing=cleanup_config.get('strip_trailing_spaces', True))

    if content != original:
        if not dry_run:
            dst_path.parent.mkdir(parents=True, exist_ok=True)
            dst_path.write_text(content, encoding=enc_write)
        return True
    if not dry_run:
        dst_path.parent.mkdir(parents=True, exist_ok=True)
        if not dst_path.exists():
            shutil.copy2(src_path, dst_path)
    return False


# ============================================================
# ВАРИАНТ 2: переименование файлов + подстрочная замена
# ============================================================
def is_vendor_file(path: Path, dst_root: Path, skip_dirs, skip_files, skip_exact):
    for part in path.parts:
        if part in skip_dirs:
            return True
    name = path.name
    if name in skip_files:
        return True
    if fnmatch.fnmatch(name, 'summernote*') or fnmatch.fnmatch(name, 'jquery*'):
        return True
    try:
        rel = path.relative_to(dst_root).as_posix()
        if rel in skip_exact:
            return True
    except ValueError:
        pass
    return False


def rename_page_files(dst, pairs, skip_dirs, skip_files, skip_exact, logger, dry_run):
    total = 0
    to_rename = []
    for p in dst.rglob('*'):
        if not p.is_file():
            continue
        if is_vendor_file(p, dst, skip_dirs, skip_files, skip_exact):
            continue
        for old, new in pairs:
            if p.stem == old:
                to_rename.append((p, new))
    for p, new in to_rename:
        np_ = p.with_name(new + p.suffix)
        if np_.exists():
            logger.warning(f"⚠️ уже есть: {np_.relative_to(dst)}")
            continue
        logger.info(f"  📦 {p.relative_to(dst)} → {np_.relative_to(dst)}")
        if not dry_run:
            p.rename(np_)
        total += 1
    return total


def is_text_for_replace(path: Path, text_exts, extra_files) -> bool:
    if path.name in extra_files:
        return True
    return path.suffix.lower() in text_exts


def replace_substrings(dst, pairs, text_exts, extra_files, skip_dirs, skip_files, skip_exact,
                        logger, dry_run):
    total_files = 0
    total_repl = 0
    for p in dst.rglob('*'):
        if not p.is_file():
            continue
        if is_vendor_file(p, dst, skip_dirs, skip_files, skip_exact):
            continue
        if not is_text_for_replace(p, text_exts, extra_files):
            continue
        try:
            c = p.read_text(encoding='utf-8')
        except (UnicodeDecodeError, OSError):
            logger.warning(f"⚠️ не UTF-8: {p.relative_to(dst)}")
            continue
        original = c
        fc = 0
        for old, new in pairs:
            n = c.count(old)
            if n:
                c = c.replace(old, new)
                fc += n
                logger.info(f"  ✏️  {p.relative_to(dst)}: '{old}' → '{new}'  ({n})")
        if c != original:
            total_files += 1
            total_repl += fc
            if not dry_run:
                try:
                    p.write_text(c, encoding='utf-8')
                except OSError as e:
                    logger.error(f"❌ {p.relative_to(dst)}: {e}")
    return total_files, total_repl


# ============================================================
# MAIN
# ============================================================
def main():
    parser = argparse.ArgumentParser(description='Уникализация CMS (Вариант 2: файлы + подстрока)')
    parser.add_argument('--config', default='config_files.yaml')
    parser.add_argument('--dry-run', action='store_true')
    parser.add_argument('--prefix')
    parser.add_argument('--dest')
    parser.add_argument('--seed', type=int)
    args = parser.parse_args()

    if args.seed is not None:
        random.seed(args.seed)

    cfg_path = Path(args.config)
    if not cfg_path.exists():
        print(f"❌ Конфиг не найден: {cfg_path}")
        sys.exit(1)
    with open(cfg_path, encoding='utf-8') as f:
        config = yaml.safe_load(f)

    if args.prefix:
        config['unique']['prefix'] = args.prefix
        if not args.dest:
            old = config['destination'].get('path', './muhomor-msk-files')
            base = old.rsplit('-', 1)[0] if '-' in old else './muhomor'
            config['destination']['path'] = f"{base}-{args.prefix}"
    elif args.dest:
        config['destination']['path'] = args.dest

    logger = setup_logging(config)
    logger.info("=" * 60)
    logger.info("Уникализация CMS — Вариант 2 (файлы + подстрока)")
    logger.info("=" * 60)

    src = Path(config['source']['path']).resolve()
    dst = Path(config['destination']['path']).resolve()
    prefix = config['unique']['prefix'].strip().lower().replace('-', '')
    mode = config['unique'].get('mode', 'safe')
    aggressive = (mode == 'aggressive')
    skip_classes = set(config.get('skip_classes', []))
    force_process = set(config.get('force_process', []))
    patterns = list(config.get('tailwind_patterns', []))
    extensions = set(config.get('files', {}).get('extensions', ['.php', '.css', '.js', '.html']))
    exclude = set(config['source'].get('exclude', []))
    skip_process = set(config['source'].get('skip_process', []))
    overwrite = config['destination'].get('overwrite', False)
    backup = config['destination'].get('backup_before_overwrite', True)
    enc = config.get('files', {}).get('encoding', {})
    enc_read = enc.get('read', 'utf-8')
    enc_fallback = enc.get('fallback', 'cp1251')
    enc_write = enc.get('write', 'utf-8')
    html_config = config.get('html', {})
    css_config = config.get('css', {})
    cleanup_config = config.get('cleanup', {})
    renames = list(config.get('renames', []))
    rename_map = build_rename_map(renames)

    fr = config.get('files_rename', {})
    fr_enabled = fr.get('enabled', False)
    fr_pairs = [tuple(x) for x in fr.get('pairs', [])] if fr_enabled else []
    fr_text_exts = set(fr.get('text_exts', []))
    fr_extra_files = set(fr.get('extra_files', []))
    fr_skip_dirs = set(fr.get('skip_dirs', []))
    fr_skip_files = set(fr.get('skip_files', []))
    fr_skip_exact = set(fr.get('skip_exact', []))

    if not src.exists() or not src.is_dir():
        logger.error(f"❌ Исходная папка не найдена: {src}")
        sys.exit(1)
    if not re.match(r'^[a-z][a-z0-9]{1,9}$', prefix):
        logger.error(f"❌ Префикс невалиден: {prefix}")
        sys.exit(1)

    logger.info(f"Источник: {src}")
    logger.info(f"Назначение: {dst}")
    logger.info(f"Префикс: {prefix}, режим: {mode}")
    logger.info(f"files_rename: {'да' if fr_enabled else 'нет'} ({len(fr_pairs)} пар)")
    logger.info(f"Dry-run: {args.dry_run}\n")

    if dst.exists() and not args.dry_run:
        if not overwrite:
            if input(f"⚠️ Папка {dst} существует. Перезаписать? (y/N): ").lower() != 'y':
                logger.info("Отменено.")
                sys.exit(0)
        if backup:
            bp = dst.parent / f"{dst.name}_backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
            logger.info(f"📦 Бэкап: {bp}")
            shutil.move(str(dst), str(bp))
        else:
            shutil.rmtree(dst)

    if not args.dry_run:
        logger.info(f"📁 Копирую {src} → {dst}")
        def ignore_func(d, files):
            return [f for f in files if f in exclude or any(f.endswith(e) for e in exclude if e.startswith('*'))]
        shutil.copytree(src, dst, ignore=ignore_func)

    target = src if args.dry_run else dst
    rename_stats = apply_renames(target, renames, skip_process, logger, args.dry_run,
                                  minify_css=html_config.get('minify_css', False))
    renamed_changed = set(rename_stats.get('changed_files', []))

    replaced = []
    stats = {'total': 0, 'changed': 0, 'skipped': 0, 'errors': 0, 'copied_as_is': 0, 'renamed_skipped': 0}

    logger.info(f"🔍 Сканирую {src}...\n")
    for root, dirs, files in os.walk(src):
        dirs[:] = [d for d in dirs if d not in exclude and not d.startswith('.')]
        rel_path = Path(root).relative_to(src)
        for filename in files:
            file_path = Path(root) / filename
            if filename in exclude:
                continue
            if rel_path / filename in renamed_changed:
                stats['renamed_skipped'] += 1
                logger.info(f"  ⏭️  (renames) {rel_path / filename}")
                continue
            actual = filename
            if rename_map and filename in rename_map and (dst / rel_path / rename_map[filename]).exists():
                actual = rename_map[filename]
            stats['total'] += 1
            dst_file = dst / rel_path / actual
            if is_in_skip_process(filename, rel_path, skip_process):
                stats['copied_as_is'] += 1
                logger.info(f"  📄 (без кастомизации) {rel_path / filename}")
                if not args.dry_run:
                    dst_file.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copy2(file_path, dst_file)
                continue
            try:
                changed = process_file(file_path, dst_file, prefix, skip_classes, force_process,
                                        patterns, aggressive, extensions, enc_read, enc_fallback, enc_write,
                                        html_config, css_config, cleanup_config, logger, replaced, args.dry_run)
                if changed:
                    stats['changed'] += 1
                    logger.info(f"  ✏️  {rel_path / filename}")
                else:
                    stats['skipped'] += 1
            except Exception as e:
                stats['errors'] += 1
                logger.error(f"  ❌ {rel_path / filename}: {e}")

    total_renamed_pages = 0
    total_files_repl = 0
    total_repl = 0
    if fr_pairs and not args.dry_run:
        logger.info("\n🔧 Переименование файлов разделов...")
        total_renamed_pages = rename_page_files(dst, fr_pairs, fr_skip_dirs, fr_skip_files, fr_skip_exact, logger, args.dry_run)
        logger.info(f"Переименовано файлов-страниц: {total_renamed_pages}\n")
        logger.info("🔧 Подстрочная замена во всех текстовых файлах...")
        total_files_repl, total_repl = replace_substrings(
            dst, fr_pairs, fr_text_exts, fr_extra_files,
            fr_skip_dirs, fr_skip_files, fr_skip_exact, logger, args.dry_run
        )

    logger.info("\n" + "=" * 60)
    logger.info("📊 СТАТИСТИКА")
    logger.info("=" * 60)
    logger.info(f"Всего файлов:           {stats['total']}")
    logger.info(f"Изменено (классы):      {stats['changed']}")
    logger.info(f"Без изменений:          {stats['skipped']}")
    logger.info(f"Пропущено (renames):    {stats['renamed_skipped']}")
    logger.info(f"Скопировано как есть:   {stats['copied_as_is']}")
    logger.info(f"Ошибок:                 {stats['errors']}")
    logger.info(f"Замен классов:          {len(replaced)}")
    logger.info(f"Переименовано файлов:   {rename_stats['renamed_files']}")
    logger.info(f"Переименовано страниц:  {total_renamed_pages}")
    logger.info(f"Файлов с подстроками:   {total_files_repl}")
    logger.info(f"Всего подстрочных замен: {total_repl}")

    if config.get('report', {}).get('enabled', True) and not args.dry_run:
        rf = config['report'].get('file', 'reports/report_files_{prefix}_{date}.json')
        rf = rf.replace('{prefix}', prefix).replace('{date}', datetime.now().strftime('%Y-%m-%d'))
        Path(rf).parent.mkdir(parents=True, exist_ok=True)
        report = {
            'date': datetime.now().isoformat(),
            'variant': 'files',
            'source': str(src),
            'destination': str(dst),
            'prefix': prefix,
            'stats': stats,
            'files_rename_stats': {
                'renamed_pages': total_renamed_pages,
                'files_with_substrings': total_files_repl,
                'total_replacements': total_repl,
            },
            'rename_stats': {k: v for k, v in rename_stats.items() if k != 'changed_files'},
        }
        if config['report'].get('include_replaced', True):
            report['replaced'] = replaced
        with open(rf, 'w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        logger.info(f"\n📄 Отчёт: {rf}")

    if args.dry_run:
        logger.info("\n⚠️  Dry-run.")
    else:
        logger.info(f"\n✅ Готово: {dst}")


if __name__ == '__main__':
    main()