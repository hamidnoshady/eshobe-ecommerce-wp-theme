<?php
/**
 * Home page data helpers.
 *
 * @package WM_Theme
 */

function wm_home_get_option( $key, $default = '' ) {
	return wm_get_option( $key, $default );
}

/**
 * URL of the first rendered hero slide's desktop image (LCP preload target).
 *
 * Mirrors the slide merge order in template-parts/home/hero-slider.php
 * (content slides, then image slides, then video slides) so the preload link
 * in <head> points at the same image the first slide actually renders.
 *
 * @return string
 */
function wm_home_first_hero_image_url() {
	$content_slides = wm_home_get_valid_items(
		'home_hero_slides',
		function( $slide ) {
			return ! empty( $slide['slide_enabled'] ) && ( ! empty( $slide['slide_title'] ) || ! empty( $slide['slide_image_desktop'] ) );
		}
	);

	if ( ! empty( $content_slides[0]['slide_image_desktop'] ) ) {
		return wm_home_get_image_url( $content_slides[0]['slide_image_desktop'], 'large' );
	}

	$image_slides = wm_home_get_valid_items(
		'home_hero_image_slides',
		function( $slide ) {
			return ! empty( $slide['image_slide_enabled'] ) && ( ! empty( $slide['image_slide_image_desktop'] ) || ! empty( $slide['image_slide_image_mobile'] ) );
		}
	);

	if ( ! empty( $image_slides[0]['image_slide_image_desktop'] ) ) {
		return wm_home_get_image_url( $image_slides[0]['image_slide_image_desktop'], 'full' );
	}

	return '';
}

/**
 * Transient key for the assembled front-page sections output.
 *
 * The front page runs several `wc_get_products` and term queries per request
 * (bestsellers, recommended, brand/style cards). The rendered sections are
 * cached here for a short TTL and flushed whenever products or the ACF
 * options that feed them change.
 *
 * @return string
 */
function wm_home_cache_key() {
	return 'wm_home_sections_output_v1';
}

/**
 * Flush the front-page sections cache.
 */
function wm_home_cache_flush() {
	delete_transient( wm_home_cache_key() );
}

// Invalidate on product lifecycle changes (create/update/trash/untrash) and
// on any ACF save (options pages + product meta) so edits surface promptly.
add_action( 'save_post_product', 'wm_home_cache_flush' );
add_action( 'woocommerce_update_product', 'wm_home_cache_flush' );
add_action( 'trashed_post', 'wm_home_cache_flush' );
add_action( 'untrashed_post', 'wm_home_cache_flush' );
add_action( 'acf/save_post', 'wm_home_cache_flush' );

/**
 * Gets a home option and filters its items using a callback.
 *
 * @param string   $key      The option key.
 * @param callable $callback The callback function to use for filtering.
 * @return array The filtered items.
 */
function wm_home_get_valid_items( $key, $callback ) {
    $items = wm_home_get_option( $key, array() );

    if ( ! is_array( $items ) || empty( $items ) ) {
        return array();
    }

    return array_values( array_filter( $items, $callback ) );
}


function wm_home_enabled( $key, $default = true ) {
    $value = wm_home_get_option( $key, null );
    return null === $value ? $default : (bool) $value;
}

function wm_home_get_image_url( $image, $size = 'large' ) {
    if ( empty( $image ) ) {
        return '';
    }

    if ( is_array( $image ) ) {
        if ( ! empty( $image['sizes'][ $size ] ) ) {
            return $image['sizes'][ $size ];
        }
        return ! empty( $image['url'] ) ? $image['url'] : '';
    }

    if ( is_numeric( $image ) ) {
        return wp_get_attachment_image_url( absint( $image ), $size );
    }

    return is_string( $image ) ? $image : '';
}

