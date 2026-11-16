/* Keep WooCommerce's variation select as the source of truth. */
jQuery(function ($) {
    const syncCartName=()=>document.querySelectorAll('a.dona-cart').forEach(link=>link.setAttribute('aria-label','Shopping bag, '+(link.querySelector('.dona-cart-count')?.textContent.trim()||'0')+' items'));
    syncCartName();$(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart',syncCartName);
    const mobileMenu = document.querySelector('#tm-dialog-mobile');
    if (mobileMenu && !mobileMenu.hasAttribute('aria-label') && !mobileMenu.hasAttribute('aria-labelledby')) mobileMenu.setAttribute('aria-label', 'Mobile navigation');
    const mobileCurrency = document.querySelector('.tm-header-mobile .dona-currency');
    const mobileMenuBar = mobileMenu?.querySelector('.uk-offcanvas-bar, .uk-modal-dialog');
    if (mobileCurrency && mobileMenuBar) {
        const currencyOrigin = document.createComment('Mobile header currency');
        mobileCurrency.before(currencyOrigin);
        const mobileHeader = window.matchMedia('(max-width: 959px)');
        const placeCurrency = () => {
            mobileCurrency.classList.toggle('dona-mobile-menu-currency', mobileHeader.matches);
            if (mobileHeader.matches) mobileMenuBar.prepend(mobileCurrency);
            else currencyOrigin.after(mobileCurrency);
        };
        placeCurrency();
        mobileHeader.addEventListener('change', placeCurrency);
    }
    if (!document.documentElement.dataset.donaDrawerReady) {
        document.documentElement.dataset.donaDrawerReady = 'true';
        const drawer = '#dona-cart-drawer';
        const status = document.createElement('p');
        status.className = 'dona-drawer-status';
        status.setAttribute('role', 'status');
        document.querySelector(drawer + ' .uk-offcanvas-bar')?.append(status);
        const updateCart = (data, removedItem = null) => {
            if (typeof donaDrawer === 'undefined') return;
            const panel = $(drawer);
            if (panel.attr('aria-busy') === 'true') return;
            const scroll = panel.find('.dona-drawer-scroll').scrollTop();
            const openDetails = panel.find('details').map((i, el) => el.open ? i : null).get();
            const note = panel.find('#dona-order-note').val();
            panel.attr('aria-busy', 'true');
            if(removedItem)removedItem.hidden=true;
            panel.find('.dona-drawer-remove, [data-step]').prop('disabled',true);
            status.textContent = removedItem ? 'Removing product…' : 'Updating…';
            $.post(donaDrawer.url, {nonce:donaDrawer.nonce, ...data}).done(response => {
                if (!response.success) { if(removedItem)removedItem.hidden=false; status.textContent = response.data?.message || 'Unable to update cart.'; return; }
                panel.find('.dona-drawer-content').replaceWith(response.data.html);
                panel.find('details').each((i, el) => { el.open = openDetails.includes(i); });
                panel.find('.dona-drawer-scroll').scrollTop(scroll);
                if (note !== undefined) panel.find('#dona-order-note').val(note);
                $('.dona-cart-count').text(response.data.count);syncCartName();
                status.textContent = response.data.message || '';
                if(removedItem&&typeof UIkit!=='undefined'){
                    let confirmation=document.querySelector('#dona-cart-removed');
                    if(!confirmation){
                        confirmation=document.createElement('div');confirmation.id='dona-cart-removed';confirmation.setAttribute('uk-modal','stack: true');confirmation.setAttribute('aria-labelledby','dona-cart-removed-title');
                        confirmation.innerHTML='<div class="uk-modal-dialog uk-modal-body"><button class="uk-modal-close-default" type="button" uk-close aria-label="Close confirmation"></button><h2 id="dona-cart-removed-title">Product removed</h2><p class="dona-cart-removed-message"></p><button type="button" class="uk-button uk-button-primary uk-modal-close">Continue shopping</button></div>';
                        document.body.append(confirmation);
                    }
                    confirmation.querySelector('.dona-cart-removed-message').textContent=response.data.message||'The product has been removed from your cart.';
                    UIkit.modal(confirmation).show();
                }
                // The response already contains the fresh drawer and cart count.
            }).fail(() => { if(removedItem)removedItem.hidden=false; status.textContent = 'Unable to update cart. Please try again.'; })
              .always(() => {panel.removeAttr('aria-busy');panel.find('.dona-drawer-remove, [data-step]').prop('disabled',false);});
        };
        $(document).on('click.donaDrawer', '.dona-cart, #dona-cart-confirmation a[href]', function (event) {
            if (!document.querySelector(drawer) || typeof UIkit === 'undefined') return;
            event.preventDefault();
            if (document.querySelector('#dona-cart-confirmation.uk-open')) UIkit.modal('#dona-cart-confirmation').hide();
            UIkit.offcanvas(drawer).show();
        }).on('click.donaDrawer', drawer + ' [data-step], ' + drawer + ' .dona-drawer-remove', function () {
            const item = this.closest('.dona-drawer-item');
            const quantity = this.classList.contains('dona-drawer-remove') ? 0 : Number(item.querySelector('.dona-drawer-quantity span').textContent) + Number(this.dataset.step);
            updateCart({cart_action:'quantity', key:item.dataset.key, quantity},quantity===0?item:null);
        }).on('submit.donaDrawer', '.dona-drawer-coupon', function (event) {
            event.preventDefault(); updateCart({cart_action:'coupon', coupon:this.elements.coupon.value});
        }).on('click.donaDrawer', '[data-remove-coupon]', function () {
            updateCart({cart_action:'remove_coupon', coupon:this.dataset.removeCoupon});
        }).on('click.donaDrawer', '.dona-save-note', function () {
            updateCart({cart_action:'note', note:document.querySelector('#dona-order-note').value});
        }).on('click.donaDrawer', '.dona-drawer-checkout', function (event) {
            const terms = document.querySelector('.dona-drawer-terms input');
            if (terms && !terms.checked) { event.preventDefault(); status.textContent = 'Please agree to the terms and conditions.'; terms.focus(); }
        });
    }
    $(document.body).off('added_to_cart.donaConfirmation').on('added_to_cart.donaConfirmation', function (event, fragments, hash, trigger) {
        const button = trigger && trigger.jquery ? trigger : $(trigger || []);
        const card = button.closest('.el-item, .product');
        card.find('a.added_to_cart').remove();
        button.removeClass('added');
        const modal = document.querySelector('#dona-cart-confirmation');
        if (!modal || typeof UIkit === 'undefined') return;
        const title = card.find('.el-title, .woocommerce-loop-product__title').first().text().trim();
        modal.querySelector('.dona-cart-confirmation-message').textContent = title ? title + ' has been added to your cart.' : 'Your bag has been added to your cart.';
        UIkit.modal(modal).show();
    });
    const compactProductCards = window.matchMedia('(max-width: 959px)');
    document.querySelectorAll('.single_add_to_cart_button').forEach(button => {
        const originalClasses = new Set(button.classList);
        const stylePurchase = () => {
            ['uk-button', 'uk-button-text'].forEach(name => button.classList.toggle(name, compactProductCards.matches || originalClasses.has(name)));
        };
        stylePurchase();
        compactProductCards.addEventListener('change', stylePurchase);
    });
    document.querySelectorAll('.dona-home-cart-data').forEach(data => {
        const card = data.closest('li.product') || data.closest('.el-item');
        if (!card || card.querySelector('.dona-home-cart')) return;
        const media = card.querySelector('.uk-inline-clip, .dona-shop-product-media') || card.querySelector('.el-image, img');
        if (!media) return;
        card.querySelectorAll(':scope > .button, :scope > a.added_to_cart').forEach(button => button.remove());
        card.classList.add('dona-quick-cart-card');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dona-home-cart';
        button.textContent = 'Add to cart';
        button.setAttribute('aria-label', data.dataset.cartLabel);
        button.title = data.dataset.cartLabel;
        card.append(button);
        const productLink = data.closest('a');
        const originalBody = productLink && [...productLink.children].find(node => node.contains(data) && node.matches('.uk-padding, .uk-card-body'));
        let mobileContent = null;
        let contentMarkers = [];
        let titleLink = null;
        const placeCardContent = mobile => {
            if (mobile && !mobileContent && productLink) {
                mobileContent = originalBody || document.createElement('div');
                const nodes = originalBody ? [originalBody] : [...productLink.children].filter(node => node.matches('.el-title, .woocommerce-loop-product__title, .el-content, .price'));
                contentMarkers = nodes.map(node => {
                    const marker = document.createComment('Product card content');
                    node.before(marker);
                    if (!originalBody) mobileContent.append(node);
                    return {node, marker};
                });
                mobileContent.classList.add('dona-card-mobile-content');
                mobileContent.style.setProperty('--dona-card-content-background', getComputedStyle(productLink).backgroundColor);
                productLink.after(mobileContent);
                const title = mobileContent.querySelector('.el-title, .woocommerce-loop-product__title');
                if (title && !title.querySelector('a')) {
                    titleLink = document.createElement('a');
                    titleLink.href = productLink.href;
                    titleLink.className = 'dona-card-title-link';
                    titleLink.append(...title.childNodes);
                    title.append(titleLink);
                }
                mobileContent.append(button);
                card.classList.add('dona-card-inline-purchase');
            } else if (!mobile && mobileContent) {
                card.append(button);
                if (titleLink) titleLink.replaceWith(...titleLink.childNodes);
                contentMarkers.forEach(({node, marker}) => marker.replaceWith(node));
                mobileContent.classList.remove('dona-card-mobile-content');
                mobileContent.style.removeProperty('--dona-card-content-background');
                if (!originalBody) mobileContent.remove();
                mobileContent = null;
                titleLink = null;
                card.classList.remove('dona-card-inline-purchase');
            }
        };
        const place = () => {
            placeCardContent(compactProductCards.matches);
            button.classList.toggle('uk-button', compactProductCards.matches);
            button.classList.toggle('uk-button-text', compactProductCards.matches);
            if (compactProductCards.matches) {
                button.style.removeProperty('top');
                return;
            }
            button.style.top = (media.getBoundingClientRect().bottom - card.getBoundingClientRect().top - button.offsetHeight - 12) + 'px';
        };
        compactProductCards.addEventListener('change', place);
        new ResizeObserver(place).observe(media);
        place();
        button.addEventListener('click', function () {
            const id = Number(data.dataset.cartId);
            if (!id || typeof wc_add_to_cart_params === 'undefined') {
                window.location.href = card.querySelector('a').href;
                return;
            }
            button.disabled = true;
            button.textContent = 'Adding…';
            $.post(wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart'), {product_id:id, quantity:1})
                .done(response => {
                    if (response.error || !response.fragments) {
                        window.location.href = response.product_url || card.querySelector('a').href;
                        return;
                    }
                    Object.entries(response.fragments).forEach(([selector, html]) => $(selector).replaceWith(html));
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $(button)]);
                    button.textContent = 'Add to cart';
                }).fail(() => { button.textContent = 'Try again'; })
                .always(() => { button.disabled = false; });
        });
    });
    const filterBar = document.querySelector('#tm-element-woo-filter-offcanvas .uk-offcanvas-bar');
    if (filterBar && !filterBar.querySelector('.dona-filter-heading')) {
        const heading = document.createElement('h2');
        heading.className = 'dona-filter-heading';
        heading.textContent = 'FILTERS';
        filterBar.prepend(heading);
        const view = document.createElement('button');
        view.type = 'button';
        view.className = 'dona-view-results';
        view.textContent = 'View results';
        view.addEventListener('click', () => UIkit.offcanvas('#tm-element-woo-filter-offcanvas').hide());
        filterBar.append(view);
    }
    if (!document.documentElement.dataset.donaShopVideoReady) {
        document.documentElement.dataset.donaShopVideoReady = 'true';
        const cards = '.woocommerce ul.products li.product a:has(.dona-shop-hover-video)';
        $(document).on('mouseenter.donaVideo focusin.donaVideo', cards, function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const video = this.querySelector('.dona-shop-hover-video');
            video.dataset.active = 'true';
            video.muted = true;
            if (!video.getAttribute('src')) video.src = video.dataset.src;
            const playing = video.play();
            if (playing) playing.then(() => {
                if (video.dataset.active === 'true') video.parentElement.classList.add('is-playing');
                else video.pause();
            }).catch(() => video.parentElement.classList.remove('is-playing'));
        }).on('mouseleave.donaVideo focusout.donaVideo', cards, function () {
            const video = this.querySelector('.dona-shop-hover-video');
            video.dataset.active = 'false';
            video.pause();
            if (video.readyState) video.currentTime = 0;
            video.parentElement.classList.remove('is-playing');
        });
    }
    $('form.variations_form').each(function () {
        const form = $(this);
        if (form.data('donaSwatchesReady')) return;
        const select = form.find('select[name="attribute_color"]');
        if (!select.length) return;
        select.closest('tr').addClass('dona-color-row');
        form.data('donaSwatchesReady', true);
        const variations = form.data('product_variations') || [];
        const group = $('<div class="dona-color-swatches" role="group" aria-label="Color"></div>');
        const selected = $('<span class="dona-selected-color" aria-live="polite"></span>');
        const palette = {black:'#242424', burgundy:'#56232e', chocolate:'#4a3429', espresso:'#382820', ivory:'#e2dccb', sand:'#c9b99d'};
        select.find('option').each(function () {
            if (!this.value) return;
            const value = this.value;
            const label = this.text;
            const variation = variations.find(item => item.attributes.attribute_color === value);
            const button = $('<button type="button" class="dona-color-swatch" aria-pressed="false"></button>')
                .attr({'data-value':value, 'aria-label':label, title:label})
                .css('--dona-swatch-color', palette[value.toLowerCase()] || '#827368');
            button.append($('<span class="dona-swatch-label"></span>').text(label));
            button.on('click', function () { select.val(value).trigger('change'); });
            group.append(button);
        });
        select.after(selected, group).addClass('dona-color-select');
        function sync() {
            group.find('button').each(function () {
                const option = select.find('option').filter((_, item) => item.value === this.dataset.value);
                this.disabled = !option.length || option.prop('disabled');
                this.setAttribute('aria-pressed', String(this.dataset.value === select.val()));
            });
            selected.text(select.val() ? select.find(':selected').text() : 'Choose a color');
        }
        form.on('woocommerce_update_variation_values reset_data found_variation', sync);
        select.on('change', sync);
        sync();
    });
});

