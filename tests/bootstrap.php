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

require_once __DIR__ . '/../inc/product-components.php';
