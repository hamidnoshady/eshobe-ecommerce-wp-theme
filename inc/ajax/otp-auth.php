<?php
/**
 * OTP (one-time password) login/registration via Kavenegar SMS.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize an Iranian mobile number to the local 09XXXXXXXXX format.
 *
 * @param string $phone Raw phone number input.
 * @return string Normalized number, or '' if invalid.
 */
function wm_otp_normalize_phone( $phone ) {
	$phone = preg_replace( '/[^0-9+]/', '', (string) $phone );

	if ( 0 === strpos( $phone, '+98' ) ) {
		$phone = '0' . substr( $phone, 3 );
	} elseif ( 0 === strpos( $phone, '0098' ) ) {
		$phone = '0' . substr( $phone, 4 );
	} elseif ( 0 === strpos( $phone, '98' ) && 12 === strlen( $phone ) ) {
		$phone = '0' . substr( $phone, 2 );
	}

	if ( 0 === strpos( $phone, '9' ) && 10 === strlen( $phone ) ) {
		$phone = '0' . $phone;
	}

	if ( 1 !== preg_match( '/^09\d{9}$/', $phone ) ) {
		return '';
	}

	return $phone;
}

/**
 * Send an OTP code to a phone number via Kavenegar.
 *
 * Uses the Verify Lookup API when a template name is configured, otherwise
 * falls back to a plain SMS via the sender line.
 *
 * @param string $phone Normalized phone number.
 * @param string $code  One-time code to deliver.
 * @return true|WP_Error
 */
function wm_otp_send_via_kavenegar( $phone, $code ) {
	$settings = wm_technical_get_otp_settings();

	if ( empty( $settings['api_key'] ) ) {
		return new WP_Error( 'wm_otp_not_configured', 'سرویس ارسال کد یکبارمصرف پیکربندی نشده است.' );
	}

	if ( ! empty( $settings['template'] ) ) {
		$endpoint = sprintf(
			'https://api.kavenegar.com/v1/%s/verify/lookup.json',
			rawurlencode( $settings['api_key'] )
		);

		$args = array(
			'receptor' => $phone,
			'token'    => $code,
			'template' => $settings['template'],
		);
	} else {
		if ( empty( $settings['sender'] ) ) {
			return new WP_Error( 'wm_otp_not_configured', 'سرویس ارسال کد یکبارمصرف پیکربندی نشده است.' );
		}

		$endpoint = sprintf(
			'https://api.kavenegar.com/v1/%s/sms/send.json',
			rawurlencode( $settings['api_key'] )
		);

		$args = array(
			'receptor' => $phone,
			'sender'   => $settings['sender'],
			/* translators: %s: one-time password code. */
			'message'  => sprintf( __( 'کد ورود شما: %s', 'eshobe-ecommerce' ), $code ),
		);
	}

	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout' => 15,
			'body'    => $args,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code_status = wp_remote_retrieve_response_code( $response );
	if ( $code_status < 200 || $code_status >= 300 ) {
		return new WP_Error( 'wm_otp_send_failed', 'ارسال کد یکبارمصرف ناموفق بود. لطفاً دوباره تلاش کنید.' );
	}

	return true;
}

/**
 * Generate, send, and store an OTP code for a phone number.
 *
 * Shared by the "request code" and "check phone" AJAX endpoints so the
 * throttle/send/transient logic only lives in one place.
 *
 * @param string $phone Normalized phone number.
 * @return true|WP_Error
 */
