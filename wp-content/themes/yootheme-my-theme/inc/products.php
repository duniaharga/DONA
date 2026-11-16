<?php
/** DONA product presentation fields and native YOOtheme dynamic bindings. */
add_action('woocommerce_product_options_general_product_data', function () {
    woocommerce_wp_text_input(['id' => '_dona_badge', 'label' => 'DONA card badge', 'desc_tip' => true, 'description' => 'Optional label such as Best Seller or Pre-order.']);
    woocommerce_wp_text_input(['id' => '_dona_delivery_note', 'label' => 'DONA delivery note']);
});
add_action('woocommerce_admin_process_product_object', function ($product) {
    foreach (['_dona_badge', '_dona_delivery_note'] as $key) {
        if (isset($_POST[$key])) $product->update_meta_data($key, sanitize_text_field(wp_unslash($_POST[$key])));
    }
});
add_action('after_setup_theme', function () {
    if (!class_exists(\YOOtheme\Event::class)) return;
    \YOOtheme\Event::on('builder.template', function ($query) {
        // WooCommerce exposes its Shop page as the queried object, rather than WP_Post_Type.
        $object = $query->get_queried_object();
        if ($query->is_post_type_archive() && $object instanceof WP_Post && (int) $object->ID === wc_get_page_id('shop')) {
            return ['type' => 'archive-product', 'query' => ['pages' => $query->get('paged') > 1 ? 'except_first' : 'first']];
        }
    }, -100);
    \YOOtheme\Event::on('source.init', function ($source) {
        $source->objectType('Product', ['fields' => [
            'donaBadge' => ['type' => 'String', 'metadata' => ['label' => 'DONA badge'], 'extensions' => ['call' => 'dona_product_badge']],
            'donaCardDetails' => ['type' => 'String', 'metadata' => ['label' => 'DONA delivery note and price'], 'extensions' => ['call' => 'dona_product_card_details']],
            'donaProductVideo' => ['type' => 'String', 'metadata' => ['label' => 'DONA product background video'], 'extensions' => ['call' => 'dona_product_video']],
            'donaProductVideos' => ['type' => ['listOf' => 'Attachment'], 'metadata' => ['label' => 'DONA product videos'], 'extensions' => ['call' => 'dona_product_videos']],
            'donaLowerGalleryImages' => ['type' => ['listOf' => 'Attachment'], 'metadata' => ['label' => 'DONA images below videos'], 'extensions' => ['call' => 'dona_lower_gallery_images']],
            'donaEditorialImages' => ['type' => ['listOf' => 'Attachment'], 'metadata' => ['label' => 'DONA editorial images'], 'extensions' => ['call' => 'dona_product_editorial_images']],
            'donaHoverImage' => ['type' => 'Attachment', 'metadata' => ['label' => 'DONA product hover image'], 'extensions' => ['call' => 'dona_product_hover_image']],
            'donaShopHoverImage' => ['type' => 'Attachment', 'metadata' => ['label' => 'DONA shop hover image'], 'extensions' => ['call' => 'dona_product_shop_hover_image']],
            'donaShopHoverVideo' => ['type' => 'String', 'metadata' => ['label' => 'DONA shop hover video'], 'extensions' => ['call' => 'dona_product_shop_hover_video']],
        ]]);
        $source->objectType('ProductCat', ['fields' => [
            'donaCollectionImage' => ['type' => 'Attachment', 'metadata' => ['label' => 'DONA collection editorial image'], 'extensions' => ['call' => 'dona_collection_editorial_image']],
        ]]);
    });
}, 20);
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dona-products', get_stylesheet_directory_uri().'/css/products.css', ['dona-header'], filemtime(get_stylesheet_directory().'/css/products.css'));
    if (function_exists('WC')) {
        wp_enqueue_script('dona-product-swatches', get_stylesheet_directory_uri().'/js/dona.js', is_product() ? ['jquery', 'wc-add-to-cart', 'wc-add-to-cart-variation'] : ['jquery', 'wc-add-to-cart'], filemtime(get_stylesheet_directory().'/js/dona.js'), true);
    }
});

