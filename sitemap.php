<?php
/**
 * sitemap.php — генерация XML-карты сайта для поисковых систем.
 *
 * Формат: XML sitemap 0.9
 * Документация: https://www.sitemaps.org/protocol.html
 *
 * Что включено:
 *   - Главная
 *   - Разделы: /catalog, /blog, /news, /promotions, /reviews, /faq, /contacts, /search, /about.php
 *   - Все товары (/product/<slug>.html)
 *   - Все статьи блога (/blog/<slug>.html)
 *   - Все новости (/news/<slug>.html)
 *   - Все акции (/promotion/<slug>.html)
 *   - Ключевые страницы пагинации (первые N для каждого раздела)
 *
 * Что НЕ включено:
 *   - Страницы с фильтрами (?search=, ?sort=, ?min_price=) — они помечены noindex
 *   - /admin/, /api/, /data/ — закрыты robots.txt и .htaccess
 *   - /uploads/ — это файлы, не контент
 */

require_once __DIR__ . '/includes/config.php';

// ============================================================
// НАСТРОЙКИ
// ============================================================

// Сколько страниц пагинации включать для каждого раздела.
// Например, 5 = страницы ?page=2, ?page=3, ?page=4, ?page=5 (1-я страница = сам раздел без ?page).
$paginationDepth = 5;

// Записей на страницу — должно совпадать с script.js и шаблонами!
$perPage = [
    'catalog'    => 12,
    'blog'       => 6,
    'news'       => 6,
    'promotions' => 9,
    'reviews'    => 10,
];

$dataDir = __DIR__ . '/data/';

// ============================================================
// ХЕЛПЕРЫ
// ============================================================

/**
 * Экранирование URL для XML.
 */
