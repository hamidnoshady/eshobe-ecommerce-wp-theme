<?php
/**
 * Home brand categories.
 *
 * @package WM_Theme
 */

$brands = wm_home_get_valid_items(
    'home_brand_items',
    function( $item ) {
        return ! empty( $item['brand_enabled'] ) && ! empty( $item['brand_term'] );
    }
);

if ( empty( $brands ) && taxonomy_exists( 'product_brand' ) ) {
    $terms = get_terms(
        array(
            'taxonomy'   => 'product_brand',
            'hide_empty' => true,
            'number'     => 8,
        )
    );
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $brands[] = array( 'brand_enabled' => true, 'brand_term' => $term );
        }
    }
}

if ( empty( $brands ) ) {
    return;
}
?>
<section class="wm-home-section wm-home-brands wm-section-decor wm-section-decor--brands wm-section-decor--dots">
    <div class="wm-home-section__header">
        <div>
            <h2 class="wm-home-section__title"><?php echo esc_html__( 'برندهای محبوب', 'eshobe-ecommerce' ); ?></h2>
            <p class="wm-home-section__subtitle"><?php echo esc_html__( 'انتخاب سریع بر اساس برندهای پربازدید فروشگاه', 'eshobe-ecommerce' ); ?></p>
        </div>
    </div>
    <div class="wm-home-brands__grid">
        <?php
        $brand_term_ids = array();
        foreach ( $brands as $item ) {
            if ( is_numeric( $item['brand_term'] ) ) {
                $brand_term_ids[] = absint( $item['brand_term'] );
            }
        }
        if ( ! empty( $brand_term_ids ) ) {
            get_terms(
                array(
                    'taxonomy'   => 'product_brand',
                    'include'    => $brand_term_ids,
                    'hide_empty' => false,
                )
            );
        }
        ?>
        <?php foreach ( $brands as $item ) : ?>
            <?php
            $term = $item['brand_term'];
            if ( is_numeric( $term ) ) {
                $term = get_term( absint( $term ), 'product_brand' );
            }
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }
            $link = get_term_link( $term );
            if ( is_wp_error( $link ) ) {
                continue;
            }
            ?>
            <a class="wm-home-brand-card" href="<?php echo esc_url( $link ); ?>">
                <span class="wm-home-brand-card__media">
                    <?php echo wm_home_get_image_html( ! empty( $item['brand_image'] ) ? $item['brand_image'] : '', 'medium', array( 'alt' => $term->name, 'class' => 'wm-home-brand-card__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
                <span class="wm-home-brand-card__body">
                    <strong><?php echo esc_html( $term->name ); ?></strong>
                    <span class="wm-home-brand-card__count"><?php echo esc_html( number_format_i18n( (int) $term->count ) . ' محصول' ); ?></span>
                    <?php if ( ! empty( $item['brand_subtitle'] ) ) : ?>
                        <small><?php echo esc_html( $item['brand_subtitle'] ); ?></small>
                    <?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
