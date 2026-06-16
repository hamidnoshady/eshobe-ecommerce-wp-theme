<?php
/**
 * Underscores Multipurpose theme functions and definitions.
 *
 * @package WM_Theme
 */

if ( ! defined( 'WATCHMID_VERSION' ) ) {
    define( 'WATCHMID_VERSION', '0.4.48' );
}

/**
 * Resolve the URI for a theme asset, preferring the minified build unless WP_DEBUG is enabled.
 *
 * @param string $relative_path Asset path relative to the theme root, e.g. 'assets/css/theme.css'.
 * @return string
 */
function wm_asset_uri( $relative_path ) {
    if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
        $min_relative_path = preg_replace( '/\.(css|js)$/', '.min.$1', $relative_path );
        if ( file_exists( get_theme_file_path( $min_relative_path ) ) ) {
            return get_theme_file_uri( $min_relative_path );
        }
    }

    return get_theme_file_uri( $relative_path );
}

/**
 * Resolve the cache-busting version for a theme asset based on its file modification time,
 * matching whichever variant (minified or source) wm_asset_uri() will actually serve.
 *
 * @param string $relative_path Asset path relative to the theme root, e.g. 'assets/css/components/cart.css'.
 * @return string|int
 */
function wm_asset_version( $relative_path ) {
    $resolved_path = get_theme_file_path( $relative_path );

    if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
        $min_relative_path = preg_replace( '/\.(css|js)$/', '.min.$1', $relative_path );
        $min_path          = get_theme_file_path( $min_relative_path );
        if ( file_exists( $min_path ) ) {
            $resolved_path = $min_path;
        }
    }

    return file_exists( $resolved_path ) ? filemtime( $resolved_path ) : WATCHMID_VERSION;
}

if ( ! function_exists( 'watchmid_setup' ) ) :
    function watchmid_setup() {
        load_theme_textdomain( 'watchmid', get_template_directory() . '/languages' );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'custom-logo', array(
            'height'      => 120,
            'width'       => 240,
            'flex-width'  => true,
            'flex-height' => true,
        ) );
        add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
        add_theme_support( 'customize-selective-refresh-widgets' );
        add_theme_support( 'align-wide' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'editor-styles' );
        add_editor_style( array( 'assets/css/fonts.css', 'assets/css/tokens.css', 'assets/css/editor-style.css' ) );

        add_theme_support( 'woocommerce' );
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );

        register_nav_menus( array(
            'menu-1' => esc_html__( 'Primary', 'watchmid' ),
            'primary' => esc_html__( 'Primary Menu', 'watchmid' ),
            'footer' => esc_html__( 'Footer Menu', 'watchmid' ),
        ) );
    }
endif;
add_action( 'after_setup_theme', 'watchmid_setup' );

function watchmid_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'watchmid_content_width', 1200 );
}
add_action( 'after_setup_theme', 'watchmid_content_width', 0 );

function watchmid_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Sidebar', 'watchmid' ),
        'id'            => 'sidebar-1',
        'description'   => esc_html__( 'Add widgets here.', 'watchmid' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'watchmid_widgets_init' );

