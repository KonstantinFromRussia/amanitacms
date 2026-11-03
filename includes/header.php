<?php
$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Site Name';
$logoUrl = !empty($settings['logo_url']) ? $settings['logo_url'] : null;

// Определяем активный пункт для aria-current
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = function($path) use ($currentPath) {
    if ($path === '/' && $currentPath === '/') return true;
    if ($path !== '/' && strpos($currentPath, $path) === 0) return true;
    return false;
};

// Проверяем, авторизован ли админ (config.php уже запустил сессию)
$isAdmin = !empty($_SESSION['logged_in']);

// --- Логотип: определяем WebP-версию и размеры ---
$logoWebpUrl = '';
$logoWidth   = 0;
$logoHeight  = 0;

if ($logoUrl) {
    // WebP-версия рядом с оригиналом
    $webpUrl = preg_replace('/\.(jpe?g|png)$/i', '.webp', $logoUrl);
    if ($webpUrl !== $logoUrl) {
        $webpFsPath = dirname(__DIR__) . $webpUrl;
        if (file_exists($webpFsPath)) {
            $logoWebpUrl = $webpUrl;
        }
    }

    // Пытаемся получить размеры логотипа из файла
    $origFsPath = dirname(__DIR__) . $logoUrl;
    if (file_exists($origFsPath)) {
        $imgInfo = @getimagesize($origFsPath);
        if ($imgInfo !== false) {
            $logoWidth  = (int)$imgInfo[0];
            $logoHeight = (int)$imgInfo[1];
        }
    }
    // Если не удалось — используем пропорции по умолчанию (для 1200×234)
    if ($logoWidth === 0 || $logoHeight === 0) {
        $logoWidth  = 1200;
        $logoHeight = 234;
    }
}
?>
<header class="site-header" id="siteHeader">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="logo-container text-center mb-3">
            <a href="/" class="inline-flex items-center justify-center gap-2 text-xl font-bold text-[var(--text-primary)] no-underline" aria-label="<?php echo htmlspecialchars($siteName); ?> — на главную">
                <?php if ($logoUrl): ?>
                    <?php if ($logoWebpUrl): ?>
                        <picture>
                            <source srcset="<?php echo htmlspecialchars($logoWebpUrl); ?>" type="image/webp">
                            <img src="<?php echo htmlspecialchars($logoUrl); ?>"
                                 alt="<?php echo htmlspecialchars($siteName); ?>"
                                 class="logo-image h-auto max-h-12 w-auto"
                                 width="<?php echo $logoWidth; ?>"
                                 height="<?php echo $logoHeight; ?>"
                                 loading="eager"
                                 fetchpriority="high"
                                 decoding="async">
                        </picture>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>"
                             alt="<?php echo htmlspecialchars($siteName); ?>"
                             class="logo-image h-auto max-h-12 w-auto"
                             width="<?php echo $logoWidth; ?>"
                             height="<?php echo $logoHeight; ?>"
                             loading="eager"
                             fetchpriority="high"
                             decoding="async">
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-2xl" aria-hidden="true">🍄</span>
                    <span><?php echo htmlspecialchars($siteName); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="desktop-nav hidden md:flex items-center gap-6 flex-1 justify-center" aria-label="Основная навигация">
                <a class="nav-link" href="/" <?php echo $isActive('/') ? 'aria-current="page"' : ''; ?>>Главная</a>
                <a class="nav-link" href="/catalog" <?php echo $isActive('/catalog') ? 'aria-current="page"' : ''; ?>>Каталог</a>
                
                <!-- НОВАЯ ССЫЛКА: О Нас -->
                <a class="nav-link" href="/about.php" <?php echo $isActive('/about.php') ? 'aria-current="page"' : ''; ?>>О Нас</a>
                
                <a class="nav-link" href="/blog" <?php echo $isActive('/blog') ? 'aria-current="page"' : ''; ?>>Блог</a>
                <a class="nav-link" href="/news" <?php echo $isActive('/news') ? 'aria-current="page"' : ''; ?>>Новости</a>
                <a class="nav-link" href="/contacts" <?php echo $isActive('/contacts') ? 'aria-current="page"' : ''; ?>>Контакты</a>
                <a class="nav-link" href="/faq" <?php echo $isActive('/faq') ? 'aria-current="page"' : ''; ?>>FAQ</a>
                <a class="nav-link" href="/promotions" <?php echo $isActive('/promotions') ? 'aria-current="page"' : ''; ?>>Акции</a>
                <a class="nav-link" href="/reviews" <?php echo $isActive('/reviews') ? 'aria-current="page"' : ''; ?>>Отзывы</a>
            </nav>

            <div class="flex items-center gap-3">
                <button type="button" class="cart-btn relative p-2 rounded-full hover:bg-muted transition" onclick="window.toggleCart()" aria-label="Корзина">
                    🛒<span id="cartBadge" class="absolute -top-1 -right-1 bg-primary text-primary-foreground text-xs rounded-full w-5 h-5 flex items-center justify-center cart-badge-init">0</span>
                </button>
                <button type="button" id="theme-toggle" class="p-2 rounded-full transition cursor-pointer" onclick="window.toggleTheme()" aria-label="Переключить тему">🌙</button>
                <?php if ($isAdmin): ?>
                    <a href="/admin.php" class="p-2 rounded-full hover:bg-muted transition" title="Админ-панель" aria-label="Админ-панель">⚙️</a>
                <?php endif; ?>
                <button type="button" class="mobile-hamburger md:hidden text-2xl bg-transparent border-none cursor-pointer text-[var(--text-primary)] p-2" onclick="window.toggleMobileMenu()" aria-label="Открыть меню">☰</button>
            </div>
        </div>
    </div>
</header>

<!-- ============================================================
     МОБИЛЬНОЕ МЕНЮ И ОВЕРЛЕЙ — ВНЕ <header>!
     ============================================================ -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="window.toggleMobileMenu()"></div>
<div class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Мобильное меню" aria-modal="true">
    <div class="flex items-center justify-between mb-4">
        <span class="text-lg font-bold">🍄 <?php echo htmlspecialchars($siteName); ?></span>
        <button type="button" class="text-2xl bg-transparent border-none cursor-pointer text-[var(--text-primary)]" onclick="window.toggleMobileMenu()" aria-label="Закрыть меню">✕</button>
    </div>
    <a class="nav-link text-lg" href="/">Главная</a>
    <a class="nav-link text-lg" href="/catalog">Каталог</a>
    
    <!-- НОВАЯ ССЫЛКА: О Грибомане (мобильная версия) -->
    <a class="nav-link text-lg" href="/about.php">О Грибомане</a>
    
    <a class="nav-link text-lg" href="/blog">Блог</a>
    <a class="nav-link text-lg" href="/news">Новости</a>
    <a class="nav-link text-lg" href="/contacts">Контакты</a>
    <a class="nav-link text-lg" href="/faq">FAQ</a>
    <a class="nav-link text-lg" href="/promotions">Акции</a>
    <a class="nav-link text-lg" href="/reviews">Отзывы</a>
    <form action="/search" method="get" class="mt-4">
        <input type="text" name="q" placeholder="Поиск..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="w-full px-3 py-2 rounded-full bg-[var(--bg-secondary)] border border-[var(--border)] text-sm text-[var(--text-primary)]">
        <button type="submit" class="btn-primary w-full mt-2">🔍 Найти</button>
    </form>
    <button type="button" class="btn-outline mt-4 w-full" onclick="window.toggleCart()">🛒 Корзина <span id="mobileCartCount"></span></button>
</div>
