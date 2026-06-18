<?php
/**
 * Site header component.
 *
 * @package WM_Theme
 */

function wm_header_get_option( $key, $default = '' ) {
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $key, 'option' );
        if ( null !== $value && '' !== $value ) {
            return $value;
        }
    }

    return $default;
}

function wm_header_default_topbar_items() {
    return array(
        array( 'item_enabled' => 1, 'item_text' => 'ضمانت اصالت کالا', 'item_url' => '' ),
        array( 'item_enabled' => 1, 'item_text' => 'ارسال سریع', 'item_url' => '' ),
        array( 'item_enabled' => 1, 'item_text' => 'پرداخت امن', 'item_url' => '' ),
        array( 'item_enabled' => 1, 'item_text' => 'پشتیبانی قبل از خرید', 'item_url' => '' ),
    );
}

function wm_header_get_topbar_items() {
    $items = wm_header_get_option( 'wm_header_topbar_items', array() );
    $items = array_values(
        array_filter(
            (array) $items,
            function( $item ) {
                return ! empty( $item['item_enabled'] ) && ! empty( $item['item_text'] );
            }
        )
    );

    return $items ? $items : wm_header_default_topbar_items();
}

function wm_header_get_site_name() {
    $site_name = wm_header_get_option( 'wm_header_site_name', '' );
    $site_name = $site_name ? $site_name : get_bloginfo( 'name' );

    return $site_name ? $site_name : 'فروشگاه آنلاین';
}

function wm_header_get_logo_html() {
    $logo = wm_header_get_option( 'wm_header_logo', '' );

    if ( is_array( $logo ) && ! empty( $logo['ID'] ) ) {
        return wp_get_attachment_image(
            absint( $logo['ID'] ),
            'thumbnail',
            false,
            array(
                'class' => 'wm-site-header__logo-image',
                'alt'   => wm_header_get_site_name(),
            )
        );
    }

    if ( is_numeric( $logo ) ) {
        return wp_get_attachment_image(
            absint( $logo ),
            'thumbnail',
            false,
            array(
                'class' => 'wm-site-header__logo-image',
                'alt'   => wm_header_get_site_name(),
            )
        );
    }

    $custom_logo_id = get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id ) {
        return wp_get_attachment_image(
            absint( $custom_logo_id ),
            'thumbnail',
            false,
            array(
                'class' => 'wm-site-header__logo-image',
                'alt'   => wm_header_get_site_name(),
            )
        );
    }

    return '';
}

/**
 * Inline SVG icons used for header action buttons.
 *
 * Returns an empty string for unknown icon names.
 */
function wm_header_icon_svg( $name ) {
    $icons = array(
        'search'  => '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"></circle><line x1="20" y1="20" x2="16.2" y2="16.2"></line></svg>',
        'account' => '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3.6"></circle><path d="M4.5 19.2c1.2-3.2 4.2-5.2 7.5-5.2s6.3 2 7.5 5.2"></path></svg>',
        'cart'    => '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3.5 6h2l1.6 10.2a1.8 1.8 0 0 0 1.8 1.5h8.4a1.8 1.8 0 0 0 1.78-1.52L20.5 9H7.1"></path><circle cx="9.5" cy="20" r="1.3"></circle><circle cx="17" cy="20" r="1.3"></circle></svg>',
    );

    return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

function wm_header_get_account_url() {
    if ( function_exists( 'wc_get_page_permalink' ) ) {
        return wc_get_page_permalink( 'myaccount' );
    }

    return wp_login_url();
}

function wm_header_get_cart_url() {
    if ( function_exists( 'wc_get_cart_url' ) ) {
        return wc_get_cart_url();
    }

    return home_url( '/' );
}

function wm_header_get_cart_count() {
    if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
        return absint( WC()->cart->get_cart_contents_count() );
    }

    return 0;
}

function wm_header_render_topbar() {
    if ( ! wm_header_get_option( 'wm_header_topbar_enabled', true ) ) {
        return;
    }

    $items = wm_header_get_topbar_items();
    if ( ! $items ) {
        return;
    }

    ?>
    <div class="wm-header-topbar">
        <div class="wm-header-topbar__inner">
            <?php foreach ( $items as $item ) : ?>
                <?php $url = ! empty( $item['item_url'] ) ? $item['item_url'] : ''; ?>
                <span class="wm-header-topbar__item">
                    <?php if ( $url ) : ?>
                        <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $item['item_text'] ); ?></a>
                    <?php else : ?>
                        <?php echo esc_html( $item['item_text'] ); ?>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function wm_header_render_menu() {
    $menu_location = has_nav_menu( 'primary' ) ? 'primary' : 'menu-1';

    if ( has_nav_menu( $menu_location ) ) {
        wp_nav_menu(
            array(
                'theme_location'  => $menu_location,
                'container'       => 'nav',
                'container_class' => 'wm-site-header__nav',
                'menu_class'      => 'wm-site-header__menu',
                'fallback_cb'     => false,
                'depth'           => 3,
            )
        );
        return;
    }

    ?>
    <nav class="wm-site-header__nav" aria-label="<?php echo esc_attr__( 'Primary Menu', 'eshobe-ecommerce' ); ?>">
        <ul class="wm-site-header__menu">
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'خانه', 'eshobe-ecommerce' ); ?></a></li>
            <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
                <li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo esc_html__( 'فروشگاه', 'eshobe-ecommerce' ); ?></a></li>
            <?php endif; ?>
            <li><a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php echo esc_html__( 'تماس با ما', 'eshobe-ecommerce' ); ?></a></li>
        </ul>
    </nav>
    <?php
}