function watchmid_scripts() {
    wp_enqueue_style( 'watchmid-fonts', wm_asset_uri( 'assets/css/fonts.css' ), array(), wm_asset_version( 'assets/css/fonts.css' ) );
    wp_enqueue_style( 'watchmid-tokens', wm_asset_uri( 'assets/css/tokens.css' ), array( 'watchmid-fonts' ), wm_asset_version( 'assets/css/tokens.css' ) );
    wp_enqueue_style( 'watchmid-style', wm_asset_uri( 'assets/css/theme.css' ), array( 'watchmid-tokens' ), wm_asset_version( 'assets/css/theme.css' ) );
    wp_enqueue_style( 'watchmid-header', wm_asset_uri( 'assets/css/components/header.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/header.css' ) );
    wp_enqueue_style( 'watchmid-footer', wm_asset_uri( 'assets/css/components/footer.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/footer.css' ) );
    wp_enqueue_style( 'watchmid-decorative-motifs', wm_asset_uri( 'assets/css/components/decorative-motifs.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/decorative-motifs.css' ) );
    wp_enqueue_style( 'watchmid-mobile-nav', wm_asset_uri( 'assets/css/components/mobile-nav.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/mobile-nav.css' ) );
    wp_add_inline_style( 'watchmid-style', watchmid_get_design_customizer_css() );
    wp_add_inline_style( 'watchmid-style', wm_get_design_tokens_css() );
    wp_enqueue_style( 'watchmid-product-components', wm_asset_uri( 'assets/css/product-components.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/product-components.css' ) );

    wp_enqueue_script( 'watchmid-navigation', wm_asset_uri( 'assets/js/navigation.js' ), array(), wm_asset_version( 'assets/js/navigation.js' ), true );
    wp_enqueue_script( 'watchmid-header', wm_asset_uri( 'assets/js/header.js' ), array(), wm_asset_version( 'assets/js/header.js' ), true );
    wp_enqueue_style( 'watchmid-search-modal', wm_asset_uri( 'assets/css/components/search-modal.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/search-modal.css' ) );
    wp_enqueue_script( 'watchmid-header-search', wm_asset_uri( 'assets/js/header-search.js' ), array(), wm_asset_version( 'assets/js/header-search.js' ), true );
    wp_localize_script(
        'watchmid-header-search',
        'wmSearchData',
        array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'wm_search_nonce' ),
            'searchUrl' => home_url( '/' ),
        )
    );
    wp_enqueue_script( 'watchmid-mobile-nav', wm_asset_uri( 'assets/js/mobile-nav.js' ), array(), wm_asset_version( 'assets/js/mobile-nav.js' ), true );

    if ( function_exists( 'WC' ) ) {
        wp_enqueue_style( 'watchmid-mini-cart', wm_asset_uri( 'assets/css/components/mini-cart.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/mini-cart.css' ) );
        wp_enqueue_script( 'watchmid-mini-cart', wm_asset_uri( 'assets/js/mini-cart.js' ), array( 'jquery' ), wm_asset_version( 'assets/js/mini-cart.js' ), true );
    }
    if ( is_front_page() || ( function_exists( 'wm_product_archive_is_context' ) && wm_product_archive_is_context() ) ) {
        wp_enqueue_style( 'watchmid-promo-banner', wm_asset_uri( 'assets/css/components/promo-banner.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/promo-banner.css' ) );
    }

    if ( is_front_page() ) {
        wp_enqueue_style( 'watchmid-home', wm_asset_uri( 'assets/css/pages/home.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/pages/home.css' ) );
        wp_enqueue_script( 'watchmid-home-hero-slider', wm_asset_uri( 'assets/js/home-hero-slider.js' ), array(), wm_asset_version( 'assets/js/home-hero-slider.js' ), true );
        wp_enqueue_script( 'watchmid-product-carousel', wm_asset_uri( 'assets/js/product-carousel.js' ), array(), wm_asset_version( 'assets/js/product-carousel.js' ), true );
    }

    if ( function_exists( 'wm_brand_archive_is_context' ) && wm_brand_archive_is_context() ) {
        wp_enqueue_style( 'watchmid-brand-archive', wm_asset_uri( 'assets/css/pages/brand-archive.css' ), array( 'watchmid-style', 'watchmid-decorative-motifs' ), wm_asset_version( 'assets/css/pages/brand-archive.css' ) );
        wp_enqueue_script( 'watchmid-brand-archive', wm_asset_uri( 'assets/js/brand-archive.js' ), array(), wm_asset_version( 'assets/js/brand-archive.js' ), true );
    }

    if ( function_exists( 'is_product' ) && is_product() ) {
        wp_enqueue_script( 'watchmid-product-gallery', wm_asset_uri( 'assets/js/product-gallery.js' ), array(), wm_asset_version( 'assets/js/product-gallery.js' ), true );
        wp_enqueue_script( 'watchmid-product-tabs', wm_asset_uri( 'assets/js/product-tabs.js' ), array(), wm_asset_version( 'assets/js/product-tabs.js' ), true );
        wp_enqueue_script( 'watchmid-related-products', wm_asset_uri( 'assets/js/related-products.js' ), array(), wm_asset_version( 'assets/js/related-products.js' ), true );
        wp_enqueue_script( 'watchmid-product-mobile', wm_asset_uri( 'assets/js/product-mobile.js' ), array(), wm_asset_version( 'assets/js/product-mobile.js' ), true );
    }

    if ( function_exists( 'wm_product_archive_is_context' ) && wm_product_archive_is_context() ) {
        wp_enqueue_style( 'watchmid-product-archive', wm_asset_uri( 'assets/css/components/product-archive.css' ), array( 'watchmid-style', 'watchmid-decorative-motifs' ), wm_asset_version( 'assets/css/components/product-archive.css' ) );
        wp_enqueue_script( 'watchmid-product-archive', wm_asset_uri( 'assets/js/product-archive.js' ), array(), wm_asset_version( 'assets/js/product-archive.js' ), true );
    }

    if ( function_exists( 'is_cart' ) && is_cart() ) {
        wp_enqueue_style( 'watchmid-cart', wm_asset_uri( 'assets/css/components/cart.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/cart.css' ) );
    }

    if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
        wp_enqueue_style( 'watchmid-checkout', wm_asset_uri( 'assets/css/components/checkout.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/checkout.css' ) );
        wp_enqueue_script( 'watchmid-checkout', wm_asset_uri( 'assets/js/checkout.js' ), array(), wm_asset_version( 'assets/js/checkout.js' ), true );
    }

    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        wp_enqueue_style( 'watchmid-myaccount', wm_asset_uri( 'assets/css/components/myaccount.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/myaccount.css' ) );
    }

    if ( function_exists( 'wm_technical_otp_enabled' ) && wm_technical_otp_enabled() && ! is_user_logged_in() ) {
        wp_enqueue_style( 'watchmid-otp-modal', wm_asset_uri( 'assets/css/components/otp-modal.css' ), array( 'watchmid-style' ), wm_asset_version( 'assets/css/components/otp-modal.css' ) );
        wp_enqueue_script( 'watchmid-otp-auth', wm_asset_uri( 'assets/js/otp-auth.js' ), array(), wm_asset_version( 'assets/js/otp-auth.js' ), true );
        wp_localize_script(
            'watchmid-otp-auth',
            'wmOtpData',
            array(
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'wm_otp_nonce' ),
                'resendSeconds' => 60,
            )
        );
    }

    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'watchmid_scripts' );

