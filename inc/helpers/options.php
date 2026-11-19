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
 * @param string $cidr The CIDR block (e.g. 192.168.1.0/24).
 * @return bool
 */
function wm_ip_in_cidr( $ip, $cidr ) {
	if ( false === strpos( $cidr, '/' ) ) {
		return $ip === $cidr;
	}
	list( $subnet, $mask ) = explode( '/', $cidr );

	if ( false !== strpos( $ip, ':' ) && false !== strpos( $subnet, ':' ) ) {
		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( $subnet );
		if ( false === $ip_bin || false === $subnet_bin ) {
			return false;
		}

		$mask = (int) $mask;
		for ( $i = 0; $i < 16; $i++ ) {
			$byte_mask = 0;
			if ( $mask >= 8 ) {
				$byte_mask = 255;
				$mask     -= 8;
			} elseif ( $mask > 0 ) {
				$byte_mask = ( 255 << ( 8 - $mask ) ) & 255;
				$mask      = 0;
			}
			if ( ( ord( $ip_bin[ $i ] ) & $byte_mask ) !== ( ord( $subnet_bin[ $i ] ) & $byte_mask ) ) {
				return false;
			}
		}
		return true;
	}

	if ( false === strpos( $ip, ':' ) && false === strpos( $subnet, ':' ) ) {
		$ip_long     = ip2long( $ip );
		$subnet_long = ip2long( $subnet );
		if ( false === $ip_long || false === $subnet_long ) {
			return false;
		}

		if ( 32 == $mask ) {
			return $ip_long === $subnet_long;
		}

		$mask_long = -1 << ( 32 - (int) $mask );
		return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
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

	// Cloudflare IPs (IPv4 and IPv6)
	$cloudflare_ips = array(
		'173.245.48.0/20',
		'103.21.244.0/22',
		'103.22.200.0/22',
		'103.31.4.0/22',
		'141.101.64.0/18',
		'108.162.192.0/18',
		'190.93.240.0/20',
		'188.114.96.0/20',
		'197.234.240.0/22',
		'198.41.128.0/17',
		'162.158.0.0/15',
		'104.16.0.0/13',
		'104.24.0.0/14',
		'172.64.0.0/13',
		'131.0.72.0/22',
		'2400:cb00::/32',
		'2606:4700::/32',
		'2803:f800::/32',
		'2405:b500::/32',
		'2405:8100::/32',
		'2a06:98c0::/29',
		'2c0f:f248::/32',
	);

	// Determine if the direct peer is Cloudflare
	$is_cloudflare = false;
	if ( $remote ) {
		foreach ( $cloudflare_ips as $cidr ) {
			if ( wm_ip_in_cidr( $remote, $cidr ) ) {
				$is_cloudflare = true;
				break;
			}
		}
	}

	// Cloudflare: CF-Connecting-IP is set alongside CF-RAY on every edge request.
	if ( $is_cloudflare && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_RAY'] ) ) {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	// Generic reverse proxy: only trust X-Forwarded-For when the direct peer
	// is allowlisted. Empty list (default) = header never trusted.
	$trusted = apply_filters( 'wm_trusted_proxy_ips', array() );
	$is_trusted_proxy = false;
	if ( $remote ) {
		foreach ( (array) $trusted as $trusted_cidr ) {
			if ( wm_ip_in_cidr( $remote, $trusted_cidr ) ) {
				$is_trusted_proxy = true;
				break;
			}
		}
	}

	if ( $is_trusted_proxy && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
		$ip    = isset( $parts[0] ) ? $parts[0] : '';
		if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
	}

	return $remote;
}
