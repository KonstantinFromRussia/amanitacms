<?php
/**
 * index.php — главная страница сайта.
 *
 * В начале файла — проверка на 404 для «мусорных» URL,
 * которые попали сюда через fallback в .htaccess.
 */

// ============================================================
// ПРОВЕРКА 404 (для «мусорных» URL, попавших через fallback)
// ============================================================
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$allowedPaths = [
    '/', '/catalog', '/blog', '/news', '/faq', '/promotions', '/reviews',
    '/search', '/contacts', '/about.php', '/admin.php', '/auth.php',
    '/logout.php', '/sitemap.xml', '/robots.txt', '/backup.php'
];
$isKnown = in_array($requestUri, $allowedPaths)
    || strpos($requestUri, '/product/') === 0
    || strpos($requestUri, '/blog/') === 0
    || strpos($requestUri, '/news/') === 0
    || strpos($requestUri, '/promotion/') === 0
    || strpos($requestUri, '/api/') === 0;

if (!$isKnown) {
    // Передаём управление странице 404
    require __DIR__ . '/404.php';
    exit;
}

// ============================================================
// ДАЛЬШЕ — ОБЫЧНАЯ ЛОГИКА ГЛАВНОЙ СТРАНИЦЫ
// ============================================================
$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';
$page_title = $settings['site_seo_title'] ?? ($siteName . ' — Сушеные мухоморы премиум‑класса');
$page_description = $settings['site_description'] ?? 'Интернет-магазин сушеных грибов премиум-качества. Органические грибы, бережная сушка, доставка по России.';

// Читаем настройки для главной страницы
$hero_premium = $settings['hero_premium'] ?? 'Премиум-качество с 2020 года';
$hero_title = $settings['hero_title'] ?? 'Откройте мир<br>натуральных сушеных грибов';
$hero_subtitle = $settings['hero_subtitle'] ?? 'Бережная сушка, ручной отбор и строгий контроль качества. Мы доставляем лучшие грибы со всего мира прямо к вашему столу.';
$hero_btn_catalog = $settings['hero_btn_catalog'] ?? '🛍 Перейти в каталог';
$hero_btn_blog = $settings['hero_btn_blog'] ?? '📖 Узнать больше';

$advantages_title = $settings['advantages_title'] ?? 'Почему выбирают нас';
$adv1_icon = $settings['adv1_icon'] ?? '🌿';
$adv1_title = $settings['adv1_title'] ?? '100% Органика';
$adv1_desc = $settings['adv1_desc'] ?? 'Все грибы собраны в экологически чистых регионах, без химикатов и пестицидов.';
$adv2_icon = $settings['adv2_icon'] ?? '🔬';
$adv2_title = $settings['adv2_title'] ?? 'Лабораторный контроль';
$adv2_desc = $settings['adv2_desc'] ?? 'Каждая партия проходит проверку на безопасность и содержание активных веществ.';
$adv3_icon = $settings['adv3_icon'] ?? '📦';
$adv3_title = $settings['adv3_title'] ?? 'Бережная упаковка';
$adv3_desc = $settings['adv3_desc'] ?? 'Вакуумная упаковка сохраняет свежесть и аромат до 24 месяцев.';
$adv4_icon = $settings['adv4_icon'] ?? '🚚';
$adv4_title = $settings['adv4_title'] ?? 'Быстрая доставка';
$adv4_desc = $settings['adv4_desc'] ?? 'Отправка в день заказа по всей России. Бесплатно при заказе от 3000₽.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>
<main>
    <!-- Hero -->
    <section class="hero-section">
        <div class="hero-glow hero-glow--amber"></div>
        <div class="hero-glow hero-glow--green"></div>
        <div class="relative z-10 max-w-4xl mx-auto">
            <span class="inline-block text-[var(--accent)] font-semibold text-sm uppercase tracking-widest mb-3 fade-in-section"><?php echo htmlspecialchars($hero_premium); ?></span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black leading-tight mb-6 fade-in-section"><?php echo $hero_title; ?></h1>
            <p class="text-lg md:text-xl text-[var(--text-secondary)] max-w-2xl mx-auto mb-8 fade-in-section"><?php echo nl2br(htmlspecialchars($hero_subtitle)); ?></p>
            <div class="flex flex-wrap gap-3 justify-center fade-in-section">
                <a href="/catalog" class="btn-primary"><?php echo htmlspecialchars($hero_btn_catalog); ?></a>
                <a href="/blog" class="btn-outline"><?php echo htmlspecialchars($hero_btn_blog); ?></a>
            </div>
        </div>
    </section>

    <!-- Преимущества -->
    <section class="py-16 px-4 max-w-7xl mx-auto fade-in-section" id="advantages-block">
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-12"><?php echo htmlspecialchars($advantages_title); ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="advantage-card"><div class="advantage-icon"><?php echo htmlspecialchars($adv1_icon); ?></div><h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($adv1_title); ?></h3><p class="text-sm"><?php echo htmlspecialchars($adv1_desc); ?></p></div>
            <div class="advantage-card"><div class="advantage-icon"><?php echo htmlspecialchars($adv2_icon); ?></div><h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($adv2_title); ?></h3><p class="text-sm"><?php echo htmlspecialchars($adv2_desc); ?></p></div>
            <div class="advantage-card"><div class="advantage-icon"><?php echo htmlspecialchars($adv3_icon); ?></div><h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($adv3_title); ?></h3><p class="text-sm"><?php echo htmlspecialchars($adv3_desc); ?></p></div>
            <div class="advantage-card"><div class="advantage-icon"><?php echo htmlspecialchars($adv4_icon); ?></div><h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($adv4_title); ?></h3><p class="text-sm"><?php echo htmlspecialchars($adv4_desc); ?></p></div>
        </div>
    </section>

    <!-- Популярные товары -->
    <section class="py-16 px-4 max-w-7xl mx-auto fade-in-section" id="featuredProducts">
        <div class="flex items-center justify-between mb-10 flex-wrap gap-3">
            <h2 class="text-2xl md:text-3xl font-bold">🔥 Популярные товары</h2>
            <a href="/catalog" class="btn-outline btn-sm">Смотреть все →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5" id="featuredGrid"></div>
    </section>

    <!-- Превью блога -->
    <section class="py-16 px-4 max-w-7xl mx-auto fade-in-section">
        <div class="flex items-center justify-between mb-10 flex-wrap gap-3">
            <h2 class="text-2xl md:text-3xl font-bold">📝 Из блога</h2>
            <a href="/blog" class="btn-outline btn-sm">Все статьи →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5" id="blogPreviewGrid"></div>
    </section>

    <!-- Превью новостей -->
    <section class="py-16 px-4 max-w-7xl mx-auto fade-in-section">
        <div class="flex items-center justify-between mb-10 flex-wrap gap-3">
            <h2 class="text-2xl md:text-3xl font-bold">📰 Новости магазина</h2>
            <a href="/news" class="btn-outline btn-sm">Все новости →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5" id="newsPreviewGrid"></div>
    </section>

    <!-- Блок акций -->
    <?php include_once __DIR__ . '/includes/blocks/promotions.php'; ?>

    <!-- Блок отзывов -->
    <?php include_once __DIR__ . '/includes/blocks/reviews.php'; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>