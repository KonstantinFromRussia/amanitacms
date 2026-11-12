<?php
/**
 * api.php — API для админки и публичной части.
 *
 * Обрабатывает GET, PUT, POST запросы.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/email-templates.php';

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Генерирует slug из названия (транслитерация + очистка).
 */
function generateSlug($text) {
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        'А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Д'=>'d','Е'=>'e','Ё'=>'yo','Ж'=>'zh','З'=>'z','И'=>'i','Й'=>'y','К'=>'k','Л'=>'l','М'=>'m','Н'=>'n','О'=>'o','П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u','Ф'=>'f','Х'=>'h','Ц'=>'ts','Ч'=>'ch','Ш'=>'sh','Щ'=>'sch','Ъ'=>'','Ы'=>'y','Ь'=>'','Э'=>'e','Ю'=>'yu','Я'=>'ya'
    ];
    $slug = '';
    $len = mb_strlen($text, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $char = mb_substr($text, $i, 1, 'UTF-8');
        $slug .= isset($map[$char]) ? $map[$char] : $char;
    }
    $slug = mb_strtolower($slug, 'UTF-8');
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'untitled';
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$dataDir = __DIR__ . '/data/';

// ============================================================
// СМЕНА ПАРОЛЯ АДМИНА
// ============================================================
if ($action === 'change-password') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    if (empty($_SESSION['logged_in'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $currentPassword = (string)($body['current_password'] ?? '');
    $newPassword     = (string)($body['new_password'] ?? '');
    $newPassword2    = (string)($body['new_password_confirm'] ?? '');

    // Валидация
    if ($currentPassword === '' || $newPassword === '' || $newPassword2 === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Все поля обязательны']);
        exit;
    }
    if ($newPassword !== $newPassword2) {
        http_response_code(400);
        echo json_encode(['error' => 'Новый пароль и подтверждение не совпадают']);
        exit;
    }
    if (mb_strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Пароль должен быть не короче 8 символов']);
        exit;
    }
    if ($newPassword === $currentPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'Новый пароль должен отличаться от текущего']);
        exit;
    }

    $settingsFile = $dataDir . 'settings.json';
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    if (!is_array($settings)) $settings = [];

    $adminHash = $settings['admin_password_hash'] ?? '';

    // Если хеша нет — разрешаем дефолтный пароль
    if (empty($adminHash)) {
        $currentOk = hash_equals('secret123', $currentPassword);
    } else {
        $currentOk = password_verify($currentPassword, $adminHash);
    }
    if (!$currentOk) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный текущий пароль']);
        exit;
    }

    // Сохраняем новый хеш
    $settings['admin_password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    if (empty($settings['admin_login'])) {
        $settings['admin_login'] = 'admin';
    }

    if (file_put_contents($settingsFile, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось сохранить пароль']);
        exit;
    }

    unset($_SESSION['default_password_warning']);
    echo json_encode(['success' => true, 'message' => 'Пароль успешно изменён']);
    exit;
}

