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
 * Check if an IP address belongs to a CIDR block.
 *
 * @param string $ip   The IP address to check.
 * @param string $cidr The CIDR block (e.g., '192.168.1.0/24' or just '192.168.1.5').
 * @return bool True if IP is in CIDR, false otherwise.
 */
function wm_ip_in_cidr( $ip, $cidr ) {
	if ( strpos( $cidr, '/' ) === false ) {
		return $ip === $cidr;
	}
	list( $subnet, $mask ) = explode( '/', $cidr );

	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$ip_long     = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		$mask_long   = -1 << ( 32 - $mask );
		return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
	}

	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( $subnet );
		if ( ! $ip_bin || ! $subnet_bin ) {
			return false;
		}
		$bytes = floor( $mask / 8 );
		$bits  = $mask % 8;
		for ( $i = 0; $i < $bytes; $i++ ) {
			if ( $ip_bin[ $i ] !== $subnet_bin[ $i ] ) {
				return false;
			}
		}
		if ( $bits > 0 ) {
			$mask_bin = chr( 256 - ( 1 << ( 8 - $bits ) ) );
			return ( ord( $ip_bin[ $bytes ] ) & ord( $mask_bin ) ) === ( ord( $subnet_bin[ $bytes ] ) & ord( $mask_bin ) );
		}
		return true;
	}

	return false;
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

	$trusted = apply_filters( 'wm_trusted_proxy_ips', array() );
	$is_trusted_proxy = false;

	// Always trust Cloudflare if HTTP_CF_RAY is present, to prevent breaking
	// existing deployments that don't configure wm_trusted_proxy_ips.
	// For generic reverse proxies, require explicit CIDR allowlisting.
	$is_cloudflare = ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_RAY'] );

	if ( $is_cloudflare ) {
		$is_trusted_proxy = true;
	} elseif ( $remote && ! empty( $trusted ) ) {
		foreach ( (array) $trusted as $cidr ) {
			if ( wm_ip_in_cidr( $remote, $cidr ) ) {
				$is_trusted_proxy = true;
				break;
			}
		}
	}

	// Cloudflare: CF-Connecting-IP is set alongside CF-RAY on every edge request.
	if ( $is_trusted_proxy && $is_cloudflare ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	// Generic reverse proxy: only trust X-Forwarded-For when the direct peer
	// is allowlisted.
	if ( $is_trusted_proxy && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
		$ip    = isset( $parts[0] ) ? $parts[0] : '';
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return $remote;
}
