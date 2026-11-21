<?php
/**
 * Discount display helpers.
 *
 * The theme shows a single, consistent discount language across the store:
 * the regular price is struck through, the payable price follows it and a
 * percentage badge explains the saving. The same helpers read the effective
 * price, so they work for WooCommerce sale prices and for prices adjusted by
 * Discount Rules for WooCommerce (Flycart / "awdr"), which filters the product
 * price without always setting a native sale price.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the Flycart Discount Rules plugin is active.
 *
 * Only used to load its label/table styles; the discount helpers themselves
 * work from the effective product price and do not require the plugin.
 *
 * @return bool
 */
function wm_is_discount_rules_active() {
	return defined( 'WDR_VERSION' )
		|| defined( 'ADVANCED_WOO_DISCOUNT_RULES_VERSION' )
		|| class_exists( '\Wdr\App\Controllers\ManageDiscount' )
		|| class_exists( 'Woo_Discount_Rules' );
}

/**
 * Regular / current price pair for a product.
 *
 * Variable products use their lowest variation so the badge and percentage
 * still describe the "starting from" price shown in the price range.
 *
 * @param WC_Product $product Product.
 * @return array{regular: float, current: float, percent: int, saved: float}
 */
function wm_get_product_price_parts( $product ) {
	$parts = array(
		'regular' => 0.0,
		'current' => 0.0,
		'percent' => 0,
		'saved'   => 0.0,
	);

	if ( ! $product instanceof WC_Product ) {
		return $parts;
	}

	if ( $product->is_type( 'variable' ) ) {
		$regular = (float) $product->get_variation_regular_price( 'min' );
		$current = (float) $product->get_variation_price( 'min' );
	} else {
		$regular = (float) $product->get_regular_price();
		$current = (float) $product->get_price();
	}

	if ( $regular <= 0 || $current <= 0 || $current >= $regular ) {
		return $parts;
	}

	$regular = (float) wc_get_price_to_display( $product, array( 'price' => $regular ) );
	$current = (float) wc_get_price_to_display( $product );

	if ( $regular <= 0 || $current >= $regular ) {
		return $parts;
	}

	$parts['regular'] = $regular;
	$parts['current'] = $current;
	$parts['saved']   = $regular - $current;
	$parts['percent'] = max( 1, min( 99, (int) round( ( $parts['saved'] / $regular ) * 100 ) ) );

	return $parts;
}

/**
 * Discount percentage for a product (0 when there is no discount).
 *
 * @param WC_Product $product Product.
 * @return int
 */
function wm_get_product_discount_percentage( $product ) {
	$parts = wm_get_product_price_parts( $product );

	return (int) $parts['percent'];
}

/**
 * Whether a product currently has a visible discount.
 *
 * Covers native sale prices and rule-based price adjustments.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function wm_product_has_discount( $product ) {
	return $product instanceof WC_Product && wm_get_product_discount_percentage( $product ) > 0;
}

/**
 * Percentage badge markup.
 *
 * @param int   $percent Discount percentage.
 * @param array $args {
 *     @type string $label 'short' (۱۵٪) or 'full' (۱۵٪ تخفیف).
 *     @type string $class Extra CSS class.
 * }
 * @return string
 */
function wm_get_discount_badge_html( $percent, $args = array() ) {
	$percent = (int) $percent;

	if ( $percent < 1 ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'label' => 'short',
			'class' => '',
		)
	);

	if ( 'full' === $args['label'] ) {
		/* translators: %s: discount percentage. */
		$text = sprintf( __( '%s٪ تخفیف', 'eshobe-ecommerce' ), number_format_i18n( $percent ) );
	} else {
		/* translators: %s: discount percentage. */
		$text = sprintf( __( '%s٪', 'eshobe-ecommerce' ), number_format_i18n( $percent ) );
	}

	return sprintf(
		'<span class="wm-discount-badge%1$s">%2$s</span>',
		$args['class'] ? ' ' . esc_attr( $args['class'] ) : '',
		esc_html( $text )
	);
}

