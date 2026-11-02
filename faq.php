<?php
require_once __DIR__ . '/includes/config.php';

$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$siteName = $settings['site_name'] ?? 'Магазин';

$page_title = $siteName . ' — Часто задаваемые вопросы';
$page_description = 'Ответы на популярные вопросы о грибах, доставке, оплате и микродозинге.';

$faq = json_decode(file_get_contents(__DIR__ . '/data/faq.json'), true);
if (!is_array($faq)) $faq = [];
usort($faq, function($a, $b) { return ($a['order'] ?? 0) - ($b['order'] ?? 0); });

// Генерация Schema.org для FAQPage (сохраняем в переменную, выведем ПОСЛЕ head)
$faqSchema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => []
];
foreach ($faq as $item) {
    $faqSchema["mainEntity"][] = [
        "@type" => "Question",
        "name" => $item['question'],
        "acceptedAnswer" => [
            "@type" => "Answer",
            "text" => strip_tags($item['answer'])
        ]
    ];
}
$schemaHtml = '<script type="application/ld+json">'
    . json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    . '</script>';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
echo $schemaHtml; // ← теперь выводим ВНУТРИ <body>, а не до DOCTYPE
?>
<main class="py-12 px-4 max-w-4xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-black mb-2 text-[var(--text-primary)]">❓ Часто задаваемые вопросы</h1>
    <p class="text-[var(--text-secondary)] mb-8">Ответы на ваши вопросы о продукции, доставке и микродозинге.</p>

    <?php if (empty($faq)): ?>
        <p class="text-center text-[var(--text-muted)] py-12">Вопросов пока нет. Задайте их через форму обратной связи!</p>
    <?php else: ?>
        <div class="space-y-4" id="faq-accordion">
            <?php foreach ($faq as $item): ?>
            <div class="faq-item border border-[var(--border)] rounded-lg overflow-hidden">
                <button type="button" class="faq-question w-full text-left px-6 py-4 bg-[var(--bg-card)] hover:bg-[var(--bg-card-hover)] transition-colors flex justify-between items-center font-semibold text-[var(--text-primary)]">
                    <?php echo htmlspecialchars($item['question']); ?>
                    <span class="faq-icon text-xl">+</span>
                </button>
                <div class="faq-answer hidden px-6 py-4 bg-[var(--bg-secondary)] text-[var(--text-secondary)] border-t border-[var(--border)]">
                    <?php echo $item['answer']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
    document.querySelectorAll('.faq-question').forEach(button => {
        button.addEventListener('click', () => {
            const answer = button.nextElementSibling;
            const icon = button.querySelector('.faq-icon');
            answer.classList.toggle('hidden');
            icon.textContent = answer.classList.contains('hidden') ? '+' : '−';
        });
    });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>