jQuery(function ($) {
    if (document.documentElement.dataset.donaFavoritesReady) return;
    document.documentElement.dataset.donaFavoritesReady = 'true';
    // Public catalog cards only; account details never enter browser storage.
    const cardKey='dona-catalog-cards-v1:'+donaFavorites.currency;
    let cardStore={};const cardPending=new Map();
    try{cardStore=JSON.parse(sessionStorage.getItem(cardKey)||'{}');}catch(_){}
    for(const item of donaFavorites.seed||[])cardStore[item.id]={item,time:Date.now()};
    try{sessionStorage.setItem(cardKey,JSON.stringify(cardStore));}catch(_){}
    window.donaProductCards=function(productIds){
        const now=Date.now(),missing=productIds.filter(id=>!cardStore[id]||now-cardStore[id].time>300000);
        if(!missing.length)return $.Deferred().resolve({success:true,data:productIds.map(id=>cardStore[id].item).filter(Boolean)}).promise();
        const key=missing.join(',');
        if(!cardPending.has(key)){
            const pending=$.ajax({url:donaFavorites.url,data:{ids:key},timeout:20000}).then(response=>{
                if(response.success){response.data.forEach(item=>cardStore[item.id]={item,time:Date.now()});try{sessionStorage.setItem(cardKey,JSON.stringify(cardStore));}catch(_){} }
                return response;
            });
            cardPending.set(key,pending);pending.always(()=>cardPending.delete(key));
        }
        return cardPending.get(key).then(response=>({success:response.success,data:productIds.map(id=>cardStore[id]?.item).filter(Boolean)}));
    };
    const key = 'dona-favorites-v1';
    const drawer = document.querySelector('#dona-wishlist');
    if (!drawer) return;
    const list = drawer.querySelector('.dona-favorites-list');
    const status = drawer.querySelector('.dona-favorites-status');
    let ids = [];
    let request;
    try {
        const stored = JSON.parse(localStorage.getItem(key) || '[]');
        if (Array.isArray(stored)) ids = [...new Set(stored.filter(id => Number.isInteger(id) && id > 0))].slice(0, 100);
    } catch (_) { /* An unavailable browser store must not prevent shopping. */ }
    const favorite = document.querySelector('#dona-product-favorite');
    if (favorite) {
        const form = document.querySelector('form.cart');
        if (form) { const timeline=document.querySelector('.dona-shipping-timeline'); if(timeline)timeline.after(favorite);else form.after(favorite); favorite.hidden = false; }
    }
    function sync() {
        drawer.querySelector('.dona-favorites-count').textContent = ids.length;
        document.querySelectorAll('.dona-wishlist').forEach(button => {
            const badge = button.querySelector('.dona-wishlist-count');
            if (badge) { badge.textContent = ids.length; badge.hidden = ids.length === 0; }
            button.setAttribute('aria-label', 'Open favorites, ' + ids.length + (ids.length === 1 ? ' saved product' : ' saved products'));
        });
        document.querySelectorAll('.dona-favorite-toggle').forEach(button => {
            const saved = ids.includes(Number(button.dataset.productId));
            button.setAttribute('aria-pressed', String(saved));
            button.classList.toggle('is-saved', saved);
            button.querySelector('.dona-favorite-label').textContent = saved ? 'Saved to Wishlist' : 'Add to Wishlist';
        });
    }
    function save() {
        try { localStorage.setItem(key, JSON.stringify(ids)); }
        catch (_) { status.textContent = 'Browser storage is unavailable. Favorites will last only for this visit.'; }
        sync();
    }
    function load() {
        list.replaceChildren();
        if (!ids.length) { status.textContent = 'Your favorites are empty. Save a bag using the heart on its product page.'; return; }
        status.textContent = 'Loading favorites…';
        const requested=ids.join(',');
        request = window.donaProductCards(ids).done(response => {
            if(requested!==ids.join(','))return;
            list.replaceChildren();
            if (!response.success) { status.textContent = 'Unable to load favorites.'; return; }
            status.textContent = response.data.length ? '' : 'These products are no longer available.';
            response.data.forEach(item => {
                const card = $('<article class="dona-favorites-item el-item"></article>').attr('data-product-id', item.id);
                $('<a></a>').attr('href', item.url).append($('<img>').attr({src:item.image, alt:item.name})).appendTo(card);
                const details = $('<div></div>').appendTo(card);
                $('<h3 class="el-title"></h3>').append($('<a></a>').attr('href', item.url).text(item.name)).appendTo(details);
                $('<div class="dona-favorites-price"></div>').html(item.price).appendTo(details);
                if (item.cartId) $('<button type="button" class="dona-favorites-add">Add to cart</button>').attr({'data-cart-id':item.cartId, 'aria-label':item.cartLabel}).appendTo(details);
                else $('<a></a>').attr('href', item.url).text(item.available ? 'View product' : 'Out of stock').appendTo(details);
                $('<button type="button" class="dona-favorites-remove">Remove</button>').attr('aria-label','Remove ' + item.name + ' from favorites').appendTo(details);
                list.append(card[0]);
            });
        }).fail((xhr, reason) => { if (reason !== 'abort') status.textContent = 'Unable to load favorites. Please reopen to try again.'; });
    }
    sync();
    $(document).on('click.donaFavorites', '.dona-favorite-toggle', function () {
        const id = Number(this.dataset.productId);
        if (ids.includes(id)) ids = ids.filter(value => value !== id);
        else { if (ids.length >= 100) { favorite.querySelector('.dona-favorite-status').textContent = 'Your favorites are full. Remove a bag to save another.'; return; } ids.push(id); }
        save();
        favorite.querySelector('.dona-favorite-status').textContent = ids.includes(id) ? 'Saved in this browser.' : 'Removed from favorites.';
    }).on('click.donaFavorites', '.dona-favorites-remove', function () {
        ids = ids.filter(id => id !== Number(this.closest('.dona-favorites-item').dataset.productId));
        save(); load();
    }).on('click.donaFavorites', '.dona-favorites-add', function () {
        const button = $(this);
        button.prop('disabled', true).text('Adding…');
        $.post(wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%','add_to_cart'), {product_id:this.dataset.cartId, quantity:1})
            .done(response => {
                if (response.error || !response.fragments) { status.textContent = 'Please open the product to choose an available option.'; return; }
                Object.entries(response.fragments).forEach(([selector, html]) => $(selector).replaceWith(html));
                UIkit.offcanvas(drawer).hide();
                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, button]);
            }).fail(() => { status.textContent = 'Unable to add this bag. Please try again.'; })
            .always(() => button.prop('disabled', false).text('Add to cart'));
    });
    UIkit.util.on(drawer, 'beforeshow', load);
    window.addEventListener('storage', event => {
        if (event.key !== key) return;
        try { const value = JSON.parse(event.newValue || '[]'); ids = Array.isArray(value) ? [...new Set(value.filter(id => Number.isInteger(id) && id > 0))].slice(0,100) : []; sync(); if (drawer.classList.contains('uk-open')) load(); } catch (_) {}
    });
});

