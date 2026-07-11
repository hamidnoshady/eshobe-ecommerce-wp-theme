<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Appearance → Theme Updates: lets an admin switch between the stable
 * (main branch releases) and beta (open PR builds) update channels
 * read by WM_Theme_Updater.
 */
add_action( 'admin_menu', function () {
	add_theme_page( 'Theme Updates', 'Theme Updates', 'manage_options', 'wm-theme-updates', 'wm_theme_updates_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'wm_theme_updates', 'wm_theme_update_channel', [
		'type'              => 'string',
		'sanitize_callback' => fn( $value ) => 'beta' === $value ? 'beta' : 'stable',
		'default'           => 'stable',
	] );
} );

function wm_theme_updates_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$channel = get_option( 'wm_theme_update_channel', 'stable' );
	?>
	<div class="wrap">
		<h1>Theme Update Channel</h1>
		<p>Stable tracks tagged releases on <code>main</code>. Beta tracks the latest open pull-request build — use it only for testing.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'wm_theme_updates' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Channel</th>
					<td>
						<label><input type="radio" name="wm_theme_update_channel" value="stable" <?php checked( $channel, 'stable' ); ?>> Stable (recommended)</label><br>
						<label><input type="radio" name="wm_theme_update_channel" value="beta" <?php checked( $channel, 'beta' ); ?>> Beta (latest pull-request build)</label>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
