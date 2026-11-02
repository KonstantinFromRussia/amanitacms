<?php
/**
 * counter.php — счётчик уникальных посетителей с логированием и блокировкой.
 *
 * Обновлено:
 *  - Блокировка сканеров через strpos() (устраняет "No ending delimiter '/' found").
 *  - Расширенный список URI-паттернов (облачные сканеры, .env, docker, claude и т.д.).
 *  - Отсечение ботов Mail.ru (domain verifier), Googlebot и др. по User-Agent.
 *  - 24.10.2026: добавлены /.cursor/, /.netrc, /sentry.properties, PlugyDiag, CensysInspect.
 */

function counterLog(string $message): void
{
    $logFile = dirname(__DIR__) . '/data/counter.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $line = "$timestamp | IP: $ip | URI: $uri | $message | UA: " . substr($ua, 0, 80) . "\n";

    // Обрезка лога, если он стал больше 10 МБ
    if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
        $lines = file($logFile);
        $lines = array_slice($lines, -1000);
        file_put_contents($logFile, implode('', $lines));
    }

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Атомарно инкрементирует счётчик с файловой блокировкой.
 * Возвращает [было, стало] или [null, null] при ошибке.
 */
function incrementCounterAtomic(): array
{
    $dataFile = dirname(__DIR__) . '/data/counter.json';
    $lockFile = dirname(__DIR__) . '/data/counter.lock';

    $lockFp = @fopen($lockFile, 'c');
    if (!$lockFp) {
        counterLog("ОШИБКА: не удалось открыть counter.lock");
        return [null, null];
    }

    $locked = false;
    for ($i = 0; $i < 50; $i++) {
        if (flock($lockFp, LOCK_EX | LOCK_NB)) {
            $locked = true;
            break;
        }
        usleep(100000);
    }

    if (!$locked) {
        counterLog("ОШИБКА: не удалось получить блокировку counter.lock");
        fclose($lockFp);
        return [null, null];
    }

    try {
        $before = 1000;
        if (file_exists($dataFile)) {
            $content = @file_get_contents($dataFile);
            if ($content !== false && trim($content) !== '') {
                $data = json_decode($content, true);
                if (is_array($data) && isset($data['total']) && is_numeric($data['total'])) {
                    $before = (int)$data['total'];
                } else {
                    counterLog("ОШИБКА: counter.json невалидный JSON (total будет 1000)");
                }
            } else {
                counterLog("ОШИБКА: counter.json пустой или не читается");
            }
        } else {
            counterLog("Файл counter.json не существует — создаём (total=1000)");
        }

        $after = $before + 1;

        $newData = [
            'total'   => $after,
            'updated' => date('c'),
        ];
        $json = json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $tmpFile = $dataFile . '.tmp.' . getmypid();
        if (@file_put_contents($tmpFile, $json) === false) {
            counterLog("ОШИБКА: не удалось записать tmp-файл");
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            return [null, null];
        }

        if (!@rename($tmpFile, $dataFile)) {
            counterLog("ОШИБКА: не удалось переименовать tmp → counter.json");
            @unlink($tmpFile);
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
            return [null, null];
        }

        flock($lockFp, LOCK_UN);
        fclose($lockFp);

        return [$before, $after];

    } catch (Throwable $e) {
        counterLog("ИСКЛЮЧЕНИЕ: " . $e->getMessage());
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
        return [null, null];
    }
}

function readCounter(): array
{
    $file = dirname(__DIR__) . '/data/counter.json';

    if (!file_exists($file)) {
        return ['total' => 1000, 'updated' => date('c')];
    }

    $content = @file_get_contents($file);
    if ($content === false || trim($content) === '') {
        return ['total' => 1000, 'updated' => date('c')];
    }

    $data = json_decode($content, true);
    if (!is_array($data) || !isset($data['total']) || !is_numeric($data['total'])) {
        return ['total' => 1000, 'updated' => date('c')];
    }

    return $data;
}

function trackVisitor(): void
{
    static $alreadyTracked = false;
    if ($alreadyTracked) return;
    $alreadyTracked = true;

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/api/') === 0 || strpos($uri, '/api.php') === 0) return;
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') return;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET') return;

    if (!empty($_SESSION['logged_in'])) {
        counterLog("ПРОПУЩЕН: админ");
        return;
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if ($ua === '') {
        counterLog("ПРОПУЩЕН: пустой User-Agent");
        return;
    }

    // --- ФИЛЬТР БОТОВ И СКАНЕРОВ ПО USER-AGENT ---
    // 24.10.2026: добавлены PlugyDiag, CensysInspect.
    $botPattern = '/bot|crawl|spider|slurp|yandex|google|bing|baidu|duckduck|facebook|telegram|whatsapp|preview|scan|monitor|curl|wget|python|java|http|semrush|ahrefs|mj12|dotbot|petalbot|validator|palo alto|security|scanner|nmap|nikto|sqlmap|masscan|shodan|censys|zgrab|nuclei|acunetix|netsparker|burp|dirbuster|gobuster|wfuzz|ffuf|hostings-l1-check|domain verifier|mail\.ru|uptime|pingdom|statuscake|PlugyDiag|CensysInspect/i';
    if (preg_match($botPattern, $ua)) {
        counterLog("ПРОПУЩЕН: бот (UA)");
        return;
    }

    // --- ФИЛЬТР СКАНЕРОВ ПО URI (strpos) ---
    $scannerPatterns = [
        'phpinfo', '.env', '.aws', '.git', '/wp-', 'config.', 'credentials', 
        'secrets', '.npmrc', '.boto', '.s3cfg', 'Dockerfile', 'composer.json', 
        'database.sql', 'appsettings', '/shell', 'webshell', 'info.php', 
        'test.php', 'debug.php', 'runtime-config', '_profiler',
        // Новые паттерны для облачных сканеров и перебора файлов
        '.claude', '.docker', '.amplifyrc', 'auth.json', 'app.js', 
        'app.config.js', 'aws-exports.js', 'env.txt', '/env', 'env.js', 
        'env-config.js', 'index.js', 'docker-compose', 'sendgrid.env', 
        'vendor/composer', 'kestra', 'hightouch', 'temporal', 'prefect', 
        '.pypirc', '.dev.vars', 'livewire', '/filament', '/nova', '/pulse', 
        '/telescope', '/horizon', 'backpack', '/pricing', '/signup', 
        '/dashboard', '/checkout', '/settings', '/login', '/register',
        'mailru-verification', 'domain verifier',
        // 24.10.2026: новые паттерны (по логам сканеров)
        '/.cursor/', '/.netrc', '/sentry.properties',
    ];
    foreach ($scannerPatterns as $pattern) {
        if (strpos($uri, $pattern) !== false) {
            counterLog("ПРОПУЩЕН: сканер (URI: $uri)");
            return;
        }
    }

    if (!empty($_COOKIE['visitor_id'])) {
        counterLog("ПРОПУЩЕН: cookie visitor_id");
        return;
    }

    list($before, $after) = incrementCounterAtomic();

    if ($before === null || $after === null) {
        counterLog("ОШИБКА: не удалось инкрементировать счётчик");
        return;
    }

    $visitorId = bin2hex(random_bytes(8));
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    @setcookie('visitor_id', $visitorId, time() + 31536000, '/', '', $isSecure, true);
    $_COOKIE['visitor_id'] = $visitorId;

    counterLog("ЗАПИСАНО: total $before → $after (visitor_id=$visitorId)");
}

function getVisitorCount(): int
{
    $data = readCounter();
    return (int)($data['total'] ?? 1000);
}