// A sticky product summary must fit the viewport; expanded panels use page scrolling.
jQuery(function () {
    const column = document.querySelector('.dona-product-details-column');
    const sticky = column?.querySelector('[uk-sticky]');
    if (!sticky || sticky.dataset.donaFitReady) return;
    sticky.dataset.donaFitReady = 'true';
    const base = (sticky.getAttribute('uk-sticky') || '').replace(/(?:^|;)\s*media\s*:[^;]*/g, '');
    let previous = '';
    function fit() {
        const required = Math.ceil(sticky.getBoundingClientRect().height + 104);
        const media = '(min-width: 960px) and (min-height: ' + required + 'px)';
        if (media === previous) return;
        previous = media;
        sticky.setAttribute('uk-sticky', base + '; media: ' + media);
    }
    new ResizeObserver(fit).observe(sticky);
    window.addEventListener('resize', fit);
    fit();
});

jQuery(function () {
    const images = document.querySelector('.dona-product-images-column');
    const details = document.querySelector('.dona-product-details-column');
    if (!images || !details) return;
    function matchHeight() {
        const height = Math.ceil(details.getBoundingClientRect().height);
        if (height > 0) images.style.setProperty('--dona-details-height', height + 'px');
    }
    new ResizeObserver(matchHeight).observe(details);
    window.addEventListener('resize', matchHeight);
    matchHeight();
});

