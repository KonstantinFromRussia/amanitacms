<?php
require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

$pageNum = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($pageNum - 1) * $perPage;

$page_title = $siteName . ' — Отзывы наших клиентов';
if ($pageNum > 1) $page_title .= ' — страница ' . $pageNum;
$page_description = 'Честные отзывы покупателей о качестве грибов, доставке и сервисе.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

$reviews = json_decode(file_get_contents(__DIR__ . '/data/reviews.json'), true);
$published = array_filter($reviews, function($item) { return ($item['status'] ?? 0) == 1; });
usort($published, function($a, $b) {
    return strtotime($b['date'] ?? '1970-01-01') - strtotime($a['date'] ?? '1970-01-01');
});

$totalItems = count($published);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$pageItems = array_slice($published, $offset, $perPage);

function pageUrl($n) {
    if ($n <= 1) return '/reviews';
    return '/reviews?page=' . $n;
}
?>
<main class="py-12 px-4 max-w-5xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-black mb-4 text-[var(--text-primary)]">⭐ Отзывы наших клиентов</h1>
    <p class="text-[var(--text-secondary)] mb-10">Реальные люди делятся впечатлениями о нашей продукции и сервисе.</p>

    <?php if (empty($pageItems)): ?>
        <div class="text-center py-16 bg-[var(--bg-card)] rounded-lg border border-[var(--border)]">
            <span class="text-6xl mb-4 block">💬</span>
            <h2 class="text-2xl font-bold mb-2">Пока нет отзывов</h2>
            <p class="text-[var(--text-muted)]">Будьте первым, кто оставит отзыв о нашей продукции!</p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($pageItems as $review): ?>
                <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-[var(--accent)] flex items-center justify-center text-white font-bold text-lg">
                            <?php echo mb_substr($review['name'] ?? 'А', 0, 1); ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-[var(--text-primary)]"><?php echo htmlspecialchars($review['name']); ?></h3>
                            <div class="flex items-center gap-2 text-sm text-[var(--text-muted)]">
                                <span>⭐ <?php echo str_repeat('★', (int)($review['rating'] ?? 5)); ?><?php echo str_repeat('☆', 5 - (int)($review['rating'] ?? 5)); ?></span>
                                <span>•</span>
                                <span><?php echo date('d.m.Y', strtotime($review['date'] ?? 'now')); ?></span>
                            </div>
                        </div>
                    </div>
                    <p class="text-[var(--text-secondary)] leading-relaxed"><?php echo nl2br(htmlspecialchars($review['text'] ?? '')); ?></p>
                    <?php if (!empty($review['product'])): ?>
                        <div class="mt-3 text-sm text-[var(--accent)]">Товар: <?php echo htmlspecialchars($review['product']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="catalog-pagination" aria-label="Навигация по страницам отзывов">
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