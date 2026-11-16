<?php
/** In-page account drawer and native WooCommerce authentication. */
add_action('wp_enqueue_scripts',function () {
    if (!function_exists('WC')) return;
    wp_enqueue_style('dona-account',get_stylesheet_directory_uri().'/css/account.css',['dona-header'],filemtime(get_stylesheet_directory().'/css/account.css'));
    wp_enqueue_script('dona-account',get_stylesheet_directory_uri().'/js/account.js',['jquery','dona-product-swatches','wc-address-i18n'],filemtime(get_stylesheet_directory().'/js/account.js'),true);
    wp_localize_script('dona-account','donaAccount',[
        'url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('dona_account'),'accountUrl'=>wc_get_page_permalink('myaccount'),
        'productId'=>is_product()?get_queried_object_id():0,
    ]);
});

function dona_account_panel($tab='for-you',$order_id=0) {
    ob_start();
    if ($tab==='orders' && is_user_logged_in()) {
        echo '<h2 id="dona-account-drawer-title">Your orders</h2><button type="button" class="dona-account-back" data-account-refresh>Refresh orders</button>';
        if ($order_id) {
            $order=wc_get_order($order_id);
            if (!$order || (int)$order->get_customer_id()!==get_current_user_id()) {
                echo '<p role="alert">This order is unavailable.</p>';
            } else {
                echo '<button class="dona-account-back" type="button" data-account-panel="orders">← All orders</button>';
                echo '<h3>Order #'.esc_html($order->get_order_number()).'</h3><p>'.esc_html(wc_get_order_status_name($order->get_status())).'</p><ul class="dona-account-order-items">';
                foreach($order->get_items() as $item)echo '<li><span>'.esc_html($item->get_name()).' × '.absint($item->get_quantity()).'</span><span>'.wp_kses_post($order->get_formatted_line_subtotal($item)).'</span></li>';
                echo '</ul><p class="dona-account-order-total">Total: '.wp_kses_post($order->get_formatted_order_total()).'</p>';
            }
        } else {
            $orders=wc_get_orders(['customer_id'=>get_current_user_id(),'limit'=>10,'orderby'=>'date','order'=>'DESC']);
            if (!$orders)echo '<p>You haven’t placed an order yet.</p><a class="dona-account-primary" href="'.esc_url(wc_get_page_permalink('shop')).'">Explore the collection</a>';
            foreach($orders as $order)echo '<button type="button" class="dona-account-order" data-account-order="'.absint($order->get_id()).'"><span>Order #'.esc_html($order->get_order_number()).'<small>'.esc_html($order->get_date_created()?wc_format_datetime($order->get_date_created()):'').'</small></span><span>'.esc_html(wc_get_order_status_name($order->get_status())).'<small>'.wp_kses_post($order->get_formatted_order_total()).'</small></span><span aria-hidden="true">›</span></button>';
        }
    } elseif (in_array($tab,['profile','addresses'],true) && is_user_logged_in()) {
        $address=$tab==='addresses';
        echo '<h2 id="dona-account-drawer-title">'.($address?'Your addresses':'Account details').'</h2><div class="woocommerce dona-native-account" data-account-url="'.esc_url(wc_get_account_endpoint_url($address?'edit-address':'edit-account')).'"><div class="woocommerce-MyAccount-content">';
        global $post;
        $previous_post=$post;$post=get_post(wc_get_page_id('myaccount'));
        try {
            if($address)wc_get_template('myaccount/my-address.php');
            else WC_Shortcode_My_Account::edit_account();
        } finally {$post=$previous_post;}
        echo '</div></div>';
    } elseif ($tab==='for-you') {
        if (is_user_logged_in())echo '<h2 id="dona-account-drawer-title">Welcome back, '.esc_html(wp_get_current_user()->display_name).'.</h2><p>Your orders and recently viewed bags, all in one place.</p>';
        else echo '<h2 id="dona-account-drawer-title">Sign In To Track Your Orders.</h2><button type="button" class="dona-account-primary" data-account-signin>Sign In</button>';
        $hero=get_posts(['post_type'=>'attachment','name'=>'women-dunia-bag-1','posts_per_page'=>1]);
        echo '<a class="dona-account-discover" href="'.esc_url(wc_get_page_permalink('shop')).'">';
        if ($hero)echo wp_get_attachment_image($hero[0]->ID,'woocommerce_thumbnail',false,['alt'=>'Discover the DONA bag collection','loading'=>'eager','sizes'=>'400px']);
        echo '<span>Discover New <span aria-hidden="true">›</span></span></a>';
        echo '<section class="dona-account-shelf"><h3>Recently viewed</h3><div class="dona-account-products" data-account-products="recent"></div></section>';
    } else {
        echo '<h2 id="dona-account-drawer-title">'.($tab==='orders'?'Track your orders':'Your profile').'</h2><p>Sign in to view your '.($tab==='orders'?'order history':'account information').'.</p><button type="button" class="dona-account-primary" data-account-signin>Sign In</button>';
    }
    return ob_get_clean();
}