function wm_home_get_image_html( $image, $size = 'large', $attrs = array() ) {
    if ( empty( $image ) ) {
        return '';
    }

    $attrs = wp_parse_args(
        $attrs,
        array(
            'loading' => 'lazy',
        )
    );

    if ( is_array( $image ) && ! empty( $image['ID'] ) ) {
        return wp_get_attachment_image( absint( $image['ID'] ), $size, false, $attrs );
    }

    if ( is_numeric( $image ) ) {
        return wp_get_attachment_image( absint( $image ), $size, false, $attrs );
    }

    $url = wm_home_get_image_url( $image, $size );
    if ( ! $url ) {
        return '';
    }

    $alt     = isset( $attrs['alt'] ) ? $attrs['alt'] : '';
    $loading = isset( $attrs['loading'] ) ? $attrs['loading'] : 'lazy';
    return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="' . esc_attr( $loading ) . '" decoding="async">';
}

function wm_home_get_video_url( $video ) {
    if ( empty( $video ) ) {
        return '';
    }

    if ( is_array( $video ) ) {
        return ! empty( $video['url'] ) ? $video['url'] : '';
    }

    if ( is_numeric( $video ) ) {
        return wp_get_attachment_url( absint( $video ) );
    }

    return is_string( $video ) ? $video : '';
}

function wm_home_get_video_type( $video ) {
    if ( is_array( $video ) && ! empty( $video['mime_type'] ) ) {
        return $video['mime_type'];
    }

    $url = wm_home_get_video_url( $video );
    $ext = strtolower( pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

    $types = array(
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'ogv'  => 'video/ogg',
        'ogg'  => 'video/ogg',
    );

    return isset( $types[ $ext ] ) ? $types[ $ext ] : '';
}

function wm_home_default_sections() {
    return array(
        'hero_slider'          => array( 'label' => 'Hero Slider', 'template' => 'hero-slider' ),
        'brand_categories'     => array( 'label' => 'برندها', 'template' => 'brand-categories' ),
        'recommended_products' => array( 'label' => 'پیشنهاد ما', 'template' => 'recommended-products' ),
        'filter_boxes'         => array( 'label' => 'باکس‌های فیلتر', 'template' => 'filter-boxes' ),
        'bestsellers'          => array( 'label' => 'پرفروش‌ها', 'template' => 'bestsellers' ),
        'popular_styles'       => array( 'label' => 'سبک‌های محبوب', 'template' => 'popular-styles' ),
        'trust'                => array( 'label' => 'اعتمادسازی', 'template' => 'trust' ),
    );
}

function wm_get_home_sections_order() {
    static $cache = null;
    $is_test = defined( 'PHPUNIT_COMPOSER_INSTALL' ) || defined( 'WP_TESTS_DOMAIN' );

    if ( ! $is_test && null !== $cache ) {
        return $cache;
    }

    // Optimize: Memoize to avoid redundant processing and database queries per request
    $defaults = wm_home_default_sections();
    $rows     = wm_home_get_option( 'home_sections_order', array() );

    if ( empty( $rows ) || ! is_array( $rows ) ) {
        $result = array_keys( $defaults );
        if ( ! $is_test ) {
            $cache = $result;
        }
        return $result;
    }

    $sections = array();
    foreach ( $rows as $row ) {
        if ( empty( $row['section_enabled'] ) || empty( $row['section_key'] ) ) {
            continue;
        }

        $key = sanitize_key( $row['section_key'] );
        if ( isset( $defaults[ $key ] ) && ! in_array( $key, $sections, true ) ) {
            $sections[] = $key;
        }
    }

    $result = $sections ? $sections : array_keys( $defaults );
    if ( ! $is_test ) {
        $cache = $result;
    }
    return $result;
}

function wm_render_home_section( $section_key ) {
    $sections = wm_home_default_sections();
    $key      = sanitize_key( $section_key );

    if ( empty( $sections[ $key ]['template'] ) ) {
        return;
    }

    $slug = 'template-parts/home/' . $sections[ $key ]['template'];
    if ( locate_template( $slug . '.php' ) ) {
        get_template_part( $slug );
    }
}

function wm_normalize_product_ids( $products ) {
    $ids = array();

    foreach ( (array) $products as $product ) {
        if ( is_numeric( $product ) ) {
            $ids[] = absint( $product );
        } elseif ( is_object( $product ) && isset( $product->ID ) ) {
            $ids[] = absint( $product->ID );
        } elseif ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
            $ids[] = absint( $product->get_id() );
        }
    }

    return array_values( array_unique( array_filter( $ids ) ) );
}