/** Session-backed cart drawer; WooCommerce remains the source of totals and stock rules. */
function dona_cart_drawer_content() {
    if (!WC()->cart) return;
    ?>
    <div class="dona-drawer-content">
      <header><h2>Cart (<?php echo absint(WC()->cart->get_cart_contents_count()); ?>)</h2><button type="button" class="uk-offcanvas-close" uk-close aria-label="Close cart"></button></header>
      <div class="dona-drawer-scroll">
      <?php foreach (WC()->cart->get_cart() as $key => $item): $product = $item['data']; ?>
        <article class="dona-drawer-item" data-key="<?php echo esc_attr($key); ?>">
          <a href="<?php echo esc_url($product->get_permalink($item)); ?>"><?php echo $product->get_image('medium', ['sizes' => '(max-width: 480px) 75px, 100px']); ?></a>
          <div><a href="<?php echo esc_url($product->get_permalink($item)); ?>"><?php echo esc_html($product->get_name()); ?></a>
          <div class="dona-drawer-unit"><?php echo wp_kses_post(WC()->cart->get_product_price($product)); ?></div>
          <?php echo wc_get_formatted_cart_item_data($item); ?>
          <div class="dona-drawer-quantity"><button type="button" data-step="-1" aria-label="Decrease quantity">−</button><span><?php echo esc_html($item['quantity']); ?></span><button type="button" data-step="1" aria-label="Increase quantity" <?php disabled($product->is_sold_individually()); ?>>+</button></div>
          <button type="button" class="dona-drawer-remove">Remove</button></div>
          <strong><?php echo wp_kses_post(WC()->cart->get_product_subtotal($product, $item['quantity'])); ?></strong>
        </article>
      <?php endforeach; ?>
      <?php if (WC()->cart->is_empty()): ?><p class="dona-drawer-empty">Your shopping bag is empty.</p><?php else: ?>
        <details><summary>Add order notes</summary><textarea class="uk-textarea" id="dona-order-note" aria-label="Order notes" rows="3"><?php echo esc_textarea(WC()->session->get('dona_order_note', '')); ?></textarea><button type="button" class="uk-button uk-button-default dona-save-note">Save note</button></details>
        <?php if (wc_coupons_enabled()): ?><details><summary>Discount code</summary><form class="dona-drawer-coupon"><input class="uk-input" name="coupon" aria-label="Discount code" placeholder="Discount code" required><button class="uk-button uk-button-default">Apply</button></form><?php foreach(WC()->cart->get_applied_coupons() as $code): ?><p><?php echo esc_html($code); ?> <button type="button" data-remove-coupon="<?php echo esc_attr($code); ?>">Remove</button></p><?php endforeach; ?></details><?php endif; ?>
      <?php endif; ?>
      </div>
      <footer><div class="dona-drawer-subtotal"><span>Subtotal</span><strong><?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?></strong></div>
      <?php if (WC()->cart->get_discount_total()): ?><p>Discount: −<?php echo wp_kses_post(wc_price(WC()->cart->get_discount_total())); ?></p><?php endif; ?>
      <?php if (!WC()->cart->is_empty()): ?>
        <?php if (wc_get_page_id('terms') > 0): ?><label class="dona-drawer-terms"><input class="uk-checkbox" type="checkbox"> I agree to the <a href="<?php echo esc_url(get_permalink(wc_get_page_id('terms'))); ?>">terms and conditions</a></label><?php endif; ?>
        <a class="uk-button uk-button-primary dona-drawer-checkout" href="<?php echo esc_url(wc_get_checkout_url()); ?>">Check Out</a><p class="dona-drawer-footnote">Shipping, taxes, and discounts calculated at checkout.</p>
      <?php else: ?><a class="uk-button uk-button-primary" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Continue shopping</a><?php endif; ?>
      </footer>
    </div>
    <?php
}
add_action('wp_footer', function () {
    if (!function_exists('WC') || !WC()->cart) return;
    echo '<div id="dona-cart-drawer" uk-offcanvas="flip: true; overlay: true" aria-label="Shopping cart"><div class="uk-offcanvas-bar">';
    dona_cart_drawer_content();
    echo '</div></div>';
    wp_localize_script('dona-product-swatches', 'donaDrawer', ['url' => WC_AJAX::get_endpoint('dona_cart'), 'nonce' => wp_create_nonce('dona_cart')]);
});
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    ob_start(); dona_cart_drawer_content();
    $fragments['.dona-drawer-content'] = ob_get_clean();
    return $fragments;
});
add_action('wc_ajax_dona_cart', function () {
    check_ajax_referer('dona_cart', 'nonce');
    $action = sanitize_key($_POST['cart_action'] ?? '');
    $removed_name = '';
    if ($action === 'quantity') {
        $key = wc_clean(wp_unslash($_POST['key'] ?? ''));
        $item = WC()->cart->get_cart_item($key);
        $quantity = max(0, wc_stock_amount($_POST['quantity'] ?? 0));
        if (!$item) wp_send_json_error(['message' => 'This item is no longer in your cart.']);
        $product = $item['data'];
        $valid = !$quantity || ((!$product->is_sold_individually() || $quantity <= 1) && ($product->get_max_purchase_quantity() < 0 || $quantity <= $product->get_max_purchase_quantity()));
        $valid = $valid && apply_filters('woocommerce_update_cart_validation', true, $key, $item, $quantity);
        if (!$valid) wp_send_json_error(['message' => 'This quantity is not available.']);
        if (!$quantity) $removed_name = $product->get_name();
        WC()->cart->set_quantity($key, $quantity, false);
    }
    if ($action === 'coupon') WC()->cart->apply_coupon(wc_format_coupon_code(wp_unslash($_POST['coupon'] ?? '')));
    if ($action === 'remove_coupon') WC()->cart->remove_coupon(wc_format_coupon_code(wp_unslash($_POST['coupon'] ?? '')));
    if ($action === 'note') WC()->session->set('dona_order_note', sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')));
    WC()->cart->calculate_totals();
    $errors = wc_get_notices('error');
    wc_clear_notices();
    ob_start(); dona_cart_drawer_content(); $html = ob_get_clean();
    wp_send_json_success(['html' => $html, 'count' => WC()->cart->get_cart_contents_count(), 'message' => $errors ? html_entity_decode(wp_strip_all_tags($errors[0]['notice']), ENT_QUOTES, 'UTF-8') : ($removed_name ? $removed_name.' has been removed from your cart.' : ($action === 'note' ? 'Order note saved.' : ''))]);
});
add_filter('woocommerce_checkout_get_value', function ($value, $key) {
    return $key === 'order_comments' && !$value ? WC()->session->get('dona_order_note', '') : $value;
}, 10, 2);
add_action('woocommerce_checkout_create_order', function ($order) {
    if (!$order->get_customer_note() && WC()->session->get('dona_order_note')) $order->set_customer_note(WC()->session->get('dona_order_note'));
});
add_action('woocommerce_thankyou', function () { if (WC()->session) WC()->session->__unset('dona_order_note'); });
add_action('woocommerce_single_product_summary', function () {
    global $product;
    if ($product && $product->get_meta('_dona_demo_data')) {
        echo '<p class="dona-demo-notice"><small>Sample product: specifications, sizes and availability are demonstration data.</small></p>';
    }
}, 25);

function dona_product_badge($post) { return get_post_meta($post->ID, '_dona_badge', true); }
add_action('wp_footer', function () {
    if (!(is_front_page() || is_shop() || is_product_taxonomy() || is_product())) return;
    ?>
    <div id="dona-cart-confirmation" class="uk-flex-top" uk-modal aria-labelledby="dona-cart-confirmation-title">
        <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical uk-text-center">
            <button class="uk-modal-close-default" type="button" uk-close aria-label="Close confirmation"></button>
            <span class="dona-cart-check" uk-icon="icon: check; ratio: 1.4" aria-hidden="true"></span>
            <h2 id="dona-cart-confirmation-title">Added to your cart</h2>
            <p class="dona-cart-confirmation-message" role="status">Your bag has been added to your cart.</p>
            <a class="uk-button uk-button-primary" href="<?php echo esc_url(wc_get_cart_url()); ?>">View cart</a>
            <button class="uk-button uk-button-text uk-modal-close" type="button">Continue shopping</button>
        </div>
    </div>
    <?php
});
// Register after WooCommerce has registered product types and attributes.
add_action('init', function () {
    if (!function_exists('acf_add_local_field_group')) return;
    acf_add_local_field_group([
        'key' => 'group_dona_collection_editorial', 'title' => 'DONA — Collection page',
        'fields' => [['key' => 'field_dona_collection_editorial_image', 'label' => 'Collection editorial image',
            'name' => 'dona_collection_editorial_image', 'type' => 'image', 'return_format' => 'id',
            'preview_size' => 'medium', 'library' => 'all', 'instructions' => 'Large editorial photograph on this collection page. The category name and description appear above it.']],
        'location' => [[['param' => 'taxonomy', 'operator' => '==', 'value' => 'product_cat']]],
        'active' => true,
    ]);
    acf_add_local_field_group([
        'key' => 'group_dona_product_hover_images', 'title' => 'DONA — Product hover images',
        'fields' => [
            ['key' => 'field_dona_home_hover_image', 'label' => 'Homepage hover image', 'name' => 'dona_home_hover_image',
                'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'library' => 'all',
                'instructions' => 'Second image shown when hovering over a product card on the homepage. Leave empty to disable.'],
            ['key' => 'field_dona_shop_hover_image', 'label' => 'Shop hover image', 'name' => 'dona_shop_hover_image',
                'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'library' => 'all',
                'instructions' => 'Second image shown on Shop and product category/tag pages. Independent of the homepage. Leave empty to disable.'],
            ['key' => 'field_dona_shop_hover_video', 'label' => 'Shop hover video', 'name' => 'dona_shop_hover_video',
                'type' => 'file', 'return_format' => 'id', 'library' => 'all', 'mime_types' => 'mp4,webm',
                'instructions' => 'Optional muted looping video for Shop and category cards. Takes priority over Shop hover image. Leave empty to use the hover image.'],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]],
        'position' => 'normal', 'active' => true,
    ]);
    $fields = [[
        'key' => 'field_dona_product_video', 'label' => 'Background video', 'name' => 'dona_product_video',
        'type' => 'file', 'return_format' => 'id', 'library' => 'all', 'mime_types' => 'mp4,webm',
        'instructions' => 'First product video. Additional videos can be selected in the separate Videos & lower gallery field group.',
    ]];
    for ($i = 1; $i <= 5; $i++) {
        $fields[] = ['key' => 'field_dona_editorial_image_' . $i, 'label' => 'Editorial image ' . $i,
            'name' => 'dona_editorial_image_' . $i, 'type' => 'image', 'return_format' => 'id',
            'preview_size' => 'thumbnail', 'library' => 'all'];
    }
    acf_add_local_field_group(['key' => 'group_dona_product_media', 'title' => 'DONA — Product video & editorial gallery',
        'fields' => $fields, 'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]],
        'position' => 'normal', 'style' => 'default', 'active' => true]);
    $lower = [['key'=>'field_dona_lower_media_gallery', 'name'=>'dona_lower_media_gallery',
        'label'=>'Gallery below video', 'type'=>'dona_mixed_media',
        'instructions'=>'Choose images, videos, or both. Drag to reorder.']];
    $editing_id = absint($_GET['post'] ?? $_POST['post_ID'] ?? 0);
    $editing_product = $editing_id ? wc_get_product($editing_id) : false;
    if ($editing_product && $editing_product->is_type('variable')) {
        array_unshift($lower, ['key'=>'field_dona_lower_defaults_tab', 'label'=>'Product defaults', 'type'=>'tab']);
        foreach (dona_product_colors($editing_product) as $key => $color) {
            $prefix = 'dona_lower_color_'.$key.'_';
            $lower[] = ['key'=>'field_'.$prefix.'tab', 'label'=>$color['label'], 'type'=>'tab'];
            $lower[] = ['key'=>'field_'.$prefix.'enabled', 'name'=>$prefix.'enabled', 'label'=>'Use media for this color',
                'type'=>'true_false', 'ui'=>1, 'default_value'=>0,
                'instructions'=>'Enable to use this color’s video below product details, additional videos and images below them. Empty fields are skipped. Disabled uses Product defaults. Shared across all sizes. Save Attributes and reload to show new colors.'];
            $lower[] = ['key'=>'field_'.$prefix.'video_1', 'name'=>$prefix.'video_1',
                'label'=>'Video below product details', 'type'=>'file', 'return_format'=>'id',
                'library'=>'all', 'mime_types'=>'mp4,webm'];
            $lower[] = ['key'=>'field_'.$prefix.'gallery', 'name'=>$prefix.'gallery',
                'label'=>'Gallery below video', 'type'=>'dona_mixed_media',
                'instructions'=>'Choose images, videos, or both for this color. Drag to reorder.'];
        }
    }
    acf_add_local_field_group(['key' => 'group_dona_lower_media', 'title' => 'DONA — Videos & lower gallery',
        'fields' => $lower, 'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]],
        'position' => 'normal', 'active' => true]);
}, 20);
function dona_lower_media_ids($product_id, $color_key = '', $kind = 'video') {
    $prefix = $color_key ? 'dona_lower_color_'.$color_key.'_' : '';
    if ($prefix && !get_post_meta($product_id, $prefix.'enabled', true)) $prefix = '';
    if ($kind === 'image') return dona_mixed_gallery_ids($product_id, $prefix);
    $field = $prefix ? $prefix.'video_1' : 'dona_product_video';
    $id = absint(get_post_meta($product_id, $field, true));
    return $id && in_array(get_post_mime_type($id), ['video/mp4', 'video/webm'], true) ? [$id] : [];

}
function dona_lower_media_union($post, $kind) {
    $ids = dona_lower_media_ids($post->ID, '', $kind);
    $product = wc_get_product($post->ID);
    foreach ($product ? dona_product_colors($product) : [] as $key => $color) $ids = array_merge($ids, dona_lower_media_ids($post->ID, $key, $kind));
    return array_values(array_unique($ids));
}
function dona_product_videos($post) {
    return dona_lower_media_union($post, 'video');
}
function dona_lower_gallery_images($post) {
    return dona_lower_media_union($post, 'image');
}
function dona_product_video($post) {
    $id = absint(get_post_meta($post->ID, 'dona_product_video', true));
    return $id && str_starts_with((string) get_post_mime_type($id), 'video/') ? wp_get_attachment_url($id) : '';
}
function dona_product_hover_image($post) {
    $id = absint(get_post_meta($post->ID, 'dona_home_hover_image', true));
    return $id && wp_attachment_is_image($id) ? $id : null;
}
function dona_product_shop_hover_image($post) {
    $id = absint(get_post_meta($post->ID, 'dona_shop_hover_image', true));
    return $id && wp_attachment_is_image($id) ? $id : null;
}
function dona_product_shop_hover_video($post) {
    $id = absint(get_post_meta($post->ID, 'dona_shop_hover_video', true));
    return $id && str_starts_with((string) get_post_mime_type($id), 'video/') ? wp_get_attachment_url($id) : '';
}
function dona_collection_editorial_image($term) {
    $id = absint(get_term_meta($term->term_id, 'dona_collection_editorial_image', true));
    if ($id && wp_attachment_is_image($id)) return $id;
    $id = absint(get_term_meta($term->term_id, 'thumbnail_id', true));
    return $id && wp_attachment_is_image($id) ? $id : null;
}
add_filter('woocommerce_product_get_image', function ($html, $product) {
    if (is_admin() || !(is_shop() || is_product_taxonomy())) return $html;
    if ($product->get_image_id()) $html = wp_get_attachment_image($product->get_image_id(), 'large', false, ['alt' => $product->get_name(), 'sizes' => '(max-width: 959px) 48vw, (max-width: 1199px) 30vw, 24vw']);
    $tags = new WP_HTML_Tag_Processor($html);
    if ($tags->next_tag('IMG')) $tags->add_class('el-image');
    $html = $tags->get_updated_html();
    $id = dona_product_shop_hover_image(get_post($product->get_id()));
    $videoId = absint(get_post_meta($product->get_id(), 'dona_shop_hover_video', true));
    $videoUrl = $videoId && str_starts_with((string) get_post_mime_type($videoId), 'video/') ? wp_get_attachment_url($videoId) : '';
    if (!$id && !$videoUrl) return $html;
    $hover = $id ? wp_get_attachment_image($id, 'woocommerce_thumbnail', false, ['class' => 'dona-shop-hover-image', 'alt' => '', 'aria-hidden' => 'true']) : '';
    $video = $videoUrl ? '<video class="dona-shop-hover-video" data-src="' . esc_url($videoUrl) . '" muted loop playsinline preload="none" aria-hidden="true" tabindex="-1"></video>' : '';
    return '<span class="dona-shop-product-media">' . $html . $hover . $video . '</span>';
}, 10, 2);
add_filter('woocommerce_product_loop_title_classes', function ($classes) {
    return is_shop() || is_product_taxonomy() ? $classes . ' el-title' : $classes;
});
function dona_product_editorial_images($post) {
    // The color gallery already displays these photographs; skip the hidden legacy copy.
    if (dona_uses_color_gallery($post->ID)) return [];
    $ids = [];
    for ($i = 1; $i <= 5; $i++) {
        $id = absint(get_post_meta($post->ID, 'dona_editorial_image_' . $i, true));
        if ($id && wp_attachment_is_image($id)) $ids[] = $id;
    }
    $product = wc_get_product($post->ID);
    return $ids ?: ($product ? array_slice($product->get_gallery_image_ids(), 0, 5) : []);
}
function dona_product_card_details($post) {
    $product = wc_get_product($post->ID);
    if (!$product) return '';
    $note = $product->get_meta('_dona_delivery_note');
    return ($note ? '<div class="dona-delivery-note">'.esc_html($note).'</div>' : '').'<div class="dona-price">'.wp_kses_post($product->get_price_html()).'</div>';
}

