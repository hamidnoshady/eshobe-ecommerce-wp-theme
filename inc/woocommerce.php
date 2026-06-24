<?php
/**
 * WooCommerce compatibility.
 *
 * @package WM_Theme
 */

if ( ! class_exists( 'WooCommerce' ) ) {
    return;
}

/**
 * The theme ships dedicated stylesheets for every WooCommerce surface
 * (product-archive.css, product-components.css, cart.css, checkout.css,
 * myaccount.css, mini-cart.css), so WooCommerce's own default
 * generic/layout/smallscreen stylesheets are redundant page weight.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );

add_action( 'woocommerce_before_main_content', function() {
    echo '<main id="primary" class="site-main">';
}, 10 );

add_action( 'woocommerce_after_main_content', function() {
    echo '</main>';
}, 10 );

/**
 * The single-product template already renders the product name as an <h1>
 * (wm_render_product_intro()), so drop the duplicate, unlinked product-title
 * crumb that WC_Breadcrumb::add_crumbs_single() appends to the trail.
 *
 * @param array $crumbs Breadcrumb trail.
 * @return array
 */
add_filter( 'woocommerce_get_breadcrumb', function( $crumbs ) {
    if ( is_product() && count( $crumbs ) > 1 ) {
        array_pop( $crumbs );
    }

    return $crumbs;
} );

/**
 * Force the Cart page back to the classic shortcode when it was built with the
 * WooCommerce Cart Block. Block Cart bypasses PHP template overrides.
 *
 * @param string $content Page content.
 * @return string
 */
function wm_use_classic_cart_template_for_cart_block( $content ) {
    if ( is_admin() || ! function_exists( 'is_cart' ) || ! is_cart() || ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }

    if ( has_block( 'woocommerce/cart', $content ) || false !== strpos( $content, 'wp:woocommerce/cart' ) ) {
        return do_shortcode( '[woocommerce_cart]' );
    }

    return $content;
}
add_filter( 'the_content', 'wm_use_classic_cart_template_for_cart_block', 8 );

/**
 * Force the Checkout page back to the classic shortcode when it was built with
 * the WooCommerce Checkout Block. Block Checkout bypasses PHP template
 * overrides, so the stepped checkout template cannot render otherwise.
 *
 * @param string $content Page content.
 * @return string
 */
function wm_use_classic_checkout_template_for_checkout_block( $content ) {
    if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }

    if ( has_block( 'woocommerce/checkout', $content ) || false !== strpos( $content, 'wp:woocommerce/checkout' ) ) {
        return do_shortcode( '[woocommerce_checkout]' );
    }

    return $content;
}
add_filter( 'the_content', 'wm_use_classic_checkout_template_for_checkout_block', 8 );

/**
 * Keep cart shipping package labels Persian-first.
 *
 * WooCommerce can output package names such as "Shipment" inside the shipping
 * fragment even when the cart totals label is customized.
 *
 * @param string $name Package name.
 * @return string
 */
function wm_persian_cart_shipping_package_name( $name ) {
    if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
        return esc_html__( 'ارسال', 'eshobe-ecommerce' );
    }

    return $name;
}
add_filter( 'woocommerce_shipping_package_name', 'wm_persian_cart_shipping_package_name', 20 );

/**
 * The theme renders its own variation swatch UI on single-product pages
 * (see woocommerce/single-product/add-to-cart/variable.php), reading YITH's
 * term meta directly. Dequeue YITH's own frontend swatch script/style there
 * to avoid it hijacking the native <select> a second time. Left fully
 * active everywhere else (shop loop, admin term-meta screens).
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( is_product() ) {
		wp_dequeue_script( 'yith_wccl_frontend' );
		wp_dequeue_style( 'yith_wccl_frontend' );
	}
}, 20 );
