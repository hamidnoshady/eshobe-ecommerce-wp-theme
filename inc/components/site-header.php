<?php
/**
 * Site header component.
 *
 * @package WM_Theme
 */

function wm_header_get_option( $key, $default = '' ) {
	return wm_get_option( $key, $default );
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

/**
 * Resolves the logo attachment ID to use for a given header context.
 *
 * 'mobile' context prefers `wm_header_logo_mobile`, falling back to the
 * desktop logo (and ultimately the WP custom logo) when not set.
 */
function wm_header_get_logo_attachment_id( $context = 'desktop' ) {
    if ( 'mobile' === $context ) {
        $mobile_logo = wm_header_get_option( 'wm_header_logo_mobile', '' );

        if ( is_array( $mobile_logo ) && ! empty( $mobile_logo['ID'] ) ) {
            return absint( $mobile_logo['ID'] );
        }

        if ( is_numeric( $mobile_logo ) ) {
            return absint( $mobile_logo );
        }
    }

    $logo = wm_header_get_option( 'wm_header_logo', '' );

    if ( is_array( $logo ) && ! empty( $logo['ID'] ) ) {
        return absint( $logo['ID'] );
    }

    if ( is_numeric( $logo ) ) {
        return absint( $logo );
    }

    $custom_logo_id = get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id ) {
        return absint( $custom_logo_id );
    }

    return 0;
}

