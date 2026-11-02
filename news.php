<?php
require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';
$pageNum = max(1, (int)($_GET['page'] ?? 1));

$page_title = 'Новости — ' . $siteName;
if ($pageNum > 1) $page_title .= ' — страница ' . $pageNum;
$page_description = 'Новости магазина: акции, поступления, события.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="py-12 px-4 max-w-7xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-black mb-2 text-[var(--text-primary)]">📰 Новости</h1>
    <p class="text-[var(--text-secondary)] mb-8">Актуальные события компании: анонсы, акции и обновления.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="newsFullGrid"></div>
    <nav class="catalog-pagination" id="newsPagination" aria-label="Навигация по страницам новостей"></nav>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>