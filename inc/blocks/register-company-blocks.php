<?php
/**
 * Registration + asset wiring for the company-page block family
 * (About Us / Contact Us): wm/page-hero, wm/about-story, wm/stats-row(+item),
 * wm/feature-cards(+card), wm/team-grid(+member), wm/contact-cards(+card),
 * wm/contact-form, wm/map-embed, wm/faq(+item), wm/cta-banner.
 *
 * Same architecture as the homepage blocks (inc/blocks/register-blocks.php):
 * dynamic blocks rendered by blocks/{name}/render.php, one shared editor
 * script, front-end CSS/JS only enqueued on pages that actually contain one
 * of these blocks.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block directory names under /blocks for this family (containers first so
 * parent relationships resolve).
 *
 * @return string[]
 */
function wm_company_blocks_registry() {
	return array(
		'page-hero',
		'about-story',
		'stats-row',
		'stat-item',
		'feature-cards',
		'feature-card',
		'team-grid',
		'team-member',
		'contact-cards',
		'contact-card',
		'contact-form',
		'map-embed',
		'faq',
		'faq-item',
		'cta-banner',
	);
}

/**
 * Registers the shared editor script referenced by every company block.json.
 */
function wm_company_register_editor_script() {
	wp_register_script(
		'eshobe-ecommerce-company-blocks-editor',
		wm_asset_uri( 'assets/js/company-blocks-editor.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		wm_asset_version( 'assets/js/company-blocks-editor.js' ),
		true
	);

	wp_localize_script(
		'eshobe-ecommerce-company-blocks-editor',
		'wmCompanyEditorData',
		array(
			'icons' => wm_company_icons(),
		)
	);
}
add_action( 'init', 'wm_company_register_editor_script', 5 );

/**
 * Registers all company blocks from their block.json files.
 */
function wm_company_register_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	foreach ( wm_company_blocks_registry() as $block ) {
		$path = get_template_directory() . '/blocks/' . $block;
		if ( file_exists( $path . '/block.json' ) ) {
			register_block_type( $path );
		}
	}
}
add_action( 'init', 'wm_company_register_blocks', 10 );

/**
 * Front-end assets: only on singular content that contains a company block.
 * Runs on wp_enqueue_scripts after the main theme styles are registered so
 * the dependency on eshobe-ecommerce-style resolves.
 */
function wm_company_enqueue_assets() {
	if ( ! is_singular() || ! wm_company_page_has_blocks() ) {
		return;
	}

	wp_enqueue_style(
		'eshobe-ecommerce-company',
		wm_asset_uri( 'assets/css/pages/company.css' ),
		array( 'eshobe-ecommerce-style', 'eshobe-ecommerce-decorative-motifs' ),
		wm_asset_version( 'assets/css/pages/company.css' )
	);

	wp_enqueue_script(
		'eshobe-ecommerce-company',
		wm_asset_uri( 'assets/js/company.js' ),
		array(),
		wm_asset_version( 'assets/js/company.js' ),
		true
	);

	wp_localize_script(
		'eshobe-ecommerce-company',
		'wmCompanyData',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wm_company_enqueue_assets', 20 );

/**
 * Editor canvas: load company.css so ServerSideRender previews look like the
 * front end. Mirrors wm_enqueue_block_editor_preview_styles() — hooked on
 * enqueue_block_assets (admin only) so styles reach the iframed canvas too.
 */
function wm_company_enqueue_editor_preview_styles() {
	if ( ! is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'eshobe-ecommerce-company',
		wm_asset_uri( 'assets/css/pages/company.css' ),
		array(),
		wm_asset_version( 'assets/css/pages/company.css' )
	);
}
add_action( 'enqueue_block_assets', 'wm_company_enqueue_editor_preview_styles' );
