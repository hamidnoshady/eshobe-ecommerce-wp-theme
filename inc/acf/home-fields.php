<?php
/**
 * ACF fields for theme options pages.
 *
 * @package WM_Theme
 */

function wm_register_home_acf_options() {
    if ( ! function_exists( 'acf_add_options_page' ) ) {
        return;
    }

    acf_add_options_page(
        array(
            'page_title' => 'تنظیمات قالب',
            'menu_title' => 'تنظیمات قالب',
            'menu_slug'  => 'eshobe-ecommerce',
            'capability' => 'edit_posts',
            'redirect'   => true,
            'position'   => 59,
        )
    );

    $pages = array(
        array( 'page_title' => 'تنظیمات طراحی', 'menu_title' => 'تنظیمات طراحی', 'menu_slug' => 'eshobe-ecommerce-design-settings' ),
        array( 'page_title' => 'صفحه اصلی', 'menu_title' => 'صفحه اصلی', 'menu_slug' => 'eshobe-ecommerce-home-settings' ),
        array( 'page_title' => 'صفحه محصول', 'menu_title' => 'صفحه محصول', 'menu_slug' => 'eshobe-ecommerce-product-settings' ),
        array( 'page_title' => 'آرشیوها / فروشگاه', 'menu_title' => 'آرشیوها / فروشگاه', 'menu_slug' => 'eshobe-ecommerce-archive-settings' ),
        array( 'page_title' => 'هدر و فوتر', 'menu_title' => 'هدر و فوتر', 'menu_slug' => 'eshobe-ecommerce-header-footer-settings' ),
        array( 'page_title' => 'بازاریابی و فروش', 'menu_title' => 'بازاریابی و فروش', 'menu_slug' => 'eshobe-ecommerce-marketing-settings' ),
        array( 'page_title' => 'تنظیمات فنی', 'menu_title' => 'تنظیمات فنی', 'menu_slug' => 'eshobe-ecommerce-technical-settings', 'capability' => 'manage_options' ),
    );

    foreach ( $pages as $page ) {
        acf_add_options_sub_page(
            array(
                'page_title'  => $page['page_title'],
                'menu_title'  => $page['menu_title'],
                'menu_slug'   => $page['menu_slug'],
                'parent_slug' => 'eshobe-ecommerce',
                'capability'  => isset( $page['capability'] ) ? $page['capability'] : 'edit_posts',
                'post_id'     => 'option',
            )
        );
    }
}
add_action( 'acf/init', 'wm_register_home_acf_options' );

