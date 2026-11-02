<?php
/**
 * 404.php — страница "Страница не найдена".
 *
 * Дружелюбная заглушка с навигацией и поиском, чтобы удержать пользователя.
 */

require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

// Устанавливаем код ответа 404
http_response_code(404);

// SEO-переменные для этой страницы
$page_title = 'Страница не найдена — ' . $siteName;
$page_description = 'К сожалению, запрашиваемая страница не найдена. Вернитесь на главную или воспользуйтесь поиском.';

// Отключаем индексацию этой страницы
$robotsMeta = 'noindex, nofollow';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>
<main class="py-16 px-4 max-w-3xl mx-auto text-center">
    <!-- Эмодзи / Иконка -->
    <div class="text-6xl md:text-8xl mb-6">🍄</div>

    <!-- Заголовок -->
    <h1 class="text-3xl md:text-4xl font-black mb-4 text-[var(--text-primary)]">
        Ой-ой! Кажется, мы потеряли эту страничку
    </h1>

    <!-- Объяснение -->
    <p class="text-[var(--text-secondary)] text-lg mb-8 max-w-xl mx-auto">
        Возможно, она была удалена, перемещена или вы ошиблись в адресе. Не переживайте — вот несколько способов найти то, что вам нужно.
    </p>

    <!-- Кнопки навигации -->
    <div class="flex flex-wrap gap-3 justify-center mb-10">
        <a href="/" class="btn-primary">🏠 На главную</a>
        <a href="/catalog" class="btn-outline">🛍 В каталог</a>
        <a href="/contacts" class="btn-outline">📬 Связаться с нами</a>
    </div>

    <!-- Поиск по сайту -->
    <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-6 mb-10">
        <h2 class="text-lg font-semibold mb-3 text-[var(--text-primary)]">🔍 Поиск по сайту</h2>
        <form action="/search" method="get" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="q" placeholder="Например, мухомор красный..." class="flex-1 px-4 py-3 bg-[var(--bg-secondary)] border border-[var(--border)] rounded-[var(--radius-sm)] text-[var(--text-primary)] focus:outline-none focus:border-[var(--accent)]">
            <button type="submit" class="btn-primary">Найти</button>
        </form>
    </div>

    <!-- Полезные ссылки -->
    <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-6">
        <h2 class="text-lg font-semibold mb-3 text-[var(--text-primary)]">📌 Полезные разделы</h2>
        <ul class="grid grid-cols-2 gap-2 text-sm text-[var(--text-secondary)]">
            <li><a href="/catalog" class="hover:text-[var(--accent)]">Каталог</a></li>
            <li><a href="/blog" class="hover:text-[var(--accent)]">Блог</a></li>
            <li><a href="/news" class="hover:text-[var(--accent)]">Новости</a></li>
            <li><a href="/promotions" class="hover:text-[var(--accent)]">Акции</a></li>
            <li><a href="/reviews" class="hover:text-[var(--accent)]">Отзывы</a></li>
            <li><a href="/faq" class="hover:text-[var(--accent)]">FAQ</a></li>
            <li><a href="/about.php" class="hover:text-[var(--accent)]">О Грибомане</a></li>
            <li><a href="/contacts" class="hover:text-[var(--accent)]">Контакты</a></li>
        </ul>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>