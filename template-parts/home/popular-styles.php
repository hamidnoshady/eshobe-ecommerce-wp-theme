<?php
/**
 * Home popular styles.
 *
 * @package WM_Theme
 */

$items = wm_home_get_valid_items(
    'home_popular_styles',
    function( $item ) {
        return ! empty( $item['style_enabled'] ) && ! empty( $item['style_title'] ) && ( ! empty( $item['style_url'] ) || ! empty( $item['style_term'] ) );
    }
);

if ( empty( $items ) ) {
    $items = array(
        array( 'style_enabled' => true, 'style_title' => 'کلاسیک', 'style_subtitle' => 'ساده، ماندگار و همیشه قابل استفاده', 'style_url' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ),
        array( 'style_enabled' => true, 'style_title' => 'اسپرت', 'style_subtitle' => 'برای استفاده روزمره و فعال', 'style_url' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ),
        array( 'style_enabled' => true, 'style_title' => 'رسمی', 'style_subtitle' => 'هماهنگ با استایل کاری و مهمانی', 'style_url' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ),
    );
}
?>
<section class="wm-home-section wm-home-styles wm-section-decor wm-section-decor--styles wm-section-decor--soft-grid">
    <div class="wm-home-section__header">
        <div>
            <h2 class="wm-home-section__title"><?php echo esc_html__( 'سبک‌های محبوب', 'eshobe-ecommerce' ); ?></h2>
            <p class="wm-home-section__subtitle"><?php echo esc_html__( 'بر اساس زبان طراحی و موقعیت استفاده انتخاب کنید.', 'eshobe-ecommerce' ); ?></p>
        </div>
    </div>
    <div class="wm-home-styles__grid">
        <?php
        // Preload term caches to avoid N+1 queries in the loop.
        $term_ids_to_cache = array();
        foreach ( $items as $item ) {
            if ( empty( $item['style_url'] ) && ! empty( $item['style_term'] ) && is_numeric( $item['style_term'] ) ) {
                $term_ids_to_cache[] = absint( $item['style_term'] );
            }
        }
        if ( ! empty( $term_ids_to_cache ) ) {
            _prime_term_caches( $term_ids_to_cache );
        }
        ?>
        <?php foreach ( $items as $item ) : ?>
            <?php
            $url = ! empty( $item['style_url'] ) ? $item['style_url'] : '';
            if ( ! $url && ! empty( $item['style_term'] ) ) {
                $term = is_numeric( $item['style_term'] ) ? get_term( absint( $item['style_term'] ) ) : $item['style_term'];
                if ( $term && ! is_wp_error( $term ) ) {
                    $term_url = get_term_link( $term );
                    $url      = is_wp_error( $term_url ) ? '' : $term_url;
                }
            }
            if ( ! $url ) {
                continue;
            }
            ?>
            <a class="wm-home-style-card" href="<?php echo esc_url( $url ); ?>">
                <span class="wm-home-style-card__media">
                    <?php echo wm_home_get_image_html( ! empty( $item['style_image'] ) ? $item['style_image'] : '', 'medium', array( 'alt' => $item['style_title'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
                <span class="wm-home-style-card__body">
                    <strong><?php echo esc_html( $item['style_title'] ); ?></strong>
                    <?php if ( ! empty( $item['style_subtitle'] ) ) : ?>
                        <small><?php echo esc_html( $item['style_subtitle'] ); ?></small>
                    <?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