function wm_otp_issue_code( $phone ) {
	$security = wm_technical_get_otp_security_settings();

	// Per-IP hourly limit
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	// REMOTE_ADDR may be absent in proxied/CLI contexts; per-IP limit is silently skipped when empty.
	if ( $ip ) {
		$ip_key      = 'wm_otp_ip_' . md5( $ip );
		$ip_attempts = (int) get_transient( $ip_key );
		if ( $ip_attempts >= $security['max_per_ip'] ) {
			return new WP_Error( 'wm_otp_limit', 'تعداد درخواست‌های OTP از این آدرس بیش از حد مجاز است. لطفاً بعداً تلاش کنید.' );
		}
	}

	// Per-phone hourly limit
	$phone_hourly_key = 'wm_otp_hourly_' . md5( $phone );
	$phone_attempts   = (int) get_transient( $phone_hourly_key );
	if ( $phone_attempts >= $security['max_per_phone'] ) {
		return new WP_Error( 'wm_otp_limit', 'تعداد درخواست‌های OTP برای این شماره بیش از حد مجاز است. لطفاً یک ساعت دیگر تلاش کنید.' );
	}

	// Per-phone resend throttle (uses configurable resend_seconds)
	$throttle_key = 'wm_otp_throttle_' . md5( $phone );
	if ( get_transient( $throttle_key ) ) {
		return new WP_Error( 'wm_otp_throttled', 'کد قبلی هنوز معتبر است. کمی صبر کنید و دوباره تلاش کنید.' );
	}

	$code = (string) wp_rand( 10000, 99999 );
	$sent = wm_otp_send_via_kavenegar( $phone, $code );
	if ( is_wp_error( $sent ) ) {
		return $sent;
	}

	set_transient( 'wm_otp_code_' . md5( $phone ), $code, 2 * MINUTE_IN_SECONDS );
	set_transient( $throttle_key, 1, $security['resend_seconds'] );

	// Increment counters after successful send
	if ( $ip ) {
		set_transient( $ip_key, $ip_attempts + 1, HOUR_IN_SECONDS );
	}
	set_transient( $phone_hourly_key, $phone_attempts + 1, HOUR_IN_SECONDS );

	return true;
}

/**
 * AJAX: return a fresh nonce for the OTP modal.
 *
 * Called by the JS when the modal opens so page-caching plugins (FlyingPress,
 * WP Rocket, etc.) that bake a stale nonce into the HTML do not break every
 * subsequent AJAX call.  This endpoint has no nonce input — it just mints and
 * returns one — so it is safe to expose as nopriv.
 */
function wm_ajax_otp_get_nonce() {
	wp_send_json_success( array( 'nonce' => wp_create_nonce( 'wm_otp_nonce' ) ) );
}
add_action( 'wp_ajax_wm_otp_get_nonce', 'wm_ajax_otp_get_nonce' );
add_action( 'wp_ajax_nopriv_wm_otp_get_nonce', 'wm_ajax_otp_get_nonce' );

/**
 * AJAX: request an OTP code for a phone number.
 */
function wm_ajax_otp_request_code() {
	check_ajax_referer( 'wm_otp_nonce', 'nonce' );

	$phone = isset( $_POST['phone'] ) ? wm_otp_normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';

	if ( ! $phone ) {
		wp_send_json_error( array( 'message' => 'شماره موبایل وارد شده معتبر نیست.' ) );
	}

	$issued = wm_otp_issue_code( $phone );
	if ( is_wp_error( $issued ) ) {
		wp_send_json_error( array( 'message' => $issued->get_error_message() ) );
	}

	$security = wm_technical_get_otp_security_settings();
	wp_send_json_success(
		array(
			'message'  => 'کد یکبارمصرف ارسال شد.',
			'resendIn' => $security['resend_seconds'],
		)
	);
}
add_action( 'wp_ajax_wm_otp_request_code', 'wm_ajax_otp_request_code' );
add_action( 'wp_ajax_nopriv_wm_otp_request_code', 'wm_ajax_otp_request_code' );

/**
 * AJAX: check whether a phone number already has an account.
 *
 * - Existing user → { exists: true }
 * - New user      → sends OTP, { exists: false }
 */
