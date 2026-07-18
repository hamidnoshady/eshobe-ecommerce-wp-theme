<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hooks into WordPress's native theme update flow to serve releases from a
 * public "dist" repo (github.com/hamidnoshady/eshobe-ecommerce-wp-theme-dist).
 *
 * The source theme repo stays private. GitHub Actions builds the zip there
 * (using a fine-grained PAT stored as a repo secret) and pushes it — plus a
 * small update.json manifest — to the public dist repo. WordPress only ever
 * reads those two static public files, so no GitHub credentials belong here.
 *
 * Channel is picked on Technical Settings → "به‌روزرسانی قالب" (stable or beta).
 */
class WM_Theme_Updater {

	private string $theme_slug;
	private string $version;
	private string $manifest_base = 'https://raw.githubusercontent.com/hamidnoshady/eshobe-ecommerce-wp-theme-dist/main';

	public function __construct() {
		$this->theme_slug = get_stylesheet();
		$this->version    = wp_get_theme( $this->theme_slug )->get( 'Version' );

		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_for_update' ] );
		add_filter( 'themes_api', [ $this, 'theme_popup' ], 10, 3 );
	}

	private function channel(): string {
		return function_exists( 'wm_technical_theme_update_channel' ) ? wm_technical_theme_update_channel() : 'stable';
	}

	/**
	 * Inject update info into the WP update transient when a newer release exists.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) return $transient;

		$release = $this->get_release();
		if ( ! $release ) return $transient;

		if ( version_compare( $release['version'], $this->version, '>' ) ) {
			$transient->response[ $this->theme_slug ] = [
				'theme'       => $this->theme_slug,
				'new_version' => $release['version'],
				'url'         => $release['url'],
				'package'     => $release['zip_url'],
			];
		} else {
			// Mark as "no update" so WP shows the auto-update toggle.
			$transient->no_update[ $this->theme_slug ] = [
				'theme'       => $this->theme_slug,
				'new_version' => $this->version,
				'url'         => $release['url'],
				'package'     => $release['zip_url'],
			];
		}

		return $transient;
	}

	/**
	 * Provide release notes for the "View version details" popup in WP admin.
	 */
	public function theme_popup( $result, $action, $args ) {
		if ( 'theme_information' !== $action || ( $args->slug ?? '' ) !== $this->theme_slug ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) return $result;

		return (object) [
			'name'          => wp_get_theme( $this->theme_slug )->get( 'Name' ),
			'slug'          => $this->theme_slug,
			'version'       => $release['version'],
			'last_updated'  => $release['published_at'],
			'sections'      => [ 'changelog' => nl2br( esc_html( $release['changelog'] ) ) ],
			'download_link' => $release['zip_url'],
		];
	}

	/**
	 * Fetch the active channel's update.json, cached (beta refreshes hourly
	 * since PR builds land more often than stable releases).
	 *
	 * @return array|false
	 */
	private function get_release() {
		$channel   = $this->channel();
		$cache_key = 'wm_theme_update_' . $channel;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) return $cached;

		$response = wp_remote_get( "{$this->manifest_base}/{$channel}/update.json", [ 'timeout' => 10 ] );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['version'] ) || empty( $data['download_url'] ) ) return false;

		$release = [
			'version'      => $data['version'],
			'url'          => 'https://github.com/hamidnoshady/eshobe-ecommerce-wp-theme',
			'published_at' => $data['published_at'] ?? '',
			'changelog'    => $data['changelog'] ?? '',
			'zip_url'      => $data['download_url'],
		];

		set_transient( $cache_key, $release, ( 'beta' === $channel ? 1 : 6 ) * HOUR_IN_SECONDS );
		return $release;
	}
}

new WM_Theme_Updater();
