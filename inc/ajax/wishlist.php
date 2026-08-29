<?php
/**
 * AJAX wishlist: render product cards for the saved (localStorage) IDs.
 *
 * Wishlist state lives client-side (localStorage, see product-wishlist.js);
 * this endpoint turns the stored IDs into rendered cards for the wishlist
 * page. Read-only and nonce-protected.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

function wm_ajax_wishlist_products() {
	check_ajax_referer( 'wm_wishlist_nonce', 'nonce' );

	$raw_ids = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array();
	// Limit to 100 items to prevent Array DoS attacks (memory and database connection exhaustion)
	$raw_ids = array_slice( $raw_ids, 0, 100 );
	$ids     = array();

	foreach ( $raw_ids as $id ) {
		$id = absint( $id );
		if ( $id ) {
			$ids[] = $id;
		}
	}
	$ids = array_values( array_unique( $ids ) );

	if ( empty( $ids ) || ! function_exists( 'wc_get_product' ) ) {
		wp_send_json_success( array( 'cards' => array() ) );
	}

	$cards = array();
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product instanceof WC_Product && $product->is_visible() ) {
			$cards[] = array(
				'id'   => $product->get_id(),
				'html' => wm_render_product_card( $product, array( 'quick_view' => true ) ),
			);
		}
	}

	wp_send_json_success( array( 'cards' => $cards ) );
}
add_action( 'wp_ajax_wm_wishlist_products', 'wm_ajax_wishlist_products' );
add_action( 'wp_ajax_nopriv_wm_wishlist_products', 'wm_ajax_wishlist_products' );