function dona_card_purchase($product) {
    if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) return null;
    $id = $product->get_id();
    $choice = '';
    if ($product->is_type('variable')) {
        $defaults = $product->get_default_attributes();
        $attrs = [];
        foreach ($defaults as $key => $value) $attrs['attribute_' . $key] = $value;
        $id = WC_Data_Store::load('product')->find_matching_product_variation($product, $attrs);
        $variation = $id ? wc_get_product($id) : false;
        if (!$variation || !$variation->is_purchasable() || !$variation->is_in_stock()) return null;
        $choice = implode(', ', $defaults);
    } elseif (!$product->is_type('simple')) return null;
    return ['id' => $id, 'label' => 'Add to cart — ' . $product->get_name() . ($choice ? ', ' . $choice : '')];
}
add_filter('woocommerce_get_price_html', function ($html, $product) {
    if (is_admin()) return $html;
    $purchase = dona_card_purchase($product);
    if (!$purchase) return $html;
    return $html . '<span hidden class="dona-home-cart-data" data-cart-id="' . absint($purchase['id']) . '" data-cart-label="' . esc_attr($purchase['label']) . '"></span>';
}, 30, 2);
add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product) {
    $purchase = dona_card_purchase($product);
    if (!$purchase) return $html;
    return '<a href="' . esc_url(add_query_arg('add-to-cart', $purchase['id'], wc_get_cart_url())) . '" data-product_id="' . absint($purchase['id']) . '" data-quantity="1" class="button add_to_cart_button ajax_add_to_cart" aria-label="' . esc_attr($purchase['label']) . '">Add to cart</a>';
}, 30, 2);
add_filter('woocommerce_product_add_to_cart_aria_describedby', function ($text, $product) {
    $purchase = dona_card_purchase($product);
    return $purchase ? $purchase['label'] : $text;
}, 30, 2);