add_action('wp_footer',function () {
    if (!function_exists('WC'))return;
    ?>
    <div id="dona-account-drawer" uk-offcanvas="flip:true; overlay:true" aria-labelledby="dona-account-drawer-title" data-account-nonce="<?php echo esc_attr(wp_create_nonce('dona_account')); ?>">
      <div class="uk-offcanvas-bar dona-account-drawer-bar">
        <button class="uk-offcanvas-close" type="button" uk-close aria-label="Close account"></button>
        <nav class="dona-account-switcher" aria-label="Account sections">
          <?php foreach(['for-you'=>'For You','orders'=>'Orders','addresses'=>'Addresses','profile'=>'Account'] as $tab=>$label): ?>
          <button type="button" data-account-panel="<?php echo esc_attr($tab); ?>" aria-pressed="<?php echo $tab==='for-you'?'true':'false'; ?>"><?php echo esc_html($label); ?></button>
          <?php endforeach; ?>
        </nav>
        <div class="dona-account-scroll"><div class="dona-account-content"><?php echo dona_account_panel(); ?></div><p class="dona-account-status" role="status"></p></div>
        <button type="button" class="dona-account-logout" data-account-logout <?php echo is_user_logged_in()?'':'hidden'; ?>>Log out</button>
        <template id="dona-account-dashboard-template"><?php echo dona_account_panel('profile'); ?></template>
        <template id="dona-account-addresses-template"><?php echo dona_account_panel('addresses'); ?></template>
        <template id="dona-account-orders-template"><?php echo dona_account_panel('orders'); ?></template>
      </div>
    </div>
    <div id="dona-account-modal" class="uk-flex-top" uk-modal aria-labelledby="dona-account-title">
      <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical">
        <button class="uk-modal-close-default" type="button" uk-close aria-label="Close sign in"></button>
        <p class="dona-account-brand">D O N A</p><h2 id="dona-account-title">Sign in to DONA</h2>
        <div class="dona-account-tabs" role="group" aria-label="Account form"><button type="button" aria-pressed="true" data-account-tab="login">Sign In</button><?php if(get_option('woocommerce_enable_myaccount_registration')==='yes'): ?><button type="button" aria-pressed="false" data-account-tab="register">Create account</button><?php endif; ?></div>
        <div class="woocommerce dona-account-forms" data-account-view="login">
          <?php
          // Render guest forms even when the page was loaded by a signed-in shopper.
          ob_start();wc_get_template('myaccount/form-login.php');$forms=ob_get_clean();
          $forms=preg_replace('/<form\b/','<form action="'.esc_url(wc_get_page_permalink('myaccount')).'"',$forms);
          foreach(['username','password','rememberme','reg_username','reg_email','reg_password','customer_login','woocommerce-login-nonce','woocommerce-register-nonce'] as $id)$forms=str_replace(['id="'.$id.'"','for="'.$id.'"'],['id="dona-'.$id.'"','for="dona-'.$id.'"'],$forms);
          echo $forms;
          // Use Nextend's own OAuth button and popup flow on both account tabs.
          if (!is_user_logged_in() && class_exists('NextendSocialLogin') && NextendSocialLogin::isProviderEnabled('google')) {
              $google_button = NextendSocialLogin::shortcode([
                  'provider' => 'google',
                  'style' => 'fullwidth',
                  'customlabel' => 'Continue with Google',
              ]);
              if ($google_button) {
                  echo '<div class="dona-account-social"><p class="dona-account-social-divider">or</p>'.$google_button.'</div>';
              }
          }
          ?>
        </div>
        <form class="dona-account-reset" hidden><label for="dona-reset-email">Email address</label><input type="email" name="user_login" id="dona-reset-email" autocomplete="email" required><button type="submit" class="dona-account-primary">Send reset link</button><button type="button" class="dona-account-back" data-account-return-login>Back to sign in</button></form>
        <p class="dona-account-auth-status" role="status" aria-live="polite"></p>
      </div>
    </div>
    <?php
});

