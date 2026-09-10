<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize Brain Monkey to load Patchwork early
\Brain\Monkey\setUp();
\Brain\Monkey\tearDown();

// Mock WP core functions used during include if any
if (!function_exists('add_action')) {
    function add_action() {}
}
if (!function_exists('add_shortcode')) {
    function add_shortcode() {}
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return $text; }
}

// WP time constants used by the theme's transient TTLs.
if (!defined('MINUTE_IN_SECONDS')) { define('MINUTE_IN_SECONDS', 60); }
if (!defined('HOUR_IN_SECONDS')) { define('HOUR_IN_SECONDS', 3600); }
if (!defined('DAY_IN_SECONDS')) { define('DAY_IN_SECONDS', 86400); }

require_once __DIR__ . '/../inc/product-components.php';
