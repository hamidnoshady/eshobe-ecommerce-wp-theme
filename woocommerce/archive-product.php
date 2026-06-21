<?php
/**
 * Product archive template.
 *
 * @package WM_Theme
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<main id="primary" class="site-main <?php echo esc_attr( wm_product_archive_classes() ); ?>" style="<?php echo esc_attr( wm_product_archive_css_vars() ); ?>" data-product-archive>
    <div class="wm-product-archive__container">
        <?php if ( function_exists( 'wm_marketing_render_promo_banners' ) ) { echo wm_marketing_render_promo_banners( 'archive_top' ); } ?>
        <?php woocommerce_breadcrumb(); ?>
        <?php wm_product_archive_render_header(); ?>

        <div class="wm-product-archive__layout">
            <?php wm_product_archive_render_sidebar(); ?>

            <section class="wm-product-archive__main" aria-label="<?php echo esc_attr__( 'محصولات', 'eshobe-ecommerce' ); ?>">
                <?php if ( woocommerce_product_loop() ) : ?>
                    <?php wm_product_archive_render_toolbar(); ?>
                    <?php wm_product_archive_render_loop(); ?>
                    <?php wm_product_archive_render_pagination(); ?>
                <?php else : ?>
                    <?php wm_product_archive_render_toolbar(); ?>
                    <?php wm_product_archive_render_empty(); ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <div class="wm-product-archive__backdrop" data-archive-filter-close hidden></div>
</main>
<?php
get_footer( 'shop' );
