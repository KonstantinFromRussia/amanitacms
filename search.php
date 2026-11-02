<?php
require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';
$query = trim($_GET['q'] ?? '');
$page_title = $siteName . ' — Результаты поиска: ' . htmlspecialchars($query);
$page_description = 'Результаты поиска по сайту.';
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

$searchLower = mb_strtolower($query);
$found = [];

// Товары
$products = json_decode(file_get_contents(__DIR__ . '/data/products.json'), true);
$found['products'] = array_filter($products, function($p) use ($searchLower) {
    $name = mb_strtolower($p['name'] ?? '');
    $desc = mb_strtolower($p['desc'] ?? '');
    return strpos($name, $searchLower) !== false || strpos($desc, $searchLower) !== false;
});

// Блог
$blog = json_decode(file_get_contents(__DIR__ . '/data/blog.json'), true);
$found['blog'] = array_filter($blog, function($b) use ($searchLower) {
    return strpos(mb_strtolower($b['title'] ?? ''), $searchLower) !== false
        || strpos(mb_strtolower($b['content'] ?? ''), $searchLower) !== false;
});

// Новости
$news = json_decode(file_get_contents(__DIR__ . '/data/news.json'), true);
$found['news'] = array_filter($news, function($n) use ($searchLower) {
    return strpos(mb_strtolower($n['title'] ?? ''), $searchLower) !== false
        || strpos(mb_strtolower($n['content'] ?? ''), $searchLower) !== false;
});

// Акции (добавлено)
$promotions = json_decode(file_get_contents(__DIR__ . '/data/promotions.json'), true);
$found['promotions'] = array_filter($promotions, function($p) use ($searchLower) {
    return strpos(mb_strtolower($p['title'] ?? ''), $searchLower) !== false
        || strpos(mb_strtolower($p['description'] ?? ''), $searchLower) !== false;
});
?>
<main class="py-12 px-4 max-w-7xl mx-auto">
    <h1 class="text-3xl font-black mb-4">Результаты поиска: <?php echo htmlspecialchars($query); ?></h1>
    <?php if (empty($found['products']) && empty($found['blog']) && empty($found['news']) && empty($found['promotions'])): ?>
        <p class="text-[var(--text-muted)]">Ничего не найдено. Попробуйте изменить запрос.</p>
    <?php else: ?>
        <?php if (!empty($found['products'])): ?>
            <h2 class="text-2xl font-bold mt-8 mb-4">🍄 Товары</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($found['products'] as $p): ?>
                    <div class="product-card cursor-pointer" onclick="location.href='/product/<?php echo $p['slug'] ?? $p['id']; ?>.html'">
                        <div class="product-card__image">
                            <?php if ($p['image']): ?>
                                <?php echo renderPicture($p['image'], $p['alt'] ?? $p['name'], 'w-full h-full object-cover', 'lazy'); ?>
                            <?php else: ?>
                                <div class="product-card__image-placeholder">🍄</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold"><?php echo htmlspecialchars($p['name']); ?></h3>
                            <p class="text-[var(--accent)] font-bold mt-2"><?php echo number_format($p['price'], 0, ',', ' '); ?> ₽</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($found['promotions'])): ?>
            <h2 class="text-2xl font-bold mt-8 mb-4">🎁 Акции</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <?php foreach ($found['promotions'] as $promo): ?>
                    <a href="/promotion/<?php echo $promo['slug'] ?? $promo['id']; ?>.html" class="bg-[var(--bg-card)] rounded-xl overflow-hidden hover:shadow-md transition block">
                        <?php if ($promo['image']): ?>
                            <?php echo renderPicture($promo['image'], $promo['alt'] ?? $promo['title'], 'w-full h-40 object-cover', 'lazy'); ?>
                        <?php else: ?>
                            <div class="w-full h-40 bg-gradient-to-r from-[var(--accent-dark)] to-[var(--accent-light)] flex items-center justify-center text-4xl">🎁</div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h3 class="font-semibold"><?php echo htmlspecialchars($promo['title']); ?></h3>
                            <p class="text-sm text-[var(--text-muted)] mt-1"><?php echo htmlspecialchars(mb_substr(strip_tags($promo['description'] ?? ''), 0, 100)); ?>…</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($found['blog'])): ?>
            <h2 class="text-2xl font-bold mt-8 mb-4">📝 Статьи блога</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <?php foreach ($found['blog'] as $b): ?>
                    <a href="/blog/<?php echo $b['slug'] ?? $b['id']; ?>.html" class="bg-[var(--bg-card)] rounded-lg p-4 hover:shadow-md transition">
                        <h3 class="font-semibold"><?php echo htmlspecialchars($b['title']); ?></h3>
                        <p class="text-sm text-[var(--text-muted)] mt-1"><?php echo htmlspecialchars(mb_substr(strip_tags($b['content'] ?? ''), 0, 100)); ?>…</p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($found['news'])): ?>
            <h2 class="text-2xl font-bold mt-8 mb-4">📰 Новости</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <?php foreach ($found['news'] as $n): ?>
                    <a href="/news/<?php echo $n['slug'] ?? $n['id']; ?>.html" class="bg-[var(--bg-card)] rounded-lg p-4 hover:shadow-md transition">
                        <h3 class="font-semibold"><?php echo htmlspecialchars($n['title']); ?></h3>
                        <p class="text-sm text-[var(--text-muted)] mt-1"><?php echo htmlspecialchars(mb_substr(strip_tags($n['content'] ?? ''), 0, 100)); ?>…</p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>