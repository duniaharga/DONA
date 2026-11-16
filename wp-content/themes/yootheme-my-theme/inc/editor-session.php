<?php
/** Ensure YOOtheme's existing preview cookie reaches the browser before its first iframe request. */
add_action('admin_init', function () {
    if (($_GET['action'] ?? '') !== 'kernel' || ($_GET['p'] ?? '') !== 'customizer') return;
    if (!current_user_can('edit_theme_options') || headers_sent() || !interface_exists('YOOtheme\\Config')) return;

    $config = YOOtheme\app(YOOtheme\Config::class);
    $secret = $config->get('app.secret');
    $theme_id = $config->get('theme.id');
    if (!$secret || !$theme_id) return;

    // Match YOOtheme's own LoadCustomizerSession and browser cookie exactly.
    // This selects preview mode; WordPress authentication and permissions still apply.
    $session = 'yootheme_customizer_' . md5(wp_get_session_token());
    $name = hash_hmac('md5', $session, $secret);
    setcookie($name, $theme_id, [
        'expires' => time() + DAY_IN_SECONDS,
        'path' => '/',
        'secure' => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
});
