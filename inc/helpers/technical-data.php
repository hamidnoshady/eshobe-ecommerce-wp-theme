<?php
/**
 * Technical settings accessors and effects (Whitelabel, plugins, analytics, maintenance, OTP).
 *
 * @package WM_Theme
 */

/**
 * Load the design-token-aware ACF options page styling on theme settings screens.
 */
function wm_technical_admin_assets() {
	$screen = get_current_screen();
	if ( ! $screen || false === strpos( $screen->id, 'eshobe-ecommerce' ) ) {
		return;
	}

	wp_enqueue_style( 'eshobe-ecommerce-acf-options', wm_asset_uri( 'assets/css/admin/acf-options.css' ), array(), wm_asset_version( 'assets/css/admin/acf-options.css' ) );

	if ( function_exists( 'wm_design_token_hex' ) ) {
		$vars = ':root{';
		foreach ( array(
			'wm_color_primary'   => '--wm-color-primary',
			'wm_color_muted'     => '--wm-color-muted',
			'wm_color_background' => '--wm-color-background',
			'wm_color_surface'   => '--wm-color-surface',
			'wm_color_border'    => '--wm-color-border',
			'wm_color_accent'    => '--wm-color-accent',
		) as $field => $css_var ) {
			$vars .= $css_var . ':' . wm_design_token_hex( $field ) . ';';
		}
		$vars .= '}';

		wp_add_inline_style( 'eshobe-ecommerce-acf-options', $vars );
	}
}
add_action( 'admin_enqueue_scripts', 'wm_technical_admin_assets' );

function wm_technical_get_option( $key, $default = '' ) {
	return wm_get_option( $key, $default );
}

/**
 * Build the HTML status list for theme-required plugins, shown as a read-only message field.
 *
 * @return string
 */
function wm_technical_required_plugins_status_html() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugins = array(
		'WooCommerce'              => 'woocommerce/woocommerce.php',
		'Advanced Custom Fields Pro' => 'advanced-custom-fields-pro/acf.php',
	);

	$rows = '';
	foreach ( $plugins as $label => $file ) {
		$active = is_plugin_active( $file );
		$status = $active
			? '<span style="color:#1a7f37;font-weight:700;">فعال</span>'
			: '<span style="color:#b42318;font-weight:700;">غیرفعال / نصب نشده</span>';

		$rows .= sprintf( '<li>%s — %s</li>', esc_html( $label ), $status );
	}

	return '<ul style="margin:0;padding-right:18px;">' . $rows . '</ul>';
}

/**
 * Whitelabel: replace the WP admin login logo with the configured one.
 */
function wm_technical_login_logo_css() {
	$logo_id = wm_technical_get_option( 'wm_technical_login_logo' );
	if ( empty( $logo_id ) ) {
		return;
	}

	$url = wp_get_attachment_image_url( absint( $logo_id ), 'medium' );
	if ( ! $url ) {
		return;
	}

	printf(
		'<style>.login h1 a { background-image: url(%s); background-size: contain; width: 100%%; height: 84px; }</style>',
		esc_url( $url )
	);
}
add_action( 'login_enqueue_scripts', 'wm_technical_login_logo_css' );

/**
 * Whitelabel: override the admin footer text.
 *
 * @param string $text
 * @return string
 */
function wm_technical_admin_footer_text( $text ) {
	$custom = wm_technical_get_option( 'wm_technical_admin_footer_text' );

	return $custom ? esc_html( $custom ) : $text;
}
add_filter( 'admin_footer_text', 'wm_technical_admin_footer_text' );

/**
 * Whitelabel: override the dashboard page title.
 */
function wm_technical_dashboard_title( $admin_title, $title ) {
	$custom = wm_technical_get_option( 'wm_technical_dashboard_title' );

	if ( $custom && function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( $screen && 'dashboard' === $screen->id ) {
			return $custom . $admin_title;
		}
	}

	return $admin_title;
}
add_filter( 'admin_title', 'wm_technical_dashboard_title', 10, 2 );

function wm_technical_dashboard_heading() {
	$custom = wm_technical_get_option( 'wm_technical_dashboard_title' );
	if ( ! $custom ) {
		return;
	}

	$screen = get_current_screen();
	if ( $screen && 'dashboard' === $screen->id ) {
		printf( '<script>document.addEventListener("DOMContentLoaded",function(){var h=document.querySelector(".wrap > h1");if(h){h.textContent=%s;}});</script>', wp_json_encode( $custom ) );
	}
}
add_action( 'admin_footer', 'wm_technical_dashboard_heading' );

