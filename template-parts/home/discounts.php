<?php
/**
 * Home discounted products carousel.
 *
 * @package WM_Theme
 */

if ( ! wm_home_enabled( 'home_discounts_enabled', true ) ) {
    return;
}

$products = wm_get_discounted_products( wm_home_get_option( 'home_discounts_count', 10 ) );
if ( empty( $products ) ) {
    return;
}

echo wm_render_product_carousel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $products,
    array(
        'title'    => wm_home_get_option( 'home_discounts_title', 'پیشنهادهای تخفیف‌دار' ),
        'subtitle' => wm_home_get_option( 'home_discounts_subtitle', 'محصولاتی که همین حالا با قیمت کمتر در دسترس‌اند.' ),
        'class'    => 'wm-home-section wm-home-products wm-home-products--discounts',
        'view_all' => wm_discount_archive_url(),
    )
);
