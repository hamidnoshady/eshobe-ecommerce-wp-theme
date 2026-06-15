<?php
/**
 * Functions which enhance the theme by hooking into WordPress.
 *
 * @package WM_Theme
 */

function watchmid_body_classes( $classes ) {
    if ( ! is_singular() ) {
        $classes[] = 'hfeed';
    }
    if ( is_woocommerce() || is_product() || is_shop() || is_product_taxonomy() ) {
        $classes[] = 'watchmid-woocommerce';
    }
    $font = function_exists( 'wm_get_design_token' ) ? wm_get_design_token( 'wm_font_mode', 'vazirmatn' ) : get_theme_mod( 'watchmid_font_family', 'vazirmatn' );
    $classes[] = 'watchmid-font-' . sanitize_html_class( $font );

    if ( is_product() ) {
        $classes[] = 'watchmid-product-image-' . sanitize_html_class( get_theme_mod( 'watchmid_product_image_position', 'right' ) );
        if ( get_theme_mod( 'watchmid_product_gallery_sticky', true ) ) {
            $classes[] = 'watchmid-product-gallery-sticky';
        }
    }
    return $classes;
}
add_filter( 'body_class', 'watchmid_body_classes' );

function watchmid_get_design_customizer_css() {
    $content_width = absint( get_theme_mod( 'watchmid_content_width', 1320 ) );
    $section_gap   = absint( get_theme_mod( 'watchmid_product_section_gap', 20 ) );
    $density       = get_theme_mod( 'watchmid_product_card_density', 'comfortable' );
    $font          = get_theme_mod( 'watchmid_font_family', 'vazirmatn' );

    $content_width = min( max( $content_width, 1040 ), 1440 );
    $section_gap   = min( max( $section_gap, 12 ), 36 );
    $card_padding  = 'compact' === $density ? 16 : 20;
    $font_stack    = "'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";

    if ( 'peyda' === $font ) {
        $font_stack = "'Peyda', 'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    } elseif ( 'system' === $font ) {
        $font_stack = "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    }

    return ':root{--wm-content-width:' . $content_width . 'px;--wm-product-section-gap:' . $section_gap . 'px;--wm-product-card-padding:' . $card_padding . 'px;--wm-font-primary:' . $font_stack . ';}';
}