require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/helpers/home-data.php';
require get_template_directory() . '/inc/helpers/marketing-data.php';
require get_template_directory() . '/inc/helpers/technical-data.php';
require get_template_directory() . '/inc/acf/mega-menu-cpt.php';
require get_template_directory() . '/inc/acf/mega-menu-fields.php';
require get_template_directory() . '/inc/acf/mega-menu-nav-field.php';
require get_template_directory() . '/inc/acf/mega-menu-migration.php';
require get_template_directory() . '/inc/components/mega-menu.php';
require get_template_directory() . '/inc/components/site-header.php';
require get_template_directory() . '/inc/components/mini-cart.php';
require get_template_directory() . '/inc/components/product-card.php';
require get_template_directory() . '/inc/components/product-carousel.php';
require get_template_directory() . '/inc/components/product-archive.php';
require get_template_directory() . '/inc/components/brand-archive.php';
require get_template_directory() . '/inc/components/site-footer.php';
require get_template_directory() . '/inc/components/mobile-nav.php';
require get_template_directory() . '/inc/product-components.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/ajax/search.php';
require get_template_directory() . '/inc/ajax/otp-auth.php';
require get_template_directory() . '/inc/customizer/design-settings.php';
require get_template_directory() . '/inc/patterns/register-patterns.php';
require get_template_directory() . '/inc/acf/design-tokens.php';
require get_template_directory() . '/inc/acf/home-fields.php';
require get_template_directory() . '/inc/compat/cache.php';
