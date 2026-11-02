<?php
/**
 * robots.php — генерация robots.txt для поисковых систем.
 *
 * Закрывает от индексации:
 *   - /admin/ — панель управления (папка)
 *   - /admin.php — панель управления (файл)
 *   - /admin — панель управления (без расширения)
 *   - /api/ — API-запросы (не для роботов)
 *   - /data/ — JSON-файлы с данными (конфиденциально)
 *   - /auth.php — страница входа (не должна быть в поиске)
 *   - /logout.php — выход (не нужен в поиске)
 *   - /backup.php — резервное копирование (только для админа)
 *
 * Разрешает AI-краулеры (GEO-2026) для индексации контента,
 * но с теми же запретами на служебные разделы.
 */

require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Читаем настройки, где хранится базовый robots.txt
$settings = json_decode(file_get_contents(__DIR__ . '/data/settings.json'), true);
$robotsContent = $settings['robots'] ?? "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /data/\nSitemap: " . SITE_URL . "/sitemap.xml";

// Заменяем возможный старый домен в Sitemap (если сайт переехал)
$robotsContent = preg_replace('/Sitemap: https?:\/\/[^\/]+/i', 'Sitemap: ' . SITE_URL, $robotsContent);

// ============================================================
// ДОБАВЛЯЕМ ЗАЩИТУ ДЛЯ СЛУЖЕБНЫХ СТРАНИЦ
// ============================================================
// Эти страницы не должны попадать в индекс ни при каких условиях.
// Даже если в settings.json их нет — добавляем принудительно.
$protectedPages = [
    'Disallow: /auth.php',
    'Disallow: /logout.php',
    'Disallow: /backup.php',
    'Disallow: /admin.php',   // Сам файл админки
    'Disallow: /admin',       // Админка без расширения (ЧПУ)
];

foreach ($protectedPages as $rule) {
    // Проверяем, есть ли уже такое правило (чтобы не дублировать)
    if (stripos($robotsContent, $rule) === false) {
        // Вставляем после "Disallow: /data/" — там, где начинается блок запретов
        $robotsContent = str_replace(
            "Disallow: /data/",
            "Disallow: /data/\n" . $rule,
            $robotsContent
        );
    }
}

// ============================================================
// AI-КРАУЛЕРЫ ДЛЯ GEO-2026
// ============================================================
// Разрешаем AI-ботам индексировать контент, но запрещаем служебные разделы.
// Это нужно, чтобы сайт упоминался в ответах ChatGPT, Perplexity и т.д.
$aiCrawlers = [
    'GPTBot',           // OpenAI / ChatGPT
    'ChatGPT-User',     // ChatGPT при кликах пользователей
    'OAI-SearchBot',    // OpenAI Search
    'PerplexityBot',    // Perplexity AI
    'Google-Extended',  // Google AI (Gemini, AI Overviews)
    'ClaudeBot',        // Anthropic Claude
    'anthropic-ai',     // Anthropic
    'Applebot-Extended',// Apple Intelligence
    'CCBot',            // Common Crawl (используется многими AI)
    'cohere-ai',        // Cohere
    'Bytespider',       // ByteDance (Doubao)
    'Amazonbot',        // Amazon
];

$aiBlock = '';
foreach ($aiCrawlers as $bot) {
    // Добавляем только если бот ещё не упомянут в robots.txt
    if (stripos($robotsContent, 'User-agent: ' . $bot) === false) {
        $aiBlock .= "\n# AI-краулер (GEO-2026)\n";
        $aiBlock .= "User-agent: " . $bot . "\n";
        $aiBlock .= "Allow: /\n";
        $aiBlock .= "Disallow: /admin/\n";
        $aiBlock .= "Disallow: /admin.php\n";
        $aiBlock .= "Disallow: /admin\n";
        $aiBlock .= "Disallow: /api/\n";
        $aiBlock .= "Disallow: /data/\n";
        $aiBlock .= "Disallow: /auth.php\n";
        $aiBlock .= "Disallow: /logout.php\n";
        $aiBlock .= "Disallow: /backup.php\n";
        $aiBlock .= "\n";
    }
}

if ($aiBlock !== '') {
    // Вставляем перед Sitemap, чтобы Sitemap был последней строкой
    $robotsContent = preg_replace('/(Sitemap:)/', $aiBlock . '$1', $robotsContent, 1);
}

echo $robotsContent;