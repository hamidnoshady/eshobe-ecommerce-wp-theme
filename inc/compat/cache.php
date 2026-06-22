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
 * Exclude cart/checkout/account pages, and any page viewed by a logged-in
 * visitor, from full-page caching.
 *
 * The OTP login flow can leave a visitor on any page (home, product,
 * archive) right after authenticating, since it redirects back to wherever
 * they were instead of forcing a trip through /my-account/. Those pages are
 * normally cached for guests, so without this, a freshly logged-in browser
 * (or LiteSpeed's edge cache) can keep serving the anonymous-rendered copy
 * until something forces a real network fetch — which is why a hard refresh
 * "fixes" it but a normal navigation/login doesn't. Setting DONOTCACHEPAGE
 * only signals PHP-level cache plugins; we also send real Cache-Control
 * headers (for browsers/proxies) and LiteSpeed's own no-cache header (for
 * its edge/server-level cache, which doesn't always key off DONOTCACHEPAGE).
 */
function wm_cache_exclude_dynamic_pages() {
    $is_dynamic = is_user_logged_in()
        || ( function_exists( 'is_cart' ) && is_cart() )
        || ( function_exists( 'is_checkout' ) && is_checkout() )
        || ( function_exists( 'is_account_page' ) && is_account_page() );

    if ( ! $is_dynamic ) {
        return;
    }

    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }

    nocache_headers();

    if ( ! headers_sent() ) {
        header( 'X-LiteSpeed-Cache-Control: no-cache' );
    }
}
add_action( 'template_redirect', 'wm_cache_exclude_dynamic_pages' );
