/**
 * admin.js — скрипт для админ-панели.
 *
 * Управляет вкладками, загрузкой данных, сохранением, модалками.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Глобальные переменные для хранения данных
    let products = [], blog = [], news = [], orders = [], settings = {}, faq = [], promotions = [], reviews = [];
    
    const statusDiv = document.getElementById('status');
    const tabs = document.querySelectorAll('.tab');
    const panels = document.querySelectorAll('.form-panel');
    const deleteModal = document.getElementById('delete-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    
    let deleteContext = { type: null, id: null };
    let imageUploading = false;

    // Состояние фильтров заказов
    let ordersFilter = 'all';
    let ordersSearch = '';
    let ordersSort = 'date_desc';

    // ============================================================
    // ВСПЛЫВАЮЩИЕ УВЕДОМЛЕНИЯ (заменяют старую плашку сверху)
    // ============================================================
    function showStatus(msg, isError = false) {
        // Старое сообщение (для совместимости, но оно уже не видно)
        if (statusDiv) {
            statusDiv.textContent = msg;
            statusDiv.className = 'status-msg ' + (isError ? 'status-error' : 'status-success');
            statusDiv.style.display = 'block';
            setTimeout(() => { if(statusDiv) statusDiv.style.display = 'none'; }, 3000);
        }

        // Новое: всплывающее уведомление в правом нижнем углу
        let toast = document.getElementById('admin-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'admin-toast';
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: bold;
                font-size: 14px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                transition: opacity 0.3s, transform 0.3s;
                opacity: 0;
                transform: translateY(20px);
                pointer-events: none;
            `;
            document.body.appendChild(toast);
        }

        toast.textContent = msg;
        toast.style.background = isError ? '#d4554a' : '#5a9a5a';
        toast.style.color = '#fff';
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';

        clearTimeout(window.adminToastTimer);
        window.adminToastTimer = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
        }, 3000);
    }

    // ============================================================
    // СЧЁТЧИКИ СОБЫТИЙ (бейджи на вкладках, favicon)
    // ============================================================
    function getNewReviewsCount() {
        return reviews.filter(r => (r.status ?? 0) === 0).length;
    }

    function getNewOrdersCount() {
        return orders.filter(o => o.status === 'Новый').length;
    }

    function updateEventBadges() {
        const newReviews = getNewReviewsCount();
        const newOrders = getNewOrdersCount();

        // Бейдж на вкладке "Отзывы"
        const reviewsTab = document.querySelector('.tab[data-tab="reviews"]');
        if (reviewsTab) {
            let badge = reviewsTab.querySelector('.tab-badge');
            if (newReviews > 0) {
                if (!badge) { badge = document.createElement('span'); badge.className = 'tab-badge'; reviewsTab.appendChild(badge); }
                badge.textContent = newReviews;
            } else if (badge) badge.remove();
        }

        // Бейдж на вкладке "Заказы"
        const ordersTab = document.querySelector('.tab[data-tab="orders"]');
        if (ordersTab) {
            let badge = ordersTab.querySelector('.tab-badge');
            if (newOrders > 0) {
                if (!badge) { badge = document.createElement('span'); badge.className = 'tab-badge'; ordersTab.appendChild(badge); }
                badge.textContent = newOrders;
            } else if (badge) badge.remove();
        }

        // Общий счётчик в заголовке
        const total = newReviews + newOrders;
        const totalBadge = document.getElementById('total-events-badge');
        if (totalBadge) totalBadge.textContent = total > 0 ? ' (' + total + ' новых)' : '';

        // Favicon с цифрой
        updateFaviconBadge(total);
    }

    function updateFaviconBadge(count) {
        const favicon = document.querySelector('link[rel="shortcut icon"]') || document.querySelector('link[rel="icon"]');
        if (!favicon) return;
        if (!favicon.dataset.originalHref) favicon.dataset.originalHref = favicon.href;
        if (count === 0) { favicon.href = favicon.dataset.originalHref; return; }
        const canvas = document.createElement('canvas');
        canvas.width = 64; canvas.height = 64;
        const ctx = canvas.getContext('2d');
        ctx.beginPath(); ctx.arc(32, 32, 28, 0, 2 * Math.PI); ctx.fillStyle = '#d4554a'; ctx.fill();
        ctx.fillStyle = '#fff'; ctx.font = 'bold 38px sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(count > 9 ? '9+' : String(count), 32, 34);
        favicon.href = canvas.toDataURL('image/png');
    }

    // ============================================================
    // УТИЛИТЫ
    // ============================================================
    function generateSlug(text) {
        const map = {
            'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya',
            'А':'a','Б':'b','В':'v','Г':'g','Д':'d','Е':'e','Ё':'yo','Ж':'zh','З':'z','И':'i','Й':'y','К':'k','Л':'l','М':'m','Н':'n','О':'o','П':'p','Р':'r','С':'s','Т':'t','У':'u','Ф':'f','Х':'h','Ц':'ts','Ч':'ch','Ш':'sh','Щ':'sch','Ъ':'','Ы':'y','Ь':'','Э':'e','Ю':'yu','Я':'ya'
        };
        let slug = '';
        for (let char of text) slug += map[char] || char;
        slug = slug.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return slug || 'untitled';
    }

    function getNextId(arr) {
        return arr.length ? Math.max(...arr.map(i => i.id)) + 1 : 1;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>]/g, function(m){
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // ============================================================
    // SLUG-АВТОГЕНЕРАЦИЯ
    // ============================================================
    document.getElementById('prod-name')?.addEventListener('input', function() { document.getElementById('prod-slug').value = generateSlug(this.value); });
    document.getElementById('blog-title')?.addEventListener('input', function() { document.getElementById('blog-slug').value = generateSlug(this.value); });
    document.getElementById('news-title')?.addEventListener('input', function() { document.getElementById('news-slug').value = generateSlug(this.value); });
    document.getElementById('promo-title')?.addEventListener('input', function() { document.getElementById('promo-slug').value = generateSlug(this.value); });

    // ============================================================
    // ЗАГРУЗКА ИЗОБРАЖЕНИЙ
    // ============================================================
    async function uploadImage(file, urlFieldId) {
        if (!file) return;
        imageUploading = true;
        showStatus('⏳ Загрузка...', false);
        const fd = new FormData();
        fd.append('image', file);
        try {
            const res = await fetch('/upload.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            if (!res.ok) throw new Error(await res.text());
            const url = await res.text();
            document.getElementById(urlFieldId).value = url;
            showStatus('✅ Загружено', false);
        } catch (e) {
            alert('Ошибка загрузки');
            showStatus('❌ Ошибка', true);
        } finally {
            imageUploading = false;
        }
    }

    document.getElementById('prod-image-file')?.addEventListener('change', function() { if (this.files[0]) uploadImage(this.files[0], 'prod-image-url'); });
    document.getElementById('blog-image-file')?.addEventListener('change', function() { if (this.files[0]) uploadImage(this.files[0], 'blog-image-url'); });
    document.getElementById('settings-logo-file')?.addEventListener('change', function() { if (this.files[0]) uploadImage(this.files[0], 'settings-logo-url'); });
    document.getElementById('settings-favicon-file')?.addEventListener('change', function() { if (this.files[0]) uploadImage(this.files[0], 'settings-favicon-url'); });
    document.getElementById('promo-image-file')?.addEventListener('change', function() { if (this.files[0]) uploadImage(this.files[0], 'promo-image-url'); });
    document.getElementById('clear-logo-btn')?.addEventListener('click', () => {
        document.getElementById('settings-logo-url').value = '';
        document.getElementById('settings-logo-file').value = '';
        showStatus('Логотип удалён', false);
    });

    function isImageUploading() {
        if (imageUploading) { alert('Дождитесь загрузки'); return true; }
        return false;
    }

    // ============================================================
    // SUMMERNOTE
    // ============================================================
    if (typeof jQuery !== 'undefined' && jQuery.fn.summernote) {
        function handleImageUpload(files, editor) {
            var data = new FormData();
            data.append('image', files[0]);
            $.ajax({
                url: '/upload.php',
                method: 'POST',
                data: data,
                processData: false,
                contentType: false,
                success: function(url) { $(editor).summernote('insertImage', url); },
                error: function() { alert('Ошибка загрузки'); }
            });
        }
        $('#prod-details, #blog-content, #news-content, #faq-answer, #promo-description').each(function() {
            $(this).summernote({
                height: 250,
                lang: 'ru-RU',
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['codeview', 'help']]
                ],
                callbacks: { onImageUpload: function(files) { handleImageUpload(files, $(this)); } }
            });
        });
    }

    // ============================================================
    // ЗАПОЛНЕНИЕ SELECT ТОВАРОВ ДЛЯ ОТЗЫВОВ
    // ============================================================
    function populateReviewProductSelect() {
        const select = document.getElementById('review-product');
        if (!select) return;
        const currentValue = select.value;
        while (select.options.length > 1) select.remove(1);
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.name;
            option.textContent = product.name;
            select.appendChild(option);
        });
        if (currentValue) select.value = currentValue;
    }

    // ============================================================
    // ЗАГРУЗКА ВСЕХ ДАННЫХ
    // ============================================================
    async function loadData() {
        try {
            const [pRes, bRes, nRes, oRes, sRes, fRes, promRes, revRes] = await Promise.all([
                fetch('/api/products'), fetch('/api/blog'), fetch('/api/news'), fetch('/api/orders'),
                fetch('/api/settings'), fetch('/api/faq'), fetch('/api/promotions'), fetch('/api/reviews')
            ]);
            products = await pRes.json();
            blog = await bRes.json();
            news = await nRes.json();
            orders = await oRes.json();
            settings = await sRes.json();
            faq = await fRes.json();
            promotions = await promRes.json();
            reviews = await revRes.json();

            populateReviewProductSelect();

            // Заполнение формы настроек
            document.getElementById('settings-theme').value = settings.theme || 'dark';
            document.getElementById('settings-site-name').value = settings.site_name || '';
            document.getElementById('settings-site-seo-title').value = settings.site_seo_title || '';
            document.getElementById('settings-site-desc').value = settings.site_description || '';
            document.getElementById('settings-site-keywords').value = settings.site_keywords || '';
            document.getElementById('settings-phone').value = settings.phone || '';
            document.getElementById('settings-email').value = settings.email || '';
            document.getElementById('settings-address').value = settings.address || '';
            document.getElementById('settings-hours').value = settings.hours || '';
            document.getElementById('settings-telegram').value = settings.telegram || '';
            document.getElementById('settings-vk').value = settings.vk || '';
            document.getElementById('settings-youtube').value = settings.youtube || '';
            document.getElementById('settings-max').value = settings.max || '';
            document.getElementById('settings-logo-url').value = settings.logo_url || '';
            document.getElementById('settings-favicon-url').value = settings.favicon_url || '';
            document.getElementById('settings-hero-premium').value = settings.hero_premium || 'Премиум-качество с 2020 года';
            document.getElementById('settings-hero-title').value = settings.hero_title || 'Откройте мир<br>натуральных сушеных грибов';
            document.getElementById('settings-hero-subtitle').value = settings.hero_subtitle || 'Бережная сушка, ручной отбор...';
            document.getElementById('settings-hero-btn-catalog').value = settings.hero_btn_catalog || '🛍 Перейти в каталог';
            document.getElementById('settings-hero-btn-blog').value = settings.hero_btn_blog || '📖 Узнать больше';
            document.getElementById('settings-advantages-title').value = settings.advantages_title || 'Почему выбирают Muhomor.spb.ru';
            document.getElementById('settings-adv1-icon').value = settings.adv1_icon || '🌿';
            document.getElementById('settings-adv1-title').value = settings.adv1_title || '100% Органика';
            document.getElementById('settings-adv1-desc').value = settings.adv1_desc || 'Все грибы собраны в экологически чистых регионах...';
            document.getElementById('settings-adv2-icon').value = settings.adv2_icon || '🔬';
            document.getElementById('settings-adv2-title').value = settings.adv2_title || 'Лабораторный контроль';
            document.getElementById('settings-adv2-desc').value = settings.adv2_desc || 'Каждая партия проходит проверку...';
            document.getElementById('settings-adv3-icon').value = settings.adv3_icon || '📦';
            document.getElementById('settings-adv3-title').value = settings.adv3_title || 'Бережная упаковка';
            document.getElementById('settings-adv3-desc').value = settings.adv3_desc || 'Вакуумная упаковка...';
            document.getElementById('settings-adv4-icon').value = settings.adv4_icon || '🚚';
            document.getElementById('settings-adv4-title').value = settings.adv4_title || 'Быстрая доставка';
            document.getElementById('settings-adv4-desc').value = settings.adv4_desc || 'Отправка в день заказа...';
            document.getElementById('settings-footer-description').value = settings.footer_description || 'Сушеные грибы премиум-качества. Доставка по России.';
            document.getElementById('settings-ga-id').value = settings.ga_id || '';
            document.getElementById('settings-ym-id').value = settings.ym_id || '';
            document.getElementById('settings-gsc-tag').value = settings.gsc_tag || '';
            document.getElementById('settings-ywm-tag').value = settings.ywm_tag || '';
            document.getElementById('settings-robots').value = settings.robots || '';
            document.getElementById('settings-age-check-enabled').value = settings.age_check_enabled || 'yes';
            document.documentElement.setAttribute('data-theme', settings.theme || 'dark');

            renderAllLists();
            renderOrdersList();
            updateEventBadges();
        } catch (e) {
            console.error('loadData error:', e);
            showStatus('Ошибка загрузки данных: ' + e.message, true);
        }
    }

    // ============================================================
    // СОХРАНЕНИЕ
    // ============================================================
    async function saveData(type, arr) {
        const apiMap = {
            product: 'products', blog: 'blog', news: 'news', faq: 'faq',
            promotion: 'promotions', review: 'reviews', order: 'orders'
        };
        const endpoint = apiMap[type] || type;
        try {
            const res = await fetch('/api/' + endpoint, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(arr),
                credentials: 'same-origin'
            });
            if (!res.ok) {
                const txt = await res.text().catch(() => '');
                throw new Error('HTTP ' + res.status + ' ' + txt);
            }
            showStatus('✅ Сохранено');
            await loadData();
            return true;
        } catch (e) {
            console.error('saveData error:', e);
            showStatus('❌ Ошибка сохранения: ' + e.message, true);
            return false;
        }
    }

    // ============================================================
    // РЕНДЕР СПИСКОВ
    // ============================================================
    function renderList(containerId, items, type) {
        const container = document.getElementById(containerId);
        if (!container) return;
        let sorted;
        if (type === 'review') {
            // Неопубликованные — вверх
            const unmoderated = items.filter(i => (i.status ?? 0) === 0).reverse();
            const others = items.filter(i => (i.status ?? 0) !== 0).reverse();
            sorted = [...unmoderated, ...others];
        } else {
            sorted = [...items].reverse();
        }

        container.innerHTML = sorted.map(item => {
            let title = '', sub = '', badge = '', publishBtn = '';
            const isNew = (type === 'review' && (item.status ?? 0) === 0);
            if (type === 'product') {
                title = (item.emoji || '') + ' ' + item.name;
                sub = `${item.price} ₽ | ${item.category}`;
            } else if (type === 'faq') {
                title = item.question;
                sub = (item.answer || '').replace(/<[^>]*>/g, '').substring(0, 60);
            } else if (type === 'promotion') {
                title = item.title;
                sub = (item.description || '').replace(/<[^>]*>/g, '').substring(0, 60);
            } else if (type === 'review') {
                title = item.name + ' (' + (item.rating || 5) + '★)';
                sub = (item.text || '').substring(0, 80);
                if (isNew) {
                    badge = '<span class="new-badge">🆕 Новый</span>';
                    publishBtn = `<button type="button" class="btn btn-primary btn-sm publish-btn" data-id="${item.id}" title="Опубликовать отзыв" style="background:var(--success); color:#fff;">✅</button>`;
                }
            } else {
                title = item.title;
                sub = item.date || '';
            }
            return `<div class="list-item${isNew ? ' list-item--new' : ''}">
                <div class="info">
                    <div class="title">${escapeHtml(title)}${badge}</div>
                    <div class="sub">${escapeHtml(sub)}</div>
                </div>
                <div class="actions">
                    ${publishBtn}
                    <button type="button" class="btn btn-outline btn-sm edit-btn" data-type="${type}" data-id="${item.id}">✏️</button>
                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-type="${type}" data-id="${item.id}">🗑</button>
                </div>
            </div>`;
        }).join('');

        // Делегирование событий (один раз)
        if (!container.dataset.listHandlersAttached) {
            container.dataset.listHandlersAttached = '1';
            container.addEventListener('click', async function (e) {
                const publishBtn = e.target.closest('.publish-btn');
                if (publishBtn) {
                    const id = parseInt(publishBtn.dataset.id);
                    const review = reviews.find(r => r.id === id);
                    if (!review) { showStatus('❌ Отзыв не найден', true); return; }
                    review.status = 1;
                    publishBtn.disabled = true;
                    publishBtn.textContent = '⏳';
                    await saveData('review', reviews);
                    showStatus('✅ Отзыв опубликован');
                    return;
                }
                const editBtn = e.target.closest('.edit-btn');
                if (editBtn) {
                    const t = editBtn.dataset.type;
                    const id = parseInt(editBtn.dataset.id);
                    const map = { product: products, blog: blog, news: news, faq: faq, promotion: promotions, review: reviews };
                    const item = (map[t] || []).find(i => i.id === id);
                    if (item) {
                        fillForm(t, item);
                        const panel = document.getElementById('panel-' + t);
                        if (panel) panel.scrollIntoView({ behavior: 'smooth' });
                    }
                    return;
                }
                const delBtn = e.target.closest('.delete-btn');
                if (delBtn) {
                    deleteContext.type = delBtn.dataset.type;
                    deleteContext.id = parseInt(delBtn.dataset.id);
                    deleteModal.classList.add('open');
                }
            });
        }
    }

    // ============================================================
    // ЗАКАЗЫ — ЦВЕТА, ФИЛЬТРЫ, ПОИСК, СОРТИРОВКА
    // ============================================================
    function getStatusDotClass(status) {
        if (status === 'Новый') return 'status-dot--new';
        if (status === 'В обработке') return 'status-dot--processing';
        if (status === 'Отправлен') return 'status-dot--shipped';
        if (status === 'Выполнен') return 'status-dot--done';
        return '';
    }

    function getStatusSelectClass(status) {
        if (status === 'Новый') return 'status-new';
        if (status === 'В обработке') return 'status-processing';
        if (status === 'Отправлен') return 'status-shipped';
        if (status === 'Выполнен') return 'status-done';
        return '';
    }

    function getFilteredOrders() {
        let result = [...orders];

        // Фильтр по статусу
        if (ordersFilter !== 'all') {
            result = result.filter(o => o.status === ordersFilter);
        }

        // Поиск
        if (ordersSearch.trim() !== '') {
            const q = ordersSearch.trim().toLowerCase();
            result = result.filter(o => {
                const inId = String(o.id).includes(q);
                const inName = (o.customer?.name || '').toLowerCase().includes(q);
                const inPhone = (o.customer?.phone || '').toLowerCase().includes(q);
                const inEmail = (o.customer?.email || '').toLowerCase().includes(q);
                const inItems = (o.items || []).some(it => (it.name || '').toLowerCase().includes(q));
                return inId || inName || inPhone || inEmail || inItems;
            });
        }

        // Сортировка
        if (ordersSort === 'date_desc') {
            result.sort((a, b) => (b.id || 0) - (a.id || 0));
        } else if (ordersSort === 'date_asc') {
            result.sort((a, b) => (a.id || 0) - (b.id || 0));
        } else if (ordersSort === 'total_desc') {
            result.sort((a, b) => (b.total || 0) - (a.total || 0));
        } else if (ordersSort === 'total_asc') {
            result.sort((a, b) => (a.total || 0) - (b.total || 0));
        }

        return result;
    }

    function updateOrderFilterCounts() {
        const counts = {
            'all': orders.length,
            'Новый': orders.filter(o => o.status === 'Новый').length,
            'В обработке': orders.filter(o => o.status === 'В обработке').length,
            'Отправлен': orders.filter(o => o.status === 'Отправлен').length,
            'Выполнен': orders.filter(o => o.status === 'Выполнен').length,
        };
        document.querySelectorAll('.orders-filter-btn .count').forEach(el => {
            const key = el.dataset.count;
            if (counts[key] !== undefined) el.textContent = counts[key];
        });
    }

    function renderOrdersList() {
        const container = document.getElementById('orders-list');
        if (!container) return;

        updateOrderFilterCounts();

        const filtered = getFilteredOrders();

        if (filtered.length === 0) {
            let message = 'Нет заказов';
            if (ordersFilter !== 'all') message = 'Нет заказов со статусом «' + ordersFilter + '»';
            if (ordersSearch.trim() !== '') message = 'Ничего не найдено по запросу «' + escapeHtml(ordersSearch) + '»';
            container.innerHTML = '<div class="orders-empty">' + message + '</div>';
            return;
        }

        container.innerHTML = filtered.map(order => {
            const isNew = order.status === 'Новый';
            const dotClass = getStatusDotClass(order.status);
            const selectClass = getStatusSelectClass(order.status);
            const itemsHtml = (order.items || []).map(item =>
                `<li>${escapeHtml(item.name)} × ${item.qty} = <strong>${(item.price * item.qty).toLocaleString('ru-RU')} ₽</strong></li>`
            ).join('');

            return `<div class="order-card${isNew ? ' order-card--new' : ''}">
                <div class="order-header" onclick="toggleOrder(${order.id})">
                    <div style="flex:1;">
                        <div style="font-weight:600;">
                            <span class="status-dot ${dotClass}"></span>
                            Заказ #${order.id} от ${order.date}
                            ${isNew ? '<span class="new-badge">🆕 Новый</span>' : ''}
                            <span style="color:var(--text-muted); font-size:0.85rem; margin-left:0.5rem;">▼</span>
                        </div>
                        <div style="color:var(--text-muted); font-size:0.85rem; margin-left:16px;">
                            ${escapeHtml(order.customer.name)} | ${escapeHtml(order.customer.phone)} | Сумма: <strong>${order.total.toLocaleString('ru-RU')} ₽</strong>
                        </div>
                    </div>
                    <div style="display:flex; gap:0.4rem; align-items:center;" onclick="event.stopPropagation();">
                        <select class="order-status ${selectClass}" data-id="${order.id}" onchange="changeOrderStatus(${order.id}, this.value)">
                            <option ${order.status==='Новый'?'selected':''}>Новый</option>
                            <option ${order.status==='В обработке'?'selected':''}>В обработке</option>
                            <option ${order.status==='Отправлен'?'selected':''}>Отправлен</option>
                            <option ${order.status==='Выполнен'?'selected':''}>Выполнен</option>
                        </select>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteOrder(${order.id})">🗑</button>
                    </div>
                </div>
                <div class="order-details" id="order-details-${order.id}" style="display:none;">
                    <h4>📋 Контактные данные</h4>
                    <table>
                        <tr><td>Имя:</td><td>${escapeHtml(order.customer.name || '—')}</td></tr>
                        <tr><td>Телефон:</td><td><a href="tel:${escapeHtml(order.customer.phone || '')}">${escapeHtml(order.customer.phone || '—')}</a></td></tr>
                        <tr><td>Email:</td><td>${order.customer.email ? `<a href="mailto:${escapeHtml(order.customer.email)}">${escapeHtml(order.customer.email)}</a>` : '—'}</td></tr>
                        <tr><td>Адрес:</td><td>${escapeHtml(order.customer.address || '—')}</td></tr>
                        <tr><td>Комментарий:</td><td>${escapeHtml(order.customer.comment || '—')}</td></tr>
                    </table>
                    <h4>🛒 Состав заказа</h4>
                    <ul>${itemsHtml}</ul>
                    <div class="order-total">
                        <strong>Итого: ${order.total.toLocaleString('ru-RU')} ₽</strong>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    window.toggleOrder = function(id) {
        const details = document.getElementById('order-details-' + id);
        if (details) details.style.display = details.style.display === 'none' ? 'block' : 'none';
    };

    window.changeOrderStatus = async function(id, newStatus) {
        const order = orders.find(o => o.id === id);
        if (!order) return;
        order.status = newStatus;
        
        // Показываем, что идёт отправка (письмо может занять пару секунд)
        showStatus('⏳ Обновление статуса и отправка письма...');
        
        try {
            await fetch('/api/orders', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orders),
                credentials: 'same-origin'
            });
            showStatus('✅ Статус заказа #' + id + ' обновлён (письмо клиенту отправлено)');
            renderOrdersList();
            updateEventBadges();
        } catch (e) {
            console.error('order status error:', e);
            showStatus('❌ Ошибка обновления статуса', true);
        }
    };

    window.deleteOrder = async function(id) {
        if (confirm('Удалить заказ #' + id + '?')) {
            orders = orders.filter(o => o.id !== id);
            await fetch('/api/orders', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orders),
                credentials: 'same-origin'
            });
            renderOrdersList();
            updateEventBadges();
            showStatus('Заказ удалён');
        }
    };

    function renderAllLists() {
        renderList('products-list', products, 'product');
        renderList('blog-list', blog, 'blog');
        renderList('news-list', news, 'news');
        renderList('faq-list', faq, 'faq');
        renderList('promotions-list', promotions, 'promotion');
        renderList('reviews-list', reviews, 'review');
    }

    // ============================================================
    // ИНИЦИАЛИЗАЦИЯ ФИЛЬТРОВ ЗАКАЗОВ
    // ============================================================
    function initOrdersFilters() {
        const filterBar = document.getElementById('orders-filter-bar');
        if (filterBar) {
            filterBar.addEventListener('click', (e) => {
                const btn = e.target.closest('.orders-filter-btn');
                if (!btn) return;
                filterBar.querySelectorAll('.orders-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                ordersFilter = btn.dataset.filter;
                renderOrdersList();
            });
        }

        const searchInput = document.getElementById('orders-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                ordersSearch = searchInput.value;
                renderOrdersList();
            });
        }

        const sortSelect = document.getElementById('orders-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                ordersSort = sortSelect.value;
                renderOrdersList();
            });
        }
    }

    // ============================================================
    // ОЧИСТКА ФОРМ
    // ============================================================
    window.clearForm = function(type) {
        if (type === 'product') {
            document.getElementById('prod-id').value = '';
            document.getElementById('prod-name').value = '';
            document.getElementById('prod-seo-title').value = '';
            document.getElementById('prod-slug').value = '';
            document.getElementById('prod-latin').value = '';
            document.getElementById('prod-category').value = 'culinary';
            document.getElementById('prod-price').value = '';
            document.getElementById('prod-sku').value = '';
            document.getElementById('prod-image-file').value = '';
            document.getElementById('prod-image-url').value = '';
            document.getElementById('prod-alt').value = '';
            document.getElementById('prod-emoji').value = '';
            document.getElementById('prod-desc').value = '';
            document.getElementById('prod-gradient').value = '';
            document.getElementById('prod-keywords').value = '';
            if (jQuery.fn.summernote) $('#prod-details').summernote('code', '');
            else document.getElementById('prod-details').value = '';
        } else if (type === 'blog') {
            document.getElementById('blog-id').value = '';
            document.getElementById('blog-title').value = '';
            document.getElementById('blog-slug').value = '';
            document.getElementById('blog-excerpt').value = '';
            document.getElementById('blog-image-file').value = '';
            document.getElementById('blog-image-url').value = '';
            document.getElementById('blog-alt').value = '';
            document.getElementById('blog-emoji').value = '';
            document.getElementById('blog-date').value = '';
            document.getElementById('blog-gradient').value = '';
            document.getElementById('blog-keywords').value = '';
            if (jQuery.fn.summernote) $('#blog-content').summernote('code', '');
            else document.getElementById('blog-content').value = '';
        } else if (type === 'news') {
            document.getElementById('news-id').value = '';
            document.getElementById('news-title').value = '';
            document.getElementById('news-slug').value = '';
            document.getElementById('news-excerpt').value = '';
            document.getElementById('news-date').value = '';
            document.getElementById('news-keywords').value = '';
            if (jQuery.fn.summernote) $('#news-content').summernote('code', '');
            else document.getElementById('news-content').value = '';
        } else if (type === 'faq') {
            document.getElementById('faq-id').value = '';
            document.getElementById('faq-question').value = '';
            if (jQuery.fn.summernote) $('#faq-answer').summernote('code', '');
            else document.getElementById('faq-answer').value = '';
            document.getElementById('faq-order').value = '0';
        } else if (type === 'promo') {
            document.getElementById('promo-id').value = '';
            document.getElementById('promo-title').value = '';
            document.getElementById('promo-slug').value = '';
            document.getElementById('promo-image-file').value = '';
            document.getElementById('promo-image-url').value = '';
            document.getElementById('promo-alt').value = '';
            document.getElementById('promo-start-date').value = '';
            document.getElementById('promo-end-date').value = '';
            if (jQuery.fn.summernote) $('#promo-description').summernote('code', '');
            else document.getElementById('promo-description').value = '';
        } else if (type === 'review') {
            document.getElementById('review-id').value = '';
            document.getElementById('review-name').value = '';
            document.getElementById('review-rating').value = '5';
            document.getElementById('review-product').value = '';
            document.getElementById('review-text').value = '';
            document.getElementById('review-status').value = '1';
        }
    };

    // ============================================================
    // ЗАПОЛНЕНИЕ ФОРМ ДЛЯ РЕДАКТИРОВАНИЯ
    // ============================================================
    function fillForm(type, item) {
        if (type === 'product') {
            document.getElementById('prod-id').value = item.id;
            document.getElementById('prod-name').value = item.name || '';
            document.getElementById('prod-seo-title').value = item.seo_title || ''; // <-- НОВОЕ ПОЛЕ
            document.getElementById('prod-slug').value = item.slug || '';
            document.getElementById('prod-latin').value = item.latin || '';
            document.getElementById('prod-category').value = item.category || 'culinary';
            document.getElementById('prod-price').value = item.price || '';
            document.getElementById('prod-sku').value = item.sku || '';
            document.getElementById('prod-image-url').value = item.image || '';
            document.getElementById('prod-alt').value = item.alt || '';
            document.getElementById('prod-emoji').value = item.emoji || '';
            document.getElementById('prod-desc').value = item.desc || '';
            document.getElementById('prod-gradient').value = item.gradient || '';
            document.getElementById('prod-keywords').value = item.keywords || '';
            if (jQuery.fn.summernote) $('#prod-details').summernote('code', item.details || '');
            else document.getElementById('prod-details').value = item.details || '';
        } else if (type === 'blog') {
            document.getElementById('blog-id').value = item.id;
            document.getElementById('blog-title').value = item.title || '';
            document.getElementById('blog-slug').value = item.slug || '';
            document.getElementById('blog-excerpt').value = item.excerpt || '';
            document.getElementById('blog-image-url').value = item.image || '';
            document.getElementById('blog-alt').value = item.alt || '';
            document.getElementById('blog-emoji').value = item.emoji || '';
            document.getElementById('blog-date').value = item.date || '';
            document.getElementById('blog-gradient').value = item.gradient || '';
            document.getElementById('blog-keywords').value = item.keywords || '';
            if (jQuery.fn.summernote) $('#blog-content').summernote('code', item.content || '');
            else document.getElementById('blog-content').value = item.content || '';
        } else if (type === 'news') {
            document.getElementById('news-id').value = item.id;
            document.getElementById('news-title').value = item.title || '';
            document.getElementById('news-slug').value = item.slug || '';
            document.getElementById('news-excerpt').value = item.excerpt || '';
            document.getElementById('news-date').value = item.date || '';
            document.getElementById('news-keywords').value = item.keywords || '';
            if (jQuery.fn.summernote) $('#news-content').summernote('code', item.content || '');
            else document.getElementById('news-content').value = item.content || '';
        } else if (type === 'faq') {
            document.getElementById('faq-id').value = item.id;
            document.getElementById('faq-question').value = item.question || '';
            if (jQuery.fn.summernote) $('#faq-answer').summernote('code', item.answer || '');
            else document.getElementById('faq-answer').value = item.answer || '';
            document.getElementById('faq-order').value = item.order || 0;
        } else if (type === 'promotion') {
            document.getElementById('promo-id').value = item.id;
            document.getElementById('promo-title').value = item.title || '';
            document.getElementById('promo-slug').value = item.slug || '';
            document.getElementById('promo-image-url').value = item.image || '';
            document.getElementById('promo-alt').value = item.alt || '';
            document.getElementById('promo-start-date').value = item.start_date || '';
            document.getElementById('promo-end-date').value = item.end_date || '';
            if (jQuery.fn.summernote) $('#promo-description').summernote('code', item.description || '');
            else document.getElementById('promo-description').value = item.description || '';
        } else if (type === 'review') {
            document.getElementById('review-id').value = item.id;
            document.getElementById('review-name').value = item.name || '';
            document.getElementById('review-rating').value = item.rating || 5;
            document.getElementById('review-product').value = item.product || '';
            document.getElementById('review-text').value = item.text || '';
            document.getElementById('review-status').value = (item.status === undefined ? 1 : item.status);
        }
    }

    // ============================================================
    // ОТПРАВКА ФОРМ
    // ============================================================
    document.getElementById('product-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (isImageUploading()) return;
        const id = document.getElementById('prod-id').value;
        const details = jQuery.fn.summernote ? $('#prod-details').summernote('code') : document.getElementById('prod-details').value;
        const item = {
            id: id ? parseInt(id) : getNextId(products),
            name: document.getElementById('prod-name').value,
            seo_title: document.getElementById('prod-seo-title').value, // <-- НОВОЕ ПОЛЕ
            slug: document.getElementById('prod-slug').value,
            latin: document.getElementById('prod-latin').value,
            category: document.getElementById('prod-category').value,
            price: parseInt(document.getElementById('prod-price').value) || 0,
            sku: document.getElementById('prod-sku').value,
            image: document.getElementById('prod-image-url').value,
            alt: document.getElementById('prod-alt').value,
            emoji: document.getElementById('prod-emoji').value,
            desc: document.getElementById('prod-desc').value,
            gradient: document.getElementById('prod-gradient').value,
            details: details,
            keywords: document.getElementById('prod-keywords').value
        };
        const newArr = id ? products.map(p => p.id === item.id ? item : p) : [...products, item];
        await saveData('products', newArr);
        clearForm('product');
    });

    document.getElementById('blog-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (isImageUploading()) return;
        const id = document.getElementById('blog-id').value;
        const content = jQuery.fn.summernote ? $('#blog-content').summernote('code') : document.getElementById('blog-content').value;
        const item = {
            id: id ? parseInt(id) : getNextId(blog),
            title: document.getElementById('blog-title').value,
            slug: document.getElementById('blog-slug').value,
            excerpt: document.getElementById('blog-excerpt').value,
            image: document.getElementById('blog-image-url').value,
            alt: document.getElementById('blog-alt').value,
            emoji: document.getElementById('blog-emoji').value,
            date: document.getElementById('blog-date').value,
            gradient: document.getElementById('blog-gradient').value,
            content: content,
            keywords: document.getElementById('blog-keywords').value
        };
        const newArr = id ? blog.map(b => b.id === item.id ? item : b) : [...blog, item];
        await saveData('blog', newArr);
        clearForm('blog');
    });

    document.getElementById('news-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (isImageUploading()) return;
        const id = document.getElementById('news-id').value;
        const content = jQuery.fn.summernote ? $('#news-content').summernote('code') : document.getElementById('news-content').value;
        const item = {
            id: id ? parseInt(id) : getNextId(news),
            title: document.getElementById('news-title').value,
            slug: document.getElementById('news-slug').value,
            excerpt: document.getElementById('news-excerpt').value,
            date: document.getElementById('news-date').value,
            content: content,
            keywords: document.getElementById('news-keywords').value
        };
        const newArr = id ? news.map(n => n.id === item.id ? item : n) : [...news, item];
        await saveData('news', newArr);
        clearForm('news');
    });

    document.getElementById('faq-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (isImageUploading()) return;
        const id = document.getElementById('faq-id').value;
        const answer = jQuery.fn.summernote ? $('#faq-answer').summernote('code') : document.getElementById('faq-answer').value;
        const item = {
            id: id ? parseInt(id) : getNextId(faq),
            question: document.getElementById('faq-question').value,
            answer: answer,
            order: parseInt(document.getElementById('faq-order').value) || 0
        };
        const newArr = id ? faq.map(f => f.id === item.id ? item : f) : [...faq, item];
        await saveData('faq', newArr);
        clearForm('faq');
    });

    document.getElementById('promo-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (isImageUploading()) return;
        const id = document.getElementById('promo-id').value;
        const description = jQuery.fn.summernote ? $('#promo-description').summernote('code') : document.getElementById('promo-description').value;
        const item = {
            id: id ? parseInt(id) : getNextId(promotions),
            title: document.getElementById('promo-title').value,
            slug: document.getElementById('promo-slug').value,
            image: document.getElementById('promo-image-url').value,
            alt: document.getElementById('promo-alt').value,
            start_date: document.getElementById('promo-start-date').value,
            end_date: document.getElementById('promo-end-date').value,
            description: description
        };
        const newArr = id ? promotions.map(p => p.id === item.id ? item : p) : [...promotions, item];
        await saveData('promotions', newArr);
        clearForm('promo');
    });

    document.getElementById('review-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = document.getElementById('review-id').value;
        const item = {
            id: id ? parseInt(id) : getNextId(reviews),
            name: document.getElementById('review-name').value,
            rating: parseInt(document.getElementById('review-rating').value) || 5,
            product: document.getElementById('review-product').value,
            text: document.getElementById('review-text').value,
            status: parseInt(document.getElementById('review-status').value),
            date: new Date().toISOString()
        };
        const newArr = id ? reviews.map(r => r.id === item.id ? item : r) : [...reviews, item];
        await saveData('reviews', newArr);
        clearForm('review');
    });

    // ============================================================
    // СОХРАНЕНИЕ НАСТРОЕК
    // ============================================================
    document.getElementById('settings-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const newSettings = {
            theme: document.getElementById('settings-theme').value,
            site_name: document.getElementById('settings-site-name').value,
            site_seo_title: document.getElementById('settings-site-seo-title').value, // <-- НОВОЕ ПОЛЕ
            site_description: document.getElementById('settings-site-desc').value,
            site_keywords: document.getElementById('settings-site-keywords').value,
            phone: document.getElementById('settings-phone').value,
            email: document.getElementById('settings-email').value,
            address: document.getElementById('settings-address').value,
            hours: document.getElementById('settings-hours').value,
            telegram: document.getElementById('settings-telegram').value,
            vk: document.getElementById('settings-vk').value,
            youtube: document.getElementById('settings-youtube').value,
            max: document.getElementById('settings-max').value,
            logo_url: document.getElementById('settings-logo-url').value,
            favicon_url: document.getElementById('settings-favicon-url').value,
            hero_premium: document.getElementById('settings-hero-premium').value,
            hero_title: document.getElementById('settings-hero-title').value,
            hero_subtitle: document.getElementById('settings-hero-subtitle').value,
            hero_btn_catalog: document.getElementById('settings-hero-btn-catalog').value,
            hero_btn_blog: document.getElementById('settings-hero-btn-blog').value,
            advantages_title: document.getElementById('settings-advantages-title').value,
            adv1_icon: document.getElementById('settings-adv1-icon').value,
            adv1_title: document.getElementById('settings-adv1-title').value,
            adv1_desc: document.getElementById('settings-adv1-desc').value,
            adv2_icon: document.getElementById('settings-adv2-icon').value,
            adv2_title: document.getElementById('settings-adv2-title').value,
            adv2_desc: document.getElementById('settings-adv2-desc').value,
            adv3_icon: document.getElementById('settings-adv3-icon').value,
            adv3_title: document.getElementById('settings-adv3-title').value,
            adv3_desc: document.getElementById('settings-adv3-desc').value,
            adv4_icon: document.getElementById('settings-adv4-icon').value,
            adv4_title: document.getElementById('settings-adv4-title').value,
            adv4_desc: document.getElementById('settings-adv4-desc').value,
            footer_description: document.getElementById('settings-footer-description').value,
            ga_id: document.getElementById('settings-ga-id').value,
            ym_id: document.getElementById('settings-ym-id').value,
            gsc_tag: document.getElementById('settings-gsc-tag').value,
            ywm_tag: document.getElementById('settings-ywm-tag').value,
            robots: document.getElementById('settings-robots').value,
            age_check_enabled: document.getElementById('settings-age-check-enabled').value
        };
        try {
            const res = await fetch('/api/settings', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newSettings),
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            showStatus('✅ Настройки сохранены');
            document.documentElement.setAttribute('data-theme', newSettings.theme);
        } catch (e) {
            console.error('settings save error:', e);
            showStatus('❌ Ошибка сохранения настроек', true);
        }
    });

    // ============================================================
    // СМЕНА ПАРОЛЯ
    // ============================================================
    document.getElementById('password-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const currentPwd = document.getElementById('password-current').value;
        const newPwd = document.getElementById('password-new').value;
        const confirmPwd = document.getElementById('password-confirm').value;

        if (!currentPwd || !newPwd || !confirmPwd) { showStatus('⚠️ Заполните все поля', true); return; }
        if (newPwd.length < 8) { showStatus('⚠️ Новый пароль должен быть не короче 8 символов', true); return; }
        if (newPwd !== confirmPwd) { showStatus('⚠️ Новый пароль и подтверждение не совпадают', true); return; }
        if (newPwd === currentPwd) { showStatus('⚠️ Новый пароль должен отличаться от текущего', true); return; }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Сохранение...';

        try {
            const res = await fetch('/api/change-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: currentPwd, new_password: newPwd, new_password_confirm: confirmPwd }),
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Ошибка');
            showStatus('✅ Пароль успешно изменён');
            document.getElementById('password-current').value = '';
            document.getElementById('password-new').value = '';
            document.getElementById('password-confirm').value = '';
            const warn = document.getElementById('default-password-warning');
            if (warn) warn.remove();
        } catch (err) {
            console.error('password change error:', err);
            showStatus('❌ ' + err.message, true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });

    // ============================================================
    // МОДАЛКА УДАЛЕНИЯ
    // ============================================================
    confirmDeleteBtn.addEventListener('click', async () => {
        const { type, id } = deleteContext;
        if (!type || !id) { deleteModal.classList.remove('open'); return; }

        let arr;
        if (type === 'product')        arr = products.filter(p => p.id !== id);
        else if (type === 'blog')      arr = blog.filter(b => b.id !== id);
        else if (type === 'news')      arr = news.filter(n => n.id !== id);
        else if (type === 'faq')       arr = faq.filter(f => f.id !== id);
        else if (type === 'promotion') arr = promotions.filter(p => p.id !== id);
        else if (type === 'review')    arr = reviews.filter(r => r.id !== id);
        else { alert('Неизвестный тип: ' + type); deleteModal.classList.remove('open'); return; }

        await saveData(type, arr);
        deleteModal.classList.remove('open');
        deleteContext = { type: null, id: null };
    });

    cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.remove('open'));
    deleteModal.addEventListener('click', (e) => { if (e.target === deleteModal) deleteModal.classList.remove('open'); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && deleteModal.classList.contains('open')) deleteModal.classList.remove('open'); });

    // ============================================================
    // ПЕРЕКЛЮЧЕНИЕ ТАБОВ
    // ============================================================
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('panel-' + tabName).classList.add('active');
        });
    });

    // ============================================================
    // РЕЗЕРВНОЕ КОПИРОВАНИЕ
    // ============================================================
    const backupBtn = document.getElementById('backup-btn');
    if (backupBtn) {
        backupBtn.addEventListener('click', async function() {
            const statusEl = document.getElementById('backup-status');
            const originalText = backupBtn.textContent;
            backupBtn.disabled = true;
            backupBtn.textContent = '⏳ Создание архива...';
            if (statusEl) { statusEl.style.display = 'block'; statusEl.textContent = '⏳ Готовим архив, подождите...'; statusEl.style.color = 'var(--text-muted)'; }
            try {
                const res = await fetch('/backup.php', { method: 'GET', credentials: 'same-origin' });
                if (!res.ok) { const txt = await res.text().catch(() => ''); throw new Error('HTTP ' + res.status + ' ' + txt); }
                const blob = await res.blob();
                const contentDisposition = res.headers.get('Content-Disposition') || '';
                let filename = 'backup.zip';
                const match = contentDisposition.match(/filename="?([^"]+)"?/);
                if (match) filename = match[1];
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = filename;
                document.body.appendChild(a); a.click(); document.body.removeChild(a);
                URL.revokeObjectURL(url);
                const sizeMB = (blob.size / 1024 / 1024).toFixed(2);
                if (statusEl) { statusEl.textContent = '✅ Архив скачан: ' + filename + ' (' + sizeMB + ' МБ)'; statusEl.style.color = 'var(--success)'; }
                showStatus('✅ Резервная копия скачана');
            } catch (e) {
                console.error('backup error:', e);
                if (statusEl) { statusEl.textContent = '❌ Ошибка: ' + e.message; statusEl.style.color = 'var(--danger)'; }
                showStatus('❌ Ошибка резервного копирования', true);
            } finally {
                backupBtn.disabled = false;
                backupBtn.textContent = originalText;
            }
        });
    }

    // ============================================================
    // АВТОПРОВЕРКА НОВЫХ СОБЫТИЙ (60 сек)
    // ============================================================
    setInterval(async () => {
        try {
            const [revRes, ordRes] = await Promise.all([fetch('/api/reviews'), fetch('/api/orders')]);
            const newReviews = await revRes.json();
            const newOrders = await ordRes.json();
            const prevReviews = getNewReviewsCount();
            const prevOrders = getNewOrdersCount();
            reviews = newReviews;
            orders = newOrders;
            const currentReviews = getNewReviewsCount();
            const currentOrders = getNewOrdersCount();
            if (currentReviews > prevReviews) showStatus('🆕 Новый отзыв на модерации!');
            if (currentOrders > prevOrders) showStatus('🆕 Новый заказ!');
            updateEventBadges();
            renderAllLists();
            renderOrdersList();
        } catch (e) { console.error('poll error:', e); }
    }, 60000);

    // ============================================================
    // СТАРТ
    // ============================================================
    initOrdersFilters();
    loadData();
});