function wm_home_get_manual_products( $field_name, $count ) {
    if ( ! function_exists( 'wc_get_products' ) ) {
        return array();
    }

    $product_ids = wm_normalize_product_ids( wm_home_get_option( $field_name, array() ) );

    if ( empty( $product_ids ) ) {
        return array();
    }

    $args = array(
        'include' => $product_ids,
        'limit'   => -1,
        'status'  => 'publish',
        'return'  => 'objects',
    );

    $fetched_products = wc_get_products( $args );

    // Index by ID to preserve the original manual sorting order
    $products_by_id = array();
    foreach ( $fetched_products as $p ) {
        if ( is_object( $p ) && method_exists( $p, 'get_id' ) ) {
            $products_by_id[ $p->get_id() ] = $p;
        }
    }

    $products = array();
    foreach ( $product_ids as $product_id ) {
        if ( count( $products ) >= $count ) {
            break;
        }

        if ( isset( $products_by_id[ $product_id ] ) ) {
            $product = $products_by_id[ $product_id ];
            if ( method_exists( $product, 'get_image_id' ) && $product->get_image_id() ) {
                $products[ $product_id ] = $product;
            }
        }
    }

    return array_values( $products );
}

function wm_home_get_auto_products( $type, $count ) {
    if ( ! class_exists( 'WC_Product_Query' ) ) {
        return array();
    }

    $args = array(
        'limit'        => $count,
        'status'       => 'publish',
        'stock_status' => 'instock',
        'return'       => 'objects',
    );

    if ( 'recommended' === $type ) {
        $args['meta_key']   = 'eshobe_ecommerce_is_recommended';
        $args['meta_value'] = '1';
        $args['orderby']    = 'date';
        $args['order']      = 'DESC';
    } elseif ( 'bestsellers' === $type ) {
        $args['meta_key'] = 'total_sales';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
    } else {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    }

    $products = array_values(
        array_filter(
            wc_get_products( $args ),
            function( $product ) {
                return $product && $product->get_image_id();
            }
        )
    );

    if ( 'bestsellers' === $type && count( $products ) < 4 ) {
        unset( $args['meta_key'], $args['orderby'], $args['order'] );
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
        $products        = wc_get_products( $args );
    }

    return array_slice( $products, 0, $count );
}

function wm_home_get_products( $type = 'recommended', $count = 10 ) {
    $count = max( 1, absint( $count ) );

    $source_map = array(
        'recommended' => array( 'source' => 'home_recommended_source', 'manual' => 'home_recommended_products', 'auto' => 'auto_meta' ),
        'bestsellers' => array( 'source' => 'home_bestsellers_source', 'manual' => 'home_bestsellers_products', 'auto' => 'auto_sales' ),
    );

    if ( empty( $source_map[ $type ] ) ) {
        return wm_home_get_auto_products( $type, $count );
    }

    $config   = $source_map[ $type ];
    $source   = wm_home_get_option( $config['source'], $config['auto'] );
    $manual   = wm_home_get_manual_products( $config['manual'], $count );
    $products = array();

    if ( 'manual_products' === $source ) {
        return array_slice( $manual, 0, $count );
    }

    if ( 'mixed' === $source ) {
        $products = $manual;
    }

    if ( count( $products ) < $count ) {
        foreach ( wm_home_get_auto_products( $type, $count ) as $product ) {
            if ( ! $product || wm_home_product_list_contains( $products, $product->get_id() ) ) {
                continue;
            }
            $products[] = $product;
            if ( count( $products ) >= $count ) {
                break;
            }
        }
    }

    return array_slice( array_values( $products ), 0, $count );
}

function wm_home_product_list_contains( $products, $product_id ) {
    foreach ( $products as $product ) {
        if ( $product && method_exists( $product, 'get_id' ) && absint( $product->get_id() ) === absint( $product_id ) ) {
            return true;
        }
    }

    return false;
}

