<?php
require_once __DIR__ . '/config.php';
$settings = json_decode(file_get_contents(__DIR__ . '/../data/settings.json'), true);

// Короткое название — для логотипа, меню, футера (если где-то используется $siteName)
$siteName        = $settings['site_name'] ?? 'Магазин';

// Длинный SEO-заголовок — ТОЛЬКО для тега <title>
$siteSeoTitle    = $settings['site_seo_title'] ?? $siteName;

$siteDescription = $settings['site_description'] ?? 'Интернет-магазин';
$siteKeywords    = $settings['site_keywords'] ?? '';
$faviconUrl      = $settings['favicon_url'] ?? '/favicon/favicon.ico';
$gaId            = $settings['ga_id'] ?? '';
$ymId            = $settings['ym_id'] ?? '';
$gscTag          = $settings['gsc_tag'] ?? '';
$ywmTag          = $settings['ywm_tag'] ?? '';
$currentTheme    = $settings['theme'] ?? 'dark';

// --- Значения по умолчанию, могут быть переопределены вызывающим файлом ---
$page_title       = $page_title ?? $siteSeoTitle;
$page_description = $page_description ?? $siteDescription;
$page_image       = $page_image ?? '';
$og_type          = $og_type ?? 'website';

// --- Canonical: чистим мусорные query-параметры ---
$allowedCanonicalParams = ['page', 'category', 'sort'];
$canonicalParams = [];
foreach ($allowedCanonicalParams as $p) {
    if (isset($_GET[$p]) && $_GET[$p] !== '') {
        $canonicalParams[$p] = $_GET[$p];
    }
}
$canonicalPath = strtok($_SERVER['REQUEST_URI'], '?');
$canonicalUrl = SITE_URL . $canonicalPath;
if (!empty($canonicalParams)) {
    $canonicalUrl .= '?' . http_build_query($canonicalParams);
}
$canonicalSafe = htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8');
$page_url      = $canonicalSafe;

// --- Robots: noindex для страниц с фильтрами ---
$robotsMeta = 'index, follow';
$filterKeys = ['search', 'sort', 'min_price', 'max_price'];
$hasFilter = false;
foreach ($filterKeys as $k) {
    if (!empty($_GET[$k])) { $hasFilter = true; break; }
}
if ($hasFilter) {
    $robotsMeta = 'noindex, follow';
}

// --- Schema.org: WebSite ---
$schemaSite = [
    "@context" => "https://schema.org",
    "@type" => "WebSite",
    "name" => $siteName,
    "url" => SITE_URL,
    "description" => $siteDescription,
    "potentialAction" => [
        "@type" => "SearchAction",
        "target" => SITE_URL . "/catalog?search={search_term_string}",
        "query-input" => "required name=search_term_string"
    ]
];

// --- Schema.org: OnlineStore ---
$schemaStore = [
    "@context" => "https://schema.org",
    "@type" => "OnlineStore",
    "name" => $siteName,
    "url" => SITE_URL,
    "hasMerchantReturnPolicy" => [
        "@type" => "MerchantReturnPolicy",
        "applicableCountry" => "RU",
        "returnPolicyCategory" => "https://schema.org/MerchantReturnFiniteReturnWindow",
        "merchantReturnDays" => 14,
        "returnMethod" => "https://schema.org/ReturnByMail",
        "returnFees" => "https://schema.org/FreeReturn"
    ],
    "shippingDetails" => [
        "@type" => "OfferShippingDetails",
        "shippingRate" => [
            "@type" => "MonetaryAmount",
            "value" => 300,
            "currency" => "RUB"
        ],
        "shippingDestination" => [
            "@type" => "DefinedRegion",
            "addressCountry" => "RU"
        ],
        "deliveryTime" => [
            "@type" => "ShippingDeliveryTime",
            "handlingTime" => [
                "@type" => "QuantitativeValue",
                "minValue" => 1,
                "maxValue" => 2,
                "unitCode" => "DAY"
            ],
            "transitTime" => [
                "@type" => "QuantitativeValue",
                "minValue" => 2,
                "maxValue" => 5,
                "unitCode" => "DAY"
            ]
        ]
    ]
];

// --- Читаем themes.css для инлайна ---
$themesCssPath = __DIR__ . '/../css/themes.css';
$themesCss = file_exists($themesCssPath) ? file_get_contents($themesCssPath) : '';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="<?php echo htmlspecialchars($currentTheme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($siteKeywords); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($robotsMeta); ?>">
    <link rel="canonical" href="<?php echo $canonicalSafe; ?>">

    <!-- Open Graph -->
    <meta property="og:locale" content="ru_RU">
    <meta property="og:type" content="<?php echo htmlspecialchars($og_type); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:url" content="<?php echo $canonicalSafe; ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">
    <?php if (!empty($page_image)): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($page_image); ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="<?php echo htmlspecialchars($page_title); ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php if (!empty($page_image)): ?>
        <meta name="twitter:image" content="<?php echo htmlspecialchars($page_image); ?>">
    <?php endif; ?>

    <?php if ($gscTag): ?><meta name="google-site-verification" content="<?php echo htmlspecialchars($gscTag); ?>"><?php endif; ?>
    <?php if ($ywmTag): ?><meta name="yandex-verification" content="<?php echo htmlspecialchars($ywmTag); ?>"><?php endif; ?>

    <!-- Favicon -->
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($faviconUrl); ?>" type="image/x-icon">
    <link rel="manifest" href="/favicon/site.webmanifest">

    <!-- CSS -->
    <link rel="stylesheet" href="/css/tailwind-output.css">
    <link rel="stylesheet" href="/css/styles.css">
<!-- Preload критичных шрифтов -->
<link rel="preload" href="/fonts/roboto-regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/roboto-bold.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/roboto-medium.woff2" as="font" type="font/woff2" crossorigin>

    <!-- themes.css инлайнится ниже как <style>
         — устраняет 450 мс блокировки и предотвращает FOUC -->
    <?php if ($themesCss !== ''): ?>
    <style><?php echo $themesCss; ?></style>
    <?php endif; ?>

    <?php if ($gaId): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($gaId); ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo htmlspecialchars($gaId); ?>');</script>
    <?php endif; ?>

    <?php if ($ymId): ?>
    <script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");ym(<?php echo (int)$ymId; ?>,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true});</script>
    <noscript><div><img src="https://mc.yandex.ru/watch/<?php echo (int)$ymId; ?>" style="position:absolute; left:-9999px;" alt=""></div></noscript>
    <?php endif; ?>

    <!-- Schema.org -->
    <script type="application/ld+json"><?php echo json_encode($schemaSite, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <script type="application/ld+json"><?php echo json_encode($schemaStore, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
</head>
<body>