/**
 * Derive the regular / current price pair from rendered price HTML.
 *
 * Discount plugins (Discount Rules for WooCommerce among them) can replace
 * get_price_html() without changing get_regular_price() / get_price(), so the
 * numbers above report "no discount" while the markup already shows a struck
 * through regular price. Reading the two amounts back keeps the percentage
 * badge in step with what the customer actually sees.
 *
 * @param string $html Price HTML.
 * @return array{regular: float, current: float, percent: int, saved: float}|null
 */
function wm_get_price_parts_from_html( $html ) {
	if ( false === strpos( $html, '<del' ) ) {
		return null;
	}

	if ( ! preg_match( '#<del[^>]*>(.*?)</del>#s', $html, $regular_match ) ) {
		return null;
	}

	if ( ! preg_match( '#<ins[^>]*>(.*?)</ins>#s', $html, $current_match ) ) {
		return null;
	}

	$regular = (float) preg_replace( '/\D+/', '', wp_strip_all_tags( $regular_match[1] ) );
	$current = (float) preg_replace( '/\D+/', '', wp_strip_all_tags( $current_match[1] ) );

	if ( $regular <= 0 || $current <= 0 || $current >= $regular ) {
		return null;
	}

	return array(
		'regular' => $regular,
		'current' => $current,
		'saved'   => $regular - $current,
		'percent' => max( 1, min( 99, (int) round( ( ( $regular - $current ) / $regular ) * 100 ) ) ),
	);
}

/**
 * Regular / current price pair resolved for display.
 *
 * Prefers the product's own price fields and falls back to the rendered price
 * markup when a discount plugin only adjusts the price HTML.
 *
 * @param WC_Product $product Product.
 * @return array{regular: float, current: float, percent: int, saved: float}
 */
function wm_get_resolved_price_parts( $product ) {
	$parts = wm_get_product_price_parts( $product );

	if ( ! $product instanceof WC_Product || $parts['percent'] >= 1 ) {
		return $parts;
	}

	$parts_from_html = wm_get_price_parts_from_html( $product->get_price_html() );

	return null === $parts_from_html ? $parts : $parts_from_html;
}

/**
 * Price HTML with the regular price struck through and a percentage badge.
 *
 * WooCommerce (and Discount Rules) already output a struck-out price when a
 * native sale price exists; in that case the original markup is preserved and
 * the badge is appended. When only the effective price is lower (rule based
 * discount), the regular/current pair is rendered explicitly.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function wm_get_product_price_html( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$html = $product->get_price_html();

	if ( '' === $html ) {
		return '';
	}

	$parts = wm_get_resolved_price_parts( $product );

	if ( $parts['percent'] < 1 ) {
		return $html;
	}

	// Some plugins print their own "-20%" inside the price; do not repeat it.
	$has_percent_text = false !== strpos( $html, '٪' ) || false !== strpos( $html, '%' );
	$badge            = $has_percent_text ? '' : wm_get_discount_badge_html( $parts['percent'] );

	if ( false !== strpos( $html, '<del' ) || $product->is_type( 'variable' ) ) {
		return '<span class="wm-price wm-price--discounted">' . $html . $badge . '</span>';
	}

	return '<span class="wm-price wm-price--discounted">'
		. '<del class="wm-price__regular">' . wc_price( $parts['regular'] ) . '</del>'
		. '<ins class="wm-price__current">' . wc_price( $parts['current'] ) . '</ins>'
		. $badge
		. '</span>';
}

/**
 * Price block for the single product purchase box and the mobile buy bar.
 *
 * The purchase box has room for the full story, so instead of reusing the
 * compact card markup it prints the payable price, a "20٪ تخفیف" badge, the
 * labelled regular price and how much the customer saves.
 *
 * The mobile buy bar shows the same breakdown without the saving line, which
 * does not fit the bar's height.
 *
 * Variable products keep WooCommerce's range markup in the purchase box; the
 * bar can opt into the starting (lowest variation) price instead, because a
 * price range does not fit beside its stock pill.
 *
 * @param WC_Product $product Product.
 * @param array      $args {
 *     @type bool   $show_saved       Whether to append the "… سود شما" line.
 *     @type string $class            Extra CSS class on the wrapper.
 *     @type bool   $starting_price   Use the lowest variation price for
 *                                    variable products instead of their
 *                                    WooCommerce range markup.
 * }
 * @return string
 */
