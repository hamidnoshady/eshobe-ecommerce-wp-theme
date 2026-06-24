<?php
/**
 * Read-only helpers for reading YITH Color, Image & Label Variation Swatches
 * term-meta, so the theme can render its own swatch UI without depending on
 * YITH's frontend JS. Falls back to plain label swatches if YITH is inactive
 * or an attribute isn't configured with a YITH swatch type. Never writes to
 * YITH's data — its admin screens keep working unchanged.
 *
 * @package WM_Theme
 */

if ( ! class_exists( 'WooCommerce' ) ) {
    return;
}

/**
 * Get the YITH swatch type configured for a product attribute taxonomy.
 *
 * @param string $taxonomy Attribute taxonomy name, e.g. 'pa_color' or 'color'.
 * @return string One of 'colorpicker', 'image', 'label', or 'none'.
 */
function wm_get_attribute_swatch_type( $taxonomy ) {
    if ( ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
        return 'none';
    }

    $attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy );
    if ( ! $attribute_id ) {
        return 'none';
    }

    $taxonomies = wc_get_attribute_taxonomies();
    $tax_object = isset( $taxonomies[ 'id:' . $attribute_id ] ) ? $taxonomies[ 'id:' . $attribute_id ] : null;

    if ( ! $tax_object || empty( $tax_object->attribute_type ) ) {
        return 'none';
    }

    $supported = array( 'colorpicker', 'image', 'label' );

    return in_array( $tax_object->attribute_type, $supported, true ) ? $tax_object->attribute_type : 'none';
}

/**
 * Get normalized swatch-rendering data for a single attribute term, reading
 * YITH's term meta read-only. Falls back to a plain label using the term
 * name if YITH isn't active or the term has no swatch value configured.
 *
 * @param WP_Term $term     The attribute term.
 * @param string  $taxonomy Attribute taxonomy name (e.g. 'pa_color').
 * @return array{type: string, value: string, value2: string, tooltip: string}
 */
function wm_get_term_swatch_data( $term, $taxonomy ) {
    $fallback = array(
        'type'    => 'label',
        'value'   => $term->name,
        'value2'  => '',
        'tooltip' => '',
    );

    if ( ! function_exists( 'ywccl_get_term_meta' ) ) {
        return $fallback;
    }

    $swatch_type = wm_get_attribute_swatch_type( $taxonomy );
    if ( 'none' === $swatch_type ) {
        return $fallback;
    }

    $value   = ywccl_get_term_meta( $term->term_id, '_yith_wccl_value', true, $taxonomy );
    $tooltip = ywccl_get_term_meta( $term->term_id, '_yith_wccl_tooltip', true, $taxonomy );
    $tooltip = is_string( $tooltip ) ? $tooltip : '';

    if ( 'colorpicker' === $swatch_type ) {
        $swatch_subtype = ywccl_get_term_meta( $term->term_id, '_yith_wccl_swatch_type', true, $taxonomy );

        if ( 'image_color' === $swatch_subtype ) {
            $image_url = ywccl_get_term_meta( $term->term_id, '_yith_wccl_attribute_image', true, $taxonomy );
            return array(
                'type'    => 'image',
                'value'   => $image_url ? $image_url : '',
                'value2'  => '',
                'tooltip' => $tooltip,
            );
        }

        $colors = is_string( $value ) ? array_values( array_filter( array_map( 'trim', explode( ',', $value ) ) ) ) : array();

        if ( empty( $colors ) ) {
            return $fallback;
        }

        return array(
            'type'    => 'color',
            'value'   => $colors[0],
            'value2'  => isset( $colors[1] ) ? $colors[1] : '',
            'tooltip' => $tooltip,
        );
    }

    if ( 'image' === $swatch_type ) {
        return array(
            'type'    => 'image',
            'value'   => $value ? $value : '',
            'value2'  => '',
            'tooltip' => $tooltip,
        );
    }

    // 'label' type.
    return array(
        'type'    => 'label',
        'value'   => $value ? $value : $term->name,
        'value2'  => '',
        'tooltip' => $tooltip,
    );
}