// Compact size buttons use the native variation select as their source of truth.
jQuery(function ($) {
    $('form.variations_form').each(function () {
        const form = $(this);
        const select = form.find('select[name="attribute_size"]');
        if (!select.length || form.find('.dona-size-options').length) return;
        const row = select.closest('tr');
        row.addClass('dona-size-row');
        row.appendTo(row.parent());
        const group = $('<div class="dona-size-options" role="group" aria-label="Size"></div>');
        const stock = $('<span class="dona-size-stock" role="status"></span>');
        const selected = $('<span class="dona-current-size"></span>');
        row.find('label').append(selected, stock);
        select.find('option').each(function () {
            if (!this.value) return;
            const value = this.value;
            $('<button type="button" aria-pressed="false"></button>').text(this.text).attr('data-size',value)
                .on('click', () => select.val(value).trigger('change')).appendTo(group);
        });
        select.addClass('dona-size-select').after(group);
        function sync() {
            selected.text(select.val() ? ': ' + select.find(':selected').text() : '');
            group.find('button').each(function () {
                const option = select.find('option').filter((i, el) => el.value === this.dataset.size);
                this.disabled = !option.length || option.prop('disabled');
                this.setAttribute('aria-pressed',String(this.dataset.size === select.val()));
            });
        }
        select.on('change',sync);
        form.on('woocommerce_update_variation_values',sync).on('found_variation', (event, variation) => {
            stock.text(variation.is_in_stock ? '● IN STOCK' : 'OUT OF STOCK').toggleClass('is-unavailable', !variation.is_in_stock);
        }).on('reset_data hide_variation', () => stock.text(''));
        sync();
        form.trigger('check_variations');
    });
});