function wm_get_single_product_price_html( $product, $args = array() ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'show_saved'     => true,
			'class'          => '',
			'starting_price' => false,
		)
	);

	if ( ! $product->is_type( 'simple' ) && ! ( $args['starting_price'] && $product->is_type( 'variable' ) ) ) {
		return wm_get_product_price_html( $product );
	}

	$parts = wm_get_resolved_price_parts( $product );

	if ( $parts['percent'] < 1 ) {
		return $product->get_price_html();
	}

	$wrapper_class = 'wm-price-hero wm-price-hero--discounted';
	if ( $args['class'] ) {
		$wrapper_class .= ' ' . $args['class'];
	}

	$html = '<div class="' . esc_attr( $wrapper_class ) . '">'
		. '<div class="wm-price-hero__row">'
		. ( $product->is_type( 'variable' )
			? '<span class="wm-price-hero__from">' . esc_html__( 'از', 'eshobe-ecommerce' ) . '</span>'
			: '' )
		. '<span class="wm-price-hero__current">' . wc_price( $parts['current'] ) . '</span>'
		. wm_get_discount_badge_html( $parts['percent'], array( 'label' => 'full', 'class' => 'wm-price-hero__badge' ) )
		. '</div>'
		. '<div class="wm-price-hero__meta">'
		. '<span class="wm-price-hero__regular-label">' . esc_html__( 'قیمت قبلی', 'eshobe-ecommerce' ) . '</span>'
		. '<del class="wm-price-hero__regular">' . wc_price( $parts['regular'] ) . '</del>';

	if ( $args['show_saved'] ) {
		$html .= '<span class="wm-price-hero__saved">'
			/* translators: %s: amount saved on the product. */
			. sprintf( esc_html__( '%s سود شما', 'eshobe-ecommerce' ), wc_price( $parts['saved'] ) )
			. '</span>';
	}

	return $html . '</div></div>';
}

/**
 * Regular / current price pair for a cart item.
 *
 * @param array $cart_item Cart item.
 * @return array{regular: float, current: float, percent: int, saved: float}|null
 */
function wm_get_cart_item_price_parts( $cart_item ) {
	$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;

	if ( ! $product instanceof WC_Product ) {
		return null;
	}

	$regular = (float) $product->get_regular_price();
	$current = (float) $product->get_price();

	if ( $regular <= 0 || $current <= 0 || $current >= $regular ) {
		return null;
	}

	$regular = (float) wc_get_price_to_display( $product, array( 'price' => $regular ) );
	$current = (float) wc_get_price_to_display( $product );

	if ( $regular <= 0 || $current >= $regular ) {
		return null;
	}

	return array(
		'regular' => $regular,
		'current' => $current,
		'percent' => max( 1, min( 99, (int) round( ( ( $regular - $current ) / $regular ) * 100 ) ) ),
		'saved'   => $regular - $current,
	);
}

/**
 * Discount line for a cart item: regular price, percentage and saving.
 *
 * @param array $cart_item Cart item.
 * @param array $args {
 *     @type string $context 'cart', 'drawer' or 'checkout'.
 * }
 * @return string
 */
function wm_get_cart_item_discount_html( $cart_item, $args = array() ) {
	$parts = wm_get_cart_item_price_parts( $cart_item );

	if ( empty( $parts ) ) {
		return '';
	}

	$args = wp_parse_args( $args, array( 'context' => 'cart' ) );
	$args['context'] = sanitize_html_class( $args['context'] );

	return '<div class="wm-cart-discount wm-cart-discount--' . esc_attr( $args['context'] ) . '">'
		. wm_get_discount_badge_html( $parts['percent'], array( 'label' => 'full' ) )
		. '<del class="wm-cart-discount__regular">' . wc_price( $parts['regular'] ) . '</del>'
		. '<span class="wm-cart-discount__saved">'
		/* translators: %s: saved amount per item. */
		. sprintf( esc_html__( '%s سود', 'eshobe-ecommerce' ), wc_price( $parts['saved'] ) )
		. '</span>'
		. '</div>';
}

