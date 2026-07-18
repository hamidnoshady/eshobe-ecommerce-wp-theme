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
    $render_promo = function( $position ) {
        if ( function_exists( 'wm_marketing_render_promo_banners' ) ) {
            echo wm_marketing_render_promo_banners( $position );
        }
    };

    $render_promo( 'home_top' );

    foreach ( wm_get_home_sections_order() as $section_key ) {
        wm_render_home_section( $section_key );

        if ( 'bestsellers' === $section_key ) {
            $render_promo( 'home_middle' );
        }
    }
    ?>
</main>

<?php
get_footer();
