<?php
/**
 * Home hero slider.
 *
 * @package WM_Theme
 */

$content_slides = wm_home_get_valid_items(
    'home_hero_slides',
    function( $slide ) {
        return ! empty( $slide['slide_enabled'] ) && ( ! empty( $slide['slide_title'] ) || ! empty( $slide['slide_image_desktop'] ) );
    }
);

$image_slides = wm_home_get_valid_items(
    'home_hero_image_slides',
    function( $slide ) {
        return ! empty( $slide['image_slide_enabled'] ) && ( ! empty( $slide['image_slide_image_desktop'] ) || ! empty( $slide['image_slide_image_mobile'] ) );
    }
);

$slides = array();

foreach ( $content_slides as $slide ) {
    $slide['type'] = 'content';
    $slides[]      = $slide;
}

foreach ( $image_slides as $slide ) {
    $slide['type'] = 'image';
    $slides[]      = $slide;
}

if ( empty( $slides ) ) {
    $slides = array(
        array(
            'type'                 => 'content',
            'slide_enabled'        => true,
            'slide_eyebrow'        => 'مجموعه ویژه',
            'slide_title'          => 'محصولات منتخب برای امروز',
            'slide_subtitle'       => 'مجموعه‌ای منتخب با تجربه خرید تمیز و سریع.',
            'slide_primary_text'   => 'مشاهده فروشگاه',
            'slide_primary_url'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
            'slide_secondary_text' => 'پرفروش‌ها',
            'slide_secondary_url'  => '#wm-home-bestsellers',
        ),
    );
}

$has_multiple = count( $slides ) > 1;