/**
 * Cart-level discounts that never reach a line price.
 *
 * Discount Rules for WooCommerce applies its cart rules as negative fees and
 * coupons come off the cart total, so neither appears as a product price
 * change. Both are surfaced here so the store can show what was saved.
 *
 * @return array<int, array{label: string, amount: float}>
 */
function wm_get_cart_adjustment_rows() {
	$rows = array();

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $rows;
	}

	foreach ( (array) WC()->cart->get_fees() as $fee ) {
		$amount = isset( $fee->amount ) ? (float) $fee->amount : 0.0;

		if ( $amount >= 0 ) {
			continue;
		}

		$label = isset( $fee->name ) ? trim( wp_strip_all_tags( (string) $fee->name ) ) : '';

		$rows[] = array(
			'label'  => '' !== $label ? $label : __( 'تخفیف', 'eshobe-ecommerce' ),
			'amount' => abs( $amount ),
		);
	}

	$coupons = (float) WC()->cart->get_discount_total();

	if ( $coupons > 0 ) {
		$rows[] = array(
			'label'  => __( 'کد تخفیف', 'eshobe-ecommerce' ),
			'amount' => $coupons,
		);
	}

	return $rows;
}

/**
 * Total product-level saving for the current cart.
 *
 * Cart-level fees and coupons are deliberately left out: the cart page already
 * lists them as their own rows, so adding them here would show the same money
 * twice.
 *
 * @return float
 */
function wm_get_cart_savings_total() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}

	$total = 0.0;

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$parts = wm_get_cart_item_price_parts( $cart_item );

		if ( empty( $parts ) ) {
			continue;
		}

		$total += $parts['saved'] * max( 1, (int) $cart_item['quantity'] );
	}

	return $total;
}

/**
 * Append the discount line to checkout line items.
 *
 * The checkout review table calls this filter for each product name; the
 * theme's own cart template renders the same line directly.
 *
 * @param string $name          Product name HTML.
 * @param array  $cart_item     Cart item.
 * @param string $cart_item_key Cart item key.
 * @return string
 */
function wm_checkout_cart_item_discount( $name, $cart_item, $cart_item_key ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return $name;
	}

	return $name . wm_get_cart_item_discount_html( $cart_item, array( 'context' => 'checkout' ) );
}
add_filter( 'woocommerce_cart_item_name', 'wm_checkout_cart_item_discount', 20, 3 );

/**
 * Option holding the discounted product sweep.
 *
 * Shape: ids (products of the last complete pass), pending (products found by
 * the pass in progress), offset (next catalog row), sweeping (a full pass is in
 * progress), time (when the last complete pass finished) and updated (last
 * slice). Kept in an option rather than a transient so the last complete list
 * can still be served while the next sweep runs in the background.
 *
 * @return string
 */
function wm_discounted_products_option_key() {
	return 'wm_discounted_products_list';
}

/**
 * How long a completed sweep stays fresh.
 *
 * @return int
 */
function wm_discounted_products_ttl() {
	return max( 60, (int) apply_filters( 'wm_discounted_products_ttl', 20 * MINUTE_IN_SECONDS ) );
}

/**
 * Normalised sweep state.
 *
 * @return array{ids: int[], pending: int[], offset: int, sweeping: bool, time: int, updated: int}
 */
function wm_discounted_products_state() {
	$state = get_option( wm_discounted_products_option_key(), array() );

	if ( ! is_array( $state ) ) {
		$state = array();
	}

	$normalise = function( $key ) use ( $state ) {
		return isset( $state[ $key ] ) && is_array( $state[ $key ] ) ? array_values( array_map( 'intval', $state[ $key ] ) ) : array();
	};

	return array(
		'ids'      => $normalise( 'ids' ),
		'pending'  => $normalise( 'pending' ),
		'offset'   => isset( $state['offset'] ) ? max( 0, (int) $state['offset'] ) : 0,
		'sweeping' => ! empty( $state['sweeping'] ),
		'time'     => isset( $state['time'] ) ? (int) $state['time'] : 0,
		'updated'  => isset( $state['updated'] ) ? (int) $state['updated'] : 0,
	);
}

