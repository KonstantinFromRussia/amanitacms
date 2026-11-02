(function() {
    window.products = [];
    window.blogArticles = [];
    window.newsItems = [];

    let cart = JSON.parse(localStorage.getItem('muhomor_cart')) || [];

    // ============================================================
    // ПЕРЕКЛЮЧЕНИЕ ТЕМЫ
    // ============================================================
    window.toggleTheme = function() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme') || 'dark';
        const next = current === 'dark' ? 'light' : (current === 'light' ? 'forest' : 'dark');
        html.setAttribute('data-theme', next);
        localStorage.setItem('amanita_theme', next);
    };

    // Применяем сохранённую тему при загрузке (если отличается от серверной)
    (function() {
        const saved = localStorage.getItem('amanita_theme');
        if (saved && saved !== document.documentElement.getAttribute('data-theme')) {
            document.documentElement.setAttribute('data-theme', saved);
        }
    })();

    // ========== НАСТРОЙКИ ПАГИНАЦИИ ==========
    const PER_PAGE = {
        catalog: 12,
        blog: 6,
        news: 6
    };

    // ========== WEBP-ПОДСТАНОВКА ==========
    function buildPicture(imageUrl, alt, cls, loading) {
        if (!imageUrl) return '';
        alt = alt || '';
        cls = cls || '';
        loading = loading || 'lazy';

        const altEsc = String(alt).replace(/"/g, '&quot;');
        const classAttr = cls ? ` class="${cls}"` : '';
        const loadingAttr = loading ? ` loading="${loading}"` : '';

        const webpUrl = imageUrl.replace(/\.(jpe?g|png)$/i, '.webp');
        if (webpUrl === imageUrl) {
            return `<img src="${imageUrl}" alt="${altEsc}"${classAttr}${loadingAttr}>`;
        }
        return `<picture>`
            + `<source srcset="${webpUrl}" type="image/webp">`
            + `<img src="${imageUrl}" alt="${altEsc}"${classAttr}${loadingAttr}>`
            + `</picture>`;
    }

    // ========== ХЕЛПЕРЫ URL ==========
    function getPageParam() {
        return Math.max(1, parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10));
    }

    function setPageParam(page) {
        const url = new URL(window.location.href);
        if (page <= 1) url.searchParams.delete('page');
        else url.searchParams.set('page', page);
        window.history.pushState({}, '', url.toString());
    }

    function syncCatalogUrl() {
        const url = new URL(window.location.href);
        const search = document.getElementById('search-input')?.value || '';
        const category = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
        const sortSelect = document.getElementById('sort-select')?.value || 'id_asc';
        const minPrice = document.getElementById('min-price')?.value || '';
        const maxPrice = document.getElementById('max-price')?.value || '';

        ['search', 'category', 'sort', 'min_price', 'max_price', 'page'].forEach(k => url.searchParams.delete(k));

        if (search) url.searchParams.set('search', search);
        if (category !== 'all') url.searchParams.set('category', category);
        if (sortSelect !== 'id_asc') url.searchParams.set('sort', sortSelect);
        if (minPrice) url.searchParams.set('min_price', minPrice);
        if (maxPrice) url.searchParams.set('max_price', maxPrice);

        window.history.pushState({}, '', url.toString());
    }

    // ========== ПАГИНАЦИЯ ==========
    function renderPagination(container, currentPage, totalItems, perPage, basePath, onNavigate) {
        if (!container) return;
        const totalPages = Math.ceil(totalItems / perPage);

        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        function pageUrl(n) {
            const url = new URL(window.location.origin + basePath);
            const current = new URLSearchParams(window.location.search);
            current.forEach((val, key) => {
                if (key !== 'page') url.searchParams.set(key, val);
            });
            if (n > 1) url.searchParams.set('page', n);
            return url.pathname + (url.search !== '' ? url.search : '');
        }

        const pages = [];
        const delta = 2;
        const rangeStart = Math.max(2, currentPage - delta);
        const rangeEnd   = Math.min(totalPages - 1, currentPage + delta);

        pages.push(1);
        if (rangeStart > 2) pages.push('...');
        for (let i = rangeStart; i <= rangeEnd; i++) pages.push(i);
        if (rangeEnd < totalPages - 1) pages.push('...');
        if (totalPages > 1) pages.push(totalPages);

        let html = '<div class="pagination">';

        if (currentPage > 1) {
            html += `<a href="${pageUrl(currentPage - 1)}" class="pagination__link" data-page="${currentPage - 1}" rel="prev" aria-label="Предыдущая страница">← Назад</a>`;
        } else {
            html += `<span class="pagination__link pagination__link--disabled" aria-disabled="true">← Назад</span>`;
        }

        pages.forEach(p => {
            if (p === '...') {
                html += `<span class="pagination__dots">…</span>`;
            } else if (p === currentPage) {
                html += `<span class="pagination__link pagination__link--active" aria-current="page">${p}</span>`;
            } else {
                html += `<a href="${pageUrl(p)}" class="pagination__link" data-page="${p}">${p}</a>`;
            }
        });

        if (currentPage < totalPages) {
            html += `<a href="${pageUrl(currentPage + 1)}" class="pagination__link" data-page="${currentPage + 1}" rel="next" aria-label="Следующая страница">Вперёд →</a>`;
        } else {
            html += `<span class="pagination__link pagination__link--disabled" aria-disabled="true">Вперёд →</span>`;
        }

        html += '</div>';
        container.innerHTML = html;

        container.querySelectorAll('a.pagination__link').forEach(a => {
            a.addEventListener('click', function(e) {
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
                e.preventDefault();
                const page = parseInt(this.dataset.page, 10);
                setPageParam(page);
                onNavigate(page);
                const target = container.previousElementSibling;
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    // ========== КАРТОЧКИ ==========
    function createProductCard(p) {
        const slug = p.slug || 'product-' + p.id;
        const inCart = findInCart(p.id);
        const altText = p.alt || p.name || 'Изображение товара';
        const imageHtml = p.image
            ? buildPicture(p.image, altText, '', 'lazy').replace(
                  /<img /,
                  '<img style="width:100%;height:100%;object-fit:cover;" '
              ).replace(
                  /<picture>/,
                  '<picture style="display:block;width:100%;height:100%;">'
              )
            : `<span class="product-card__image-placeholder" style="font-size:5rem;">${p.emoji}</span>`;
        return `
            <div class="product-card" data-category="${p.category}">
                <a href="/product/${slug}.html" class="product-card__image" style="background:${p.gradient}; display:block; overflow:hidden;">
                    ${imageHtml}
                </a>
                <div class="p-4 flex flex-col flex-1 gap-2">
                    <span class="text-xs text-[var(--accent)] font-medium uppercase tracking-wide">${p.latin || ''}</span>
                    <a href="/product/${slug}.html" class="no-underline"><h3 class="font-semibold text-[var(--text-primary)] leading-tight">${p.name}</h3></a>
                    <p class="text-xs text-[var(--text-secondary)] flex-1">${p.desc || ''}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-lg font-bold text-[var(--accent)]">${p.price.toLocaleString('ru-RU')} ₽</span>
                        <button class="btn-primary btn-sm" onclick="event.preventDefault(); event.stopPropagation(); window.addToCart(${p.id});">
                            🛒 В корзину ${inCart ? `<span style="font-size:0.7rem;opacity:0.8;">(${inCart.qty})</span>` : ''}
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function createBlogCard(a) {
        const slug = a.slug || 'blog-' + a.id;
        const altText = a.alt || a.title || 'Изображение статьи';
        const thumbInner = a.image
            ? buildPicture(a.image, altText, '', 'lazy').replace(
                  /<img /,
                  '<img style="width:100%;height:100%;object-fit:cover;" '
              ).replace(
                  /<picture>/,
                  '<picture style="display:block;width:100%;height:100%;">'
              )
            : `<span>${a.emoji || ''}</span>`;
        return `
            <div class="article-card">
                <a href="/blog/${slug}.html" class="article-card__thumb" style="overflow:hidden;">${thumbInner}</a>
                <div class="p-5 flex flex-col flex-1 gap-2">
                    <span class="text-xs text-[var(--text-muted)]">${a.date || ''}</span>
                    <a href="/blog/${slug}.html" class="no-underline"><h3 class="font-semibold text-[var(--text-primary)] leading-snug">${a.title}</h3></a>
                    <p class="text-sm text-[var(--text-secondary)] flex-1">${a.excerpt || ''}</p>
                    <a href="/blog/${slug}.html" class="text-[var(--accent)] text-sm font-medium hover:underline">Читать дальше →</a>
                </div>
            </div>`;
    }

    function createNewsCard(n) {
        const slug = n.slug || 'news-' + n.id;
        return `
            <div class="article-card p-5 flex flex-col gap-3">
                <span class="text-xs text-[var(--text-muted)]">${n.date || ''}</span>
                <a href="/news/${slug}.html" class="no-underline"><h3 class="font-semibold text-[var(--text-primary)] leading-snug">${n.title}</h3></a>
                <p class="text-sm text-[var(--text-secondary)] flex-1">${n.excerpt || ''}</p>
                <a href="/news/${slug}.html" class="text-[var(--accent)] text-sm font-medium hover:underline">Подробнее →</a>
            </div>`;
    }

    // ========== РЕНДЕР КАТАЛОГА ==========
    async function renderCatalogGrid(page) {
        const g = document.getElementById('catalogGrid');
        const pag = document.getElementById('catalogPagination');
        if (!g) return;

        const category = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
        const search = document.getElementById('search-input')?.value || '';
        const sortSelect = document.getElementById('sort-select')?.value || 'id_asc';
        let sort_by = 'id', order = 'asc';
        if (sortSelect === 'price_asc') { sort_by = 'price'; order = 'asc'; }
        else if (sortSelect === 'price_desc') { sort_by = 'price'; order = 'desc'; }
        else if (sortSelect === 'name_asc') { sort_by = 'name'; order = 'asc'; }
        const min_price = document.getElementById('min-price')?.value || '';
        const max_price = document.getElementById('max-price')?.value || '';

        const perPage = PER_PAGE.catalog;
        const offset = (page - 1) * perPage;

        const params = new URLSearchParams();
        params.set('limit', perPage);
        params.set('offset', offset);
        if (category !== 'all') params.set('category', category);
        if (search) params.set('search', search);
        if (sort_by !== 'id') { params.set('sort_by', sort_by); params.set('order', order); }
        if (min_price) params.set('min_price', min_price);
        if (max_price) params.set('max_price', max_price);

        try {
            const res = await fetch('/api/products?' + params.toString());
            const data = await res.json();

            let items, total;
            if (Array.isArray(data)) {
                items = data.slice(offset, offset + perPage);
                total = data.length;
            } else {
                items = data.items || [];
                total = data.total || 0;
            }

            if (!items.length) {
                g.innerHTML = '<div class="col-span-full text-center py-12 text-[var(--text-muted)]">Ничего не найдено</div>';
                if (pag) pag.innerHTML = '';
                return;
            }

            g.innerHTML = items.map(createProductCard).join('');

            g.querySelectorAll('.product-card').forEach((c, i) => {
                c.style.opacity = '0';
                c.style.transform = 'translateY(20px)';
                c.style.transition = `opacity 0.4s ease ${i * 0.05}s, transform 0.4s ease ${i * 0.05}s`;
                requestAnimationFrame(() => { c.style.opacity = '1'; c.style.transform = 'translateY(0)'; });
            });

            renderPagination(pag, page, total, perPage, '/catalog', (newPage) => {
                renderCatalogGrid(newPage);
            });
        } catch (e) {
            console.error('catalog load error:', e);
            g.innerHTML = '<div class="col-span-full text-center py-12 text-[var(--danger)]">Ошибка загрузки каталога</div>';
        }
    }

    // ========== РЕНДЕР БЛОГА ==========
    async function renderBlogFull(page) {
        const g = document.getElementById('blogFullGrid');
        const pag = document.getElementById('blogPagination');
        if (!g) return;

        const perPage = PER_PAGE.blog;
        const offset = (page - 1) * perPage;

        try {
            const res = await fetch(`/api/blog?limit=${perPage}&offset=${offset}`);
            const data = await res.json();

            let items, total;
            if (Array.isArray(data)) {
                items = data.slice(offset, offset + perPage);
                total = data.length;
            } else {
                items = data.items || [];
                total = data.total || 0;
            }

            if (!items.length) {
                g.innerHTML = '<div class="col-span-full text-center py-12 text-[var(--text-muted)]">Пока нет статей</div>';
                if (pag) pag.innerHTML = '';
                return;
            }

            const sorted = [...items].reverse();
            g.innerHTML = sorted.map(createBlogCard).join('');

            renderPagination(pag, page, total, perPage, '/blog', (newPage) => {
                renderBlogFull(newPage);
            });
        } catch (e) {
            console.error('blog load error:', e);
        }
    }

    // ========== РЕНДЕР НОВОСТЕЙ ==========
    async function renderNewsFull(page) {
        const g = document.getElementById('newsFullGrid');
        const pag = document.getElementById('newsPagination');
        if (!g) return;

        const perPage = PER_PAGE.news;
        const offset = (page - 1) * perPage;

        try {
            const res = await fetch(`/api/news?limit=${perPage}&offset=${offset}`);
            const data = await res.json();

            let items, total;
            if (Array.isArray(data)) {
                items = data.slice(offset, offset + perPage);
                total = data.length;
            } else {
                items = data.items || [];
                total = data.total || 0;
            }

            if (!items.length) {
                g.innerHTML = '<div class="col-span-full text-center py-12 text-[var(--text-muted)]">Пока нет новостей</div>';
                if (pag) pag.innerHTML = '';
                return;
            }

            const sorted = [...items].reverse();
            g.innerHTML = sorted.map(createNewsCard).join('');

            renderPagination(pag, page, total, perPage, '/news', (newPage) => {
                renderNewsFull(newPage);
            });
        } catch (e) {
            console.error('news load error:', e);
        }
    }

    // ========== ГЛАВНАЯ ==========
    function renderFeaturedProducts() {
        const g = document.getElementById('featuredGrid');
        if (g && window.products.length) {
            const sorted = [...window.products].reverse();
            g.innerHTML = sorted.slice(0, 4).map(createProductCard).join('');
        }
    }
    function renderBlogPreview() {
        const g = document.getElementById('blogPreviewGrid');
        if (g && window.blogArticles.length) {
            const sorted = [...window.blogArticles].reverse();
            g.innerHTML = sorted.slice(0, 3).map(createBlogCard).join('');
        }
    }
    function renderNewsPreview() {
        const g = document.getElementById('newsPreviewGrid');
        if (g && window.newsItems.length) {
            const sorted = [...window.newsItems].reverse();
            g.innerHTML = sorted.slice(0, 3).map(createNewsCard).join('');
        }
    }

    // ========== ФИЛЬТРЫ КАТАЛОГА ==========
    function initCatalogFilters() {
        const cont = document.getElementById('catalogFilters');
        if (!cont) return;

        const urlParams = new URLSearchParams(window.location.search);
        const searchParam   = urlParams.get('search');
        const categoryParam = urlParams.get('category');
        const sortParam     = urlParams.get('sort');
        const minPriceParam = urlParams.get('min_price');
        const maxPriceParam = urlParams.get('max_price');

        if (searchParam) {
            const inp = document.getElementById('search-input');
            if (inp) inp.value = searchParam;
        }
        if (categoryParam) {
            const btn = document.querySelector(`.filter-btn[data-filter="${categoryParam}"]`);
            if (btn) {
                cont.querySelectorAll('.filter-btn').forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline');
                });
                btn.classList.remove('btn-outline');
                btn.classList.add('btn-primary', 'active');
            }
        }
        if (sortParam) {
            const sel = document.getElementById('sort-select');
            if (sel && ['id_asc', 'price_asc', 'price_desc', 'name_asc'].includes(sortParam)) {
                sel.value = sortParam;
            }
        }
        if (minPriceParam) {
            const inp = document.getElementById('min-price');
            if (inp) inp.value = minPriceParam;
        }
        if (maxPriceParam) {
            const inp = document.getElementById('max-price');
            if (inp) inp.value = maxPriceParam;
        }

        cont.addEventListener('click', e => {
            const btn = e.target.closest('.filter-btn');
            if (!btn) return;
            cont.querySelectorAll('.filter-btn').forEach(b => { b.classList.remove('btn-primary', 'active'); b.classList.add('btn-outline'); });
            btn.classList.remove('btn-outline'); btn.classList.add('btn-primary', 'active');
            setPageParam(1);
            syncCatalogUrl();
            renderCatalogGrid(1);
        });

        const applyBtn = document.getElementById('apply-filters');
        if (applyBtn) applyBtn.addEventListener('click', () => {
            setPageParam(1);
            syncCatalogUrl();
            renderCatalogGrid(1);
        });

        const resetBtn = document.getElementById('reset-filters');
        if (resetBtn) resetBtn.addEventListener('click', () => {
            document.getElementById('search-input').value = '';
            document.getElementById('sort-select').value = 'id_asc';
            document.getElementById('min-price').value = '';
            document.getElementById('max-price').value = '';
            cont.querySelectorAll('.filter-btn').forEach(b => { b.classList.remove('btn-primary', 'active'); b.classList.add('btn-outline'); });
            const allBtn = cont.querySelector('.filter-btn[data-filter="all"]');
            if (allBtn) { allBtn.classList.remove('btn-outline'); allBtn.classList.add('btn-primary', 'active'); }
            setPageParam(1);
            syncCatalogUrl();
            renderCatalogGrid(1);
        });

        const searchInput = document.getElementById('search-input');
        if (searchInput) searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                setPageParam(1);
                syncCatalogUrl();
                renderCatalogGrid(1);
            }
        });
    }

    // ========== КОРЗИНА ==========
    window.toggleCart = function() {
        const s = document.getElementById('cartSidebar');
        const o = document.getElementById('cartOverlay');
        s.classList.toggle('open');
        o.classList.toggle('open');
        document.body.style.overflow = s.classList.contains('open') ? 'hidden' : '';
    };
    window.toggleMobileMenu = function() {
        const m = document.getElementById('mobileMenu');
        const o = document.getElementById('mobileMenuOverlay');
        m.classList.toggle('open');
        o.classList.toggle('open');
        document.body.style.overflow = m.classList.contains('open') ? 'hidden' : '';
    };
    window.closeOrderModal = function() {
        document.getElementById('order-modal-overlay').classList.remove('open');
        document.getElementById('order-modal').classList.remove('open');
        document.body.style.overflow = '';
    };
    window.checkout = function() {
        if (!cart.length) return;
        document.getElementById('order-modal-overlay').classList.add('open');
        document.getElementById('order-modal').classList.add('open');
        document.body.style.overflow = 'hidden';
    };
    window.clearCart = function() {
        if (!cart.length) return;
        cart = [];
        saveCart();
        showToast('🧹 Корзина очищена');
        document.getElementById('cartSidebar').classList.remove('open');
        document.getElementById('cartOverlay').classList.remove('open');
    };
    window.addToCart = function(id) {
        const p = window.products.find(x => x.id === id);
        if (!p) return;
        const exist = findInCart(id);
        exist ? exist.qty++ : cart.push({...p, qty: 1});
        saveCart();
        showToast(`✅ "${p.name}" добавлен в корзину`);
        const sidebar = document.getElementById('cartSidebar');
        const overlay = document.getElementById('cartOverlay');
        if (!sidebar.classList.contains('open')) {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            setTimeout(() => {
                if (sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('open');
                }
            }, 1500);
        }
    };
    window.changeQty = function(id, d) {
        const item = findInCart(id);
        if (!item) return;
        item.qty += d;
        if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
        saveCart();
    };
    window.removeFromCart = function(id) {
        const item = findInCart(id);
        if (item) {
            cart = cart.filter(i => i.id !== id);
            saveCart();
            showToast(`🗑 "${item.name}" удалён`);
        }
    };
    window.submitOrder = async function() {
        const name = document.getElementById('order-name').value.trim();
        const phone = document.getElementById('order-phone').value.trim();
        const email = document.getElementById('order-email').value.trim();
        const address = document.getElementById('order-address').value.trim();
        const comment = document.getElementById('order-comment').value.trim();
        if (!name || !phone) { showToast('⚠️ Заполните имя и телефон'); return; }

        const order = {
            customer: { name, phone, email, address, comment },
            items: cart.map(i => ({ id: i.id, name: i.name, price: i.price, qty: i.qty })),
            total: getCartTotal()
        };
        try {
            const res = await fetch('/api/orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(order)
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Ошибка');
            showToast('✅ Заказ успешно оформлен!');
            cart = [];
            saveCart();
            window.closeOrderModal();
            document.getElementById('cartSidebar').classList.remove('open');
            document.getElementById('cartOverlay').classList.remove('open');
            document.body.style.overflow = '';
        } catch (e) {
            showToast('❌ Не удалось оформить заказ');
            console.error(e);
        }
    };

    function saveCart() {
        localStorage.setItem('muhomor_cart', JSON.stringify(cart));
        updateCartUI();
    }
    function getCartTotal() { return cart.reduce((sum, i) => sum + i.price * i.qty, 0); }
    function getCartCount() { return cart.reduce((sum, i) => sum + i.qty, 0); }
    function findInCart(id) { return cart.find(i => i.id === id); }

    function updateCartUI() {
        const count = getCartCount();
        const badge = document.getElementById('cartBadge');
        const mobileCount = document.getElementById('mobileCartCount');
        const cartFooter = document.getElementById('cartFooter');
        const container = document.getElementById('cartItemsContainer');
        const totalEl = document.getElementById('cartTotal');

        if (count > 0) {
            badge.style.display = 'flex';
            badge.textContent = count;
            badge.classList.add('bump');
            setTimeout(() => badge.classList.remove('bump'), 300);
        } else {
            badge.style.display = 'none';
        }
        if (mobileCount) mobileCount.textContent = count > 0 ? `(${count})` : '';

        if (cart.length === 0) {
            container.innerHTML = '<p class="text-[var(--text-muted)] text-center mt-12">Ваша корзина пока пуста 🍄</p>';
            cartFooter.style.display = 'none';
        } else {
            cartFooter.style.display = 'block';
            container.innerHTML = cart.map(item => `
                <div class="flex items-center gap-3 py-3 border-b border-[var(--border)]">
                    <div class="w-12 h-12 rounded-[var(--radius-sm)] flex items-center justify-center text-2xl shrink-0"
                         style="background:${item.gradient || '#1a1a1a'};overflow:hidden;">
                        ${item.image
                            ? buildPicture(item.image, item.alt || item.name, '', 'lazy').replace(
                                /<img /,
                                '<img style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-sm)" '
                              ).replace(
                                /<picture>/,
                                '<picture style="display:block;width:100%;height:100%;">'
                              )
                            : item.emoji}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-[var(--text-primary)] truncate">${item.name}</p>
                        <p class="text-xs text-[var(--text-muted)]">${item.price} ₽ × ${item.qty}</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button class="bg-[var(--bg-elevated)] border border-[var(--border)] text-[var(--text-primary)] w-7 h-7 rounded-full cursor-pointer text-sm flex items-center justify-center hover:border-[var(--accent)]"
                                onclick="window.changeQty(${item.id}, -1)">−</button>
                        <span class="text-sm w-6 text-center text-[var(--text-primary)]">${item.qty}</span>
                        <button class="bg-[var(--bg-elevated)] border border-[var(--border)] text-[var(--text-primary)] w-7 h-7 rounded-full cursor-pointer text-sm flex items-center justify-center hover:border-[var(--accent)]"
                                onclick="window.changeQty(${item.id}, 1)">+</button>
                    </div>
                    <button class="text-[var(--text-muted)] hover:text-[var(--danger)] bg-transparent border-none cursor-pointer text-lg"
                            onclick="window.removeFromCart(${item.id})" title="Удалить">🗑</button>
                </div>
            `).join('');
            totalEl.textContent = getCartTotal().toLocaleString('ru-RU') + ' ₽';
        }
    }

    function showToast(msg) {
        const container = document.getElementById('toastContainer');
        const div = document.createElement('div');
        div.className = 'toast';
        div.textContent = msg;
        container.appendChild(div);
        setTimeout(() => div.remove(), 2600);
    }

    function initContactForm() {
        const form = document.getElementById('contactForm');
        if (!form) return;
        form.addEventListener('submit', e => {
            e.preventDefault();
            const name = document.getElementById('contactName').value.trim();
            const email = document.getElementById('contactEmail').value.trim();
            const msg = document.getElementById('contactMessage').value.trim();
            if (!name || !email || !msg) { showToast('⚠️ Заполните все поля'); return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('⚠️ Некорректный email'); return; }
            document.getElementById('contactSuccess').classList.remove('hidden');
            showToast('✅ Сообщение отправлено!');
            form.reset();
            setTimeout(() => document.getElementById('contactSuccess').classList.add('hidden'), 5000);
        });
    }

    function initScrollAnimations() {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.fade-in-section').forEach(el => obs.observe(el));
    }
    function initBackToTop() {
        const btn = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            btn.style.display = window.scrollY > 600 ? 'flex' : 'none';
        });
    }
    function initHeaderShadow() {
        const h = document.getElementById('siteHeader');
        window.addEventListener('scroll', () => h.classList.toggle('scrolled', window.scrollY > 20));
    }

    async function loadData() {
        try {
            const [prodRes, blogRes, newsRes] = await Promise.all([
                fetch('/api/products'),
                fetch('/api/blog'),
                fetch('/api/news')
            ]);
            if (!prodRes.ok || !blogRes.ok || !newsRes.ok) throw new Error('Ошибка загрузки');
            window.products = await prodRes.json();
            window.blogArticles = await blogRes.json();
            window.newsItems = await newsRes.json();
        } catch(e) {
            console.error('Ошибка загрузки данных:', e);
            showToast('⚠️ Не удалось загрузить каталог');
        }
    }

    function determinePageType() {
        const path = window.location.pathname;
        if (path === '/' || path === '/index.html') return 'home';
        if (path.startsWith('/catalog')) return 'catalog';
        if (path.startsWith('/blog') && !path.includes('/blog/')) return 'blog';
        if (path.startsWith('/news') && !path.includes('/news/')) return 'news';
        if (path.startsWith('/contacts')) return 'contacts';
        return 'other';
    }

    async function init() {
        await loadData();
        const pageType = determinePageType();
        const currentPage = getPageParam();

        if (pageType === 'home') {
            renderFeaturedProducts();
            renderBlogPreview();
            renderNewsPreview();
        } else if (pageType === 'catalog') {
            initCatalogFilters();
            renderCatalogGrid(currentPage);
        } else if (pageType === 'blog') {
            renderBlogFull(currentPage);
        } else if (pageType === 'news') {
            renderNewsFull(currentPage);
        } else {
            initCatalogFilters();
        }

        initContactForm();
        initScrollAnimations();
        initBackToTop();
        initHeaderShadow();
        updateCartUI();

        document.getElementById('cartOverlay').addEventListener('click', window.toggleCart);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                if (document.getElementById('order-modal-overlay').classList.contains('open')) window.closeOrderModal();
                if (document.getElementById('cartSidebar').classList.contains('open')) window.toggleCart();
                if (document.getElementById('mobileMenu').classList.contains('open')) window.toggleMobileMenu();
            }
        });

        window.addEventListener('popstate', () => {
            const p = getPageParam();
            if (pageType === 'catalog') renderCatalogGrid(p);
            else if (pageType === 'blog') renderBlogFull(p);
            else if (pageType === 'news') renderNewsFull(p);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();