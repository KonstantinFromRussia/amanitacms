<?php
require_once __DIR__ . '/../config.php';

$promotions = json_decode(file_get_contents(__DIR__ . '/../../data/promotions.json'), true);
if (empty($promotions)) return;

// Берём только 3 последние (или можно сортировать по дате)
$active = array_slice($promotions, 0, 3);
?>
<section class="py-16 px-4 max-w-7xl mx-auto fade-in-section">
    <div class="flex items-center justify-between mb-10 flex-wrap gap-3">
        <h2 class="text-2xl md:text-3xl font-bold text-[var(--text-primary)]">🎁 Наши акции</h2>
        <a href="/promotions" class="btn-outline btn-sm">Все акции →</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($active as $promo): ?>
            <?php 
            $image = !empty($promo['image']) ? $promo['image'] : null;
            $url = '/promotion/' . htmlspecialchars($promo['slug'] ?? $promo['id']) . '.html';
            ?>
            <a href="<?php echo $url; ?>" class="group block bg-[var(--bg-card)] border border-[var(--border)] rounded-xl overflow-hidden hover:shadow-lg transition hover:translate-y-[-4px]">
                <?php if ($image): ?>
                    <?php echo renderPicture($image, $promo['title'], 'w-full h-40 object-cover', 'lazy'); ?>
                <?php else: ?>
                    <div class="w-full h-40 bg-gradient-to-r from-[var(--accent-dark)] to-[var(--accent-light)] flex items-center justify-center text-5xl">🎁</div>
                <?php endif; ?>
                <div class="p-4">
                    <h3 class="font-bold text-lg text-[var(--text-primary)] group-hover:text-[var(--accent)] transition"><?php echo htmlspecialchars($promo['title']); ?></h3>
                    <p class="text-[var(--text-muted)] text-sm mt-1"><?php echo nl2br(htmlspecialchars(mb_substr(strip_tags($promo['description'] ?? ''), 0, 80))); ?>…</p>
                    <span class="inline-block mt-3 text-sm text-[var(--accent)] group-hover:underline">Подробнее →</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>