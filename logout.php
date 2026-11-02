<?php
// 1. Инициализируем сессию (нужно для доступа к ней)
session_start();

// 2. Очищаем все переменные сессии
$_SESSION = [];

// 3. Удаляем cookie сессии
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 4. Уничтожаем сессию на сервере
session_destroy();

// 5. Редирект на вход
header('Location: auth.php');
exit;