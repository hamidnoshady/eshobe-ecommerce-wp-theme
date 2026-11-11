<?php
/**
 * Global option helper functions.
 *
 * @package WM_Theme
 */

/**
 * Check if an IP address matches a CIDR range or exact IP.
 *
 * @param string $ip   The IP address to check.
 * @param string $cidr The CIDR block or exact IP (e.g. 192.168.1.0/24 or 127.0.0.1).
 * @return bool True if IP is in the range.
 */
function wm_ip_in_cidr( $ip, $cidr ) {
	if ( strpos( $cidr, '/' ) === false ) {
		return inet_pton( $ip ) !== false && inet_pton( $ip ) === inet_pton( $cidr );
	}

	list( $subnet, $mask ) = explode( '/', $cidr );
	$mask = (int) $mask;

	$ip_bin     = inet_pton( $ip );
	$subnet_bin = inet_pton( $subnet );

	if ( $ip_bin === false || $subnet_bin === false ) {
		return false;
	}

	if ( strlen( $ip_bin ) !== strlen( $subnet_bin ) ) {
		return false;
	}

	for ( $i = 0; $i < strlen( $ip_bin ); $i++ ) {
		if ( $mask <= 0 ) {
			return true;
		}

		if ( $mask >= 8 ) {
			if ( $ip_bin[ $i ] !== $subnet_bin[ $i ] ) {
				return false;
			}
			$mask -= 8;
		} else {
			$bitmask = chr( 0xFF << ( 8 - $mask ) );
			return ( $ip_bin[ $i ] & $bitmask ) === ( $subnet_bin[ $i ] & $bitmask );
		}
	}

	return true;
}

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
function wm_get_client_ip() {
	$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	$trusted = apply_filters( 'wm_trusted_proxy_ips', array() );
	$is_trusted_proxy = false;

	if ( $remote ) {
		foreach ( (array) $trusted as $proxy_cidr ) {
			if ( wm_ip_in_cidr( $remote, $proxy_cidr ) ) {
				$is_trusted_proxy = true;
				break;
			}
		}
	}

	if ( $is_trusted_proxy ) {
		// Cloudflare: CF-Connecting-IP is set alongside CF-RAY on every edge request.
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_RAY'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
			if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}

		// Generic reverse proxy: only trust X-Forwarded-For when the direct peer
		// is allowlisted. Empty list (default) = header never trusted.
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );

			// Parse from right-to-left: the last IP is the one added by the trusted proxy.
			// We keep going left as long as the IPs are also trusted proxies, until we find the real client.
			$parts = array_reverse( $parts );

			foreach ( $parts as $part ) {
				$is_part_trusted = false;
				foreach ( (array) $trusted as $proxy_cidr ) {
					if ( wm_ip_in_cidr( $part, $proxy_cidr ) ) {
						$is_part_trusted = true;
						break;
					}
				}

				if ( ! $is_part_trusted ) {
					if ( filter_var( $part, FILTER_VALIDATE_IP ) ) {
						return $part;
					}
					break;
				}
			}
		}
	}

	return $remote;
}