function dona_catalog_card($product) {
    $purchase = dona_card_purchase($product);
    return ['id' => $product->get_id(), 'name' => $product->get_name(), 'url' => $product->get_permalink(),
        'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src(),
        'price' => wp_kses_post(wc_price($product->get_price())), 'cartId' => $purchase ? $purchase['id'] : 0,
        'cartLabel' => $purchase ? $purchase['label'] : '', 'available' => $product->is_in_stock()];
}

// Favorites store only product IDs locally; product information comes from WooCommerce.
add_action('wp_footer', function () {
    if (!function_exists('WC')) return;
    $current = is_product() ? wc_get_product(get_queried_object_id()) : false;
    wp_localize_script('dona-product-swatches', 'donaFavorites', ['url' => WC_AJAX::get_endpoint('dona_favorites'), 'currency' => get_woocommerce_currency(), 'seed' => $current ? [dona_catalog_card($current)] : []]);
    if (!is_product()) return;
    $product = wc_get_product(get_queried_object_id());
    if (!$product) return;
    echo '<div id="dona-product-favorite" hidden><button type="button" class="dona-favorite-toggle" data-product-id="' . absint($product->get_id()) . '" aria-pressed="false"><span class="dona-favorite-icon" aria-hidden="true">' . file_get_contents(get_stylesheet_directory() . '/assets/header/heart.svg') . '</span> <span class="dona-favorite-label">Add to Wishlist</span></button><span class="dona-favorite-status" role="status"></span></div>';
});
add_action('wc_ajax_dona_favorites', function () {
    $raw = sanitize_text_field(wp_unslash($_GET['ids'] ?? ''));
    $ids = array_slice(array_unique(array_filter(array_map('absint', explode(',', $raw)))), 0, 100);
    $items = [];
    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (!$product || $product->get_status() !== 'publish' || $product->is_type('variation') || $product->get_catalog_visibility() === 'hidden') continue;
        $items[] = dona_catalog_card($product);
    }
    wp_send_json_success($items);
});

