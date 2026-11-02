<?php
/**
 * auth.php — страница входа в админ-панель.
 *
 * ВАЖНО: Эта страница не должна индексироваться поисковиками.
 * Для этого в <head> добавлен <meta name="robots" content="noindex, nofollow">.
 * Также рекомендуется закрыть её в robots.txt через Disallow: /auth.php
 */

session_start();

// Читаем настройки (там хранится логин и хеш пароля)
$settingsFile = __DIR__ . '/data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
if (!is_array($settings)) $settings = [];

$adminLogin = $settings['admin_login'] ?? 'admin';
$adminHash  = $settings['admin_password_hash'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $pass  = $_POST['pass'] ?? '';

    $isDefault = empty($adminHash);
    $loginOk = hash_equals($adminLogin, (string)$login);

    if ($isDefault) {
        // Если хеша нет — разрешаем дефолтный пароль (только до первой смены)
        $passOk = hash_equals('secret123', (string)$pass);
    } else {
        $passOk = password_verify($pass, $adminHash);
    }

    if ($loginOk && $passOk) {
        // Защита от session fixation
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        if ($isDefault) {
            $_SESSION['default_password_warning'] = true;
        } else {
            unset($_SESSION['default_password_warning']);
        }

        header('Location: admin.php');
        exit;
    } else {
        // Задержка 300 мс для защиты от брутфорса
        usleep(300000);
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ЗАПРЕТ ИНДЕКСАЦИИ: страница входа не должна быть в поиске -->
    <meta name="robots" content="noindex, nofollow">

    <title>Вход в админку сайта</title>
    <style>
        /* Локальный шрифт Roboto (без Google Fonts) */
        @font-face {
            font-family: 'Roboto';
            src: url('/fonts/roboto-regular.woff2') format('woff2'),
                 url('/fonts/roboto-regular.woff') format('woff');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Roboto';
            src: url('/fonts/roboto-medium.woff2') format('woff2'),
                 url('/fonts/roboto-medium.woff') format('woff');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }

        * { box-sizing: border-box; }
        body {
            background: #0a0a0c;
            color: #f0ece6;
            font-family: 'Roboto', system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 1rem;
        }
        form {
            background: #18181c;
            padding: 2rem;
            border-radius: 14px;
            border: 1px solid #2a2a30;
            width: 100%;
            max-width: 320px;
        }
        h2 { margin-top: 0; margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: #b0a99e; }
        input {
            width: 100%;
            padding: 0.6rem 0.8rem;
            margin-bottom: 1rem;
            background: #111114;
            border: 1px solid #2a2a30;
            color: #f0ece6;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
        }
        input:focus { outline: none; border-color: #c9965a; }
        button {
            background: #c9965a;
            color: #000;
            border: none;
            padding: 0.7rem 2rem;
            border-radius: 9999px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
            font-size: 1rem;
            transition: background 0.2s;
        }
        button:hover { background: #d4a574; }
        .error { color: #d4554a; margin-bottom: 1rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <form method="post">
        <h2>Вход</h2>
        <?php if (isset($error)) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
        <label for="login">Логин</label>
        <input type="text" id="login" name="login" required autocomplete="username" autofocus>
        <label for="pass">Пароль</label>
        <input type="password" id="pass" name="pass" required autocomplete="current-password">
        <button type="submit">Войти</button>
    </form>
</body>
</html>