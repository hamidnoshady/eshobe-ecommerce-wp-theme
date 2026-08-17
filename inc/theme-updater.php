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
		add_filter( 'upgrader_pre_download', [ $this, 'verify_download' ], 10, 3 );
	}

	/**
	 * Only accept zip packages that come from the known public dist repo.
	 */
	private function is_allowed_download_url( $url ): bool {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		if ( 'raw.githubusercontent.com' === $host && 0 === strpos( $path, '/hamidnoshady/eshobe-ecommerce-wp-theme-dist/' ) ) {
			return true;
		}

		if ( 'github.com' === $host && 0 === strpos( $path, '/hamidnoshady/eshobe-ecommerce-wp-theme-dist/releases/download/' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Verify the advertised zip's SHA-256 before WordPress installs it.
	 *
	 * Runs for every upgrader download on the site but only acts on the exact
	 * package this class advertised (the active channel's zip_url), so plugin
	 * and core updates are never touched. When the manifest carries no sha256
	 * (older dist builds), the host/path validation above is the remaining
	 * guard and the package downloads normally.
	 */
	public function verify_download( $reply, $package, $upgrader ) {
		if ( ! empty( $reply ) || is_wp_error( $reply ) ) {
			return $reply;
		}

		$release = $this->get_release();
		if ( empty( $release['sha256'] ) || $package !== $release['zip_url'] ) {
			return $reply;
		}

		$tmp = download_url( $package );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$hash = @hash_file( 'sha256', $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $hash || ! hash_equals( strtolower( (string) $release['sha256'] ), strtolower( $hash ) ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'wm_theme_update_checksum_mismatch', 'فایل به‌روزرسانی قالب سالم نیست و نصب متوقف شد.' );
		}

		// Hand the already-downloaded, verified file to the upgrader.
		return $tmp;
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

		$update_data = [
			'theme'   => $this->theme_slug,
			'url'     => $release['url'],
			'package' => $release['zip_url'],
		];

		if ( version_compare( $release['version'], $this->version, '>' ) ) {
			$update_data['new_version'] = $release['version'];
			$transient->response[ $this->theme_slug ] = $update_data;
		} else {
			// Mark as "no update" so WP shows the auto-update toggle.
			$update_data['new_version'] = $this->version;
			$transient->no_update[ $this->theme_slug ] = $update_data;
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

		// Reject malformed versions and any download URL outside the known dist repo.
		if ( empty( $data['version'] ) || ! preg_match( '/^\d+\.\d+\.\d+$/', (string) $data['version'] ) || empty( $data['download_url'] ) ) {
			return false;
		}
		if ( ! $this->is_allowed_download_url( $data['download_url'] ) ) {
			return false;
		}

		$release = [
			'version'      => $data['version'],
			'url'          => 'https://github.com/hamidnoshady/eshobe-ecommerce-wp-theme',
			'published_at' => $data['published_at'] ?? '',
			'changelog'    => $data['changelog'] ?? '',
			'zip_url'      => $data['download_url'],
			'sha256'       => $data['sha256'] ?? '',
		];

		set_transient( $cache_key, $release, ( 'beta' === $channel ? 1 : 6 ) * HOUR_IN_SECONDS );
		return $release;
	}
}

new WM_Theme_Updater();