// Use existing product media for the native thumbnail gallery when no gallery is set.
add_filter('woocommerce_product_get_gallery_image_ids', function ($ids, $product) {
    if (!is_admin() && is_product() && dona_uses_color_gallery($product->get_id())) return [];
    if ($ids || is_admin()) return $ids;
    for ($i = 1; $i <= 5; $i++) {
        $id = absint(get_post_meta($product->get_id(), 'dona_editorial_image_' . $i, true));
        if ($id && $id !== $product->get_image_id() && wp_attachment_is_image($id)) $ids[] = $id;
    }
    return array_values(array_unique($ids));
}, 20, 2);

add_action('wp_footer', function () {
    if (!is_product()) return;
    $product = wc_get_product(get_queried_object_id());
    if (!$product) return;
    $url = $product->get_permalink();
    $title = $product->get_name();
    $links = [
        'facebook' => ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url)],
        'twitter' => ['X', 'https://twitter.com/intent/tweet?url=' . rawurlencode($url) . '&text=' . rawurlencode($title)],
        'pinterest' => ['Pinterest', 'https://pinterest.com/pin/create/button/?url=' . rawurlencode($url) . '&media=' . rawurlencode(wp_get_attachment_url($product->get_image_id()) ?: '') . '&description=' . rawurlencode($title)],
        'telegram' => ['Telegram', 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($title)],
        'whatsapp' => ['WhatsApp', 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url)],
        'mail' => ['Email', 'mailto:?subject=' . rawurlencode($title) . '&body=' . rawurlencode($url)],
    ];
    ?>
    <div id="dona-product-share" hidden>
      <button type="button" class="dona-share-toggle" aria-label="Share product"><svg width="21" height="24" viewBox="0 0 24 28" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><path d="M12 19V2m-6 6 6-6 6 6M3 14v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V14"/></svg></button>
      <div class="dona-share-popup uk-drop" uk-drop="mode: click; pos: bottom-right; offset: 12" role="dialog" aria-label="Share this product">
        <button class="uk-drop-close dona-share-close" type="button" uk-close aria-label="Close sharing"></button>
        <h3>Copy Link</h3>
        <div class="dona-share-copy"><input readonly aria-label="Product link" value="<?php echo esc_attr($url); ?>"><button type="button" class="dona-copy-link" aria-label="Copy product link" uk-icon="link"></button></div>
        <p class="dona-share-status" role="status"></p>
        <h3>Share Now</h3><div class="dona-share-networks">
        <?php foreach ($links as $icon => [$label, $href]): ?>
          <a href="<?php echo esc_url($href); ?>" <?php if ($icon !== 'mail') echo 'target="_blank" rel="noopener noreferrer"'; ?> aria-label="<?php echo esc_attr('Share via ' . $label); ?>" title="<?php echo esc_attr($label); ?>" uk-icon="<?php echo esc_attr($icon); ?>"></a>
        <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php
});