jQuery(function () {
    const share = document.querySelector('#dona-product-share');
    const details = document.querySelector('.dona-product-details-column');
    if (!share || !details) return;
    details.prepend(share); share.hidden = false;
    share.querySelector('.dona-copy-link').addEventListener('click', async () => {
        const field = share.querySelector('input');
        const status = share.querySelector('.dona-share-status');
        try { await navigator.clipboard.writeText(field.value); status.textContent = 'Link copied.'; }
        catch (_) { field.focus(); field.select(); status.textContent = 'Select and copy this product link.'; }
    });
});

// Switch the whole upper image set by color; lower editorial media stays independent.
jQuery(function ($) {
    const data = document.querySelector('#dona-color-gallery-data');
    const column = document.querySelector('.dona-product-images-column');
    const form = $('form.variations_form').first();
    if (!data || !column || !form.length || column.dataset.donaColorGalleryReady) return;
    let galleries;
    try { galleries = JSON.parse(data.textContent); } catch (_) { return; }
    column.dataset.donaColorGalleryReady = 'true';
    const select = form.find('select[name="attribute_color"],select[name="attribute_pa_color"],select[name="attribute_colour"],select[name="attribute_pa_colour"]').first();
    if (!select.length) return;
    const compact = matchMedia('(max-width:959px)');
    const original = [...column.children];
    const stage = document.createElement('div'); stage.className = 'dona-color-image-stack'; stage.setAttribute('role','region'); stage.setAttribute('aria-label','Product images'); column.prepend(stage);
    const shell = document.createElement('div');
    shell.className = 'dona-product-gallery-shell';
    column.before(shell); shell.append(column);
    const details = document.querySelector('.dona-product-details-column');
    function placeGallery() {
        if (!details || details.parentElement !== shell.parentElement) return;
        if (compact.matches) details.after(shell);
        else details.before(shell);
        if (typeof UIkit !== 'undefined') UIkit.update(shell.parentElement);
    }
    placeGallery();
    const rail = document.createElement('div');
    rail.className = 'dona-product-thumbnail-rail'; shell.append(rail);
    const nav = document.createElement('div'); nav.className = 'dona-color-gallery-nav'; nav.setAttribute('role','group'); nav.setAttribute('aria-label','Product images for selected color');
    rail.append(nav);
    const mirrorHeight = () => shell.style.setProperty('--dona-details-height', column.style.getPropertyValue('--dona-details-height') || '650px');
    new ResizeObserver(mirrorHeight).observe(column); mirrorHeight();
    const controls = document.createElement('div'); controls.className = 'dona-product-slider-controls';
    const previous = document.createElement('button'); previous.type='button'; previous.textContent='←'; previous.setAttribute('aria-label','Previous product image');
    const counter = document.createElement('span'); counter.setAttribute('aria-live','polite'); counter.setAttribute('aria-atomic','true');
    const next = document.createElement('button'); next.type='button'; next.textContent='→'; next.setAttribute('aria-label','Next product image');
    controls.append(previous,counter,next); shell.append(controls);
    let selected = null;
    let observer;
    let activeIndex = 0;
    function setActive(index) {
        activeIndex=index;
        const count=stage.querySelectorAll('.dona-color-image').length;
        nav.querySelectorAll('button').forEach((button,i)=>button.setAttribute('aria-pressed',String(i===index)));
        counter.textContent=count ? (index+1)+' / '+count : '';
        previous.disabled=index===0 || !count; next.disabled=index>=count-1;
    }
    function showImage(index) {
        const items=stage.querySelectorAll('.dona-color-image');
        const item=items[Math.max(0,Math.min(items.length-1,index))];
        if(!item)return;
        const behavior=matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth';
        if(compact.matches) stage.scrollTo({left:stage.scrollLeft+item.getBoundingClientRect().left-stage.getBoundingClientRect().left,behavior});
        else column.scrollTo({top:column.scrollTop+item.getBoundingClientRect().top-column.getBoundingClientRect().top,behavior});
    }
    previous.addEventListener('click',()=>showImage(activeIndex-1));
    next.addEventListener('click',()=>showImage(activeIndex+1));
    stage.addEventListener('keydown',event=>{
        if(!compact.matches || event.target!==stage)return;
        if(event.key==='ArrowRight'){event.preventDefault();showImage(activeIndex+1);}
        if(event.key==='ArrowLeft'){event.preventDefault();showImage(activeIndex-1);}
    });
    function render() {
        const color = select.val();
        if (color === selected) return;
        selected = color;
        const images = galleries[color] || [];
        original.forEach(el=>el.hidden=true);
        stage.replaceChildren(); nav.replaceChildren();
        if (observer) observer.disconnect();
        observer = new IntersectionObserver(entries=>{
            const visible = entries.filter(e=>e.isIntersecting).sort((a,b)=>b.intersectionRatio-a.intersectionRatio)[0];
            if (!visible) return;
            setActive(Number(visible.target.dataset.index));
        },{root:compact.matches?stage:column,threshold:[.25,.5,.75]});
        controls.hidden=!images.length;
        stage.tabIndex=compact.matches?0:-1;
        if(compact.matches)stage.setAttribute('aria-roledescription','carousel');else stage.removeAttribute('aria-roledescription');
        if (!images.length) { const message=document.createElement('p');message.textContent='Images for this color will be added soon.';stage.append(message);setActive(0);return; }
        images.forEach((image,index)=>{
            const item=document.createElement('div');item.className='dona-color-image';item.dataset.index=index;item.setAttribute('role','group');item.setAttribute('aria-label','Image '+(index+1)+' of '+images.length);
            const link=document.createElement('a');link.href=image.full;link.setAttribute('aria-label','View '+image.alt);link.innerHTML=image.html;
            const fullImage=link.querySelector('img');if(fullImage)fullImage.alt=image.alt;
            item.append(link);stage.append(item);observer.observe(item);
            const button=document.createElement('button');button.type='button';button.setAttribute('aria-label','Scroll to '+image.alt+', image '+(index+1));button.setAttribute('aria-pressed',String(index===0));
            const thumb=document.createElement('img');thumb.src=image.thumb;thumb.alt='';button.append(thumb);nav.append(button);
            button.addEventListener('click',()=>showImage(index));
        });
        if (typeof UIkit !== 'undefined') UIkit.lightbox(stage);
        column.scrollTop=0;stage.scrollLeft=0;setActive(0);
    }
    select.on('change.donaColorGallery',render);
    form.on('found_variation.donaColorGallery reset_data.donaColorGallery',render);
    compact.addEventListener('change',()=>{placeGallery();selected=null;render();});
    render();
});

