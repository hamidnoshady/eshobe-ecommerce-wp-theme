<?php
/**
 * Marketing & Sale option accessors (search, future campaigns).
 *
 * @package WM_Theme
 */

function wm_search_get_option( $key, $default = '' ) {
	return wm_get_option( $key, $default );
}

/**
 * Sale badge configuration (Marketing > Sales & Campaigns).
 *
 * @return array{enabled: bool, text: string, bg: string, color: string}
 */
function wm_marketing_get_sale_badge_config() {
	return array(
		'enabled' => (bool) wm_search_get_option( 'wm_marketing_sale_badge_enabled', true ),
		'text'    => wm_search_get_option( 'wm_marketing_sale_badge_text', 'تخفیف' ),
		'bg'      => wm_search_get_option( 'wm_marketing_sale_badge_bg', '#C0392B' ),
		'color'   => wm_search_get_option( 'wm_marketing_sale_badge_color', '#FFFFFF' ),
	);
}

/**
 * Render the sale badge markup for a product, or an empty string if not applicable.
 *
 * @param WC_Product $product
 * @return string
 */
function wm_marketing_get_sale_badge_html( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
		return '';
	}

	$config = wm_marketing_get_sale_badge_config();
	if ( empty( $config['enabled'] ) ) {
		return '';
	}

	$text = $config['text'];

	if ( false !== strpos( $text, '{percent}' ) ) {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		$percent = ( $regular > 0 ) ? round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
		$text    = str_replace( '{percent}', $percent, $text );
	}

	if ( '' === $text ) {
		return '';
	}

	return sprintf(
		'<span class="wm-product-card__badge" style="--wm-sale-badge-bg: %1$s; --wm-sale-badge-color: %2$s;">%3$s</span>',
		esc_attr( $config['bg'] ),
		esc_attr( $config['color'] ),
		esc_html( $text )
	);
}

/**
 * Promo banners configured for a given placement, filtered to those currently active by date.
 *
 * @param string $position One of 'home_top', 'home_middle', 'archive_top'.
 * @return array<int, array{image: int, link: string, position: string}>
 */
function wm_marketing_get_active_promo_banners( $position ) {
	$banners = wm_search_get_option( 'wm_marketing_promo_banners', array() );

	if ( empty( $banners ) || ! is_array( $banners ) ) {
		return array();
	}

	$today  = current_time( 'Y-m-d' );
	$active = array();

	foreach ( $banners as $banner ) {
		if ( empty( $banner['enabled'] ) || empty( $banner['image'] ) ) {
			continue;
		}

		if ( ( $banner['position'] ?? '' ) !== $position ) {
			continue;
		}

		if ( ! empty( $banner['start_date'] ) && $banner['start_date'] > $today ) {
			continue;
		}

		if ( ! empty( $banner['end_date'] ) && $banner['end_date'] < $today ) {
			continue;
		}

		$active[] = $banner;
	}

	return $active;
}

/**
 * Render promo banners for a given placement.
 *
 * @param string $position One of 'home_top', 'home_middle', 'archive_top'.
 * @return string
 */
function wm_marketing_render_promo_banners( $position ) {
	$banners = wm_marketing_get_active_promo_banners( $position );

	if ( empty( $banners ) ) {
		return '';
	}

	$output = '<div class="wm-promo-banners wm-promo-banners--' . esc_attr( $position ) . '">';

	foreach ( $banners as $banner ) {
		$image_html = wp_get_attachment_image( absint( $banner['image'] ), 'large', false, array( 'class' => 'wm-promo-banner__image', 'loading' => 'lazy' ) );

		if ( ! $image_html ) {
			continue;
		}

		if ( ! empty( $banner['link'] ) ) {
			$output .= sprintf( '<a class="wm-promo-banner" href="%1$s">%2$s</a>', esc_url( $banner['link'] ), $image_html );
		} else {
			$output .= sprintf( '<div class="wm-promo-banner">%s</div>', $image_html );
		}
	}

	$output .= '</div>';

	return $output;
}

function wm_search_get_suggested_products() {
	$ids = wm_search_get_option( 'wm_search_suggested_products', array() );

	if ( empty( $ids ) || ! is_array( $ids ) ) {
		return array();
	}

	$parsed_ids = array();
	foreach ( $ids as $item ) {
		$parsed_ids[] = is_object( $item ) ? $item->ID : absint( $item );
	}

	$products = array();

	if ( function_exists( 'wc_get_products' ) ) {
		$queried_products = wc_get_products(
			array(
				'include' => $parsed_ids,
				'limit'   => -1,
				'return'  => 'objects',
				'orderby' => 'post__in',
			)
		);

		foreach ( $queried_products as $product ) {
			if ( $product instanceof WC_Product && $product->is_visible() ) {
				$products[] = $product;
			}
		}
	} else {
		foreach ( $parsed_ids as $product_id ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
			if ( $product instanceof WC_Product && $product->is_visible() ) {
				$products[] = $product;
			}
		}
	}

	return $products;
}
