<?php
/**
 * Front page template.
 *
 * @package WM_Theme
 */

get_header();
?>

<main id="primary" class="site-main wm-home">
    <?php
    if ( function_exists( 'wm_marketing_render_promo_banners' ) ) {
        echo wm_marketing_render_promo_banners( 'home_top' );
    }

    foreach ( wm_get_home_sections_order() as $section_key ) {
        wm_render_home_section( $section_key );

        if ( function_exists( 'wm_marketing_render_promo_banners' ) && 'bestsellers' === $section_key ) {
            echo wm_marketing_render_promo_banners( 'home_middle' );
        }
    }
    ?>
</main>

<?php
get_footer();
