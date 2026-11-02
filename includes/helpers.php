<?php
/**
 * helpers.php — вспомогательные функции проекта AmanitaLove.ru.
 *
 * Подключается автоматически через includes/config.php.
 * Содержит: рендер изображений с WebP, SVG-иконки соцсетей, нормализацию URL.
 */

// ============================================================
// ИЗОБРАЖЕНИЯ С WEBP
// ============================================================

/**
 * Возвращает HTML-блок <picture> с WebP-версией и fallback на оригинал.
 *
 * @param string $imageUrl  Путь к оригиналу, например /uploads/img_123.jpg
 * @param string $alt       ALT-текст
 * @param string $class     CSS-класс для <img> (опционально)
 * @param string $loading   loading-атрибут: 'lazy' или 'eager' (опционально)
 * @return string           HTML
 */
function renderPicture(string $imageUrl, string $alt = '', string $class = '', string $loading = 'lazy'): string
{
    if ($imageUrl === '') {
        return '';
    }

    $altEsc      = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    $classAttr   = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    $loadingAttr = $loading !== '' ? ' loading="' . htmlspecialchars($loading, ENT_QUOTES, 'UTF-8') . '"' : '';

    $webpUrl = preg_replace('/\.(jpe?g|png)$/i', '.webp', $imageUrl);

    if ($webpUrl === $imageUrl) {
        return '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $altEsc . '"' . $classAttr . $loadingAttr . '>';
    }

    $webpFsPath = dirname(__DIR__) . $webpUrl;
    if (!file_exists($webpFsPath)) {
        return '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $altEsc . '"' . $classAttr . $loadingAttr . '>';
    }

    $imgSrcEsc = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
    $webpEsc   = htmlspecialchars($webpUrl, ENT_QUOTES, 'UTF-8');

    return '<picture>'
         . '<source srcset="' . $webpEsc . '" type="image/webp">'
         . '<img src="' . $imgSrcEsc . '" alt="' . $altEsc . '"' . $classAttr . $loadingAttr . '>'
         . '</picture>';
}

// ============================================================
// SVG-ИКОНКА МЕССЕНДЖЕРА MAX
// ============================================================

/**
 * Возвращает SVG-иконку мессенджера MAX.
 *
 * @param string $size  Размер: "1em", "24px" или просто "24".
 *                      Если единиц нет — автоматически добавляется "px".
 * @return string       HTML/SVG-код
 */
function getMaxIcon(string $size = '1em'): string
{
    // Если в $size нет единиц измерения (просто число) — добавляем "px"
    if (preg_match('/^\d+(\.\d+)?$/', $size)) {
        $size .= 'px';
    }
    $sizeEsc = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');

    // Вариант 1: официальный SVG-файл, если он есть
    $iconFsPath = dirname(__DIR__) . '/favicon/max-icon.svg';
    if (file_exists($iconFsPath) && filesize($iconFsPath) > 50) {
        return '<img src="/favicon/max-icon.svg" alt="MAX" style="width:' . $sizeEsc . ';height:' . $sizeEsc . ';vertical-align:middle;display:inline-block;">';
    }

    // Вариант 2: inline-SVG fallback (стилизованное облачко с градиентом)
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="' . $sizeEsc . '" height="' . $sizeEsc . '" style="vertical-align:middle;display:inline-block;" aria-hidden="true">'
         . '<defs>'
         .   '<linearGradient id="maxGrad" x1="0%" y1="0%" x2="100%" y2="100%">'
         .     '<stop offset="0%" style="stop-color:#3b5bdb;stop-opacity:1"/>'
         .     '<stop offset="100%" style="stop-color:#7048e8;stop-opacity:1"/>'
         .   '</linearGradient>'
         . '</defs>'
         . '<path fill="url(#maxGrad)" d="M50 10C25.7 10 6 27.9 6 50c0 12.1 5.5 23 14.3 30.3L16 90l18.9-9.4C39.5 81.9 44.6 83 50 83c24.3 0 44-17.9 44-40S74.3 10 50 10zm0 66c-4.6 0-9-0.9-13.1-2.6l-1.8-0.7-11.2 5.6 2.1-10.9-1.5-1.4C17.5 60.8 13 55.8 13 50c0-18.2 16.6-33 37-33s37 14.8 37 33-16.6 33-37 33z"/>'
         . '<circle cx="35" cy="46" r="4" fill="url(#maxGrad)"/>'
         . '<circle cx="50" cy="46" r="4" fill="url(#maxGrad)"/>'
         . '<circle cx="65" cy="46" r="4" fill="url(#maxGrad)"/>'
         . '</svg>';
}

// ============================================================
// НОРМАЛИЗАЦИЯ URL СОЦСЕТЕЙ
// ============================================================

/**
 * Нормализует URL соцсети:
 *   - Если значение — полный URL (http/https), возвращает как есть.
 *   - Если username/токен — добавляет указанный префикс.
 *
 * @param string $value    Значение из настроек
 * @param string $prefix   Префикс для username (например, "https://t.me/")
 * @param string $strip    Что убрать из начала значения (например, "@")
 * @return string
 */
function socialUrl(string $value, string $prefix, string $strip = ''): string
{
    $value = trim($value);
    if ($value === '') return '';
    if (preg_match('#^https?://#i', $value)) return $value;
    if ($strip !== '') $value = ltrim($value, $strip);
    return $prefix . $value;
}

/**
 * Извлекает только цифры из номера телефона (для tel: и wa.me).
 */
function phoneDigits(string $phone): string
{
    return preg_replace('/\D/', '', $phone);
}