function wm_parse_filter_extra_query_args( $value ) {
    $query = array();
    $lines = preg_split( '/[\r\n&]+/', (string) $value );

    foreach ( $lines as $line ) {
        if ( false === strpos( $line, '=' ) ) {
            continue;
        }

        list( $raw_key, $raw_value ) = array_map( 'trim', explode( '=', $line, 2 ) );
        $key = sanitize_key( $raw_key );
        if ( '' !== $key && '' !== $raw_value ) {
            $query[ $key ] = sanitize_text_field( $raw_value );
        }
    }

    return $query;
}

function wm_build_filter_box_url( $item ) {
    $mode       = ! empty( $item['filter_link_mode'] ) ? sanitize_key( $item['filter_link_mode'] ) : 'manual_url';
    $manual_url = ! empty( $item['filter_manual_url'] ) ? $item['filter_manual_url'] : ( ! empty( $item['filter_url'] ) ? $item['filter_url'] : '' );

    if ( 'manual_url' === $mode && $manual_url ) {
        return esc_url_raw( $manual_url );
    }

    $term = ! empty( $item['filter_term'] ) ? $item['filter_term'] : ( ! empty( $item['filter_gender_term'] ) ? $item['filter_gender_term'] : '' );
    $base_url = '';
    if ( in_array( $mode, array( 'taxonomy_term', 'taxonomy_and_price' ), true ) ) {
        if ( $term ) {
            $term_link = get_term_link( $term );
            $base_url  = is_wp_error( $term_link ) ? '' : $term_link;
        } elseif ( ! empty( $item['filter_taxonomy'] ) && function_exists( 'wm_taxonomy_landing_get_base_url' ) ) {
            // No specific term picked — link to the "all products in this taxonomy" landing page.
            $base_url = wm_taxonomy_landing_get_base_url( $item['filter_taxonomy'] );
        }
    }

    if ( ! $base_url ) {
        $base_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
    }

    $query = array();
    if ( in_array( $mode, array( 'price_range', 'taxonomy_and_price', 'advanced_query' ), true ) ) {
        if ( isset( $item['filter_min_price'] ) && '' !== $item['filter_min_price'] ) {
            $query['min_price'] = absint( $item['filter_min_price'] );
        }
        if ( isset( $item['filter_max_price'] ) && '' !== $item['filter_max_price'] ) {
            $query['max_price'] = absint( $item['filter_max_price'] );
        }
    }

    if ( 'advanced_query' === $mode && ! empty( $item['filter_extra_query_args'] ) ) {
        $query = array_merge( $query, wm_parse_filter_extra_query_args( $item['filter_extra_query_args'] ) );
    }

    if ( $term && 'advanced_query' === $mode ) {
        $query['filter_gender'] = sanitize_title( is_object( $term ) ? $term->slug : $term );
    }

    /**
     * Filters the query arguments used to build the filter box URL.
     *
     * @param array $query The parsed query arguments.
     * @param array $item  The filter item data.
     */
    $query = apply_filters( 'wm_filter_box_query_args', $query, $item );

    // Map to YITH filter URL format if custom filters are disabled.
    if ( ! empty( $query ) && function_exists( 'wm_product_archive_bool_option' ) && ! wm_product_archive_bool_option( 'wm_archive_custom_filters_enabled', true ) ) {
        $yith_query = array( 'yith_wcan' => '1' );
        foreach ( $query as $key => $val ) {
            $yith_query[ $key ] = $val;
        }

        $query = $yith_query;
    }

    return esc_url_raw( add_query_arg( $query, $base_url ) );
}

function wm_home_normalize_filter_item( $item ) {
    $item = wp_parse_args(
        (array) $item,
        array(
            'filter_enabled'     => true,
            'filter_title'       => '',
            'filter_subtitle'    => '',
            'filter_image'          => '',
            'filter_style'          => 'dark_card',
            'filter_cover_enabled'  => true,
            'filter_cover_color'    => '',
            'filter_badge_text'     => '',
            'filter_button_text' => __( 'مشاهده', 'eshobe-ecommerce' ),
            'filter_link_mode'   => ! empty( $item['filter_url'] ) ? 'manual_url' : 'taxonomy_and_price',
        )
    );

    if ( empty( $item['filter_manual_url'] ) && ! empty( $item['filter_url'] ) ) {
        $item['filter_manual_url'] = $item['filter_url'];
    }
    if ( empty( $item['filter_term'] ) && ! empty( $item['filter_gender_term'] ) ) {
        $item['filter_term'] = $item['filter_gender_term'];
    }

    return $item;
}

