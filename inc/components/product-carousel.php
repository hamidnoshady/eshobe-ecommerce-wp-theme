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
        )
    );

    $section_id = 'wm-product-carousel-' . wp_unique_id();

    ob_start();
    ?>
    <section class="wm-product-carousel <?php echo esc_attr( $args['class'] ); ?>" id="<?php echo esc_attr( $section_id ); ?>" data-product-carousel>
        <div class="wm-home-section__header">
            <div>
                <?php if ( $args['title'] ) : ?>
                    <h2 class="wm-home-section__title"><?php echo esc_html( $args['title'] ); ?></h2>
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

