<?php
require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

$page_title = 'Контакты — ' . $siteName;
$page_description = 'Свяжитесь с администрацией: телефон, email, адрес.';

$phone    = $settings['phone']    ?? '+7 (800) 555-19-87';
$email    = $settings['email']    ?? 'hello@site.ru';
$address  = $settings['address']  ?? 'г. Москва, ул. Лесная, д. 15';
$hours    = $settings['hours']    ?? 'Пн–Вс: 9:00–21:00';
$telegram = trim($settings['telegram'] ?? '');
$vk       = trim($settings['vk']       ?? '');
$max      = trim($settings['max']      ?? '');

// Нормализация URL
$telegramUrl = '';
if ($telegram !== '') {
    $telegramUrl = preg_match('#^https?://#i', $telegram)
        ? $telegram
        : 'https://t.me/' . ltrim($telegram, '@');
}
$vkUrl = '';
if ($vk !== '') {
    $vkUrl = preg_match('#^https?://#i', $vk)
        ? $vk
        : 'https://vk.com/' . ltrim($vk, '@');
}
$maxUrl = '';
if ($max !== '') {
    $maxUrl = preg_match('#^https?://#i', $max)
        ? $max
        : 'https://max.ru/' . ltrim($max, '@');
}

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>
<main class="py-12 px-4 max-w-4xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-black mb-2 text-[var(--text-primary)] text-center">📬 Контакты</h1>
    <p class="text-[var(--text-secondary)] mb-8 text-center">Свяжитесь с нами — мы всегда рады помочь!</p>

    <!-- Быстрые способы связи -->
    <?php if ($telegramUrl || $maxUrl || $vkUrl || $email || $phone): ?>
    <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-[var(--radius-lg)] p-6 md:p-8 mb-8">
        <h2 class="text-xl font-bold mb-5 text-[var(--text-primary)] text-center">⚡ Быстрая связь</h2>
<br>
        <div class="flex flex-wrap gap-3 justify-center">
            <?php if ($telegramUrl): ?>
                <a href="<?php echo htmlspecialchars($telegramUrl); ?>" target="_blank" rel="noopener noreferrer"
                   class="btn-primary inline-flex items-center gap-2"
                   style="background:#229ED9; color:#fff;">
                    <?php echo iconTelegram('20'); ?>
                    <span>TG</span>
                </a>
            <?php endif; ?>

            <?php if ($maxUrl): ?>
                <a href="<?php echo htmlspecialchars($maxUrl); ?>" target="_blank" rel="noopener noreferrer"
                   class="btn-primary inline-flex items-center gap-2"
                   style="background:linear-gradient(135deg,#3b5bdb,#7048e8); color:#fff;">
                    <?php echo getMaxIcon('20'); ?>
                    <span>MAX</span>
                </a>
            <?php endif; ?>

            <?php if ($vkUrl): ?>
                <a href="<?php echo htmlspecialchars($vkUrl); ?>" target="_blank" rel="noopener noreferrer"
                   class="btn-primary inline-flex items-center gap-2"
                   style="background:#0077FF; color:#fff;">
                    <?php echo iconVk('20'); ?>
                    <span>VK</span>
                </a>
            <?php endif; ?>

            <?php if ($email): ?>
                <a href="mailto:<?php echo htmlspecialchars($email); ?>"
                   class="btn-outline inline-flex items-center gap-2">
                    <?php echo iconEnvelope('18'); ?>
                    <span>Написать</span>
                </a>
            <?php endif; ?>

            <?php if ($phone): ?>
                <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^\d+]/', '', $phone)); ?>"
                   class="btn-outline inline-flex items-center gap-2">
                    <?php echo iconPhone('18'); ?>
                    <span>Позвонить</span>
                </a>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

    <!-- Контактные данные -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-10">
        <div class="text-center p-5 bg-[var(--bg-card)] border border-[var(--border)] rounded-[var(--radius-md)]">
            <div class="mb-2 text-[var(--accent)]"><?php echo iconEnvelope('32'); ?></div>
            <h3 class="font-semibold text-[var(--text-primary)]">Email</h3>
            <p class="text-[var(--text-secondary)] text-sm break-all"><?php echo htmlspecialchars($email); ?></p>
        </div>
        <div class="text-center p-5 bg-[var(--bg-card)] border border-[var(--border)] rounded-[var(--radius-md)]">
            <div class="mb-2 text-[var(--accent)]"><?php echo iconPhone('32'); ?></div>
            <h3 class="font-semibold text-[var(--text-primary)]">Телефон</h3>
            <p class="text-[var(--text-secondary)] text-sm"><?php echo htmlspecialchars($phone); ?></p>
        </div>
        <div class="text-center p-5 bg-[var(--bg-card)] border border-[var(--border)] rounded-[var(--radius-md)]">
            <div class="mb-2 text-[var(--accent)]"><?php echo iconMapMarker('32'); ?></div>
            <h3 class="font-semibold text-[var(--text-primary)]">Адрес</h3>
            <p class="text-[var(--text-secondary)] text-sm"><?php echo htmlspecialchars($address); ?></p>
        </div>
        <div class="text-center p-5 bg-[var(--bg-card)] border border-[var(--border)] rounded-[var(--radius-md)]">
            <div class="mb-2 text-[var(--accent)]"><?php echo iconClock('32'); ?></div>
            <h3 class="font-semibold text-[var(--text-primary)]">Время работы</h3>
            <p class="text-[var(--text-secondary)] text-sm"><?php echo htmlspecialchars($hours); ?></p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>