function wm_ajax_otp_check_phone() {
	check_ajax_referer( 'wm_otp_nonce', 'nonce' );

	$phone = isset( $_POST['phone'] ) ? wm_otp_normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';

	if ( ! $phone ) {
		wp_send_json_error( array( 'message' => 'شماره موبایل وارد شده معتبر نیست.' ) );
	}

	$security = wm_technical_get_otp_security_settings();
	$user = wm_otp_get_user_by_phone( $phone );

	if ( $user ) {
		// Incomplete registration: account exists but password was never set
		if ( get_user_meta( $user->ID, '_wm_otp_needs_password', true ) ) {
			$issued = wm_otp_issue_code( $phone );
			if ( is_wp_error( $issued ) ) {
				wp_send_json_error( array( 'message' => $issued->get_error_message() ) );
			}
			wp_send_json_success(
				array(
					'exists'                 => true,
					'incompleteRegistration' => true,
					'message'                => 'کد یکبارمصرف ارسال شد.',
					'resendIn'               => $security['resend_seconds'],
				)
			);
		}

		wp_send_json_success( array( 'exists' => true ) );
	}

	$issued = wm_otp_issue_code( $phone );
	if ( is_wp_error( $issued ) ) {
		wp_send_json_error( array( 'message' => $issued->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'exists'   => false,
			'message'  => 'کد یکبارمصرف ارسال شد.',
			'resendIn' => $security['resend_seconds'],
		)
	);
}
add_action( 'wp_ajax_wm_otp_check_phone', 'wm_ajax_otp_check_phone' );
add_action( 'wp_ajax_nopriv_wm_otp_check_phone', 'wm_ajax_otp_check_phone' );

/**
 * AJAX: verify an OTP code and log the user in, registering them if needed.
 */
function wm_ajax_otp_verify_code() {
	check_ajax_referer( 'wm_otp_nonce', 'nonce' );

	$phone = isset( $_POST['phone'] ) ? wm_otp_normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';
	$code  = isset( $_POST['code'] ) ? preg_replace( '/[^0-9]/', '', wp_unslash( $_POST['code'] ) ) : '';

	if ( ! $phone || ! $code ) {
		wp_send_json_error( array( 'message' => 'اطلاعات وارد شده معتبر نیست.' ) );
	}

	$transient_key = 'wm_otp_code_' . md5( $phone );
	$expected_code = get_transient( $transient_key );

	if ( ! $expected_code || ! hash_equals( (string) $expected_code, $code ) ) {
		wp_send_json_error( array( 'message' => 'کد وارد شده نادرست یا منقضی شده است.' ) );
	}

	delete_transient( $transient_key );
	delete_transient( 'wm_otp_throttle_' . md5( $phone ) );

	$user = wm_otp_get_user_by_phone( $phone );
	$is_new_user = false;

	if ( ! $user ) {
		$user_id = wm_otp_create_user_from_phone( $phone );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}

		$user        = get_user_by( 'id', $user_id );
		$is_new_user = true;
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );

	$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : '';
	$needs_password = $is_new_user || get_user_meta( $user->ID, '_wm_otp_needs_password', true );

	if ( $needs_password ) {
		$pw_token = wp_generate_password( 40, false );
		set_transient( 'wm_otp_pw_token_' . $pw_token, $user->ID, 10 * MINUTE_IN_SECONDS );

		wp_send_json_success(
			array(
				'message'       => 'ورود با موفقیت انجام شد.',
				'needsPassword' => true,
				'passwordToken' => $pw_token,
			)
		);
	}

	wp_send_json_success(
		array(
			'message'  => 'ورود با موفقیت انجام شد.',
			'redirect' => wm_otp_resolve_redirect_url( $redirect_to ),
		)
	);
}
add_action( 'wp_ajax_wm_otp_verify_code', 'wm_ajax_otp_verify_code' );
add_action( 'wp_ajax_nopriv_wm_otp_verify_code', 'wm_ajax_otp_verify_code' );

/**
 * AJAX: set the account password after a first-time OTP registration.
 *
 * Accepts a single-use `passwordToken` transient (set during verify_code) so
 * it works even when the auth cookie from the verify step has not yet been
 * stored by the browser.  Falls back to the standard logged-in check when no
 * token is present.
 */
function wm_ajax_otp_set_password() {
	$pw_token = isset( $_POST['passwordToken'] ) ? sanitize_text_field( wp_unslash( $_POST['passwordToken'] ) ) : '';
	$user_id  = 0;

	if ( $pw_token ) {
		$user_id = (int) get_transient( 'wm_otp_pw_token_' . $pw_token );
		delete_transient( 'wm_otp_pw_token_' . $pw_token );

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'توکن نامعتبر یا منقضی شده است. دوباره ثبت‌نام کنید.' ) );
		}
	} else {
		check_ajax_referer( 'wm_otp_nonce', 'nonce' );
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'ابتدا وارد حساب کاربری شوید.' ) );
		}
	}

	$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

	if ( mb_strlen( $password ) < 6 ) {
		wp_send_json_error( array( 'message' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.' ) );
	}

	wp_set_password( $password, $user_id );
	delete_user_meta( $user_id, '_wm_otp_needs_password' );

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );

	$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : '';

	wp_send_json_success(
		array(
			'message'  => 'رمز عبور با موفقیت ثبت شد.',
			'redirect' => wm_otp_resolve_redirect_url( $redirect_to ),
		)
	);
}
add_action( 'wp_ajax_wm_otp_set_password', 'wm_ajax_otp_set_password' );
add_action( 'wp_ajax_nopriv_wm_otp_set_password', 'wm_ajax_otp_set_password' );

