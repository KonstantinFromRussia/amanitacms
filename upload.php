<?php
/**
 * upload.php — загрузка изображений с авто-конвертацией в WebP.
 * 
 * 11.11.2026: Сначала ресайз (imagecopyresampled), потом WebP из уменьшенной.
 */

require_once __DIR__ . '/includes/config.php';
session_start();

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    http_response_code(400);
    echo 'No file uploaded';
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo 'Upload error code: ' . $file['error'];
    exit;
}

$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo 'File too large (max 10 MB)';
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo 'Unsupported image type: ' . $mime;
    exit;
}

$ext = $allowed[$mime];

$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo 'File is not a valid image';
    exit;
}

$realMime = $imageInfo['mime'] ?? '';
if (!isset($allowed[$realMime])) {
    http_response_code(400);
    echo 'Image MIME mismatch: ' . $realMime;
    exit;
}

function transliterateFilename($text) {
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        'А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Д'=>'d','Е'=>'e','Ё'=>'yo','Ж'=>'zh','З'=>'z','И'=>'i','Й'=>'y','К'=>'k','Л'=>'l','М'=>'m','Н'=>'n','О'=>'o','П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u','Ф'=>'f','Х'=>'h','Ц'=>'ts','Ч'=>'ch','Ш'=>'sh','Щ'=>'sch','Ъ'=>'','Ы'=>'y','Ь'=>'','Э'=>'e','Ю'=>'yu','Я'=>'ya'
    ];
    $translit = '';
    for ($i = 0; $i < mb_strlen($text); $i++) {
        $char = mb_substr($text, $i, 1);
        $translit .= $map[$char] ?? $char;
    }
    return $translit;
}

$uploadsDir = __DIR__ . '/uploads/';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}

$originalName = $file['name'];
$baseName = pathinfo($originalName, PATHINFO_FILENAME);
$baseName = transliterateFilename($baseName);
$baseName = strtolower($baseName);
$baseName = preg_replace('/[^a-z0-9-]+/', '-', $baseName);
$baseName = trim($baseName, '-');
$baseName = preg_replace('/-+/', '-', $baseName);

if ($baseName === '') {
    $baseName = 'img_' . time() . '_' . bin2hex(random_bytes(4));
}

$finalName = $baseName;
$counter = 1;
while (file_exists($uploadsDir . $finalName . '.' . $ext) || file_exists($uploadsDir . $finalName . '.webp')) {
    $finalName = $baseName . '-' . $counter;
    $counter++;
}

$origPath = $uploadsDir . $finalName . '.' . $ext;
$origUrl  = '/uploads/' . $finalName . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $origPath)) {
    http_response_code(500);
    echo 'Failed to save file';
    exit;
}

// ---------- РЕСАЙЗ И КОНВЕРТАЦИЯ В WEBP ----------
$webpPath = $uploadsDir . $finalName . '.webp';
$webpCreated = false;

if (function_exists('imagewebp') && ($ext === 'jpg' || $ext === 'png')) {
    try {
        $image = null;
        if ($ext === 'jpg') {
            $image = @imagecreatefromjpeg($origPath);
        } elseif ($ext === 'png') {
            $image = @imagecreatefrompng($origPath);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        }

        if ($image) {
            $width  = imagesx($image);
            $height = imagesy($image);

            // 1. Сначала ресайз (макс. 1200px)
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

            // 2. Теперь создаем WebP из (возможно уменьшенной) картинки
            if (@imagewebp($image, $webpPath, 82)) {
                $webpCreated = true;
            }

            // 3. Пересохраняем уменьшенный оригинал (JPG)
            if ($ext === 'jpg') {
                @imagejpeg($image, $origPath, 75);
            } elseif ($ext === 'png') {
                @imagepng($image, $origPath, 8);
            }
            
            imagedestroy($image);
        }
    } catch (Throwable $e) {
        error_log('WebP/Resize failed: ' . $e->getMessage());
    }
}

// Если WebP больше оригинала (уменьшенного) — удаляем WebP
if ($webpCreated && file_exists($webpPath) && filesize($webpPath) >= filesize($origPath) * 1.05) {
    @unlink($webpPath);
    $webpCreated = false;
}

// Возвращаем URL WebP (если создан), иначе — URL оригинала
if ($webpCreated) {
    echo '/uploads/' . $finalName . '.webp';
} else {
    echo $origUrl;
}