function wm_render_site_header() {
    $site_name     = wm_header_get_site_name();
    $logo_html     = wm_header_get_logo_html();
    $show_search   = (bool) wm_header_get_option( 'wm_header_show_search', true );
    $show_account  = (bool) wm_header_get_option( 'wm_header_show_account', true );
    $show_cart     = (bool) wm_header_get_option( 'wm_header_show_cart', true );
    $sticky_class  = wm_header_get_option( 'wm_header_sticky_enabled', true ) ? ' is-sticky' : '';
    $cart_count    = wm_header_get_cart_count();

    ?>
    <header id="masthead" class="wm-site-header<?php echo esc_attr( $sticky_class ); ?>">
        <?php wm_header_render_topbar(); ?>

        <div class="wm-site-header__inner">
            <a class="wm-site-header__brand<?php echo $logo_html ? ' wm-site-header__brand--has-logo' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                <?php if ( $logo_html ) : ?>
                    <span class="wm-site-header__logo"><?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <span class="wm-site-header__site-name screen-reader-text"><?php echo esc_html( $site_name ); ?></span>
                <?php else : ?>
                    <span class="wm-site-header__site-name"><?php echo esc_html( $site_name ); ?></span>
                <?php endif; ?>
            </a>

            <?php wm_header_render_menu(); ?>

            <div class="wm-site-header__actions">
                <?php if ( $show_search ) : ?>
                    <div class="wm-header-search">
                        <button class="wm-site-header__action wm-site-header__search-toggle" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="wm-search-modal">
                            <span class="wm-site-header__action-icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="wm-site-header__action-text"><?php echo esc_html__( 'جستجو', 'eshobe-ecommerce' ); ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ( $show_account ) : ?>
                    <?php $wm_header_otp_active = ! is_user_logged_in(); ?>
                    <a class="wm-site-header__action wm-site-header__account" href="<?php echo esc_url( wm_header_get_account_url() ); ?>" aria-label="<?php echo esc_attr__( 'حساب کاربری', 'eshobe-ecommerce' ); ?>"<?php echo $wm_header_otp_active ? ' data-wm-otp-trigger data-wm-otp-redirect="account"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                        <span class="wm-site-header__action-icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <span class="wm-site-header__action-text"><?php echo esc_html__( 'حساب', 'eshobe-ecommerce' ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( $show_cart ) : ?>
                    <a class="wm-site-header__action wm-site-header__cart" href="<?php echo esc_url( wm_header_get_cart_url() ); ?>" aria-label="<?php echo esc_attr__( 'سبد خرید', 'eshobe-ecommerce' ); ?>" data-wm-cart-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="wm-cart-drawer">
                        <span class="wm-site-header__action-icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <span class="wm-site-header__action-text"><?php echo esc_html__( 'سبد خرید', 'eshobe-ecommerce' ); ?></span>
                        <span class="wm-site-header__cart-count<?php echo $cart_count > 0 ? '' : ' wm-site-header__cart-count--hidden'; ?>" data-wm-cart-count><?php echo esc_html( number_format_i18n( $cart_count ) ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        if ( function_exists( 'wm_render_header_mega_menus' ) ) {
            wm_render_header_mega_menus();
        }
        ?>

        <?php if ( $show_search ) : ?>
            <div id="wm-search-modal" class="wm-search-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'جستجو', 'eshobe-ecommerce' ); ?>" hidden>
                <div class="wm-search-modal__backdrop" data-search-modal-close></div>
                <div class="wm-search-modal__panel">
                    <form role="search" method="get" class="wm-search-modal__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <label class="screen-reader-text" for="wm-search-modal-field"><?php echo esc_html__( 'جستجو', 'eshobe-ecommerce' ); ?></label>
                        <span class="wm-search-modal__icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <input
                            id="wm-search-modal-field"
                            class="wm-search-modal__field"
                            type="search"
                            name="s"
                            autocomplete="off"
                            placeholder="<?php echo esc_attr( wm_search_get_option( 'wm_search_placeholder', 'جستجوی محصول، برند یا دسته...' ) ); ?>"
                            value="<?php echo esc_attr( get_search_query() ); ?>"
                        >
                        <?php if ( function_exists( 'wc_get_product_types' ) ) : ?>
                            <input type="hidden" name="post_type" value="product">
                        <?php endif; ?>
                        <button type="button" class="wm-search-modal__close" data-search-modal-close aria-label="<?php echo esc_attr__( 'بستن', 'eshobe-ecommerce' ); ?>">×</button>
                    </form>

                    <div class="wm-search-modal__body">
                        <?php
                        $suggested_products = wm_search_get_suggested_products();
                        $suggested_label    = wm_search_get_option( 'wm_search_suggested_label', 'پیشنهاد ویژه' );
                        ?>
                        <div class="wm-search-modal__suggestions" data-search-suggestions <?php echo empty( $suggested_products ) ? 'hidden' : ''; ?>>
                            <?php if ( ! empty( $suggested_products ) ) : ?>
                                <h3 class="wm-search-modal__section-title"><?php echo esc_html( $suggested_label ); ?></h3>
                                <ul class="wm-search-modal__results">
                                    <?php foreach ( $suggested_products as $product ) : ?>
                                        <?php echo wm_render_search_result_row( $product ); ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="wm-search-modal__results-wrap" data-search-results hidden>
                            <ul class="wm-search-modal__results" data-search-results-list></ul>
                            <a href="#" class="wm-search-modal__view-all" data-search-view-all hidden><?php echo esc_html__( 'مشاهده همه نتایج', 'eshobe-ecommerce' ); ?></a>
                        </div>

                        <p class="wm-search-modal__empty" data-search-empty hidden><?php echo esc_html__( 'نتیجه‌ای یافت نشد.', 'eshobe-ecommerce' ); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </header>
    <?php
}
