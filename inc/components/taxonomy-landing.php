<?php
/**
 * Auto "all products in this taxonomy" landing page for product taxonomies
 * that have no dedicated archive of their own (e.g. ACF-registered
 * attribute-style taxonomies such as "strap-material"). Visiting the
 * taxonomy's bare rewrite base (e.g. /strap-material/) would otherwise
 * 404, since WordPress only generates rewrite rules per *term*, never a
 * root archive for the taxonomy itself.
 *
 * Rather than building a separate template, this rewrites the request into
 * a normal product-taxonomy query scoped to every term of that taxonomy
 * (an OR across all its terms), so WooCommerce/the theme's existing
 * archive-product.php (filters, sorting, pagination, product cards) renders
 * exactly as it does for a single term's archive.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public product taxonomies eligible for the auto landing page, keyed by
 * their rewrite base (the URL segment WordPress matches), mapped to the
 * taxonomy name.
 *
 * product_cat and product_brand already have dedicated archive templates
 * and are excluded.
 */
function wm_taxonomy_landing_get_taxonomies() {
    $taxonomies = array();

    foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
        if ( in_array( $taxonomy->name, array( 'product_cat', 'product_brand', 'category', 'post_tag', 'product_shipping_class' ), true ) ) {
            continue;
        }

        if ( ! is_object_in_taxonomy( 'product', $taxonomy->name ) ) {
            continue;
        }

        if ( false === $taxonomy->rewrite ) {
            continue;
        }

        $slug = ! empty( $taxonomy->rewrite['slug'] ) ? $taxonomy->rewrite['slug'] : $taxonomy->name;
        $taxonomies[ trim( $slug, '/' ) ] = $taxonomy->name;
    }

    return $taxonomies;
}

/**
 * URL for a taxonomy's "all products" landing page, or '' if the taxonomy
 * isn't a product taxonomy at all.
 *
 * Taxonomies with a usable rewrite base (see wm_taxonomy_landing_get_taxonomies())
 * get a pretty bare-slug URL handled by the hijack below. WooCommerce product
 * attribute taxonomies (pa_*) are registered non-public with no rewrite base,
 * so they instead get the shop page filtered by every term via a query var,
 * handled by wm_taxonomy_landing_maybe_filter_shop_query() below.
 *
 * @param string $taxonomy_name
 * @return string
 */
function wm_taxonomy_landing_get_base_url( $taxonomy_name ) {
    if ( ! taxonomy_exists( $taxonomy_name ) || ! is_object_in_taxonomy( 'product', $taxonomy_name ) ) {
        return '';
    }

    $slug = array_search( $taxonomy_name, wm_taxonomy_landing_get_taxonomies(), true );
    if ( $slug ) {
        return home_url( '/' . $slug . '/' );
    }

    $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

    return add_query_arg( 'wm_tax', $taxonomy_name, $shop_url );
}

add_action( 'parse_request', 'wm_taxonomy_landing_maybe_hijack_query' );
/**
 * Catch requests to a taxonomy's bare base URL (which 404 by default) and
 * turn them into a product query across every term of that taxonomy.
 *
 * @param WP $wp
 */
function wm_taxonomy_landing_maybe_hijack_query( $wp ) {
    $request = trim( $wp->request, '/' );
    if ( '' === $request ) {
        return;
    }

    $taxonomies = wm_taxonomy_landing_get_taxonomies();
    if ( ! isset( $taxonomies[ $request ] ) ) {
        return;
    }

    $taxonomy_name = $taxonomies[ $request ];
    $term_ids      = get_terms(
        array(
            'taxonomy'   => $taxonomy_name,
            'hide_empty' => true,
            'fields'     => 'ids',
        )
    );

    if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
        return; // Nothing to show; let the real 404 stand.
    }

    $wp->query_vars = array(
        'post_type' => 'product',
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy_name,
                'field'    => 'term_id',
                'terms'    => $term_ids,
                'operator' => 'IN',
            ),
        ),
    );

    $GLOBALS['wm_taxonomy_landing_taxonomy'] = $taxonomy_name;
}

add_action( 'pre_get_posts', 'wm_taxonomy_landing_maybe_filter_shop_query' );
/**
 * Companion to the rewrite-base hijack above, for taxonomies with no
 * rewrite base (WooCommerce attribute taxonomies): ?wm_tax=pa_xxx on the
 * shop page scopes it to every term of that taxonomy.
 *
 * @param WP_Query $query
 */
function wm_taxonomy_landing_maybe_filter_shop_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! function_exists( 'is_shop' ) || ! is_shop() ) {
        return;
    }

    $taxonomy_name = isset( $_GET['wm_tax'] ) ? sanitize_key( wp_unslash( $_GET['wm_tax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! $taxonomy_name || ! taxonomy_exists( $taxonomy_name ) || ! is_object_in_taxonomy( 'product', $taxonomy_name ) ) {
        return;
    }

    $term_ids = get_terms(
        array(
            'taxonomy'   => $taxonomy_name,
            'hide_empty' => true,
            'fields'     => 'ids',
        )
    );

    if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
        return;
    }

    $tax_query   = (array) $query->get( 'tax_query' );
    $tax_query[] = array(
        'taxonomy' => $taxonomy_name,
        'field'    => 'term_id',
        'terms'    => $term_ids,
        'operator' => 'IN',
    );
    $query->set( 'tax_query', $tax_query );

    $GLOBALS['wm_taxonomy_landing_taxonomy'] = $taxonomy_name;
}

add_filter( 'woocommerce_page_title', 'wm_taxonomy_landing_page_title' );
/**
 * The main query's queried object is whichever single term happened to be
 * first in the tax query, so the default title would show one term's name
 * instead of the taxonomy as a whole. Show the taxonomy's label instead.
 */
function wm_taxonomy_landing_page_title( $title ) {
    if ( empty( $GLOBALS['wm_taxonomy_landing_taxonomy'] ) ) {
        return $title;
    }

    $taxonomy = get_taxonomy( $GLOBALS['wm_taxonomy_landing_taxonomy'] );

    return $taxonomy ? $taxonomy->labels->name : $title;
}
