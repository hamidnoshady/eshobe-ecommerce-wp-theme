<?php
/**
 * Render callback for wm/price-filter-card.
 *
 * Builds the same $item shape the ACF-driven filter-boxes section already
 * uses, then reuses wm_render_filter_card() so markup/CSS/link-building
 * behavior (wm_build_filter_box_url) is identical to the existing cards.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wm_render_filter_card' ) ) {
    return;
}

$a = wp_parse_args(
    $attributes,
    array(
        'filterTitle'    => '',
        'filterSubtitle' => '',
        'badgeText'      => '',
        'buttonText'     => '',
        'style'          => 'dark_card',
        'imageId'        => 0,
        'linkMode'       => 'price_range',
        'manualUrl'      => '',
        'taxonomy'       => 'product_cat',
        'termId'         => 0,
        'minPrice'       => 0,
        'maxPrice'       => 0,
        'colorValue'     => '',
    )
);

if ( '' === trim( (string) $a['filterTitle'] ) ) {
    return;
}

$term = 0;
if ( $a['termId'] && taxonomy_exists( $a['taxonomy'] ) ) {
    $term_obj = get_term( absint( $a['termId'] ), $a['taxonomy'] );
    if ( $term_obj && ! is_wp_error( $term_obj ) ) {
        $term = $term_obj;
    }
}

$item = array(
    'filter_enabled'     => true,
    'filter_title'       => $a['filterTitle'],
    'filter_subtitle'    => $a['filterSubtitle'],
    'filter_image'       => absint( $a['imageId'] ),
    'filter_style'       => $a['style'],
    'filter_badge_text'  => $a['badgeText'],
    'filter_button_text' => $a['buttonText'] ? $a['buttonText'] : __( 'مشاهده', 'eshobe-ecommerce' ),
    'filter_link_mode'   => $a['linkMode'],
    'filter_manual_url'  => $a['manualUrl'],
    'filter_term'        => $term,
    'filter_min_price'   => $a['minPrice'] ? $a['minPrice'] : '',
    'filter_max_price'   => $a['maxPrice'] ? $a['maxPrice'] : '',
    'filter_color_value' => $a['colorValue'],
);

echo wm_render_filter_card( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
