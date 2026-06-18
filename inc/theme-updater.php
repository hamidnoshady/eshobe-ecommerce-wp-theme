<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hooks into WordPress's native theme update flow to serve releases from GitHub.
 *
 * Public repo  — no extra config needed.
 * Private repo — define WM_GITHUB_TOKEN in wp-config.php with a read-only PAT.
 */
class WM_Theme_Updater {

	private string $theme_slug;
	private string $github_user = 'hamidnoshady';
	private string $github_repo = 'eshobe-ecommerce-wp-theme';
	private string $token;
	private string $version;

	public function __construct() {
		// Derive the slug from the installed folder name so this still works
		// if the theme directory on disk doesn't match the GitHub repo name.
		$this->theme_slug = get_stylesheet();
		$this->token      = defined( 'WM_GITHUB_TOKEN' ) ? WM_GITHUB_TOKEN : '';
		$this->version    = wp_get_theme( $this->theme_slug )->get( 'Version' );

		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_for_update' ] );
		add_filter( 'themes_api', [ $this, 'theme_popup' ], 10, 3 );

		if ( $this->token ) {
			add_filter( 'upgrader_pre_download', [ $this, 'auth_download' ], 10, 3 );
		}
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
				'url'         => $release['html_url'],
				'package'     => $release['zip_url'],
			];
		} else {
			// Mark as "no update" so WP shows the auto-update toggle.
			$transient->no_update[ $this->theme_slug ] = [
				'theme'       => $this->theme_slug,
				'new_version' => $this->version,
				'url'         => $release['html_url'],
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
			'sections'      => [ 'changelog' => nl2br( esc_html( $release['body'] ) ) ],
			'download_link' => $release['zip_url'],
		];
	}

	/**
	 * For private repos: inject Authorization + Accept headers before WP downloads the asset.
	 */
	public function auth_download( $reply, $package, $upgrader ) {
		if ( strpos( $package, 'api.github.com' ) === false ) return $reply;

		$token = $this->token;
		add_filter( 'http_request_args', function ( $args, $url ) use ( $package, $token ) {
			if ( strpos( $url, 'api.github.com' ) !== false ) {
				$args['headers']['Authorization'] = 'token ' . $token;
				$args['headers']['Accept']        = 'application/octet-stream';
			}
			return $args;
		}, 10, 2 );

		return $reply;
	}

	/**
	 * Fetch the latest GitHub release, cached for 6 hours.
	 *
	 * @return array|false
	 */
	private function get_release() {
		$cache_key = 'wm_theme_update';
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) return $cached;

		$args = [
			'timeout' => 10,
			'headers' => [ 'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) ],
		];
		if ( $this->token ) {
			$args['headers']['Authorization'] = 'token ' . $this->token;
		}

		$api_url  = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		$response = wp_remote_get( $api_url, $args );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['tag_name'] ) ) return false;

		$zip_url = null;
		foreach ( $data['assets'] ?? [] as $asset ) {
			if ( str_ends_with( $asset['name'], '.zip' ) ) {
				// Private repo: use the API asset endpoint (auth header injected above).
				// Public repo:  browser_download_url works directly.
				$zip_url = $this->token
					? "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/assets/{$asset['id']}"
					: $asset['browser_download_url'];
				break;
			}
		}

		if ( ! $zip_url ) return false;

		$release = [
			'version'      => ltrim( $data['tag_name'], 'v' ),
			'html_url'     => $data['html_url'],
			'published_at' => $data['published_at'],
			'body'         => $data['body'] ?? '',
			'zip_url'      => $zip_url,
		];

		set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}
}

new WM_Theme_Updater();
