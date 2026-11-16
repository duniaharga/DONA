<?php
/** A single Rank Math breadcrumb trail below the shared YOOtheme header. */
add_action('after_setup_theme',function () {
    add_theme_support('rank-math-breadcrumbs');
    remove_action('woocommerce_before_main_content','woocommerce_breadcrumb',20);
},30);
add_filter('rank_math/frontend/breadcrumb/settings',function ($settings) {
    $settings['home']=true;
    $settings['remove_title']=false;
    return $settings;
});
add_filter('rank_math/frontend/breadcrumb/strings',function ($strings) {
    $strings['home']='Home';
    return $strings;
});
function dona_site_breadcrumbs() {
    if (is_front_page()) return '';
    if (!function_exists('rank_math_get_breadcrumbs')) return '';
    $html=rank_math_get_breadcrumbs([
        'wrap_before'=>'<nav class="dona-breadcrumbs rank-math-breadcrumb" aria-label="Breadcrumb"><p>',
        'wrap_after'=>'</p></nav>',
        'separator'=>'›',
    ]);
    $tags=new WP_HTML_Tag_Processor($html);
    while ($tags->next_tag()) {
        if ($tags->has_class('last')) $tags->set_attribute('aria-current','page');
        if ($tags->has_class('separator')) $tags->set_attribute('aria-hidden','true');
    }
    return $tags->get_updated_html();
}
add_action('dynamic_sidebar_after',function ($sidebar) {
    static $rendered=false;
    // YOOtheme captures this top position and places it directly after the header.
    if ($rendered || is_admin() || is_feed() || !in_array($sidebar,['top','topsection','top:section'],true)) return;
    $html=dona_site_breadcrumbs();
    if (!$html) return;
    $rendered=true;
    echo '<div class="dona-breadcrumb-bar"><div class="uk-container uk-container-expand">'.wp_kses_post($html).'</div></div>';
},30);

// Products share the All Products route, independent of their category.
add_filter('rank_math/frontend/breadcrumb/items',function ($items) {
    if(!is_product())return $items;
    return [['Home',home_url('/')],['Shop',wc_get_page_permalink('shop')],[get_the_title(get_queried_object_id())]];
},20);
