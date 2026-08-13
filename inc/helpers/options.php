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
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $key, 'option' );
		if ( null !== $value && '' !== $value && false !== $value ) {
			return $value;
		}
	}

	return $default;
}