// ============================================================
// ПРИЁМ ОТЗЫВА ОТ ПОЛЬЗОВАТЕЛЯ (публичный endpoint)
// ============================================================
if ($action === 'reviews-submit') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Honeypot: если поле website заполнено — это бот
    $honeypot = trim((string)($body['website'] ?? ''));
    if ($honeypot !== '') {
        echo json_encode(['success' => true, 'message' => 'Спасибо! Отзыв отправлен на модерацию.']);
        exit;
    }

    $name    = trim((string)($body['name'] ?? ''));
    $rating  = (int)($body['rating'] ?? 0);
    $text    = trim((string)($body['text'] ?? ''));
    $product = trim((string)($body['product'] ?? ''));

    if ($name === '' || $text === '' || $product === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Заполните все поля']);
        exit;
    }

    // Валидация длины
    if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
        http_response_code(400);
        echo json_encode(['error' => 'Имя должно быть от 2 до 50 символов']);
        exit;
    }
    if (mb_strlen($text) < 10 || mb_strlen($text) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Текст отзыва должен быть от 10 до 2000 символов']);
        exit;
    }

    // Оценка
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Оценка должна быть от 1 до 5']);
        exit;
    }

    // Капча
    $captchaAnswer   = trim((string)($body['captcha_answer'] ?? ''));
    $captchaExpected = trim((string)($body['captcha_expected'] ?? ''));
    if ($captchaAnswer === '' || $captchaExpected === '' || $captchaAnswer !== $captchaExpected) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверный ответ на капчу']);
        exit;
    }

    // Rate-limit: 1 отзыв в час на сессию
    $now = time();
    $lastSubmit = (int)($_SESSION['last_review_submit'] ?? 0);
    if ($now - $lastSubmit < 3600) {
        $waitMin = ceil((3600 - ($now - $lastSubmit)) / 60);
        http_response_code(429);
        echo json_encode(['error' => 'Слишком часто. Попробуйте через ' . $waitMin . ' мин.']);
        exit;
    }

    // Проверяем, что продукт существует (сравниваем без учета регистра и невидимых символов)
    $productsFile = $dataDir . 'products.json';
    $products = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) : [];
    if (!is_array($products)) $products = [];
    
    $productExists = false;
    $productClean = trim(preg_replace('/[\x00-\x1F\x7F\x{FEFF}]/u', '', mb_strtolower($product)));
    
    foreach ($products as $p) {
        $pNameClean = trim(preg_replace('/[\x00-\x1F\x7F\x{FEFF}]/u', '', mb_strtolower($p['name'] ?? '')));
        if ($pNameClean === $productClean) { 
            $productExists = true; 
            // Сохраняем оригинальное имя из базы (на случай, если в форме были лишние пробелы)
            $product = $p['name']; 
            break; 
        }
    }
    
    if (!$productExists) {
        http_response_code(400);
        echo json_encode(['error' => 'Товар не найден: "' . $product . '"']);
        exit;
    }

    // Записываем отзыв
    $reviewsFile = $dataDir . 'reviews.json';
    $reviews = file_exists($reviewsFile) ? json_decode(file_get_contents($reviewsFile), true) : [];
    if (!is_array($reviews)) $reviews = [];

    $newId = count($reviews) ? max(array_column($reviews, 'id')) + 1 : 1;

    $newReview = [
        'id'      => $newId,
        'name'    => $name,
        'rating'  => $rating,
        'product' => $product,
        'text'    => $text,
        'status'  => 0,                       // на модерации
        'date'    => date('c'),               // ISO 8601
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    $reviews[] = $newReview;

    if (file_put_contents($reviewsFile, json_encode($reviews, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось сохранить отзыв']);
        exit;
    }

    // --- EMAIL: уведомление админу о новом отзыве ---
    $tpl = templateNewReviewAdmin($newReview);
    sendAdminEmail($tpl['subject'], $tpl['body']);

    $_SESSION['last_review_submit'] = $now;

    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваш отзыв отправлен на модерацию.',
        'id'      => $newId,
    ]);
    exit;
}

// ============================================================
// ОСНОВНОЙ РОУТЕР
// ============================================================
// 04.11.2026: добавлен action=pages для произвольных страниц.
$allowed = [
    'products'   => 'products.json',
    'blog'       => 'blog.json',
    'news'       => 'news.json',
    'orders'     => 'orders.json',
    'settings'   => 'settings.json',
    'faq'        => 'faq.json',
    'promotions' => 'promotions.json',
    'reviews'    => 'reviews.json',
    'pages'      => 'pages.json',
];

if (!array_key_exists($action, $allowed)) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$filePath = $dataDir . $allowed[$action];