$autoplay_enabled  = (bool) wm_home_get_option( 'home_hero_autoplay', false );
$autoplay_interval = $autoplay_enabled ? max( 1500, absint( wm_home_get_option( 'home_hero_autoplay_interval', 5000 ) ) ) : 0;
$autoplay_pause    = $autoplay_enabled ? (bool) wm_home_get_option( 'home_hero_autoplay_pause_hover', true ) : false;
?>
<section class="wm-home-hero wm-section-decor wm-section-decor--hero wm-section-decor--dots wm-section-decor--ring" data-home-hero data-autoplay="<?php echo esc_attr( $autoplay_interval ); ?>" data-autoplay-pause-hover="<?php echo $autoplay_pause ? '1' : '0'; ?>">
    <div class="wm-home-hero__track">
        <?php foreach ( $slides as $index => $slide ) : ?>
            <?php if ( 'image' === ( $slide['type'] ?? 'content' ) ) : ?>
                <?php
                $image_url     = ! empty( $slide['image_slide_url'] ) ? $slide['image_slide_url'] : '';
                $desktop_image = ! empty( $slide['image_slide_image_desktop'] ) ? $slide['image_slide_image_desktop'] : '';
                $mobile_image  = ! empty( $slide['image_slide_image_mobile'] ) ? $slide['image_slide_image_mobile'] : $desktop_image;
                ?>
                <article class="wm-home-hero__slide wm-home-hero__slide--image <?php echo 0 === $index ? 'is-active' : ''; ?>" data-home-hero-slide>
                    <?php if ( $image_url ) : ?>
                        <a class="wm-home-hero__image-link" href="<?php echo esc_url( $image_url ); ?>">
                    <?php endif; ?>
                    <?php if ( $desktop_image || $mobile_image ) : ?>
                        <picture>
                            <?php if ( $mobile_image ) : ?>
                                <source media="(max-width: 767px)" srcset="<?php echo esc_url( wm_home_get_image_url( $mobile_image, 'full' ) ); ?>">
                            <?php endif; ?>
                            <?php echo wm_home_get_image_html( $desktop_image, 'full', array( 'loading' => 0 === $index ? 'eager' : 'lazy', 'alt' => get_bloginfo( 'name' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </picture>
                    <?php endif; ?>
                    <?php if ( $image_url ) : ?>
                        </a>
                    <?php endif; ?>
                </article>
            <?php else : ?>
                <?php
                $desktop_image = ! empty( $slide['slide_image_desktop'] ) ? $slide['slide_image_desktop'] : '';
                $mobile_image  = ! empty( $slide['slide_image_mobile'] ) ? $slide['slide_image_mobile'] : $desktop_image;
                ?>
                <article class="wm-home-hero__slide <?php echo 0 === $index ? 'is-active' : ''; ?>" data-home-hero-slide>
                    <div class="wm-home-hero__content">
                        <?php if ( ! empty( $slide['slide_eyebrow'] ) ) : ?>
                            <span class="wm-home-hero__eyebrow"><?php echo esc_html( $slide['slide_eyebrow'] ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $slide['slide_title'] ) ) : ?>
                            <?php if ( 0 === $index ) : ?>
                                <h1 class="wm-home-hero__title"><?php echo esc_html( $slide['slide_title'] ); ?></h1>
                            <?php else : ?>
                                <p class="wm-home-hero__title"><?php echo esc_html( $slide['slide_title'] ); ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ( ! empty( $slide['slide_subtitle'] ) ) : ?>
                            <p class="wm-home-hero__subtitle"><?php echo esc_html( $slide['slide_subtitle'] ); ?></p>
                        <?php endif; ?>
                        <div class="wm-home-hero__actions">
                            <?php if ( ! empty( $slide['slide_primary_text'] ) && ! empty( $slide['slide_primary_url'] ) ) : ?>
                                <a class="wm-home-button wm-home-button--primary" href="<?php echo esc_url( $slide['slide_primary_url'] ); ?>"><?php echo esc_html( $slide['slide_primary_text'] ); ?></a>
                            <?php endif; ?>
                            <?php if ( ! empty( $slide['slide_secondary_text'] ) && ! empty( $slide['slide_secondary_url'] ) ) : ?>
                                <a class="wm-home-button wm-home-button--outline" href="<?php echo esc_url( $slide['slide_secondary_url'] ); ?>"><?php echo esc_html( $slide['slide_secondary_text'] ); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="wm-home-hero__visual">
                        <?php if ( $desktop_image ) : ?>
                            <picture>
                                <?php if ( $mobile_image ) : ?>
                                    <source media="(max-width: 767px)" srcset="<?php echo esc_url( wm_home_get_image_url( $mobile_image, 'large' ) ); ?>">
                                <?php endif; ?>
                                <?php echo wm_home_get_image_html( $desktop_image, 'large', array( 'loading' => 0 === $index ? 'eager' : 'lazy', 'alt' => ! empty( $slide['slide_title'] ) ? $slide['slide_title'] : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </picture>
                        <?php else : ?>
                            <div class="wm-home-hero__placeholder" aria-hidden="true"></div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php if ( $has_multiple && $autoplay_interval > 0 ) : ?>
        <div class="wm-home-hero__progress" data-home-hero-progress aria-hidden="true"><span class="wm-home-hero__progress-bar"></span></div>
    <?php endif; ?>
    <?php if ( $has_multiple ) : ?>
        <div class="wm-home-hero__nav">
            <button type="button" class="wm-home-hero__arrow" data-home-hero-prev aria-label="<?php esc_attr_e( 'Previous slide', 'eshobe-ecommerce' ); ?>">‹</button>
            <div class="wm-home-hero__dots">
                <?php foreach ( $slides as $index => $slide ) : ?>
                    <button type="button" class="wm-home-hero__dot <?php echo 0 === $index ? 'is-active' : ''; ?>" data-home-hero-dot="<?php echo esc_attr( $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Show slide %d', 'eshobe-ecommerce' ), $index + 1 ) ); ?>"></button>
                <?php endforeach; ?>
            </div>
            <button type="button" class="wm-home-hero__arrow" data-home-hero-next aria-label="<?php esc_attr_e( 'Next slide', 'eshobe-ecommerce' ); ?>">›</button>
        </div>
    <?php endif; ?>
</section>