function wm_home_default_filter_items() {
    $shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

    return array(
        array( 'filter_enabled' => true, 'filter_title' => 'تا ۵ میلیون', 'filter_subtitle' => 'شروع اقتصادی و کاربردی', 'filter_link_mode' => 'price_range', 'filter_max_price' => 5000000 ),
        array( 'filter_enabled' => true, 'filter_title' => 'کلاسیک', 'filter_subtitle' => 'طراحی ساده و همیشه قابل استفاده', 'filter_link_mode' => 'manual_url', 'filter_manual_url' => $shop_url, 'filter_style' => 'light_card' ),
        array( 'filter_enabled' => true, 'filter_title' => 'هدیه', 'filter_subtitle' => 'انتخاب‌های امن برای مناسبت‌ها', 'filter_link_mode' => 'manual_url', 'filter_manual_url' => $shop_url ),
    );
}

function wm_home_get_filter_sections() {
    $sections = wm_home_get_option( 'home_filter_sections', array() );
    $output   = array();

    foreach ( (array) $sections as $section ) {
        if ( empty( $section['filter_section_enabled'] ) ) {
            continue;
        }

        $items = array();
        foreach ( (array) ( $section['filter_section_items'] ?? array() ) as $item ) {
            $item = wm_home_normalize_filter_item( $item );
            if ( ! empty( $item['filter_enabled'] ) && ! empty( $item['filter_title'] ) ) {
                $items[] = $item;
            }
        }

        if ( $items ) {
            $output[] = array(
                'title'    => $section['filter_section_title'] ?? '',
                'subtitle' => $section['filter_section_subtitle'] ?? '',
                'layout'   => $section['filter_section_layout'] ?? 'cards_3',
                'items'    => $items,
            );
        }
    }

    if ( $output ) {
        return $output;
    }

    $legacy_sections = array(
        array( 'title' => 'زنانه / دخترانه', 'items' => wm_home_get_option( 'home_women_quick_filters', array() ) ),
        array( 'title' => 'مردانه / پسرانه', 'items' => wm_home_get_option( 'home_men_quick_filters', array() ) ),
    );

    foreach ( $legacy_sections as $legacy_section ) {
        $items = array();
        foreach ( (array) $legacy_section['items'] as $item ) {
            $item = wm_home_normalize_filter_item( $item );
            if ( ! empty( $item['filter_enabled'] ) && ! empty( $item['filter_title'] ) ) {
                $items[] = $item;
            }
        }

        if ( $items ) {
            $output[] = array(
                'title'    => $legacy_section['title'],
                'subtitle' => '',
                'layout'   => 'cards_3',
                'items'    => $items,
            );
        }
    }

    if ( $output ) {
        return $output;
    }

    return array(
        array(
            'title'    => 'مسیر خرید سریع',
            'subtitle' => '',
            'layout'   => 'cards_3',
            'items'    => wm_home_default_filter_items(),
        ),
    );
}