// Switch the existing native lower media instantly, using the same color as the main gallery.
jQuery(function ($) {
    const data = document.querySelector('#dona-lower-color-data');
    const form = $('form.variations_form').first();
    const select = form.find('select[name="attribute_color"],select[name="attribute_pa_color"],select[name="attribute_colour"],select[name="attribute_pa_colour"]').first();
    const sections = [...document.querySelectorAll('.single-product .dona-lower-color-media')];
    if (!data || !select.length || !sections.length || form[0].dataset.donaLowerMediaReady) return;
    let media;
    try { media = JSON.parse(data.textContent); } catch (_) { return; }
    form[0].dataset.donaLowerMediaReady = 'true';
    const normalize = url => new URL(url, document.baseURI).href;
    const films = new Map();
    sections.forEach(section => {
        const video = section.querySelector(':scope > video');
        if (video) films.set(normalize(video.getAttribute('src')), {section, video, src:video.getAttribute('src')});
    });
    const anchor = document.createComment('DONA films for selected color');
    sections[0].before(anchor);
    const gallerySection = sections.find(section => !section.querySelector(':scope > video'));
    const grid = gallerySection?.querySelector('[uk-lightbox][uk-grid]');
    const images = new Map();
    if (grid) [...grid.children].forEach(item => {
        const link = item.querySelector('a[data-type]');
        if (link) images.set(normalize(link.getAttribute('href')), item);
    });
    let selected;
    function render() {
        const color = select.val() || '';
        if (selected === color) return;
        selected = color;
        const chosen = media.colors[color] || media.defaults;
        const selectedFilms = new Set(chosen.videos.map(normalize));
        films.forEach(({section, video}, url) => {
            video.pause(); section.remove();
            if (!selectedFilms.has(url) && video.hasAttribute('src')) { video.removeAttribute('src'); video.load(); }
        });
        let previous = anchor;
        chosen.videos.forEach(url => {
            const film = films.get(normalize(url));
            if (!film) return;
            if (!film.video.hasAttribute('src')) film.video.setAttribute('src', film.src);
            previous.after(film.section); previous = film.section;
        });
        if (grid) {
            grid.querySelectorAll('video').forEach(video => video.pause());
            grid.replaceChildren();
            chosen.images.forEach((url, index) => {
                const item = images.get(normalize(url));
                if (item) {
                    const link = item.querySelector('a[data-type]');
                    if (link && !link.getAttribute('aria-label')) {
                        const alt = item.querySelector('img')?.alt;
                        link.setAttribute('aria-label', alt ? 'View ' + alt : (link.dataset.type === 'video' ? 'Play product video ' : 'View product image ') + (index + 1));
                    }
                    grid.append(item);
                }
            });
            gallerySection.classList.toggle('dona-lower-media-empty', !grid.children.length);
        }
        if (typeof UIkit !== 'undefined') UIkit.update(anchor.parentElement);
    }
    select.on('change.donaLowerMedia', render);
    form.on('found_variation.donaLowerMedia reset_data.donaLowerMedia', render);
    render();
});

