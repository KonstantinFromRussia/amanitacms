<?php
/**
 * email-templates.php — HTML-шаблоны писем.
 */

/**
 * Базовый шаблон (обёртка).
 */
function emailTemplate(string $title, string $content): string
{
    return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="background:#f5f5f5; padding:20px; font-family:Arial,sans-serif;">
    <div style="max-width:600px; margin:0 auto; background:#fff; border-radius:8px; padding:24px; border:1px solid #e0e0e0;">
        <h2 style="color:#c9965a; margin-top:0;">' . htmlspecialchars($title) . '</h2>
        ' . $content . '
        <hr style="border:none; border-top:1px solid #eee; margin:20px 0;">
        <p style="color:#999; font-size:12px; text-align:center;">
            Автоматическое уведомление от Muhomor.spb.ru
        </p>
    </div>
</body>
</html>';
}

/**
 * Письмо админу: новый заказ.
 */
function templateNewOrderAdmin(array $order): array
{
    $id     = (int)($order['id'] ?? 0);
    $date   = htmlspecialchars($order['date'] ?? '');
    $total  = number_format((float)($order['total'] ?? 0), 0, ',', ' ') . ' ₽';
    $cust   = $order['customer'] ?? [];

    $name    = htmlspecialchars($cust['name'] ?? '—');
    $phone   = htmlspecialchars($cust['phone'] ?? '—');
    $email   = htmlspecialchars($cust['email'] ?? '—');
    $address = htmlspecialchars($cust['address'] ?? '—');
    $comment = htmlspecialchars($cust['comment'] ?? '—');

    $itemsHtml = '';
    foreach (($order['items'] ?? []) as $it) {
        $itName  = htmlspecialchars($it['name'] ?? '');
        $itQty   = (int)($it['qty'] ?? 0);
        $itPrice = number_format((float)($it['price'] ?? 0), 0, ',', ' ');
        $itSum   = number_format((float)($it['price'] ?? 0) * $itQty, 0, ',', ' ');
        $itemsHtml .= "<tr>
            <td style='padding:6px 8px; border-bottom:1px solid #eee;'>$itName</td>
            <td style='padding:6px 8px; border-bottom:1px solid #eee; text-align:center;'>$itQty</td>
            <td style='padding:6px 8px; border-bottom:1px solid #eee; text-align:right;'>$itPrice ₽</td>
            <td style='padding:6px 8px; border-bottom:1px solid #eee; text-align:right;'><strong>$itSum ₽</strong></td>
        </tr>";
    }

    $content = "
        <p><strong>Заказ #$id</strong> от $date</p>
        <p style='font-size:18px; color:#c9965a;'><strong>Сумма: $total</strong></p>

        <h3 style='margin-top:20px;'>👤 Клиент</h3>
        <table style='width:100%; font-size:14px;'>
            <tr><td style='color:#888; width:100px;'>Имя:</td><td>$name</td></tr>
            <tr><td style='color:#888;'>Телефон:</td><td><a href='tel:$phone'>$phone</a></td></tr>
            <tr><td style='color:#888;'>Email:</td><td>$email</td></tr>
            <tr><td style='color:#888;'>Адрес:</td><td>$address</td></tr>
            <tr><td style='color:#888;'>Комментарий:</td><td>$comment</td></tr>
        </table>

        <h3 style='margin-top:20px;'>🛒 Состав заказа</h3>
        <table style='width:100%; font-size:14px; border-collapse:collapse;'>
            <thead>
                <tr style='background:#f8f8f8;'>
                    <th style='padding:8px; text-align:left;'>Товар</th>
                    <th style='padding:8px; text-align:center;'>Кол-во</th>
                    <th style='padding:8px; text-align:right;'>Цена</th>
                    <th style='padding:8px; text-align:right;'>Сумма</th>
                </tr>
            </thead>
            <tbody>$itemsHtml</tbody>
        </table>

        <p style='margin-top:20px;'>
            <a href='" . SITE_URL . "/admin.php' style='display:inline-block; background:#c9965a; color:#000; padding:10px 20px; border-radius:20px; text-decoration:none; font-weight:bold;'>
                Открыть админку →
            </a>
        </p>
    ";

    return [
        'subject' => "🆕 Новый заказ #$id на сумму $total",
        'body'    => emailTemplate("Новый заказ #$id", $content),
    ];
}

/**
 * Письмо клиенту: смена статуса заказа.
 */
function templateStatusChangedClient(array $order, string $newStatus): array
{
    $id   = (int)($order['id'] ?? 0);
    $name = htmlspecialchars($order['customer']['name'] ?? 'Клиент');
    $statusColor = '#c9965a';

    $statusText = '';
    switch ($newStatus) {
        case 'В обработке':
            $statusText = '⏳ Ваш заказ собирается';
            $statusColor = '#d4a574';
            break;
        case 'Отправлен':
            $statusText = '📦 Ваш заказ отправлен';
            $statusColor = '#5a7a9a';
            break;
        case 'Выполнен':
            $statusText = '✅ Ваш заказ выполнен';
            $statusColor = '#5a9a5a';
            break;
        default:
            $statusText = '🔄 Статус вашего заказа обновлён';
            break;
    }

    $content = "
        <p>Здравствуйте, <strong>$name</strong>!</p>
        <p>Статус вашего заказа <strong>#$id</strong> был изменён:</p>
        <p style='font-size:20px; color:$statusColor; font-weight:bold; margin:20px 0;'>$statusText</p>
        <p>Если у вас есть вопросы, просто ответьте на это письмо или свяжитесь с нами по телефону.</p>
        <p style='margin-top:30px;'>С уважением,<br>Muhomor.spb.ru</p>
    ";

    return [
        'subject' => "$statusText (Заказ #$id)",
        'body'    => emailTemplate("Статус заказа #$id", $content),
    ];
}

/**
 * Письмо админу: новый отзыв на модерации.
 */
function templateNewReviewAdmin(array $review): array
{
    $id      = (int)($review['id'] ?? 0);
    $name    = htmlspecialchars($review['name'] ?? '—');
    $rating  = (int)($review['rating'] ?? 5);
    $text    = nl2br(htmlspecialchars(mb_substr($review['text'] ?? '', 0, 300)));
    $product = htmlspecialchars($review['product'] ?? '—');
    $stars   = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

    $content = "
        <p><strong>Новый отзыв на модерации!</strong></p>
        <p><strong>Товар:</strong> $product</p>
        <p><strong>Автор:</strong> $name</p>
        <p><strong>Оценка:</strong> <span style='color:#c9965a; font-size:18px;'>$stars</span> ($rating/5)</p>
        <p><strong>Текст:</strong></p>
        <div style='background:#f9f9f9; border-left:4px solid #c9965a; padding:10px 15px; margin:10px 0; font-style:italic;'>
            $text
        </div>
        <p style='margin-top:20px;'>
            <a href='" . SITE_URL . "/admin.php' style='display:inline-block; background:#c9965a; color:#000; padding:10px 20px; border-radius:20px; text-decoration:none; font-weight:bold;'>
                Перейти к модерации →
            </a>
        </p>
    ";

    return [
        'subject' => "⭐ Новый отзыв от $name на товар «$product»",
        'body'    => emailTemplate("Новый отзыв на модерации", $content),
    ];
}