/** Color galleries are shared by every size of a color. */
function dona_product_colors($product) {
    $colors = [];
    foreach ($product->get_attributes() as $attribute) {
        if (!in_array(sanitize_title($attribute->get_name()), ['color', 'colour', 'pa_color', 'pa_colour'], true) || !$attribute->get_variation()) continue;
        foreach ($attribute->get_options() as $option) {
            $term = $attribute->is_taxonomy() ? get_term($option, $attribute->get_name()) : null;
            $value = $term && !is_wp_error($term) ? $term->slug : (string) $option;
            $colors[sanitize_title($value)] = ['value' => $value, 'label' => $term && !is_wp_error($term) ? $term->name : $value];
        }
    }
    return $colors;
}
function dona_color_gallery_fields($product) {
    $saved = (array) $product->get_meta('_dona_color_galleries');
    echo '<p>Choose all photographs for each color, in display order. The first image is the main image for that color. Click multiple images to select them. Each color uses only its own selected images; empty galleries do not inherit the main product image. Galleries are shared across sizes. New colors appear after saving Attributes.</p>';
    foreach (dona_product_colors($product) as $key => $color) {
        $ids = array_filter(array_map('absint', $saved[$key] ?? []));
        echo '<div class="dona-admin-color-gallery" data-color="'.esc_attr($key).'" style="border-top:1px solid #ddd;padding:16px 0"><h3 style="padding:0">'.esc_html($color['label']).' gallery</h3><input type="hidden" name="dona_color_gallery['.esc_attr($key).']" value="'.esc_attr(implode(',', $ids)).'"><div class="dona-admin-gallery-images" style="display:flex;gap:8px;flex-wrap:wrap">';
        foreach ($ids as $id) echo '<span data-id="'.absint($id).'">'.wp_get_attachment_image($id,'thumbnail',false,['style'=>'width:70px;height:85px;object-fit:cover;cursor:move']).'</span>';
        echo '</div><p><button type="button" class="button dona-select-color-gallery">Choose images</button> <button type="button" class="button-link dona-clear-color-gallery">Clear gallery</button></p></div>';
    }
    if (!dona_product_colors($product)) echo '<p>Add a Color attribute, enable “Used for variations”, and save Attributes to create color galleries.</p>';
}
add_action('acf/include_field_types', function () {
    if (!class_exists('acf_field')) return;
    class DONA_Color_Gallery_Field extends acf_field {
        public function initialize() {
            $this->name = 'dona_color_galleries';
            $this->label = 'DONA color galleries';
            $this->category = 'content';
            $this->defaults = [];
        }
        public function render_field($field) {
            $id = absint(get_the_ID());
            if (!$id || !($product = wc_get_product($id))) return;
            wp_nonce_field('dona_color_galleries', 'dona_color_galleries_nonce');
            echo '<div id="dona-color-gallery-fields">';
            dona_color_gallery_fields($product);
            echo '</div><p><button type="button" class="button dona-refresh-color-galleries">Refresh colors</button></p><p class="dona-gallery-admin-status" role="status"></p>';
        }
    }
    acf_register_field_type('DONA_Color_Gallery_Field');
});
add_action('woocommerce_admin_process_product_object', function ($product) {
    if (!isset($_POST['dona_color_galleries_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dona_color_galleries_nonce'])), 'dona_color_galleries') || !current_user_can('edit_post', $product->get_id())) return;
    $input = isset($_POST['dona_color_gallery']) && is_array($_POST['dona_color_gallery']) ? wp_unslash($_POST['dona_color_gallery']) : [];
    $saved = (array) $product->get_meta('_dona_color_galleries');
    foreach (dona_product_colors($product) as $key => $color) {
        if (!array_key_exists($key, $input) || !is_string($input[$key])) continue;
        $saved[$key] = array_values(array_unique(array_filter(array_map('absint', explode(',', $input[$key])), 'wp_attachment_is_image')));
    }
    $product->update_meta_data('_dona_color_galleries', $saved);

});
add_action('wp_ajax_dona_color_gallery_fields', function () {
    check_ajax_referer('dona_color_galleries', 'nonce');
    $id = absint($_POST['product_id'] ?? 0);
    if (!current_user_can('edit_post', $id) || !($product = wc_get_product($id))) wp_send_json_error();
    ob_start(); dona_color_gallery_fields($product); wp_send_json_success(ob_get_clean());
});
add_action('admin_enqueue_scripts', function () {
    if (get_current_screen()?->id !== 'product') return;
    wp_enqueue_media(); wp_enqueue_script('jquery-ui-sortable');
});
add_action('admin_footer', function () {
    if (get_current_screen()?->id !== 'product') return;
    ?>
    <script>
    jQuery(function($){
        function sortable(){ $('.dona-admin-gallery-images').sortable({update:function(){const row=$(this).closest('.dona-admin-color-gallery');row.find('input').val($(this).children().map(function(){return this.dataset.id}).get().join(','));}}); }
        sortable();
        $(document).on('click','.dona-select-color-gallery',function(){
            const row=$(this).closest('.dona-admin-color-gallery');
            const frame=wp.media({title:row.find('h3').text(),button:{text:'Use these images'},library:{type:'image'},multiple:'add'});
            frame.on('open',function(){const selection=frame.state().get('selection');(row.find('input').val()||'').split(',').filter(Boolean).forEach(id=>selection.add(wp.media.attachment(id)));});
            frame.on('select',function(){const images=frame.state().get('selection').toJSON();row.find('input').val(images.map(i=>i.id).join(','));const preview=row.find('.dona-admin-gallery-images').empty();images.forEach(i=>$('<span></span>').attr('data-id',i.id).append($('<img>').attr({src:i.sizes?.thumbnail?.url||i.url,alt:i.alt||''}).css({width:70,height:85,objectFit:'cover',cursor:'move'})).appendTo(preview));sortable();});frame.open();
        }).on('click','.dona-clear-color-gallery',function(){const row=$(this).closest('.dona-admin-color-gallery');row.find('input').val('');row.find('.dona-admin-gallery-images').empty();});
        function refresh(){
            const pending={};$('.dona-admin-color-gallery').each(function(){pending[this.dataset.color]=$(this).find('input').val();});
            $('.dona-gallery-admin-status').text('Loading colors…');
            $.post(ajaxurl,{action:'dona_color_gallery_fields',product_id:$('#post_ID').val(),nonce:$('#dona_color_galleries_nonce').val()}).done(function(r){if(!r.success){$('.dona-gallery-admin-status').text('Unable to load colors.');return;}$('#dona-color-gallery-fields').html(r.data);Object.entries(pending).forEach(([key,value])=>{const row=$('.dona-admin-color-gallery').filter(function(){return this.dataset.color===key});row.find('input').val(value);const preview=row.find('.dona-admin-gallery-images').empty();value.split(',').filter(Boolean).forEach(id=>{const a=wp.media.attachment(id);a.fetch().then(()=>{const i=a.toJSON();$('<span></span>').attr('data-id',id).append($('<img>').attr('src',i.sizes?.thumbnail?.url||i.url).css({width:70,height:85,objectFit:'cover',cursor:'move'})).appendTo(preview);});});sortable();});$('.dona-gallery-admin-status').text('Colors updated. Click Update to save your galleries.');}).fail(()=>$('.dona-gallery-admin-status').text('Unable to load colors. Please try again.'));
        }
        $(document).on('click','.dona-refresh-color-galleries',refresh);
        $(document).ajaxComplete(function(e,xhr,settings){if(typeof settings.data==='string'&&/(^|&)action=woocommerce_save_attributes(&|$)/.test(settings.data))refresh();});
    });
    </script>
    <?php
});
add_action('wp_footer', function () {
    if (!is_product()) return;
    $product = wc_get_product(get_queried_object_id());
    if (!$product || !$product->is_type('variable')) return;
    $saved = (array) $product->get_meta('_dona_color_galleries'); $galleries = [];
    foreach (dona_product_colors($product) as $key => $color) {
        $ids = $saved[$key] ?? [];
        $images = [];
        foreach (array_unique(array_map('absint',$ids)) as $id) if (wp_attachment_is_image($id)) {
            $images[] = ['html'=>wp_get_attachment_image($id,'large',false,['class'=>'dona-color-full-image','loading'=>'lazy','sizes'=>'(max-width: 959px) 100vw, 48vw']), 'full'=>wp_get_attachment_url($id),'thumb'=>wp_get_attachment_image_url($id,'thumbnail'),'alt'=>get_post_meta($id,'_wp_attachment_image_alt',true) ?: $product->get_name().' — '.$color['label']];
        }
        $galleries[$color['value']] = $images;
    }
    $defaults = $product->get_default_attributes();
    $default_color = $defaults['color'] ?? $defaults['pa_color'] ?? $defaults['colour'] ?? $defaults['pa_colour'] ?? '';
    echo '<script type="application/json" id="dona-color-gallery-data" data-default-color="'.esc_attr($default_color).'">'.wp_json_encode($galleries,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
});

// Lower media is keyed by the same values as the working main color gallery.
add_action('wp_footer', function () {
    if (!is_product() || !($product = wc_get_product(get_queried_object_id())) || !$product->is_type('variable')) return;
    $urls = function ($ids) { return array_values(array_filter(array_map('wp_get_attachment_url', $ids))); };
    $read = function ($key) use ($product, $urls) {
        return ['videos'=>$urls(dona_lower_media_ids($product->get_id(), $key, 'video')),
            'images'=>$urls(dona_lower_media_ids($product->get_id(), $key, 'image'))];
    };
    $data = ['defaults'=>$read(''), 'colors'=>[]];
    foreach (dona_product_colors($product) as $key => $color) $data['colors'][$color['value']] = $read($key);
    echo '<script type="application/json" id="dona-lower-color-data">'.wp_json_encode($data, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
});

// Mark the existing native sections in memory, preserving the saved Builder layout.
add_filter('option_yootheme', function ($value) {
    if (is_admin() || !is_string($value)) return $value;
    $config = json_decode($value, true);
    if (!is_array($config)) return $value;
    $mark = function (&$node) use (&$mark) {
        if (!is_array($node)) return;
        if (($node['type'] ?? '') === 'section') {
            $serialized = wp_json_encode($node);
            if (str_contains($serialized, 'singleProduct.donaProductVideos') || str_contains($serialized, 'singleProduct.donaLowerGalleryImages')) {
                $node['props']['class'] = trim(($node['props']['class'] ?? '').' dona-lower-color-media');
            }
        }
        foreach ($node as &$child) if (is_array($child)) $mark($child);
    };
    foreach ($config['templates'] ?? [] as $key => $template) {
        if (($template['type'] ?? '') === 'single-product' && isset($config['templates'][$key]['layout'])) $mark($config['templates'][$key]['layout']);
    }
    return wp_json_encode($config);
});

function dona_mixed_gallery_ids($product_id, $prefix = '') {
    $field = $prefix ? $prefix.'gallery' : 'dona_lower_media_gallery';
    if (metadata_exists('post', $product_id, $field)) {
        $ids = get_post_meta($product_id, $field, true);
        $ids = is_array($ids) ? $ids : explode(',', (string) $ids);
    } else {
        $ids = [];
        for ($i=2; $i<=5; $i++) $ids[] = get_post_meta($product_id, $prefix ? $prefix.'video_'.$i : 'dona_product_video_'.$i, true);
        for ($i=1; $i<=5; $i++) $ids[] = get_post_meta($product_id, $prefix ? $prefix.'image_'.$i : 'dona_lower_image_'.$i, true);
    }
    return dona_valid_gallery_ids($ids);
}
function dona_valid_gallery_ids($ids) {
    return array_values(array_unique(array_filter(array_map('absint', $ids), function ($id) {
        return $id && (wp_attachment_is_image($id) || in_array(get_post_mime_type($id), ['video/mp4', 'video/webm'], true));
    })));
}
add_action('acf/include_field_types', function () {
    if (!class_exists('acf_field')) return;
    class DONA_Mixed_Media_Field extends acf_field {
        public function initialize() { $this->name='dona_mixed_media'; $this->label='DONA mixed media gallery'; $this->category='content'; }
        public function render_field($field) {
            $post_id = absint(get_the_ID());
            $prefix = $field['_name'] ?? $field['name'];
            $prefix = $prefix === 'dona_lower_media_gallery' ? '' : preg_replace('/gallery$/', '', $prefix);
            $ids = dona_mixed_gallery_ids($post_id, $prefix);
            echo '<div class="dona-mixed-media"><input type="hidden" name="'.esc_attr($field['name']).'" value="'.esc_attr(implode(',', $ids)).'"><div class="dona-mixed-preview" style="display:flex;gap:8px;flex-wrap:wrap">';
            foreach ($ids as $id) {
                $label = get_the_title($id);
                echo '<span data-id="'.$id.'" style="cursor:move;max-width:120px">';
                echo wp_attachment_is_image($id) ? wp_get_attachment_image($id,'thumbnail',false,['style'=>'width:80px;height:80px;object-fit:cover']) : '<span aria-hidden="true">▶</span> ';
                echo '<small style="display:block">'.esc_html($label).'</small></span>';
            }
            echo '</div><p><button type="button" class="button dona-mixed-choose">Choose images or videos</button> <button type="button" class="button-link dona-mixed-clear">Clear gallery</button></p></div>';
        }
        public function update_value($value, $post_id, $field) {
            return dona_valid_gallery_ids(is_array($value) ? $value : explode(',', (string) $value));
        }
    }
    acf_register_field_type('DONA_Mixed_Media_Field');
});
add_action('admin_footer', function () {
    if (get_current_screen()?->id !== 'product') return;
    ?>
    <script>
    jQuery(function($){
        function sortableMixed(){ $('.dona-mixed-preview').sortable({update:function(){const row=$(this).closest('.dona-mixed-media');row.find('input').val($(this).children().map(function(){return this.dataset.id;}).get().join(','));}}); }
        sortableMixed();
        $(document).on('click','.dona-mixed-choose',function(){
            const row=$(this).closest('.dona-mixed-media');
            const frame=wp.media({title:'Gallery below video',button:{text:'Use selected media'},library:{type:['image','video/mp4','video/webm']},multiple:'add'});
            frame.on('open',()=>{const selection=frame.state().get('selection');(row.find('input').val()||'').split(',').filter(Boolean).forEach(id=>selection.add(wp.media.attachment(id)));});
            frame.on('select',()=>{
                const items=frame.state().get('selection').toJSON();row.find('input').val(items.map(item=>item.id).join(','));
                const preview=row.find('.dona-mixed-preview').empty();
                items.forEach(item=>{const tile=$('<span>').attr('data-id',item.id).css({cursor:'move',maxWidth:120});if(item.type==='image')tile.append($('<img>').attr({src:item.sizes?.thumbnail?.url||item.url,alt:''}).css({width:80,height:80,objectFit:'cover'}));else tile.append($('<span aria-hidden="true">').text('▶'));tile.append($('<small>').text(item.title||item.filename).css('display','block'));preview.append(tile);});sortableMixed();
            });frame.open();
        }).on('click','.dona-mixed-clear',function(){const row=$(this).closest('.dona-mixed-media');row.find('input').val('');row.find('.dona-mixed-preview').empty();});
    });
    </script>
    <?php
});

function dona_uses_color_gallery($product_id) {
    $product = wc_get_product($product_id);
    return $product && $product->is_type('variable') && dona_product_colors($product) && (array) $product->get_meta('_dona_color_galleries');
}
// Suppress the last hidden native featured-image copy when color media owns the column.
add_filter('woocommerce_single_product_image_thumbnail_html', function ($html) {
    return is_product() && dona_uses_color_gallery(get_queried_object_id()) ? '' : $html;
});

// Editable Builder shortcode: real review totals and the standard WooCommerce review form.
add_shortcode('dona_product_reviews', function () {
    if (!is_product()) return '';
    $product = wc_get_product(get_queried_object_id());
    if (!$product || !wc_reviews_enabled()) return '';
    $count = $product->get_review_count();
    $label = $count ? sprintf(_n('%s review', '%s reviews', $count, 'woocommerce'), number_format_i18n($count)) : __('No reviews yet', 'woocommerce');
    ob_start();
    echo '<a class="dona-product-review-link" href="#dona-reviews">';
    $stars = wc_get_rating_html($product->get_average_rating(), $product->get_rating_count());
    echo $stars ?: '<span class="dona-empty-stars" aria-hidden="true">☆☆☆☆☆</span>';
    echo '<span>'.esc_html($label).'</span></a>';
    return ob_get_clean();
});

// Match the enhanced controls before the deferred swatch script runs.
add_filter('woocommerce_dropdown_variation_attribute_options_html',function ($html,$args) {
    if(!is_product())return $html;
    $attribute=strtolower((string)($args['attribute']??''));
    $class=in_array($attribute,['color','pa_color'],true)?'dona-color-select':(in_array($attribute,['size','pa_size'],true)?'dona-size-select':'');
    if(!$class)return $html;
    $tags=new WP_HTML_Tag_Processor($html);
    if($tags->next_tag('SELECT'))$tags->add_class($class);
    return $tags->get_updated_html();
},20,2);
