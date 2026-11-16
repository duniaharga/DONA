<?php
// Child theme functions

// DONA native header widget content and scoped presentation.
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dona-header', get_stylesheet_directory_uri().'/css/dona.css', [], filemtime(get_stylesheet_directory().'/css/dona.css'));
});
add_shortcode('dona_announcement', function () {
    $text = get_option('dona_announcement_text','Free international shipping on orders over USD $300');
    if (function_exists('wc_price') && has_filter('wcml_raw_price_amount')) {
        $threshold = get_woocommerce_currency().' '.wp_strip_all_tags(wc_price(apply_filters('wcml_raw_price_amount',300)));
        $text = str_replace('USD $300', $threshold, $text);
    }
    return '<div class="dona-announcement"><span class="dona-announcement-arrow dona-previous" aria-hidden="true"></span><span>'.esc_html(html_entity_decode($text, ENT_QUOTES, 'UTF-8')).'</span><span class="dona-announcement-arrow dona-next" aria-hidden="true"></span></div>';
});
add_shortcode('dona_header_actions', function ($atts) {
    if (!function_exists('WC')) return '';
    $asset=get_stylesheet_directory_uri().'/assets/header/';
    $icon=function($name)use($asset){
        $sizes=['account-head'=>[7.91667,7.91667],'account-body'=>[12.9167,6.25],'search-circle'=>[12.9167,12.9167],'search-handle'=>[5,5],'heart'=>[18.1481,16.0184],'cart'=>[20,20]];
        [$width,$height]=$sizes[$name]??[20,20];
        return '<img src="'.esc_url($asset.$name.'.svg').'" alt="" width="'.esc_attr($width).'" height="'.esc_attr($height).'">';
    };
    $count=WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    ob_start(); ?>
    <div class="dona-actions">
      <div class="dona-currency" aria-label="Store currency"><?php
          if (shortcode_exists('currency_switcher')) {
              echo do_shortcode('[currency_switcher format="%code% %symbol%" switcher_style="wcml-dropdown"]');
          } else {
              echo esc_html(get_woocommerce_currency().' '.html_entity_decode(get_woocommerce_currency_symbol()));
          }
      ?></div>
      <a class="dona-action dona-account" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="My account"><span class="dona-icon"><span class="dona-account-head"><?php echo $icon('account-head'); ?></span><span class="dona-account-body"><?php echo $icon('account-body'); ?></span></span></a>
      <button class="dona-action dona-wishlist" type="button" uk-toggle="target: #dona-wishlist" aria-label="Open favorites"><?php echo $icon('heart'); ?><span class="dona-wishlist-count" aria-hidden="true" hidden>0</span></button>
      <a class="dona-action dona-cart" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="<?php echo esc_attr(sprintf('Shopping bag, %d items', $count)); ?>"><span class="dona-icon"><?php echo $icon('cart'); ?></span><span class="dona-cart-count" aria-hidden="true"><?php echo absint($count); ?></span></a>
    </div>
    <?php return ob_get_clean();
});
add_filter('woocommerce_add_to_cart_fragments', function($fragments){
    $fragments['span.dona-cart-count']='<span class="dona-cart-count" aria-hidden="true">'.absint(WC()->cart->get_cart_contents_count()).'</span>';
    return $fragments;
});
add_action('wp_footer',function(){ ?>
<div id="dona-search" uk-modal aria-labelledby="dona-search-title"><div class="uk-modal-dialog uk-modal-body"><button class="uk-modal-close-default" type="button" uk-close aria-label="Close search"></button><h2 id="dona-search-title" class="uk-modal-title">Search bags</h2><?php if(function_exists('get_product_search_form')) get_product_search_form(); ?></div></div>
<div id="dona-wishlist" uk-offcanvas="flip: true; overlay: true" aria-labelledby="dona-favorites-title"><div class="uk-offcanvas-bar"><header><h2 id="dona-favorites-title">Favorites (<span class="dona-favorites-count">0</span>)</h2><button class="uk-offcanvas-close" type="button" uk-close aria-label="Close favorites"></button></header><p class="dona-favorites-status" role="status"></p><div class="dona-favorites-list"></div><footer><p>Saved in this browser.</p><a class="uk-button uk-button-primary" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Continue shopping</a></footer></div></div>
<?php });

add_action('wp_enqueue_scripts', function () {
    if (!is_front_page()) return;
    wp_enqueue_style('dona-confidence', get_stylesheet_directory_uri().'/css/quiet-confidence.css', ['dona-header'], filemtime(get_stylesheet_directory().'/css/quiet-confidence.css'));
});

require_once __DIR__ . '/inc/products.php';

require_once __DIR__ . '/inc/account.php';

// Native Figma editorial sections: scoped presentation, editable Builder content.
add_action('wp_enqueue_scripts', function () {
    if (!is_page('about')) return;
    wp_enqueue_style('dona-about', get_stylesheet_directory_uri().'/css/about.css', ['dona-header'], filemtime(get_stylesheet_directory().'/css/about.css'));
});

add_action('wp_enqueue_scripts', function () {
    if (!is_front_page()) return;
    wp_enqueue_style('dona-editorial-font', 'https://fonts.googleapis.com/css2?family=Jost:wght@400&display=swap', [], null);
    wp_enqueue_style('dona-editorial', get_stylesheet_directory_uri().'/css/editorial.css', ['dona-header'], filemtime(get_stylesheet_directory().'/css/editorial.css'));
});

// Admin-only spacing for the WooCommerce product list with Rank Math enabled.
add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-product') return;
    wp_enqueue_style('dona-admin-products', get_stylesheet_directory_uri().'/css/admin-products.css', [], filemtime(get_stylesheet_directory().'/css/admin-products.css'));
});

require_once __DIR__.'/inc/media-folders.php';
require_once __DIR__.'/inc/currency.php';

require_once __DIR__.'/inc/breadcrumbs.php';

require_once __DIR__.'/inc/shop-collections.php';

require_once __DIR__.'/inc/editor-session.php';
require_once __DIR__.'/inc/shipping-timeline.php';
require_once __DIR__.'/inc/reviews.php';

add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_checkout') || !is_checkout()) return;
    wp_enqueue_style('dona-checkout', get_stylesheet_directory_uri().'/css/checkout.css', ['dona-header'], filemtime(__DIR__.'/css/checkout.css'));
});

// Supply uncropped thumbnails to the native Store API cart/checkout summaries.
add_filter('woocommerce_store_api_cart_item_images', function ($images) {
    foreach ($images as $image) {
        $thumbnail = wp_get_attachment_image_url($image->id, 'medium');
        if (!$thumbnail) continue;
        $image->thumbnail = $thumbnail;
        $image->srcset = wp_get_attachment_image_srcset($image->id, 'medium') ?: '';
        $image->sizes = '80px';
        $image->thumbnail_srcset = $image->srcset;
        $image->thumbnail_sizes = '80px';
    }
    return $images;
});

add_action('wp_enqueue_scripts',function () {
    if (!is_front_page()) return;
    wp_enqueue_script('dona-home-accessibility',get_stylesheet_directory_uri().'/js/home-accessibility.js',[],filemtime(__DIR__.'/js/home-accessibility.js'),true);
});

// Include the visible DONA wordmark in its accessible home-link name.
add_filter('gettext_yootheme',function ($translation,$text) {
    return $text==='Back to home' ? 'D O N A — Back to home' : $translation;
},10,2);
require_once __DIR__.'/inc/seo.php';
require_once __DIR__.'/inc/performance.php';

require_once __DIR__.'/inc/product-experience.php';
