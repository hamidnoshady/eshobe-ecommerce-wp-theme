<?php
/**
 * Site footer component.
 *
 * @package WM_Theme
 */

function wm_footer_get_option( $key, $default = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $key, 'option' );
        if ( null !== $value && false !== $value && '' !== $value ) {
            return $value;
        }
    }

    return $default;
}

function wm_footer_default_texts() {
    $site_name = get_bloginfo( 'name' );

    return array(
        'title'       => $site_name ? $site_name : __( 'فروشگاه آنلاین', 'watchmid' ),
        'description' => __( 'انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا و ارسال سریع.', 'watchmid' ),
        'copyright'   => sprintf(
            /* translators: 1: year, 2: site name. */
            __( '© %1$s %2$s. کلیه حقوق محفوظ است.', 'watchmid' ),
            date_i18n( 'Y' ),
            $site_name ? $site_name : __( 'فروشگاه آنلاین', 'watchmid' )
        ),
        'developer'   => '',
    );
}

function wm_footer_get_fallback_links() {
    return array(
        array( 'label' => __( 'درباره ما', 'watchmid' ), 'url' => home_url( '/about-us/' ) ),
        array( 'label' => __( 'تماس با ما', 'watchmid' ), 'url' => home_url( '/contact-us/' ) ),
        array( 'label' => __( 'راهنمای خرید', 'watchmid' ), 'url' => home_url( '/buying-guide/' ) ),
        array( 'label' => __( 'پیگیری سفارش', 'watchmid' ), 'url' => home_url( '/order-tracking/' ) ),
    );
}

function wm_footer_render_links() {
    if ( has_nav_menu( 'footer' ) ) {
        wp_nav_menu(
            array(
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'wm-site-footer__links',
                'fallback_cb'    => false,
                'depth'          => 1,
            )
        );
        return;
    }

    ?>
    <ul class="wm-site-footer__links">
        <?php foreach ( wm_footer_get_fallback_links() as $link ) : ?>
            <li>
                <a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function wm_footer_get_badge_image_html( $image, $title = '' ) {
    if ( empty( $image ) ) {
        return '';
    }

    if ( is_array( $image ) && ! empty( $image['ID'] ) ) {
        return wp_get_attachment_image(
            absint( $image['ID'] ),
            'thumbnail',
            false,
            array(
                'alt'     => $title,
                'loading' => 'lazy',
            )
        );
    }

    if ( is_numeric( $image ) ) {
        return wp_get_attachment_image(
            absint( $image ),
            'thumbnail',
            false,
            array(
                'alt'     => $title,
                'loading' => 'lazy',
            )
        );
    }

    return '';
}

function wm_footer_render_badge( $badge ) {
    if ( empty( $badge['badge_enabled'] ) ) {
        return;
    }

    $title = ! empty( $badge['badge_title'] ) ? $badge['badge_title'] : '';
    $url   = ! empty( $badge['badge_url'] ) ? $badge['badge_url'] : '';
    $html  = '';

    if ( ! empty( $badge['badge_html_code'] ) ) {
        $html = wp_kses_post( $badge['badge_html_code'] );
    } else {
        $html = wm_footer_get_badge_image_html( $badge['badge_image'] ?? '', $title );
    }

    if ( ! $html && $title ) {
        $html = '<span>' . esc_html( $title ) . '</span>';
    }

    if ( ! $html ) {
        return;
    }

    ?>
    <div class="wm-site-footer__badge">
        <?php if ( $url ) : ?>
            <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="nofollow noopener" aria-label="<?php echo esc_attr( $title ); ?>">
                <?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </a>
        <?php else : ?>
            <?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php endif; ?>
    </div>
    <?php
}

function wm_footer_render_badges() {
    $badges = wm_footer_get_option( 'wm_footer_badges', array() );
    $badges = array_values(
        array_filter(
            (array) $badges,
            function( $badge ) {
                return ! empty( $badge['badge_enabled'] ) && ( ! empty( $badge['badge_html_code'] ) || ! empty( $badge['badge_image'] ) || ! empty( $badge['badge_title'] ) );
            }
        )
    );

    if ( empty( $badges ) ) {
        return;
    }

    ?>
    <div class="wm-site-footer__badges" aria-label="<?php echo esc_attr__( 'نمادهای اعتماد', 'watchmid' ); ?>">
        <?php foreach ( $badges as $badge ) : ?>
            <?php wm_footer_render_badge( $badge ); ?>
        <?php endforeach; ?>
    </div>
    <?php
}

function wm_render_site_footer() {
    $defaults      = wm_footer_default_texts();
    $title         = wm_footer_get_option( 'wm_footer_title', $defaults['title'] );
    $description   = wm_footer_get_option( 'wm_footer_description', $defaults['description'] );
    $copyright     = wm_footer_get_option( 'wm_footer_copyright', $defaults['copyright'] );
    $developer     = wm_footer_get_option( 'wm_footer_developer_text', $defaults['developer'] );
    $developer_url = wm_footer_get_option( 'wm_footer_developer_url', '' );

    ?>
    <footer id="colophon" class="wm-site-footer wm-section-decor wm-section-decor--footer">
        <div class="wm-site-footer__inner">
            <div class="wm-site-footer__content">
                <div class="wm-site-footer__brand">
                    <?php if ( $title ) : ?>
                        <h2 class="wm-site-footer__title"><?php echo esc_html( $title ); ?></h2>
                    <?php endif; ?>

                    <?php if ( $description ) : ?>
                        <p class="wm-site-footer__text"><?php echo esc_html( $description ); ?></p>
                    <?php endif; ?>

                    <?php wm_footer_render_links(); ?>
                </div>

                <?php wm_footer_render_badges(); ?>
            </div>

            <div class="wm-site-footer__bottom">
                <?php if ( $copyright ) : ?>
                    <div class="wm-site-footer__copyright"><?php echo esc_html( $copyright ); ?></div>
                <?php endif; ?>

                <?php if ( $developer ) : ?>
                    <div class="wm-site-footer__developer">
                        <?php if ( $developer_url ) : ?>
                            <a href="<?php echo esc_url( $developer_url ); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html( $developer ); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html( $developer ); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>
    <?php
}