/**
 * ID list that can be served right now.
 *
 * The last complete pass wins, so a running refresh never shrinks the archive
 * or the carousel. Only the very first pass (no completed list yet) is served
 * while it is still filling, so a fresh install is not left with nothing.
 *
 * @param array $state Sweep state.
 * @return int[]
 */
function wm_discounted_products_served_ids( $state ) {
	return $state['time'] > 0 ? $state['ids'] : $state['pending'];
}

/**
 * Prevents two sweep slices from writing the same pass.
 *
 * Cron and the inline nudge can overlap on a busy store; running them at once
 * would interleave their cursors and corrupt the pass.
 *
 * @return bool Whether this request may sweep.
 */
function wm_discounted_products_acquire_lock() {
	$key = 'wm_discounted_products_sweep_lock';

	if ( get_transient( $key ) ) {
		return false;
	}

	set_transient( $key, time(), 2 * MINUTE_IN_SECONDS );

	return true;
}

/**
 * Releases the sweep lock.
 */
function wm_discounted_products_release_lock() {
	delete_transient( 'wm_discounted_products_sweep_lock' );
}

/**
 * Queues the next sweep slice.
 */
function wm_schedule_discount_sweep() {
	if ( ! wp_next_scheduled( 'wm_refresh_discounted_products' ) ) {
		wp_schedule_single_event( time() + 1, 'wm_refresh_discounted_products' );
	}
}

/**
 * Marks the stored list as stale and asks for a background sweep.
 *
 * The timestamp is moved just past the freshness window, so the next request
 * keeps serving the known list instead of rebuilding it inline.
 */
function wm_discounted_products_cache_flush() {
	$state = wm_discounted_products_state();

	if ( $state['time'] > 0 ) {
		$state['time'] = time() - wm_discounted_products_ttl();
		update_option( wm_discounted_products_option_key(), $state, false );
	}

	wm_schedule_discount_sweep();
}

add_action( 'woocommerce_update_product', 'wm_discounted_products_cache_flush' );
add_action( 'woocommerce_new_product', 'wm_discounted_products_cache_flush' );
add_action( 'save_post_product', 'wm_discounted_products_cache_flush' );
add_action( 'trashed_post', 'wm_discounted_products_cache_flush' );
add_action( 'untrashed_post', 'wm_discounted_products_cache_flush' );

/**
 * Discount Rules stores its rules as posts; a changed rule changes prices
 * without touching a product, so refresh the list when one is saved.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function wm_discounted_products_flush_on_rule_save( $post_id, $post ) {
	if ( $post instanceof WP_Post && 0 === strpos( (string) $post->post_type, 'wdr' ) ) {
		wm_discounted_products_cache_flush();
	}
}
add_action( 'save_post', 'wm_discounted_products_flush_on_rule_save', 10, 2 );

/**
 * Runs one time-boxed slice of the catalog sweep.
 *
 * Rendering every product's price markup takes long enough on a large catalog
 * that a single pass could hit the PHP time limit, so the sweep stores its
 * cursor after every batch and continues from there on the next run.
 *
 * A pass collects into `pending` and only replaces the served `ids` once it
 * reaches the end of the catalog, so the archive and the carousel keep the last
 * complete list (instead of a half-filled one) for the whole refresh.
 *
 * @param int $budget Seconds this slice may spend.
 */