function wm_header_get_logo_html( $context = 'desktop' ) {
    $attachment_id = wm_header_get_logo_attachment_id( $context );

    if ( ! $attachment_id ) {
        return '';
    }

    return wp_get_attachment_image(
        $attachment_id,
        'wm-header-logo',
        false,
        array(
            'class'         => 'wm-site-header__logo-image',
            'alt'           => wm_header_get_site_name(),
            'loading'       => 'eager',
            'fetchpriority' => 'high',
            'decoding'      => 'async',
        )
    );
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
        'wishlist' => '<svg class="wm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20.3 4.9 13.2a4.6 4.6 0 0 1 0-6.5 4.6 4.6 0 0 1 6.5 0l.6.6.6-.6a4.6 4.6 0 0 1 6.5 0 4.6 4.6 0 0 1 0 6.5Z"></path></svg>',
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

function wm_header_render_menu( $context = 'desktop' ) {
    $menu_location = has_nav_menu( 'primary' ) ? 'primary' : 'menu-1';

    if ( 'mobile' === $context && has_nav_menu( 'mobile' ) ) {
        $menu_location = 'mobile';
    }

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
    $site_name          = wm_header_get_site_name();
    $desktop_logo_id     = wm_header_get_logo_attachment_id( 'desktop' );
    $mobile_logo_id      = wm_header_get_logo_attachment_id( 'mobile' );
    $has_distinct_mobile = $mobile_logo_id && $mobile_logo_id !== $desktop_logo_id;
    $desktop_logo_html   = wm_header_get_logo_html( 'desktop' );
    $mobile_logo_html    = $has_distinct_mobile ? wm_header_get_logo_html( 'mobile' ) : '';
    $logo_html           = $desktop_logo_html ?: $mobile_logo_html;
    $show_search   = (bool) wm_header_get_option( 'wm_header_show_search', true );
    $show_account  = (bool) wm_header_get_option( 'wm_header_show_account', true );
    $show_cart     = (bool) wm_header_get_option( 'wm_header_show_cart', true );
    $show_wishlist = (bool) wm_header_get_option( 'wm_header_show_wishlist', true );
    $wishlist_url  = function_exists( 'wm_wishlist_page_url' ) ? wm_wishlist_page_url() : home_url( '/' );
    $sticky_class  = wm_header_get_option( 'wm_header_sticky_enabled', true ) ? ' is-sticky' : '';
    $cart_count    = wm_header_get_cart_count();

    ?>
    <header id="masthead" class="wm-site-header<?php echo esc_attr( $sticky_class ); ?>">
        <?php wm_header_render_topbar(); ?>

        <div class="wm-site-header__inner">
            <a class="wm-site-header__brand<?php echo $logo_html ? ' wm-site-header__brand--has-logo' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                <?php if ( $logo_html ) : ?>
                    <?php if ( $has_distinct_mobile && $mobile_logo_html ) : ?>
                        <span class="wm-site-header__logo wm-site-header__logo--desktop"><?php echo $desktop_logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <span class="wm-site-header__logo wm-site-header__logo--mobile"><?php echo $mobile_logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <?php else : ?>
                        <span class="wm-site-header__logo"><?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <?php endif; ?>
                    <span class="wm-site-header__site-name screen-reader-text"><?php echo esc_html( $site_name ); ?></span>
                <?php else : ?>
                    <span class="wm-site-header__site-name"><?php echo esc_html( $site_name ); ?></span>
                <?php endif; ?>
            </a>

            <button type="button" class="wm-site-header__tablet-toggle" aria-haspopup="dialog" aria-expanded="false" aria-controls="wm-tablet-nav-drawer" aria-label="<?php echo esc_attr__( 'منو', 'eshobe-ecommerce' ); ?>">
                <span class="wm-site-header__tablet-toggle-bar"></span>
                <span class="wm-site-header__tablet-toggle-bar"></span>
                <span class="wm-site-header__tablet-toggle-bar"></span>
            </button>

            <?php wm_header_render_menu(); ?>

            <div class="wm-site-header__actions">
                <?php if ( $show_search ) : ?>
                    <div class="wm-header-search">
                        <button class="wm-site-header__action wm-site-header__search-toggle" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="wm-search-modal" aria-label="<?php echo esc_attr__( 'جستجو', 'eshobe-ecommerce' ); ?>">
                            <span class="wm-site-header__action-icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="wm-site-header__action-text"><?php echo esc_html__( 'جستجو', 'eshobe-ecommerce' ); ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ( $show_account ) : ?>
                    <?php $wm_header_otp_active = ! is_user_logged_in(); ?>
                    <div class="wm-site-header__account-wrap">
                        <a class="wm-site-header__action wm-site-header__account" href="<?php echo esc_url( wm_header_get_account_url() ); ?>" aria-label="<?php echo esc_attr__( 'حساب کاربری', 'eshobe-ecommerce' ); ?>" data-wm-account-toggle aria-haspopup="true" aria-expanded="false">
                            <span class="wm-site-header__action-icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="wm-site-header__action-text"><?php echo esc_html__( 'حساب', 'eshobe-ecommerce' ); ?></span>
                        </a>
                        <div class="wm-account-dropdown" data-wm-account-dropdown>
                            <?php if ( $wm_header_otp_active ) : ?>
                                <strong class="wm-account-dropdown__title"><?php echo esc_html__( 'حساب کاربری', 'eshobe-ecommerce' ); ?></strong>
                                <p class="wm-notification-empty"><?php echo esc_html__( 'برای مشاهده اعلان‌ها، سفارش‌ها و علاقه‌مندی‌ها وارد شوید.', 'eshobe-ecommerce' ); ?></p>
                            <?php else : ?>
                                <strong class="wm-account-dropdown__title"><?php echo esc_html__( 'اعلان‌ها', 'eshobe-ecommerce' ); ?></strong>
                                <div class="wm-notification-list" data-wm-notification-list></div>
                            <?php endif; ?>

                            <?php if ( $show_wishlist ) : ?>
                                <a class="wm-account-dropdown__item" href="<?php echo esc_url( $wishlist_url ); ?>">
                                    <span class="wm-account-dropdown__item-icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'wishlist' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                    <span class="wm-account-dropdown__item-text"><?php echo esc_html__( 'علاقه‌مندی‌ها', 'eshobe-ecommerce' ); ?></span>
                                    <span class="wm-account-dropdown__item-count" data-wm-wishlist-count hidden>0</span>
                                </a>
                            <?php endif; ?>

                            <?php if ( $wm_header_otp_active ) : ?>
                                <a class="wm-account-dropdown__link wm-account-dropdown__link--login" href="<?php echo esc_url( wm_header_get_account_url() ); ?>" data-wm-otp-trigger><?php echo esc_html__( 'ورود / ثبت‌نام', 'eshobe-ecommerce' ); ?></a>
                            <?php else : ?>
                                <a class="wm-account-dropdown__link" href="<?php echo esc_url( wm_header_get_account_url() ); ?>"><?php echo esc_html__( 'مشاهده حساب کاربری', 'eshobe-ecommerce' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $show_wishlist ) : ?>
                    <a class="wm-site-header__action wm-site-header__wishlist" href="<?php echo esc_url( $wishlist_url ); ?>" aria-label="<?php echo esc_attr__( 'علاقه‌مندی‌ها', 'eshobe-ecommerce' ); ?>">
                        <span class="wm-site-header__action-icon" aria-hidden="true"><?php echo wm_header_icon_svg( 'wishlist' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <span class="wm-site-header__action-text"><?php echo esc_html__( 'علاقه‌مندی‌ها', 'eshobe-ecommerce' ); ?></span>
                        <span class="wm-site-header__wishlist-count" data-wm-wishlist-count hidden>0</span>
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
                        <kbd class="wm-search-modal__hint" aria-hidden="true">/</kbd>
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

                        <div class="wm-search-modal__recent" data-search-recent hidden>
                            <h3 class="wm-search-modal__section-title"><?php echo esc_html__( 'جستجوهای اخیر', 'eshobe-ecommerce' ); ?></h3>
                            <div class="wm-search-modal__recent-chips" data-search-recent-chips></div>
                        </div>

                        <p class="wm-search-modal__empty" data-search-empty hidden>
                            <?php echo esc_html__( 'نتیجه‌ای یافت نشد.', 'eshobe-ecommerce' ); ?>
                            <a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"><?php echo esc_html__( 'مشاهده همه محصولات', 'eshobe-ecommerce' ); ?></a>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="wm-tablet-nav-drawer__backdrop" data-tablet-nav-close hidden></div>
        <div id="wm-tablet-nav-drawer" class="wm-tablet-nav-drawer" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'منو', 'eshobe-ecommerce' ); ?>" hidden>
            <div class="wm-tablet-nav-drawer__head">
                <strong><?php echo esc_html__( 'منو', 'eshobe-ecommerce' ); ?></strong>
                <button type="button" class="wm-tablet-nav-drawer__close" data-tablet-nav-close aria-label="<?php echo esc_attr__( 'بستن', 'eshobe-ecommerce' ); ?>">&times;</button>
            </div>
            <div class="wm-tablet-nav-drawer__body">
                <?php wm_header_render_menu( 'mobile' ); ?>
            </div>
        </div>
    </header>
    <?php
}
