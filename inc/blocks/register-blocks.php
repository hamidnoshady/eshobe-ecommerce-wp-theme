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
    return array( 'home-section', 'product-carousel', 'post-carousel', 'filter-section', 'price-filter-card' );
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
 * Discovers every public product taxonomy (categories, tags, brand, other
 * custom taxonomies) plus WooCommerce attribute taxonomies, instead of a
 * hardcoded slug list, so newly added taxonomies show up automatically.
 *
 * @return array<string, array{label: string, hierarchical: bool}>
 */
function wm_blocks_get_supported_taxonomies() {
    $taxonomies = array();
    $excluded   = array( 'product_type', 'product_visibility', 'product_shipping_class', 'pos_product_visibility' );

    foreach ( get_object_taxonomies( 'product', 'objects' ) as $slug => $object ) {
        if ( empty( $object->public ) || in_array( $slug, $excluded, true ) ) {
            continue;
        }

        $taxonomies[ $slug ] = array(
            'label'        => ! empty( $object->labels->singular_name ) ? $object->labels->singular_name : $slug,
            'hierarchical' => ! empty( $object->hierarchical ),
        );
    }

    if ( function_exists( 'wc_get_attribute_taxonomies' ) && function_exists( 'wc_attribute_taxonomy_name' ) ) {
        foreach ( wc_get_attribute_taxonomies() as $attribute ) {
            if ( empty( $attribute->attribute_name ) ) {
                continue;
            }

            $slug = wc_attribute_taxonomy_name( $attribute->attribute_name );
            if ( isset( $taxonomies[ $slug ] ) ) {
                continue;
            }

            $taxonomies[ $slug ] = array(
                'label'        => $attribute->attribute_label,
                'hierarchical' => false, // WooCommerce attribute taxonomies never support term parent/child.
            );
        }
    }

    return $taxonomies;
}

/**
 * Stylesheets needed for ServerSideRender previews of the theme's blocks
 * (hero, carousels, filter cards, …) to look like the real site.
 *
 * @return array<string, string> handle => relative asset path (order = dependency order).
 */
function wm_block_editor_preview_stylesheets() {
    return array(
        'eshobe-ecommerce-fonts'              => 'assets/css/fonts.css',
        'eshobe-ecommerce-tokens'             => 'assets/css/tokens.css',
        'eshobe-ecommerce-style'              => 'assets/css/theme.css',
        'eshobe-ecommerce-decorative-motifs'  => 'assets/css/components/decorative-motifs.css',
        'eshobe-ecommerce-product-components' => 'assets/css/components/product-components.css',
        'eshobe-ecommerce-home'               => 'assets/css/pages/home.css',
        'eshobe-ecommerce-blog'               => 'assets/css/pages/blog.css',
    );
}

/**
 * Loads the theme's front-end styles in the block editor so ServerSideRender
 * previews of these blocks look like the real site, not unstyled HTML.
 *
 * IMPORTANT: this deliberately hooks `enqueue_block_assets` (guarded by
 * is_admin()), NOT `enqueue_block_editor_assets`. Since WP 6.2 the editor
 * canvas renders inside an iframe (enforced on every theme from WP 7.1 on).
 * Styles enqueued via enqueue_block_editor_assets only land in the admin
 * document and silently stop applying to the canvas; core collects styles
 * enqueued on `enqueue_block_assets` into the iframe document as well
 * (see _wp_get_iframed_editor_assets()), while still printing them in the
 * admin document for the legacy non-iframed path. One hook covers both.
 * The front end is skipped because eshobe_ecommerce_scripts() already
 * enqueues (a superset of) these stylesheets on wp_enqueue_scripts.
 */
function wm_enqueue_block_editor_preview_styles() {
    if ( ! is_admin() ) {
        return;
    }

    foreach ( wm_block_editor_preview_stylesheets() as $wm_handle => $wm_relative_path ) {
        wp_enqueue_style( $wm_handle, wm_asset_uri( $wm_relative_path ), array(), wm_asset_version( $wm_relative_path ) );
    }

    if ( function_exists( 'wm_get_design_tokens_css' ) ) {
        wp_add_inline_style( 'eshobe-ecommerce-style', wm_get_design_tokens_css() );
    }

    // On the front end the home sections sit inside <main class="wm-home">
    // (front-page.php), which defines the custom properties home.css relies
    // on (shadows, motion timing). The editor canvas has no .wm-home
    // wrapper, so re-declare them on the canvas wrapper — otherwise
    // box-shadow/transition declarations that use var(--wm-home-*) are
    // dropped and previews drift from the front end. Only loaded in the
    // editor request; never reaches the front end.
    wp_add_inline_style(
        'eshobe-ecommerce-style',
        '.editor-styles-wrapper{'
        . '--wm-home-shadow-sm:0 10px 26px rgba(17,24,39,0.045);'
        . '--wm-home-shadow-md:0 16px 36px rgba(17,24,39,0.07);'
        . '--wm-home-shadow-accent:0 16px 34px rgba(200,155,60,0.13);'
        . '--wm-home-hover-border:rgba(200,155,60,0.5);'
        . '--wm-home-motion:260ms cubic-bezier(0.22,1,0.36,1);'
        . '}'
    );
}
add_action( 'enqueue_block_assets', 'wm_enqueue_block_editor_preview_styles' );
