<?php
/**
 * Product components and shortcodes.
 *
 * @package WM_Theme
 */

function wm_get_current_product() {
    if ( ! function_exists( 'wc_get_product' ) ) {
        return null;
    }

    global $product;
    if ( $product instanceof WC_Product ) {
        return $product;
    }

    $product_id = get_the_ID();
    return $product_id ? wc_get_product( $product_id ) : null;
}

function wm_get_terms_for_product( $product_id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) {
        return array();
    }

    $terms = get_the_terms( $product_id, $taxonomy );
    return ( empty( $terms ) || is_wp_error( $terms ) ) ? array() : $terms;
}

function wm_term_link( $term ) {
    $link = get_term_link( $term );
    return is_wp_error( $link ) ? '' : $link;
}

function wm_render_linked_terms( $terms ) {
    $output = array();

    foreach ( $terms as $term ) {
        $link = wm_term_link( $term );
        $name = esc_html( $term->name );
        $output[] = $link ? '<a href="' . esc_url( $link ) . '">' . $name . '</a>' : $name;
    }

    return implode( '، ', $output );
}

function wm_get_product_brand_terms( $product_id ) {
    $taxonomies = array( 'product_brand', 'pa_brand', 'brand' );

    foreach ( $taxonomies as $taxonomy ) {
        $terms = wm_get_terms_for_product( $product_id, $taxonomy );
        if ( ! empty( $terms ) ) {
            return $terms;
        }
    }

    return array();
}

function wm_get_product_category_terms( $product_id ) {
    return wm_get_terms_for_product( $product_id, 'product_cat' );
}

function wm_get_guarantee( $product_id ) {
    $meta_keys = array( 'گارانتی', 'guarantee', 'warranty', 'product_guarantee', 'product_warranty', '_guarantee', '_warranty' );

    if ( function_exists( 'get_field' ) ) {
        foreach ( $meta_keys as $key ) {
            $value = get_field( $key, $product_id );
            if ( ! empty( $value ) ) {
                return wm_normalize_meta_value( $value );
            }
        }
    }

    foreach ( $meta_keys as $key ) {
        $value = get_post_meta( $product_id, $key, true );
        if ( ! empty( $value ) ) {
            return wm_normalize_meta_value( $value );
        }
    }

    foreach ( get_post_meta( $product_id ) as $key => $values ) {
        if ( false === strpos( $key, 'گارانتی' ) && false === stripos( $key, 'guarantee' ) && false === stripos( $key, 'warranty' ) ) {
            continue;
        }

        $value = reset( $values );
        if ( ! empty( $value ) ) {
            return wm_normalize_meta_value( maybe_unserialize( $value ) );
        }
    }

    return '';
}

function wm_normalize_meta_value( $value ) {
    if ( is_array( $value ) ) {
        $value = implode( '، ', array_filter( array_map( 'wm_normalize_meta_value', $value ) ) );
    }

    if ( is_object( $value ) ) {
        return '';
    }

    return trim( wp_strip_all_tags( (string) $value ) );
}

function wm_get_product_specs( $product_id ) {
    $taxonomies = array(
        'gender'                   => 'جنسیت',
        'style'                    => 'استایل',
        'strap-material'           => 'جنس بند',
        'material'                 => 'جنس بدنه',
        'glass-material'           => 'جنس شیشه',
        'frame-material'           => 'جنس قاب',
        'frame-shape'              => 'شکل قاب',
        'engine-type'              => 'نوع موتور',
        'water-resistant'          => 'مقاومت در برابر آب',
        'country'                  => 'کشور برند',
        'lock-type'                => 'نوع قفل',
        'calendars'                => 'تقویم',
        'band-design'              => 'طرح بند',
        'page-ayout'               => 'طرح صفحه',
        'frame'                    => 'عرض قاب',
        'feature'                  => 'ویژگی',
        'color-of-the-strap'       => 'رنگ بند',
        'page-color'               => 'رنگ صفحه',
        'frame-color'              => 'رنگ قاب',
        'scratch-resistant-screen' => 'صفحه ضد خش',
    );

    $items = array();
    foreach ( $taxonomies as $taxonomy => $label ) {
        $terms = wm_get_terms_for_product( $product_id, $taxonomy );
        if ( empty( $terms ) ) {
            continue;
        }

        $items[] = array(
            'label' => $label,
            'value' => wm_render_linked_terms( $terms ),
        );
    }

    $guarantee = wm_get_guarantee( $product_id );
    if ( ! empty( $guarantee ) ) {
        $items[] = array(
            'label' => 'گارانتی',
            'value' => esc_html( $guarantee ),
        );
    }

    return $items;
}

