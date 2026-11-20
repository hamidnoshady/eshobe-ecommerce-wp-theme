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