function wm_render_filter_card( $item ) {
    $style       = ! empty( $item['filter_style'] ) ? sanitize_html_class( $item['filter_style'] ) : 'dark_card';
    $button_text = ! empty( $item['filter_button_text'] ) ? $item['filter_button_text'] : __( 'مشاهده', 'eshobe-ecommerce' );

    // Optional per-card cover control: --wm-filter-cover recolors the overlay
    // layer over the card image; --no-cover removes it entirely.
    $card_style = array();
    foreach ( array( '--wm-filter-accent' => 'filter_color_value', '--wm-filter-cover' => 'filter_cover_color' ) as $css_var => $field ) {
        if ( empty( $item[ $field ] ) ) {
            continue;
        }
        $color = sanitize_hex_color( $item[ $field ] );
        if ( $color ) {
            $card_style[] = $css_var . ':' . $color;
        }
    }
    $style_attr  = $card_style ? ' style="' . esc_attr( implode( ';', $card_style ) ) . '"' : '';
    $cover_class = empty( $item['filter_cover_enabled'] ) ? ' wm-home-filter-card--no-cover' : '';

    ob_start();
    ?>
    <a class="wm-home-filter-card wm-home-filter-card--<?php echo esc_attr( $style ); ?><?php echo esc_attr( $cover_class ); ?>" href="<?php echo esc_url( wm_build_filter_box_url( $item ) ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <span class="wm-home-filter-card__media">
            <?php echo wm_home_get_image_html( ! empty( $item['filter_image'] ) ? $item['filter_image'] : '', 'medium', array( 'alt' => $item['filter_title'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </span>
        <span class="wm-home-filter-card__body">
            <?php if ( ! empty( $item['filter_badge_text'] ) ) : ?>
                <span class="wm-home-filter-card__badge"><?php echo esc_html( $item['filter_badge_text'] ); ?></span>
            <?php endif; ?>
            <strong><?php echo esc_html( $item['filter_title'] ); ?></strong>
            <?php if ( ! empty( $item['filter_subtitle'] ) ) : ?>
                <small><?php echo esc_html( $item['filter_subtitle'] ); ?></small>
            <?php endif; ?>
            <em><?php echo esc_html( $button_text ); ?></em>
        </span>
    </a>
    <?php
    return ob_get_clean();
}

function wm_render_home_filter_section( $section, $extra_class = '' ) {
    $layout = ! empty( $section['layout'] ) ? sanitize_html_class( $section['layout'] ) : 'cards_3';
    $items  = ! empty( $section['items'] ) ? (array) $section['items'] : array();

    if ( ! $items ) {
        return;
    }

    ?>
    <section class="wm-home-section wm-home-filters wm-home-filters--<?php echo esc_attr( $layout ); ?> wm-section-decor wm-section-decor--filters wm-section-decor--ring <?php echo esc_attr( $extra_class ); ?>">
        <div class="wm-home-section__header">
            <div>
                <?php if ( ! empty( $section['title'] ) ) : ?>
                    <h2 class="wm-home-section__title"><?php echo esc_html( $section['title'] ); ?></h2>
                <?php endif; ?>
                <?php if ( ! empty( $section['subtitle'] ) ) : ?>
                    <p class="wm-home-section__subtitle"><?php echo esc_html( $section['subtitle'] ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="wm-home-filters__grid">
            <?php foreach ( $items as $item ) : ?>
                <?php echo wm_render_filter_card( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function wm_render_home_filter_sections() {
    foreach ( wm_home_get_filter_sections() as $section ) {
        wm_render_home_filter_section( $section );
    }
}

/**
 * Builds the inline style for the optional card cover color.
 *
 * @param array  $item   Card item data (ACF repeater row).
 * @param string $prefix Field prefix, e.g. 'brand_' or 'style_'.
 * @return string Style attribute string (may be empty).
 */
function wm_home_card_cover_style_attr( $item, $prefix ) {
    $color = ! empty( $item[ $prefix . 'cover_color' ] ) ? sanitize_hex_color( $item[ $prefix . 'cover_color' ] ) : '';
    return $color ? ' style="--wm-filter-cover:' . $color . '"' : '';
}

function wm_render_home_quick_filters_section( $items, $args = array() ) {
    $args = wp_parse_args(
        $args,
        array(
            'class'    => '',
            'title'    => '',
            'subtitle' => '',
        )
    );

    $normalized = array();
    foreach ( (array) $items as $item ) {
        $item = wm_home_normalize_filter_item( $item );
        if ( ! empty( $item['filter_enabled'] ) && ! empty( $item['filter_title'] ) ) {
            $normalized[] = $item;
        }
    }

    if ( ! $normalized ) {
        $normalized = wm_home_default_filter_items();
    }

    wm_render_home_filter_section(
        array(
            'title'    => $args['title'],
            'subtitle' => $args['subtitle'],
            'layout'   => 'cards_3',
            'items'    => $normalized,
        ),
        $args['class']
    );
}
