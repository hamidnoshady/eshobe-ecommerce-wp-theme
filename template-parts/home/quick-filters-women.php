<?php
/**
 * Home women quick filters.
 *
 * @package WM_Theme
 */

$items = wm_home_get_option( 'home_women_quick_filters', array() );
wm_render_home_quick_filters_section(
    $items,
    array(
        'class'    => 'wm-home-filters--women',
        'title'    => 'انتخاب‌های زنانه و دخترانه',
        'subtitle' => 'مسیرهای سریع برای رسیدن به مدل‌های مناسب استایل روزمره، رسمی یا هدیه.',
    )
);

