<?php
/**
 * Home bestselling products.
 *
 * @package WM_Theme
 */

if ( ! wm_home_enabled( 'home_bestsellers_enabled', true ) ) {
    return;
}

$products = wm_home_get_products( 'bestsellers', wm_home_get_option( 'home_bestsellers_count', 10 ) );
if ( empty( $products ) ) {
    return;
}

echo wm_render_product_carousel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $products,
    array(
        'title'    => wm_home_get_option( 'home_bestsellers_title', 'پرفروش‌ها' ),
        'subtitle' => wm_home_get_option( 'home_bestsellers_subtitle', 'محصولاتی که بیشتر انتخاب شده‌اند.' ),
        'class'    => 'wm-home-section wm-home-products wm-home-products--bestsellers',
    )
);

