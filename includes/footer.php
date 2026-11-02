<?php
$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';
$phone    = $settings['phone'] ?? '+7 (800) 555-19-87';
$email    = $settings['email'] ?? 'shop@example.ru';
$hours    = $settings['hours'] ?? 'Пн–Вс: 9:00–21:00';

// Соцсети: берём значения из настроек и нормализуем
$telegram = trim($settings['telegram'] ?? '');
$vk       = trim($settings['vk'] ?? '');
$youtube  = trim($settings['youtube'] ?? '');
$max      = trim($settings['max'] ?? '');

// Нормализация URL (полный URL или username)
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
$youtubeUrl = '';
if ($youtube !== '') {
    $youtubeUrl = preg_match('#^https?://#i', $youtube)
        ? $youtube
        : 'https://youtube.com/' . ltrim($youtube, '@');
}
$maxUrl = '';
if ($max !== '') {
    $maxUrl = preg_match('#^https?://#i', $max)
        ? $max
        : 'https://max.ru/' . ltrim($max, '@');
}

$footer_description = $settings['footer_description'] ?? 'Сушеные грибы премиум-качества. Доставка по России.';
$ageCheckEnabled = $settings['age_check_enabled'] ?? 'yes';
?>
<footer class="border-t border-[var(--border)] bg-[var(--bg-secondary)] mt-12">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-3">🍄 <?php echo htmlspecialchars($siteName); ?></h3>
                <p class="text-sm"><?php echo htmlspecialchars($footer_description); ?></p>
            </div>

            <div>
                <h4 class="font-semibold mb-3">Навигация</h4>
                <ul class="space-y-2 text-sm">
                    <li><a class="footer-link" href="/catalog">Каталог</a></li>
                    
                    <!-- НОВАЯ ССЫЛКА: О Грибомане -->
                    <li><a class="footer-link" href="/about.php">О Грибомане</a></li>
                    
                    <li><a class="footer-link" href="/blog">Блог</a></li>
                    <li><a class="footer-link" href="/news">Новости</a></li>
                    <li><a class="footer-link" href="/contacts">Контакты</a></li>
                    <li><a class="footer-link" href="/faq">FAQ</a></li>
                    <li><a class="footer-link" href="/promotions">Акции</a></li>
                    <li><a class="footer-link" href="/reviews">Отзывы</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold mb-3">Поддержка</h4>
                <ul class="space-y-2 text-sm">
                    <li>📧 <?php echo htmlspecialchars($email); ?></li>
                    <li>📞 <?php echo htmlspecialchars($phone); ?></li>
                    <li>🕐 <?php echo htmlspecialchars($hours); ?></li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold mb-3">Мы в соцсетях</h4>
                <div class="flex gap-3 items-center">
                    <?php if ($telegramUrl): ?>
                        <a href="<?php echo htmlspecialchars($telegramUrl); ?>" target="_blank" rel="noopener" class="text-[var(--text-secondary)] hover:text-[var(--accent)] transition-colors" aria-label="Telegram" title="Telegram">
                            <?php echo iconTelegram('24'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($vkUrl): ?>
                        <a href="<?php echo htmlspecialchars($vkUrl); ?>" target="_blank" rel="noopener" class="text-[var(--text-secondary)] hover:text-[var(--accent)] transition-colors" aria-label="VK" title="VK">
                            <?php echo iconVk('24'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($youtubeUrl): ?>
                        <a href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" rel="noopener" class="text-[var(--text-secondary)] hover:text-[var(--accent)] transition-colors" aria-label="YouTube" title="YouTube">
                            <?php echo iconYoutube('24'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($maxUrl): ?>
                        <a href="<?php echo htmlspecialchars($maxUrl); ?>" target="_blank" rel="noopener" class="text-[var(--text-secondary)] hover:text-[var(--accent)] transition-colors" aria-label="MAX" title="MAX">
                            <?php echo getMaxIcon('24'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="border-t mt-8 pt-6 text-center text-sm">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
                <span>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?></span>
                <span class="hidden sm:inline text-[var(--text-muted)]">·</span>
                <span class="text-[var(--text-muted)] inline-flex items-center gap-1">
                    👥 Нас посетило:
                    <strong class="text-[var(--accent)]"><?php echo number_format(getVisitorCount(), 0, ',', ' '); ?></strong>
                    <span>человек</span>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- ========== КОРЗИНА ========== -->
<div class="cart-overlay" id="cartOverlay"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="flex items-center justify-between p-4 border-b">
        <h2 class="text-lg font-bold">🛒 Корзина</h2>
        <button type="button" class="text-xl bg-transparent border-none cursor-pointer" onclick="window.toggleCart()">✕</button>
    </div>
    <div class="flex-1 overflow-y-auto p-4" id="cartItemsContainer">
        <p class="text-center mt-12">Ваша корзина пока пуста 🍄</p>
    </div>
    <div class="p-4 border-t init-hidden" id="cartFooter">
        <div class="flex justify-between mb-3">
            <span>Итого:</span>
            <span class="text-xl font-bold text-[var(--accent)]" id="cartTotal">0 ₽</span>
        </div>
        <button class="btn-primary w-full" onclick="window.checkout()">📦 Оформить заказ</button>
        <button class="text-sm mt-2 w-full" onclick="window.clearCart()">Очистить корзину</button>
    </div>
</div>

<!-- ========== МОДАЛЬНОЕ ОКНО ЗАКАЗА ========== -->
<div class="modal-overlay" id="order-modal-overlay" onclick="window.closeOrderModal()"></div>
<div class="modal" id="order-modal">
    <button class="modal__close" onclick="window.closeOrderModal()">✕</button>
    <div class="modal__inner">
        <h2>Оформление заказа</h2>
        <form id="order-form" onsubmit="event.preventDefault(); window.submitOrder();" class="order-form-stack">
            <input type="text" id="order-name" placeholder="Имя *" required>
            <input type="tel" id="order-phone" placeholder="Телефон *" required>
            <input type="email" id="order-email" placeholder="Email">
            <input type="text" id="order-address" placeholder="Адрес">
            <textarea id="order-comment" rows="2" placeholder="Комментарий"></textarea>
            <button type="submit" class="btn-primary">📦 Отправить</button>
        </form>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Наверх">⬆</button>

<script src="/js/script.js" defer></script>

<?php if ($ageCheckEnabled === 'yes'): ?>
<div id="ageModal" class="age-modal-overlay">
    <div class="age-modal">
        <div class="age-modal-content">
            <p style="font-size: 1.2rem; margin-bottom: 1rem; text-align: center;">🍄</p>
            <p><strong>ИНФОРМАЦИЯ НА САЙТЕ ПРЕДНАЗНАЧЕНА ДЛЯ ПОСЕТИТЕЛЕЙ СТАРШЕ 18 ЛЕТ.</strong></p>
            <p>ДЛЯ ДОСТУПА К НЕЙ, ПОЖАЛУЙСТА, ПОДТВЕРДИТЕ СВОЁ СОВЕРШЕННОЛЕТИЕ.</p>
            <p style="margin-bottom: 1.5rem;"><strong>ВАМ ИСПОЛНИЛОСЬ 18 ЛЕТ?</strong></p>
            <div class="age-modal-buttons">
                <button type="button" id="ageConfirmYes" class="btn-primary">✅ Да</button>
                <button type="button" id="ageConfirmNo" class="btn-outline">❌ Нет</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        var modal = document.getElementById('ageModal');
        if (!modal) return;

        var confirmed = localStorage.getItem('ageConfirmed');
        if (confirmed === 'yes') {
            modal.style.display = 'none';
            return;
        }

        document.body.style.overflow = 'hidden';

        var yesBtn = document.getElementById('ageConfirmYes');
        var noBtn = document.getElementById('ageConfirmNo');

        if (yesBtn) yesBtn.onclick = function() {
            localStorage.setItem('ageConfirmed', 'yes');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        };
        if (noBtn) noBtn.onclick = function() {
            modal.innerHTML = '<div class="age-modal"><div class="age-modal-content" style="text-align:center;"><p style="font-size:1.2rem;margin-bottom:1rem;">🚫</p><p><strong>Доступ запрещён</strong></p><p>Сайт предназначен для лиц старше 18 лет.</p></div></div>';
        };
    })();
</script>
<?php endif; ?>

<script>
    (function() {
        var saved = localStorage.getItem('amanita_theme');
        if (saved && saved !== document.documentElement.getAttribute('data-theme')) {
            document.documentElement.setAttribute('data-theme', saved);
        }
    })();
</script>
</body>
</html>