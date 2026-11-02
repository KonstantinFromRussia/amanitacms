<?php
require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

$pageNum = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($pageNum - 1) * $perPage;

$page_title = $siteName . ' — Акции и предложения';
if ($pageNum > 1) $page_title .= ' — страница ' . $pageNum;
$page_description = 'Специальные акции, скидки и предложения на сушёные грибы.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

$promotions = json_decode(file_get_contents(__DIR__ . '/data/promotions.json'), true);
usort($promotions, function($a, $b) {
    return strtotime($b['start_date'] ?? '1970-01-01') - strtotime($a['start_date'] ?? '1970-01-01');
});

$totalItems = count($promotions);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$pageItems = array_slice($promotions, $offset, $perPage);

// Хелпер для ссылки на страницу пагинации
function pageUrl($n) {
    if ($n <= 1) return '/promotions';
    return '/promotions?page=' . $n;
}
?>
<main class="py-12 px-4 max-w-7xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-black mb-4 text-[var(--text-primary)]">🎉 Акции и предложения</h1>
    <p class="text-[var(--text-secondary)] mb-10">Не пропустите выгодные предложения и сезонные скидки!</p>

    <?php if (empty($pageItems)): ?>
        <div class="text-center py-16 bg-[var(--bg-card)] rounded-lg border border-[var(--border)]">
            <span class="text-6xl mb-4 block">🎁</span>
            <h2 class="text-2xl font-bold mb-2">Акций пока нет</h2>
            <p class="text-[var(--text-muted)]">Следите за обновлениями – скоро появятся новые предложения.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($pageItems as $promo): ?>
                <?php 
                $image = !empty($promo['image']) ? $promo['image'] : null;
                $start = !empty($promo['start_date']) ? date('d.m.Y', strtotime($promo['start_date'])) : '';
                $end   = !empty($promo['end_date']) ? date('d.m.Y', strtotime($promo['end_date'])) : '';
                $dateString = '';
                if ($start && $end) $dateString = $start . ' – ' . $end;
                elseif ($start) $dateString = 'с ' . $start;
                elseif ($end) $dateString = 'до ' . $end;
                $url = '/promotion/' . htmlspecialchars($promo['slug'] ?? $promo['id']) . '.html';
                ?>
                <a href="<?php echo $url; ?>" class="group block bg-[var(--bg-card)] border border-[var(--border)] rounded-xl overflow-hidden hover:shadow-xl transition hover:translate-y-[-4px]">
                    <?php if ($image): ?>
                        <?php echo renderPicture($image, $promo['title'], 'w-full h-48 object-cover', 'lazy'); ?>
                    <?php else: ?>
                        <div class="w-full h-48 bg-gradient-to-r from-[var(--accent-dark)] to-[var(--accent-light)] flex items-center justify-center text-6xl">🎁</div>
                    <?php endif; ?>
                    <div class="p-5">
                        <span class="text-sm text-[var(--accent)] font-semibold"><?php echo htmlspecialchars($dateString); ?></span>
                        <h2 class="text-xl font-bold mt-1 mb-2 text-[var(--text-primary)] group-hover:text-[var(--accent)] transition"><?php echo htmlspecialchars($promo['title']); ?></h2>
                        <p class="text-[var(--text-secondary)] text-sm mb-4"><?php echo nl2br(htmlspecialchars(mb_substr(strip_tags($promo['description'] ?? ''), 0, 120))); ?>…</p>
                        <span class="inline-block text-sm text-[var(--accent)] group-hover:underline">Подробнее →</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="catalog-pagination" aria-label="Навигация по страницам акций">
                <?php if ($pageNum > 1): ?>
                    <a href="<?php echo pageUrl($pageNum - 1); ?>" class="pagination__link" rel="prev">← Назад</a>
                <?php else: ?>
                    <span class="pagination__link pagination__link--disabled" aria-disabled="true">← Назад</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $pageNum): ?>
                        <span class="pagination__link pagination__link--active" aria-current="page"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo pageUrl($i); ?>" class="pagination__link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pageNum < $totalPages): ?>
                    <a href="<?php echo pageUrl($pageNum + 1); ?>" class="pagination__link" rel="next">Вперёд →</a>
                <?php else: ?>
                    <span class="pagination__link pagination__link--disabled" aria-disabled="true">Вперёд →</span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>