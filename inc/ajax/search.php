<?php
/**
 * AJAX live product search for the header search modal.
 *
 * @package WM_Theme
 */

/**
 * Per-IP request bucket for the public search endpoint.
 *
 * Search tolerates stale/missing nonces (cached pages), so it is effectively
 * unauthenticated — this bucket keeps it from being hammered into expensive
 * `orderby => relevance` LIKE queries. Configurable via
 * `wm_search_max_per_ip` (Marketing settings → search tab).
 *
 * @return bool True when the caller should be throttled.
 */
function wm_search_is_throttled() {
	$max = max( 5, absint( wm_search_get_option( 'wm_search_max_per_ip', 30 ) ) );
	$ip  = wm_get_client_ip();

	if ( ! $ip ) {
		return false;
	}

	$key   = 'wm_search_ip_' . md5( $ip );
	$count = (int) get_transient( $key );

	if ( $count >= $max ) {
		return true;
	}

	set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

	return false;
}

function wm_ajax_search_products() {
	// Don't die on a stale nonce: this header markup can be served from a full-page
	// cache (e.g. FlyingPress) long after the embedded nonce has expired, but search
	// is a public, read-only lookup so a stale/missing nonce shouldn't block it.
	check_ajax_referer( 'wm_search_nonce', 'nonce', false );

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

	// Rate limit first (cheap), then validate the term (min 2, max 60 chars).
	if ( wm_search_is_throttled() ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	if ( '' === $term || mb_strlen( $term ) < 2 || mb_strlen( $term ) > 60 || ! function_exists( 'wc_get_products' ) ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	$max_results = absint( wm_search_get_option( 'wm_search_max_results', 6 ) );
	$max_results = $max_results > 0 ? $max_results : 6;

	// Short-lived per-term cache: the same term is queried on every keystroke,
	// and full-page caching can serve the same term to many visitors at once.
	$cache_key = 'wm_search_results_' . md5( $term . '|' . $max_results );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		wp_send_json_success( array( 'results' => $cached ) );
	}

	$products = wc_get_products(
		array(
			's'       => $term,
			'status'  => 'publish',
			'limit'   => $max_results,
			'orderby' => 'relevance',
		)
	);

	$results = array();
	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			continue;
		}

		$image_id = $product->get_image_id();
		$terms    = wm_get_product_brand_terms( $product->get_id() );
		$brand    = '';
		if ( ! empty( $terms ) ) {
			$brand = $terms[0]->name;
		} else {
			$cat_terms = wm_get_product_category_terms( $product->get_id() );
			if ( ! empty( $cat_terms ) ) {
				$brand = $cat_terms[0]->name;
			}
		}

		$results[] = array(
			'id'        => $product->get_id(),
			'title'     => $product->get_name(),
			'permalink' => get_permalink( $product->get_id() ),
			'image'     => $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' ),
			'price'     => $product->get_price_html(),
			'brand'     => $brand,
		);
	}

	set_transient( $cache_key, $results, MINUTE_IN_SECONDS );

	wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_wm_search_products', 'wm_ajax_search_products' );
add_action( 'wp_ajax_nopriv_wm_search_products', 'wm_ajax_search_products' );
