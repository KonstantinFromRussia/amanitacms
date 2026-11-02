<?php
$reviews = json_decode(file_get_contents(__DIR__ . '/../../data/reviews.json'), true);
$published = array_filter($reviews, function($item) { return ($item['status'] ?? 0) == 1; });
usort($published, function($a, $b) {
    return strtotime($b['date'] ?? '1970-01-01') - strtotime($a['date'] ?? '1970-01-01');
});
$latest = array_slice($published, 0, 3);
if (empty($latest)) return;
?>
<section class="py-16 px-4 max-w-7xl mx-auto fade-in-section">
    <div class="flex items-center justify-between mb-10 flex-wrap gap-3">
        <h2 class="text-2xl md:text-3xl font-bold text-[var(--text-primary)]">⭐ Отзывы покупателей</h2>
        <a href="/reviews" class="btn-outline btn-sm">Все отзывы →</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($latest as $review): ?>
            <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-5 hover:shadow-md transition">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-[var(--accent)] flex items-center justify-center text-white font-bold">
                        <?php echo mb_substr($review['name'] ?? 'А', 0, 1); ?>
                    </div>
                    <div>
                        <h3 class="font-bold text-[var(--text-primary)]"><?php echo htmlspecialchars($review['name']); ?></h3>
                        <div class="text-sm text-[var(--accent)]">⭐ <?php echo $review['rating'] ?? 5; ?>/5</div>
                    </div>
                </div>
                <p class="text-[var(--text-secondary)] text-sm line-clamp-3"><?php echo nl2br(htmlspecialchars(mb_substr($review['text'] ?? '', 0, 150))); ?>…</p>
                <div class="mt-3 text-right">
                    <a href="/reviews#review-<?php echo $review['id']; ?>" class="text-xs text-[var(--accent)] hover:underline">Читать полностью →</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>