function wm_run_discount_sweep( $budget = 20 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return;
	}

	$budget = max( 1, (int) $budget );

	if ( ! wm_discounted_products_acquire_lock() ) {
		wm_schedule_discount_sweep();
		return;
	}

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( $budget + 20 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$state = wm_discounted_products_state();

	if ( ! $state['sweeping'] ) {
		// Start a fresh pass next to the still-served complete list.
		$state['pending']  = array();
		$state['offset']   = 0;
		$state['sweeping'] = true;
		$state['updated']  = time();
		update_option( wm_discounted_products_option_key(), $state, false );
	}

	$deadline = microtime( true ) + $budget;
	$done     = false;

	do {
		$batch = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 100,
				'offset'  => $state['offset'],
				'return'  => 'objects',
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$batch           = (array) $batch;
		$state['offset'] += count( $batch );

		foreach ( $batch as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			// Resolved parts also read the rendered price markup, which is the
			// only place Discount Rules reports some of its adjustments.
			$parts = wm_get_resolved_price_parts( $product );

			if ( $parts['percent'] > 0 ) {
				$state['pending'][] = (int) $product->get_id();
			}
		}

		$state['pending'] = array_values( array_unique( $state['pending'] ) );
		$state['updated'] = time();

		if ( count( $batch ) < 100 ) {
			$done = true;
			break;
		}

		update_option( wm_discounted_products_option_key(), $state, false );
	} while ( microtime( true ) < $deadline );

	if ( $done ) {
		// Publish the finished pass in one write; until here the previous
		// complete list stayed untouched.
		$state['ids']      = $state['pending'];
		$state['pending']  = array();
		$state['time']     = time();
		$state['offset']   = 0;
		$state['sweeping'] = false;
	}

	update_option( wm_discounted_products_option_key(), $state, false );
	wm_discounted_products_release_lock();

	if ( ! $done ) {
		wm_schedule_discount_sweep();
	}
}

/**
 * WP-Cron callback: continue the sweep.
 */
function wm_refresh_discounted_products_cron() {
	wm_run_discount_sweep( 20 );
}
add_action( 'wm_refresh_discounted_products', 'wm_refresh_discounted_products_cron' );

/**
 * IDs of published products whose payable price is below their regular price.
 *
 * Covers native WooCommerce sale prices and rule-based price adjustments. The
 * last complete pass is returned immediately, even while a refresh is running,
 * so the archive and the carousel never shrink to a partial (or empty) list;
 * a full catalog scan always happens in the background.
 *
 * @return int[]
 */
function wm_get_discounted_product_ids() {
	static $memory_cache = null;

	if ( is_array( $memory_cache ) ) {
		return $memory_cache;
	}

	$state = wm_discounted_products_state();
	$ttl   = wm_discounted_products_ttl();
	$age   = time() - $state['time'];

	if ( $state['time'] > 0 && $age < $ttl ) {
		$memory_cache = $state['ids'];

		return $memory_cache;
	}

	if ( wp_doing_cron() ) {
		$memory_cache = wm_discounted_products_served_ids( $state );

		return $memory_cache;
	}

	// No complete pass yet: run a short slice now so the archive is not empty
	// while the rest of the catalog is being swept.
	if ( 0 === $state['time'] && ! $state['sweeping'] && empty( $state['pending'] ) ) {
		wm_run_discount_sweep( 8 );
		$state = wm_discounted_products_state();
	}

	// A sweep that stopped progressing (cron unavailable) gets nudged forward.
	// It keeps filling `pending`, so the served list is unaffected.
	if ( $state['sweeping'] && $state['updated'] > 0 && ( time() - $state['updated'] ) > 5 * MINUTE_IN_SECONDS ) {
		wm_run_discount_sweep( 8 );
		$state = wm_discounted_products_state();
	}

	$age = time() - $state['time'];

	if ( $state['sweeping'] || 0 === $state['time'] || $age >= $ttl ) {
		wm_schedule_discount_sweep();
	}

	$memory_cache = wm_discounted_products_served_ids( $state );

	return $memory_cache;
}

/**
 * Discounted products for carousels.
 *
 * The sweep stores its ids newest first, so slicing that list would always
 * show the same newest products. The carousel shuffles a copy instead, so
 * repeated visits surface different products from the whole discounted
 * catalog. The archive keeps the ordered list for stable pagination.
 *
 * @param int $count Max number of products.
 * @return WC_Product[]
 */
function wm_get_discounted_products( $count = 10 ) {
	$count = max( 1, absint( $count ) );
	$ids   = wm_get_discounted_product_ids();

	if ( count( $ids ) > 1 ) {
		shuffle( $ids );
	}

	$ids = array_slice( $ids, 0, $count );

	if ( empty( $ids ) || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$products = array();

	foreach ( $ids as $product_id ) {
		$product = wc_get_product( $product_id );

		if ( $product instanceof WC_Product ) {
			$products[] = $product;
		}
	}

	return $products;
}
