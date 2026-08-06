<?php
/**
 * Shared post carousel for blog posts.
 *
 * @package WM_Theme
 */

function wm_render_post_carousel( $posts, $args = array() ) {
    $posts = array_filter( (array) $posts );
    if ( empty( $posts ) ) {
        return '';
    }

    $args = wp_parse_args(
        $args,
        array(
            'title'          => '',
            'subtitle'       => '',
            'class'          => '',
            'view_all'       => '',
            'columnsDesktop' => 4,
            'columnsTablet'  => 3,
            'columnsMobile'  => 1,
        )
    );

    $section_id = 'wm-post-carousel-' . wp_unique_id();
    $style = sprintf(
        '--wm-cols-desktop: %d; --wm-cols-tablet: %d; --wm-cols-mobile: %d;',
        $args['columnsDesktop'],
        $args['columnsTablet'],
        $args['columnsMobile']
    );

    ob_start();
    ?>
    <section class="wm-product-carousel <?php echo esc_attr( $args['class'] ); ?>" id="<?php echo esc_attr( $section_id ); ?>" data-post-carousel style="<?php echo esc_attr( $style ); ?>">
        <div class="wm-home-section__header">
            <div>
                <?php if ( $args['title'] ) : ?>
                    <h2 class="wm-home-section__title"><?php echo esc_html( $args['title'] ); ?></h2>
                <?php endif; ?>
                <?php if ( $args['subtitle'] ) : ?>
                    <p class="wm-home-section__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="wm-home-section__actions" style="display: flex; gap: 12px; align-items: center;">
                <?php if ( ! empty( $args['view_all'] ) ) : ?>
                    <a href="<?php echo esc_url( $args['view_all'] ); ?>" class="wm-home-section__link">
                        <?php esc_html_e( 'مشاهده همه', 'eshobe-ecommerce' ); ?>
                        <span aria-hidden="true">&larr;</span>
                    </a>
                <?php endif; ?>

                <div class="wm-product-carousel__controls">
                    <button class="wm-product-carousel__arrow" type="button" data-carousel-direction="prev" aria-label="<?php esc_attr_e( 'قبلی', 'eshobe-ecommerce' ); ?>"><span aria-hidden="true">‹</span></button>
                    <button class="wm-product-carousel__arrow" type="button" data-carousel-direction="next" aria-label="<?php esc_attr_e( 'بعدی', 'eshobe-ecommerce' ); ?>"><span aria-hidden="true">›</span></button>
                </div>
            </div>
        </div>
        <div class="wm-product-carousel__viewport">
            <div class="wm-product-carousel__track wm-post-carousel__track" tabindex="0">
                <?php foreach ( $posts as $post ) : ?>
                    <?php echo wm_render_post_card( $post->ID, array( 'class' => 'wm-product-carousel__item' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
