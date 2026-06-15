<?php
/**
 * Theme design Customizer settings.
 *
 * @package WM_Theme
 */

function watchmid_customize_register_design( $wp_customize ) {
    $wp_customize->add_section(
        'watchmid_design',
        array(
            'title'       => __( 'طراحی قالب', 'watchmid' ),
            'priority'    => 35,
            'description' => __( 'تنظیمات کلی و کاربردی برای چیدمان و خوانایی قالب.', 'watchmid' ),
        )
    );

    $wp_customize->add_setting(
        'watchmid_font_family',
        array(
            'default'           => 'vazirmatn',
            'sanitize_callback' => 'watchmid_sanitize_choice',
        )
    );
    $wp_customize->add_control(
        'watchmid_font_family',
        array(
            'section' => 'watchmid_design',
            'label'   => __( 'فونت سایت', 'watchmid' ),
            'type'    => 'select',
            'choices' => array(
                'vazirmatn' => 'Vazirmatn',
                'peyda'     => 'Peyda',
                'system'    => 'System',
            ),
        )
    );

    $wp_customize->add_setting(
        'watchmid_content_width',
        array(
            'default'           => 1320,
            'sanitize_callback' => 'absint',
        )
    );
    $wp_customize->add_control(
        'watchmid_content_width',
        array(
            'section'     => 'watchmid_design',
            'label'       => __( 'عرض کلی محتوا', 'watchmid' ),
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 1040,
                'max'  => 1440,
                'step' => 20,
            ),
        )
    );

    $wp_customize->add_setting(
        'watchmid_product_image_position',
        array(
            'default'           => 'right',
            'sanitize_callback' => 'watchmid_sanitize_choice',
        )
    );
    $wp_customize->add_control(
        'watchmid_product_image_position',
        array(
            'section' => 'watchmid_design',
            'label'   => __( 'جایگاه تصویر محصول', 'watchmid' ),
            'type'    => 'select',
            'choices' => array(
                'right' => __( 'راست', 'watchmid' ),
                'left'  => __( 'چپ', 'watchmid' ),
            ),
        )
    );

    $wp_customize->add_setting(
        'watchmid_product_section_gap',
        array(
            'default'           => 20,
            'sanitize_callback' => 'absint',
        )
    );
    $wp_customize->add_control(
        'watchmid_product_section_gap',
        array(
            'section'     => 'watchmid_design',
            'label'       => __( 'فاصله بین بخش‌های محصول', 'watchmid' ),
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 12,
                'max'  => 36,
                'step' => 2,
            ),
        )
    );

    $wp_customize->add_setting(
        'watchmid_product_card_density',
        array(
            'default'           => 'comfortable',
            'sanitize_callback' => 'watchmid_sanitize_choice',
        )
    );
    $wp_customize->add_control(
        'watchmid_product_card_density',
        array(
            'section' => 'watchmid_design',
            'label'   => __( 'تراکم کارت‌ها', 'watchmid' ),
            'type'    => 'select',
            'choices' => array(
                'comfortable' => __( 'راحت', 'watchmid' ),
                'compact'     => __( 'فشرده', 'watchmid' ),
            ),
        )
    );

    $wp_customize->add_setting(
        'watchmid_product_gallery_sticky',
        array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
        )
    );
    $wp_customize->add_control(
        'watchmid_product_gallery_sticky',
        array(
            'section' => 'watchmid_design',
            'label'   => __( 'استیکی بودن تصویر محصول در دسکتاپ', 'watchmid' ),
            'type'    => 'checkbox',
        )
    );
}
add_action( 'customize_register', 'watchmid_customize_register_design' );

function watchmid_sanitize_choice( $value, $setting ) {
    $control = $setting->manager->get_control( $setting->id );
    $choices = $control ? $control->choices : array();

    return array_key_exists( $value, $choices ) ? $value : $setting->default;
}