// ------------------ ЗАКАЗЫ ------------------
if ($action === 'orders') {
    if ($method === 'GET') {
        if (empty($_SESSION['logged_in'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        echo file_exists($filePath) ? file_get_contents($filePath) : json_encode([]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $order = json_decode($raw, true);
        if (!is_array($order) || empty($order['customer']) || empty($order['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid order data']);
            exit;
        }

        $orders = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
        $newId = count($orders) ? max(array_column($orders, 'id')) + 1 : 1;
        $order['id'] = $newId;
        $order['date'] = date('Y-m-d H:i:s');
        $order['status'] = 'Новый';
        $orders[] = $order;

        file_put_contents($filePath, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

        // --- EMAIL: уведомление админу о новом заказе ---
        $tpl = templateNewOrderAdmin($order);
        sendAdminEmail($tpl['subject'], $tpl['body']);

        echo json_encode(['success' => true, 'orderId' => $newId]);
        exit;
    }

    if ($method === 'PUT') {
        if (empty($_SESSION['logged_in'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $orders = json_decode(file_get_contents('php://input'), true);
        if (!is_array($orders)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        // Загружаем текущие заказы, чтобы найти старый статус для сравнения
        $oldOrdersRaw = file_exists($filePath) ? file_get_contents($filePath) : '[]';
        $oldOrders = json_decode($oldOrdersRaw, true) ?: [];
        $oldStatusMap = [];
        foreach ($oldOrders as $o) { 
            $oldStatusMap[$o['id']] = $o['status'] ?? ''; 
        }

        file_put_contents($filePath, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

        // --- EMAIL: уведомление клиенту при смене статуса ---
        foreach ($orders as $newOrder) {
            $id = $newOrder['id'] ?? 0;
            $newStatus = $newOrder['status'] ?? '';
            $oldStatus = $oldStatusMap[$id] ?? '';

            // Если статус изменился на один из этих — отправляем письмо
            if ($oldStatus !== $newStatus && in_array($newStatus, ['В обработке', 'Отправлен', 'Выполнен'])) {
                $clientEmail = trim($newOrder['customer']['email'] ?? '');
                if ($clientEmail !== '') {
                    $tpl = templateStatusChangedClient($newOrder, $newStatus);
                    sendAdminEmail($tpl['subject'], $tpl['body'], $clientEmail);
                }
            }
        }

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ------------------ НАСТРОЙКИ ------------------
if ($action === 'settings') {
    if ($method === 'GET') {
        $default = ['theme' => 'dark', 'site_name' => 'Магазин'];
        $current = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
        $result = array_merge($default, $current);
        // Скрываем секретные поля
        unset($result['admin_password_hash']);
        unset($result['admin_login']);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'PUT') {
        if (empty($_SESSION['logged_in'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $newSettings = json_decode(file_get_contents('php://input'), true);
        if (!is_array($newSettings)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        // Сохраняем секретные поля из текущих настроек, если они не переданы
        $current = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
        if (!is_array($current)) $current = [];

        if (isset($current['admin_password_hash'])) {
            $newSettings['admin_password_hash'] = $current['admin_password_hash'];
        }
        if (isset($current['admin_login'])) {
            $newSettings['admin_login'] = $current['admin_login'];
        }

        // Формируем robots.txt
        if (empty($newSettings['robots'])) {
            $newSettings['robots'] = "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /data/\nSitemap: " . SITE_URL . "/sitemap.xml";
        } else {
            $newSettings['robots'] = preg_replace('/Sitemap: https?:\/\/[^\/]+/i', 'Sitemap: ' . SITE_URL, $newSettings['robots']);
        }

        file_put_contents($filePath, json_encode($newSettings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        file_put_contents(__DIR__ . '/robots.txt', $newSettings['robots'], LOCK_EX);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ============================================================
// GET — с поддержкой пагинации
// ============================================================
if ($method === 'GET') {
    $limit  = isset($_GET['limit'])  ? max(1, min(100, (int)$_GET['limit']))  : null;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset'])            : 0;

    if ($action === 'products') {
        $data = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];

        $category = $_GET['category'] ?? '';
        if ($category !== '' && $category !== 'all') {
            $data = array_filter($data, function($item) use ($category) {
                return ($item['category'] ?? '') === $category;
            });
        }

        $search = $_GET['search'] ?? '';
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $data = array_filter($data, function($item) use ($searchLower) {
                $name = mb_strtolower($item['name'] ?? '');
                $desc = mb_strtolower($item['desc'] ?? '');
                return strpos($name, $searchLower) !== false || strpos($desc, $searchLower) !== false;
            });
        }

        $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
        $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
        if ($min_price !== null || $max_price !== null) {
            $data = array_filter($data, function($item) use ($min_price, $max_price) {
                $price = floatval($item['price'] ?? 0);
                if ($min_price !== null && $price < $min_price) return false;
                if ($max_price !== null && $price > $max_price) return false;
                return true;
            });
        }

        $sort_by = $_GET['sort_by'] ?? 'id';
        $order = $_GET['order'] ?? 'asc';
        if (in_array($sort_by, ['id', 'price', 'name'])) {
            usort($data, function($a, $b) use ($sort_by, $order) {
                $valA = $a[$sort_by] ?? 0;
                $valB = $b[$sort_by] ?? 0;
                if ($sort_by === 'price') {
                    $valA = floatval($valA);
                    $valB = floatval($valB);
                }
                if ($order === 'asc') {
                    return $valA <=> $valB;
                } else {
                    return $valB <=> $valA;
                }
            });
        }

        $data = array_values($data);
        $total = count($data);

        if ($limit !== null) {
            $data = array_slice($data, $offset, $limit);
            echo json_encode([
                'items'  => $data,
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ]);
            exit;
        }

        echo json_encode($data);
        exit;
    }

    if (in_array($action, ['blog', 'news'], true)) {
        $data = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
        if (!is_array($data)) $data = [];

        $total = count($data);

        if ($limit !== null) {
            $data = array_slice($data, $offset, $limit);
            echo json_encode([
                'items'  => array_values($data),
                'total'  => $total,
                'limit'  => $limit,
                'offset' => $offset,
            ]);
            exit;
        }

        echo json_encode(array_values($data));
        exit;
    }

    echo file_exists($filePath) ? file_get_contents($filePath) : json_encode([]);
    exit;
}

// ============================================================
// PUT — обновление данных (только админ)
// ============================================================
if ($method === 'PUT') {
    if (empty($_SESSION['logged_in'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Авто-генерация slug для записей, где его нет
    foreach ($data as &$item) {
        $titleField = '';
        if ($action === 'products') $titleField = $item['name'] ?? '';
        elseif ($action === 'blog') $titleField = $item['title'] ?? '';
        elseif ($action === 'news') $titleField = $item['title'] ?? '';
        elseif ($action === 'faq') $titleField = $item['question'] ?? '';
        elseif ($action === 'promotions') $titleField = $item['title'] ?? '';
        // 04.11.2026: slug для произвольной страницы генерим из title.
        elseif ($action === 'pages') $titleField = $item['title'] ?? '';

        if (!empty($titleField) && empty($item['slug'])) {
            $item['slug'] = generateSlug($titleField);
        }

        // 04.11.2026: pages — приводим статусы к int, ставим даты, id = slug.
        if ($action === 'pages') {
            $item['status']       = isset($item['status'])       ? (int)(bool)$item['status']       : 0;
            $item['show_in_menu'] = isset($item['show_in_menu']) ? (int)(bool)$item['show_in_menu'] : 0;
            $item['sort_order']   = isset($item['sort_order'])   ? (int)$item['sort_order']         : 10;
            $item['updated_at']   = date('Y-m-d H:i:s');
            if (empty($item['created_at'])) {
                $item['created_at'] = $item['updated_at'];
            }
            if (empty($item['id']) && !empty($item['slug'])) {
                $item['id'] = $item['slug'];
            }
        }
    }
    unset($item);

    if (file_put_contents($filePath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) !== false) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Unable to write file']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>