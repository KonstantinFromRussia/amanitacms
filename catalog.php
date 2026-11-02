<?php
require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

// --- SEO: определяем текущую страницу пагинации для title/canonical ---
$pageNum = max(1, (int)($_GET['page'] ?? 1));

$page_title = $siteName . ' — Каталог товаров';
if ($pageNum > 1) {
    $page_title .= ' — страница ' . $pageNum;
}
$page_description = $settings['site_description'] ?? 'Купить сушеные грибы. Доставка по России.';

// Canonical с сохранением фильтров (кроме page)
$canonicalParams = $_GET;
unset($canonicalParams['page']);
$canonicalQuery = http_build_query($canonicalParams);
$canonical = SITE_URL . '/catalog';
if ($pageNum > 1) {
    $canonicalParams['page'] = $pageNum;
    $canonical .= '?' . http_build_query($canonicalParams);
} elseif ($canonicalQuery !== '') {
    $canonical .= '?' . $canonicalQuery;
}

// --- Schema.org: ItemList текущей страницы ---
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);
$perPage = 12;
$offset = ($pageNum - 1) * $perPage;
$pageProducts = array_slice($products, $offset, $perPage);
$itemList = [];
foreach ($pageProducts as $i => $p) {
    $itemList[] = [
        "@type" => "ListItem",
        "position" => $offset + $i + 1,
        "url" => SITE_URL . '/product/' . ($p['slug'] ?? $p['id']) . '.html',
        "name" => $p['name']
    ];
}
$listSchema = [
    "@context" => "https://schema.org",
    "@type" => "ItemList",
    "itemListElement" => $itemList
];
$schema = '<script type="application/ld+json">' . json_encode($listSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
echo $schema;
?>
<main class="py-12 px-4 max-w-7xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-black mb-2">🛍 Каталог товаров</h1>

    <!-- Блок фильтров -->
    <div class="filter-block">
        <div class="filter-row">
            <div class="filter-group search-group">
                <label>🔍 Поиск</label>
                <input type="text" id="search-input" placeholder="Название или описание...">
            </div>
            <div class="filter-group">
                <label>📊 Сортировка</label>
                <select id="sort-select">
                    <option value="id_asc">По умолчанию</option>
                    <option value="price_asc">💰 Сначала дешёвые</option>
                    <option value="price_desc">💰 Сначала дорогие</option>
                    <option value="name_asc">📝 По названию (А-Я)</option>
                </select>
            </div>
            <div class="filter-group">
                <label>💰 Цена от</label>
                <input type="number" id="min-price" placeholder="0">
            </div>
            <div class="filter-group">
                <label>💰 Цена до</label>
                <input type="number" id="max-price" placeholder="10000">
            </div>
            <div class="filter-group">
                <button id="apply-filters" class="btn-primary">Применить</button>
                <button id="reset-filters" class="btn-outline">Сбросить</button>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-8" id="catalogFilters">
        <button class="btn-primary btn-sm filter-btn active" data-filter="all">Все</button>
        <button class="btn-outline btn-sm filter-btn" data-filter="medicinal">🍃 Лечебные</button>
        <button class="btn-outline btn-sm filter-btn" data-filter="culinary">🍳 Кулинарные</button>
        <button class="btn-outline btn-sm filter-btn" data-filter="exotic">✨ Экзотические</button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="catalogGrid"></div>

    <!-- Контейнер пагинации -->
    <nav class="catalog-pagination" id="catalogPagination" aria-label="Навигация по страницам каталога"></nav>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>