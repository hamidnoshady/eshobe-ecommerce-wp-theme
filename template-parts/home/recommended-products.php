<?php
/**
 * Home recommended products.
 *
 * @package WM_Theme
 */

if ( ! wm_home_enabled( 'home_recommended_enabled', true ) ) {
    return;
}

$products = wm_home_get_products( 'recommended', wm_home_get_option( 'home_recommended_count', 10 ) );
if ( empty( $products ) ) {
    return;
}

echo wm_render_product_carousel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $products,
    array(
        'title'    => wm_home_get_option( 'home_recommended_title', 'پیشنهاد ما' ),
        'subtitle' => wm_home_get_option( 'home_recommended_subtitle', 'محصولاتی که برای شروع انتخاب، ارزش دیدن دارند.' ),
        'class'    => 'wm-home-section wm-home-products wm-home-products--recommended',
        'view_all' => function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : '',
    )
);

