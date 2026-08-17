<?php
/**
 * Shared home product card.
 *
 * @package WM_Theme
 */

function wm_resolve_product( $product ) {
    if ( is_numeric( $product ) && function_exists( 'wc_get_product' ) ) {
        $product = wc_get_product( absint( $product ) );
    }

    if ( ! $product instanceof WC_Product ) {
        return null;
    }

    return $product;
}

function wm_get_product_card_default_args( $args ) {
    return wp_parse_args(
        $args,
        array(
            'class'              => '',
            'context'            => '',
            'enable_hover_image' => true,
            'ajax_add_to_cart'   => true,
            'fetchpriority'      => '',
        )
    );
}

function wm_render_product_card( $product, $args = array() ) {
    $product = wm_resolve_product( $product );

    if ( ! $product ) {
        return '';
    }

    $args = wm_get_product_card_default_args( $args );

    $image_id     = $product->get_image_id();
    $gallery_ids  = $product->get_gallery_image_ids();
    $secondary_id = ! empty( $gallery_ids ) ? absint( $gallery_ids[0] ) : 0;
    $has_hover    = ! empty( $args['enable_hover_image'] ) && $secondary_id && $secondary_id !== $image_id;
    $permalink    = get_permalink( $product->get_id() );
    $title        = $product->get_name();
    $classes      = trim( 'wm-product-card ' . $args['class'] . ( $args['context'] ? ' wm-product-card--' . sanitize_html_class( $args['context'] ) : '' ) . ( $has_hover ? ' wm-product-card--has-hover-image' : '' ) );
    $image_attrs  = array(
        'class'   => 'wm-product-card__image wm-product-card__image-main',
        'alt'     => $title,
        'loading' => 'lazy',
    );
    if ( ! empty( $args['fetchpriority'] ) ) {
        $image_attrs['fetchpriority'] = sanitize_key( $args['fetchpriority'] );
    }

    $button_class = implode(
        ' ',
        array_filter(
            array(
                'wm-product-card__button',
                'button',
                ! empty( $args['ajax_add_to_cart'] ) && $product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '',
                $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
                'product_type_' . $product->get_type(),
            )
        )
    );

    ob_start();
    ?>
    <article class="<?php echo esc_attr( $classes ); ?>">
        <a class="wm-product-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
            <?php if ( function_exists( 'wm_marketing_get_sale_badge_html' ) ) { echo wm_marketing_get_sale_badge_html( $product ); } ?>
            <?php
            if ( $image_id ) {
                echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, $image_attrs );
            } elseif ( function_exists( 'wc_placeholder_img' ) ) {
                echo wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'wm-product-card__image wm-product-card__image-main' ) );
            }
            ?>
            <?php if ( $has_hover ) : ?>
                <?php echo wp_get_attachment_image( $secondary_id, 'woocommerce_thumbnail', false, array( 'class' => 'wm-product-card__image wm-product-card__image-hover', 'alt' => $title, 'loading' => 'lazy' ) ); ?>
            <?php endif; ?>
        </a>
        <h3 class="wm-product-card__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>
        <?php if ( $product->get_price_html() ) : ?>
            <div class="wm-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
        <?php endif; ?>
        <div class="wm-product-card__actions">
            <a
                href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
                data-quantity="1"
                data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                class="<?php echo esc_attr( $button_class ); ?>"
                aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>"
                rel="nofollow"
            >
                <?php echo esc_html( $product->add_to_cart_text() ); ?>
            </a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function wm_render_search_result_row( $product ) {
    $product = wm_resolve_product( $product );

    if ( ! $product ) {
        return '';
    }

    $image_id  = $product->get_image_id();
    $permalink = get_permalink( $product->get_id() );
    $title     = $product->get_name();

    $brand = '';
    $terms = wm_get_product_brand_terms( $product->get_id() );
    if ( ! empty( $terms ) ) {
        $brand = $terms[0]->name;
    } else {
        $cat_terms = wm_get_product_category_terms( $product->get_id() );
        if ( ! empty( $cat_terms ) ) {
            $brand = $cat_terms[0]->name;
        }
    }

    ob_start();
    ?>
    <li class="wm-search-result">
        <a class="wm-search-result__link" href="<?php echo esc_url( $permalink ); ?>">
            <span class="wm-search-result__media">
                <?php
                if ( $image_id ) {
                    echo wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'wm-search-result__image', 'alt' => $title, 'loading' => 'lazy' ) );
                } elseif ( function_exists( 'wc_placeholder_img' ) ) {
                    echo wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'wm-search-result__image' ) );
                }
                ?>
            </span>
            <span class="wm-search-result__info">
                <span class="wm-search-result__title"><?php echo esc_html( $title ); ?></span>
                <?php if ( $brand ) : ?>
                    <span class="wm-search-result__brand"><?php echo esc_html( $brand ); ?></span>
                <?php endif; ?>
            </span>
            <?php if ( $product->get_price_html() ) : ?>
                <span class="wm-search-result__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php
    return ob_get_clean();
}