function wm_register_home_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_design_settings',
            'title'    => 'تنظیمات طراحی',
            'fields'   => wm_site_settings_design_fields(),
            'location' => wm_site_settings_location( 'eshobe-ecommerce-design-settings' ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_home',
            'title'    => 'صفحه اصلی',
            'fields'   => wm_site_settings_home_fields_dynamic(),
            'location' => wm_site_settings_location( 'eshobe-ecommerce-home-settings' ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_product_settings',
            'title'    => 'صفحه محصول',
            'fields'   => wm_site_settings_product_fields(),
            'location' => wm_site_settings_location( 'eshobe-ecommerce-product-settings' ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_archive_settings',
            'title'    => 'آرشیوها / فروشگاه',
            'fields'   => wm_site_settings_archive_fields(),
            'location' => wm_site_settings_location( 'eshobe-ecommerce-archive-settings' ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_header_footer_settings',
            'title'    => 'هدر و فوتر',
            'fields'   => wm_site_settings_header_footer_fields(),
            'location' => wm_site_settings_location( 'eshobe-ecommerce-header-footer-settings' ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_marketing_settings',
            'title'    => 'بازاریابی و فروش',
            'fields'   => wm_site_settings_marketing_fields(),
            'location' => wm_site_settings_location( 'eshobe-ecommerce-marketing-settings' ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_technical_settings',
            'title'    => 'تنظیمات فنی',
            'fields'   => wm_site_settings_technical_fields(),
            'location' => wm_site_settings_location( 'eshobe-ecommerce-technical-settings' ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_eshobe_ecommerce_product_flags',
            'title'    => 'Product flags',
            'fields'   => array(
                array(
                    'key'   => 'field_eshobe_ecommerce_is_recommended',
                    'label' => 'پیشنهاد ما',
                    'name'  => 'eshobe_ecommerce_is_recommended',
                    'type'  => 'true_false',
                    'ui'    => 1,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'product',
                    ),
                ),
            ),
        )
    );
}

function wm_acf_product_relationship_query( $args, $field, $post_id ) {
    $args['post_type']      = array( 'product' );
    $args['post_status']    = 'publish';
    $args['posts_per_page'] = 20;

    return $args;
}
add_filter( 'acf/fields/relationship/query/name=home_recommended_products', 'wm_acf_product_relationship_query', 10, 3 );
add_filter( 'acf/fields/relationship/query/name=home_bestsellers_products', 'wm_acf_product_relationship_query', 10, 3 );
add_filter( 'acf/fields/relationship/query/name=style_products', 'wm_acf_product_relationship_query', 10, 3 );
add_filter( 'acf/fields/relationship/query/name=wm_search_suggested_products', 'wm_acf_product_relationship_query', 10, 3 );
add_action( 'acf/init', 'wm_register_home_acf_fields' );

function wm_site_settings_location( $options_page ) {
    return array(
        array(
            array(
                'param'    => 'options_page',
                'operator' => '==',
                'value'    => $options_page,
            ),
        ),
    );
}

function wm_site_settings_tab( $key, $label, $placement = 'top' ) {
    return array(
        'key'       => $key,
        'label'     => $label,
        'name'      => '',
        'type'      => 'tab',
        'placement' => $placement,
    );
}

function wm_site_settings_accordion( $key, $label, $endpoint = 0 ) {
    return array(
        'key'          => $key,
        'label'        => $label,
        'name'         => '',
        'type'         => 'accordion',
        'open'         => 0 === $endpoint,
        'multi_expand' => 1,
        'endpoint'     => $endpoint,
    );
}

function wm_site_settings_placeholder_fields( $prefix, $title, $message ) {
    return array(
        array(
            'key'     => 'field_wm_' . $prefix . '_placeholder',
            'label'   => $title,
            'name'    => '',
            'type'    => 'message',
            'message' => $message,
        ),
    );
}

function wm_site_settings_header_footer_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_header_footer_tab_header', 'هدر' ),
        wm_site_settings_accordion( 'field_wm_header_settings_accordion', 'تنظیمات هدر' ),
        array(
            'key'           => 'field_wm_header_topbar_enabled',
            'label'         => 'نمایش نوار اعتماد بالای هدر',
            'name'          => 'wm_header_topbar_enabled',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
        ),
        array(
            'key'          => 'field_wm_header_topbar_items',
            'label'        => 'آیتم‌های نوار اعتماد',
            'name'         => 'wm_header_topbar_items',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => 'افزودن آیتم',
            'instructions' => 'اگر آیتمی وارد نشود، متن‌های پیش‌فرض ضمانت اصالت، ارسال سریع، پرداخت امن و پشتیبانی نمایش داده می‌شود.',
            'sub_fields'   => array(
                array( 'key' => 'field_wm_header_topbar_item_enabled', 'label' => 'فعال', 'name' => 'item_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_wm_header_topbar_item_text', 'label' => 'متن آیتم', 'name' => 'item_text', 'type' => 'text' ),
                array( 'key' => 'field_wm_header_topbar_item_url', 'label' => 'لینک اختیاری', 'name' => 'item_url', 'type' => 'url' ),
            ),
        ),
        array(
            'key'           => 'field_wm_header_sticky_enabled',
            'label'         => 'فعال بودن هدر چسبان',
            'name'          => 'wm_header_sticky_enabled',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
        ),
        array(
            'key'           => 'field_wm_header_show_search',
            'label'         => 'نمایش جستجو',
            'name'          => 'wm_header_show_search',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
        ),
        array(
            'key'           => 'field_wm_header_show_account',
            'label'         => 'نمایش حساب کاربری',
            'name'          => 'wm_header_show_account',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
        ),
        array(
            'key'           => 'field_wm_header_show_cart',
            'label'         => 'نمایش سبد خرید',
            'name'          => 'wm_header_show_cart',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
        ),
        array(
            'key'           => 'field_wm_header_logo',
            'label'         => 'لوگوی هدر (دسکتاپ)',
            'name'          => 'wm_header_logo',
            'type'          => 'image',
            'return_format' => 'array',
            'preview_size'  => 'thumbnail',
            'instructions'  => 'اگر خالی باشد، از لوگوی سفارشی وردپرس استفاده می‌شود. سایز کادر لوگو متناسب با ابعاد تصویر تنظیم می‌شود.',
        ),
        array(
            'key'           => 'field_wm_header_logo_mobile',
            'label'         => 'لوگوی هدر (موبایل)',
            'name'          => 'wm_header_logo_mobile',
            'type'          => 'image',
            'return_format' => 'array',
            'preview_size'  => 'thumbnail',
            'instructions'  => 'اگر خالی باشد، همان لوگوی دسکتاپ در حالت موبایل استفاده می‌شود.',
        ),
        array(
            'key'           => 'field_wm_header_site_name',
            'label'         => 'نام فروشگاه در هدر',
            'name'          => 'wm_header_site_name',
            'type'          => 'text',
            'default_value' => 'فروشگاه آنلاین',
            'instructions'  => 'اگر خالی باشد، نام سایت وردپرس نمایش داده می‌شود.',
        ),
        wm_site_settings_accordion( 'field_wm_header_settings_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_header_footer_tab_mobile_nav', 'Mobile Nav' ),
        wm_site_settings_accordion( 'field_wm_mobile_nav_settings_accordion', 'تنظیمات نوار موبایل' ),
        array( 'key' => 'field_wm_mobile_nav_enabled', 'label' => 'فعال بودن Mobile Bottom Nav', 'name' => 'wm_mobile_nav_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_mobile_nav_show_labels', 'label' => 'نمایش label آیتم‌ها', 'name' => 'wm_mobile_nav_show_labels', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_mobile_nav_density', 'label' => 'تراکم نوار موبایل', 'name' => 'wm_mobile_nav_density', 'type' => 'select', 'choices' => array( 'comfortable' => 'Comfortable', 'compact' => 'Compact' ), 'default_value' => 'comfortable' ),
        wm_site_settings_accordion( 'field_wm_mobile_nav_settings_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_header_footer_tab_footer', 'فوتر' ),
        wm_site_settings_accordion( 'field_wm_footer_content_accordion', 'محتوای اصلی فوتر' ),
        array(
            'key'           => 'field_wm_footer_title',
            'label'         => 'عنوان فوتر',
            'name'          => 'wm_footer_title',
            'type'          => 'text',
            'default_value' => 'فروشگاه آنلاین',
            'instructions'  => 'نام فروشگاه یا عنوان کوتاه فوتر.',
        ),
        array(
            'key'           => 'field_wm_footer_description',
            'label'         => 'توضیح کوتاه',
            'name'          => 'wm_footer_description',
            'type'          => 'textarea',
            'rows'          => 3,
            'default_value' => 'انتخابی مطمئن برای خرید آنلاین با ضمانت اصالت کالا و ارسال سریع.',
            'instructions'  => 'متن کوتاه و خوانا؛ برای حفظ خلوتی فوتر طولانی ننویسید.',
        ),
        wm_site_settings_accordion( 'field_wm_footer_badges_accordion', 'نمادها و کدهای اعتماد' ),
        array(
            'key'          => 'field_wm_footer_badges',
            'label'        => 'نمادها',
            'name'         => 'wm_footer_badges',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'افزودن نماد',
            'instructions' => 'اگر کد HTML نماد وارد شود با wp_kses_post چاپ می‌شود و script مجاز نیست. برای کدهای scriptدار باید روش امن جداگانه/whitelist بررسی شود.',
            'sub_fields'   => array(
                array( 'key' => 'field_wm_footer_badge_enabled', 'label' => 'فعال', 'name' => 'badge_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_wm_footer_badge_title', 'label' => 'عنوان نماد', 'name' => 'badge_title', 'type' => 'text' ),
                array( 'key' => 'field_wm_footer_badge_image', 'label' => 'تصویر نماد', 'name' => 'badge_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'thumbnail' ),
                array( 'key' => 'field_wm_footer_badge_html_code', 'label' => 'کد HTML نماد', 'name' => 'badge_html_code', 'type' => 'textarea', 'rows' => 4, 'instructions' => 'کد با wp_kses_post فیلتر می‌شود؛ scriptها حذف می‌شوند.' ),
                array( 'key' => 'field_wm_footer_badge_url', 'label' => 'لینک نماد', 'name' => 'badge_url', 'type' => 'url' ),
            ),
        ),
        wm_site_settings_accordion( 'field_wm_footer_bottom_accordion', 'ردیف پایینی' ),
        array(
            'key'           => 'field_wm_footer_copyright',
            'label'         => 'کپی‌رایت',
            'name'          => 'wm_footer_copyright',
            'type'          => 'text',
            'default_value' => '© ۱۴۰۵ فروشگاه آنلاین. کلیه حقوق محفوظ است.',
        ),
        array(
            'key'           => 'field_wm_footer_developer_text',
            'label'         => 'متن توسعه‌دهنده',
            'name'          => 'wm_footer_developer_text',
            'type'          => 'text',
            'default_value' => '',
        ),
        array(
            'key'          => 'field_wm_footer_developer_url',
            'label'        => 'لینک توسعه‌دهنده',
            'name'         => 'wm_footer_developer_url',
            'type'         => 'url',
            'instructions' => 'اگر خالی باشد متن توسعه‌دهنده بدون لینک نمایش داده می‌شود.',
        ),
        wm_site_settings_accordion( 'field_wm_footer_content_end', '', 1 ),
    );
}

function wm_site_settings_marketing_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_marketing_tab_search', 'جستجو' ),
        wm_site_settings_accordion( 'field_wm_marketing_search_accordion', 'تنظیمات جستجوی هدر' ),
        array(
            'key'           => 'field_wm_search_placeholder',
            'label'         => 'متن راهنمای جستجو',
            'name'          => 'wm_search_placeholder',
            'type'          => 'text',
            'default_value' => 'جستجوی محصول، برند یا دسته...',
        ),
        array(
            'key'           => 'field_wm_search_suggested_label',
            'label'         => 'عنوان بخش پیشنهادها',
            'name'          => 'wm_search_suggested_label',
            'type'          => 'text',
            'default_value' => 'پیشنهاد ویژه',
        ),
        array(
            'key'           => 'field_wm_search_max_results',
            'label'         => 'حداکثر تعداد نتایج جستجو',
            'name'          => 'wm_search_max_results',
            'type'          => 'number',
            'default_value' => 6,
            'min'           => 1,
            'max'           => 20,
        ),
        array(
            'key'           => 'field_wm_search_max_per_ip',
            'label'         => 'حداکثر درخواست جستجو از هر IP (در دقیقه)',
            'name'          => 'wm_search_max_per_ip',
            'type'          => 'number',
            'default_value' => 30,
            'min'           => 5,
            'max'           => 500,
            'instructions'  => 'محدودیت نرخ درخواست‌های جستجوی زنده برای جلوگیری از بارگذاری بیش از حد پایگاه‌داده.',
        ),
        array(
            'key'          => 'field_wm_search_suggested_products',
            'label'        => 'محصولات پیشنهادی (هنگام خالی بودن جستجو)',
            'name'         => 'wm_search_suggested_products',
            'type'         => 'relationship',
            'post_type'    => array( 'product' ),
            'filters'      => array( 'search' ),
            'max'          => 6,
            'return_format' => 'id',
        ),
        wm_site_settings_tab( 'field_wm_marketing_tab_sales', 'تخفیف‌ها و کمپین‌ها' ),
        wm_site_settings_accordion( 'field_wm_marketing_sale_badge_accordion', 'برچسب تخفیف روی کارت محصول' ),
        array(
            'key'           => 'field_wm_marketing_sale_badge_enabled',
            'label'         => 'نمایش برچسب تخفیف',
            'name'          => 'wm_marketing_sale_badge_enabled',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
            'instructions'  => 'در صورت فعال بودن، روی محصولاتی که تخفیف دارند یک برچسب نمایش داده می‌شود.',
        ),
        array(
            'key'           => 'field_wm_marketing_sale_badge_text',
            'label'         => 'متن برچسب',
            'name'          => 'wm_marketing_sale_badge_text',
            'type'          => 'text',
            'default_value' => 'تخفیف',
            'instructions'  => 'برای نمایش درصد تخفیف از {percent} استفاده کنید؛ مثال: «{percent}% تخفیف».',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_marketing_sale_badge_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        array(
            'key'               => 'field_wm_marketing_sale_badge_bg',
            'label'             => 'رنگ پس‌زمینه برچسب',
            'name'              => 'wm_marketing_sale_badge_bg',
            'type'              => 'color_picker',
            'default_value'     => '#C0392B',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_marketing_sale_badge_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        array(
            'key'               => 'field_wm_marketing_sale_badge_color',
            'label'             => 'رنگ متن برچسب',
            'name'              => 'wm_marketing_sale_badge_color',
            'type'              => 'color_picker',
            'default_value'     => '#FFFFFF',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_marketing_sale_badge_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        wm_site_settings_tab( 'field_wm_marketing_tab_promo', 'بنرهای تبلیغاتی' ),
        wm_site_settings_accordion( 'field_wm_marketing_promo_accordion', 'بنرهای صفحه اصلی و آرشیو' ),
        array(
            'key'          => 'field_wm_marketing_promo_banners',
            'label'        => 'بنرهای تبلیغاتی',
            'name'         => 'wm_marketing_promo_banners',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'افزودن بنر',
            'instructions' => 'بنرهایی که در بازه زمانی فعال هستند، در محل انتخاب‌شده نمایش داده می‌شوند.',
            'sub_fields'   => array(
                array( 'key' => 'field_wm_marketing_promo_enabled', 'label' => 'فعال', 'name' => 'enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_wm_marketing_promo_image', 'label' => 'تصویر بنر', 'name' => 'image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium' ),
                array( 'key' => 'field_wm_marketing_promo_link', 'label' => 'لینک بنر', 'name' => 'link', 'type' => 'url' ),
                array(
                    'key'           => 'field_wm_marketing_promo_position',
                    'label'         => 'محل نمایش',
                    'name'          => 'position',
                    'type'          => 'select',
                    'choices'       => array(
                        'home_top'      => 'بالای صفحه اصلی',
                        'home_middle'   => 'وسط صفحه اصلی',
                        'archive_top'   => 'بالای آرشیو محصولات',
                    ),
                    'default_value' => 'home_top',
                    'ui'            => 1,
                ),
                array( 'key' => 'field_wm_marketing_promo_start', 'label' => 'تاریخ شروع', 'name' => 'start_date', 'type' => 'date_picker', 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d', 'instructions' => 'در صورت خالی بودن، از همین حالا فعال است.' ),
                array( 'key' => 'field_wm_marketing_promo_end', 'label' => 'تاریخ پایان', 'name' => 'end_date', 'type' => 'date_picker', 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d', 'instructions' => 'در صورت خالی بودن، نمایش بدون محدودیت زمانی است.' ),
            ),
        ),
    );
}

function wm_site_settings_design_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_design_tab_colors', 'رنگ‌ها' ),
        wm_site_settings_accordion( 'field_wm_design_colors_brand_accordion', 'رنگ‌های برند' ),
        array( 'key' => 'field_wm_color_primary', 'label' => 'رنگ اصلی', 'name' => 'wm_color_primary', 'type' => 'color_picker', 'default_value' => '#111827', 'instructions' => 'برای تیترها، متن‌های مهم و CTAهای اصلی استفاده می‌شود.' ),
        array( 'key' => 'field_wm_color_secondary', 'label' => 'رنگ فرعی', 'name' => 'wm_color_secondary', 'type' => 'color_picker', 'default_value' => '#6B7280', 'instructions' => 'برای متن‌های پشتیبان و عناصر ثانویه.' ),
        array( 'key' => 'field_wm_color_accent', 'label' => 'رنگ Accent / طلایی', 'name' => 'wm_color_accent', 'type' => 'color_picker', 'default_value' => '#C89B3C', 'instructions' => 'رنگ تاکید سایت؛ در خطوط، hover و جزئیات استفاده می‌شود.' ),
        array( 'key' => 'field_wm_color_accent_dark', 'label' => 'رنگ Accent Dark', 'name' => 'wm_color_accent_dark', 'type' => 'color_picker', 'default_value' => '#9F7425', 'instructions' => 'نسخه تیره‌تر accent برای قیمت و لینک‌های تاکید.' ),
        array( 'key' => 'field_wm_color_cta', 'label' => 'رنگ CTA', 'name' => 'wm_color_cta', 'type' => 'color_picker', 'default_value' => '#111827', 'instructions' => 'رنگ اصلی دکمه‌های اقدام.' ),
        wm_site_settings_accordion( 'field_wm_design_colors_surface_accordion', 'رنگ‌های سطح و خوانایی' ),
        array( 'key' => 'field_wm_color_text', 'label' => 'رنگ متن', 'name' => 'wm_color_text', 'type' => 'color_picker', 'default_value' => '#1F2937', 'instructions' => 'رنگ پایه متن بدنه.' ),
        array( 'key' => 'field_wm_color_background', 'label' => 'رنگ پس‌زمینه', 'name' => 'wm_color_background', 'type' => 'color_picker', 'default_value' => '#F6F5F2', 'instructions' => 'پس‌زمینه عمومی سایت.' ),
        array( 'key' => 'field_wm_color_surface', 'label' => 'رنگ سطح / کارت‌ها', 'name' => 'wm_color_surface', 'type' => 'color_picker', 'default_value' => '#FFFFFF', 'instructions' => 'پس‌زمینه کارت‌ها و پنل‌ها.' ),
        array( 'key' => 'field_wm_color_border', 'label' => 'رنگ Border', 'name' => 'wm_color_border', 'type' => 'color_picker', 'default_value' => '#E5E0D8', 'instructions' => 'مرز کارت‌ها و فیلدها.' ),
        array( 'key' => 'field_wm_color_muted', 'label' => 'رنگ Muted', 'name' => 'wm_color_muted', 'type' => 'color_picker', 'default_value' => '#6B7280', 'instructions' => 'متن‌های کم‌اهمیت و توضیحات.' ),
        wm_site_settings_accordion( 'field_wm_design_colors_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_design_tab_typography', 'فونت و تایپوگرافی' ),
        wm_site_settings_accordion( 'field_wm_design_font_family_accordion', 'انتخاب و آپلود فونت' ),
        array(
            'key'           => 'field_wm_font_mode',
            'label'         => 'نوع فونت اصلی',
            'name'          => 'wm_font_mode',
            'type'          => 'select',
            'choices'       => array( 'vazirmatn' => 'Vazirmatn', 'peyda' => 'Peyda', 'custom' => 'فونت سفارشی', 'system' => 'System Font' ),
            'default_value' => 'vazirmatn',
            'instructions'  => 'فونت اصلی body، دکمه‌ها و فرم‌ها از این گزینه خوانده می‌شود.',
        ),
        array( 'key' => 'field_wm_font_primary', 'label' => 'فونت اصلی Legacy', 'name' => 'wm_font_primary', 'type' => 'text', 'instructions' => 'برای سازگاری قبلی حفظ شده است؛ منبع اصلی جدید wm_font_mode است.' ),
        array( 'key' => 'field_wm_font_custom_family_name', 'label' => 'نام فونت سفارشی', 'name' => 'wm_font_custom_family_name', 'type' => 'text', 'default_value' => 'CustomFont', 'conditional_logic' => array( array( array( 'field' => 'field_wm_font_mode', 'operator' => '==', 'value' => 'custom' ) ) ), 'instructions' => 'اگر خالی باشد CustomFont استفاده می‌شود.' ),
        array( 'key' => 'field_wm_font_custom_regular', 'label' => 'فونت Regular / 400', 'name' => 'wm_font_custom_regular', 'type' => 'file', 'return_format' => 'array', 'mime_types' => 'woff2,woff', 'conditional_logic' => array( array( array( 'field' => 'field_wm_font_mode', 'operator' => '==', 'value' => 'custom' ) ) ), 'instructions' => 'فقط woff2 یا woff آپلود کنید.' ),
        array( 'key' => 'field_wm_font_custom_medium', 'label' => 'فونت Medium / 500', 'name' => 'wm_font_custom_medium', 'type' => 'file', 'return_format' => 'array', 'mime_types' => 'woff2,woff', 'conditional_logic' => array( array( array( 'field' => 'field_wm_font_mode', 'operator' => '==', 'value' => 'custom' ) ) ) ),
        array( 'key' => 'field_wm_font_custom_semibold', 'label' => 'فونت SemiBold / 600', 'name' => 'wm_font_custom_semibold', 'type' => 'file', 'return_format' => 'array', 'mime_types' => 'woff2,woff', 'conditional_logic' => array( array( array( 'field' => 'field_wm_font_mode', 'operator' => '==', 'value' => 'custom' ) ) ) ),
        array( 'key' => 'field_wm_font_custom_bold', 'label' => 'فونت Bold / 700', 'name' => 'wm_font_custom_bold', 'type' => 'file', 'return_format' => 'array', 'mime_types' => 'woff2,woff', 'conditional_logic' => array( array( array( 'field' => 'field_wm_font_mode', 'operator' => '==', 'value' => 'custom' ) ) ) ),
        array( 'key' => 'field_wm_font_custom_extrabold', 'label' => 'فونت ExtraBold / 800', 'name' => 'wm_font_custom_extrabold', 'type' => 'file', 'return_format' => 'array', 'mime_types' => 'woff2,woff', 'conditional_logic' => array( array( array( 'field' => 'field_wm_font_mode', 'operator' => '==', 'value' => 'custom' ) ) ) ),
        wm_site_settings_accordion( 'field_wm_design_font_sizes_accordion', 'سایز و وزن تایپوگرافی' ),
        array( 'key' => 'field_wm_font_size_base', 'label' => 'سایز پایه فونت', 'name' => 'wm_font_size_base', 'type' => 'text', 'default_value' => '15px', 'instructions' => 'مثال: 15px یا 1rem' ),
        array( 'key' => 'field_wm_font_size_h1', 'label' => 'سایز عنوان H1', 'name' => 'wm_font_size_h1', 'type' => 'text', 'default_value' => '32px' ),
        array( 'key' => 'field_wm_font_size_h2', 'label' => 'سایز عنوان H2', 'name' => 'wm_font_size_h2', 'type' => 'text', 'default_value' => '26px' ),
        array( 'key' => 'field_wm_font_size_h3', 'label' => 'سایز عنوان H3', 'name' => 'wm_font_size_h3', 'type' => 'text', 'default_value' => '20px' ),
        array( 'key' => 'field_wm_font_size_small', 'label' => 'سایز متن کوچک', 'name' => 'wm_font_size_small', 'type' => 'text', 'default_value' => '13px' ),
        array( 'key' => 'field_wm_line_height_body', 'label' => 'Line Height متن', 'name' => 'wm_line_height_body', 'type' => 'number', 'default_value' => 1.9, 'min' => 1, 'max' => 2.4, 'step' => 0.1 ),
        array( 'key' => 'field_wm_font_weight_heading', 'label' => 'وزن عنوان‌ها', 'name' => 'wm_font_weight_heading', 'type' => 'number', 'default_value' => 800, 'min' => 300, 'max' => 900, 'step' => 100 ),
        array( 'key' => 'field_wm_font_weight_body', 'label' => 'وزن متن معمولی', 'name' => 'wm_font_weight_body', 'type' => 'number', 'default_value' => 400, 'min' => 300, 'max' => 900, 'step' => 100 ),
        wm_site_settings_accordion( 'field_wm_design_font_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_design_tab_layout', 'ابعاد و فاصله‌ها' ),
        array( 'key' => 'field_wm_site_width', 'label' => 'عرض کلی سایت', 'name' => 'wm_site_width', 'type' => 'number', 'default_value' => 1200, 'min' => 1040, 'max' => 1440, 'step' => 20, 'instructions' => 'به CSS variable --wm-content-width متصل است.' ),
        array( 'key' => 'field_wm_global_radius', 'label' => 'Radius کلی کارت‌ها', 'name' => 'wm_global_radius', 'type' => 'number', 'default_value' => 24, 'min' => 8, 'max' => 40, 'instructions' => 'به --wm-radius-lg متصل است.' ),
        array( 'key' => 'field_wm_design_density', 'label' => 'تراکم کلی طراحی', 'name' => 'wm_design_density', 'type' => 'select', 'choices' => array( 'compact' => 'فشرده', 'standard' => 'استاندارد', 'spacious' => 'باز' ), 'default_value' => 'standard', 'instructions' => 'فعلا class روی body اضافه می‌کند: wm-density-*' ),
        wm_site_settings_accordion( 'field_wm_design_decorative_motifs_accordion', 'عناصر تزئینی بک‌گراند' ),
        array( 'key' => 'field_wm_enable_decorative_motifs', 'label' => 'فعال‌سازی تزئینات ظریف پس‌زمینه', 'name' => 'wm_enable_decorative_motifs', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'برای خاموش کردن موتیف‌های تزئینی CSS در بخش‌های مجاز صفحه استفاده می‌شود.' ),
        array( 'key' => 'field_wm_decorative_motifs_intensity', 'label' => 'شدت کلی استفاده', 'name' => 'wm_decorative_motifs_intensity', 'type' => 'select', 'choices' => array( 'low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد' ), 'default_value' => 'medium', 'instructions' => 'کم برای ظاهر بسیار مینیمال، متوسط برای حالت پیش‌فرض، زیاد برای تاکید بصری بیشتر.' ),
        array( 'key' => 'field_wm_decorative_motifs_color', 'label' => 'رنگ عناصر تزئینی', 'name' => 'wm_decorative_motifs_color', 'type' => 'color_picker', 'default_value' => '#C89B3C', 'instructions' => 'اگر خالی باشد از رنگ accent سایت استفاده می‌شود.' ),
        array( 'key' => 'field_wm_decorative_motifs_opacity', 'label' => 'شفافیت عناصر تزئینی', 'name' => 'wm_decorative_motifs_opacity', 'type' => 'number', 'default_value' => 0.16, 'min' => 0, 'max' => 1, 'step' => 0.01, 'instructions' => 'مقدار بین 0 و 1. پیشنهاد: 0.08 تا 0.26.' ),
        array( 'key' => 'field_wm_decor_home_intensity', 'label' => 'شدت در صفحه اصلی', 'name' => 'wm_decor_home_intensity', 'type' => 'select', 'choices' => array( 'inherit' => 'پیروی از تنظیم کلی', 'off' => 'خاموش', 'low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد' ), 'default_value' => 'inherit' ),
        array( 'key' => 'field_wm_decor_product_intensity', 'label' => 'شدت در صفحه محصول', 'name' => 'wm_decor_product_intensity', 'type' => 'select', 'choices' => array( 'inherit' => 'پیروی از تنظیم کلی', 'off' => 'خاموش', 'low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد' ), 'default_value' => 'inherit' ),
        array( 'key' => 'field_wm_decor_archive_intensity', 'label' => 'شدت در صفحات آرشیو / فروشگاه', 'name' => 'wm_decor_archive_intensity', 'type' => 'select', 'choices' => array( 'inherit' => 'پیروی از تنظیم کلی', 'off' => 'خاموش', 'low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد' ), 'default_value' => 'inherit' ),
        array( 'key' => 'field_wm_decor_page_intensity', 'label' => 'شدت در صفحات معمولی', 'name' => 'wm_decor_page_intensity', 'type' => 'select', 'choices' => array( 'inherit' => 'پیروی از تنظیم کلی', 'off' => 'خاموش', 'low' => 'کم', 'medium' => 'متوسط', 'high' => 'زیاد' ), 'default_value' => 'inherit' ),
        wm_site_settings_accordion( 'field_wm_design_decorative_motifs_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_design_tab_hero_progress', 'نوار پیشرفت اسلایدر' ),
        wm_site_settings_accordion( 'field_wm_design_hero_progress_accordion', 'نوار پیشرفت پخش خودکار' ),
        array( 'key' => 'field_wm_hero_progress_color', 'label' => 'رنگ نوار پیشرفت', 'name' => 'wm_hero_progress_color', 'type' => 'color_picker', 'default_value' => '#C89B3C', 'instructions' => 'رنگ نوار پیشرفت پخش خودکار اسلایدر Hero. پیش‌فرض: رنگ Accent سایت.' ),
        array( 'key' => 'field_wm_hero_progress_direction', 'label' => 'جهت پر شدن نوار', 'name' => 'wm_hero_progress_direction', 'type' => 'select', 'choices' => array( 'right' => 'راست به چپ', 'left' => 'چپ به راست' ), 'default_value' => 'right', 'instructions' => 'جهت رشد نوار پیشرفت؛ پیش‌فرض راست به چپ متناسب با چیدمان RTL.' ),
        wm_site_settings_accordion( 'field_wm_design_hero_progress_end', '', 1 ),
    );
}

function wm_home_acf_product_source_field( $key, $name, $label, $choices, $default_value ) {
    return array(
        'key'           => $key,
        'label'         => $label,
        'name'          => $name,
        'type'          => 'select',
        'choices'       => $choices,
        'default_value' => $default_value,
        'ui'            => 1,
        'instructions'  => 'در حالت دستی، محصولات از همین پنل انتخاب می‌شوند. در حالت ترکیبی، انتخاب دستی اولویت دارد و کمبود با لیست خودکار پر می‌شود.',
    );
}

function wm_acf_archive_filter_taxonomy_choices() {
    $choices = array();

    if ( function_exists( 'get_object_taxonomies' ) ) {
        foreach ( get_object_taxonomies( 'product', 'objects' ) as $taxonomy => $object ) {
            if ( empty( $object->public ) || in_array( $taxonomy, array( 'product_type', 'product_visibility', 'product_shipping_class' ), true ) ) {
                continue;
            }

            $choices[ $taxonomy ] = $object->labels->singular_name ? $object->labels->singular_name : $object->label;
        }
    }

    if ( function_exists( 'wc_get_attribute_taxonomies' ) && function_exists( 'wc_attribute_taxonomy_name' ) ) {
        foreach ( wc_get_attribute_taxonomies() as $attribute ) {
            if ( empty( $attribute->attribute_name ) ) {
                continue;
            }

            $taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );
            $choices[ $taxonomy ] = $attribute->attribute_label;
        }
    }

    return $choices;
}

function wm_site_settings_archive_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_archive_tab_layout', 'چیدمان آرشیو' ),
        wm_site_settings_accordion( 'field_wm_archive_layout_accordion', 'گرید و تعداد محصولات' ),
        array( 'key' => 'field_wm_archive_products_per_page', 'label' => 'تعداد محصولات در هر صفحه', 'name' => 'wm_archive_products_per_page', 'type' => 'number', 'default_value' => 12, 'min' => 1, 'max' => 48, 'step' => 1 ),
        array( 'key' => 'field_wm_archive_columns_desktop', 'label' => 'ستون‌های دسکتاپ', 'name' => 'wm_archive_columns_desktop', 'type' => 'number', 'default_value' => 3, 'min' => 2, 'max' => 4, 'step' => 1 ),
        array( 'key' => 'field_wm_archive_columns_tablet', 'label' => 'ستون‌های تبلت', 'name' => 'wm_archive_columns_tablet', 'type' => 'number', 'default_value' => 2, 'min' => 1, 'max' => 3, 'step' => 1 ),
        array( 'key' => 'field_wm_archive_columns_mobile', 'label' => 'ستون‌های موبایل', 'name' => 'wm_archive_columns_mobile', 'type' => 'number', 'default_value' => 2, 'min' => 1, 'max' => 2, 'step' => 1 ),
        wm_site_settings_accordion( 'field_wm_archive_layout_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_archive_tab_filters', 'فیلترها' ),
        wm_site_settings_accordion( 'field_wm_archive_filters_accordion', 'سایدبار فیلترها' ),
        array( 'key' => 'field_wm_archive_sidebar_enabled', 'label' => 'فعال‌سازی سایدبار فیلترها', 'name' => 'wm_archive_sidebar_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_custom_filters_enabled', 'label' => 'فعال‌سازی فیلتر اختصاصی قالب', 'name' => 'wm_archive_custom_filters_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'در آرشیو/فروشگاه، خروجی فیلترهای قالب جایگزین YITH می‌شود.' ),
        array( 'key' => 'field_wm_archive_filter_ajax_enabled', 'label' => 'فعال بودن Ajax فیلترها', 'name' => 'wm_archive_filter_ajax_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'در صورت فعال بودن، فیلترها بدون reload کامل صفحه و با بروزرسانی URL اعمال می‌شوند.' ),
        array( 'key' => 'field_wm_archive_filter_price_enabled', 'label' => 'نمایش فیلتر قیمت', 'name' => 'wm_archive_filter_price_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_stock_enabled', 'label' => 'نمایش فیلتر موجودی', 'name' => 'wm_archive_filter_stock_enabled', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_show_counts', 'label' => 'نمایش تعداد کنار گزینه‌ها', 'name' => 'wm_archive_filter_show_counts', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_empty_behavior', 'label' => 'رفتار گزینه‌های بدون نتیجه', 'name' => 'wm_archive_filter_empty_behavior', 'type' => 'select', 'choices' => array( 'hide' => 'مخفی', 'disable' => 'غیرفعال', 'show' => 'نمایش' ), 'default_value' => 'hide', 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_term_limit', 'label' => 'حداکثر گزینه در هر فیلتر', 'name' => 'wm_archive_filter_term_limit', 'type' => 'number', 'default_value' => 24, 'min' => 1, 'max' => 200, 'step' => 1 ),
        array( 'key' => 'field_wm_archive_filter_price_step', 'label' => 'گام تغییر قیمت', 'name' => 'wm_archive_filter_price_step', 'type' => 'number', 'default_value' => 10000, 'min' => 1, 'step' => 1, 'instructions' => 'روی range slider و رفتار input قیمت اعمال می‌شود.' ),
        array( 'key' => 'field_wm_archive_filter_accordion_default', 'label' => 'وضعیت پیش‌فرض گروه‌های فیلتر', 'name' => 'wm_archive_filter_accordion_default', 'type' => 'select', 'choices' => array( 'closed' => 'بسته', 'open' => 'باز' ), 'default_value' => 'closed', 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_hierarchy_enabled', 'label' => 'نمایش سلسله‌مراتبی taxonomyهای درختی', 'name' => 'wm_archive_filter_hierarchy_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_hierarchy_toggle', 'label' => 'نمایش + / - برای parentها', 'name' => 'wm_archive_filter_hierarchy_toggle', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_hierarchy_depth', 'label' => 'حداکثر سطح نمایش hierarchy', 'name' => 'wm_archive_filter_hierarchy_depth', 'type' => 'number', 'default_value' => 4, 'min' => 1, 'max' => 8, 'step' => 1 ),
        array( 'key' => 'field_wm_archive_filter_taxonomies', 'label' => 'taxonomy / attributeهای قابل نمایش', 'name' => 'wm_archive_filter_taxonomies', 'type' => 'select', 'choices' => wm_acf_archive_filter_taxonomy_choices(), 'multiple' => 1, 'ui' => 1, 'allow_null' => 1, 'instructions' => 'اگر خالی باشد، دسته‌بندی، برند، attributeهای ووکامرس و taxonomyهای عمومی محصول به‌صورت خودکار نمایش داده می‌شوند.' ),
        array( 'key' => 'field_wm_archive_sidebar_default_state', 'label' => 'وضعیت پیش‌فرض سایدبار', 'name' => 'wm_archive_sidebar_default_state', 'type' => 'select', 'choices' => array( 'open' => 'باز', 'closed' => 'بسته' ), 'default_value' => 'open', 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_filter_sidebar_position', 'label' => 'جایگاه سایدبار در دسکتاپ', 'name' => 'wm_archive_filter_sidebar_position', 'type' => 'select', 'choices' => array( 'right' => 'راست', 'left' => 'چپ' ), 'default_value' => 'right', 'ui' => 1 ),
        wm_site_settings_accordion( 'field_wm_archive_filters_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_archive_tab_toolbar', 'نوار کنترل' ),
        wm_site_settings_accordion( 'field_wm_archive_toolbar_accordion', 'مرتب‌سازی و شمارش' ),
        array( 'key' => 'field_wm_archive_show_result_count', 'label' => 'نمایش تعداد محصولات', 'name' => 'wm_archive_show_result_count', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_show_ordering', 'label' => 'نمایش مرتب‌سازی', 'name' => 'wm_archive_show_ordering', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_show_archive_description', 'label' => 'نمایش توضیح آرشیو', 'name' => 'wm_archive_show_archive_description', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        wm_site_settings_accordion( 'field_wm_archive_toolbar_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_archive_tab_cards', 'کارت محصول' ),
        wm_site_settings_accordion( 'field_wm_archive_cards_accordion', 'رفتار کارت‌ها' ),
        array( 'key' => 'field_wm_archive_card_style', 'label' => 'استایل کارت', 'name' => 'wm_archive_card_style', 'type' => 'select', 'choices' => array( 'site_default' => 'هماهنگ با سایت' ), 'default_value' => 'site_default', 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_enable_card_hover_image', 'label' => 'تصویر دوم در hover', 'name' => 'wm_archive_enable_card_hover_image', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_wm_archive_enable_ajax_add_to_cart', 'label' => 'افزودن به سبد خرید Ajax', 'name' => 'wm_archive_enable_ajax_add_to_cart', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        wm_site_settings_accordion( 'field_wm_archive_cards_end', '', 1 ),
    );
}

function wm_home_acf_product_relationship_field( $key, $name, $label, $source_field_key, $auto_value ) {
    return array(
        'key'               => $key,
        'label'             => $label,
        'name'              => $name,
        'type'              => 'relationship',
        'post_type'         => array( 'product' ),
        'filters'           => array( 'search' ),
        'elements'          => array( 'featured_image' ),
        'return_format'     => 'object',
        'min'               => 0,
        'max'               => 20,
        'instructions'      => 'فقط محصولات منتشرشده نمایش داده می‌شوند. ترتیب انتخاب در فرانت‌اند حفظ می‌شود.',
        'conditional_logic' => array(
            array(
                array(
                    'field'    => $source_field_key,
                    'operator' => '!=',
                    'value'    => $auto_value,
                ),
            ),
        ),
    );
}

function wm_home_acf_sections_order_field() {
    return array(
        'key'           => 'field_home_sections_order',
        'label'         => 'ترتیب سکشن‌های صفحه اصلی',
        'name'          => 'home_sections_order',
        'type'          => 'repeater',
        'layout'        => 'table',
        'button_label'  => 'افزودن سکشن',
        'instructions'  => 'ردیف‌ها را جابه‌جا کنید تا ترتیب نمایش صفحه اصلی تغییر کند. هر سکشن فقط یک بار رندر می‌شود.',
        'default_value' => array(
            array( 'section_enabled' => 1, 'section_key' => 'hero_slider', 'section_label' => 'Hero Slider' ),
            array( 'section_enabled' => 1, 'section_key' => 'brand_categories', 'section_label' => 'برندها' ),
            array( 'section_enabled' => 1, 'section_key' => 'recommended_products', 'section_label' => 'پیشنهاد ما' ),
            array( 'section_enabled' => 1, 'section_key' => 'filter_boxes', 'section_label' => 'باکس‌های فیلتر' ),
            array( 'section_enabled' => 1, 'section_key' => 'bestsellers', 'section_label' => 'پرفروش‌ها' ),
            array( 'section_enabled' => 1, 'section_key' => 'popular_styles', 'section_label' => 'سبک‌های محبوب' ),
            array( 'section_enabled' => 1, 'section_key' => 'trust', 'section_label' => 'اعتمادسازی' ),
        ),
        'sub_fields'    => array(
            array( 'key' => 'field_home_section_order_enabled', 'label' => 'فعال', 'name' => 'section_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
            array(
                'key'     => 'field_home_section_order_key',
                'label'   => 'سکشن',
                'name'    => 'section_key',
                'type'    => 'select',
                'choices' => array(
                    'hero_slider'          => 'Hero Slider',
                    'brand_categories'     => 'برندها',
                    'recommended_products' => 'پیشنهاد ما',
                    'filter_boxes'         => 'باکس‌های فیلتر',
                    'bestsellers'          => 'پرفروش‌ها',
                    'popular_styles'       => 'سبک‌های محبوب',
                    'trust'                => 'اعتمادسازی',
                ),
                'ui'      => 1,
            ),
            array( 'key' => 'field_home_section_order_label', 'label' => 'برچسب داخلی', 'name' => 'section_label', 'type' => 'text' ),
        ),
    );
}

function wm_home_acf_filter_sections_field() {
    return array(
        'key'          => 'field_home_filter_sections',
        'label'        => 'باکس‌های فیلتر داینامیک',
        'name'         => 'home_filter_sections',
        'type'         => 'repeater',
        'layout'       => 'block',
        'button_label' => 'افزودن گروه فیلتر',
        'instructions' => 'برای هر کمپین یا مسیر خرید، یک گروه مستقل بسازید. لینک‌ها به صفحه آرشیو، ترم یا URL دستی وصل می‌شوند؛ AJAX جدیدی اضافه نشده است.',
        'sub_fields'   => array(
            array( 'key' => 'field_filter_section_enabled', 'label' => 'فعال', 'name' => 'filter_section_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
            array( 'key' => 'field_filter_section_title', 'label' => 'عنوان گروه', 'name' => 'filter_section_title', 'type' => 'text' ),
            array( 'key' => 'field_filter_section_subtitle', 'label' => 'زیرعنوان گروه', 'name' => 'filter_section_subtitle', 'type' => 'textarea', 'rows' => 2 ),
            array(
                'key'           => 'field_filter_section_layout',
                'label'         => 'چیدمان',
                'name'          => 'filter_section_layout',
                'type'          => 'select',
                'choices'       => array(
                    'cards_3'           => 'سه کارت',
                    'cards_4'           => 'چهار کارت',
                    'horizontal_scroll' => 'اسکرول افقی',
                    'compact_grid'      => 'گرید فشرده',
                ),
                'default_value' => 'cards_3',
                'ui'            => 1,
            ),
            array(
                'key'          => 'field_filter_section_items',
                'label'        => 'آیتم‌های فیلتر',
                'name'         => 'filter_section_items',
                'type'         => 'repeater',
                'layout'       => 'block',
                'button_label' => 'افزودن باکس فیلتر',
                'sub_fields'   => wm_home_acf_dynamic_filter_sub_fields(),
            ),
        ),
    );
}

function wm_home_acf_dynamic_filter_sub_fields() {
    return array(
        array( 'key' => 'field_filter_box_enabled', 'label' => 'فعال', 'name' => 'filter_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_filter_box_title', 'label' => 'عنوان', 'name' => 'filter_title', 'type' => 'text' ),
        array( 'key' => 'field_filter_box_subtitle', 'label' => 'زیرعنوان', 'name' => 'filter_subtitle', 'type' => 'text' ),
        array( 'key' => 'field_filter_box_image', 'label' => 'تصویر', 'name' => 'filter_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
        array(
            'key'           => 'field_filter_box_style',
            'label'         => 'استایل کارت',
            'name'          => 'filter_style',
            'type'          => 'select',
            'choices'       => array(
                'dark_card'    => 'تصویری تیره',
                'light_card'   => 'روشن',
                'image_card'   => 'تصویری مینیمال',
                'minimal_card' => 'مینیمال',
            ),
            'default_value' => 'dark_card',
            'ui'            => 1,
        ),
        array( 'key' => 'field_filter_box_badge_text', 'label' => 'Badge', 'name' => 'filter_badge_text', 'type' => 'text' ),
        array( 'key' => 'field_filter_box_button_text', 'label' => 'متن دکمه', 'name' => 'filter_button_text', 'type' => 'text', 'default_value' => 'مشاهده' ),
        array(
            'key'           => 'field_filter_box_link_mode',
            'label'         => 'نوع لینک',
            'name'          => 'filter_link_mode',
            'type'          => 'select',
            'choices'       => array(
                'manual_url'        => 'URL دستی',
                'taxonomy_term'     => 'ترم دسته‌بندی/ویژگی',
                'price_range'       => 'رنج قیمت',
                'taxonomy_and_price'=> 'ترم + رنج قیمت',
                'advanced_query'    => 'Query پیشرفته',
            ),
            'default_value' => 'manual_url',
            'ui'            => 1,
            'instructions'  => 'برای سازگاری با YITH، خروجی فعلا URL استاندارد ووکامرس/ترم است و ساختار دقیق YITH بعدا قابل مپ شدن است.',
        ),
        array( 'key' => 'field_filter_box_manual_url', 'label' => 'URL دستی', 'name' => 'filter_manual_url', 'type' => 'url' ),
        array( 'key' => 'field_filter_box_term', 'label' => 'ترم', 'name' => 'filter_term', 'type' => 'taxonomy', 'taxonomy' => 'product_cat', 'field_type' => 'select', 'return_format' => 'object', 'allow_null' => 1, 'instructions' => 'برای فیلترهای جنسیت/برند می‌توانید فعلا URL دستی یا Query پیشرفته استفاده کنید.' ),
        array( 'key' => 'field_filter_box_min_price', 'label' => 'حداقل قیمت', 'name' => 'filter_min_price', 'type' => 'number' ),
        array( 'key' => 'field_filter_box_max_price', 'label' => 'حداکثر قیمت', 'name' => 'filter_max_price', 'type' => 'number' ),
        array( 'key' => 'field_filter_box_color_value', 'label' => 'رنگ تاکیدی کارت', 'name' => 'filter_color_value', 'type' => 'color_picker' ),
        array(
            'key'           => 'field_filter_box_cover_enabled',
            'label'         => 'نمایش پوشش روی تصویر',
            'name'          => 'filter_cover_enabled',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
            'instructions'  => 'لایه رنگی روی تصویر کارت؛ برای حذف کامل آن این گزینه را غیرفعال کنید.',
        ),
        array(
            'key'           => 'field_filter_box_cover_color',
            'label'         => 'رنگ پوشش کارت',
            'name'          => 'filter_cover_color',
            'type'          => 'color_picker',
            'instructions'  => 'رنگ لایه پوشش روی تصویر کارت؛ اگر خالی بماند از رنگ پیش‌فرض استایل (تیره/روشن) استفاده می‌شود.',
        ),
        array( 'key' => 'field_filter_box_extra_query_args', 'label' => 'Query اضافه', 'name' => 'filter_extra_query_args', 'type' => 'textarea', 'rows' => 3, 'instructions' => 'هر خط به شکل key=value. فقط کلید و مقدار sanitize شده به URL اضافه می‌شود.' ),
    );
}

function wm_site_settings_home_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_home_tab_sections_order', 'ترتیب سکشن‌ها' ),
        wm_site_settings_accordion( 'field_wm_home_sections_order_accordion', 'مدیریت ترتیب نمایش' ),
        wm_home_acf_sections_order_field(),
        wm_site_settings_accordion( 'field_wm_home_sections_order_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_home_tab_hero', 'Hero Slider' ),
        wm_site_settings_accordion( 'field_wm_home_hero_accordion', 'اسلایدها' ),
        array(
            'key'          => 'field_home_hero_slides',
            'label'        => 'Hero Slider',
            'name'         => 'home_hero_slides',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'افزودن اسلاید',
            'instructions'  => 'اگر فقط یک اسلاید فعال باشد، navigation و animation اضافی اجرا نمی‌شود.',
            'sub_fields'   => array(
                array( 'key' => 'field_slide_enabled', 'label' => 'فعال', 'name' => 'slide_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_slide_eyebrow', 'label' => 'Eyebrow', 'name' => 'slide_eyebrow', 'type' => 'text' ),
                array( 'key' => 'field_slide_title', 'label' => 'عنوان', 'name' => 'slide_title', 'type' => 'text' ),
                array( 'key' => 'field_slide_subtitle', 'label' => 'زیرعنوان', 'name' => 'slide_subtitle', 'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'field_slide_image_desktop', 'label' => 'تصویر دسکتاپ', 'name' => 'slide_image_desktop', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
                array( 'key' => 'field_slide_image_mobile', 'label' => 'تصویر موبایل', 'name' => 'slide_image_mobile', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
                array( 'key' => 'field_slide_primary_text', 'label' => 'متن CTA اصلی', 'name' => 'slide_primary_text', 'type' => 'text' ),
                array( 'key' => 'field_slide_primary_url', 'label' => 'لینک CTA اصلی', 'name' => 'slide_primary_url', 'type' => 'url' ),
                array( 'key' => 'field_slide_secondary_text', 'label' => 'متن CTA دوم', 'name' => 'slide_secondary_text', 'type' => 'text' ),
                array( 'key' => 'field_slide_secondary_url', 'label' => 'لینک CTA دوم', 'name' => 'slide_secondary_url', 'type' => 'url' ),
                array( 'key' => 'field_slide_product', 'label' => 'محصول اختیاری', 'name' => 'slide_product', 'type' => 'post_object', 'post_type' => array( 'product' ), 'return_format' => 'object', 'allow_null' => 1 ),
            ),
        ),
        array(
            'key'          => 'field_home_hero_image_slides',
            'label'        => 'اسلایدهای تمام‌تصویر',
            'name'         => 'home_hero_image_slides',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'افزودن اسلاید تمام‌تصویر',
            'instructions' => 'این نوع اسلاید فقط تصویر (بدون متن یا دکمه) با یک لینک است و ارتفاع اسلایدر با ارتفاع تصویر تنظیم می‌شود.',
            'sub_fields'   => array(
                array( 'key' => 'field_image_slide_enabled', 'label' => 'فعال', 'name' => 'image_slide_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_image_slide_image_desktop', 'label' => 'تصویر دسکتاپ', 'name' => 'image_slide_image_desktop', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
                array( 'key' => 'field_image_slide_image_mobile', 'label' => 'تصویر موبایل', 'name' => 'image_slide_image_mobile', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
                array( 'key' => 'field_image_slide_url', 'label' => 'لینک اسلاید', 'name' => 'image_slide_url', 'type' => 'url', 'instructions' => 'کل تصویر به این لینک متصل می‌شود.' ),
            ),
        ),
        array(
            'key'          => 'field_home_hero_video_slides',
            'label'        => 'اسلایدهای ویدیویی',
            'name'         => 'home_hero_video_slides',
            'type'         => 'repeater',
            'layout'       => 'block',
            'button_label' => 'افزودن اسلاید ویدیویی',
            'instructions' => 'ویدیو به‌صورت تمام‌عرض و بدون کنترل‌ها (نه پلیر) پخش و حلقه می‌شود؛ بی‌صدا و خودکار. کل ویدیو به لینک اسلاید متصل می‌شود.',
            'sub_fields'   => array(
                array( 'key' => 'field_video_slide_enabled', 'label' => 'فعال', 'name' => 'video_slide_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array(
                    'key'           => 'field_video_slide_video_desktop',
                    'label'         => 'ویدیو دسکتاپ',
                    'name'          => 'video_slide_video_desktop',
                    'type'          => 'file',
                    'return_format' => 'array',
                    'mime_types'    => 'mp4,webm,ogv',
                    'instructions'  => 'فایل MP4 / WebM. بدون دکمه‌های پخش، بی‌صدا و با حلقه پخش می‌شود.',
                ),
                array(
                    'key'           => 'field_video_slide_video_mobile',
                    'label'         => 'ویدیو موبایل',
                    'name'          => 'video_slide_video_mobile',
                    'type'          => 'file',
                    'return_format' => 'array',
                    'mime_types'    => 'mp4,webm,ogv',
                    'instructions'  => 'اختیاری؛ اگر خالی باشد همان ویدیو دسکتاپ استفاده می‌شود.',
                ),
                array(
                    'key'          => 'field_video_slide_url',
                    'label'        => 'لینک اسلاید',
                    'name'         => 'video_slide_url',
                    'type'         => 'url',
                    'instructions' => 'کل ویدیو به این لینک متصل می‌شود.',
                ),
            ),
        ),
        wm_site_settings_accordion( 'field_wm_home_hero_end', '', 1 ),

        wm_site_settings_accordion( 'field_wm_home_hero_autoplay_accordion', 'پخش خودکار' ),
        array( 'key' => 'field_home_hero_autoplay', 'label' => 'پخش خودکار اسلایدها', 'name' => 'home_hero_autoplay', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1, 'instructions' => 'اسلایدها به‌صورت خودکار و با فاصله زمانی مشخص جابه‌جا می‌شوند.' ),
        array( 'key' => 'field_home_hero_autoplay_interval', 'label' => 'فاصله زمانی (میلی‌ثانیه)', 'name' => 'home_hero_autoplay_interval', 'type' => 'number', 'default_value' => 5000, 'min' => 1500, 'max' => 30000, 'step' => 500, 'instructions' => 'هر اسلاید چند میلی‌ثانیه نمایش داده شود (5000 = ۵ ثانیه).', 'conditional_logic' => array( array( array( 'field' => 'field_home_hero_autoplay', 'operator' => '==', 'value' => '1' ) ) ) ),
        array( 'key' => 'field_home_hero_autoplay_pause_hover', 'label' => 'توقف هنگام hover', 'name' => 'home_hero_autoplay_pause_hover', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'با قرار دادن نشانگر روی اسلایدر، پخش خودکار متوقف شود.', 'conditional_logic' => array( array( array( 'field' => 'field_home_hero_autoplay', 'operator' => '==', 'value' => '1' ) ) ) ),
        wm_site_settings_accordion( 'field_wm_home_hero_autoplay_end', '', 1 ),

        wm_site_settings_tab( 'field_wm_home_tab_brands', 'برندها' ),
        array(
            'key'          => 'field_home_brand_items',
            'label'        => 'برندهای محبوب',
            'name'         => 'home_brand_items',
            'type'         => 'repeater',
            'button_label' => 'افزودن برند',
            'instructions'  => 'لوگو داخل قاب کنترل‌شده نمایش داده می‌شود و تعداد محصول فارسی است.',
            'sub_fields'   => array(
                array( 'key' => 'field_brand_enabled', 'label' => 'فعال', 'name' => 'brand_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_brand_term', 'label' => 'برند', 'name' => 'brand_term', 'type' => 'taxonomy', 'taxonomy' => 'product_brand', 'field_type' => 'select', 'return_format' => 'object', 'allow_null' => 1 ),
                array( 'key' => 'field_brand_image', 'label' => 'تصویر', 'name' => 'brand_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'thumbnail' ),
                array( 'key' => 'field_brand_subtitle', 'label' => 'زیرعنوان', 'name' => 'brand_subtitle', 'type' => 'text' ),
                array( 'key' => 'field_brand_cover_enabled', 'label' => 'نمایش پوشش کارت', 'name' => 'brand_cover_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'اگر غیرفعال شود، پس‌زمینه روشن کارت حذف می‌شود.' ),
                array( 'key' => 'field_brand_cover_color', 'label' => 'رنگ پوشش کارت', 'name' => 'brand_cover_color', 'type' => 'color_picker', 'instructions' => 'رنگ پس‌زمینه کارت؛ اگر خالی بماند از سفید/روشن پیش‌فرض استفاده می‌شود.' ),
            ),
        ),

        wm_site_settings_tab( 'field_wm_home_tab_recommended', 'پیشنهاد ما' ),
        array( 'key' => 'field_home_recommended_enabled', 'label' => 'نمایش پیشنهاد ما', 'name' => 'home_recommended_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'محصولاتی که در صفحه محصول تیک پیشنهاد ما دارند نمایش داده می‌شوند.' ),
        array( 'key' => 'field_home_recommended_title', 'label' => 'عنوان پیشنهاد ما', 'name' => 'home_recommended_title', 'type' => 'text', 'default_value' => 'پیشنهاد ما' ),
        array( 'key' => 'field_home_recommended_subtitle', 'label' => 'زیرعنوان پیشنهاد ما', 'name' => 'home_recommended_subtitle', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_home_recommended_count', 'label' => 'تعداد پیشنهادها', 'name' => 'home_recommended_count', 'type' => 'number', 'default_value' => 10, 'min' => 1, 'max' => 20 ),

        wm_site_settings_tab( 'field_wm_home_tab_women_filters', 'فیلترهای زنانه / دخترانه' ),
        array( 'key' => 'field_home_women_quick_filters', 'label' => 'فیلترهای سریع زنانه / دخترانه', 'name' => 'home_women_quick_filters', 'type' => 'repeater', 'button_label' => 'افزودن فیلتر', 'instructions' => 'برای مسیر خرید سریع استفاده می‌شود.', 'sub_fields' => wm_home_acf_filter_sub_fields( 'women' ) ),

        wm_site_settings_tab( 'field_wm_home_tab_bestsellers', 'پرفروش‌ها' ),
        array( 'key' => 'field_home_bestsellers_enabled', 'label' => 'نمایش پرفروش‌ها', 'name' => 'home_bestsellers_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_home_bestsellers_title', 'label' => 'عنوان پرفروش‌ها', 'name' => 'home_bestsellers_title', 'type' => 'text', 'default_value' => 'پرفروش‌ها' ),
        array( 'key' => 'field_home_bestsellers_subtitle', 'label' => 'زیرعنوان پرفروش‌ها', 'name' => 'home_bestsellers_subtitle', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'field_home_bestsellers_count', 'label' => 'تعداد پرفروش‌ها', 'name' => 'home_bestsellers_count', 'type' => 'number', 'default_value' => 10, 'min' => 1, 'max' => 20 ),

        wm_site_settings_tab( 'field_wm_home_tab_men_filters', 'فیلترهای مردانه / پسرانه' ),
        array( 'key' => 'field_home_men_quick_filters', 'label' => 'فیلترهای سریع مردانه / پسرانه', 'name' => 'home_men_quick_filters', 'type' => 'repeater', 'button_label' => 'افزودن فیلتر', 'instructions' => 'برای مسیر خرید سریع استفاده می‌شود.', 'sub_fields' => wm_home_acf_filter_sub_fields( 'men' ) ),

        wm_site_settings_tab( 'field_wm_home_tab_styles', 'سبک‌های محبوب' ),
        array(
            'key'          => 'field_home_popular_styles',
            'label'        => 'سبک‌های محبوب',
            'name'         => 'home_popular_styles',
            'type'         => 'repeater',
            'button_label' => 'افزودن سبک',
            'sub_fields'   => array(
                array( 'key' => 'field_style_enabled', 'label' => 'فعال', 'name' => 'style_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_style_title', 'label' => 'عنوان', 'name' => 'style_title', 'type' => 'text' ),
                array( 'key' => 'field_style_subtitle', 'label' => 'زیرعنوان', 'name' => 'style_subtitle', 'type' => 'text' ),
                array( 'key' => 'field_style_term', 'label' => 'ترم', 'name' => 'style_term', 'type' => 'taxonomy', 'taxonomy' => 'style', 'field_type' => 'select', 'return_format' => 'object', 'allow_null' => 1 ),
                array( 'key' => 'field_style_image', 'label' => 'تصویر', 'name' => 'style_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
                array( 'key' => 'field_style_url', 'label' => 'لینک دستی', 'name' => 'style_url', 'type' => 'url' ),
                array( 'key' => 'field_style_cover_enabled', 'label' => 'نمایش پوشش کارت', 'name' => 'style_cover_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'اگر غیرفعال شود، پس‌زمینه روشن کارت حذف می‌شود.' ),
                array( 'key' => 'field_style_cover_color', 'label' => 'رنگ پوشش کارت', 'name' => 'style_cover_color', 'type' => 'color_picker', 'instructions' => 'رنگ پس‌زمینه کارت؛ اگر خالی بماند از سفید/روشن پیش‌فرض استفاده می‌شود.' ),
            ),
        ),

        wm_site_settings_tab( 'field_wm_home_tab_trust', 'اعتمادسازی' ),
        array(
            'key'          => 'field_home_trust_items',
            'label'        => 'مزیت‌های فروشگاه',
            'name'         => 'home_trust_items',
            'type'         => 'repeater',
            'button_label' => 'افزودن مزیت',
            'sub_fields'   => array(
                array( 'key' => 'field_trust_enabled', 'label' => 'فعال', 'name' => 'trust_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
                array( 'key' => 'field_trust_title', 'label' => 'عنوان', 'name' => 'trust_title', 'type' => 'text' ),
                array( 'key' => 'field_trust_text', 'label' => 'متن', 'name' => 'trust_text', 'type' => 'text' ),
                array( 'key' => 'field_trust_icon', 'label' => 'آیکن', 'name' => 'trust_icon', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'thumbnail' ),
            ),
        ),
    );
}

function wm_site_settings_home_fields_dynamic() {
    $fields = wm_site_settings_home_fields();
    $legacy = array();

    foreach ( $fields as $index => $field ) {
        if ( ! isset( $field['key'] ) ) {
            continue;
        }

        if ( in_array( $field['key'], array( 'field_wm_home_tab_women_filters', 'field_wm_home_tab_men_filters' ), true ) ) {
            unset( $fields[ $index ] );
        }

        if ( in_array( $field['key'], array( 'field_home_women_quick_filters', 'field_home_men_quick_filters' ), true ) ) {
            $legacy[] = $field;
            unset( $fields[ $index ] );
        }
    }

    $fields = array_values( $fields );
    $fields = wm_acf_insert_fields_after_key(
        $fields,
        'field_home_recommended_count',
        array(
            wm_home_acf_product_source_field(
                'field_home_recommended_source',
                'home_recommended_source',
                'منبع محصولات پیشنهاد ما',
                array(
                    'auto_meta'       => 'خودکار: تیک پیشنهاد ما',
                    'manual_products' => 'دستی',
                    'mixed'           => 'ترکیبی',
                ),
                'auto_meta'
            ),
            wm_home_acf_product_relationship_field( 'field_home_recommended_products', 'home_recommended_products', 'انتخاب دستی محصولات پیشنهاد ما', 'field_home_recommended_source', 'auto_meta' ),
            wm_site_settings_tab( 'field_wm_home_tab_filter_boxes', 'باکس‌های فیلتر' ),
            wm_site_settings_accordion( 'field_wm_home_filter_boxes_accordion', 'گروه‌های فیلتر داینامیک' ),
            wm_home_acf_filter_sections_field(),
            wm_site_settings_accordion( 'field_wm_home_filter_boxes_end', '', 1 ),
        )
    );

    $fields = wm_acf_insert_fields_after_key(
        $fields,
        'field_home_bestsellers_count',
        array(
            wm_home_acf_product_source_field(
                'field_home_bestsellers_source',
                'home_bestsellers_source',
                'منبع محصولات پرفروش',
                array(
                    'auto_sales'      => 'خودکار: فروش ووکامرس',
                    'manual_products' => 'دستی',
                    'mixed'           => 'ترکیبی',
                ),
                'auto_sales'
            ),
            wm_home_acf_product_relationship_field( 'field_home_bestsellers_products', 'home_bestsellers_products', 'انتخاب دستی محصولات پرفروش', 'field_home_bestsellers_source', 'auto_sales' ),
        )
    );

    foreach ( $fields as &$field ) {
        if ( isset( $field['key'] ) && 'field_home_popular_styles' === $field['key'] && isset( $field['sub_fields'] ) ) {
            $field['sub_fields'][] = array(
                'key'           => 'field_style_products',
                'label'         => 'محصولات این سبک',
                'name'          => 'style_products',
                'type'          => 'relationship',
                'post_type'     => array( 'product' ),
                'filters'       => array( 'search' ),
                'elements'      => array( 'featured_image' ),
                'return_format' => 'object',
                'max'           => 12,
                'instructions'  => 'برای نمایش یا استفاده در توسعه‌های بعدی ذخیره می‌شود و به خود سبک وابسته است.',
            );
        }
    }
    unset( $field );

    if ( $legacy ) {
        $legacy_fields = array_merge(
            array(
                wm_site_settings_tab( 'field_wm_home_tab_legacy_filters', 'Legacy / قدیمی' ),
                array(
                    'key'     => 'field_home_legacy_filters_note',
                    'label'   => 'سازگاری با فیلترهای قبلی',
                    'name'    => '',
                    'type'    => 'message',
                    'message' => 'این فیلدها فقط برای حفظ داده‌های نسخه‌های قبلی هستند. ساختار اصلی جدید از تب «باکس‌های فیلتر» و سکشن filter_boxes استفاده می‌کند.',
                ),
            ),
            $legacy
        );
        $fields = wm_acf_insert_fields_before_key( $fields, 'field_wm_home_tab_trust', $legacy_fields );
    }

    return $fields;
}

function wm_acf_insert_fields_after_key( $fields, $target_key, $insert_fields ) {
    $output = array();

    foreach ( $fields as $field ) {
        $output[] = $field;
        if ( isset( $field['key'] ) && $target_key === $field['key'] ) {
            foreach ( $insert_fields as $insert_field ) {
                $output[] = $insert_field;
            }
        }
    }

    return $output;
}

function wm_acf_insert_fields_before_key( $fields, $target_key, $insert_fields ) {
    $output = array();

    foreach ( $fields as $field ) {
        if ( isset( $field['key'] ) && $target_key === $field['key'] ) {
            foreach ( $insert_fields as $insert_field ) {
                $output[] = $insert_field;
            }
        }
        $output[] = $field;
    }

    return $output;
}

function wm_site_settings_product_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_product_tab_general', 'تنظیمات کلی' ),
        array( 'key' => 'field_wm_product_tabs_enabled', 'label' => 'نمایش تب توضیحات / نظرات', 'name' => 'wm_product_tabs_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1, 'instructions' => 'تب توضیحات و نظرات در صفحه محصول.' ),

        wm_site_settings_tab( 'field_wm_product_tab_gallery', 'گالری محصول' ),
        array( 'key' => 'field_wm_product_gallery_sticky', 'label' => 'فعال بودن گالری Sticky در دسکتاپ', 'name' => 'wm_product_gallery_sticky', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),

        wm_site_settings_tab( 'field_wm_product_tab_specs', 'مشخصات' ),
        array( 'key' => 'field_wm_product_mobile_specs_visible_count', 'label' => 'تعداد مشخصات قابل نمایش در موبایل', 'name' => 'wm_product_mobile_specs_visible_count', 'type' => 'number', 'default_value' => 3, 'min' => 1, 'max' => 12 ),

        wm_site_settings_tab( 'field_wm_product_tab_purchase', 'کارت خرید' ),
        array(
            'key'          => 'field_wm_product_trust_items',
            'label'        => 'متن‌های اعتمادسازی کارت خرید',
            'name'         => 'wm_product_trust_items',
            'type'         => 'repeater',
            'button_label' => 'افزودن مورد',
            'sub_fields'   => array(
                array( 'key' => 'field_wm_product_trust_text', 'label' => 'متن', 'name' => 'text', 'type' => 'text' ),
            ),
        ),

        wm_site_settings_tab( 'field_wm_product_tab_mobile', 'موبایل نوبار' ),
        array( 'key' => 'field_wm_product_mobile_bottom_bar_enabled', 'label' => 'فعال بودن Mobile Bottom Bar', 'name' => 'wm_product_mobile_bottom_bar_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),

        wm_site_settings_tab( 'field_wm_product_tab_related', 'محصولات مشابه' ),
        array( 'key' => 'field_wm_product_related_count', 'label' => 'تعداد محصولات مشابه', 'name' => 'wm_product_related_count', 'type' => 'number', 'default_value' => 10, 'min' => 1, 'max' => 20 ),
    );
}

function wm_home_acf_filter_sub_fields( $prefix ) {
    return array(
        array( 'key' => 'field_' . $prefix . '_filter_enabled', 'label' => 'فعال', 'name' => 'filter_enabled', 'type' => 'true_false', 'default_value' => 1, 'ui' => 1 ),
        array( 'key' => 'field_' . $prefix . '_filter_title', 'label' => 'عنوان', 'name' => 'filter_title', 'type' => 'text' ),
        array( 'key' => 'field_' . $prefix . '_filter_subtitle', 'label' => 'زیرعنوان', 'name' => 'filter_subtitle', 'type' => 'text' ),
        array( 'key' => 'field_' . $prefix . '_filter_image', 'label' => 'تصویر', 'name' => 'filter_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
        array( 'key' => 'field_' . $prefix . '_filter_url', 'label' => 'لینک دستی', 'name' => 'filter_url', 'type' => 'url' ),
        array( 'key' => 'field_' . $prefix . '_filter_gender_term', 'label' => 'ترم جنسیت', 'name' => 'filter_gender_term', 'type' => 'taxonomy', 'taxonomy' => 'gender', 'field_type' => 'select', 'return_format' => 'object', 'allow_null' => 1 ),
        array( 'key' => 'field_' . $prefix . '_filter_min_price', 'label' => 'حداقل قیمت', 'name' => 'filter_min_price', 'type' => 'number' ),
        array( 'key' => 'field_' . $prefix . '_filter_max_price', 'label' => 'حداکثر قیمت', 'name' => 'filter_max_price', 'type' => 'number' ),
    );
}

function wm_site_settings_technical_fields() {
    return array(
        wm_site_settings_tab( 'field_wm_technical_tab_whitelabel', 'هویت بصری مدیریت' ),
        array(
            'key'           => 'field_wm_technical_login_logo',
            'label'         => 'لوگوی صفحه ورود ادمین',
            'name'          => 'wm_technical_login_logo',
            'type'          => 'image',
            'return_format' => 'id',
            'preview_size'  => 'medium',
            'instructions'  => 'در صورت تعیین، لوگوی پیش‌فرض وردپرس در صفحه ورود با این تصویر جایگزین می‌شود.',
        ),
        array(
            'key'          => 'field_wm_technical_admin_footer_text',
            'label'        => 'متن فوتر پنل مدیریت',
            'name'         => 'wm_technical_admin_footer_text',
            'type'         => 'text',
            'instructions' => 'در صورت خالی بودن، متن پیش‌فرض وردپرس نمایش داده می‌شود.',
        ),
        array(
            'key'          => 'field_wm_technical_dashboard_title',
            'label'        => 'عنوان داشبورد مدیریت',
            'name'         => 'wm_technical_dashboard_title',
            'type'         => 'text',
            'instructions' => 'در صورت خالی بودن، «داشبورد» پیش‌فرض وردپرس نمایش داده می‌شود.',
        ),

        wm_site_settings_tab( 'field_wm_technical_tab_plugins', 'افزونه‌های مورد نیاز' ),
        array(
            'key'     => 'field_wm_technical_plugins_status',
            'label'   => 'وضعیت افزونه‌ها',
            'name'    => '',
            'type'    => 'message',
            'message' => wm_technical_required_plugins_status_html(),
        ),

        wm_site_settings_tab( 'field_wm_technical_tab_otp', 'ورود با OTP (کاوه‌نگار)' ),
        array(
            'key'           => 'field_wm_technical_otp_enabled',
            'label'         => 'فعال بودن ورود/ثبت‌نام با OTP',
            'name'          => 'wm_technical_otp_enabled',
            'type'          => 'true_false',
            'default_value' => 0,
            'ui'            => 1,
            'instructions'  => 'در صورت فعال بودن، کاربران می‌توانند با کد یکبارمصرف ارسالی از طریق کاوه‌نگار وارد شوند یا ثبت‌نام کنند.',
        ),
        array(
            'key'               => 'field_wm_technical_otp_api_key',
            'label'             => 'API Key کاوه‌نگار',
            'name'              => 'wm_technical_otp_api_key',
            'type'              => 'text',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_technical_otp_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        array(
            'key'               => 'field_wm_technical_otp_sender',
            'label'             => 'شماره فرستنده (Sender Line)',
            'name'              => 'wm_technical_otp_sender',
            'type'              => 'text',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_technical_otp_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        array(
            'key'               => 'field_wm_technical_otp_template',
            'label'             => 'نام Template پترن پیامکی',
            'name'              => 'wm_technical_otp_template',
            'type'              => 'text',
            'instructions'      => 'نام پترن تعریف‌شده در پنل کاوه‌نگار برای ارسال کد یکبارمصرف.',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_technical_otp_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        array(
            'key'               => 'field_wm_technical_otp_resend_seconds',
            'label'             => 'فاصله ارسال مجدد کد (ثانیه)',
            'name'              => 'wm_technical_otp_resend_seconds',
            'type'              => 'number',
            'default_value'     => 60,
            'min'               => 30,
            'max'               => 300,
            'instructions'      => 'حداقل فاصله بین دو ارسال کد برای یک شماره. پیش‌فرض: ۶۰ ثانیه.',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_technical_otp_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        array(
            'key'               => 'field_wm_technical_otp_max_per_phone',
            'label'             => 'حداکثر درخواست از یک شماره در ساعت',
            'name'              => 'wm_technical_otp_max_per_phone',
            'type'              => 'number',
            'default_value'     => 5,
            'min'               => 1,
            'max'               => 20,
            'instructions'      => 'بعد از این تعداد درخواست در یک ساعت، شماره مسدود می‌شود. پیش‌فرض: ۵.',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_technical_otp_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),
        array(
            'key'               => 'field_wm_technical_otp_max_per_ip',
            'label'             => 'حداکثر درخواست از یک IP در ساعت',
            'name'              => 'wm_technical_otp_max_per_ip',
            'type'              => 'number',
            'default_value'     => 10,
            'min'               => 1,
            'max'               => 50,
            'instructions'      => 'بعد از این تعداد درخواست از یک آدرس IP در یک ساعت، مسدود می‌شود. پیش‌فرض: ۱۰.',
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_technical_otp_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),

        wm_site_settings_tab( 'field_wm_technical_tab_analytics', 'آنالیتیکس و ردیابی' ),
        array(
            'key'         => 'field_wm_technical_ga4_id',
            'label'       => 'GA4 Measurement ID',
            'name'        => 'wm_technical_ga4_id',
            'type'        => 'text',
            'placeholder' => 'G-XXXXXXXXXX',
        ),
        array(
            'key'         => 'field_wm_technical_pixel_id',
            'label'       => 'Meta Pixel ID',
            'name'        => 'wm_technical_pixel_id',
            'type'        => 'text',
            'placeholder' => '1234567890',
        ),
        array(
            'key'          => 'field_wm_technical_head_scripts',
            'label'        => 'کد سفارشی Head',
            'name'         => 'wm_technical_head_scripts',
            'type'         => 'textarea',
            'rows'         => 4,
            'instructions' => 'کد HTML/JS که قبل از بسته‌شدن &lt;/head&gt; درج می‌شود. مراقب امنیت محتوا باشید.',
        ),

        wm_site_settings_tab( 'field_wm_technical_tab_maintenance', 'حالت تعمیر و نگهداری' ),
        array(
            'key'           => 'field_wm_technical_maintenance_enabled',
            'label'         => 'فعال بودن حالت تعمیر',
            'name'          => 'wm_technical_maintenance_enabled',
            'type'          => 'true_false',
            'default_value' => 0,
            'ui'            => 1,
            'instructions'  => 'در صورت فعال بودن، بازدیدکنندگان (به‌جز مدیران) پیام تعمیر و نگهداری را می‌بینند.',
        ),
        array(
            'key'               => 'field_wm_technical_maintenance_message',
            'label'             => 'پیام نمایش داده‌شده',
            'name'              => 'wm_technical_maintenance_message',
            'type'              => 'wysiwyg',
            'tabs'              => 'text',
            'media_upload'      => 0,
            'conditional_logic' => array(
                array(
                    array( 'field' => 'field_wm_technical_maintenance_enabled', 'operator' => '==', 'value' => '1' ),
                ),
            ),
        ),

        wm_site_settings_tab( 'field_wm_technical_tab_theme_update', 'به‌روزرسانی قالب' ),
        array(
            'key'          => 'field_wm_technical_theme_update_channel',
            'label'        => 'کانال به‌روزرسانی',
            'name'         => 'wm_technical_theme_update_channel',
            'type'         => 'select',
            'choices'      => array(
                'stable' => 'پایدار (Stable) — نسخه‌های منتشرشده از main',
                'beta'   => 'آزمایشی (Beta) — آخرین build از Pull Request باز',
            ),
            'default_value' => 'stable',
            'ui'            => 1,
            'instructions'  => 'پایدار برای سایت‌های production. آزمایشی فقط برای تست تغییرات در حال بررسی (PR باز) استفاده شود.',
        ),
    );
}
