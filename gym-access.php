<?php
/**
 * Plugin Name: Gym Access Manager
 * Description: Управление на фитнес карти и абонаменти (WooCommerce + RFID)
 * Version: 1.0.0
 * Requires PHP: 8.1
 */

defined('ABSPATH') || exit;

define('GYM_ACCESS_PATH', plugin_dir_path(__FILE__));

register_activation_hook(__FILE__, function () {
    require_once GYM_ACCESS_PATH . 'includes/install.php';
    gym_access_install();
    if (!wp_next_scheduled('gym_access_daily_emails')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'gym_access_daily_emails');
    }
});

register_deactivation_hook(__FILE__, function () {
    while ($t = wp_next_scheduled('gym_access_daily_emails')) {
        wp_unschedule_event($t, 'gym_access_daily_emails');
    }
});

require_once GYM_ACCESS_PATH . 'includes/wc-hooks.php';
require_once GYM_ACCESS_PATH . 'includes/email.php';
require_once GYM_ACCESS_PATH . 'includes/admin-ui.php';
require_once GYM_ACCESS_PATH . 'includes/rest-api.php';

add_action('init', function () {
    if (!wp_next_scheduled('gym_access_daily_emails')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'gym_access_daily_emails');
    }
}, 20);
