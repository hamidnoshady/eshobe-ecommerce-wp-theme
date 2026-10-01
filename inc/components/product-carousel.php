<?php
/**
 * Shared product carousel.
 *
 * @package WM_Theme
 */

function wm_render_product_carousel( $products, $args = array() ) {
    $products = array_filter( (array) $products );
    if ( empty( $products ) ) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        array(
            'title'    => '',
            'subtitle' => '',
            'class'    => '',
            'view_all' => '',
        )
    );

    $section_id = 'wm-product-carousel-' . wp_unique_id();

    // ⚡ Bolt Optimization:
    // Pre-fetch all attachment post objects for the main and hover images to
    // prevent N+1 queries when wp_get_attachment_image is called in the loop.
    if ( function_exists( '_prime_post_caches' ) ) {
        $attachment_ids = array();

        foreach ( $products as $product ) {
            if ( ! $product instanceof WC_Product ) {
                if ( is_numeric( $product ) ) {
                    $product = wc_get_product( absint( $product ) );
                }
            }
            if ( ! $product instanceof WC_Product ) {
                continue;
            }
            $image_id    = $product->get_image_id();
            $gallery_ids = $product->get_gallery_image_ids();
            if ( $image_id ) {
                $attachment_ids[] = absint( $image_id );
            }
            if ( ! empty( $gallery_ids ) ) {
                $attachment_ids[] = absint( $gallery_ids[0] );
            }
        }

        if ( ! empty( $attachment_ids ) ) {
            _prime_post_caches( array_unique( array_filter( $attachment_ids ) ), false, true );
        }
    }

    ob_start();
    ?>
    <section class="wm-product-carousel <?php echo esc_attr( $args['class'] ); ?>" id="<?php echo esc_attr( $section_id ); ?>" data-product-carousel>
        <div class="wm-home-section__header">
            <div>
                <?php if ( $args['title'] ) : ?>
                    <div class="wm-home-section__title-wrap">
                        <h2 class="wm-home-section__title"><?php echo esc_html( $args['title'] ); ?></h2>
                        <?php if ( ! empty( $args['view_all'] ) ) : ?>
                            <a href="<?php echo esc_url( $args['view_all'] ); ?>" class="wm-home-button wm-home-button--outline wm-home-button--sm">
                                <?php esc_html_e( 'مشاهده همه', 'eshobe-ecommerce' ); ?>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 19l-7-7 7-7"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $args['subtitle'] ) : ?>
                    <p class="wm-home-section__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
                <?php endif; ?>
            </div>
            <div class="wm-product-carousel__controls">
                <button class="wm-product-carousel__arrow" type="button" data-carousel-direction="prev" aria-label="<?php esc_attr_e( 'Previous products', 'eshobe-ecommerce' ); ?>"><span aria-hidden="true">‹</span></button>
                <button class="wm-product-carousel__arrow" type="button" data-carousel-direction="next" aria-label="<?php esc_attr_e( 'Next products', 'eshobe-ecommerce' ); ?>"><span aria-hidden="true">›</span></button>
            </div>
        </div>
        <div class="wm-product-carousel__viewport">
            <div class="wm-product-carousel__track" tabindex="0">
                <?php foreach ( $products as $product ) : ?>
                    <?php echo wm_render_product_card( $product, array( 'class' => 'wm-product-carousel__item' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Fetches in-stock, published products for a taxonomy-based carousel block.
 *
 * @param string $taxonomy Taxonomy slug (e.g. product_cat, product_tag, product_brand).
 * @param string $terms    Comma-separated term slugs or numeric term IDs. Empty = no term restriction.
 * @param int    $count    Max number of products to return.
 * @param string $orderby  WC_Product_Query orderby value.
 * @param string $order    ASC|DESC.
 * @return WC_Product[]
 */
function wm_get_taxonomy_carousel_products( $taxonomy, $terms, $count, $orderby = 'date', $order = 'DESC' ) {
    if ( ! class_exists( 'WC_Product_Query' ) || ! taxonomy_exists( $taxonomy ) ) {
        return array();
    }

    $count       = max( 1, absint( $count ) );
    $term_values = array_values( array_filter( array_map( 'trim', explode( ',', (string) $terms ) ) ) );

    $args = array(
        'limit'        => $count * 2,
        'status'       => 'publish',
        'stock_status' => 'instock',
        'return'       => 'objects',
        'orderby'      => in_array( $orderby, array( 'date', 'title', 'price', 'popularity', 'rand', 'menu_order' ), true ) ? $orderby : 'date',
        'order'        => 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC',
    );

    if ( ! empty( $term_values ) ) {
        $args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            array(
                'taxonomy' => $taxonomy,
                'field'    => is_numeric( $term_values[0] ) ? 'term_id' : 'slug',
                'terms'    => is_numeric( $term_values[0] ) ? array_map( 'absint', $term_values ) : $term_values,
            ),
        );
    }

    $products = array_values(
        array_filter(
            wc_get_products( $args ),
            function( $product ) {
                return $product && $product->get_image_id();
            }
        )
    );

    return array_slice( $products, 0, $count );
}