/**
 * Analytics: inject GA4 / Meta Pixel / custom head scripts on the front end.
 */
function wm_technical_render_analytics_scripts() {
	if ( is_admin() ) {
		return;
	}

	$ga4_id   = wm_technical_get_option( 'wm_technical_ga4_id' );
	$pixel_id = wm_technical_get_option( 'wm_technical_pixel_id' );

	// Strict ID validation: only well-formed GA4 (G-/GT-/AW-) and numeric
	// Meta Pixel IDs are ever emitted into the page.
	if ( $ga4_id && ! preg_match( '/^(G|GT|AW)-[A-Z0-9-]{4,}$/', $ga4_id ) ) {
		$ga4_id = '';
	}
	if ( $pixel_id && ! preg_match( '/^\d{10,20}$/', $pixel_id ) ) {
		$pixel_id = '';
	}

	if ( $ga4_id ) {
		printf(
			"<script async src=\"https://www.googletagmanager.com/gtag/js?id=%1\$s\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','%1\$s');</script>\n",
			esc_attr( $ga4_id )
		);
	}

	if ( $pixel_id ) {
		printf(
			"<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','%1\$s');fbq('track','PageView');</script>\n<noscript><img height=\"1\" width=\"1\" style=\"display:none\" src=\"https://www.facebook.com/tr?id=%1\$s&ev=PageView&noscript=1\"/></noscript>\n",
			esc_attr( $pixel_id )
		);
	}

	$head_scripts = wm_technical_get_option( 'wm_technical_head_scripts' );
	if ( $head_scripts ) {
		echo $head_scripts; // phpcs:ignore WordPress.Security.EscapeOutput -- intentional admin-provided markup.
	}
}
add_action( 'wp_head', 'wm_technical_render_analytics_scripts' );

/**
 * Maintenance mode: show a maintenance message to visitors who are not logged-in admins.
 */
function wm_technical_maintenance_mode() {
	if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( ! wm_technical_get_option( 'wm_technical_maintenance_enabled' ) ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	$message = wm_technical_get_option( 'wm_technical_maintenance_message' );
	if ( ! $message ) {
		$message = '<p>سایت به‌زودی برمی‌گردد. لطفاً کمی بعد دوباره تلاش کنید.</p>';
	}

	wp_die(
		wp_kses_post( $message ),
		'در حال تعمیر و نگهداری',
		array( 'response' => 503, 'retry_after' => 3600 )
	);
}
add_action( 'template_redirect', 'wm_technical_maintenance_mode' );

/**
 * Theme update channel: 'stable' (main releases) or 'beta' (latest open-PR build).
 * Set on Technical Settings → "به‌روزرسانی قالب".
 *
 * @return string
 */
function wm_technical_theme_update_channel() {
	$channel = wm_technical_get_option( 'wm_technical_theme_update_channel', 'stable' );
	return 'beta' === $channel ? 'beta' : 'stable';
}

/**
 * Whether OTP-based login/registration via Kavenegar is enabled.
 *
 * @return bool
 */
function wm_technical_otp_enabled() {
	return (bool) wm_technical_get_option( 'wm_technical_otp_enabled', false );
}

/**
 * Kavenegar OTP settings (API key, sender, template name).
 *
 * @return array{api_key: string, sender: string, template: string}
 */
function wm_technical_get_otp_settings() {
	return array(
		'api_key'  => wm_technical_get_option( 'wm_technical_otp_api_key', '' ),
		'sender'   => wm_technical_get_option( 'wm_technical_otp_sender', '' ),
		'template' => wm_technical_get_option( 'wm_technical_otp_template', '' ),
	);
}

/**
 * OTP security / rate-limiting settings.
 *
 * @return array{resend_seconds: int, max_per_phone: int, max_per_ip: int}
 */
function wm_technical_get_otp_security_settings() {
	return array(
		'resend_seconds' => max( 30, (int) wm_technical_get_option( 'wm_technical_otp_resend_seconds', 60 ) ),
		'max_per_phone'  => max( 1,  (int) wm_technical_get_option( 'wm_technical_otp_max_per_phone', 5 ) ),
		'max_per_ip'     => max( 1,  (int) wm_technical_get_option( 'wm_technical_otp_max_per_ip', 10 ) ),
	);
}
