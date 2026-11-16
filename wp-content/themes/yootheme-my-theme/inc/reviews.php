<?php
/** Actual WooCommerce reviews, presented in an editable Builder section. */
add_shortcode('dona_reviews_section', function () {
    if (!is_product() || !wc_reviews_enabled()) return '';
    $product = wc_get_product(get_queried_object_id());
    if (!$product) return '';
    $count = $product->get_review_count();
    $ratings = $product->get_rating_counts();
    $rated = array_sum($ratings);
    ob_start(); ?>
    <div class="dona-reviews" id="dona-reviews">
      <h2>Reviews</h2>
      <div class="dona-reviews-summary">
        <p class="dona-reviews-average"><?php if ($rated): ?><strong><?php echo esc_html(number_format_i18n($product->get_average_rating(),1)); ?></strong> <?php echo wc_get_rating_html($product->get_average_rating(), $rated); ?><?php endif; ?><span><?php echo $count ? esc_html(sprintf(_n('Based on %s review','Based on %s reviews',$count,'woocommerce'),number_format_i18n($count))) : 'No reviews yet'; ?></span></p>
        <ol class="dona-review-distribution" aria-label="Rating breakdown">
        <?php for ($rating=5;$rating>=1;$rating--): $total=(int)($ratings[$rating]??0); ?>
          <li><span><?php echo $rating; ?> <span aria-hidden="true">★</span><span class="screen-reader-text"> stars</span></span><meter min="0" max="<?php echo max(1,$rated); ?>" value="<?php echo $total; ?>" aria-label="<?php echo esc_attr($rating.' star ratings: '.$total); ?>"></meter><span><?php echo esc_html(number_format_i18n($total)); ?></span></li>
        <?php endfor; ?>
        </ol>
      </div>
      <div class="dona-review-actions"><label>Filter reviews <select data-dona-review-filter><option value="all">All ratings</option><?php for($rating=5;$rating>=1;$rating--): ?><option value="<?php echo $rating; ?>"><?php echo $rating; ?> stars</option><?php endfor; ?></select></label><a class="uk-button uk-button-primary" href="#review_form_wrapper">Write a Review</a></div>
      <div class="dona-review-toolbar"><span><?php echo esc_html(sprintf(_n('%s review','%s reviews',$count,'woocommerce'),number_format_i18n($count))); ?></span><label>Sort <select data-dona-review-sort><option value="recent">Most Recent</option><option value="highest">Highest Rated</option><option value="lowest">Lowest Rated</option></select></label></div>
      <p class="dona-review-filter-status" role="status" hidden>No reviews match this rating on this page.</p>
      <?php comments_template(); ?>
    </div>
    <?php return ob_get_clean();
});
add_filter('comment_class', function ($classes, $css_class, $comment_id) {
    if (get_comment_type($comment_id)==='review') $classes[]='dona-rating-'.(int)get_comment_meta($comment_id,'rating',true);
    return $classes;
},10,3);
add_action('wp_enqueue_scripts', function () {
    if (!is_product()) return;
    wp_enqueue_style('dona-reviews',get_stylesheet_directory_uri().'/css/reviews.css',['dona-header'],filemtime(__DIR__.'/../css/reviews.css'));
    wp_enqueue_script('dona-reviews',get_stylesheet_directory_uri().'/js/reviews.js',[],filemtime(__DIR__.'/../js/reviews.js'),true);
});
