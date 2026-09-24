<?php
/**
 * Contact form endpoint for the wm/contact-form block.
 *
 * Handles both the AJAX path (admin-ajax.php, used by company.js) and the
 * no-JS fallback (admin-post.php → redirect back with ?wm_contact=sent).
 * Every submission is nonce-checked, honeypot-filtered, per-IP rate limited,
 * emailed to the site admin, and archived as a private `wm_contact_message`
 * post so messages survive mail failures.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Private CPT that archives contact messages in the dashboard.
 */
function wm_contact_register_cpt() {
	register_post_type(
		'wm_contact_message',
		array(
			'labels'              => array(
				'name'          => __( 'پیام‌های تماس', 'eshobe-ecommerce' ),
				'singular_name' => __( 'پیام تماس', 'eshobe-ecommerce' ),
				'menu_name'     => __( 'پیام‌های تماس', 'eshobe-ecommerce' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-email-alt',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'editor' ),
			'capabilities'        => array(
				'create_posts'       => 'do_not_allow',
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
			),
			'map_meta_cap'        => true,
			'exclude_from_search' => true,
			'show_in_rest'        => false,
		)
	);
}
add_action( 'init', 'wm_contact_register_cpt' );

/**
 * Per-IP rate limit: max 5 submissions per 10 minutes.
 *
 * @return bool True when the request is allowed.
 */
function wm_contact_rate_limit_ok() {
	$ip = function_exists( 'wm_get_client_ip' ) ? wm_get_client_ip() : '';
	if ( ! $ip ) {
		return true; // Unresolvable IP (CLI/edge cases) — don't hard-fail.
	}

	$key      = 'wm_contact_rl_' . md5( $ip );
	$attempts = (int) get_transient( $key );

	if ( $attempts >= 5 ) {
		return false;
	}

	set_transient( $key, $attempts + 1, 10 * MINUTE_IN_SECONDS );

	return true;
}

/**
 * Validate + persist + mail one submission from $_POST.
 *
 * @return true|WP_Error
 */
function wm_contact_process_submission() {
	if ( ! isset( $_POST['wm_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wm_contact_nonce'] ) ), 'wm_contact_form_nonce' ) ) {
		return new WP_Error( 'wm_contact_nonce', __( 'نشست شما منقضی شده است. صفحه را تازه‌سازی و دوباره تلاش کنید.', 'eshobe-ecommerce' ) );
	}

	// Honeypot — silently accept so bots stop retrying, but store nothing.
	if ( ! empty( $_POST['wm_hp_website'] ) ) {
		return true;
	}

	if ( ! wm_contact_rate_limit_ok() ) {
		return new WP_Error( 'wm_contact_limit', __( 'تعداد پیام‌های ارسالی بیش از حد مجاز است. لطفاً کمی بعد دوباره تلاش کنید.', 'eshobe-ecommerce' ) );
	}

	$name    = isset( $_POST['wm_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wm_contact_name'] ) ) : '';
	$email   = isset( $_POST['wm_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['wm_contact_email'] ) ) : '';
	$phone   = isset( $_POST['wm_contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['wm_contact_phone'] ) ) : '';
	$subject = isset( $_POST['wm_contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['wm_contact_subject'] ) ) : '';
	$message = isset( $_POST['wm_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wm_contact_message'] ) ) : '';

	$name    = mb_substr( $name, 0, 120 );
	$phone   = mb_substr( $phone, 0, 20 );
	$subject = mb_substr( $subject, 0, 160 );
	$message = mb_substr( $message, 0, 4000 );

	if ( '' === $name || '' === $message || ! is_email( $email ) ) {
		return new WP_Error( 'wm_contact_invalid', __( 'لطفاً نام، ایمیل معتبر و متن پیام را کامل کنید.', 'eshobe-ecommerce' ) );
	}

	$post_title = $subject ? sprintf( '%s — %s', $subject, $name ) : $name;

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'wm_contact_message',
			'post_status'  => 'private',
			'post_title'   => $post_title,
			'post_content' => $message,
			'meta_input'   => array(
				'_wm_contact_name'  => $name,
				'_wm_contact_email' => $email,
				'_wm_contact_phone' => $phone,
			),
		),
		true
	);

	$to        = apply_filters( 'wm_contact_form_recipient', get_option( 'admin_email' ) );
	$mail_body = sprintf(
		/* translators: 1: name, 2: email, 3: phone, 4: message. */
		__( "پیام جدید از فرم تماس سایت:\n\nنام: %1\$s\nایمیل: %2\$s\nتلفن: %3\$s\n\nمتن پیام:\n%4\$s", 'eshobe-ecommerce' ),
		$name,
		$email,
		$phone ? $phone : '—',
		$message
	);
	$mail_subject = sprintf(
		/* translators: 1: site name, 2: subject line. */
		__( '[%1$s] پیام تماس جدید: %2$s', 'eshobe-ecommerce' ),
		wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		$subject ? $subject : $name
	);

	$sent = wp_mail( $to, $mail_subject, $mail_body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );

	// Sending can fail on hosts without mail configured; the message is still
	// archived in the dashboard, so only fail when both channels are lost.
	if ( ! $sent && ( ! $post_id || is_wp_error( $post_id ) ) ) {
		return new WP_Error( 'wm_contact_failed', __( 'ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.', 'eshobe-ecommerce' ) );
	}

	return true;
}

/**
 * AJAX handler (fetch() path from assets/js/company.js).
 */
function wm_ajax_contact_form() {
	$result = wm_contact_process_submission();

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_wm_contact_form', 'wm_ajax_contact_form' );
add_action( 'wp_ajax_nopriv_wm_contact_form', 'wm_ajax_contact_form' );

/**
 * admin-post.php handler (no-JS fallback) — redirects back to the page with
 * a ?wm_contact=sent|failed flag rendered by the block.
 */
function wm_contact_form_post_fallback() {
	$result   = wm_contact_process_submission();
	$redirect = isset( $_POST['wm_contact_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['wm_contact_redirect'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified inside wm_contact_process_submission().

	// Only redirect within this site.
	if ( ! $redirect || wp_parse_url( $redirect, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		$redirect = home_url( '/' );
	}

	$redirect = add_query_arg( 'wm_contact', is_wp_error( $result ) ? 'failed' : 'sent', $redirect );

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_wm_contact_form', 'wm_contact_form_post_fallback' );
add_action( 'admin_post_nopriv_wm_contact_form', 'wm_contact_form_post_fallback' );

/**
 * Admin list columns: show sender email/phone next to each message.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function wm_contact_admin_columns( $columns ) {
	$columns['wm_contact_email'] = __( 'ایمیل', 'eshobe-ecommerce' );
	$columns['wm_contact_phone'] = __( 'تلفن', 'eshobe-ecommerce' );
	return $columns;
}
add_filter( 'manage_wm_contact_message_posts_columns', 'wm_contact_admin_columns' );

/**
 * Render the custom admin columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function wm_contact_admin_column_content( $column, $post_id ) {
	if ( 'wm_contact_email' === $column ) {
		$email = get_post_meta( $post_id, '_wm_contact_email', true );
		if ( $email ) {
			printf( '<a href="mailto:%1$s" dir="ltr">%1$s</a>', esc_attr( $email ) );
		}
	} elseif ( 'wm_contact_phone' === $column ) {
		$phone = get_post_meta( $post_id, '_wm_contact_phone', true );
		echo $phone ? '<span dir="ltr">' . esc_html( $phone ) . '</span>' : '—';
	}
}
add_action( 'manage_wm_contact_message_posts_custom_column', 'wm_contact_admin_column_content', 10, 2 );