// Keep the shared breadcrumb trail with the page's title and introductory copy.
jQuery(function () {
    const bar = document.querySelector('.dona-breadcrumb-bar');
    const main = document.querySelector('#tm-main');
    const title = main?.querySelector('h1');
    const trail = bar?.querySelector('.dona-breadcrumbs');
    if (!title || !trail) return;
    if (document.body.classList.contains('single-product')) {
        trail.classList.add('dona-breadcrumbs--inline');
        (title.closest('.dona-product-title-row') || title).before(trail);
        bar.remove();
        return;
    }
    let anchor = title;
    const productDetails = title.closest('.dona-product-details-column');
    const header = title.closest('.entry-header, .page-header, .woocommerce-products-header');
    const section = title.closest('section') || header;
    const description = productDetails?.querySelector('.woocommerce-product-details__short-description')
        || section?.querySelector('.dona-collection-description, .term-description, .page-description, .archive-description');
    if (description && description.textContent.trim()) {
        anchor = description;
    } else if (header) {
        anchor = header;
    } else {
        // Native YOOtheme headline + text elements are adjacent in the same column.
        const next = title.nextElementSibling;
        if (next && ['P', 'DIV'].includes(next.tagName) && next.textContent.trim()
            && !next.querySelector('h1,h2,h3,a,button,form,input,img,picture,video,iframe')
            && !next.matches('.price, .woocommerce-product-rating, .dona-breadcrumbs')) anchor = next;
    }
    trail.classList.add('dona-breadcrumbs--inline');
    const alignment = getComputedStyle(title).textAlign;
    if (alignment === 'center') trail.classList.add('dona-breadcrumbs--center');
    anchor.after(trail);
    bar.remove();
});
