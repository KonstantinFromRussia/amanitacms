<?php
/**
 * backup.php — генерация ZIP-архива с данными сайта.
 *
 * Что попадает в архив:
 *   - /data/    — все JSON-файлы (товары, отзывы, заказы, настройки, счётчик и т.д.)
 *   - /uploads/ — все изображения (оригиналы + .webp)
 *
 * Требуется авторизация в админке (сессия logged_in).
 *
 * Использование:
 *   GET /backup.php — скачивает ZIP-архив.
 *
 * Имя файла: <домен>-backup-YYYY-MM-DD-HHMMSS.zip
 */

require_once __DIR__ . '/includes/config.php';

// Только для авторизованного админа
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    die('Unauthorized. Войдите в админку.');
}

// Проверяем, что ZipArchive доступен
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    die('Ошибка: расширение ZIP не установлено на сервере. Обратитесь к хостингу.');
}

$dataDir    = __DIR__ . '/data';
$uploadsDir = __DIR__ . '/uploads';

if (!is_dir($dataDir)) {
    http_response_code(500);
    die('Ошибка: папка /data/ не найдена.');
}

// ============================================================
// Имя файла: <домен>-backup-YYYY-MM-DD-HHMMSS.zip
// ============================================================
$host = $_SERVER['HTTP_HOST'] ?? 'site';
// Убираем потенциально опасные символы для имени файла
// (оставляем буквы, цифры, точку, дефис)
$host = preg_replace('/[^a-z0-9.-]/i', '-', $host);
if ($host === '') {
    $host = 'site';
}
$filename = $host . '-backup-' . date('Y-m-d-His') . '.zip';

// Временный файл (в системной tmp-папке, не на диске сайта)
$tmpFile = tempnam(sys_get_temp_dir(), 'backup_');
if ($tmpFile === false) {
    http_response_code(500);
    die('Ошибка: не удалось создать временный файл.');
}

$zip = new ZipArchive();
if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    @unlink($tmpFile);
    http_response_code(500);
    die('Ошибка: не удалось создать ZIP-архив.');
}

// ============================================================
// 1. Добавляем /data/
// ============================================================
$dataFiles = glob($dataDir . '/*.json');
$dataCount = 0;
foreach ($dataFiles as $file) {
    if (is_file($file)) {
        $zip->addFile($file, 'data/' . basename($file));
        $dataCount++;
    }
}

// ============================================================
// 2. Добавляем /uploads/ (рекурсивно)
// ============================================================
$uploadsCount = 0;
if (is_dir($uploadsDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = substr($file->getPathname(), strlen($uploadsDir) + 1);
            if (basename($file->getPathname()) === '.htaccess') {
                continue;
            }
            $zip->addFile($file->getPathname(), 'uploads/' . str_replace('\\', '/', $relativePath));
            $uploadsCount++;
        }
    }
}

// ============================================================
// 3. Добавляем README
// ============================================================
$readme = "Резервная копия сайта\n"
        . "=====================================\n\n"
        . "Домен: " . $host . "\n"
        . "Дата создания: " . date('d.m.Y H:i:s') . "\n"
        . "Сайт: " . SITE_URL . "\n\n"
        . "Содержимое архива:\n"
        . "  /data/    — {$dataCount} JSON-файлов\n"
        . "  /uploads/ — {$uploadsCount} файлов изображений\n\n"
        . "Как восстановить:\n"
        . "  1. Распакуйте архив в корень сайта.\n"
        . "  2. Убедитесь, что папки /data/ и /uploads/ имеют права на запись (755 или 777).\n"
        . "  3. Проверьте, что файл data/counter.json не сломан (откройте — должен быть валидный JSON).\n\n"
        . "ВАЖНО: этот архив содержит хеш пароля администратора (data/settings.json).\n"
        . "Храните его в надёжном месте и не передавайте третьим лицам.\n";
$zip->addFromString('README.txt', $readme);

// Закрываем архив
if (!$zip->close()) {
    @unlink($tmpFile);
    http_response_code(500);
    die('Ошибка: не удалось завершить ZIP-архив.');
}

// ============================================================
// 4. Отдаём файл на скачивание
// ============================================================
$fileSize = filesize($tmpFile);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

readfile($tmpFile);
@unlink($tmpFile);
exit;