function wm_get_product_gallery_ids( $product ) {
    if ( ! $product ) {
        return array();
    }

    $ids = array();
    if ( $product->get_image_id() ) {
        $ids[] = $product->get_image_id();
    }

    $ids = array_merge( $ids, $product->get_gallery_image_ids() );
    return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

function wm_render_product_gallery() {
    $product = wm_get_current_product();
    if ( ! $product ) {
        return '';
    }

    $image_count = count( wm_get_product_gallery_ids( $product ) );
    $image_ids   = wm_get_product_gallery_ids( $product );
    $main_id     = ! empty( $image_ids ) ? $image_ids[0] : 0;
    $class       = 'wm-product-gallery ' . ( $image_count > 1 ? 'wm-product-gallery--has-thumbs' : 'wm-product-gallery--single' );
    $main_full   = $main_id ? wp_get_attachment_image_url( $main_id, 'full' ) : '';
    $main_large  = $main_id ? wp_get_attachment_image_url( $main_id, 'large' ) : '';
    $main_alt    = $main_id ? get_post_meta( $main_id, '_wp_attachment_image_alt', true ) : '';
    $main_alt    = $main_alt ? $main_alt : $product->get_name();

    ob_start();
    ?>
    <section class="<?php echo esc_attr( $class ); ?>" aria-label="<?php esc_attr_e( 'Product gallery', 'eshobe-ecommerce' ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
        <?php if ( $main_full ) : ?>
            <button type="button" class="wm-product-gallery__zoom" data-full="<?php echo esc_url( $main_full ); ?>" data-alt="<?php echo esc_attr( $main_alt ); ?>" aria-label="<?php esc_attr_e( 'View larger product image', 'eshobe-ecommerce' ); ?>">
                <span aria-hidden="true">⌕</span>
            </button>
        <?php endif; ?>
        <div class="wm-product-gallery__main">
            <?php if ( $main_id ) : ?>
                <div class="wm-product-gallery__image" data-full="<?php echo esc_url( $main_full ); ?>">
                    <?php
                    echo wp_get_attachment_image(
                        $main_id,
                        'large',
                        false,
                        array(
                            'class' => 'wm-product-gallery__main-img',
                            'alt'   => $main_alt,
                        )
                    );
                    ?>
                </div>
            <?php else : ?>
                <div class="wm-product-gallery__image">
                    <?php echo wc_placeholder_img( 'woocommerce_single', array( 'class' => 'wm-product-gallery__main-img' ) ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ( $image_count > 1 ) : ?>
            <div class="wm-product-gallery__thumbs" aria-label="<?php esc_attr_e( 'Product image thumbnails', 'eshobe-ecommerce' ); ?>">
                <?php foreach ( $image_ids as $index => $image_id ) : ?>
                    <?php
                    $thumb_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
                    $thumb_alt = $thumb_alt ? $thumb_alt : $product->get_name();
                    ?>
                    <button
                        class="wm-product-gallery__thumb <?php echo 0 === $index ? 'is-active' : ''; ?>"
                        type="button"
                        data-large="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'large' ) ); ?>"
                        data-full="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'full' ) ); ?>"
                        data-srcset="<?php echo esc_attr( wp_get_attachment_image_srcset( $image_id, 'large' ) ); ?>"
                        data-sizes="<?php echo esc_attr( wp_get_attachment_image_sizes( $image_id, 'large' ) ); ?>"
                        data-alt="<?php echo esc_attr( $thumb_alt ); ?>"
                        aria-label="<?php echo esc_attr( sprintf( __( 'Show product image %d', 'eshobe-ecommerce' ), $index + 1 ) ); ?>"
                        aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                    >
                        <?php echo wp_get_attachment_image( $image_id, 'woocommerce_gallery_thumbnail', false, array( 'alt' => $thumb_alt ) ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function wm_get_product_stock_data( $product ) {
    if ( ! $product ) {
        return array();
    }

    if ( $product->is_on_backorder() ) {
        return array(
            'status' => 'backorder',
            'label'  => 'قابل پیش‌خرید',
            'note'   => '',
        );
    }

    if ( ! $product->is_in_stock() ) {
        return array(
            'status' => 'out_of_stock',
            'label'  => 'ناموجود',
            'note'   => '',
        );
    }

    if ( $product->managing_stock() ) {
        $qty = $product->get_stock_quantity();
        return array(
            'status' => 'in_stock',
            'label'  => 'در انبار',
            'note'   => ! is_null( $qty ) ? 'موجودی قابل سفارش: ' . wc_format_stock_quantity_for_display( $qty, $product ) : 'آماده ثبت سفارش',
        );
    }

    return array(
        'status' => 'in_stock',
        'label'  => 'در انبار',
        'note'   => 'آماده ثبت سفارش',
    );
}

function wm_get_mobile_stock_label( $product, $stock ) {
    if ( empty( $stock['label'] ) ) {
        return '';
    }

    if ( $product && 'in_stock' === $stock['status'] && $product->managing_stock() ) {
        $qty = $product->get_stock_quantity();
        if ( ! is_null( $qty ) ) {
            return $stock['label'] . ' · ' . wc_format_stock_quantity_for_display( $qty, $product );
        }
    }

    return $stock['label'];
}

function wm_render_product_rating( $product ) {
    $count = $product->get_review_count();

    if ( $count > 0 ) {
        return '<div class="wm-product-intro__rating">' . wc_get_rating_html( $product->get_average_rating(), $count ) . '<a class="wm-product-intro__rating-count" href="#wm-product-panel-reviews">(' . esc_html( number_format_i18n( $count ) ) . ')</a></div>';
    }

    return '<div class="wm-product-intro__rating wm-product-intro__rating--empty"><a href="#wm-product-panel-reviews">' . esc_html__( 'اولین نفر باشید که نظر می‌دهید', 'eshobe-ecommerce' ) . '</a></div>';
}

function wm_render_product_wishlist_button( $product_id ) {
    return '<button type="button" class="wm-product-intro__wishlist" data-wm-wishlist-toggle data-product-id="' . esc_attr( $product_id ) . '" aria-pressed="false" aria-label="' . esc_attr__( 'افزودن به علاقه‌مندی‌ها', 'eshobe-ecommerce' ) . '"><span class="wm-product-intro__wishlist-icon" aria-hidden="true"></span></button>';
}

function wm_render_product_intro() {
    $product = wm_get_current_product();
    if ( ! $product ) {
        return '';
    }

    $product_id = $product->get_id();
    $brands     = wm_get_product_brand_terms( $product_id );
    $cats       = wm_get_product_category_terms( $product_id );
    $excerpt    = $product->get_short_description();

    ob_start();
    ?>
    <section class="wm-product-intro">
        <div class="wm-product-intro__heading">
            <h1 class="wm-product-intro__title"><?php echo esc_html( get_the_title( $product_id ) ); ?></h1>
            <?php echo wm_render_product_wishlist_button( $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php echo wm_render_product_rating( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php if ( ! empty( $brands ) || ! empty( $cats ) ) : ?>
            <div class="wm-product-intro__meta">
                <?php if ( ! empty( $brands ) ) : ?>
                    <?php $brand = $brands[0]; ?>
                    <a class="wm-product-intro__meta-item wm-product-intro__meta-item--brand" href="<?php echo esc_url( wm_term_link( $brand ) ); ?>">
                        <span class="wm-product-intro__meta-label"><?php echo esc_html_x( 'برند', 'product intro meta label', 'eshobe-ecommerce' ); ?></span>
                        <span class="wm-product-intro__meta-value"><?php echo esc_html( $brand->name ); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ( ! empty( $cats ) ) : ?>
                    <?php $cat = $cats[0]; ?>
                    <a class="wm-product-intro__meta-item wm-product-intro__meta-item--category" href="<?php echo esc_url( wm_term_link( $cat ) ); ?>">
                        <span class="wm-product-intro__meta-label"><?php echo esc_html_x( 'دسته‌بندی', 'product intro meta label', 'eshobe-ecommerce' ); ?></span>
                        <span class="wm-product-intro__meta-value"><?php echo esc_html( $cat->name ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ( ! empty( $excerpt ) ) : ?>
            <div class="wm-product-intro__excerpt"><?php echo wp_kses_post( wpautop( $excerpt ) ); ?></div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function wm_render_product_specs() {
    $product = wm_get_current_product();
    if ( ! $product ) {
        return '';
    }

    $items = wm_get_product_specs( $product->get_id() );
    if ( empty( $items ) ) {
        return '';
    }

    $has_mobile_toggle = count( $items ) > 3;
    $class             = 'wm-card wm-product-specs' . ( $has_mobile_toggle ? ' wm-product-specs--mobile-collapsed' : '' );

    ob_start();
    ?>
    <section class="<?php echo esc_attr( $class ); ?>" aria-label="<?php esc_attr_e( 'Product specifications', 'eshobe-ecommerce' ); ?>">
        <div class="wm-product-specs__grid">
            <?php foreach ( $items as $item ) : ?>
                <div class="wm-product-specs__item">
                    <span class="wm-product-specs__dot" aria-hidden="true"></span>
                    <span class="wm-product-specs__label"><?php echo esc_html( $item['label'] ); ?>:</span>
                    <span class="wm-product-specs__value"><?php echo wp_kses_post( $item['value'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ( $has_mobile_toggle ) : ?>
            <div class="wm-product-specs__fade" aria-hidden="true"></div>
            <div class="wm-product-specs__toggle-wrap">
            <button class="wm-product-specs__toggle wm-product-specs__toggle--more" type="button" data-specs-toggle aria-expanded="false">
                <?php echo esc_html__( 'مشاهده بیشتر', 'eshobe-ecommerce' ); ?>
            </button>
            <button class="wm-product-specs__toggle wm-product-specs__toggle--less" type="button" data-specs-toggle aria-expanded="true">
                <?php echo esc_html__( 'مشاهده کمتر', 'eshobe-ecommerce' ); ?>
            </button>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function wm_render_product_stock() {
    $product = wm_get_current_product();
    $stock   = wm_get_product_stock_data( $product );
    if ( empty( $stock ) ) {
        return '';
    }

    $class = 'wm-product-purchase__stock wm-product-purchase__stock--' . sanitize_html_class( $stock['status'] );
    return '<div class="' . esc_attr( $class ) . '"><span class="wm-product-purchase__stock-status">' . esc_html( $stock['label'] ) . '</span>' . ( ! empty( $stock['note'] ) ? '<span class="wm-product-purchase__stock-note">' . esc_html( $stock['note'] ) . '</span>' : '' ) . '</div>';
}

function wm_render_product_purchase_trust() {
    return '<div class="wm-product-purchase__trust"><span>ضمانت اصالت کالا</span><span>ارسال سریع</span><span>پرداخت امن</span></div>';
}

function wm_get_product_purchase_meta_items( $product ) {
    $product_id = $product->get_id();
    $sku        = $product->get_sku();
    $guarantee  = wm_get_guarantee( $product_id );
    $meta_items = array();

    if ( $sku ) {
        $meta_items[] = array(
            'label' => 'SKU',
            'value' => $sku,
        );
    }

    if ( $guarantee ) {
        $meta_items[] = array(
            'label' => 'گارانتی',
            'value' => $guarantee,
        );
    }

    return $meta_items;
}

function wm_render_product_purchase() {
    $product = wm_get_current_product();
    if ( ! $product ) {
        return '';
    }

    $meta_items = wm_get_product_purchase_meta_items( $product );

    ob_start();
    ?>
    <section class="wm-card wm-product-purchase">
        <?php if ( $product->get_price_html() ) : ?>
            <div class="wm-product-purchase__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
        <?php endif; ?>
        <?php if ( $product->is_type( 'variable' ) ) : ?>
            <p class="wm-product-purchase__price-note"><?php echo esc_html__( 'قیمت بسته به رنگ انتخابی متفاوت است', 'eshobe-ecommerce' ); ?></p>
        <?php endif; ?>
        <?php echo wm_render_product_stock(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <div class="wm-product-purchase__cart">
            <?php woocommerce_template_single_add_to_cart(); ?>
        </div>
        <?php if ( ! empty( $meta_items ) ) : ?>
            <div class="wm-product-purchase__meta <?php echo 1 === count( $meta_items ) ? 'wm-product-purchase__meta--single' : ''; ?>">
                <?php foreach ( $meta_items as $item ) : ?>
                    <div class="wm-product-purchase__meta-item">
                        <span class="wm-product-purchase__meta-label"><?php echo esc_html( $item['label'] ); ?></span>
                        <span class="wm-product-purchase__meta-value"><?php echo esc_html( $item['value'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php echo wm_render_product_purchase_trust(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </section>
    <?php
    return ob_get_clean();
}

function wm_render_mobile_product_bottom_bar() {
    $product = wm_get_current_product();
    if ( ! $product ) {
        return '';
    }

    $stock           = wm_get_product_stock_data( $product );
    $starts_expanded = $product->is_type( 'variable' );
    $state_class     = $starts_expanded ? 'wm-mobile-bottom-bar--expanded' : 'wm-mobile-bottom-bar--collapsed';

    ob_start();
    ?>
    <section class="wm-mobile-bottom-bar <?php echo esc_attr( $state_class ); ?>" aria-label="<?php esc_attr_e( 'Mobile purchase bar', 'eshobe-ecommerce' ); ?>">
        <button class="wm-mobile-bottom-bar__handle" type="button" aria-expanded="<?php echo $starts_expanded ? 'true' : 'false'; ?>" aria-controls="wm-mobile-bottom-bar-content">
            <span class="wm-mobile-bottom-bar__chevron" aria-hidden="true"></span>
        </button>

        <div class="wm-mobile-bottom-bar__summary">
            <?php if ( $product->get_price_html() ) : ?>
                <div class="wm-mobile-bottom-bar__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
            <?php endif; ?>

            <?php if ( ! empty( $stock ) ) : ?>
                <div class="wm-mobile-bottom-bar__stock wm-mobile-bottom-bar__stock--<?php echo esc_attr( sanitize_html_class( $stock['status'] ) ); ?>">
                    <?php echo esc_html( wm_get_mobile_stock_label( $product, $stock ) ); ?>
                </div>
            <?php endif; ?>

            <div class="wm-mobile-bottom-bar__cta">
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </div>

        <?php /* SKU/guarantee + trust badges now live in .wm-product-purchase (visible on mobile); this div stays only so the handle's expand/collapse JS has a target. */ ?>
        <div class="wm-mobile-bottom-bar__content" id="wm-mobile-bottom-bar-content" <?php echo $starts_expanded ? '' : 'hidden'; ?>></div>
    </section>
    <?php
    return ob_get_clean();
}

function wm_get_mobile_collapsible_description( $html ) {
    if ( empty( trim( wp_strip_all_tags( $html ) ) ) ) {
        return '';
    }

    $plain_text = wp_strip_all_tags( $html );
    $is_long    = ( function_exists( 'mb_strlen' ) ? mb_strlen( $plain_text ) : strlen( $plain_text ) ) > 700;
    if ( ! $is_long ) {
        return $html;
    }

    ob_start();
    ?>
    <div class="wm-product-description wm-product-description--collapsed" data-mobile-description>
        <div class="wm-product-description__content">
            <?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <div class="wm-product-description__fade" aria-hidden="true"></div>
        <div class="wm-product-description__toggle-wrap">
            <button class="wm-product-description__toggle wm-product-description__toggle--more" type="button" data-description-toggle aria-expanded="false">
                <?php echo esc_html__( 'مشاهده بیشتر', 'eshobe-ecommerce' ); ?>
            </button>
            <button class="wm-product-description__toggle wm-product-description__toggle--less" type="button" data-description-toggle aria-expanded="true">
                <?php echo esc_html__( 'مشاهده کمتر', 'eshobe-ecommerce' ); ?>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function wm_render_product_tabs() {
    $product = wm_get_current_product();
    if ( ! $product ) {
        return '';
    }

    $product_id = $product->get_id();
    $post       = get_post( $product_id );
    $panels     = array();

    if ( $post && ! empty( trim( $post->post_content ) ) ) {
        $panels['description'] = array(
            'label' => 'توضیحات',
            'class' => 'wm-product-tabs__description',
            'html'  => wm_get_mobile_collapsible_description( apply_filters( 'the_content', $post->post_content ) ),
        );
    }

    if ( comments_open( $product_id ) || get_comments_number( $product_id ) > 0 ) {
        ob_start();
        comments_template();
        $reviews_html = ob_get_clean();

        if ( ! empty( trim( wp_strip_all_tags( $reviews_html ) ) ) ) {
            $panels['reviews'] = array(
                'label' => 'نظرات',
                'class' => 'wm-product-tabs__reviews',
                'html'  => $reviews_html,
            );
        }
    }

    if ( empty( $panels ) ) {
        return '';
    }

    $active_key = array_key_first( $panels );

    ob_start();
    ?>
    <section class="wm-product-tabs" id="wm-product-tabs">
        <?php if ( count( $panels ) > 1 ) : ?>
            <div class="wm-product-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'eshobe-ecommerce' ); ?>">
                <?php foreach ( $panels as $key => $panel ) : ?>
                    <?php $is_active = $key === $active_key; ?>
                    <button
                        class="wm-product-tabs__button <?php echo $is_active ? 'is-active' : ''; ?>"
                        type="button"
                        role="tab"
                        id="wm-product-tab-<?php echo esc_attr( $key ); ?>"
                        aria-controls="wm-product-panel-<?php echo esc_attr( $key ); ?>"
                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                        data-tab="<?php echo esc_attr( $key ); ?>"
                    >
                        <?php echo esc_html( $panel['label'] ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="wm-product-tabs__panels">
            <?php foreach ( $panels as $key => $panel ) : ?>
                <?php $is_active = $key === $active_key; ?>
                <div
                    class="wm-product-tabs__panel <?php echo esc_attr( $panel['class'] ); ?> <?php echo $is_active ? 'is-active' : ''; ?>"
                    id="wm-product-panel-<?php echo esc_attr( $key ); ?>"
                    role="tabpanel"
                    aria-labelledby="wm-product-tab-<?php echo esc_attr( $key ); ?>"
                    data-panel="<?php echo esc_attr( $key ); ?>"
                    <?php echo $is_active ? '' : 'hidden'; ?>
                >
                    <?php echo $panel['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function wm_render_related_products() {
    $product = wm_get_current_product();
    if ( ! $product || ! function_exists( 'wc_get_related_products' ) ) {
        return '';
    }

    $related_ids = wc_get_related_products( $product->get_id(), 10, array( $product->get_id() ) );
    if ( empty( $related_ids ) ) {
        return '';
    }

    ob_start();
    ?>
    <section class="wm-related-products" aria-label="<?php esc_attr_e( 'Related products', 'eshobe-ecommerce' ); ?>">
        <div class="wm-related-products__header">
            <h2 class="wm-related-products__title"><?php echo esc_html__( 'محصولات مشابه', 'eshobe-ecommerce' ); ?></h2>
            <div class="wm-related-products__nav wm-related-products__controls" aria-label="<?php esc_attr_e( 'Related products carousel controls', 'eshobe-ecommerce' ); ?>">
                <button class="wm-related-products__arrow wm-related-products__arrow--prev" type="button" data-related-direction="prev" aria-label="<?php esc_attr_e( 'Previous related products', 'eshobe-ecommerce' ); ?>">
                    <span aria-hidden="true">‹</span>
                </button>
                <button class="wm-related-products__arrow wm-related-products__arrow--next" type="button" data-related-direction="next" aria-label="<?php esc_attr_e( 'Next related products', 'eshobe-ecommerce' ); ?>">
                    <span aria-hidden="true">›</span>
                </button>
            </div>
        </div>

        <div class="wm-related-products__viewport wm-related-products__carousel">
            <div class="wm-related-products__track" tabindex="0">
                <?php foreach ( $related_ids as $related_id ) : ?>
                    <?php
                    $related_product = wc_get_product( $related_id );
                    if ( ! $related_product ) {
                        continue;
                    }

                    $image_id      = $related_product->get_image_id();
                    $gallery_ids   = $related_product->get_gallery_image_ids();
                    $secondary_id  = ! empty( $gallery_ids ) ? absint( $gallery_ids[0] ) : 0;
                    $has_hover     = $secondary_id && $secondary_id !== $image_id;
                    $title         = $related_product->get_name();
                    $permalink     = get_permalink( $related_id );
                    $card_class    = 'wm-related-products__slide wm-related-card' . ( $has_hover ? ' wm-related-card--has-hover-image' : '' );
                    $button_class  = implode(
                        ' ',
                        array_filter(
                            array(
                                'wm-related-card__button',
                                'button',
                                $related_product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '',
                                $related_product->is_purchasable() && $related_product->is_in_stock() ? 'add_to_cart_button' : '',
                                'product_type_' . $related_product->get_type(),
                            )
                        )
                    );
                    ?>
                    <article class="<?php echo esc_attr( $card_class ); ?>">
                        <h3 class="wm-related-card__title">
                            <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                        </h3>

                        <a class="wm-related-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
                            <?php
                            if ( $image_id ) {
                                echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'wm-related-card__image wm-related-card__image-main', 'alt' => $title ) );
                            } else {
                                echo wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'wm-related-card__image wm-related-card__image-main' ) );
                            }
                            ?>
                            <?php if ( $has_hover ) : ?>
                                <?php echo wp_get_attachment_image( $secondary_id, 'woocommerce_thumbnail', false, array( 'class' => 'wm-related-card__image wm-related-card__image-hover', 'alt' => $title ) ); ?>
                            <?php endif; ?>
                        </a>

                        <?php if ( $related_product->get_price_html() ) : ?>
                            <div class="wm-related-card__price"><?php echo wp_kses_post( $related_product->get_price_html() ); ?></div>
                        <?php endif; ?>

                        <div class="wm-related-card__actions">
                            <div class="wm-related-card__button-wrap">
                                <a
                                    href="<?php echo esc_url( $related_product->add_to_cart_url() ); ?>"
                                    data-quantity="1"
                                    data-product_id="<?php echo esc_attr( $related_id ); ?>"
                                    data-product_sku="<?php echo esc_attr( $related_product->get_sku() ); ?>"
                                    class="<?php echo esc_attr( $button_class ); ?>"
                                    aria-label="<?php echo esc_attr( $related_product->add_to_cart_description() ); ?>"
                                    rel="nofollow"
                                >
                                    <?php echo esc_html( $related_product->add_to_cart_text() ); ?>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

add_shortcode( 'product_gallery_block', 'wm_render_product_gallery' );
add_shortcode( 'product_intro_block', 'wm_render_product_intro' );
add_shortcode( 'product_specs_block', 'wm_render_product_specs' );
add_shortcode( 'product_purchase_block', 'wm_render_product_purchase' );
add_shortcode( 'product_tabs_block', 'wm_render_product_tabs' );
add_shortcode( 'related_products_block', 'wm_render_related_products' );
add_shortcode( 'mobile_product_bottom_bar', 'wm_render_mobile_product_bottom_bar' );
add_shortcode( 'product_tax_summary', 'wm_render_product_specs' );
add_shortcode( 'product_stock_badge', 'wm_render_product_stock' );
add_shortcode( 'product_purchase_trust', 'wm_render_product_purchase_trust' );
add_shortcode( 'product_header_meta', 'wm_render_product_intro' );
add_shortcode(
    'product_primary_cat',
    function() {
        $product = wm_get_current_product();
        if ( ! $product ) {
            return '';
        }

        $cats = wm_get_product_category_terms( $product->get_id() );
        if ( empty( $cats ) ) {
            return '';
        }

        $cat = $cats[0];
        return '<a class="wm-product-intro__meta-item" href="' . esc_url( wm_term_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
    }
);
add_shortcode(
    'product_brand_badge',
    function() {
        $product = wm_get_current_product();
        if ( ! $product ) {
            return '';
        }

        $brands = wm_get_product_brand_terms( $product->get_id() );
        if ( empty( $brands ) ) {
            return '';
        }

        $brand = $brands[0];
        return '<a class="wm-product-intro__meta-item wm-product-intro__meta-item--brand" href="' . esc_url( wm_term_link( $brand ) ) . '">' . esc_html( $brand->name ) . '</a>';
    }
);
