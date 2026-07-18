<?php
/**
 * Native Gutenberg block registrations powering the block-based homepage /
 * area management system. Every block renders through the theme's existing
 * PHP components (wm_render_home_section, wm_render_product_carousel,
 * wm_render_filter_card), so front-end markup/CSS/JS never changes because
 * of this feature by itself — only adding/reordering/configuring these
 * blocks in the editor changes anything.
 *
 * @package WM_Theme
 */

function wm_blocks_get_registry() {
    return array( 'home-section', 'product-carousel', 'filter-section', 'price-filter-card' );
}

function wm_register_blocks_editor_script() {
    wp_register_script(
        'eshobe-ecommerce-blocks-editor',
        wm_asset_uri( 'assets/js/blocks-editor.js' ),
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data', 'wp-compose' ),
        wm_asset_version( 'assets/js/blocks-editor.js' ),
        true
    );

    wp_localize_script(
        'eshobe-ecommerce-blocks-editor',
        'wmBlockEditorData',
        array(
            'homeSections' => wm_home_default_sections(),
            'taxonomies'   => wm_blocks_get_supported_taxonomies(),
        )
    );
}
add_action( 'init', 'wm_register_blocks_editor_script', 5 );

function wm_register_blocks() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    foreach ( wm_blocks_get_registry() as $block ) {
        $path = get_template_directory() . '/blocks/' . $block;
        if ( file_exists( $path . '/block.json' ) ) {
            register_block_type( $path );
        }
    }
}
add_action( 'init', 'wm_register_blocks', 10 );

function wm_register_blocks_category( $categories ) {
    array_unshift(
        $categories,
        array(
            'slug'  => 'eshobe-ecommerce',
            'title' => __( 'قالب اشوبی', 'eshobe-ecommerce' ),
        )
    );

    return $categories;
}
add_filter( 'block_categories_all', 'wm_register_blocks_category' );

/**
 * Taxonomies offered in the product-carousel / price-filter-card block controls.
 *
 * @return array<string, string> taxonomy slug => human label.
 */
function wm_blocks_get_supported_taxonomies() {
    $labels     = array();
    $candidates = array( 'product_cat', 'product_tag', 'product_brand', 'pa_brand', 'brand' );

    foreach ( $candidates as $taxonomy ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }

        $object            = get_taxonomy( $taxonomy );
        $labels[ $taxonomy ] = ( $object && ! empty( $object->labels->singular_name ) ) ? $object->labels->singular_name : $taxonomy;
    }

    return $labels;
}

/**
 * Loads the theme's front-end styles in the block editor so ServerSideRender
 * previews of these blocks look like the real site, not unstyled HTML.
 */
function wm_enqueue_block_editor_preview_styles() {
    wp_enqueue_style( 'eshobe-ecommerce-fonts', wm_asset_uri( 'assets/css/fonts.css' ), array(), wm_asset_version( 'assets/css/fonts.css' ) );
    wp_enqueue_style( 'eshobe-ecommerce-tokens', wm_asset_uri( 'assets/css/tokens.css' ), array( 'eshobe-ecommerce-fonts' ), wm_asset_version( 'assets/css/tokens.css' ) );
    wp_enqueue_style( 'eshobe-ecommerce-style', wm_asset_uri( 'assets/css/theme.css' ), array( 'eshobe-ecommerce-tokens' ), wm_asset_version( 'assets/css/theme.css' ) );
    wp_enqueue_style( 'eshobe-ecommerce-decorative-motifs', wm_asset_uri( 'assets/css/components/decorative-motifs.css' ), array( 'eshobe-ecommerce-style' ), wm_asset_version( 'assets/css/components/decorative-motifs.css' ) );
    wp_enqueue_style( 'eshobe-ecommerce-product-components', wm_asset_uri( 'assets/css/product-components.css' ), array( 'eshobe-ecommerce-style' ), wm_asset_version( 'assets/css/product-components.css' ) );
    wp_enqueue_style( 'eshobe-ecommerce-home', wm_asset_uri( 'assets/css/pages/home.css' ), array( 'eshobe-ecommerce-style' ), wm_asset_version( 'assets/css/pages/home.css' ) );

    if ( function_exists( 'wm_get_design_tokens_css' ) ) {
        wp_add_inline_style( 'eshobe-ecommerce-style', wm_get_design_tokens_css() );
    }
}
add_action( 'enqueue_block_editor_assets', 'wm_enqueue_block_editor_preview_styles' );
