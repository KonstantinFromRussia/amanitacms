<?php
/**
 * security-check.php — аудит безопасности CMS.
 *
 * ЗАПУСК: только для авторизованного админа (сессия logged_in).
 * ВЫВОД: plain text.
 * ДЕЙСТВИЕ: только чтение. Ничего не удаляет и не меняет.
 *
 * ПОСЛЕ ПРОВЕРКИ — УДАЛИТЬ С СЕРВЕРА.
 */

require_once __DIR__ . '/includes/config.php';

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    die("Unauthorized. Войдите в админку.\n");
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

$root = __DIR__;
$issues = [];
$info = [];

function out(string $s): void { echo $s . "\n"; }
function hr(): void { out(str_repeat('=', 70)); }
function h2(string $s): void { out("\n" . str_repeat('-', 70) . "\n" . $s . "\n" . str_repeat('-', 70)); }

hr();
out("SECURITY CHECK — " . date('Y-m-d H:i:s'));
out("Root: " . $root);
hr();

// ============================================================
// 1. ФАЙЛОВАЯ СИСТЕМА — подозрительные файлы
// ============================================================
h2("1. ФАЙЛОВАЯ СИСТЕМА — подозрительные файлы");

$staticDirs = ['css', 'js', 'fonts', 'webfonts', 'favicon', 'doc', 'bundle'];
$dangerExts = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pl', 'py', 'cgi', 'sh', 'asp', 'aspx', 'jsp'];
$suspiciousNames = ['shell', 'webshell', 'backdoor', 'cmd', 'eval', 'upload', 'x', 'a', 'test', 'debug'];

$found = 0;
foreach ($staticDirs as $dir) {
    $path = $root . '/' . $dir;
    if (!is_dir($path)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $ext = strtolower($f->getExtension());
        $name = strtolower(pathinfo($f->getFilename(), PATHINFO_FILENAME));
        $bad = in_array($ext, $dangerExts, true);
        if ($bad) {
            $issues[] = "СКРИПТ в статической папке: " . str_replace($root, '', $f->getPathname()) . " (" . $f->getSize() . " байт, " . date('Y-m-d H:i', $f->getMTime()) . ")";
            $found++;
        }
        if (in_array($name, $suspiciousNames, true) && $ext !== '') {
            $issues[] = "ПОДОЗРИТЕЛЬНОЕ ИМЯ: " . str_replace($root, '', $f->getPathname());
            $found++;
        }
    }
}
if ($found === 0) out("OK: в статических папках скриптов не найдено.");

// Корень — файлы .php с «мусорными» именами
$rootPhp = glob($root . '/*.php');
$knownRoot = ['index.php','catalog.php','blog.php','news.php','faq.php','promotions.php','reviews.php','search.php','contacts.php','page.php','about.php','404.php','admin.php','auth.php','logout.php','api.php','upload.php','backup.php','sitemap.php','robots.php','rename-all-images.php','batch-webp.php','security-check.php'];
$found = 0;
foreach ($rootPhp as $f) {
    $base = basename($f);
    if (!in_array($base, $knownRoot, true)) {
        $issues[] = "НЕИЗВЕСТНЫЙ PHP В КОРНЕ: $base (" . filesize($f) . " байт, " . date('Y-m-d H:i', filemtime($f)) . ")";
        $found++;
    }
}
if ($found === 0) out("OK: в корне нет неизвестных PHP-файлов.");

// /uploads/ — любые скрипты
$found = 0;
$up = $root . '/uploads';
if (is_dir($up)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($up, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $ext = strtolower($f->getExtension());
        if (in_array($ext, $dangerExts, true)) {
            $issues[] = "СКРИПТ в /uploads/: " . str_replace($root, '', $f->getPathname());
            $found++;
        }
    }
}
if ($found === 0) out("OK: в /uploads/ скриптов не найдено.");

// ============================================================
// 2. ПРАВА ДОСТУПА
// ============================================================
h2("2. ПРАВА ДОСТУПА");

$checkPerm = function(string $path) use (&$issues) {
    if (!file_exists($path)) return;
    $p = substr(sprintf('%o', fileperms($path)), -4);
    $name = str_replace(__DIR__, '', $path);
    if ($p === '0777') $issues[] = "ПРАВА 777: $name (слишком открыто)";
    out("  $name → $p");
};
foreach (['/data', '/data/settings.json', '/uploads', '/includes', '/includes/mail-config.php', '/.htaccess'] as $p) {
    $checkPerm(__DIR__ . $p);
}

// ============================================================
// 3. PHP-НАСТРОЙКИ
// ============================================================
h2("3. PHP-НАСТРОЙКИ");

$phpSettings = [
    'display_errors'        => 'Off',
    'display_startup_errors'=> 'Off',
    'log_errors'            => 'On',
    'allow_url_include'     => 'Off',
    'allow_url_fopen'       => 'Off (желательно)',
    'expose_php'            => 'Off',
    'open_basedir'          => 'не пусто (желательно)',
    'disable_functions'     => 'содержит shell_exec, exec, passthru, system, proc_open, popen',
];
foreach ($phpSettings as $k => $want) {
    $v = ini_get($k);
    out("  $k = " . ($v === '' ? '(пусто)' : $v));
}
if (ini_get('display_errors')) $issues[] = "PHP: display_errors включён (должен быть Off)";
if (ini_get('allow_url_include')) $issues[] = "PHP: allow_url_include включён (должен быть Off)";
if (ini_get('expose_php')) $issues[] = "PHP: expose_php включён (желательно Off)";
$df = ini_get('disable_functions');
if ($df === '') $issues[] = "PHP: disable_functions пуст — shell_exec/exec/passthru не отключены";