function dona_account_ajax() {
    if (!check_ajax_referer('dona_account','nonce',false))wp_send_json_error(['message'=>'Your session has expired. Refresh the page and try again.'],403);
    $operation=sanitize_key($_POST['operation']??'panel');
    if ($operation==='panel') {
        $tab=sanitize_key($_POST['tab']??'for-you');
        if(!in_array($tab,['for-you','orders','addresses','profile'],true))$tab='for-you';
        wp_send_json_success(['html'=>dona_account_panel($tab,absint($_POST['order_id']??0))]);
    }
    if ($operation==='logout') {
        wp_logout();wp_set_current_user(0);
        wp_send_json_success(['nonce'=>wp_create_nonce('dona_account'),'authenticated'=>is_user_logged_in(),'panels'=>['for-you'=>dona_account_panel(),'profile'=>dona_account_panel('profile'),'addresses'=>dona_account_panel('addresses'),'orders'=>dona_account_panel('orders')]]);
    }
    if (is_user_logged_in())wp_send_json_error(['message'=>'You are already signed in.']);
    if ($operation==='login') {
        $username=sanitize_text_field(wp_unslash($_POST['username']??''));$password=wp_unslash($_POST['password']??'');
        $error=apply_filters('woocommerce_process_login_errors',new WP_Error(),$username,$password);
        if(!$username||!$password)$error->add('missing','Enter your email or username and password.');
        if($error->has_errors())wp_send_json_error(['message'=>wp_strip_all_tags($error->get_error_message())]);
        $creds=apply_filters('woocommerce_login_credentials',['user_login'=>$username,'user_password'=>$password,'remember'=>!empty($_POST['rememberme'])]);
        $user=wp_signon($creds,is_ssl());
        if(is_wp_error($user))wp_send_json_error(['message'=>'We couldn’t sign you in. Check your details and try again.']);
        wp_set_current_user($user->ID);
    } elseif ($operation==='register') {
        if(get_option('woocommerce_enable_myaccount_registration')!=='yes')wp_send_json_error(['message'=>'Account registration is unavailable.']);
        $email=sanitize_email(wp_unslash($_POST['email']??''));$username=sanitize_user(wp_unslash($_POST['username']??''));$password=wp_unslash($_POST['password']??'');
        if(get_option('woocommerce_registration_generate_password')!=='yes'&&!$password)wp_send_json_error(['message'=>'Enter a password.']);
        $error=apply_filters('woocommerce_process_registration_errors',new WP_Error(),$username,$password,$email);
        if($error->has_errors())wp_send_json_error(['message'=>wp_strip_all_tags($error->get_error_message())]);
        $id=wc_create_new_customer($email,$username,$password);
        if(is_wp_error($id))wp_send_json_error(['message'=>wp_strip_all_tags($id->get_error_message())]);
        if(!apply_filters('woocommerce_registration_auth_new_customer',true,$id))wp_send_json_success(['message'=>'Your account was created. Check your email, then sign in.','requiresLogin'=>true]);
        wc_set_customer_auth_cookie($id);wp_set_current_user($id);
    } elseif ($operation==='reset') {
        $_POST['user_login']=sanitize_text_field(wp_unslash($_POST['user_login']??''));
        WC_Shortcode_My_Account::retrieve_password();wc_clear_notices();
        wp_send_json_success(['message'=>'If an account matches that email address, a reset link will be sent to your inbox.']);
    } else wp_send_json_error(['message'=>'Invalid account action.']);
    wp_send_json_success(['nonce'=>wp_create_nonce('dona_account'),'authenticated'=>is_user_logged_in(),'panels'=>['for-you'=>dona_account_panel(),'profile'=>dona_account_panel('profile'),'addresses'=>dona_account_panel('addresses'),'orders'=>dona_account_panel('orders')]]);
}
add_action('wp_ajax_dona_account','dona_account_ajax');
add_action('wp_ajax_nopriv_dona_account','dona_account_ajax');
