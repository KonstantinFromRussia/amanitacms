<?php
/**
 * mail-config.php — SMTP-настройки.
 *
 * ВАЖНО:
 *  - Этот файл НЕ должен попадать в Git (добавьте в .gitignore).
 *  - Этот файл НЕ попадает в ZIP-бэкап (backup.php архивирует только /data/ и /uploads/).
 *  - Доступ к /includes/ закрыт через .htaccess (Require all denied).
 *  - При смене пароля почты — правьте только этот файл.
 */

return [
    'host'        => '',
    'port'        => 587,
    'username'    => '',
    'password'    => '',
    'encryption'  => 'tls',                       // 'tls' (STARTTLS, 587) или 'ssl' (465)
    'from_email'  => '',
    'from_name'   => '',
    'admin_email' => '',
    'debug'       => false,                       // true — только для отладки, не на проде!
];