// ============================================================
// 4. .HTACCESS
// ============================================================
h2("4. .HTACCESS");

$ht = @file_get_contents(__DIR__ . '/.htaccess');
if ($ht === false) {
    $issues[] = ".htaccess не читается";
} else {
    $checks = [
        'Запрет скриптов в статических папках' => '/RewriteRule \^\(css\|js\|fonts\|webfonts\|favicon\|doc\|bundle\)/',
        'Защита /includes/'                    => '/RewriteRule \^includes\//',
        'Запрет /data/'                        => '/RedirectMatch 403 \^\/data\//',
        'Запрет скриптов в /uploads/'          => '/RewriteRule \^uploads\//',
        'Options -Indexes'                     => '/Options -Indexes/',
        'Options -MultiViews'                  => '/Options -MultiViews/',
        'Блокировка сканеров'                  => '/phpinfo\|info\\\.php\|test\\\.php/',
    ];
    foreach ($checks as $label => $re) {
        if (preg_match($re, $ht)) {
            out("  OK: $label");
        } else {
            $issues[] = ".htaccess: отсутствует правило «$label»";
        }
    }
    // Если /includes/.htaccess нет — предупредить
    if (!file_exists(__DIR__ . '/includes/.htaccess')) {
        $issues[] = "Нет /includes/.htaccess (доп. защита)";
    }
    if (!file_exists(__DIR__ . '/uploads/.htaccess')) {
        $issues[] = "Нет /uploads/.htaccess (доп. защита)";
    }
}

// ============================================================
// 5. ПОДОЗРИТЕЛЬНЫЕ ПАТТЕРНЫ В PHP-ФАЙЛАХ
// ============================================================
h2("5. ПОДОЗРИТЕЛЬНЫЕ ПАТТЕРНЫ В PHP-ФАЙЛАХ");

$patterns = [
    '/\beval\s*\(/'                => 'eval()',
    '/\bassert\s*\(/'              => 'assert()',
    '/\bbase64_decode\s*\(/'       => 'base64_decode()',
    '/\bgzinflate\s*\(/'           => 'gzinflate()',
    '/\bstr_rot13\s*\(/'           => 'str_rot13()',
    '/\bshell_exec\s*\(/'          => 'shell_exec()',
    '/\bpassthru\s*\(/'            => 'passthru()',
    '/\bsystem\s*\(/'              => 'system()',
    '/\bproc_open\s*\(/'           => 'proc_open()',
    '/\bpopen\s*\(/'               => 'popen()',
    '/\bpcntl_exec\s*\(/'          => 'pcntl_exec()',
    '/\$\{.*\}/'                   => '${...} (обфускация)',
    '/\bgoto\s+[a-zA-Z0-9_]+;/'    => 'goto (обфускация, как в styles.php)',
];

$phpFiles = [];
$dirsToScan = [$root, $root . '/includes'];
foreach ($dirsToScan as $d) {
    if (!is_dir($d)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        if (strtolower($f->getExtension()) !== 'php') continue;
        if (strpos($f->getPathname(), '/PHPMailer/') !== false) continue; // пропускаем библиотеку
        if (basename($f->getPathname()) === 'security-check.php') continue; // пропускаем сам скрипт
        $phpFiles[] = $f->getPathname();
    }
}

$found = 0;
foreach ($phpFiles as $file) {
    $src = @file_get_contents($file);
    if ($src === false) continue;
    foreach ($patterns as $re => $label) {
        if (preg_match($re, $src, $m, PREG_OFFSET_CAPTURE)) {
            $line = substr_count(substr($src, 0, $m[0][1]), "\n") + 1;
            $rel = str_replace($root, '', $file);
            $issues[] = "ПАТТЕРН [$label] в $rel : строка $line";
            $found++;
        }
    }
}
if ($found === 0) out("OK: подозрительных паттернов не найдено (" . count($phpFiles) . " файлов проверено).");

// ============================================================
// 6. COUNTER.LOG — подозрительные записи
// ============================================================
h2("6. COUNTER.LOG — последние 30 записей");

$logFile = $root . '/data/counter.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last = array_slice($lines, -30);
    foreach ($last as $l) out("  " . rtrim($l));
    out("  Всего строк: " . count($lines));
} else {
    out("  (counter.log отсутствует)");
}

// ============================================================
// 7. ИТОГ
// ============================================================
h2("7. ИТОГ");

if (empty($issues)) {
    out("ПРОБЛЕМ НЕ НАЙДЕНО.");
} else {
    out("НАЙДЕНО ПРОБЛЕМ: " . count($issues));
    out("");
    foreach ($issues as $i => $msg) {
        out("  " . ($i + 1) . ". " . $msg);
    }
}

out("");
hr();
out("ВАЖНО: скрипт только читал. После проверки — УДАЛИТЕ security-check.php с сервера.");
hr();