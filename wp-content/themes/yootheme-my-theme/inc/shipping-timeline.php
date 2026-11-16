<?php
/** Product delivery estimates, confirmed by the store owner. */
add_action('woocommerce_after_add_to_cart_form', function () {
    if (!is_product()) return;
    $date = current_datetime();
    $short = 'M j';
    $label = static function ($days) use ($date, $short) {
        return wp_date($short, $date->modify('+'.$days.' days')->getTimestamp());
    };
    $icons = get_stylesheet_directory().'/assets/header/';
    ?>
    <div class="dona-shipping-timeline" role="group" aria-label="Estimated shipping timeline">
      <ol>
        <li><span class="dona-shipping-icon" aria-hidden="true"><?php echo file_get_contents($icons.'cart.svg'); ?></span><strong><?php echo esc_html($label(0)); ?></strong><span>Purchased</span></li>
        <li><span class="dona-shipping-icon" aria-hidden="true"><svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4h10v10H2zM12 8h3.5l2.5 3v3h-6M2 14h2m4 0h5m4 0h1"/><circle cx="6" cy="15" r="2"/><circle cx="15" cy="15" r="2"/></svg></span><strong><?php echo esc_html($label(0).' – '.$label(1)); ?></strong><span>Processing</span></li>
        <li><span class="dona-shipping-icon" aria-hidden="true"><svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 8c0 4-6 10-6 10S4 12 4 8a6 6 0 1 1 12 0Z"/><circle cx="10" cy="8" r="2"/></svg></span><strong><?php echo esc_html($label(4).' – '.$label(7)); ?></strong><span>Delivered</span></li>
      </ol>
      <p>Estimated delivery dates.</p>
    </div>
    <?php
}, 5);
