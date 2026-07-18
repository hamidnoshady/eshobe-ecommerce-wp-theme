<?php
/**
 * Front page template.
 *
 * @package WM_Theme
 */

get_header();

$render_promo = function( $position ) {
    if ( function_exists( 'wm_marketing_render_promo_banners' ) ) {
        echo wm_marketing_render_promo_banners( $position );
    }
};

// If the site's front page is a static Page whose content has been set up
// with blocks (via "تنظیمات قالب ← مدیریت بلوک‌ها"), render that instead of
// the legacy hardcoded section order below. Sites that haven't opted in
// (the default) keep today's exact output.
$wm_front_page_id = ( 'page' === get_option( 'show_on_front' ) ) ? (int) get_option( 'page_on_front' ) : 0;
$wm_front_page    = $wm_front_page_id ? get_post( $wm_front_page_id ) : null;
$wm_use_blocks    = $wm_front_page && has_blocks( $wm_front_page->post_content );
?>

<main id="primary" class="site-main wm-home">
    <?php if ( $wm_use_blocks ) : ?>
        <?php
        $render_promo( 'home_top' );
        echo apply_filters( 'the_content', $wm_front_page->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
    <?php else : ?>
        <?php
        $render_promo( 'home_top' );

        foreach ( wm_get_home_sections_order() as $section_key ) {
            wm_render_home_section( $section_key );

            if ( 'bestsellers' === $section_key ) {
                $render_promo( 'home_middle' );
            }
        }
        ?>
    <?php endif; ?>
</main>

<?php
get_footer();
