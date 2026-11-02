<?php
/**
 * about.php — страница "О Грибомане" (E-E-A-T).
 *
 * Содержит информацию об эксперте, его опыте и микроразметку Person.
 */

require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

// SEO-переменные для этой страницы
$page_title = 'О Грибомане — грибник с 50-летним опытом | ' . $siteName;
$page_description = 'Дядя Костя Грибоман — эксперт по сбору мухоморов в Карельском перешейке. 50 лет опыта, ручной сбор, бережная сушка. Узнайте больше о том, кто стоит за продукцией.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Микроразметка Person для E-E-A-T -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Константин (дядя Костя Грибоман)",
  "jobTitle": "Грибник, эксперт по сбору мухоморов",
  "description": "Грибник с 50-летним опытом сбора мухоморов в Карельском перешейке. Основатель магазина Muhomor.spb.ru.",
  "url": "<?php echo SITE_URL; ?>/about.php",
  "image": "<?php echo SITE_URL; ?>/uploads/griboman.webp",
  "worksFor": {
    "@type": "Organization",
    "name": "<?php echo htmlspecialchars($siteName); ?>",
    "url": "<?php echo SITE_URL; ?>"
  },
  "knowsAbout": ["Сбор грибов", "Мухоморы", "Микродозинг", "Карельский перешеек"]
}
</script>

<main class="py-12 px-4 max-w-4xl mx-auto">
    <!-- Хлебные крошки -->
    <nav class="text-sm text-[var(--text-muted)] mb-6" aria-label="Хлебные крошки">
        <a href="/" class="hover:text-[var(--accent)]">Главная</a> /
        <span>О Грибомане</span>
    </nav>

    <h1 class="text-3xl md:text-4xl font-black mb-6 text-[var(--text-primary)]">О Грибомане</h1>

    <!-- Фото слева (обтекание текстом на десктопе) -->
    <div class="md:float-left md:mr-8 md:mb-4 mb-6 mx-auto md:mx-0" style="max-width: 320px;">
        <div class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl overflow-hidden">
            <img src="/uploads/griboman.webp" alt="Дядя Костя Грибоман — сборщик мухоморов" class="w-full h-auto object-cover">
        </div>
        <p class="text-center text-sm text-[var(--text-muted)] mt-2">Дядя Костя (Грибоман)</p>
    </div>

    <!-- Текст (занимает всю ширину, обтекает фото) -->
    <div class="text-[var(--text-secondary)] leading-relaxed space-y-4">
        <p class="text-xl font-bold text-[var(--text-primary)] mb-2">
            Меня зовут Константин. Для друзей и покупателей — просто дядя Костя, или Грибоман.
        </p>
        
        <p>
            Я собираю грибы более 50 лет. Начинал ещё в детстве, в лесах Карельского перешейка под Санкт-Петербургом, и с тех пор это стало делом моей жизни. За эти годы я изучил каждую тропинку, знаю, где и когда растут лучшие грибы, и как их правильно обрабатывать.
        </p>

        <p>
            Мухомор — это не просто гриб. Это древнее растение, которое требует глубокого понимания и уважения. Я лично отбираю каждую шляпку, слежу за правильной сушкой и гарантирую, что в упаковку попадёт только то, что я сам готов использовать.
        </p>

        <p>
            Мой опыт — это не просто слова. Это десятки лет в лесу, тысячи собранных грибов и сотни довольных клиентов, которые возвращаются ко мне снова и снова. Я не перепродаю чужой товар — я собираю его сам, вручную, в экологически чистых районах Ленинградской области.
        </p>

        <p>
            Я верю, что природа должна давать нам ровно столько, сколько нужно, и не больше. Поэтому мы собираем грибы бережно, не вытаптывая грибницы, чтобы лес мог восстанавливаться.
        </p>

        <p>
            Если вы хотите купить качественный мухомор, выращенный и обработанный с заботой — вы попали по адресу. Я гарантирую качество каждой упаковки. Если у вас есть вопросы, напишите мне в Telegram или позвоните — я всегда на связи.
        </p>
    </div>

    <!-- Очистка обтекания (чтобы блок принципов был ниже фото) -->
    <div class="clear-both"></div>

    <!-- Блок с принципами -->
    <section class="bg-[var(--bg-card)] border border-[var(--border)] rounded-xl p-6 md:p-8 mt-8">
        <h2 class="text-2xl font-bold mb-4 text-[var(--text-primary)]">Мои принципы</h2>
        <ul class="space-y-3 text-[var(--text-secondary)]">
            <li class="flex items-start gap-3">
                <span class="text-[var(--accent)] text-xl">🌿</span>
                <span><strong>Только природа.</strong> Сбор в экологически чистых районах Карельского перешейка, без химии и обработки.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-[var(--accent)] text-xl">🍄</span>
                <span><strong>Личный контроль.</strong> Я сам собираю и сортирую каждый гриб. Никаких посредников.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="text-[var(--accent)] text-xl">📦</span>
                <span><strong>Правильная сушка.</strong> Соблюдаю все рекомендации, чтобы сохранить полезные свойства и аромат.</span>
            </li>
        </ul>
    </section>

    <!-- Одна кнопка (в самом низу) -->
    <div class="mt-10 text-center">
        <a href="/catalog" class="btn-primary">🛍 Перейти в каталог</a>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>