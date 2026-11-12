<?php
/**
 * scripts/optimize-uploads.php — конвертация СТАРЫХ файлов в /uploads/.
 *
 * 11.11.2026: создан для перевода существующих JPG/PNG в WebP
 * и уменьшения оригиналов до 1200px.
 * 11.11.2026 (обновление): принудительно пересоздает WebP и ужимает JPG,
 * даже если WebP уже существует. Игнорирует старые WebP.
 */

// Для CLI — не требуем сессию.
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    require_once __DIR__ . '/../includes/config.php';
    session_start();
    if (empty($_SESSION['logged_in'])) {
        http_response_code(401);
        die('Unauthorized. Войдите в админку.');
    }
    header('Content-Type: text/html; charset=utf-8');
    echo "<pre style='background:#111;color:#0f0;padding:20px;font-family:monospace;'>";
}

// Увеличиваем лимит памяти
ini_set('memory_limit', '256M');

$uploadsDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadsDir)) {
    die("Ошибка: папка /uploads/ не найдена.\n");
}

if (!function_exists('imagewebp')) {
    die("Ошибка: GD без поддержки WebP. Конвертация невозможна.\n");
}

$logFile = __DIR__ . '/../data/optimize.log';
$log = function($msg) use ($logFile, $isCli) {
    $line = date('Y-m-d H:i:s') . ' | ' . $msg . "\n";
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo $line;
};

$log("=== Старт ПРИНУДИТЕЛЬНОЙ оптимизации uploads ===");

$files = glob($uploadsDir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
$total = count($files);
$processed = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $base = pathinfo($file, PATHINFO_FILENAME);
    $webpPath = $uploadsDir . $base . '.webp';

    try {
        $image = null;
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $image = @imagecreatefromjpeg($file);
        } elseif ($ext === 'png') {
            $image = @imagecreatefrompng($file);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        }

        if (!$image) {
            $log("ОШИБКА: не удалось открыть $file");
            $errors++;
            continue;
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        $oldSize = filesize($file);

        // 1. Ресайз оригинала до 1200px (если больше)
        $maxSide = 1200;
        if ($width > $maxSide || $height > $maxSide) {
            if ($width >= $height) {
                $newWidth  = $maxSide;
                $newHeight = (int)round($height * ($maxSide / $width));
            } else {
                $newHeight = $maxSide;
                $newWidth  = (int)round($width * ($maxSide / $height));
            }
            
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if ($newImage) {
                if ($ext === 'png') {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                }
                if (@imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
                    imagedestroy($image);
                    $image = $newImage;
                    $width = $newWidth;
                    $height = $newHeight;
                } else {
                    imagedestroy($newImage);
                }
            }
        }

        // 2. Пересохраняем уменьшенный оригинал (JPG) с качеством 75
        if ($ext === 'jpg' || $ext === 'jpeg') {
            @imagejpeg($image, $file, 75);
        } elseif ($ext === 'png') {
            @imagepng($image, $file, 8);
        }

        // 3. Принудительно пересоздаем WebP (перезаписываем старый)
        $webpCreated = false;
        if (@imagewebp($image, $webpPath, 82)) {
            $webpCreated = true;
        }

        imagedestroy($image);

        $newSize = filesize($file);
        $webpSize = $webpCreated ? filesize($webpPath) : 0;
        
        $log("OK: " . basename($file) . " → WebP (было {$oldSize} байт, JPG стал {$newSize} байт, WebP {$webpSize} байт)");
        $processed++;

    } catch (Throwable $e) {
        $log("ИСКЛЮЧЕНИЕ: " . basename($file) . " — " . $e->getMessage());
        $errors++;
    }
}

$log("=== Готово ===");
$log("Всего файлов: $total");
$log("Обработано (JPG сжат, WebP пересоздан): $processed");
$log("Пропущено: $skipped");
$log("Ошибок: $errors");

if (!$isCli) {
    echo "</pre>";
}