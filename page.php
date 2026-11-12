<?php
/**
 * page.php — универсальная страница для товаров, блога, новостей и акций.
 * 04.11.2026: добавлен type=page — вывод произвольных страниц из pages.json.
 * 11.11.2026: для type=page og:image и twitter:image теперь отдают WebP (если есть).
 */

require_once __DIR__ . '/includes/config.php';
$settings_global = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings_global['site_name'] ?? 'Магазин';

$type = $_GET['type'] ?? '';
$slug = $_GET['slug'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

// 04.11.2026: добавлен type=page для произвольных страниц из pages.json
$allowed = ['product', 'blog', 'news', 'promotion', 'page'];
if (!in_array($type, $allowed)) { http_response_code(404); die('Страница не найдена'); }

$dataDir = __DIR__ . '/data/';
$fileName = '';
if ($type === 'product') $fileName = 'products.json';
elseif ($type === 'blog') $fileName = 'blog.json';
elseif ($type === 'news') $fileName = 'news.json';
elseif ($type === 'promotion') $fileName = 'promotions.json';

// 04.11.2026: type=page — отдельная логика (pages.json, проверка status).
// $items остаётся null, чтобы не уйти в общий поиск по slug/ID ниже.
$items = null;
if ($type !== 'page') {
    $items = json_decode(file_get_contents($dataDir . $fileName), true) ?? [];
}
$item = null;

// Поиск по slug или ID (для товаров/блога/новостей/акций)
if ($type !== 'page') {
    // Поиск по slug или ID
    if ($slug) {
        foreach ($items as $i) {
            if (($i['slug'] ?? '') === $slug) { $item = $i; break; }
        }
    }
    if (!$item && $id) {
        foreach ($items as $i) {
            if ((int)$i['id'] === $id) { $item = $i; break; }
        }
    }
    if (!$item) { http_response_code(404); die('Запись не найдена'); }
} else {
    // 04.11.2026: type=page — ищем в pages.json, отдаём только status=1.
    // Черновики (status=0) и несуществующие slug → 404.php (дружелюбная).
    $pagesFile = $dataDir . 'pages.json';
    $pages = file_exists($pagesFile) ? (json_decode(file_get_contents($pagesFile), true) ?: []) : [];
    foreach ($pages as $p) {
        if (($p['slug'] ?? '') === $slug) { $item = $p; break; }
    }
    if (!$item || (int)($item['status'] ?? 0) !== 1) {
        require __DIR__ . '/404.php';
        exit;
    }
}

// Загружаем отзывы для товара (только если тип product)
$reviews = [];
$allReviewsForProduct = [];
if ($type === 'product') {
    $reviewsData = json_decode(file_get_contents(__DIR__ . '/data/reviews.json'), true);
    
    // Все отзывы для этого товара
    $allReviewsForProduct = array_filter($reviewsData, function($r) use ($item) {
        $reviewProduct = trim(mb_strtolower($r['product'] ?? ''));
        $itemName = trim(mb_strtolower($item['name']));
        return $reviewProduct === $itemName;
    });
    
    // Только опубликованные
    $reviews = array_filter($allReviewsForProduct, function($r) {
        return ($r['status'] ?? 0) == 1;
    });
    
    // Сортируем по дате (новые сверху)
    usort($reviews, function($a, $b) {
        return strtotime($b['date'] ?? '1970-01-01') - strtotime($a['date'] ?? '1970-01-01');
    });
}

$ratingValue = 0;
$reviewCount = count($reviews);
if ($reviewCount > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $ratingValue = round($sum / $reviewCount, 1);
}

// Генерация капчи для формы отзыва
if ($type === 'product') {
    $captchaA = random_int(2, 9);
    $captchaB = random_int(2, 9);
    $captchaExpected = $captchaA + $captchaB;
    $_SESSION['captcha_expected'] = $captchaExpected;
}

// Общие SEO-переменные
$page_image = '';
$og_type = 'website';

// 04.11.2026: SEO для произвольной страницы (type=page).
if ($type === 'page') {
    $page_title = !empty($item['seo_title']) ? $item['seo_title'] : ($item['title'] . ' — ' . $siteName);
    $page_description = !empty($item['seo_description'])
        ? $item['seo_description']
        : mb_substr(strip_tags($item['content'] ?? ''), 0, 160);
    
    // 11.11.2026: для og:image отдаем WebP, если он есть рядом с оригиналом.
    if (!empty($item['image'])) {
        $imgPath = $item['image'];
        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $imgPath);
        $imgFs = __DIR__ . $imgPath;
        $webpFs = __DIR__ . $webpPath;
        if (file_exists($webpFs) && filesize($webpFs) > 0) {
            $page_image = SITE_URL . $webpPath;
        } else {
            $page_image = SITE_URL . $imgPath;
        }
    }
    
    $page_url = SITE_URL . '/' . $item['slug'];
    $og_type = 'article';

    // Schema.org WebPage + BreadcrumbList
    $schemaArray = [
        "@context" => "https://schema.org",
        "@type" => "WebPage",
        "name" => $item['title'],
        "description" => $page_description,
        "url" => $page_url,
        "datePublished" => $item['created_at'] ?? '',
        "dateModified" => $item['updated_at'] ?? '',
        "isPartOf" => ["@type" => "WebSite", "name" => $siteName, "url" => SITE_URL],
    ];
    if ($page_image) { $schemaArray["image"] = $page_image; }
    $schema = '<script type="application/ld+json">' . json_encode($schemaArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

    $breadcrumbs = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => [
            ["@type" => "ListItem", "position" => 1, "name" => "Главная", "item" => SITE_URL],
            ["@type" => "ListItem", "position" => 2, "name" => $item['title'], "item" => $page_url],
        ]
    ];
    $breadcrumbsHtml = '<script type="application/ld+json">' . json_encode($breadcrumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

if ($type === 'product') {
    // Если есть SEO-заголовок, используем его. Иначе — старое название
    $page_title = !empty($item['seo_title']) ? $item['seo_title'] : ($item['name'] . ' — ' . $siteName);
    
    $desc = !empty($item['desc']) ? $item['desc'] : strip_tags($item['details'] ?? '');
    $page_description = mb_substr($desc, 0, 160);
    $page_image = !empty($item['image']) ? SITE_URL . $item['image'] : '';
    $page_url = SITE_URL . '/product/' . ($item['slug'] ?? $item['id']) . '.html';
    $image_alt = $item['alt'] ?? $item['name'];
    $og_type = 'product';

    // Schema.org Product
    $schemaArray = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => $item['name'],
        "description" => strip_tags($page_description),
        "image" => $page_image,
        "url" => $page_url,
        "brand" => ["@type" => "Brand", "name" => $siteName],
        "offers" => [
            "@type" => "Offer",
            "price" => $item['price'],
            "priceCurrency" => "RUB",
            "availability" => "https://schema.org/InStock",
            "url" => $page_url
        ]
    ];
    if ($ratingValue > 0) {
        $schemaArray["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => $ratingValue,
            "reviewCount" => $reviewCount
        ];
        $reviewList = [];
        foreach ($reviews as $review) {
            $reviewList[] = [
                "@type" => "Review",
                "author" => ["@type" => "Person", "name" => $review['name']],
                "datePublished" => $review['date'],
                "reviewRating" => ["@type" => "Rating", "ratingValue" => $review['rating'], "bestRating" => "5"],
                "reviewBody" => mb_substr($review['text'], 0, 200)
            ];
        }
        $schemaArray["review"] = $reviewList;
    }
    if (!empty($item['sku'])) {
        $schemaArray["sku"] = $item['sku'];
    }
    $schema = '<script type="application/ld+json">' . json_encode($schemaArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
} elseif ($type === 'blog') {
    $page_title = $item['title'] . ' — ' . $siteName;
    $desc = !empty($item['excerpt']) ? $item['excerpt'] : strip_tags($item['content'] ?? '');
    $page_description = mb_substr($desc, 0, 160);
    $page_image = !empty($item['image']) ? SITE_URL . $item['image'] : '';
    $page_url = SITE_URL . '/blog/' . ($item['slug'] ?? $item['id']) . '.html';
    $image_alt = $item['alt'] ?? $item['title'];
    $og_type = 'article';

    $schemaArray = [
        "@context" => "https://schema.org",
        "@type" => "Article",
        "headline" => $item['title'],
        "description" => $page_description,
        "image" => $page_image,
        "datePublished" => $item['date'] ?? '',
        "author" => ["@type" => "Organization", "name" => $siteName],
        "publisher" => ["@type" => "Organization", "name" => $siteName]
    ];
    $schema = '<script type="application/ld+json">' . json_encode($schemaArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
} elseif ($type === 'news') {
    $page_title = $item['title'] . ' — ' . $siteName;
    $desc = !empty($item['excerpt']) ? $item['excerpt'] : strip_tags($item['content'] ?? '');
    $page_description = mb_substr($desc, 0, 160);
    $page_url = SITE_URL . '/news/' . ($item['slug'] ?? $item['id']) . '.html';
    $og_type = 'article';

    $schemaArray = [
        "@context" => "https://schema.org",
        "@type" => "NewsArticle",
        "headline" => $item['title'],
        "description" => $page_description,
        "datePublished" => $item['date'] ?? '',
        "author" => ["@type" => "Organization", "name" => $siteName],
        "publisher" => ["@type" => "Organization", "name" => $siteName]
    ];
    $schema = '<script type="application/ld+json">' . json_encode($schemaArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
} elseif ($type === 'promotion') { // promotion
    $page_title = $item['title'] . ' — Акция на ' . $siteName;
    $desc = !empty($item['description']) ? $item['description'] : '';
    $page_description = mb_substr(strip_tags($desc), 0, 160);
    $page_image = !empty($item['image']) ? SITE_URL . $item['image'] : '';
    $page_url = SITE_URL . '/promotion/' . ($item['slug'] ?? $item['id']) . '.html';
    $og_type = 'article';
    $schema = '';
}

// Хлебные крошки (для товаров/блога/новостей/акций; для page — свои, см. выше)
if ($type !== 'page') {
    $breadcrumbs = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => [
            ["@type" => "ListItem", "position" => 1, "name" => "Главная", "item" => SITE_URL],
            ["@type" => "ListItem", "position" => 2, "name" => ($type === 'product' ? "Каталог" : ($type === 'blog' ? "Блог" : ($type === 'news' ? "Новости" : "Акции"))), "item" => SITE_URL . '/' . ($type === 'product' ? 'catalog' : $type . 's')],
            ["@type" => "ListItem", "position" => 3, "name" => ($type === 'product' ? $item['name'] : $item['title'])]
        ]
    ];
    $breadcrumbsHtml = '<script type="application/ld+json">' . json_encode($breadcrumbs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
echo $schema;
echo $breadcrumbsHtml;
?>
<main class="py-12 px-4 max-w-4xl mx-auto">
    <?php if ($type === 'product'): ?>
        <nav class="text-sm text-[var(--text-muted)] mb-6" aria-label="Хлебные крошки">
            <a href="/" class="hover:text-[var(--accent)]">Главная</a> /
            <a href="/catalog" class="hover:text-[var(--accent)]">Каталог</a> /
            <span><?php echo htmlspecialchars($item['name']); ?></span>
        </nav>
        <span class="text-[var(--accent)] text-sm uppercase"><?php echo htmlspecialchars($item['latin'] ?? ''); ?></span>
        <h1 class="text-3xl md:text-4xl font-bold mt-2 mb-4"><?php echo htmlspecialchars($item['name']); ?></h1>
        <?php if (!empty($item['image'])): ?>
            <?php echo renderPicture($item['image'], $image_alt, 'w-full h-auto rounded-md mb-8', 'eager'); ?>
        <?php endif; ?>
        <div class="flex flex-wrap gap-4 items-center mb-8">
            <span class="text-3xl font-bold text-[var(--accent)]"><?php echo number_format($item['price'], 0, ',', ' '); ?> ₽</span>
            <span class="text-sm px-3 py-1 bg-[var(--bg-elevated)] rounded-full"><?php echo htmlspecialchars($item['category'] ?? ''); ?></span>
            <?php if (!empty($item['sku'])): ?><span class="text-sm text-[var(--text-muted)]">Арт. <?php echo htmlspecialchars($item['sku']); ?></span><?php endif; ?>
            <?php if ($ratingValue > 0): ?><span class="text-sm text-[var(--accent)]">⭐ <?php echo $ratingValue; ?> / 5 (<?php echo $reviewCount; ?> отзыва)</span><?php endif; ?>
        </div>
        <p class="text-[var(--text-secondary)] mb-8 text-lg"><?php echo nl2br(htmlspecialchars($item['desc'] ?? '')); ?></p>
        <?php echo $item['details'] ?? ''; ?>

        <!-- ============ ОТЗЫВЫ ============ -->
        <?php if ($reviewCount > 0): ?>
        <section class="mt-12 pt-8 border-t border-[var(--border)]">
            <h2 class="text-2xl font-bold mb-6">⭐ Отзывы покупателей (<?php echo $reviewCount; ?>)</h2>
            <div class="space-y-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-5">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-full bg-[var(--accent)] flex items-center justify-center text-white font-bold">
                                <?php echo htmlspecialchars(mb_substr($review['name'] ?? 'А', 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-semibold text-[var(--text-primary)]"><?php echo htmlspecialchars($review['name']); ?></div>
                                <div class="text-sm text-[var(--text-muted)]">
                                    <span class="text-[var(--accent)]"><?php echo str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']); ?></span>
                                    • <?php echo date('d.m.Y', strtotime($review['date'] ?? 'now')); ?>
                                </div>
                            </div>
                        </div>
                        <p class="text-[var(--text-secondary)] leading-relaxed"><?php echo nl2br(htmlspecialchars($review['text'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ============ ФОРМА ОТЗЫВА ============ -->
        <section class="mt-12 pt-8 border-t border-[var(--border)]" id="review-form-section">
            <h2 class="text-2xl font-bold mb-2">✍️ Оставить отзыв</h2>
            <p class="text-[var(--text-secondary)] text-sm mb-6">Все отзывы проходят модерацию. Ваш отзыв появится на сайте после проверки.</p>

            <form id="user-review-form" class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-6 space-y-5" autocomplete="off" novalidate>
                <div>
                    <label for="ur-name" class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Ваше имя *</label>
                    <input type="text" id="ur-name" name="name" required minlength="2" maxlength="50"
                           placeholder="Иван Петров"
                           class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border)] rounded-[var(--radius-sm)] text-[var(--text-primary)] focus:outline-none focus:border-[var(--accent)] transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Оценка *</label>
                    <div class="rating-stars" role="radiogroup" aria-label="Оценка">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="ur-star-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                            <label for="ur-star-<?php echo $i; ?>" title="<?php echo $i; ?> из 5">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div>
                    <label for="ur-text" class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Текст отзыва * <span class="text-[var(--text-muted)] font-normal">(от 10 до 2000 символов)</span></label>
                    <textarea id="ur-text" name="text" required minlength="10" maxlength="2000" rows="5"
                              placeholder="Расскажите о качестве грибов, доставке, упаковке..."
                              class="w-full px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border)] rounded-[var(--radius-sm)] text-[var(--text-primary)] focus:outline-none focus:border-[var(--accent)] transition-colors resize-vertical"></textarea>
                    <div class="text-xs text-[var(--text-muted)] mt-1"><span id="ur-char-count">0</span> / 2000</div>
                </div>

                <div>
                    <label for="ur-captcha" class="block text-sm font-medium text-[var(--text-secondary)] mb-1">Проверка: сколько будет <?php echo $captchaA; ?> + <?php echo $captchaB; ?>? *</label>
                    <input type="number" id="ur-captcha" name="captcha" required
                           class="w-full max-w-[120px] px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border)] rounded-[var(--radius-sm)] text-[var(--text-primary)] focus:outline-none focus:border-[var(--accent)] transition-colors">
                </div>

                <div class="honeypot-field" aria-hidden="true">
                    <label for="ur-website">Ваш сайт (не заполняйте, если вы человек)</label>
                    <input type="text" id="ur-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <input type="hidden" id="ur-captcha-expected" value="<?php echo $captchaExpected; ?>">
                <input type="hidden" id="ur-product" value="<?php echo htmlspecialchars($item['name']); ?>">

                <div id="ur-status" class="hidden text-sm p-3 rounded-[var(--radius-sm)]"></div>

                <div>
                    <button type="submit" class="btn-primary" id="ur-submit">
                        ✉️ Отправить отзыв
                    </button>
                </div>
            </form>
        </section>

        <div class="mt-8"><button onclick="history.back()" class="btn-outline">← Назад</button></div>
    <?php elseif ($type === 'page'): ?>
        <!-- 04.11.2026: вывод произвольной страницы из pages.json -->
        <nav class="text-sm text-[var(--text-muted)] mb-6" aria-label="Хлебные крошки">
            <a href="/" class="hover:text-[var(--accent)]">Главная</a> /
            <span><?php echo htmlspecialchars($item['title']); ?></span>
        </nav>
        <h1 class="text-3xl md:text-4xl font-bold mb-6 text-[var(--text-primary)]"><?php echo htmlspecialchars($item['title']); ?></h1>
        <?php if (!empty($item['image'])): ?>
            <?php echo renderPicture($item['image'], $item['title'], 'w-full h-auto rounded-md mb-8', 'eager'); ?>
        <?php endif; ?>
        <div class="text-[var(--text-secondary)] leading-relaxed"><?php echo $item['content'] ?? ''; ?></div>
        <div class="mt-8"><button onclick="history.back()" class="btn-outline">← Назад</button></div>
    <?php elseif ($type === 'blog'): ?>
        <nav class="text-sm text-[var(--text-muted)] mb-6" aria-label="Хлебные крошки">
            <a href="/" class="hover:text-[var(--accent)]">Главная</a> /
            <a href="/blog" class="hover:text-[var(--accent)]">Блог</a> /
            <span><?php echo htmlspecialchars($item['title']); ?></span>
        </nav>
        <h1 class="text-3xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h1>
        <p class="text-[var(--text-muted)] mb-8"><?php echo htmlspecialchars($item['date'] ?? ''); ?></p>
        <?php if (!empty($item['image'])): ?>
            <?php echo renderPicture($item['image'], $image_alt, 'w-full h-auto rounded-md mb-8', 'eager'); ?>
        <?php endif; ?>
        <div class="text-[var(--text-secondary)] leading-relaxed"><?php echo $item['content'] ?? ''; ?></div>
        <div class="mt-8"><button onclick="history.back()" class="btn-outline">← Назад</button></div>
    <?php elseif ($type === 'news'): ?>
        <nav class="text-sm text-[var(--text-muted)] mb-6" aria-label="Хлебные крошки">
            <a href="/" class="hover:text-[var(--accent)]">Главная</a> /
            <a href="/news" class="hover:text-[var(--accent)]">Новости</a> /
            <span><?php echo htmlspecialchars($item['title']); ?></span>
        </nav>
        <h1 class="text-3xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h1>
        <p class="text-[var(--text-muted)] mb-8"><?php echo htmlspecialchars($item['date'] ?? ''); ?></p>
        <div class="text-[var(--text-secondary)] leading-relaxed"><?php echo $item['content'] ?? ''; ?></div>
        <div class="mt-8"><button onclick="history.back()" class="btn-outline">← Назад</button></div>
    <?php elseif ($type === 'promotion'): ?>
        <nav class="text-sm text-[var(--text-muted)] mb-6" aria-label="Хлебные крошки">
            <a href="/" class="hover:text-[var(--accent)]">Главная</a> /
            <a href="/promotions" class="hover:text-[var(--accent)]">Акции</a> /
            <span><?php echo htmlspecialchars($item['title']); ?></span>
        </nav>
        <?php if (!empty($item['image'])): ?>
            <?php echo renderPicture($item['image'], $item['alt'] ?? $item['title'], 'w-full h-auto rounded-xl mb-8', 'eager'); ?>
        <?php endif; ?>
        <h1 class="text-3xl md:text-4xl font-bold mb-4">🎁 <?php echo htmlspecialchars($item['title']); ?></h1>
        <div class="flex flex-wrap gap-4 mb-6 text-sm text-[var(--text-muted)]">
            <?php if (!empty($item['start_date'])): ?><span>📅 Начало: <?php echo date('d.m.Y', strtotime($item['start_date'])); ?></span><?php endif; ?>
            <?php if (!empty($item['end_date'])): ?><span>🔚 Окончание: <?php echo date('d.m.Y', strtotime($item['end_date'])); ?></span><?php endif; ?>
        </div>
        <div class="text-[var(--text-secondary)] leading-relaxed"><?php echo $item['description'] ?? ''; ?></div>
        <div class="mt-8"><button onclick="history.back()" class="btn-outline">← Назад к акциям</button></div>
    <?php endif; ?>
</main>

<?php if ($type === 'product'): ?>
<script>
// Скрипт для формы отзыва (без изменений, оставлен как есть)
(function() {
    const form = document.getElementById('user-review-form');
    if (!form) return;

    const statusEl = document.getElementById('ur-status');
    const submitBtn = document.getElementById('ur-submit');
    const textEl = document.getElementById('ur-text');
    const charCount = document.getElementById('ur-char-count');

    if (textEl && charCount) {
        textEl.addEventListener('input', () => {
            charCount.textContent = textEl.value.length;
        });
    }

    function showStatus(msg, isError) {
        statusEl.textContent = msg;
        statusEl.className = 'text-sm p-3 rounded-[var(--radius-sm)] ' + (isError ? 'bg-red-900/30 text-red-300' : 'bg-green-900/30 text-green-300');
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = document.getElementById('ur-name').value.trim();
        const ratingInput = form.querySelector('input[name="rating"]:checked');
        const rating = ratingInput ? parseInt(ratingInput.value, 10) : 0;
        const text = textEl.value.trim();
        const captchaAnswer = document.getElementById('ur-captcha').value.trim();
        const captchaExpected = document.getElementById('ur-captcha-expected').value.trim();
        const product = document.getElementById('ur-product').value;
        const website = document.getElementById('ur-website').value;

        if (!name || name.length < 2) { showStatus('⚠️ Укажите имя (минимум 2 символа)', true); return; }
        if (!rating) { showStatus('⚠️ Поставьте оценку', true); return; }
        if (!text || text.length < 10) { showStatus('⚠️ Текст отзыва — минимум 10 символов', true); return; }
        if (!captchaAnswer) { showStatus('⚠️ Введите ответ на капчу', true); return; }

        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Отправка...';

        try {
            const res = await fetch('/api/reviews-submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    name, rating, text, product,
                    captcha_answer: captchaAnswer,
                    captcha_expected: captchaExpected,
                    website
                })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Ошибка');

            showStatus('✅ ' + (data.message || 'Спасибо! Отзыв отправлен на модерацию.'), false);
            form.reset();
            if (charCount) charCount.textContent = '0';
        } catch (err) {
            showStatus('❌ ' + err.message, true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>