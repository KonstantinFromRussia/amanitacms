<?php
/**
 * about.php — страница "О нас" (E-E-A-T).
 *
 * Содержит информацию о компании/проекте и микроразметку Organization.
 * (Переделано с персоналии "О Грибомане" на нейтральное "О нас".)
 */

require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

// SEO-переменные для этой страницы
// Было: 'О Грибомане — грибник с 50-летним опытом | ' . $siteName;
$page_title = 'О нас | ' . $siteName;
// Было: 'Дядя Костя Грибоман — эксперт по сбору мухоморов в Карельском перешейке...';
$page_description = 'Узнайте больше о нашем проекте: кто мы, как собираем и обрабатываем продукцию, какие принципы лежат в основе нашей работы.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Микроразметка Organization для E-E-A-T -->
<!-- Было: "@type": "Person" -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?php echo htmlspecialchars($siteName); ?>",
  "description": "Проект по сбору и обработке дикорастущих грибов в экологически чистых районах.",
  "url": "<?php echo SITE_URL; ?>/about.php",
  "logo": "<?php echo SITE_URL; ?>/uploads/griboman.webp",
  "knowsAbout": ["Сбор грибов", "Мухоморы", "Правильная сушка", "Экологичный сбор"]
}
</script>

<main class="py-12 px-4 max-w-4xl mx-auto">
    <!-- Хлебные крошки -->
    <nav class="text-sm text-[var(--text-muted)] mb-6" aria-label="Хлебные крошки">
        <a href="/" class="hover:text-[var(--accent)]">Главная</a> /
        <!-- Было: <span>О Грибомане</span> -->
        <span>О нас</span>
    </nav>

    <!-- Было: <h1 ...>О Грибомане</h1> -->
    <h1 class="text-3xl md:text-4xl font-black mb-6 text-[var(--text-primary)]">О нас</h1>

    <!-- Фото слева (обтекание текстом на десктопе) -->
    <div class="md:float-left md:mr-8 md:mb-4 mb-6 mx-auto md:mx-0" style="max-width: 320px;">
        <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl overflow-hidden">
            <!-- Было: alt="Дядя Костя Грибоман — сборщик мухоморов" -->
            <img src="/uploads/griboman.webp" alt="Наша команда и процесс сбора" class="w-full h-auto object-cover">
        </div>
        <!-- Было: <p ...>Дядя Костя (Грибоман)</p> -->
        <p class="text-center text-sm text-[var(--text-muted)] mt-2">Наш проект</p>
    </div>

    <!-- Текст (занимает всю ширину, обтекает фото) -->
    <div class="text-[var(--text-secondary)] leading-relaxed space-y-4">
        <!-- Было: <p class="text-xl ...">Меня зовут Константин...</p> -->
        <p class="text-xl font-bold text-[var(--text-primary)] mb-2">
            Мы — команда, для которой сбор и обработка грибов стали делом жизни.
        </p>

        <!-- Было: "Я собираю грибы более 50 лет. Начинал ещё в детстве..." -->
        <p>
            Наш опыт начинается в лесах Карельского перешейка под Санкт-Петербургом. За годы работы мы изучили каждую тропинку, знаем, где и когда растут лучшие грибы, и как их правильно обрабатывать.
        </p>

        <!-- Было: "Мухомор — это не просто гриб..." -->
        <p>
            Мухомор — это не просто гриб. Это древнее растение, которое требует глубокого понимания и уважения. Мы лично отбираем каждую шляпку, следим за правильной сушкой и гарантируем, что в упаковку попадёт только то, что мы сами готовы использовать.
        </p>

        <!-- Было: "Мой опыт — это не просто слова..." -->
        <p>
            Наш опыт — это не просто слова. Это десятки лет в лесу, тысячи собранных грибов и сотни довольных клиентов, которые возвращаются к нам снова и снова. Мы не перепродаём чужой товар — мы собираем его сами, вручную, в экологически чистых районах Ленинградской области.
        </p>

        <!-- Было: "Я верю, что природа должна давать..." -->
        <p>
            Мы верим, что природа должна давать нам ровно столько, сколько нужно, и не больше. Поэтому мы собираем грибы бережно, не вытаптывая грибницы, чтобы лес мог восстанавливаться.
        </p>

        <!-- Было: "Если вы хотите купить качественный мухомор..." -->
        <p>
            Если вы хотите купить качественный мухомор, выращенный и обработанный с заботой — вы попали по адресу. Мы гарантируем качество каждой упаковки. Если у вас есть вопросы, напишите нам в Telegram или позвоните — мы всегда на связи.
        </p>
    </div>

    <!-- Очистка обтекания (чтобы блок принципов был ниже фото) -->
    <div class="clear-both"></div>

    <!-- Блок с принципами -->
    <section class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-6 md:p-8 mt-8">
        <!-- Было: <h2 ...>Мои принципы</h2> -->
        <h2 class="text-2xl font-bold mb-4 text-[var(--text-primary)]">Наши принципы</h2>
        <ul class="space-y-3 text-[var(--text-secondary)]">
            <li class="flex items-start gap-3">
                <span class="text-[var(--accent)] text-xl">🌿</span>
                <!-- Было: <strong>Только природа.</strong> Сбор в экологически чистых районах... -->
                <span><strong>Только природа.</strong> Сбор в экологически чистых районах Карельского перешейка, без химии и обработки.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-[var(--accent)] text-xl">🍄</span>
                <!-- Было: <strong>Личный контроль.</strong> Я сам собираю и сортирую каждый гриб... -->
                <span><strong>Личный контроль.</strong> Мы сами собираем и сортируем каждый гриб. Никаких посредников.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-[var(--accent)] text-xl">📦</span>
                <!-- Было: <strong>Правильная сушка.</strong> Соблюдаю все рекомендации... -->
                <span><strong>Правильная сушка.</strong> Соблюдаем все рекомендации, чтобы сохранить полезные свойства и аромат.</span>
            </li>
        </ul>
    </section>

    <!-- Одна кнопка (в самом низу) -->
    <div class="mt-10 text-center">
        <a href="/catalog" class="btn-primary">🛍 Перейти в каталог</a>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
