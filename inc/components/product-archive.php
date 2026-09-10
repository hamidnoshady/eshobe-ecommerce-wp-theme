<?php
/**
 * Product archive layout and settings.
 *
 * @package WM_Theme
 */

function wm_product_archive_defaults() {
    return array(
        'wm_archive_products_per_page'        => 12,
        'wm_archive_columns_desktop'          => 3,
        'wm_archive_columns_tablet'           => 2,
        'wm_archive_columns_mobile'           => 2,
        'wm_archive_sidebar_enabled'          => 1,
        'wm_archive_sidebar_default_state'    => 'open',
        'wm_archive_filter_sidebar_position'  => 'right',
        'wm_archive_custom_filters_enabled'   => 1,
        'wm_archive_filter_ajax_enabled'      => 1,
        'wm_archive_filter_price_enabled'     => 1,
        'wm_archive_filter_stock_enabled'     => 0,
        'wm_archive_filter_show_counts'       => 0,
        'wm_archive_filter_empty_behavior'    => 'hide',
        'wm_archive_filter_term_limit'        => 24,
        'wm_archive_filter_taxonomies'        => array(),
        'wm_archive_filter_price_step'        => 10000,
        'wm_archive_filter_accordion_default' => 'closed',
        'wm_archive_filter_hierarchy_enabled' => 1,
        'wm_archive_filter_hierarchy_toggle'  => 1,
        'wm_archive_filter_hierarchy_depth'   => 4,
        'wm_archive_show_result_count'        => 1,
        'wm_archive_show_ordering'            => 1,
        'wm_archive_show_archive_description' => 1,
        'wm_archive_card_style'               => 'site_default',
        'wm_archive_enable_card_hover_image'  => 1,
        'wm_archive_enable_ajax_add_to_cart'  => 1,
    );
}

function wm_product_archive_get_option( $name, $default = null ) {
    $defaults = wm_product_archive_defaults();
    $default  = null === $default && array_key_exists( $name, $defaults ) ? $defaults[ $name ] : $default;

    return wm_get_option( $name, $default );
}

function wm_product_archive_int_option( $name, $default, $min, $max ) {
    $value = wm_product_archive_get_option( $name, $default );
    $value = is_numeric( $value ) ? absint( $value ) : absint( $default );

    return min( max( $value, $min ), $max );
}

function wm_product_archive_bool_option( $name, $default = true ) {
    $value = wm_product_archive_get_option( $name, $default ? 1 : 0 );

    return ! ( false === $value || '0' === $value || 0 === $value );
}

function wm_product_archive_choice_option( $name, $allowed, $default ) {
    $value = wm_product_archive_get_option( $name, $default );
    $value = is_scalar( $value ) ? (string) $value : $default;

    return in_array( $value, $allowed, true ) ? $value : $default;
}

function wm_product_archive_sidebar_enabled() {
    return wm_product_archive_bool_option( 'wm_archive_sidebar_enabled', true );
}

function wm_product_archive_is_context() {
    if ( ! function_exists( 'is_shop' ) ) {
        return false;
    }
    $post_type = get_query_var( 'post_type' );

    return is_shop()
        || is_product_taxonomy()
        || is_post_type_archive( 'product' )
        || ( is_search() && ( 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) );
}

function wm_product_archive_products_per_page( $per_page ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return $per_page;
    }

    return wm_product_archive_int_option( 'wm_archive_products_per_page', 12, 1, 48 );
}
add_filter( 'loop_shop_per_page', 'wm_product_archive_products_per_page', 20 );

function wm_product_archive_register_sidebar() {
    register_sidebar(
        array(
            'name'          => esc_html__( 'Product Archive Filters', 'eshobe-ecommerce' ),
            'id'            => 'product-archive-filters',
            'description'   => esc_html__( 'فیلترهای صفحه آرشیو محصولات', 'eshobe-ecommerce' ),
            'before_widget' => '<section id="%1$s" class="wm-archive-filter-widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3 class="wm-archive-filter-widget__title">',
            'after_title'   => '</h3>',
        )
    );
}
add_action( 'widgets_init', 'wm_product_archive_register_sidebar' );

function wm_product_archive_filter_config() {
    static $cache = null;
    if ( null !== $cache ) {
        return $cache;
    }

    $taxonomies = array();
    $selected   = (array) wm_product_archive_get_option( 'wm_archive_filter_taxonomies', array() );
    $selected   = array_values( array_filter( array_map( 'sanitize_key', $selected ) ) );

    if ( taxonomy_exists( 'product_cat' ) ) {
        $taxonomies[] = array(
            'key'      => 'filter_product_cat',
            'taxonomy' => 'product_cat',
            'label'    => esc_html__( 'دسته‌بندی', 'eshobe-ecommerce' ),
            'type'     => 'taxonomy',
        );
    }

    foreach ( array( 'product_brand', 'pa_brand', 'brand' ) as $brand_taxonomy ) {
        if ( taxonomy_exists( $brand_taxonomy ) ) {
            $taxonomies[] = array(
                'key'      => 'filter_brand',
                'taxonomy' => $brand_taxonomy,
                'label'    => esc_html__( 'برند', 'eshobe-ecommerce' ),
                'type'     => 'taxonomy',
            );
            break;
        }
    }

    if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
        foreach ( wc_get_attribute_taxonomies() as $attribute ) {
            if ( empty( $attribute->attribute_name ) || ! function_exists( 'wc_attribute_taxonomy_name' ) ) {
                continue;
            }

            $taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
            if ( ! taxonomy_exists( $taxonomy ) || 'pa_brand' === $taxonomy ) {
                continue;
            }

            $taxonomies[] = array(
                'key'      => 'filter_' . sanitize_title( $attribute->attribute_name ),
                'taxonomy' => $taxonomy,
                'label'    => function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $taxonomy ) : $attribute->attribute_label,
                'type'     => 'taxonomy',
            );
        }
    }

    foreach ( get_object_taxonomies( 'product', 'objects' ) as $taxonomy => $object ) {
        if (
            ! $object->public
            || in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_type', 'product_visibility', 'product_shipping_class' ), true )
            || 0 === strpos( $taxonomy, 'pa_' )
        ) {
            continue;
        }

        $taxonomies[] = array(
            'key'      => 'filter_' . sanitize_title( $taxonomy ),
            'taxonomy' => $taxonomy,
            'label'    => $object->labels->singular_name ? $object->labels->singular_name : $object->label,
            'type'     => 'taxonomy',
        );
    }

    $taxonomies = array_values(
        array_reduce(
            $taxonomies,
            function( $carry, $filter ) {
                if ( ! isset( $carry[ $filter['taxonomy'] ] ) ) {
                    $carry[ $filter['taxonomy'] ] = $filter;
                }
                return $carry;
            },
            array()
        )
    );

    if ( ! empty( $selected ) ) {
        $taxonomies = array_values(
            array_filter(
                $taxonomies,
                function( $filter ) use ( $selected ) {
                    return in_array( $filter['taxonomy'], $selected, true );
                }
            )
        );
    }

    $cache = array(
        'enabled'        => wm_product_archive_bool_option( 'wm_archive_custom_filters_enabled', true ),
        'ajax_enabled'   => wm_product_archive_bool_option( 'wm_archive_filter_ajax_enabled', true ),
        'show_price'     => wm_product_archive_bool_option( 'wm_archive_filter_price_enabled', true ),
        'show_stock'     => wm_product_archive_bool_option( 'wm_archive_filter_stock_enabled', false ),
        'show_counts'    => wm_product_archive_bool_option( 'wm_archive_filter_show_counts', false ),
        'empty_behavior' => wm_product_archive_choice_option( 'wm_archive_filter_empty_behavior', array( 'hide', 'disable', 'show' ), 'hide' ),
        'term_limit'     => wm_product_archive_int_option( 'wm_archive_filter_term_limit', 24, 1, 200 ),
        'price_step'     => wm_product_archive_int_option( 'wm_archive_filter_price_step', 10000, 1, 100000000 ),
        'accordion_default' => wm_product_archive_choice_option( 'wm_archive_filter_accordion_default', array( 'open', 'closed' ), 'closed' ),
        'hierarchy_enabled' => wm_product_archive_bool_option( 'wm_archive_filter_hierarchy_enabled', true ),
        'hierarchy_toggle' => wm_product_archive_bool_option( 'wm_archive_filter_hierarchy_toggle', true ),
        'hierarchy_depth' => wm_product_archive_int_option( 'wm_archive_filter_hierarchy_depth', 4, 1, 8 ),
        'taxonomies'    => apply_filters( 'wm_product_archive_filter_taxonomies', $taxonomies ),
    );

    return $cache;
}

