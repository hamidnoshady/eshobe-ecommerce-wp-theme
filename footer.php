<?php
/**
 * The footer for our theme.
 *
 * @package WM_Theme
 */
?>
    <?php wm_render_site_footer(); ?>
</div>
<?php
if ( function_exists( 'wm_technical_otp_enabled' ) && wm_technical_otp_enabled() && ! is_user_logged_in() ) {
    get_template_part( 'template-parts/auth/otp-modal' );
}
?>
<?php wp_footer(); ?>
</body>
</html>
