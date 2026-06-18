<?php
/**
 * Theme design Customizer settings.
 *
 * @package WM_Theme
 */

function eshobe_ecommerce_customize_register_design( $wp_customize ) {
    $wp_customize->add_section(
        'eshobe_ecommerce_design',
        array(
            'title'       => __( 'طراحی قالب', 'eshobe-ecommerce' ),
            'priority'    => 35,
            'description' => __( 'تنظیمات کلی و کاربردی برای چیدمان و خوانایی قالب.', 'eshobe-ecommerce' ),
        )
    );

    $wp_customize->add_setting(
        'eshobe_ecommerce_font_family',
        array(
            'default'           => 'vazirmatn',
            'sanitize_callback' => 'eshobe_ecommerce_sanitize_choice',
        )
    );
    $wp_customize->add_control(
        'eshobe_ecommerce_font_family',
        array(
            'section' => 'eshobe_ecommerce_design',
            'label'   => __( 'فونت سایت', 'eshobe-ecommerce' ),
            'type'    => 'select',
            'choices' => array(
                'vazirmatn' => 'Vazirmatn',
                'peyda'     => 'Peyda',
                'system'    => 'System',
            ),
        )
    );

    $wp_customize->add_setting(
        'eshobe_ecommerce_content_width',
        array(
            'default'           => 1320,
            'sanitize_callback' => 'absint',
        )
    );
    $wp_customize->add_control(
        'eshobe_ecommerce_content_width',
        array(
            'section'     => 'eshobe_ecommerce_design',
            'label'       => __( 'عرض کلی محتوا', 'eshobe-ecommerce' ),
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 1040,
                'max'  => 1440,
                'step' => 20,
            ),
        )
    );

    $wp_customize->add_setting(
        'eshobe_ecommerce_product_image_position',
        array(
            'default'           => 'right',
            'sanitize_callback' => 'eshobe_ecommerce_sanitize_choice',
        )
    );
    $wp_customize->add_control(
        'eshobe_ecommerce_product_image_position',
        array(
            'section' => 'eshobe_ecommerce_design',
            'label'   => __( 'جایگاه تصویر محصول', 'eshobe-ecommerce' ),
            'type'    => 'select',
            'choices' => array(
                'right' => __( 'راست', 'eshobe-ecommerce' ),
                'left'  => __( 'چپ', 'eshobe-ecommerce' ),
            ),
        )
    );

    $wp_customize->add_setting(
        'eshobe_ecommerce_product_section_gap',
        array(
            'default'           => 20,
            'sanitize_callback' => 'absint',
        )
    );
    $wp_customize->add_control(
        'eshobe_ecommerce_product_section_gap',
        array(
            'section'     => 'eshobe_ecommerce_design',
            'label'       => __( 'فاصله بین بخش‌های محصول', 'eshobe-ecommerce' ),
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 12,
                'max'  => 36,
                'step' => 2,
            ),
        )
    );

    $wp_customize->add_setting(
        'eshobe_ecommerce_product_card_density',
        array(
            'default'           => 'comfortable',
            'sanitize_callback' => 'eshobe_ecommerce_sanitize_choice',
        )
    );
    $wp_customize->add_control(
        'eshobe_ecommerce_product_card_density',
        array(
            'section' => 'eshobe_ecommerce_design',
            'label'   => __( 'تراکم کارت‌ها', 'eshobe-ecommerce' ),
            'type'    => 'select',
            'choices' => array(
                'comfortable' => __( 'راحت', 'eshobe-ecommerce' ),
                'compact'     => __( 'فشرده', 'eshobe-ecommerce' ),
            ),
        )
    );

    $wp_customize->add_setting(
        'eshobe_ecommerce_product_gallery_sticky',
        array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        )
    );
    $wp_customize->add_control(
        'eshobe_ecommerce_product_gallery_sticky',
        array(
            'section' => 'eshobe_ecommerce_design',
            'label'   => __( 'استیکی بودن تصویر محصول در دسکتاپ', 'eshobe-ecommerce' ),
            'type'    => 'checkbox',
        )
    );
}
add_action( 'customize_register', 'eshobe_ecommerce_customize_register_design' );

function eshobe_ecommerce_sanitize_choice( $value, $setting ) {
    $control = $setting->manager->get_control( $setting->id );
    $choices = $control ? $control->choices : array();

    return array_key_exists( $value, $choices ) ? $value : $setting->default;
}