function wm_product_archive_get_filter_values( $key ) {
    if ( ! isset( $_GET['wm_archive_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['wm_archive_nonce'] ), 'wm_product_archive_filter' ) ) {
        return array();
    }

    $keys = array( $key );
    if ( 'filter_product_cat' === $key ) {
        $keys[] = 'product_cat';
    } elseif ( 'product_cat' === $key ) {
        $keys[] = 'filter_product_cat';
    }

    $values = array();
    foreach ( array_unique( $keys ) as $query_key ) {
        if ( ! isset( $_GET[ $query_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            continue;
        }

        $values = array_merge( $values, wm_product_archive_query_values( $_GET[ $query_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    if ( empty( $values ) ) {
        return array();
    }

    return array_values( array_unique( array_filter( $values ) ) );
}

function wm_get_csv_request_values( $key ) {
    if ( ! isset( $_GET['wm_archive_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['wm_archive_nonce'] ), 'wm_product_archive_filter' ) ) {
        return array();
    }

    if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return array();
    }

    $raw_values = (array) wp_unslash( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $values     = array();

    array_walk_recursive(
        $raw_values,
        function( $raw_value ) use ( &$values ) {
            foreach ( explode( ',', (string) $raw_value ) as $value ) {
                $value = sanitize_title( trim( $value ) );
                if ( '' !== $value ) {
                    $values[] = $value;
                }
            }
        }
    );

    return array_values( array_unique( $values ) );
}

function wm_product_archive_filter_key_aliases( $key, $taxonomy = '' ) {
    $aliases = array( $key );

    if ( in_array( $key, array( 'filter_product_cat', 'product_cat' ), true ) || 'product_cat' === $taxonomy ) {
        $aliases[] = 'filter_product_cat';
        $aliases[] = 'product_cat';
    }

    if ( $taxonomy ) {
        $aliases[] = $taxonomy;
        $aliases[] = 'filter_' . $taxonomy;

        if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
            $attribute = substr( $taxonomy, 3 );
            $aliases[] = $attribute;
            $aliases[] = 'filter_' . $attribute;
            $aliases[] = 'filter_pa_' . $attribute;
        }
    }

    $suffix = 0 === strpos( $key, 'filter_' ) ? substr( $key, 7 ) : $key;
    if ( $suffix && $suffix !== $key ) {
        $aliases[] = $suffix;
        $aliases[] = 'pa_' . $suffix;
        $aliases[] = 'filter_pa_' . $suffix;
    }

    return array_values( array_unique( array_filter( $aliases ) ) );
}

function wm_product_archive_get_taxonomy_filter_values( $key, $taxonomy ) {
    $values = array();

    foreach ( wm_product_archive_filter_key_aliases( $key, $taxonomy ) as $alias ) {
        $values = array_merge( $values, wm_product_archive_get_filter_values( $alias ) );
    }

    return array_values( array_unique( array_filter( $values ) ) );
}

function wm_product_archive_render_tax_filter( $filter ) {
    $config = wm_product_archive_filter_config();
    $terms = get_terms(
        array(
            'taxonomy'   => $filter['taxonomy'],
            'hide_empty' => true,
            'number'     => $config['term_limit'],
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return;
    }

    $selected = wm_product_archive_taxonomy_filter_slugs( $filter['key'], $filter['taxonomy'] );

    if ( 'product_cat' === $filter['taxonomy'] ) {
        $scope_ids = wm_product_archive_category_scope_product_ids();
    } else {
        $scope_ids = wm_product_archive_scope_product_ids();
    }

    $available_ids = wm_product_archive_available_term_ids( $filter['taxonomy'], $scope_ids );
    $terms         = wm_product_archive_filter_terms_by_availability( $terms, $available_ids, $selected, $config['empty_behavior'] );

    if ( empty( $terms ) ) {
        return;
    }

    $is_hierarchy = ! empty( $config['hierarchy_enabled'] ) && is_taxonomy_hierarchical( $filter['taxonomy'] );
    $collapsed    = 'closed' === $config['accordion_default'];
    ?>
    <section class="wm-archive-filter-widget wm-custom-filter<?php echo $collapsed ? ' is-collapsed' : ''; ?>" data-filter-group="<?php echo esc_attr( $filter['key'] ); ?>">
        <h3 class="wm-archive-filter-widget__title"><?php echo esc_html( $filter['label'] ); ?></h3>
        <?php wm_product_archive_render_term_tree( $terms, $filter['key'], $selected, $is_hierarchy ? 0 : null, 0, $config ); ?>
    </section>
    <?php
}

function wm_product_archive_term_has_selected_descendant( $terms, $term_id, $selected ) {
    static $hierarchy = array();
    static $last_terms = null;
    static $selected_map = array();
    static $last_selected = null;
    static $cache = array();

    if ( $last_terms !== $terms ) {
        $hierarchy = array();
        foreach ( $terms as $term ) {
            $term_parent = (int) $term->parent;
            if ( ! isset( $hierarchy[ $term_parent ] ) ) {
                $hierarchy[ $term_parent ] = array();
            }
            $hierarchy[ $term_parent ][] = $term;
        }
        $last_terms = $terms;
        $cache = array();
    }

    if ( $last_selected !== $selected ) {
        $selected_map  = array_flip( $selected );
        $last_selected = $selected;
        $cache = array();
    }

    // ⚡ Bolt Optimization:
    // Caching recursive descendant lookups prevents O(N²) traversal when rendering
    // large filter trees, reducing evaluation time drastically for deep/large taxonomies.
    if ( isset( $cache[ $term_id ] ) ) {
        return $cache[ $term_id ];
    }

    if ( empty( $hierarchy[ $term_id ] ) ) {
        $cache[ $term_id ] = false;
        return false;
    }

    foreach ( $hierarchy[ $term_id ] as $term ) {
        if ( isset( $selected_map[ $term->slug ] ) || wm_product_archive_term_has_selected_descendant( $terms, (int) $term->term_id, $selected ) ) {
            $cache[ $term_id ] = true;
            return true;
        }
    }

    $cache[ $term_id ] = false;
    return false;
}

function wm_product_archive_render_term_tree( $terms, $key, $selected, $parent = null, $depth = 0, $config = array() ) {
    if ( isset( $config['hierarchy_depth'] ) && $depth >= (int) $config['hierarchy_depth'] ) {
        return;
    }

    static $hierarchy = array();
    static $last_terms = null;
    static $selected_map = array();
    static $last_selected = null;

    if ( $last_terms !== $terms ) {
        $hierarchy = array();
        foreach ( $terms as $term ) {
            $term_parent = (int) $term->parent;
            if ( ! isset( $hierarchy[ $term_parent ] ) ) {
                $hierarchy[ $term_parent ] = array();
            }
            $hierarchy[ $term_parent ][] = $term;
        }
        $last_terms = $terms;
    }

    if ( $last_selected !== $selected ) {
        $selected_map  = array_flip( $selected );
        $last_selected = $selected;
    }

    if ( null === $parent ) {
        $children = $terms;
    } else {
        $children = isset( $hierarchy[ (int) $parent ] ) ? $hierarchy[ (int) $parent ] : array();
    }

    if ( empty( $children ) ) {
        return;
    }
    ?>
    <ul class="wm-custom-filter__options<?php echo 0 < $depth ? ' wm-custom-filter__options--child' : ''; ?>" <?php echo null !== $parent ? 'id="wm-filter-tree-' . esc_attr( $key . '-' . $parent ) . '"' : ''; ?>>
        <?php foreach ( $children as $term ) : ?>
            <?php
            $checked       = isset( $selected_map[ $term->slug ] );
            $has_children  = ! empty( $hierarchy[ (int) $term->term_id ] );
            $tree_open     = $checked || ( $has_children && wm_product_archive_term_has_selected_descendant( $terms, (int) $term->term_id, $selected ) );
            $unavailable   = ! empty( $term->wm_unavailable );
            ?>
            <li class="wm-custom-filter__option<?php echo $checked ? ' is-active' : ''; ?><?php echo $has_children ? ' has-children' : ''; ?><?php echo $tree_open ? ' is-tree-open' : ''; ?><?php echo $unavailable ? ' is-unavailable' : ''; ?>" style="--wm-filter-depth:<?php echo esc_attr( (string) $depth ); ?>">
                <label>
                    <input type="checkbox" name="<?php echo esc_attr( 'product_cat' === $term->taxonomy ? $key . '[]' : $key ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" data-taxonomy="<?php echo esc_attr( $term->taxonomy ); ?>" data-filter-key="<?php echo esc_attr( $key ); ?>"<?php checked( $checked ); ?><?php disabled( $unavailable ); ?>>
                    <span><?php echo esc_html( $term->name ); ?></span>
                    <?php if ( ! empty( $config['show_counts'] ) ) : ?>
                        <small><?php echo esc_html( number_format_i18n( $term->count ) ); ?></small>
                    <?php endif; ?>
                </label>
                <?php if ( $has_children && ! empty( $config['hierarchy_toggle'] ) ) : ?>
                    <button class="wm-custom-filter__tree-toggle" type="button" aria-expanded="<?php echo esc_attr( $tree_open ? 'true' : 'false' ); ?>" aria-controls="wm-filter-tree-<?php echo esc_attr( $key . '-' . $term->term_id ); ?>" aria-label="<?php echo esc_attr__( 'نمایش زیرمجموعه‌ها', 'eshobe-ecommerce' ); ?>"></button>
                <?php endif; ?>
                <?php wm_product_archive_render_term_tree( $terms, $key, $selected, (int) $term->term_id, $depth + 1, $config ); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function wm_product_archive_filter_tax_query( $config ) {
    $tax_query = array();

    $queried = get_queried_object();
    if ( $queried instanceof WP_Term && taxonomy_exists( $queried->taxonomy ) ) {
        $taxonomy_object = get_taxonomy( $queried->taxonomy );
        if ( $taxonomy_object && in_array( 'product', (array) $taxonomy_object->object_type, true ) ) {
            $tax_query[] = array(
                'taxonomy'         => $queried->taxonomy,
                'field'            => 'term_id',
                'terms'            => array( (int) $queried->term_id ),
                'include_children' => true,
            );
        }
    }

    foreach ( $config['taxonomies'] as $filter ) {
        if ( ! taxonomy_exists( $filter['taxonomy'] ) ) {
            continue;
        }

        $values = wm_product_archive_taxonomy_filter_slugs( $filter['key'], $filter['taxonomy'] );
        if ( empty( $values ) ) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $filter['taxonomy'],
            'field'    => 'slug',
            'terms'    => $values,
            'operator' => 'IN',
        );
    }

    if ( 1 < count( $tax_query ) ) {
        $tax_query['relation'] = 'AND';
    }

    return $tax_query;
}

function wm_product_archive_request_tax_clauses( $config ) {
    $tax_query = array();

    foreach ( $config['taxonomies'] as $filter ) {
        if ( empty( $filter['key'] ) || empty( $filter['taxonomy'] ) || ! taxonomy_exists( $filter['taxonomy'] ) ) {
            continue;
        }
        if ( 'product_cat' === $filter['taxonomy'] ) {
            $queried = get_queried_object();
            if ( $queried instanceof WP_Term && 'product_cat' === $queried->taxonomy ) {
                // Already restricted by the main query on a category archive page.
                continue;
            }
        }

        $values = wm_product_archive_taxonomy_filter_slugs( $filter['key'], $filter['taxonomy'] );
        if ( empty( $values ) ) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $filter['taxonomy'],
            'field'    => 'slug',
            'terms'    => $values,
            'operator' => 'IN',
        );
    }

    return $tax_query;
}

function wm_product_archive_merge_tax_query( $existing_tax_query, $custom_tax_query ) {
    $existing_tax_query = array_filter( (array) $existing_tax_query );
    $custom_tax_query   = array_filter( (array) $custom_tax_query );

    if ( empty( $custom_tax_query ) ) {
        return $existing_tax_query;
    }

    $relation = isset( $existing_tax_query['relation'] ) ? $existing_tax_query['relation'] : 'AND';
    unset( $existing_tax_query['relation'] );

    $tax_query = array_values( array_merge( $existing_tax_query, $custom_tax_query ) );
    $seen      = array();
    $tax_query = array_values(
        array_filter(
            $tax_query,
            function( $clause ) use ( &$seen ) {
                if ( ! is_array( $clause ) || empty( $clause['taxonomy'] ) || empty( $clause['terms'] ) ) {
                    return true;
                }

                $signature = md5( wp_json_encode( array( $clause['taxonomy'], isset( $clause['field'] ) ? $clause['field'] : '', (array) $clause['terms'], isset( $clause['operator'] ) ? $clause['operator'] : '' ) ) );
                if ( isset( $seen[ $signature ] ) ) {
                    return false;
                }

                $seen[ $signature ] = true;
                return true;
            }
        )
    );
    if ( 1 < count( $tax_query ) ) {
        $tax_query['relation'] = $relation ? $relation : 'AND';
    }

    return $tax_query;
}

function wm_product_archive_debug_log( $message, $context = array() ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    error_log( '[WM FILTER] ' . $message . ( empty( $context ) ? '' : ' ' . wp_json_encode( $context ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

function wm_product_archive_resolve_filter_term( $taxonomy, $value ) {
    $value = trim( (string) $value );
    if ( '' === $value || ! taxonomy_exists( $taxonomy ) ) {
        return null;
    }

    // ⚡ Bolt: Cache resolved terms to prevent repeated get_term_by queries during filter building.
    static $cache = array();
    $cache_key = $taxonomy . '|' . $value;

    $is_test = defined( 'PHPUNIT_COMPOSER_INSTALL' ) || defined( 'WP_TESTS_DOMAIN' );
    if ( ! $is_test && array_key_exists( $cache_key, $cache ) ) {
        return $cache[ $cache_key ];
    }

    $term = null;
    if ( ctype_digit( $value ) ) {
        $term = get_term_by( 'id', (int) $value, $taxonomy );
        $result = $term && ! is_wp_error( $term ) ? $term : null;
        if ( ! $is_test ) {
            $cache[ $cache_key ] = $result;
        }
        return $result;
    }

    $candidates = array_unique(
        array_filter(
            array(
                $value,
                sanitize_title( $value ),
                rawurldecode( $value ),
                sanitize_title( rawurldecode( $value ) ),
            )
        )
    );

    foreach ( $candidates as $candidate ) {
        $term = get_term_by( 'slug', $candidate, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            if ( ! $is_test ) {
                $cache[ $cache_key ] = $term;
            }
            return $term;
        }
    }

    foreach ( $candidates as $candidate ) {
        $term = get_term_by( 'name', $candidate, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) {
            if ( ! $is_test ) {
                $cache[ $cache_key ] = $term;
            }
            return $term;
        }
    }

    if ( ! $is_test ) {
        $cache[ $cache_key ] = null;
    }
    return null;
}

function wm_product_archive_taxonomy_filter_slugs( $key, $taxonomy ) {
    $slugs = array();

    foreach ( wm_product_archive_get_taxonomy_filter_values( $key, $taxonomy ) as $value ) {
        $term = wm_product_archive_resolve_filter_term( $taxonomy, $value );
        if ( $term ) {
            $slugs[] = $term->slug;
        }
    }

    return array_values( array_unique( array_filter( $slugs ) ) );
}

/**
 * Product IDs matching the current archive's category context (queried term
 * and/or `filter_product_cat`), plus active price/stock/search filters, but
 * NOT narrowed by any taxonomy filter selections. Used to compute which
 * filter options are actually relevant to the current archive so unrelated
 * options (e.g. men's-only attributes while browsing women's watches) can be
 * hidden or disabled.
 */
function wm_product_archive_scope_product_ids() {
    $tax_query = array();

    $queried = get_queried_object();
    if ( $queried instanceof WP_Term && taxonomy_exists( $queried->taxonomy ) ) {
        $taxonomy_object = get_taxonomy( $queried->taxonomy );
        if ( $taxonomy_object && in_array( 'product', (array) $taxonomy_object->object_type, true ) ) {
            $tax_query[] = array(
                'taxonomy'         => $queried->taxonomy,
                'field'            => 'term_id',
                'terms'            => array( (int) $queried->term_id ),
                'include_children' => true,
            );
        }
    }

    if ( ! ( $queried instanceof WP_Term && 'product_cat' === $queried->taxonomy ) ) {
        $cat_slugs = wm_product_archive_taxonomy_filter_slugs( 'filter_product_cat', 'product_cat' );
        if ( ! empty( $cat_slugs ) ) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $cat_slugs,
                'operator' => 'IN',
            );
        }
    }

    if ( 1 < count( $tax_query ) ) {
        $tax_query['relation'] = 'AND';
    }

    $min_price = isset( $_GET['min_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['min_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $max_price = isset( $_GET['max_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['max_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( null !== $min_price && null !== $max_price && $min_price > $max_price ) {
        $swap      = $min_price;
        $min_price = $max_price;
        $max_price = $swap;
    }

    $stock = isset( $_GET['stock_status'] ) && in_array( $_GET['stock_status'], array( 'instock', 'outofstock' ), true ) ? wc_clean( wp_unslash( $_GET['stock_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $search = is_search() ? get_search_query() : '';

    // No category/price/stock/search context at all: every term is equally
    // "available", so skip the extra queries entirely (common case: the
    // unfiltered shop root).
    if ( empty( $tax_query ) && null === $min_price && null === $max_price && '' === $stock && '' === $search ) {
        return null;
    }

    $cache_key = 'wm_archive_scope_ids_' . md5( wp_json_encode( array( $tax_query, $min_price, $max_price, $stock, $search ) ) );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    // Single-flight guard: if another request is already building this set,
    // skip the expensive query for this request (availability filtering is
    // skipped for one request rather than stampeding the cache).
    $lock_key = $cache_key . '_lock';
    if ( get_transient( $lock_key ) ) {
        return null;
    }
    set_transient( $lock_key, 1, 30 );

    $query_args = array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'fields'                 => 'ids',
        'posts_per_page'         => apply_filters( 'wm_archive_scope_max_ids', 10000 ),
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'suppress_filters'       => false,
    );

    if ( ! empty( $tax_query ) ) {
        $query_args['tax_query'] = $tax_query;
    }

    if ( '' !== $search ) {
        $query_args['s'] = $search;
    }

    $meta_query = array();
    if ( null !== $min_price || null !== $max_price ) {
        $price_query = array(
            'key'  => '_price',
            'type' => 'NUMERIC',
        );

        if ( null !== $min_price && null !== $max_price ) {
            $price_query['value']   = array( $min_price, $max_price );
            $price_query['compare'] = 'BETWEEN';
        } elseif ( null !== $min_price ) {
            $price_query['value']   = $min_price;
            $price_query['compare'] = '>=';
        } else {
            $price_query['value']   = $max_price;
            $price_query['compare'] = '<=';
        }

        $meta_query[] = $price_query;
    }

    if ( '' !== $stock ) {
        $meta_query[] = array(
            'key'   => '_stock_status',
            'value' => $stock,
        );
    }

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    $product_ids = get_posts( $query_args );
    $product_ids = array_map( 'intval', $product_ids );

    delete_transient( $lock_key );
    set_transient( $cache_key, $product_ids, 15 * MINUTE_IN_SECONDS );

    return $product_ids;
}

/**
 * Product IDs matching the current archive's non-category filters (any
 * selected brand/attribute taxonomy filters, plus active price/stock/search
 * filters), but NOT narrowed by category. Used to determine which product
 * categories actually contain matching products given the other active
 * filters, so empty categories can be hidden/disabled.
 */
function wm_product_archive_category_scope_product_ids() {
    $tax_query = array();

    foreach ( wm_product_archive_filter_config()['taxonomies'] as $filter ) {
        if ( 'product_cat' === $filter['taxonomy'] ) {
            continue;
        }

        $slugs = wm_product_archive_taxonomy_filter_slugs( $filter['key'], $filter['taxonomy'] );
        if ( empty( $slugs ) ) {
            continue;
        }

        $tax_query[] = array(
            'taxonomy' => $filter['taxonomy'],
            'field'    => 'slug',
            'terms'    => $slugs,
            'operator' => 'IN',
        );
    }

    if ( 1 < count( $tax_query ) ) {
        $tax_query['relation'] = 'AND';
    }

    $min_price = isset( $_GET['min_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['min_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $max_price = isset( $_GET['max_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['max_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( null !== $min_price && null !== $max_price && $min_price > $max_price ) {
        $swap      = $min_price;
        $min_price = $max_price;
        $max_price = $swap;
    }

    $stock  = isset( $_GET['stock_status'] ) && in_array( $_GET['stock_status'], array( 'instock', 'outofstock' ), true ) ? wc_clean( wp_unslash( $_GET['stock_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $search = is_search() ? get_search_query() : '';

    // No taxonomy/price/stock/search context: every category is equally
    // "available", so skip the extra queries entirely.
    if ( empty( $tax_query ) && null === $min_price && null === $max_price && '' === $stock && '' === $search ) {
        return null;
    }

    $cache_key = 'wm_archive_cat_scope_ids_' . md5( wp_json_encode( array( $tax_query, $min_price, $max_price, $stock, $search ) ) );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    // Single-flight guard (see wm_product_archive_scope_product_ids).
    $lock_key = $cache_key . '_lock';
    if ( get_transient( $lock_key ) ) {
        return null;
    }
    set_transient( $lock_key, 1, 30 );

    $query_args = array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'fields'                 => 'ids',
        'posts_per_page'         => apply_filters( 'wm_archive_scope_max_ids', 10000 ),
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'suppress_filters'       => false,
    );

    if ( ! empty( $tax_query ) ) {
        $query_args['tax_query'] = $tax_query;
    }

    if ( '' !== $search ) {
        $query_args['s'] = $search;
    }

    $meta_query = array();
    if ( null !== $min_price || null !== $max_price ) {
        $price_query = array(
            'key'  => '_price',
            'type' => 'NUMERIC',
        );

        if ( null !== $min_price && null !== $max_price ) {
            $price_query['value']   = array( $min_price, $max_price );
            $price_query['compare'] = 'BETWEEN';
        } elseif ( null !== $min_price ) {
            $price_query['value']   = $min_price;
            $price_query['compare'] = '>=';
        } else {
            $price_query['value']   = $max_price;
            $price_query['compare'] = '<=';
        }

        $meta_query[] = $price_query;
    }

    if ( '' !== $stock ) {
        $meta_query[] = array(
            'key'   => '_stock_status',
            'value' => $stock,
        );
    }

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    $product_ids = get_posts( $query_args );
    $product_ids = array_map( 'intval', $product_ids );

    delete_transient( $lock_key );
    set_transient( $cache_key, $product_ids, 15 * MINUTE_IN_SECONDS );

    return $product_ids;
}

/**
 * Term IDs of a taxonomy that are actually attached to the given product IDs.
 */
function wm_product_archive_available_term_ids( $taxonomy, $product_ids ) {
    if ( empty( $product_ids ) || ! taxonomy_exists( $taxonomy ) ) {
        return array();
    }

    $cache_key = 'wm_archive_avail_terms_' . md5( $taxonomy . '|' . wp_json_encode( $product_ids ) );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $term_ids = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'object_ids' => $product_ids,
            'fields'     => 'ids',
        )
    );

    $result = is_wp_error( $term_ids ) ? array() : array_map( 'intval', $term_ids );

    set_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );

    return $result;
}

/**
 * Filter (or mark) a list of terms based on which are relevant to the
 * current archive scope. Selected terms (and their ancestors) are always
 * kept so the user can still see/remove their active selection.
 *
 * @return array Filtered/annotated list of WP_Term objects.
 */
function wm_product_archive_filter_terms_by_availability( $terms, $available_ids, $selected_slugs, $empty_behavior ) {
    if ( 'show' === $empty_behavior || empty( $available_ids ) ) {
        return $terms;
    }

    $available_ids = array_flip( $available_ids );
    $by_id         = array();
    foreach ( $terms as $term ) {
        $by_id[ (int) $term->term_id ] = $term;
    }

    $keep = array();
    $mark_with_ancestors = function( $term_id ) use ( &$keep, &$by_id, &$mark_with_ancestors ) {
        while ( $term_id && isset( $by_id[ $term_id ] ) && ! isset( $keep[ $term_id ] ) ) {
            $keep[ $term_id ] = true;
            $term_id          = (int) $by_id[ $term_id ]->parent;
        }
    };

    // ⚡ Bolt: Use a hash map (O(1) lookups) for selected slugs to prevent an O(n^2) bottleneck when filtering large term lists.
    $selected_map = array_flip( $selected_slugs );

    foreach ( $terms as $term ) {
        $term_id = (int) $term->term_id;
        if ( isset( $available_ids[ $term_id ] ) || isset( $selected_map[ $term->slug ] ) ) {
            $mark_with_ancestors( $term_id );
        }
    }

    if ( 'hide' === $empty_behavior ) {
        return array_values(
            array_filter(
                $terms,
                function( $term ) use ( $keep ) {
                    return isset( $keep[ (int) $term->term_id ] );
                }
            )
        );
    }

    // disable: keep every term but flag the unavailable ones for rendering.
    foreach ( $terms as $term ) {
        if ( ! isset( $keep[ (int) $term->term_id ] ) ) {
            $term->wm_unavailable = true;
        }
    }

    return $terms;
}

function wm_product_archive_normalize_price( $value ) {
    $value = strtr(
        (string) $value,
        array(
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        )
    );
    $value = preg_replace( '/[^\d.]/', '', $value );

    return '' !== $value && is_numeric( $value ) ? (float) $value : null;
}

function wm_product_archive_price_bounds( $config = null ) {
    $config = is_array( $config ) ? $config : wm_product_archive_filter_config();
    $scope  = array(
        'tax_query' => wm_product_archive_filter_tax_query( $config ),
        'search'    => is_search() ? get_search_query() : '',
        'stock'     => isset( $_GET['stock_status'] ) && in_array( $_GET['stock_status'], array( 'instock', 'outofstock' ), true ) ? wc_clean( wp_unslash( $_GET['stock_status'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    );
    $cache_key = 'wm_archive_price_bounds_' . md5( wp_json_encode( $scope ) );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) && isset( $cached['min'], $cached['max'] ) ) {
        return $cached;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'wc_product_meta_lookup';
    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    if ( $found !== $table ) {
        return array( 'min' => 0, 'max' => 0 );
    }

    $query_args = array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'fields'                 => 'ids',
        'posts_per_page'         => -1,
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'suppress_filters'       => false,
    );

    if ( ! empty( $scope['tax_query'] ) ) {
        $query_args['tax_query'] = $scope['tax_query'];
    }

    if ( '' !== $scope['search'] ) {
        $query_args['s'] = $scope['search'];
    }

    if ( '' !== $scope['stock'] ) {
        $query_args['meta_query'] = array(
            array(
                'key'   => '_stock_status',
                'value' => $scope['stock'],
            ),
        );
    }

    $product_ids = get_posts( $query_args );
    if ( empty( $product_ids ) ) {
        return array( 'min' => 0, 'max' => 0 );
    }

    $placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
    $bounds       = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT MIN(min_price) AS min_price, MAX(max_price) AS max_price FROM {$table} WHERE product_id IN ({$placeholders}) AND min_price IS NOT NULL AND max_price IS NOT NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $product_ids
        ),
        ARRAY_A
    ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $result = array(
        'min' => isset( $bounds['min_price'] ) ? max( 0, (int) floor( $bounds['min_price'] ) ) : 0,
        'max' => isset( $bounds['max_price'] ) ? max( 0, (int) ceil( $bounds['max_price'] ) ) : 0,
    );
    set_transient( $cache_key, $result, HOUR_IN_SECONDS );

    return $result;
}

function wm_product_archive_render_price_filter() {
    $min_price = isset( $_GET['min_price'] ) ? wc_clean( wp_unslash( $_GET['min_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $max_price = isset( $_GET['max_price'] ) ? wc_clean( wp_unslash( $_GET['max_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $config    = wm_product_archive_filter_config();
    $bounds    = wm_product_archive_price_bounds( $config );
    $min_bound = (int) $bounds['min'];
    $max_bound = max( $min_bound + 1, (int) $bounds['max'] );
    $step      = max( 1, (int) $config['price_step'] );
    $min_value = '' !== $min_price ? (int) preg_replace( '/[^\d]/', '', (string) $min_price ) : $min_bound;
    $max_value = '' !== $max_price ? (int) preg_replace( '/[^\d]/', '', (string) $max_price ) : $max_bound;
    $collapsed = 'closed' === $config['accordion_default'];
    ?>
    <section class="wm-archive-filter-widget wm-custom-filter wm-custom-filter--price<?php echo $collapsed ? ' is-collapsed' : ''; ?>">
        <h3 class="wm-archive-filter-widget__title"><?php echo esc_html__( 'محدوده قیمت', 'eshobe-ecommerce' ); ?></h3>
        <div class="wm-custom-filter__price-range" data-price-filter data-price-step="<?php echo esc_attr( (string) $step ); ?>">
            <div class="wm-custom-filter__price-track" aria-hidden="true"></div>
            <input type="range" min="<?php echo esc_attr( (string) $min_bound ); ?>" max="<?php echo esc_attr( (string) $max_bound ); ?>" step="<?php echo esc_attr( (string) $step ); ?>" value="<?php echo esc_attr( (string) $min_value ); ?>" aria-label="<?php echo esc_attr__( 'حداقل قیمت', 'eshobe-ecommerce' ); ?>" data-price-min-range>
            <input type="range" min="<?php echo esc_attr( (string) $min_bound ); ?>" max="<?php echo esc_attr( (string) $max_bound ); ?>" step="<?php echo esc_attr( (string) $step ); ?>" value="<?php echo esc_attr( (string) $max_value ); ?>" aria-label="<?php echo esc_attr__( 'حداکثر قیمت', 'eshobe-ecommerce' ); ?>" data-price-max-range>
        </div>
        <div class="wm-custom-filter__price-fields">
            <label>
                <span><?php echo esc_html__( 'از', 'eshobe-ecommerce' ); ?></span>
                <span class="wm-custom-filter__price-control">
                    <input type="text" inputmode="numeric" name="min_price" value="<?php echo esc_attr( '' !== $min_price ? number_format( $min_value ) : '' ); ?>" placeholder="<?php echo esc_attr( number_format( $min_bound ) ); ?>" aria-label="<?php echo esc_attr__( 'حداقل قیمت', 'eshobe-ecommerce' ); ?>" data-price-min-input>
                    <small><?php echo esc_html__( 'تومان', 'eshobe-ecommerce' ); ?></small>
                </span>
            </label>
            <label>
                <span><?php echo esc_html__( 'تا', 'eshobe-ecommerce' ); ?></span>
                <span class="wm-custom-filter__price-control">
                    <input type="text" inputmode="numeric" name="max_price" value="<?php echo esc_attr( '' !== $max_price ? number_format( $max_value ) : '' ); ?>" placeholder="<?php echo esc_attr( number_format( $max_bound ) ); ?>" aria-label="<?php echo esc_attr__( 'حداکثر قیمت', 'eshobe-ecommerce' ); ?>" data-price-max-input>
                    <small><?php echo esc_html__( 'تومان', 'eshobe-ecommerce' ); ?></small>
                </span>
            </label>
        </div>
    </section>
    <?php
}

function wm_product_archive_render_custom_filters() {
    $config = wm_product_archive_filter_config();
    if ( empty( $config['enabled'] ) ) {
        return;
    }
    ?>
    <form class="wm-custom-filters" method="get" action="<?php echo esc_url( wm_product_archive_reset_url() ); ?>" data-wm-custom-filters data-ajax-enabled="<?php echo esc_attr( ! empty( $config['ajax_enabled'] ) ? 'true' : 'false' ); ?>">
        <?php echo wp_nonce_field( 'wm_product_archive_filter', 'wm_archive_nonce', false, false ); ?>
        <?php if ( isset( $_GET['orderby'] ) && 'menu_order' !== $_GET['orderby'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
            <input type="hidden" name="orderby" value="<?php echo esc_attr( wc_clean( wp_unslash( $_GET['orderby'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">
        <?php endif; ?>
        <?php if ( is_search() && get_search_query() ) : ?>
            <input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
            <input type="hidden" name="post_type" value="product">
        <?php endif; ?>
        <?php if ( ! empty( $config['show_price'] ) ) : ?>
            <?php wm_product_archive_render_price_filter(); ?>
        <?php endif; ?>
        <?php foreach ( $config['taxonomies'] as $filter ) : ?>
            <?php wm_product_archive_render_tax_filter( $filter ); ?>
        <?php endforeach; ?>
        <button class="screen-reader-text" type="submit"><?php echo esc_html__( 'اعمال فیلترها', 'eshobe-ecommerce' ); ?></button>
    </form>
    <?php
}

function wm_apply_archive_category_filter( $query ) {
    if ( is_admin() ) {
        return;
    }

    $is_product_taxonomy = function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
    if ( function_exists( 'is_shop' ) && ! is_shop() && ! $is_product_taxonomy && ! is_post_type_archive( 'product' ) ) {
        return;
    }

    $cats = wm_get_csv_request_values( 'filter_product_cat' );
    if ( empty( $cats ) ) {
        wm_product_archive_debug_log(
            'category_filter_empty',
            array(
                'get'  => $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'cats' => $cats,
            )
        );
        return;
    }

    $before_tax_query = (array) $query->get( 'tax_query' );
    $tax_query        = $before_tax_query;
    $tax_query[]      = array(
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => $cats,
        'operator' => 'IN',
    );

    if ( 1 < count( $tax_query ) && ! isset( $tax_query['relation'] ) ) {
        $tax_query['relation'] = 'AND';
    }

    $query->set( 'tax_query', $tax_query );

    wm_product_archive_debug_log(
        'woocommerce_product_query_category',
        array(
            'get'        => $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'cats'       => $cats,
            'before_tax' => $before_tax_query,
            'after_tax'  => $query->get( 'tax_query' ),
        )
    );
}
add_action( 'woocommerce_product_query', 'wm_apply_archive_category_filter', 20 );

function wm_product_archive_debug_posts_request( $sql, $query ) {
    if ( empty( wm_get_csv_request_values( 'filter_product_cat' ) ) || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
        return $sql;
    }

    wm_product_archive_debug_log(
        'main_query_sql',
        array(
            'sql' => $sql,
        )
    );

    return $sql;
}
add_filter( 'posts_request', 'wm_product_archive_debug_posts_request', 20, 2 );

function wm_product_archive_apply_custom_filters( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! wm_product_archive_is_context() ) {
        return;
    }

    $config    = wm_product_archive_filter_config();
    if ( empty( $config['enabled'] ) ) {
        return;
    }

    $custom_tax_query = wm_product_archive_request_tax_clauses( $config );
    $tax_query        = wm_product_archive_merge_tax_query( $query->get( 'tax_query' ), $custom_tax_query );

    if ( ! empty( $tax_query ) ) {
        $query->set( 'tax_query', $tax_query );
    }

    wm_product_archive_debug_log(
        'pre_get_posts',
        array(
            'get'       => $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'custom'    => $custom_tax_query,
            'tax_query' => $tax_query,
        )
    );

    $meta_query = (array) $query->get( 'meta_query' );
    $min_price  = isset( $_GET['min_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['min_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $max_price  = isset( $_GET['max_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['max_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( null !== $min_price && null !== $max_price && $min_price > $max_price ) {
        $swap      = $min_price;
        $min_price = $max_price;
        $max_price = $swap;
    }

    if ( null !== $min_price || null !== $max_price ) {
        $price_query = array(
            'key'     => '_price',
            'type'    => 'NUMERIC',
        );

        if ( null !== $min_price && null !== $max_price ) {
            $price_query['value']   = array( $min_price, $max_price );
            $price_query['compare'] = 'BETWEEN';
        } elseif ( null !== $min_price ) {
            $price_query['value']   = $min_price;
            $price_query['compare'] = '>=';
        } else {
            $price_query['value']   = $max_price;
            $price_query['compare'] = '<=';
        }

        $meta_query[] = $price_query;
    }

    if ( isset( $_GET['stock_status'] ) && in_array( $_GET['stock_status'], array( 'instock', 'outofstock' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $meta_query[] = array(
            'key'   => '_stock_status',
            'value' => wc_clean( wp_unslash( $_GET['stock_status'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        );
    }

    if ( ! empty( $meta_query ) ) {
        $query->set( 'meta_query', $meta_query );
    }
}
add_action( 'pre_get_posts', 'wm_product_archive_apply_custom_filters', 30 );

function wm_product_archive_woocommerce_tax_query( $tax_query ) {
    if ( is_admin() || ! wm_product_archive_is_context() ) {
        return $tax_query;
    }

    $config = wm_product_archive_filter_config();
    if ( empty( $config['enabled'] ) ) {
        return $tax_query;
    }

    $custom_tax_query = wm_product_archive_request_tax_clauses( $config );
    $merged_tax_query = wm_product_archive_merge_tax_query( $tax_query, $custom_tax_query );

    wm_product_archive_debug_log(
        'woocommerce_product_query_tax_query',
        array(
            'get'       => $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'custom'    => $custom_tax_query,
            'tax_query' => $merged_tax_query,
        )
    );

    return $merged_tax_query;
}
add_filter( 'woocommerce_product_query_tax_query', 'wm_product_archive_woocommerce_tax_query', 30 );

function wm_product_archive_classes() {
    $classes = array(
        'wm-product-archive',
        'wm-section-decor',
        'wm-section-decor--soft-grid',
        'wm-product-archive--sidebar-' . wm_product_archive_choice_option( 'wm_archive_filter_sidebar_position', array( 'right', 'left' ), 'right' ),
        'wm-product-archive--filters-' . wm_product_archive_choice_option( 'wm_archive_sidebar_default_state', array( 'open', 'closed' ), 'open' ),
    );

    if ( ! wm_product_archive_sidebar_enabled() ) {
        $classes[] = 'wm-product-archive--no-sidebar';
    }

    return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

function wm_product_archive_css_vars() {
    $desktop = wm_product_archive_int_option( 'wm_archive_columns_desktop', 3, 2, 4 );
    $tablet  = wm_product_archive_int_option( 'wm_archive_columns_tablet', 2, 1, 3 );
    $mobile  = wm_product_archive_int_option( 'wm_archive_columns_mobile', 2, 1, 2 );

    return sprintf(
        '--wm-archive-columns-desktop:%d;--wm-archive-columns-tablet:%d;--wm-archive-columns-mobile:%d;',
        $desktop,
        $tablet,
        $mobile
    );
}

function wm_product_archive_title() {
    if ( is_search() ) {
        return sprintf(
            /* translators: %s: search query. */
            esc_html__( 'نتیجه جستجو برای «%s»', 'eshobe-ecommerce' ),
            get_search_query()
        );
    }

    return function_exists( 'woocommerce_page_title' ) ? woocommerce_page_title( false ) : get_the_archive_title();
}

function wm_product_archive_description() {
    if ( ! wm_product_archive_bool_option( 'wm_archive_show_archive_description', true ) ) {
        return '';
    }

    ob_start();
    if ( is_product_taxonomy() ) {
        woocommerce_taxonomy_archive_description();
    } elseif ( is_shop() || is_post_type_archive( 'product' ) ) {
        woocommerce_product_archive_description();
    }

    return trim( ob_get_clean() );
}

function wm_product_archive_reset_url() {
    if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_permalink' ) ) {
        return wc_get_page_permalink( 'shop' );
    }

    $queried = get_queried_object();
    if ( $queried instanceof WP_Term ) {
        $term_link = get_term_link( $queried );
        if ( ! is_wp_error( $term_link ) ) {
            return $term_link;
        }
    }

    if ( is_search() ) {
        return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
    }

    $archive = get_post_type_archive_link( 'product' );
    return $archive ? $archive : home_url( '/' );
}

function wm_product_archive_filter_label( $key ) {
    $labels = array(
        'min_price'     => esc_html__( 'حداقل قیمت', 'eshobe-ecommerce' ),
        'max_price'     => esc_html__( 'حداکثر قیمت', 'eshobe-ecommerce' ),
        'rating_filter' => esc_html__( 'امتیاز', 'eshobe-ecommerce' ),
        'product_cat'   => esc_html__( 'دسته‌بندی', 'eshobe-ecommerce' ),
        'filter_product_cat' => esc_html__( 'دسته‌بندی', 'eshobe-ecommerce' ),
        'product_tag'   => esc_html__( 'برچسب', 'eshobe-ecommerce' ),
        'filter_brand'  => esc_html__( 'برند', 'eshobe-ecommerce' ),
        'orderby'       => esc_html__( 'مرتب‌سازی', 'eshobe-ecommerce' ),
        'strap_material' => esc_html__( 'جنس بند', 'eshobe-ecommerce' ),
        'strap-material' => esc_html__( 'جنس بند', 'eshobe-ecommerce' ),
        'filter_strap_material' => esc_html__( 'جنس بند', 'eshobe-ecommerce' ),
        'filter_strap-material' => esc_html__( 'جنس بند', 'eshobe-ecommerce' ),
    );

    if ( isset( $labels[ $key ] ) ) {
        return $labels[ $key ];
    }

    if ( 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'pa_' ) ) {
        $suffix   = 0 === strpos( $key, 'filter_' ) ? substr( $key, 7 ) : $key;
        $taxonomy = wm_product_archive_filter_taxonomy( $suffix );

        if ( taxonomy_exists( $taxonomy ) ) {
            return function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $taxonomy ) : get_taxonomy( $taxonomy )->labels->singular_name;
        }

        return ucwords( str_replace( array( '-', '_' ), ' ', $suffix ) );
    }

    $taxonomy = wm_product_archive_filter_taxonomy( $key );
    if ( taxonomy_exists( $taxonomy ) ) {
        return function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $taxonomy ) : get_taxonomy( $taxonomy )->labels->singular_name;
    }

    return wm_product_archive_humanize_filter_text( $key );
}

function wm_product_archive_humanize_filter_text( $value ) {
    $value = trim( rawurldecode( (string) $value ) );
    $value = preg_replace( '/\s+/', ' ', str_replace( array( '-', '_' ), ' ', $value ) );

    return $value ? ucwords( $value ) : '';
}

function wm_product_archive_filter_taxonomy( $suffix ) {
    $suffix = trim( rawurldecode( (string) $suffix ) );
    if ( '' === $suffix ) {
        return '';
    }

    $base       = 0 === strpos( $suffix, 'filter_' ) ? substr( $suffix, 7 ) : $suffix;
    $sanitized  = sanitize_title( $base );
    $normalized = str_replace( '_', '-', $sanitized );
    $underscored = str_replace( '-', '_', $sanitized );
    $candidates = array_filter(
        array(
            $base,
            $sanitized,
            $normalized,
            $underscored,
        )
    );

    foreach ( array( $base, $sanitized, $normalized, $underscored ) as $candidate ) {
        if ( ! $candidate ) {
            continue;
        }

        if ( 0 === strpos( $candidate, 'pa_' ) ) {
            $candidates[] = substr( $candidate, 3 );
        } else {
            $candidates[] = 'pa_' . $candidate;
        }

        if ( function_exists( 'wc_attribute_taxonomy_name' ) ) {
            $candidates[] = wc_attribute_taxonomy_name( preg_replace( '/^pa_/', '', $candidate ) );
        }
    }

    foreach ( array_unique( $candidates ) as $taxonomy ) {
        if ( taxonomy_exists( $taxonomy ) ) {
            return $taxonomy;
        }
    }

    return $candidates[0];
}

function wm_product_archive_filter_value_label( $key, $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    $taxonomy = '';
    if ( 'product_cat' === $key || 'product_tag' === $key ) {
        $taxonomy = $key;
    } elseif ( 'filter_brand' === $key ) {
        foreach ( array( 'product_brand', 'pa_brand', 'brand' ) as $brand_taxonomy ) {
            if ( taxonomy_exists( $brand_taxonomy ) ) {
                $taxonomy = $brand_taxonomy;
                break;
            }
        }
    } elseif ( 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'pa_' ) ) {
        $suffix   = 0 === strpos( $key, 'filter_' ) ? substr( $key, 7 ) : $key;
        $taxonomy = wm_product_archive_filter_taxonomy( $suffix );
    }

    if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
        $term = wm_product_archive_resolve_filter_term( $taxonomy, $value );
        if ( $term ) {
            return $term->name;
        }
    }

    if ( is_numeric( $value ) && function_exists( 'wc_price' ) && in_array( $key, array( 'min_price', 'max_price' ), true ) ) {
        return wp_strip_all_tags( wc_price( (float) $value ) );
    }

    if ( 'orderby' === $key ) {
        $order_labels = array(
            'menu_order' => esc_html__( 'مرتبط‌ترین', 'eshobe-ecommerce' ),
            'popularity' => esc_html__( 'پرفروش‌ترین', 'eshobe-ecommerce' ),
            'date'       => esc_html__( 'جدیدترین', 'eshobe-ecommerce' ),
            'price'      => esc_html__( 'ارزان‌ترین', 'eshobe-ecommerce' ),
            'price-desc' => esc_html__( 'گران‌ترین', 'eshobe-ecommerce' ),
            'rating'     => esc_html__( 'پیشنهاد خریداران', 'eshobe-ecommerce' ),
        );

        if ( isset( $order_labels[ $value ] ) ) {
            return $order_labels[ $value ];
        }
    }

    return wm_product_archive_humanize_filter_text( $value );
}

function wm_product_archive_is_filter_query_key( $key ) {
    if ( '' === $key ) {
        return false;
    }

    if ( in_array( $key, wm_product_archive_internal_query_keys(), true ) || 0 === strpos( $key, 'query_type_' ) || 0 === strpos( $key, 'utm_' ) ) {
        return false;
    }

    if (
        0 === strpos( $key, 'filter_' )
        || 0 === strpos( $key, 'pa_' )
        || in_array( $key, array( 'min_price', 'max_price', 'rating_filter', 'product_cat', 'product_tag', 'orderby' ), true )
    ) {
        return true;
    }

    if ( taxonomy_exists( $key ) || taxonomy_exists( 'pa_' . $key ) || taxonomy_exists( wm_product_archive_filter_taxonomy( $key ) ) ) {
        return true;
    }

    if ( isset( $_GET[ 'query_type_' . $key ] ) || isset( $_GET[ 'query_type_pa_' . $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return true;
    }

    return ! in_array( $key, array( 'fbclid', 'gclid', 'msclkid' ), true );
}

function wm_product_archive_internal_query_keys() {
    return array(
        'paged',
        'product-page',
        'add-to-cart',
        's',
        'post_type',
        'action',
        'ajax',
        'nonce',
        '_wpnonce',
        'security',
        'wc-ajax',
        'yith_wcan',
        'yith_wcan_redirect',
        'yith_wcan_ajax',
    );
}

function wm_product_archive_query_values( $raw_value ) {
    $values    = array();
    $raw_values = (array) wp_unslash( $raw_value );

    array_walk_recursive(
        $raw_values,
        function( $raw_item ) use ( &$values ) {
            foreach ( explode( ',', (string) $raw_item ) as $value ) {
                $value = sanitize_title( trim( $value ) );
                if ( '' !== $value ) {
                    $values[] = $value;
                }
            }
        }
    );

    return array_values( array_unique( $values ) );
}

function wm_product_archive_related_query_type_keys( $key ) {
    $keys   = array( 'paged' );
    $suffix = 0 === strpos( $key, 'filter_' ) ? substr( $key, 7 ) : $key;

    if ( in_array( $key, array( 'filter_product_cat', 'product_cat' ), true ) ) {
        $keys[] = 'filter_product_cat';
        $keys[] = 'product_cat';
    }

    foreach ( array( $key, $suffix ) as $candidate ) {
        if ( ! $candidate ) {
            continue;
        }

        $keys[] = 'query_type_' . $candidate;

        if ( 0 === strpos( $candidate, 'pa_' ) ) {
            $keys[] = 'query_type_' . substr( $candidate, 3 );
        } else {
            $keys[] = 'query_type_pa_' . $candidate;
        }
    }

    return array_values( array_unique( $keys ) );
}

function wm_product_archive_active_filter_items() {
    $items = array();
    $url   = function_exists( 'get_pagenum_link' ) ? get_pagenum_link( 1, false ) : home_url( add_query_arg( array() ) );
    $config = wm_product_archive_filter_config();
    $min_price = isset( $_GET['min_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['min_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $max_price = isset( $_GET['max_price'] ) ? wm_product_archive_normalize_price( wp_unslash( $_GET['max_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( null !== $min_price || null !== $max_price ) {
        if ( null !== $min_price && null !== $max_price && $min_price > $max_price ) {
            $swap      = $min_price;
            $min_price = $max_price;
            $max_price = $swap;
        }

        if ( null !== $min_price && null !== $max_price ) {
            $price_value = sprintf(
                /* translators: 1: minimum price, 2: maximum price. */
                esc_html__( '%1$s تا %2$s تومان', 'eshobe-ecommerce' ),
                number_format_i18n( $min_price ),
                number_format_i18n( $max_price )
            );
        } elseif ( null !== $min_price ) {
            $price_value = sprintf(
                /* translators: %s: minimum price. */
                esc_html__( 'از %s تومان', 'eshobe-ecommerce' ),
                number_format_i18n( $min_price )
            );
        } else {
            $price_value = sprintf(
                /* translators: %s: maximum price. */
                esc_html__( 'تا %s تومان', 'eshobe-ecommerce' ),
                number_format_i18n( $max_price )
            );
        }

        $items[] = array(
            'key'       => 'price',
            'value_raw' => '',
            'label'     => esc_html__( 'قیمت', 'eshobe-ecommerce' ),
            'value'     => $price_value,
            'url'       => remove_query_arg( array( 'min_price', 'max_price', 'paged' ), $url ),
        );
    }

    foreach ( $config['taxonomies'] as $filter ) {
        if ( empty( $filter['key'] ) || empty( $filter['taxonomy'] ) || ! taxonomy_exists( $filter['taxonomy'] ) ) {
            continue;
        }
        $key    = $filter['key'];
        $values = wm_product_archive_get_taxonomy_filter_values( $key, $filter['taxonomy'] );
        if ( empty( $values ) ) {
            continue;
        }

        foreach ( $values as $value ) {
            $term = wm_product_archive_resolve_filter_term( $filter['taxonomy'], $value );
            if ( ! $term ) {
                wm_product_archive_debug_log(
                    'active_filter_term_not_found',
                    array(
                        'key'      => $key,
                        'taxonomy' => $filter['taxonomy'],
                        'value'    => $value,
                    )
                );
                continue;
            }

            $remove_keys = array_merge( wm_product_archive_related_query_type_keys( $key ), wm_product_archive_filter_key_aliases( $key, $filter['taxonomy'] ) );
            $next_values = array_values( array_diff( $values, array( $value, (string) $term->term_id, $term->slug, $term->name ) ) );
            $base_url    = remove_query_arg( $remove_keys, $url );
            $remove_url  = empty( $next_values ) ? $base_url : add_query_arg( $key, implode( ',', array_unique( $next_values ) ), $base_url );

            $items[] = array(
                'key'       => $key,
                'value_raw' => $value,
                'label'     => $filter['label'],
                'value'     => $term->name,
                'url'       => $remove_url,
            );
        }
    }

    if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $orderby = wc_clean( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( '' !== $orderby && 'menu_order' !== $orderby ) {
            $items[] = array(
                'key'       => 'orderby',
                'value_raw' => $orderby,
                'label'     => wm_product_archive_filter_label( 'orderby' ),
                'value'     => wm_product_archive_filter_value_label( 'orderby', $orderby ),
                'url'       => remove_query_arg( array( 'orderby', 'paged' ), $url ),
            );
        }
    }

    return $items;
}

function wm_product_archive_render_active_filters() {
    $items = wm_product_archive_active_filter_items();
    ?>
    <div class="wm-active-filters-region">
    <div class="wm-active-filters<?php echo empty( $items ) ? ' is-empty' : ''; ?>" aria-label="<?php echo esc_attr__( 'فیلترهای فعال', 'eshobe-ecommerce' ); ?>">
        <?php if ( empty( $items ) ) : ?>
            <span class="screen-reader-text"><?php echo esc_html__( 'فیلتر فعالی وجود ندارد.', 'eshobe-ecommerce' ); ?></span>
        <?php else : ?>
        <span class="wm-active-filters__label"><?php echo esc_html__( 'فیلترهای فعال', 'eshobe-ecommerce' ); ?></span>
        <div class="wm-active-filters__list">
            <?php foreach ( $items as $item ) : ?>
                <a class="wm-active-filters__chip" href="<?php echo esc_url( $item['url'] ); ?>" data-filter-key="<?php echo esc_attr( $item['key'] ); ?>" data-filter-value="<?php echo esc_attr( $item['value_raw'] ); ?>">
                    <span><?php echo esc_html( $item['label'] . ': ' . $item['value'] ); ?></span>
                    <span class="wm-active-filters__remove" aria-hidden="true">×</span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    </div>
    <?php
}

function wm_product_archive_render_header() {
    $description = wm_product_archive_description();
    ?>
    <header class="wm-product-archive__header">
        <?php woocommerce_breadcrumb(); ?>
        <h1 class="wm-product-archive__title"><?php echo esc_html( wm_product_archive_title() ); ?></h1>
        <?php if ( $description ) : ?>
            <div class="wm-product-archive__description"><?php echo wp_kses_post( $description ); ?></div>
        <?php endif; ?>
    </header>
    <?php
}

function wm_product_archive_render_sidebar() {
    if ( ! wm_product_archive_sidebar_enabled() ) {
        return;
    }
    ?>
    <aside class="wm-product-archive__sidebar" id="wm-product-archive-sidebar" data-product-archive-sidebar>
        <div class="wm-product-archive__sidebar-panel">
            <div class="wm-product-archive__sidebar-header">
                <strong><?php echo esc_html__( 'فیلترها', 'eshobe-ecommerce' ); ?></strong>
                <a class="wm-product-archive__reset" href="<?php echo esc_url( wm_product_archive_reset_url() ); ?>"><?php echo esc_html__( 'حذف فیلترها', 'eshobe-ecommerce' ); ?></a>
                <button class="wm-product-archive__sidebar-close" type="button" data-archive-filter-close aria-label="<?php echo esc_attr__( 'بستن فیلترها', 'eshobe-ecommerce' ); ?>">×</button>
            </div>
            <div class="wm-product-archive__filters">
                <?php wm_product_archive_render_custom_filters(); ?>
            </div>
        </div>
    </aside>
    <?php
}

function wm_product_archive_result_count() {
    if ( ! wm_product_archive_bool_option( 'wm_archive_show_result_count', true ) ) {
        return;
    }
    global $wp_query;

    $total    = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
    $per_page = max( 1, (int) $wp_query->get( 'posts_per_page' ) );
    $current  = max( 1, (int) get_query_var( 'paged' ) );
    $first    = 0 < $total ? ( $per_page * ( $current - 1 ) ) + 1 : 0;
    $last     = min( $total, $per_page * $current );
    $label    = 0 < $total && $total <= $per_page
        ? sprintf( esc_html__( '%s کالا', 'eshobe-ecommerce' ), number_format_i18n( $total ) )
        : sprintf(
            /* translators: 1: first product number, 2: last product number, 3: total products. */
            esc_html__( 'نمایش %1$s–%2$s از %3$s نتیجه', 'eshobe-ecommerce' ),
            number_format_i18n( $first ),
            number_format_i18n( $last ),
            number_format_i18n( $total )
        );
    ?>
    <div class="wm-product-archive__result-count">
        <span><?php echo esc_html( $label ); ?></span>
    </div>
    <?php
}

function wm_product_archive_ordering() {
    if ( ! wm_product_archive_bool_option( 'wm_archive_show_ordering', true ) ) {
        return;
    }

    $current_orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $sort_links      = array(
        'menu_order' => esc_html__( 'مرتبط‌ترین', 'eshobe-ecommerce' ),
        'popularity' => esc_html__( 'پرفروش‌ترین', 'eshobe-ecommerce' ),
        'date'       => esc_html__( 'جدیدترین', 'eshobe-ecommerce' ),
        'price'      => esc_html__( 'ارزان‌ترین', 'eshobe-ecommerce' ),
        'price-desc' => esc_html__( 'گران‌ترین', 'eshobe-ecommerce' ),
        'rating'     => esc_html__( 'پیشنهاد خریداران', 'eshobe-ecommerce' ),
    );
    ?>
    <div class="wm-product-archive__ordering">
        <span class="wm-product-archive__ordering-label"><?php echo esc_html__( 'مرتب‌سازی محصولات', 'eshobe-ecommerce' ); ?></span>
        <div class="wm-product-archive__sort-pills" aria-label="<?php echo esc_attr__( 'گزینه‌های مرتب‌سازی', 'eshobe-ecommerce' ); ?>">
            <?php foreach ( $sort_links as $orderby => $label ) : ?>
                <?php
                $url     = remove_query_arg( 'paged', add_query_arg( 'orderby', $orderby ) );
                $current = $orderby === $current_orderby;
                ?>
                <a class="wm-product-archive__sort-pill<?php echo $current ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $current ? ' aria-current="true"' : ''; ?>>
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function wm_product_archive_render_toolbar() {
    ?>
    <?php if ( wm_product_archive_sidebar_enabled() ) : ?>
        <div class="wm-product-archive__mobile-actions">
            <button class="wm-product-archive__filter-button" type="button" data-archive-filter-toggle aria-expanded="true" aria-controls="wm-product-archive-sidebar">
                <span aria-hidden="true">☰</span>
                <?php echo esc_html__( 'فیلترها', 'eshobe-ecommerce' ); ?>
            </button>
        </div>
    <?php endif; ?>
    <div class="wm-product-archive__toolbar wm-shop-toolbar">
        <div class="wm-product-archive__toolbar-start">
            <?php wm_product_archive_ordering(); ?>
        </div>
        <div class="wm-product-archive__toolbar-end">
            <?php wm_product_archive_result_count(); ?>
        </div>
    </div>
    <?php wm_product_archive_render_active_filters(); ?>
    <?php
}

function wm_product_archive_render_loop() {
    // Above-the-fold row gets fetchpriority=high; everything below stays lazy.
    $first_row = wm_product_archive_int_option( 'wm_archive_columns_desktop', 3, 1, 4 );
    $index     = 0;
    ?>
    <div class="wm-product-archive__grid wm-products-loop">
        <?php while ( have_posts() ) : ?>
            <?php
            the_post();
            global $product;
            if ( ! $product instanceof WC_Product ) {
                $product = wc_get_product( get_the_ID() );
            }
            if ( $product instanceof WC_Product ) {
                echo wm_render_archive_product_card( $product, $index < $first_row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $index++;
            }
            ?>
        <?php endwhile; ?>
    </div>
    <?php
}

function wm_render_archive_product_card( WC_Product $product, $high_priority = false ) {
    $args = array(
        'class'              => 'wm-product-carousel__item',
        'enable_hover_image' => wm_product_archive_bool_option( 'wm_archive_enable_card_hover_image', true ),
        'ajax_add_to_cart'   => wm_product_archive_bool_option( 'wm_archive_enable_ajax_add_to_cart', true ),
    );

    if ( $high_priority ) {
        $args['fetchpriority'] = 'high';
    }

    return wm_render_product_card( $product, $args );
}

function wm_product_archive_render_pagination() {
    global $wp_query;

    $total = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0;
    if ( $total <= 1 ) {
        return;
    }

    $current = max( 1, (int) get_query_var( 'paged' ) );
    $links   = paginate_links(
        array(
            'base'      => esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) ),
            'format'    => '',
            'current'   => $current,
            'total'     => $total,
            'type'      => 'array',
            'end_size'  => 1,
            'mid_size'  => 1,
            'prev_next' => true,
            'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'صفحه قبلی', 'eshobe-ecommerce' ) . '</span><span class="wm-shop-pagination__arrow wm-shop-pagination__arrow--prev" aria-hidden="true"></span>',
            'next_text' => '<span class="screen-reader-text">' . esc_html__( 'صفحه بعدی', 'eshobe-ecommerce' ) . '</span><span class="wm-shop-pagination__arrow wm-shop-pagination__arrow--next" aria-hidden="true"></span>',
        )
    );

    if ( empty( $links ) || ! is_array( $links ) ) {
        return;
    }
    ?>
    <nav class="wm-shop-pagination" aria-label="<?php echo esc_attr__( 'صفحه‌بندی محصولات', 'eshobe-ecommerce' ); ?>">
        <ul class="wm-shop-pagination__list">
            <?php foreach ( $links as $link ) : ?>
                <?php
                $item_classes = array( 'wm-shop-pagination__item' );
                if ( false !== strpos( $link, 'current' ) ) {
                    $item_classes[] = 'is-current';
                }
                if ( false !== strpos( $link, 'dots' ) ) {
                    $item_classes[] = 'is-dots';
                }
                if ( false !== strpos( $link, 'prev' ) ) {
                    $item_classes[] = 'is-prev';
                }
                if ( false !== strpos( $link, 'next' ) ) {
                    $item_classes[] = 'is-next';
                }

                $link = str_replace( 'page-numbers', 'wm-shop-pagination__link', $link );
                ?>
                <li class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>">
                    <?php echo wp_kses_post( $link ); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
}

function wm_product_archive_render_empty() {
    $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
    ?>
    <section class="wm-product-archive__empty">
        <h2><?php echo esc_html__( 'محصولی پیدا نشد.', 'eshobe-ecommerce' ); ?></h2>
        <p><?php echo esc_html__( 'فیلترها را تغییر دهید یا به فروشگاه برگردید.', 'eshobe-ecommerce' ); ?></p>
        <a class="wm-product-archive__empty-button" href="<?php echo esc_url( $shop_url ); ?>"><?php echo esc_html__( 'مشاهده همه محصولات', 'eshobe-ecommerce' ); ?></a>
    </section>
    <?php
}
