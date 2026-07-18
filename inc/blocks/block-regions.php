<?php
/**
 * Generic block-editable content regions used to extend specific templates
 * (shop/category archive, single product) with admin-managed blocks without
 * touching their existing PHP-rendered markup. Each region is backed by a
 * hidden draft Page whose content is edited with the normal block editor;
 * as long as that page has no blocks, the region renders nothing and the
 * template's current output is completely unchanged.
 *
 * @package WM_Theme
 */

function wm_block_region_definitions() {
    return array(
        'shop_archive'   => array(
            'label' => __( 'زیر لیست محصولات (آرشیو/فروشگاه)', 'eshobe-ecommerce' ),
        ),
        'single_product' => array(
            'label' => __( 'زیر محصولات مرتبط (صفحه محصول)', 'eshobe-ecommerce' ),
        ),
    );
}

function wm_get_block_region_page_id( $region ) {
    $region = sanitize_key( $region );
    $id     = (int) get_option( 'wm_block_region_page_' . $region );

    if ( $id && 'page' === get_post_type( $id ) ) {
        return $id;
    }

    return 0;
}

function wm_get_or_create_block_region_page( $region ) {
    $definitions = wm_block_region_definitions();
    $region      = sanitize_key( $region );

    if ( ! isset( $definitions[ $region ] ) ) {
        return 0;
    }

    $existing_id = wm_get_block_region_page_id( $region );
    if ( $existing_id ) {
        return $existing_id;
    }

    $id = wp_insert_post(
        array(
            'post_type'   => 'page',
            'post_status' => 'draft',
            /* translators: %s: region label, e.g. "زیر لیست محصولات". */
            'post_title'  => sprintf( __( '[بلوک قالب] %s', 'eshobe-ecommerce' ), $definitions[ $region ]['label'] ),
        ),
        true
    );

    if ( is_wp_error( $id ) || ! $id ) {
        return 0;
    }

    update_option( 'wm_block_region_page_' . $region, $id );

    return $id;
}

function wm_render_block_region( $region ) {
    $id = wm_get_block_region_page_id( $region );
    if ( ! $id ) {
        return;
    }

    $post = get_post( $id );
    if ( ! $post || empty( $post->post_content ) || ! has_blocks( $post->post_content ) ) {
        return;
    }

    echo '<div class="wm-block-region wm-block-region--' . esc_attr( sanitize_key( $region ) ) . '">';
    echo apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
}
