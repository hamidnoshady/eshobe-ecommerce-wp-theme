<?php
/**
 * ACF-powered design tokens.
 *
 * @package WM_Theme
 */

function wm_design_token_defaults() {
    return array(
        'wm_color_primary'              => '#111827',
        'wm_color_secondary'            => '#6B7280',
        'wm_color_text'                 => '#1F2937',
        'wm_color_accent'               => '#C89B3C',
        'wm_color_accent_dark'          => '#9F7425',
        'wm_color_background'           => '#F6F5F2',
        'wm_color_surface'              => '#FFFFFF',
        'wm_color_border'               => '#E5E0D8',
        'wm_color_muted'                => '#6B7280',
        'wm_color_cta'                  => '#111827',
        'wm_hero_progress_color'        => '#C89B3C',
        'wm_hero_progress_direction'    => 'right',
        'wm_font_mode'                  => 'vazirmatn',
        'wm_font_custom_family_name'    => 'CustomFont',
        'wm_font_size_base'             => '15px',
        'wm_font_size_h1'               => '32px',
        'wm_font_size_h2'               => '26px',
        'wm_font_size_h3'               => '20px',
        'wm_font_size_small'            => '13px',
        'wm_line_height_body'           => '1.9',
        'wm_font_weight_heading'        => '800',
        'wm_font_weight_body'           => '400',
        'wm_site_width'                 => '1200',
        'wm_global_radius'              => '24',
        'wm_design_density'             => 'standard',
        'wm_enable_decorative_motifs'   => '1',
        'wm_decorative_motifs_intensity' => 'medium',
        'wm_decorative_motifs_color'    => '#C89B3C',
        'wm_decorative_motifs_opacity'  => '0.16',
        'wm_decor_home_intensity'       => 'inherit',
        'wm_decor_product_intensity'    => 'inherit',
        'wm_decor_archive_intensity'    => 'inherit',
        'wm_decor_page_intensity'       => 'inherit',
    );
}

function wm_get_design_token( $field_name, $default = null ) {
    $defaults = wm_design_token_defaults();
    $default  = null === $default && array_key_exists( $field_name, $defaults ) ? $defaults[ $field_name ] : $default;

    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $field_name, 'option' );
        if ( null !== $value && '' !== $value && false !== $value ) {
            return $value;
        }
    }

    return $default;
}

function wm_design_token_hex( $field_name ) {
    $value = wm_get_design_token( $field_name );
    $value = is_string( $value ) ? sanitize_hex_color( $value ) : '';

    $defaults = wm_design_token_defaults();
    return $value ? $value : $defaults[ $field_name ];
}

function wm_design_token_size( $field_name ) {
    $value = wm_get_design_token( $field_name );
    $value = is_scalar( $value ) ? trim( (string) $value ) : '';

    if ( preg_match( '/^\d+(\.\d+)?(px|rem|em)$/', $value ) ) {
        return $value;
    }

    if ( preg_match( '/^\d+(\.\d+)?$/', $value ) ) {
        return $value . 'px';
    }

    $defaults = wm_design_token_defaults();
    return $defaults[ $field_name ];
}

function wm_design_token_number( $field_name, $min, $max ) {
    $value = wm_get_design_token( $field_name );
    $value = is_numeric( $value ) ? (float) $value : (float) wm_design_token_defaults()[ $field_name ];

    return (string) min( max( $value, $min ), $max );
}

function wm_design_token_choice( $field_name, $allowed, $fallback ) {
    $value = wm_get_design_token( $field_name, $fallback );
    $value = is_scalar( $value ) ? (string) $value : $fallback;

    return in_array( $value, $allowed, true ) ? $value : $fallback;
}

function wm_design_token_opacity( $field_name, $fallback = 0.16 ) {
    $value = wm_get_design_token( $field_name, $fallback );
    $value = is_numeric( $value ) ? (float) $value : (float) $fallback;

    return (string) min( max( $value, 0 ), 1 );
}

function wm_get_decor_current_intensity() {
    $allowed = array( 'inherit', 'off', 'low', 'medium', 'high' );
    $field   = '';
    $type    = '';

    if ( is_front_page() ) {
        $field = 'wm_decor_home_intensity';
        $type  = 'home';
    } elseif ( function_exists( 'is_product' ) && is_product() ) {
        $field = 'wm_decor_product_intensity';
        $type  = 'product';
    } elseif (
        ( function_exists( 'is_shop' ) && is_shop() ) ||
        ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ||
        is_post_type_archive( 'product' )
    ) {
        $field = 'wm_decor_archive_intensity';
        $type  = 'archive';
    } elseif ( is_page() ) {
        $field = 'wm_decor_page_intensity';
        $type  = 'page';
    }

    if ( ! $field ) {
        return array( 'type' => '', 'intensity' => 'inherit' );
    }

    return array(
        'type'      => $type,
        'intensity' => wm_design_token_choice( $field, $allowed, 'inherit' ),
    );
}

