<?php
/**
 * Discounted products archive.
 *
 * The theme already has a full product archive (filters, sorting, pagination,
 * cards, AJAX filtering). Instead of duplicating it, this module exposes the
 * product post type archive at /discounts/ and narrows its main query to the
 * products that currently have a discount, so the exact same layout and
 * behaviour is reused.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Archive slug (filterable, one segment).
 *
 * @return string
 */
function wm_discount_archive_slug() {
	$slug = apply_filters( 'wm_discount_archive_slug', 'discounts' );

	return sanitize_title( $slug ? $slug : 'discounts' );
}

/**
 * Archive page title.
 *
 * @return string
 */
function wm_discount_archive_title() {
	return (string) apply_filters( 'wm_discount_archive_title', __( 'تخفیف‌دارها', 'eshobe-ecommerce' ) );
}

/**
 * Archive URL.
 *
 * @return string
 */
function wm_discount_archive_url() {
	return home_url( user_trailingslashit( wm_discount_archive_slug() ) );
}

/**
 * Whether the current request is the discounted products archive.
 *
 * @return bool
 */
function wm_discount_archive_is_context() {
	return ! is_admin() && (bool) get_query_var( 'wm_discount_archive' );
}

/**
 * Registers the archive rewrite rules.
 */
function wm_discount_archive_register_rewrites() {
	$slug = preg_quote( wm_discount_archive_slug(), '#' );

	add_rewrite_rule( '^' . $slug . '/?$', 'index.php?post_type=product&wm_discount_archive=1', 'top' );
	add_rewrite_rule( '^' . $slug . '/page/([0-9]{1,})/?$', 'index.php?post_type=product&wm_discount_archive=1&paged=$matches[1]', 'top' );
}
add_action( 'init', 'wm_discount_archive_register_rewrites', 5 );

/**
 * Registers the archive query var.
 *
 * @param string[] $vars Public query vars.
 * @return string[]
 */
function wm_discount_archive_query_vars( $vars ) {
	$vars[] = 'wm_discount_archive';

	return $vars;
}
add_filter( 'query_vars', 'wm_discount_archive_query_vars' );

/**
 * Flushes rewrite rules once per theme version / slug.
 *
 * Theme updates do not fire activation hooks, so the rules would otherwise be
 * missing until the next permalink settings save.
 */
function wm_discount_archive_maybe_flush_rewrites() {
	$flag = 'wm_discount_archive_rules_' . md5( wm_discount_archive_slug() . '|' . ESHOBE_ECOMMERCE_VERSION );

	if ( get_option( $flag ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( $flag, 1, false );
}
add_action( 'wp_loaded', 'wm_discount_archive_maybe_flush_rewrites' );

/**
 * Narrows the shop loop to discounted products.
 *
 * WooCommerce resets `post__in` on the main product query with whatever the
 * `loop_shop_post_in` filter returns, so a plain pre_get_posts constraint is
 * discarded. Filtering the list WooCommerce itself uses keeps ordering, price
 * filtering and pagination intact.
 *
 * @param int[] $ids Product IDs to limit the loop to.
 * @return int[]
 */
function wm_discount_archive_loop_shop_post_in( $ids ) {
	if ( ! wm_discount_archive_is_context() ) {
		return $ids;
	}

	$product_ids = wm_get_discounted_product_ids();

	// array( 0 ) deliberately matches nothing when no product is discounted.
	return ! empty( $product_ids ) ? $product_ids : array( 0 );
}
add_filter( 'loop_shop_post_in', 'wm_discount_archive_loop_shop_post_in' );

/**
 * Keep WordPress from canonicalising the archive URL to the shop page.
 *
 * @param string|false $redirect_url Redirect URL.
 * @return string|false
 */
function wm_discount_archive_disable_canonical( $redirect_url ) {
	return wm_discount_archive_is_context() ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'wm_discount_archive_disable_canonical' );

/**
 * Archive heading (H1) inside the shop archive layout.
 *
 * @param string $title Page title.
 * @return string
 */
function wm_discount_archive_page_title( $title ) {
	return wm_discount_archive_is_context() ? wm_discount_archive_title() : $title;
}
add_filter( 'woocommerce_page_title', 'wm_discount_archive_page_title' );

/**
 * Browser tab / document title.
 *
 * @param array $parts Title parts.
 * @return array
 */
function wm_discount_archive_document_title( $parts ) {
	if ( wm_discount_archive_is_context() ) {
		$parts['title'] = wm_discount_archive_title();
	}

	return $parts;
}
add_filter( 'document_title_parts', 'wm_discount_archive_document_title', 20 );
