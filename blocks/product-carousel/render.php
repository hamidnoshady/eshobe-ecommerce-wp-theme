<?php
/**
 * Render callback for wm/product-carousel.
 *
 * Reuses the theme's shared wm_render_product_carousel() renderer, so output
 * markup/CSS/JS hooks are identical to the existing bestsellers/recommended
 * carousels.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wm_render_product_carousel' ) ) {
    return;
}

$attributes = wp_parse_args(
    $attributes,
    array(
        'title'    => '',
        'subtitle' => '',
        'source'   => 'taxonomy',
        'taxonomy' => 'product_cat',
        'terms'    => '',
        'count'    => 10,
        'orderby'  => 'date',
        'order'    => 'DESC',
    )
);

$count = max( 1, absint( $attributes['count'] ) );

$view_all_url = '';
if ( $attributes['source'] === 'taxonomy' && ! empty( $attributes['taxonomy'] ) ) {
    if ( ! empty( $attributes['terms'] ) ) {
        $term_values = array_values( array_filter( array_map( 'trim', explode( ',', (string) $attributes['terms'] ) ) ) );
        if ( count( $term_values ) === 1 ) {
            $term = is_numeric( $term_values[0] ) ? get_term( (int) $term_values[0], $attributes['taxonomy'] ) : get_term_by( 'slug', $term_values[0], $attributes['taxonomy'] );
            if ( $term && ! is_wp_error( $term ) ) {
                $view_all_url = get_term_link( $term );
            }
        }
    }
    if ( empty( $view_all_url ) && function_exists( 'wc_get_page_id' ) ) {
        $view_all_url = get_permalink( wc_get_page_id( 'shop' ) );
    }
} elseif ( in_array( $attributes['source'], array( 'recommended', 'bestsellers' ), true ) && function_exists( 'wc_get_page_id' ) ) {
    $view_all_url = get_permalink( wc_get_page_id( 'shop' ) );
}

if ( in_array( $attributes['source'], array( 'recommended', 'bestsellers' ), true ) && function_exists( 'wm_home_get_products' ) ) {
    $products = wm_home_get_products( $attributes['source'], $count );
} elseif ( function_exists( 'wm_get_taxonomy_carousel_products' ) ) {
    $products = wm_get_taxonomy_carousel_products( $attributes['taxonomy'], $attributes['terms'], $count, $attributes['orderby'], $attributes['order'] );
} else {
    $products = array();
}

echo wm_render_product_carousel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $products,
    array(
        'title'    => $attributes['title'],
        'subtitle' => $attributes['subtitle'],
        'class'    => 'wm-home-section wm-home-products',
        'view_all' => $view_all_url,
    )
);