function wm_get_font_stack() {
    $mode = wm_get_design_token( 'wm_font_mode', 'vazirmatn' );
    $mode = in_array( $mode, array( 'vazirmatn', 'peyda', 'custom', 'system' ), true ) ? $mode : 'vazirmatn';

    if ( 'peyda' === $mode ) {
        return "'Peyda', 'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    }

    if ( 'system' === $mode ) {
        return "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    }

    if ( 'custom' === $mode ) {
        $family = wm_get_design_token( 'wm_font_custom_family_name', 'CustomFont' );
        $family = preg_replace( '/[^A-Za-z0-9 _-]/', '', (string) $family );
        $family = '' !== trim( $family ) ? trim( $family ) : 'CustomFont';

        return "'" . esc_attr( $family ) . "', 'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    }

    return "'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
}

function wm_get_font_file_url( $field_name ) {
    $file = wm_get_design_token( $field_name, '' );

    if ( is_array( $file ) && ! empty( $file['url'] ) ) {
        return esc_url_raw( $file['url'] );
    }

    if ( is_numeric( $file ) ) {
        return esc_url_raw( wp_get_attachment_url( absint( $file ) ) );
    }

    if ( is_string( $file ) && filter_var( $file, FILTER_VALIDATE_URL ) ) {
        return esc_url_raw( $file );
    }

    return '';
}

function wm_get_font_face_css() {
    if ( 'custom' !== wm_get_design_token( 'wm_font_mode', 'vazirmatn' ) ) {
        return '';
    }

    $family = wm_get_design_token( 'wm_font_custom_family_name', 'CustomFont' );
    $family = preg_replace( '/[^A-Za-z0-9 _-]/', '', (string) $family );
    $family = '' !== trim( $family ) ? trim( $family ) : 'CustomFont';
    $faces  = array(
        'wm_font_custom_regular'   => 400,
        'wm_font_custom_medium'    => 500,
        'wm_font_custom_semibold'  => 600,
        'wm_font_custom_bold'      => 700,
        'wm_font_custom_extrabold' => 800,
    );
    $css    = '';

    foreach ( $faces as $field => $weight ) {
        $url = wm_get_font_file_url( $field );
        if ( ! $url ) {
            continue;
        }

        $format = false !== stripos( $url, '.woff2' ) ? 'woff2' : 'woff';
        $css   .= "@font-face{font-family:'" . esc_attr( $family ) . "';src:url('" . esc_url( $url ) . "') format('" . esc_attr( $format ) . "');font-weight:" . absint( $weight ) . ';font-style:normal;font-display:swap;}';
    }

    return $css;
}

function wm_get_design_tokens_css() {
    $cached = get_transient( 'wm_design_tokens_css' );
    if ( false !== $cached ) {
        return $cached;
    }

    $css = wm_build_design_tokens_css();
    set_transient( 'wm_design_tokens_css', $css, DAY_IN_SECONDS );

    return $css;
}

function wm_clear_design_tokens_css_cache() {
    delete_transient( 'wm_design_tokens_css' );
}
add_action( 'acf/save_post', 'wm_clear_design_tokens_css_cache', 20 );
add_action( 'switch_theme', 'wm_clear_design_tokens_css_cache' );

function wm_build_design_tokens_css() {
    $width   = absint( wm_get_design_token( 'wm_site_width', 1200 ) );
    $width   = min( max( $width, 1040 ), 1440 );
    $radius  = absint( wm_get_design_token( 'wm_global_radius', 24 ) );
    $radius  = min( max( $radius, 8 ), 40 );
    $density = wm_get_design_token( 'wm_design_density', 'standard' );
    $density = in_array( $density, array( 'compact', 'standard', 'spacious', 'open' ), true ) ? $density : 'standard';
    $density = 'open' === $density ? 'spacious' : $density;
    $decor_color = wm_design_token_hex( 'wm_decorative_motifs_color' );
    $decor_opacity = wm_design_token_opacity( 'wm_decorative_motifs_opacity', 0.16 );

    $css  = wm_get_font_face_css();
    $css .= ':root{';
    $css .= '--wm-color-primary:' . wm_design_token_hex( 'wm_color_primary' ) . ';';
    $css .= '--wm-color-secondary:' . wm_design_token_hex( 'wm_color_secondary' ) . ';';
    $css .= '--wm-color-text:' . wm_design_token_hex( 'wm_color_text' ) . ';';
    $css .= '--wm-color-accent:' . wm_design_token_hex( 'wm_color_accent' ) . ';';
    $css .= '--wm-color-accent-dark:' . wm_design_token_hex( 'wm_color_accent_dark' ) . ';';
    $css .= '--wm-color-background:' . wm_design_token_hex( 'wm_color_background' ) . ';';
    $css .= '--wm-color-bg:' . wm_design_token_hex( 'wm_color_background' ) . ';';
    $css .= '--wm-color-surface:' . wm_design_token_hex( 'wm_color_surface' ) . ';';
    $css .= '--wm-color-border:' . wm_design_token_hex( 'wm_color_border' ) . ';';
    $css .= '--wm-color-muted:' . wm_design_token_hex( 'wm_color_muted' ) . ';';
    $css .= '--wm-color-cta:' . wm_design_token_hex( 'wm_color_cta' ) . ';';
    $css .= '--wm-hero-progress-color:' . wm_design_token_hex( 'wm_hero_progress_color' ) . ';';
    $css .= '--wm-hero-progress-origin:' . wm_design_token_choice( 'wm_hero_progress_direction', array( 'right', 'left' ), 'right' ) . ';';
    $css .= '--wm-font-primary:' . wm_get_font_stack() . ';';
    $css .= '--wm-font-size-base:' . wm_design_token_size( 'wm_font_size_base' ) . ';';
    $css .= '--wm-font-size-h1:' . wm_design_token_size( 'wm_font_size_h1' ) . ';';
    $css .= '--wm-font-size-h2:' . wm_design_token_size( 'wm_font_size_h2' ) . ';';
    $css .= '--wm-font-size-h3:' . wm_design_token_size( 'wm_font_size_h3' ) . ';';
    $css .= '--wm-font-size-small:' . wm_design_token_size( 'wm_font_size_small' ) . ';';
    $css .= '--wm-line-height-body:' . wm_design_token_number( 'wm_line_height_body', 1, 2.4 ) . ';';
    $css .= '--wm-font-weight-heading:' . absint( wm_get_design_token( 'wm_font_weight_heading', 800 ) ) . ';';
    $css .= '--wm-font-weight-body:' . absint( wm_get_design_token( 'wm_font_weight_body', 400 ) ) . ';';
    $css .= '--wm-content-width:' . $width . 'px;';
    $css .= '--wm-radius-lg:' . $radius . 'px;';
    $css .= '--wm-design-density:' . esc_attr( $density ) . ';';
    $css .= '--wm-decor-color:' . $decor_color . ';';
    $css .= '--wm-decor-opacity:' . $decor_opacity . ';';
    $css .= '--wm-decor-intensity-factor:1;';
    $css .= '}';
    $css .= 'body,button,input,select,textarea{font-family:var(--wm-font-primary);font-size:var(--wm-font-size-base);line-height:var(--wm-line-height-body);}';
    $css .= 'body{font-weight:var(--wm-font-weight-body);background:var(--wm-color-bg);color:var(--wm-color-text);}';
    $css .= 'h1,h2,h3,.wm-home-section__title,.wm-product-intro__title,.wm-related-products__title{font-weight:var(--wm-font-weight-heading);}';
    $css .= '.wm-home-hero__title{font-size:clamp(var(--wm-font-size-h1),4vw,52px);}';
    $css .= '.wm-home-section__title,.wm-related-products__title{font-size:var(--wm-font-size-h2);}';
    $css .= '.wm-product-card__title,.wm-related-card__title{font-size:var(--wm-font-size-small);}';
    $css .= '.wm-home-button--primary,.wm-product-purchase .single_add_to_cart_button,.wm-mobile-bottom-bar__cta .button{background:linear-gradient(135deg,var(--wm-color-cta),var(--wm-color-primary)) !important;}';

    return $css;
}

function wm_design_density_body_class( $classes ) {
    $density = wm_get_design_token( 'wm_design_density', 'standard' );
    $density = in_array( $density, array( 'compact', 'standard', 'spacious', 'open' ), true ) ? $density : 'standard';
    $density = 'open' === $density ? 'spacious' : $density;

    $classes[] = 'wm-density-' . sanitize_html_class( $density );
    $intensity = wm_design_token_choice( 'wm_decorative_motifs_intensity', array( 'low', 'medium', 'high' ), 'medium' );

    if ( function_exists( 'get_field' ) ) {
        $motifs_enabled = get_field( 'wm_enable_decorative_motifs', 'option' );
        if ( false === $motifs_enabled || '0' === $motifs_enabled || 0 === $motifs_enabled ) {
            $classes[] = 'wm-decor-disabled';
        }
    }

    $classes[] = 'wm-decor-intensity-' . sanitize_html_class( $intensity );

    $current = wm_get_decor_current_intensity();
    if ( ! empty( $current['type'] ) ) {
        $current_intensity = 'inherit' === $current['intensity'] ? $intensity : $current['intensity'];
        $classes[]         = 'wm-decor-current-' . sanitize_html_class( $current_intensity );
        if ( ! empty( $current['type'] ) ) {
            $classes[] = 'wm-decor-' . sanitize_html_class( $current['type'] ) . '-' . sanitize_html_class( $current_intensity );
        }
    }

    return $classes;
}
if ( function_exists( 'add_filter' ) ) {
    add_filter( 'body_class', 'wm_design_density_body_class' );
}
