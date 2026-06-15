<?php
/**
 * Single Product Template.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

while ( have_posts() ) :
    the_post();
    global $product;
    ?>
    <main id="primary" class="site-main wm-single-product">
        <div <?php wc_product_class( 'wm-product-layout', $product ); ?>>
            <div class="wm-product-gallery-column">
                <?php woocommerce_show_product_sale_flash(); ?>
                <?php echo wm_render_product_gallery(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <div class="wm-product-summary-column">
                <?php
                echo wm_render_product_intro(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo wm_render_product_specs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo wm_render_product_purchase(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
            </div>
        </div>
        <div class="wm-container">
            <?php echo wm_render_product_tabs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo wm_render_related_products(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php echo wm_render_mobile_product_bottom_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </main>
    <?php
endwhile;

get_footer( 'shop' );