function xmlUrl(string $url): string {
    return htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * Экранирование даты для W3C (YYYY-MM-DD).
 */
function xmlDate(?string $date): ?string {
    if (empty($date)) return null;
    $ts = strtotime($date);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
}

/**
 * Читает JSON-файл, возвращает массив (или пустой массив).
 */
function readJson(string $path): array {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

/**
 * Дата последнего изменения файла данных (fallback для lastmod).
 * 
 * ВАЖНО: имя функции НЕ должно быть fileMtime(), 
 * потому что PHP уже имеет встроенную filemtime().
 */
function getFileMtime(string $path): ?string {
    if (!file_exists($path)) return null;
    return date('Y-m-d', filemtime($path));
}

/**
 * Формирует URL страницы записи.
 */
function itemUrl(string $type, array $item): string {
    $slug = $item['slug'] ?? $item['id'] ?? '';
    $prefixes = [
        'product'   => 'product',
        'blog'      => 'blog',
        'news'      => 'news',
        'promotion' => 'promotion',
    ];
    $prefix = $prefixes[$type] ?? $type;
    return SITE_URL . '/' . $prefix . '/' . rawurlencode($slug) . '.html';
}

// ============================================================
// СБОР URL
// ============================================================

$urls = [];

// ---------- 1. Главная ----------
$urls[] = [
    'loc'        => SITE_URL . '/',
    'lastmod'    => date('Y-m-d'),
    'changefreq' => 'daily',
    'priority'   => '1.0',
];

// ---------- 2. Статические разделы ----------
$staticPages = [
    ['/catalog',    '0.9', 'weekly',  'products.json'],
    ['/blog',       '0.9', 'weekly',  'blog.json'],
    ['/news',       '0.9', 'weekly',  'news.json'],
    ['/promotions', '0.9', 'weekly',  'promotions.json'],
    ['/reviews',    '0.8', 'weekly',  'reviews.json'],
    ['/faq',        '0.7', 'monthly', 'faq.json'],
    ['/contacts',   '0.5', 'yearly',  null],
    ['/search',     '0.3', 'monthly', null],
    // НОВАЯ СТРАНИЦА: О Грибомане (E-E-A-T)
    ['/about.php',  '0.6', 'monthly', null],
];

foreach ($staticPages as [$path, $priority, $changefreq, $dataFile]) {
    $lastmod = $dataFile
        ? getFileMtime($dataDir . $dataFile)
        : date('Y-m-d');
    $urls[] = [
        'loc'        => SITE_URL . $path,
        'lastmod'    => $lastmod,
        'changefreq' => $changefreq,
        'priority'   => $priority,
    ];
}

// ---------- 3. Страницы пагинации для разделов ----------
// (только для тех, где реально может быть >1 страницы)
$paginationTargets = [
    ['/catalog',    'products.json',   'catalog'],
    ['/blog',       'blog.json',       'blog'],
    ['/news',       'news.json',       'news'],
    ['/promotions', 'promotions.json', 'promotions'],
    ['/reviews',    'reviews.json',    'reviews'],
];

foreach ($paginationTargets as [$basePath, $dataFile, $key]) {
    $items = readJson($dataDir . $dataFile);
    $total = count($items);

    if ($key === 'reviews') {
        // Считаем только опубликованные отзывы
        $total = count(array_filter($items, function($r) {
            return ($r['status'] ?? 0) == 1;
        }));
    }

    $pp = $perPage[$key] ?? 10;
    $totalPages = max(1, (int)ceil($total / $pp));

    $lastmod = getFileMtime($dataDir . $dataFile);

    // Начинаем с 2-й страницы (1-я уже добавлена выше как сам раздел)
    $maxPage = min($totalPages, $paginationDepth + 1);
    for ($p = 2; $p <= $maxPage; $p++) {
        $urls[] = [
            'loc'        => SITE_URL . $basePath . '?page=' . $p,
            'lastmod'    => $lastmod,
            'changefreq' => 'weekly',
            'priority'   => '0.6',
        ];
    }
}

// ---------- 4. Товары ----------
$products = readJson($dataDir . 'products.json');
$productsMtime = getFileMtime($dataDir . 'products.json');
foreach ($products as $p) {
    $urls[] = [
        'loc'        => itemUrl('product', $p),
        'lastmod'    => xmlDate($p['updated'] ?? null) ?? $productsMtime,
        'changefreq' => 'monthly',
        'priority'   => '0.8',
    ];
}

// ---------- 5. Блог ----------
$blog = readJson($dataDir . 'blog.json');
$blogMtime = getFileMtime($dataDir . 'blog.json');
foreach ($blog as $a) {
    $urls[] = [
        'loc'        => itemUrl('blog', $a),
        'lastmod'    => xmlDate($a['date'] ?? null) ?? $blogMtime,
        'changefreq' => 'monthly',
        'priority'   => '0.7',
    ];
}

// ---------- 6. Новости ----------
$news = readJson($dataDir . 'news.json');
$newsMtime = getFileMtime($dataDir . 'news.json');
foreach ($news as $n) {
    $urls[] = [
        'loc'        => itemUrl('news', $n),
        'lastmod'    => xmlDate($n['date'] ?? null) ?? $newsMtime,
        'changefreq' => 'monthly',
        'priority'   => '0.6',
    ];
}

// ---------- 7. Акции ----------
$promotions = readJson($dataDir . 'promotions.json');
$promotionsMtime = getFileMtime($dataDir . 'promotions.json');
foreach ($promotions as $promo) {
    $urls[] = [
        'loc'        => itemUrl('promotion', $promo),
        'lastmod'    => xmlDate($promo['start_date'] ?? null) ?? $promotionsMtime,
        'changefreq' => 'weekly',
        'priority'   => '0.7',
    ];
}

// ============================================================
// ВЫВОД XML
// ============================================================

// Кешируем на 1 час, чтобы не генерировать на каждый запрос
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>" . xmlUrl($u['loc']) . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo "    <lastmod>" . xmlUrl($u['lastmod']) . "</lastmod>\n";
    }
    if (!empty($u['changefreq'])) {
        echo "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
    }
    if (!empty($u['priority'])) {
        echo "    <priority>" . $u['priority'] . "</priority>\n";
    }
    echo "  </url>\n";
}

echo '</urlset>';