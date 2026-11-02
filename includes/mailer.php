<?php
/**
 * mailer.php — отправка email через SMTP.
 *
 * Креды — в mail-config.php (не в этом файле).
 * Шаблоны — в email-templates.php.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

/**
 * Загружает конфиг почты (с кэшированием в статике).
 */
function mailConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/mail-config.php';
    }
    return $config;
}

/**
 * Отправляет email через SMTP.
 *
 * @param string $subject  Тема
 * @param string $body     HTML-тело
 * @param string $to       Получатель (если пусто — admin_email из конфига)
 * @param bool   $debug    true — вывести SMTP-лог (только для отладки)
 * @return bool            true при успехе
 */
function sendAdminEmail(string $subject, string $body, string $to = '', bool $debug = false): bool
{
    $cfg = mailConfig();
    if ($to === '') {
        $to = $cfg['admin_email'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = ($cfg['encryption'] === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$cfg['port'];
        $mail->CharSet    = 'UTF-8';

        if ($debug || !empty($cfg['debug'])) {
            $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = 'echo';
        }

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        if ($debug || !empty($cfg['debug'])) {
            echo "\n\n=== ОШИБКА ===\n";
            echo "Сообщение: " . $e->getMessage() . "\n";
            echo "ErrorInfo: " . $mail->ErrorInfo . "\n";
        }
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}