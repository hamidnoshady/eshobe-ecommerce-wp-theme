<?php
/**
 * AJAX quick-view: render the product gallery/intro/purchase into the modal.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

function wm_ajax_quick_view() {
	check_ajax_referer( 'wm_quick_view_nonce', 'nonce' );

	$product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$product_obj = $product_id ? wc_get_product( $product_id ) : null;

	if ( ! $product_obj instanceof WC_Product || ! $product_obj->is_visible() ) {
		wp_send_json_error( array( 'message' => 'محصول یافت نشد.' ) );
	}

	// The render helpers resolve the product via the global, like the
	// single-product template does.
	global $product, $post;
	$product = $product_obj;
	$post    = get_post( $product->get_id() );
	setup_postdata( $post );

	$summary = wm_render_product_intro();

	if ( $product->is_type( 'variable' ) ) {
		// Keep quick view dependency-free: variable pickers need the
		// product-variations JS stack, so link out instead.
		$summary .= '<a class="button wm-quick-view__view-product" href="' . esc_url( get_permalink( $product->get_id() ) ) . '">'
			. esc_html__( 'انتخاب رنگ و سایز', 'eshobe-ecommerce' )
			. '</a>';
	} else {
		$summary .= wm_render_product_purchase();
	}

	$html = '<div class="wm-quick-view__grid">'
		. '<div class="wm-quick-view__gallery">' . wm_render_product_gallery() . '</div>'
		. '<div class="wm-quick-view__summary">' . $summary . '</div>'
		. '</div>';

	wp_reset_postdata();

	wp_send_json_success(
		array(
			'html'  => $html,
			'title' => $product->get_name(),
		)
	);
}
add_action( 'wp_ajax_wm_quick_view', 'wm_ajax_quick_view' );
add_action( 'wp_ajax_nopriv_wm_quick_view', 'wm_ajax_quick_view' );