/**
 * AJAX: log a user in with their phone number and password (modal "login" tab).
 */
function wm_ajax_otp_password_login() {
	check_ajax_referer( 'wm_otp_nonce', 'nonce' );

	$phone    = isset( $_POST['phone'] ) ? wm_otp_normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';
	$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

	if ( ! $phone || ! $password ) {
		wp_send_json_error( array( 'message' => 'شماره موبایل و رمز عبور را وارد کنید.' ) );
	}

	$user = wm_otp_get_user_by_phone( $phone );

	if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
		wp_send_json_error( array( 'message' => 'شماره موبایل یا رمز عبور اشتباه است.' ) );
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );

	$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : '';

	wp_send_json_success(
		array(
			'message'  => 'ورود با موفقیت انجام شد.',
			'redirect' => wm_otp_resolve_redirect_url( $redirect_to ),
		)
	);
}
add_action( 'wp_ajax_wm_otp_password_login', 'wm_ajax_otp_password_login' );
add_action( 'wp_ajax_nopriv_wm_otp_password_login', 'wm_ajax_otp_password_login' );

/**
 * Normalize the phone-number login field to the stored username format
 * before WooCommerce hands credentials to wp_signon().
 *
 * @param array $credentials Login credentials (user_login, user_password, remember).
 * @return array
 */
function wm_otp_normalize_login_credentials( $credentials ) {
	if ( ! empty( $credentials['user_login'] ) ) {
		$normalized = wm_otp_normalize_phone( $credentials['user_login'] );
		if ( $normalized ) {
			$credentials['user_login'] = $normalized;
		}
	}

	return $credentials;
}
add_filter( 'woocommerce_login_credentials', 'wm_otp_normalize_login_credentials' );

/**
 * Find an existing user by their OTP-verified phone number.
 *
 * @param string $phone Normalized phone number.
 * @return WP_User|null
 */
function wm_otp_get_user_by_phone( $phone ) {
	$users = get_users(
		array(
			'meta_key'   => '_wm_otp_phone',
			'meta_value' => $phone,
			'number'     => 1,
			'fields'     => 'all',
		)
	);

	if ( ! empty( $users ) ) {
		return $users[0];
	}

	$user = get_user_by( 'login', $phone );

	return $user ? $user : null;
}

/**
 * Create a new customer account for a phone number that just verified an OTP.
 *
 * @param string $phone Normalized phone number.
 * @return int|WP_Error New user ID, or WP_Error on failure.
 */
function wm_otp_create_user_from_phone( $phone ) {
	$username = $phone;
	if ( username_exists( $username ) ) {
		$username = $phone . '_' . wp_generate_password( 4, false, false );
	}

	$host  = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$email = $phone . '@' . ( $host ? $host : 'example.com' );

	$user_id = wp_insert_user(
		array(
			'user_login' => $username,
			'user_pass'  => wp_generate_password( 20 ),
			'user_email' => $email,
			'role'       => 'customer',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	update_user_meta( $user_id, '_wm_otp_phone', $phone );
	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, '_wm_otp_needs_password', 1 );

	return $user_id;
}

/**
 * Resolve the post-login redirect URL.
 *
 * Accepts:
 *   'checkout'        → WooCommerce checkout URL
 *   'account'         → WooCommerce my-account URL
 *   'https://...'     → any same-origin URL (JS sends window.location.href)
 *   '' / anything else → home URL
 *
 * @param string $redirect_to Token or full URL from the client.
 * @return string
 */
function wm_otp_resolve_redirect_url( $redirect_to ) {
	if ( 'checkout' === $redirect_to && function_exists( 'wc_get_checkout_url' ) ) {
		return wc_get_checkout_url();
	}

	if ( 'account' === $redirect_to && function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}

	// Accept a full URL only when it is on the same host (prevents open redirect).
	if ( $redirect_to && filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
		$site_host     = wp_parse_url( home_url(), PHP_URL_HOST );
		$redirect_host = wp_parse_url( $redirect_to, PHP_URL_HOST );
		if ( $site_host && $site_host === $redirect_host ) {
			return esc_url_raw( $redirect_to );
		}
	}

	return home_url( '/' );
}
