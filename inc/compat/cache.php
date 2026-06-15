<?php
/**
 * Compatibility with full-page caching plugins (e.g. FlyingPress).
 *
 * @package WM_Theme
 */

/**
 * Keep the header cart badges accurate on cached pages by refreshing them
 * through WooCommerce's built-in cart fragments AJAX request, which runs on
 * every page load regardless of page caching.
 */
function wm_cache_cart_fragments( $fragments ) {
    $count = ( function_exists( 'WC' ) && WC() && WC()->cart ) ? absint( WC()->cart->get_cart_contents_count() ) : 0;
    $label = esc_html( number_format_i18n( $count ) );

    $fragments['span.wm-site-header__cart-count'] = '<span class="wm-site-header__cart-count' . ( $count > 0 ? '' : ' wm-site-header__cart-count--hidden' ) . '">' . $label . '</span>';
    $fragments['span.wm-mobile-nav__badge']       = '<span class="wm-mobile-nav__badge' . ( $count > 0 ? '' : ' wm-mobile-nav__badge--hidden' ) . '">' . $label . '</span>';

    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'wm_cache_cart_fragments' );

/**
 * Exclude cart/checkout/account pages from full-page caching, since they
 * render session-specific content (cart contents, order history, etc.).
 */
function wm_cache_exclude_dynamic_pages() {
    if ( defined( 'DONOTCACHEPAGE' ) ) {
        return;
    }

    $is_dynamic = ( function_exists( 'is_cart' ) && is_cart() )
        || ( function_exists( 'is_checkout' ) && is_checkout() )
        || ( function_exists( 'is_account_page' ) && is_account_page() );

    if ( $is_dynamic ) {
        define( 'DONOTCACHEPAGE', true );
    }
}
add_action( 'template_redirect', 'wm_cache_exclude_dynamic_pages' );
