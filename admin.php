<?php
session_start();
if (empty($_SESSION['logged_in'])) {
    header('Location: auth.php');
    exit;
}
$showDefaultPasswordWarning = !empty($_SESSION['default_password_warning']);
?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">

    <!-- ЗАПРЕТ ИНДЕКСАЦИИ: админ-панель не должна попадать в поиск -->
    <meta name="robots" content="noindex, nofollow">

    <title>Админ‑панель сайта</title>
    <link rel="stylesheet" href="/css/tailwind-output.css">
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/themes.css">
    <link href="/js/summernote/summernote-lite.css" rel="stylesheet">
    <style>
        /* ... (стили оставлены без изменений, как в предыдущей версии) ... */
        :root { --bg-primary:#0a0a0c; --bg-secondary:#111114; --bg-card:#18181c; --bg-card-hover:#1f1f24; --bg-elevated:#222228; --text-primary:#f0ece6; --text-secondary:#b0a99e; --text-muted:#7a7368; --accent:#c9965a; --accent-light:#d4a574; --accent-dark:#a67c4a; --border:#2a2a30; --border-light:#33333a; --danger:#d4554a; --success:#5a9a5a; --warning:#d4a574; --info:#5a7a9a; --radius-sm:8px; --radius-md:14px; --radius-lg:20px; --radius-full:9999px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Roboto, sans-serif; background: var(--bg-primary); color: var(--text-primary); padding: 1rem; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { margin-bottom: 1.5rem; font-size: 2rem; }
        h2 { margin: 1.5rem 0 1rem; font-size: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; }
        h3 { margin: 1.5rem 0 0.8rem; font-size: 1.15rem; color: var(--accent); }
        h4 { margin: 1rem 0 0.5rem; font-size: 1rem; }
        .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .tab { background: var(--bg-card); border: 1px solid var(--border); padding: 0.5rem 1.2rem; border-radius: var(--radius-full); cursor: pointer; font-weight: 500; color: var(--text-secondary); transition: 0.2s; position: relative; }
        .tab.active { background: var(--accent); color: #000; border-color: var(--accent); }
        .tab:hover:not(.active) { border-color: var(--accent-dark); color: var(--text-primary); }
        .form-panel { display: none; }
        .form-panel.active { display: block; }
        form { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem; }
        label { display: block; font-size: 0.9rem; margin-bottom: 0.3rem; color: var(--text-secondary); }
        input, textarea, select { width: 100%; padding: 0.6rem 0.8rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text-primary); font-family: inherit; margin-bottom: 0.8rem; }
        textarea { resize: vertical; min-height: 80px; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--accent); }
        .note-editor { margin-bottom: 0.8rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.4rem; font-weight: 600; border-radius: var(--radius-full); border: none; cursor: pointer; font-size: 0.95rem; transition: 0.2s; }
        .btn-primary { background: var(--accent); color: #000; }
        .btn-primary:hover { background: var(--accent-light); }
        .btn-outline { background: transparent; color: var(--accent); border: 2px solid var(--accent); margin-right: 0.5rem; }
        .btn-outline:hover { background: var(--accent); color: #000; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { opacity: 0.9; }
        .btn-sm { padding: 0.4rem 1rem; font-size: 0.8rem; }
        .btn-xs { padding: 0.3rem 0.7rem; font-size: 0.75rem; }
        .list-item { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.8rem 1rem; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
        .list-item .info { flex: 1; }
        .list-item .title { font-weight: 600; }
        .list-item .sub { color: var(--text-muted); font-size: 0.8rem; }
        .actions { display: flex; gap: 0.4rem; }
        .hidden { display: none !important; }
        .status-msg { padding: 0.5rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; display: none; }
        .status-success { background: var(--success); color: #fff; }
        .status-error { background: var(--danger); color: #fff; }

        .warning-banner {
            background: #4a2a1a;
            border: 1px solid var(--danger);
            color: #f0ece6;
            padding: 1rem 1.2rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .warning-banner strong { color: #ffb088; }

        .field-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: -0.5rem;
            margin-bottom: 0.8rem;
        }

        .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--danger);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            line-height: 1;
            text-align: center;
            border-radius: 9px;
            padding: 0 5px;
            margin-left: 6px;
            vertical-align: middle;
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { box-shadow: 0 0 0 0 rgba(212, 85, 74, 0.7); }
            50% { box-shadow: 0 0 0 6px rgba(212, 85, 74, 0); }
        }

        #total-events-badge {
            color: var(--danger);
            font-size: 1rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .list-item--new {
            border-left: 4px solid var(--danger);
            background: rgba(212, 85, 74, 0.08);
        }

        .order-card--new {
            border-left: 4px solid var(--danger);
            background: rgba(212, 85, 74, 0.06);
        }

        .new-badge {
            display: inline-block;
            background: var(--danger);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            margin-bottom: 0.5rem;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .order-card .order-header {
            padding: 0.8rem 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            transition: background 0.2s;
        }
        .order-card .order-header:hover {
            background: var(--bg-card-hover);
        }
        .order-card .order-details {
            padding: 1rem;
            border-top: 1px solid var(--border);
            background: var(--bg-secondary);
        }
        .order-card .order-details table {
            width: 100%;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .order-card .order-details td {
            padding: 3px 0;
            vertical-align: top;
        }
        .order-card .order-details td:first-child {
            color: var(--text-muted);
            width: 120px;
        }
        .order-card .order-details a {
            color: var(--accent);
            text-decoration: none;
        }
        .order-card .order-details a:hover {
            text-decoration: underline;
        }
        .order-card .order-details h4 {
            margin: 1rem 0 0.5rem;
            color: var(--accent);
            font-size: 1rem;
        }
        .order-card .order-details ul {
            list-style: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        .order-card .order-details ul li {
            padding: 4px 0;
            font-size: 0.9rem;
            border-bottom: 1px dashed var(--border);
        }
        .order-card .order-details .order-total {
            text-align: right;
            font-size: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border);
        }
        .order-card .order-details .order-total strong {
            color: var(--accent);
            font-size: 1.15rem;
        }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
            flex-shrink: 0;
        }
        .status-dot--new { background: var(--danger); box-shadow: 0 0 0 3px rgba(212, 85, 74, 0.2); }
        .status-dot--processing { background: var(--warning); box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.2); }
        .status-dot--shipped { background: var(--info); box-shadow: 0 0 0 3px rgba(90, 122, 154, 0.2); }
        .status-dot--done { background: var(--success); box-shadow: 0 0 0 3px rgba(90, 154, 90, 0.2); }

        select.order-status {
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            min-width: 150px;
        }
        select.order-status.status-new {
            background: rgba(212, 85, 74, 0.15);
            border-color: var(--danger);
            color: #f0a0a0;
        }
        select.order-status.status-processing {
            background: rgba(212, 165, 116, 0.15);
            border-color: var(--warning);
            color: #f0d0a0;
        }
        select.order-status.status-shipped {
            background: rgba(90, 122, 154, 0.15);
            border-color: var(--info);
            color: #a0c0e0;
        }
        select.order-status.status-done {
            background: rgba(90, 154, 90, 0.15);
            border-color: var(--success);
            color: #a0e0a0;
        }

        .orders-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
            align-items: center;
        }
        .orders-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            padding: 0.4rem 0.9rem;
            border-radius: var(--radius-full);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .orders-filter-btn:hover {
            border-color: var(--accent-dark);
            color: var(--text-primary);
        }
        .orders-filter-btn.active {
            background: var(--accent);
            color: #000;
            border-color: var(--accent);
        }
        .orders-filter-btn .count {
            background: rgba(0,0,0,0.2);
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .orders-filter-btn.active .count {
            background: rgba(0,0,0,0.15);
        }

        .orders-search-sort {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .orders-search-sort input,
        .orders-search-sort select {
            margin-bottom: 0;
        }
        .orders-search-sort input {
            flex: 1;
            min-width: 200px;
        }
        .orders-search-sort select {
            width: auto;
            min-width: 180px;
        }

        .orders-empty {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            background: var(--bg-card);
            border: 1px dashed var(--border);
            border-radius: var(--radius-sm);
        }

        .modal-overlay.open {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay .modal {
            position: static;
            transform: none;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .modal-overlay.open .modal {
            opacity: 1;
            pointer-events: auto;
        }

        .note-editor.note-frame { border: 1px solid #ccc !important; background: #fff !important; color: #333 !important; }
        .note-editor .note-toolbar { background: #f8f9fa !important; border-bottom: 1px solid #ddd !important; }
        .note-editor .note-toolbar .note-btn { background: #fff !important; color: #333 !important; border: 1px solid #ccc !important; }
        .note-editor .note-toolbar .note-btn:hover { background: #e9ecef !important; }
        .note-editor .note-editing-area { background: #fff !important; color: #333 !important; }
        .note-editor .note-editable { background: #fff !important; color: #333 !important; }
        .note-editor .note-statusbar { background: #f8f9fa !important; border-top: 1px solid #ddd !important; }
        .note-editor .note-statusbar .note-resizebar { background: #f8f9fa !important; }
        .note-editor .note-dropdown-menu { background: #fff !important; border: 1px solid #ccc !important; }
        .note-editor .note-dropdown-item { color: #333 !important; }
        .note-editor .note-dropdown-item:hover { background: #e9ecef !important; }
        .note-modal .note-modal-content { background: #fff !important; color: #333 !important; }
        .note-modal .note-modal-header { background: #f8f9fa !important; border-bottom: 1px solid #ddd !important; }
        .note-modal .note-modal-title { color: #333 !important; }
        .note-modal .note-modal-body { background: #fff !important; }
        .note-modal .note-modal-footer { background: #f8f9fa !important; border-top: 1px solid #ddd !important; }
        .note-modal input, .note-modal textarea, .note-modal select { background: #fff !important; color: #333 !important; border: 1px solid #ccc !important; }
        .note-editable img { width: auto !important; max-width: 100% !important; height: auto !important; }

        /* 04.11.2026: стили для превью изображения страницы */
        #page-image-preview img {
            max-height: 120px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>
<div class="container">
    <h1>⚙️ Админ-панель сайта<span id="total-events-badge"></span></h1>

    <?php if ($showDefaultPasswordWarning): ?>
        <div class="warning-banner" id="default-password-warning">
            <span style="font-size:1.5rem;">🔓</span>
            <div style="flex:1;">
                <strong>Вы используете пароль по умолчанию!</strong><br>
                <span style="font-size:0.9rem;">Сейчас активен пароль <code>secret123</code>. Срочно смените его в разделе «Настройки» → «🔐 Смена пароля».</span>
            </div>
        </div>
    <?php endif; ?>

    <div id="status" class="status-msg"></div>
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
        <div class="tabs" style="margin-bottom: 0;">
            <button class="tab active" data-tab="product">🍄 Товары</button>
            <button class="tab" data-tab="blog">📝 Блог</button>
            <button class="tab" data-tab="news">📰 Новости</button>
            <button class="tab" data-tab="orders">📦 Заказы</button>
            <button class="tab" data-tab="settings">⚙️ Настройки</button>
            <button class="tab" data-tab="faq">❓ FAQ</button>
            <button class="tab" data-tab="promotions">🎁 Акции</button>
            <button class="tab" data-tab="reviews">⭐ Отзывы</button>
            <!-- 04.11.2026: новая вкладка «Страницы» -->
            <button class="tab" data-tab="page">📄 Страницы</button>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="/" target="_blank" style="color:var(--accent); text-decoration:none; display: inline-flex; align-items: center; gap: 0.3rem;">🏠 На сайт</a>
            <a href="logout.php" style="color:var(--accent); text-decoration:none; display: inline-flex; align-items: center; gap: 0.3rem;">🚪 Выход</a>
        </div>
    </div>

    <!-- ТОВАРЫ -->
    <div class="form-panel active" id="panel-product">
        <form id="product-form">
            <h2>Товар</h2>
            <input type="hidden" id="prod-id">
            
            <label>Название (для покупателей) *</label>
            <input type="text" id="prod-name" required>
            
            <!-- НОВОЕ ПОЛЕ: SEO-заголовок -->
            <label>SEO-заголовок &lt;title&gt; (для поисковиков, не меняет URL)</label>
            <input type="text" id="prod-seo-title" placeholder="Купить Мухомор Красный 1-й сорт 50г в СПб">
            
            <label>Slug (URL)</label>
            <input type="text" id="prod-slug">
            <label>Латинское</label><input type="text" id="prod-latin">
            <label>Категория</label><select id="prod-category"><option value="culinary">Кулинарные</option><option value="medicinal">Лечебные</option><option value="exotic">Экзотические</option></select>
            <label>Цена (₽) *</label><input type="number" id="prod-price" required>
            <label>Артикул (SKU)</label><input type="text" id="prod-sku" placeholder="Например: MUSH-001">
            <label>Изображение</label><input type="file" id="prod-image-file" accept="image/*"><input type="hidden" id="prod-image-url">
            <label>ALT-текст</label><input type="text" id="prod-alt">
            <label>Эмодзи</label><input type="text" id="prod-emoji">
            <label>Краткое описание</label><textarea id="prod-desc" rows="2"></textarea>
            <label>Градиент</label><input type="text" id="prod-gradient">
            <label>Полное описание (HTML)</label><textarea id="prod-details" rows="5"></textarea>
            <label>Ключевые слова</label><input type="text" id="prod-keywords">
            <div><button type="submit" class="btn btn-primary">💾 Сохранить</button><button type="button" class="btn btn-outline" onclick="clearForm('product')">✕ Отмена</button></div>
        </form>
        <h2>Список товаров</h2>
        <div id="products-list"></div>
    </div>

    <!-- БЛОГ -->
    <div class="form-panel" id="panel-blog">
        <form id="blog-form"><h2>Запись</h2><input type="hidden" id="blog-id"><label>Заголовок *</label><input type="text" id="blog-title" required><label>Slug (URL)</label><input type="text" id="blog-slug"><label>Краткое описание</label><textarea id="blog-excerpt" rows="2"></textarea><label>Изображение</label><input type="file" id="blog-image-file" accept="image/*"><input type="hidden" id="blog-image-url"><label>ALT-текст</label><input type="text" id="blog-alt"><label>Эмодзи</label><input type="text" id="blog-emoji"><label>Дата</label><input type="text" id="blog-date"><label>Градиент</label><input type="text" id="blog-gradient"><label>Полный текст (HTML)</label><textarea id="blog-content" rows="6"></textarea><label>Ключевые слова</label><input type="text" id="blog-keywords"><div><button type="submit" class="btn btn-primary">💾 Сохранить</button><button type="button" class="btn btn-outline" onclick="clearForm('blog')">✕ Отмена</button></div></form><h2>Записи блога</h2><div id="blog-list"></div>
    </div>

    <!-- НОВОСТИ -->
    <div class="form-panel" id="panel-news">
        <form id="news-form"><h2>Новость</h2><input type="hidden" id="news-id"><label>Заголовок *</label><input type="text" id="news-title" required><label>Slug (URL)</label><input type="text" id="news-slug"><label>Краткое описание</label><textarea id="news-excerpt" rows="2"></textarea><label>Дата</label><input type="text" id="news-date"><label>Полный текст (HTML)</label><textarea id="news-content" rows="6"></textarea><label>Ключевые слова</label><input type="text" id="news-keywords"><div><button type="submit" class="btn btn-primary">💾 Сохранить</button><button type="button" class="btn btn-outline" onclick="clearForm('news')">✕ Отмена</button></div></form><h2>Новости</h2><div id="news-list"></div>
    </div>

    <!-- ЗАКАЗЫ -->
    <div class="form-panel" id="panel-orders">
        <h2>Заказы</h2>

        <div class="orders-toolbar" id="orders-filter-bar">
            <button type="button" class="orders-filter-btn active" data-filter="all">Все <span class="count" data-count="all">0</span></button>
            <button type="button" class="orders-filter-btn" data-filter="Новый">🆕 Новые <span class="count" data-count="Новый">0</span></button>
            <button type="button" class="orders-filter-btn" data-filter="В обработке">⏳ В обработке <span class="count" data-count="В обработке">0</span></button>
            <button type="button" class="orders-filter-btn" data-filter="Отправлен">📦 Отправленные <span class="count" data-count="Отправлен">0</span></button>
            <button type="button" class="orders-filter-btn" data-filter="Выполнен">✅ Выполненные <span class="count" data-count="Выполнен">0</span></button>
        </div>

        <div class="orders-search-sort">
            <input type="text" id="orders-search" placeholder="🔍 Поиск по имени, телефону, email, ID...">
            <select id="orders-sort">
                <option value="date_desc">📅 Новые сверху</option>
                <option value="date_asc">📅 Старые сверху</option>
                <option value="total_desc">💰 Дорогие сверху</option>
                <option value="total_asc">💰 Дешёвые сверху</option>
            </select>
        </div>

        <div id="orders-list"></div>
    </div>

    <!-- НАСТРОЙКИ -->
    <div class="form-panel" id="panel-settings">
        <form id="settings-form">
            <h3>Тема</h3>
            <select id="settings-theme"><option value="dark">Тёмная</option><option value="light">Светлая</option><option value="forest">Лесная</option></select>

            <h3>Основное</h3>
            <label>Название сайта (короткое, для логотипа и меню)</label>
            <input type="text" id="settings-site-name" placeholder="Мухомор СПб">
            
            <!-- НОВОЕ ПОЛЕ: SEO-заголовок -->
            <label>SEO-заголовок &lt;title&gt; (длинный, для поисковиков)</label>
            <input type="text" id="settings-site-seo-title" placeholder="Купить сушеные мухоморы в СПб — цены, доставка">
            
            <label>Описание сайта (description)</label>
            <textarea id="settings-site-desc" rows="3"></textarea>
            <label>Ключевые слова (keywords)</label>
            <input type="text" id="settings-site-keywords">

            <h3>Контакты</h3>
            <label>Телефон</label><input type="text" id="settings-phone"><label>Email</label><input type="email" id="settings-email"><label>Адрес</label><input type="text" id="settings-address"><label>Часы работы</label><input type="text" id="settings-hours">
        
            <h3>Соцсети</h3>
            <label>Telegram</label>
            <input type="text" id="settings-telegram" placeholder="@username или https://t.me/username">
            <label>VK</label>
            <input type="text" id="settings-vk" placeholder="username или https://vk.com/username">
            <label>YouTube</label>
            <input type="text" id="settings-youtube" placeholder="@channel или https://youtube.com/@channel">
            <label>MAX</label>
            <input type="text" id="settings-max" placeholder="https://max.ru/u/...">
            <p class="field-hint">Ссылку на профиль MAX можно получить в приложении: Настройки → «Пригласить друзей» → скопировать ссылку.</p>

            <h3>Логотип и фавиконка</h3><label>Логотип</label><input type="file" id="settings-logo-file" accept="image/*"><input type="hidden" id="settings-logo-url"><button type="button" id="clear-logo-btn" class="btn btn-outline btn-sm" style="margin-top:0.5rem;">🗑 Удалить логотип</button><label style="margin-top:1rem;">Фавиконка</label><input type="file" id="settings-favicon-file" accept="image/*"><input type="hidden" id="settings-favicon-url"><h3>Главная страница</h3><label>Герой: премиум‑текст (строка над заголовком)</label><input type="text" id="settings-hero-premium"><label>Герой: заголовок (можно использовать &lt;br&gt;)</label><textarea id="settings-hero-title" rows="2"></textarea><label>Герой: подзаголовок</label><textarea id="settings-hero-subtitle" rows="2"></textarea><label>Герой: текст кнопки каталога</label><input type="text" id="settings-hero-btn-catalog"><label>Герой: текст кнопки блога</label><input type="text" id="settings-hero-btn-blog"><h4>Блок «Почему выбирают»</h4><label>Заголовок блока</label><input type="text" id="settings-advantages-title"><label>Преимущество 1: иконка</label><input type="text" id="settings-adv1-icon"><label>Преимущество 1: заголовок</label><input type="text" id="settings-adv1-title"><label>Преимущество 1: описание</label><textarea id="settings-adv1-desc" rows="1"></textarea><label>Преимущество 2: иконка</label><input type="text" id="settings-adv2-icon"><label>Преимущество 2: заголовок</label><input type="text" id="settings-adv2-title"><label>Преимущество 2: описание</label><textarea id="settings-adv2-desc" rows="1"></textarea><label>Преимущество 3: иконка</label><input type="text" id="settings-adv3-icon"><label>Преимущество 3: заголовок</label><input type="text" id="settings-adv3-title"><label>Преимущество 3: описание</label><textarea id="settings-adv3-desc" rows="1"></textarea><label>Преимущество 4: иконка</label><input type="text" id="settings-adv4-icon"><label>Преимущество 4: заголовок</label><input type="text" id="settings-adv4-title"><label>Преимущество 4: описание</label><textarea id="settings-adv4-desc" rows="1"></textarea><label>Текст в подвале (описание)</label><textarea id="settings-footer-description" rows="2"></textarea>

            <h3>Аналитика</h3>
            <label>GA4 ID</label><input type="text" id="settings-ga-id">
            <label>Яндекс.Метрика ID</label><input type="text" id="settings-ym-id">
            <label>Google Search Console</label><input type="text" id="settings-gsc-tag">
            <label>Яндекс.Вебмастер</label><input type="text" id="settings-ywm-tag">

            <h3>Безопасность</h3>
            <label>Возрастная проверка (18+)</label>
            <select id="settings-age-check-enabled"><option value="yes">Включена</option><option value="no">Выключена</option></select>

            <h3>robots.txt</h3>
            <textarea id="settings-robots" rows="6"></textarea>
            <button type="submit" class="btn btn-primary">💾 Сохранить настройки</button>
        </form>

        <form id="password-form" autocomplete="off">
            <h2>🔐 Смена пароля</h2>
            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1rem;">
                Пароль хранится в виде bcrypt-хеша. После смены пароля текущая сессия останется активной, а новые входы потребуют новый пароль.
            </p>
            <label>Текущий пароль *</label>
            <input type="password" id="password-current" required autocomplete="current-password">
            <label>Новый пароль * (минимум 8 символов)</label>
            <input type="password" id="password-new" required minlength="8" autocomplete="new-password">
            <label>Подтверждение нового пароля *</label>
            <input type="password" id="password-confirm" required minlength="8" autocomplete="new-password">
            <div>
                <button type="submit" class="btn btn-primary">🔐 Сменить пароль</button>
            </div>
        </form>

        <form id="backup-form">
            <h2>📦 Резервное копирование</h2>
            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1rem;">
                Создаёт ZIP-архив со всеми данными сайта (<code>/data/</code>) и изображениями (<code>/uploads/</code>).
                Архив скачивается сразу в браузер — на сервере ничего не сохраняется.
            </p>
            <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:1.2rem;">
                ⚠️ <strong>Важно:</strong> архив содержит хеш пароля администратора. Храните его в надёжном месте.
            </p>
            <div>
                <button type="button" id="backup-btn" class="btn btn-primary">📦 Скачать резервную копию</button>
            </div>
            <p id="backup-status" class="field-hint" style="margin-top:0.8rem; display:none;"></p>
        </form>
    </div>

    <!-- FAQ -->
    <div class="form-panel" id="panel-faq">
        <form id="faq-form"><h2>Вопрос/Ответ</h2><input type="hidden" id="faq-id"><label>Вопрос *</label><input type="text" id="faq-question" required><label>Ответ (HTML)</label><textarea id="faq-answer" rows="5"></textarea><label>Порядок сортировки</label><input type="number" id="faq-order" value="0"><div><button type="submit" class="btn btn-primary">💾 Сохранить</button><button type="button" class="btn btn-outline" onclick="clearForm('faq')">✕ Отмена</button></div></form><h2>Список вопросов</h2><div id="faq-list"></div>
    </div>

    <!-- АКЦИИ -->
    <div class="form-panel" id="panel-promotions">
        <form id="promo-form"><h2>Акция</h2><input type="hidden" id="promo-id"><label>Название акции *</label><input type="text" id="promo-title" required><label>Slug (URL)</label><input type="text" id="promo-slug"><label>Изображение</label><input type="file" id="promo-image-file" accept="image/*"><input type="hidden" id="promo-image-url"><label>ALT-текст</label><input type="text" id="promo-alt"><label>Дата начала</label><input type="date" id="promo-start-date"><label>Дата окончания</label><input type="date" id="promo-end-date"><label>Описание (HTML)</label><textarea id="promo-description" rows="8"></textarea><div><button type="submit" class="btn btn-primary">💾 Сохранить</button><button type="button" class="btn btn-outline" onclick="clearForm('promo')">✕ Отмена</button></div></form><h2>Список акций</h2><div id="promotions-list"></div>
    </div>

    <!-- ОТЗЫВЫ -->
    <div class="form-panel" id="panel-reviews">
        <form id="review-form"><h2>Отзыв клиента</h2><input type="hidden" id="review-id"><label>Имя *</label><input type="text" id="review-name" required><label>Оценка (1–5)</label><input type="number" id="review-rating" min="1" max="5" value="5"><label>Товар (необязательно)</label>
            <select id="review-product">
                <option value="">— Не привязывать к товару —</option>
            </select><label>Текст отзыва *</label><textarea id="review-text" rows="5" required></textarea><label>Статус</label><select id="review-status"><option value="1">Опубликован</option><option value="0">Скрыт</option></select><div><button type="submit" class="btn btn-primary">💾 Сохранить</button><button type="button" class="btn btn-outline" onclick="clearForm('review')">✕ Отмена</button></div></form><h2>Список отзывов</h2><div id="reviews-list"></div>
    </div>

    <!-- 04.11.2026: СТРАНИЦЫ (произвольные страницы из pages.json) -->
    <div class="form-panel" id="panel-page">
        <form id="page-form">
            <h2>Страница</h2>
            <input type="hidden" id="page-id-orig">
            <label>Заголовок (h1) *</label>
            <input type="text" id="page-title" required>
            <label>Slug (URL, без /) *</label>
            <input type="text" id="page-slug" required placeholder="about, delivery, policy">
            <p class="field-hint">Только латиница, цифры, дефис. URL будет: /slug</p>
            <label>Содержимое (HTML)</label>
            <textarea id="page-content" rows="8"></textarea>
            <label>Изображение (OG + превью)</label>
            <input type="file" id="page-image-file" accept="image/*">
            <input type="hidden" id="page-image-url">
            <div id="page-image-preview" class="init-hidden" style="margin-bottom:0.8rem;">
                <img src="" alt="Превью">
            </div>
            <label>SEO-заголовок &lt;title&gt;</label>
            <input type="text" id="page-seo-title" placeholder="О нас — AmanitaCMS">
            <label>SEO-описание &lt;meta description&gt;</label>
            <textarea id="page-seo-description" rows="2"></textarea>
            <label><input type="checkbox" id="page-status" checked style="width:auto;margin-right:0.4rem;"> Опубликована</label>
            <label><input type="checkbox" id="page-show-in-menu" style="width:auto;margin-right:0.4rem;"> Показывать в меню</label>
            <label>Порядок сортировки (меньше — выше)</label>
            <input type="number" id="page-sort-order" value="10">
            <div>
                <button type="submit" class="btn btn-primary">💾 Сохранить</button>
                <button type="button" class="btn btn-outline" onclick="clearForm('page')">✕ Отмена</button>
            </div>
        </form>
        <h2>Список страниц</h2>
        <div id="pages-list"></div>
    </div>
</div>

<div class="modal-overlay" id="delete-modal">
    <div class="modal">
        <p>Удалить эту запись?</p>
        <button class="btn btn-danger" id="confirm-delete">🗑 Удалить</button>
        <button class="btn btn-outline" id="cancel-delete">Отмена</button>
    </div>
</div>

<script src="/js/jquery-3.6.0.min.js"></script>
<script src="/js/summernote/summernote-lite.js"></script>
<script src="/js/summernote/lang/summernote-ru-RU.js"></script>
<script src="/js/admin.js"></script>
<script>async function applyTheme(){try{const r=await fetch('/api/settings');const s=await r.json();document.documentElement.setAttribute('data-theme',s.theme||'dark');}catch(e){}}applyTheme();</script>
</body>
</html>