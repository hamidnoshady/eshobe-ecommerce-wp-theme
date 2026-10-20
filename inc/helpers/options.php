<?php
/**
 * Global option helper functions.
 *
 * @package WM_Theme
 */

/**
 * Get an option value from ACF.
 *
 * @param string $key     The option key.
 * @param mixed  $default The default value if the option is not found or empty.
 * @return mixed
 */
function wm_get_option( $key, $default = '' ) {
	static $cache = array();

	$is_test = defined( 'PHPUNIT_COMPOSER_INSTALL' ) || defined( 'WP_TESTS_DOMAIN' );

	if ( ! $is_test && array_key_exists( $key, $cache ) ) {
		$value = $cache[ $key ];
		if ( null !== $value && '' !== $value && false !== $value ) {
			return $value;
		}
		return $default;
	}

	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $key, 'option' );
		if ( ! $is_test ) $cache[ $key ] = $value;
		if ( null !== $value && '' !== $value && false !== $value ) {
			return $value;
		}
	} else {
		if ( ! $is_test ) $cache[ $key ] = null;
	}

	return $default;
}

/**
 * Check if an IP is within a CIDR block.
 *
 * @param string $ip   The IP address to check.
 * @param string $cidr The CIDR block (e.g., 192.168.1.0/24) or a single IP.
 * @return bool True if the IP matches the CIDR or exact IP.
 */
function wm_ip_in_cidr( $ip, $cidr ) {
	if ( strpos( $cidr, '/' ) === false ) {
		return $ip === $cidr;
	}
	$parts = explode( '/', $cidr );
	if ( count( $parts ) !== 2 ) {
		return false;
	}
	list( $subnet, $bits ) = $parts;
	$bits                  = (int) $bits;
	$ip_pton               = inet_pton( $ip );
	$subnet_pton           = inet_pton( $subnet );

	if ( false === $ip_pton || false === $subnet_pton || strlen( $ip_pton ) !== strlen( $subnet_pton ) ) {
		return false;
	}

	$mask = '';
	for ( $i = 0; $i < strlen( $ip_pton ); $i++ ) {
		$mask .= chr( $bits >= 8 ? 255 : max( 0, 255 - ( ( 1 << ( 8 - $bits ) ) - 1 ) ) );
		$bits  = max( 0, $bits - 8 );
	}

	return ( $ip_pton & $mask ) === ( $subnet_pton & $mask );
}

/**
 * Resolve the client IP for rate limiting, proxy/CDN-aware.
 *
 * `REMOTE_ADDR` alone is wrong behind a proxy or CDN: every visitor shares
 * the proxy IP, so per-IP limits either never fire or lock out everyone at
 * once. Forwarded headers are only trusted when the direct peer is a known
 * proxy (Cloudflare sets both headers together on edge requests; generic
 * X-Forwarded-For requires an allowlisted peer via the filter below).
 *
 * @return string Client IP, or '' when unresolvable.
 */
function wm_get_client_ip() {
	$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	// Cloudflare: CF-Connecting-IP is set alongside CF-RAY on every edge request.
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_RAY'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	// Generic reverse proxy: only trust X-Forwarded-For when the direct peer
	// is allowlisted. Empty list (default) = header never trusted.
	$trusted   = apply_filters( 'wm_trusted_proxy_ips', array() );
	$is_trusted = false;
	if ( $remote ) {
		foreach ( (array) $trusted as $trusted_proxy ) {
			if ( wm_ip_in_cidr( $remote, $trusted_proxy ) ) {
				$is_trusted = true;
				break;
			}
		}
	}

	if ( $is_trusted && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
		$ip    = isset( $parts[0] ) ? $parts[0] : '';
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return $remote;
}
