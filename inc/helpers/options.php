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
function wm_ip_in_cidr( $ip, $cidr ) {
	if ( strpos( $cidr, '/' ) === false ) {
		return $ip === $cidr;
	}

	list( $subnet, $mask ) = explode( '/', $cidr );

	if ( strpos( $ip, ':' ) === false && strpos( $subnet, ':' ) === false ) {
		// IPv4
		$ip_long     = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		if ( false === $ip_long || false === $subnet_long ) {
			return false;
		}
		$mask_long = ~ ( ( 1 << ( 32 - $mask ) ) - 1 );
		return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
	} elseif ( strpos( $ip, ':' ) !== false && strpos( $subnet, ':' ) !== false ) {
		// IPv6
		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( $subnet );
		if ( false === $ip_bin || false === $subnet_bin ) {
			return false;
		}

		$bytes = floor( $mask / 8 );
		$bits  = $mask % 8;

		if ( substr( $ip_bin, 0, $bytes ) !== substr( $subnet_bin, 0, $bytes ) ) {
			return false;
		}

		if ( $bits > 0 ) {
			$ip_byte     = ord( $ip_bin[ $bytes ] );
			$subnet_byte = ord( $subnet_bin[ $bytes ] );
			$mask_byte   = ~ ( ( 1 << ( 8 - $bits ) ) - 1 ) & 0xFF;
			if ( ( $ip_byte & $mask_byte ) !== ( $subnet_byte & $mask_byte ) ) {
				return false;
			}
		}

		return true;
	}

	return false;
}

/**
 * Check if a given IP address is in an array of trusted IPs or CIDR blocks.
 *
 * @param string $ip      The IP address to check.
 * @param array  $proxies Array of IP addresses or CIDR blocks.
 * @return bool
 */
function wm_is_trusted_proxy( $ip, $proxies ) {
	if ( empty( $ip ) || empty( $proxies ) ) {
		return false;
	}
	foreach ( $proxies as $proxy ) {
		if ( wm_ip_in_cidr( $ip, $proxy ) ) {
			return true;
		}
	}
	return false;
}

function wm_get_client_ip() {
	$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	// Cloudflare: CF-Connecting-IP is set alongside CF-RAY on every edge request.
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_RAY'] ) ) {
		// 🛡️ Sentinel: Only trust Cloudflare headers if the direct peer is a known Cloudflare edge IP.
		$cf_ips = array(
			'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
			'141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
			'197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
			'104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32',
			'2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
			'2a06:98c0::/29', '2c0f:f248::/32',
		);
		$cf_ips = apply_filters( 'wm_cloudflare_ips', $cf_ips );

		if ( wm_is_trusted_proxy( $remote, $cf_ips ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
			if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
	}

	// Generic reverse proxy: only trust X-Forwarded-For when the direct peer
	// is allowlisted. Empty list (default) = header never trusted.
	$trusted = apply_filters( 'wm_trusted_proxy_ips', array() );
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && wm_is_trusted_proxy( $remote, (array) $trusted ) ) {
		$parts = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
		$ip    = isset( $parts[0] ) ? $parts[0] : '';
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return $remote;
}
