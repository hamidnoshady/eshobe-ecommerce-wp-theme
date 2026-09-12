<?php
/**
 * Shared helpers for the "company pages" block family (About Us / Contact Us):
 * icon set, section header renderer, and detection of company blocks inside a
 * page so assets are only enqueued where needed.
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block names that make up the company-pages family. Used both by the asset
 * enqueue logic (only load company.css/js when one of these renders) and the
 * block registration registry.
 *
 * @return string[]
 */
function wm_company_block_names() {
	return array(
		'wm/page-hero',
		'wm/about-story',
		'wm/stats-row',
		'wm/feature-cards',
		'wm/team-grid',
		'wm/contact-cards',
		'wm/contact-form',
		'wm/map-embed',
		'wm/faq',
		'wm/cta-banner',
	);
}

/**
 * Whether the current (or given) post contains any company-page block.
 *
 * @param int|WP_Post|null $post Optional post.
 * @return bool
 */
function wm_company_page_has_blocks( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! has_blocks( $post->post_content ) ) {
		return false;
	}

	foreach ( wm_company_block_names() as $block_name ) {
		if ( has_block( $block_name, $post ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Icon registry for feature/contact cards: key => Persian label.
 * Labels feed the block editor's icon dropdown (localized via
 * wmBlockEditorData.companyIcons).
 *
 * @return array<string, string>
 */
function wm_company_icons() {
	return array(
		'shield'  => __( 'سپر (ضمانت اصالت)', 'eshobe-ecommerce' ),
		'truck'   => __( 'کامیون (ارسال)', 'eshobe-ecommerce' ),
		'headset' => __( 'هدست (پشتیبانی)', 'eshobe-ecommerce' ),
		'card'    => __( 'کارت بانکی (پرداخت)', 'eshobe-ecommerce' ),
		'star'    => __( 'ستاره (کیفیت)', 'eshobe-ecommerce' ),
		'heart'   => __( 'قلب (رضایت)', 'eshobe-ecommerce' ),
		'users'   => __( 'کاربران (تیم/مشتریان)', 'eshobe-ecommerce' ),
		'target'  => __( 'هدف (ماموریت)', 'eshobe-ecommerce' ),
		'award'   => __( 'مدال (افتخارات)', 'eshobe-ecommerce' ),
		'store'   => __( 'فروشگاه', 'eshobe-ecommerce' ),
		'sparkle' => __( 'درخشش', 'eshobe-ecommerce' ),
		'phone'   => __( 'تلفن', 'eshobe-ecommerce' ),
		'mail'    => __( 'ایمیل', 'eshobe-ecommerce' ),
		'pin'     => __( 'نشانی (پین نقشه)', 'eshobe-ecommerce' ),
		'clock'   => __( 'ساعت کاری', 'eshobe-ecommerce' ),
		'chat'    => __( 'گفتگو', 'eshobe-ecommerce' ),
		'globe'   => __( 'وب/شبکه‌ها', 'eshobe-ecommerce' ),
	);
}

/**
 * Inline SVG for a registered icon key. Icons are stroke-based and inherit
 * currentColor so the design system controls their color via CSS.
 *
 * @param string $key Icon key from wm_company_icons().
 * @return string SVG markup or '' when unknown.
 */
function wm_company_icon( $key ) {
	$paths = array(
		'shield'  => '<path d="M12 3l7 3v5c0 4.6-2.9 7.7-7 9.2C7.9 18.7 5 15.6 5 11V6l7-3z"/><path d="M9.2 11.8l2 2 3.6-3.8"/>',
		'truck'   => '<path d="M3 7h11v9H3z"/><path d="M14 10h3.5l3 3v3H14z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/>',
		'headset' => '<path d="M4.5 13a7.5 7.5 0 0 1 15 0"/><rect x="3.5" y="12.5" width="3.4" height="5.4" rx="1.6"/><rect x="17.1" y="12.5" width="3.4" height="5.4" rx="1.6"/><path d="M19 18v.6a2.4 2.4 0 0 1-2.4 2.4H13"/>',
		'card'    => '<rect x="2.5" y="5.5" width="19" height="13" rx="2.6"/><path d="M2.5 10h19"/><path d="M6 14.6h4.4"/>',
		'star'    => '<path d="M12 3.6l2.5 5.1 5.6.8-4 4 1 5.6-5.1-2.7-5.1 2.7 1-5.6-4-4 5.6-.8L12 3.6z"/>',
		'heart'   => '<path d="M12 20s-7.2-4.4-9-9c-1-2.7.6-5.6 3.4-6.2 1.9-.4 3.9.4 5.6 2.5 1.7-2.1 3.7-2.9 5.6-2.5 2.8.6 4.4 3.5 3.4 6.2-1.8 4.6-9 9-9 9z"/>',
		'users'   => '<circle cx="9" cy="8.5" r="3.2"/><path d="M3.2 19.5c.6-3.1 3-5 5.8-5s5.2 1.9 5.8 5"/><circle cx="17" cy="9.5" r="2.4"/><path d="M16.4 14.6c2.3.2 4 1.8 4.5 4.4"/>',
		'target'  => '<circle cx="12" cy="12" r="8.4"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1.1"/>',
		'award'   => '<circle cx="12" cy="9" r="5.2"/><path d="M9 13.4L7.6 20l4.4-2.2L16.4 20 15 13.4"/>',
		'store'   => '<path d="M4.4 9.6L5.8 4.5h12.4l1.4 5.1"/><path d="M4.8 9.6V19.5h14.4V9.6"/><path d="M10 19.5v-5.4h4v5.4"/>',
		'sparkle' => '<path d="M12 3.5l1.8 5.4 5.4 1.8-5.4 1.8L12 18l-1.8-5.5-5.4-1.8 5.4-1.8L12 3.5z"/><path d="M18.8 15.6l.8 2.3 2.3.8-2.3.8-.8 2.3-.8-2.3-2.3-.8 2.3-.8.8-2.3z"/>',
		'phone'   => '<path d="M6.8 3.8L9 3.2c.6-.2 1.2.1 1.5.7l1.2 2.6c.2.5.1 1.1-.3 1.5L9.9 9.4a12.6 12.6 0 0 0 4.7 4.7l1.4-1.5c.4-.4 1-.5 1.5-.3l2.6 1.2c.6.3.9.9.7 1.5l-.6 2.2c-.2.8-.9 1.3-1.7 1.3C10.8 18.3 5.7 13.2 5.5 5.5c0-.8.5-1.5 1.3-1.7z"/>',
		'mail'    => '<rect x="2.8" y="5" width="18.4" height="14" rx="2.6"/><path d="M3.6 6.6L12 13l8.4-6.4"/>',
		'pin'     => '<path d="M12 21s-6.6-5.7-6.6-10.4a6.6 6.6 0 1 1 13.2 0C18.6 15.3 12 21 12 21z"/><circle cx="12" cy="10.4" r="2.4"/>',
		'clock'   => '<circle cx="12" cy="12" r="8.6"/><path d="M12 7.2V12l3.2 2.1"/>',
		'chat'    => '<path d="M4 6.6A2.6 2.6 0 0 1 6.6 4h10.8A2.6 2.6 0 0 1 20 6.6v7.2a2.6 2.6 0 0 1-2.6 2.6H10l-4.4 3.4v-3.4H6.6A2.6 2.6 0 0 1 4 13.8V6.6z"/><path d="M8.4 9h7.2M8.4 12h4.6"/>',
		'globe'   => '<circle cx="12" cy="12" r="8.6"/><path d="M3.4 12h17.2"/><path d="M12 3.4c2.4 2.3 3.7 5.3 3.7 8.6s-1.3 6.3-3.7 8.6c-2.4-2.3-3.7-5.3-3.7-8.6S9.6 5.7 12 3.4z"/>',
	);

	if ( empty( $paths[ $key ] ) ) {
		return '';
	}

	return '<svg class="wm-company-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $key ] . '</svg>';
}

/**
 * Prints the shared section header (title with the gold underline + optional
 * subtitle) used by every company-page section.
 *
 * @param string $title    Section title.
 * @param string $subtitle Section subtitle.
 */
function wm_company_section_header( $title, $subtitle = '' ) {
	if ( '' === trim( (string) $title ) && '' === trim( (string) $subtitle ) ) {
		return;
	}
	?>
	<div class="wm-company-section__header">
		<?php if ( '' !== trim( (string) $title ) ) : ?>
			<h2 class="wm-company-section__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $subtitle ) ) : ?>
			<p class="wm-company-section__subtitle"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Image HTML helper for company blocks (attachment ID based).
 *
 * @param int    $image_id Attachment ID.
 * @param string $size     Image size.
 * @param array  $attrs    Extra attributes.
 * @return string
 */
function wm_company_image_html( $image_id, $size = 'large', $attrs = array() ) {
	$image_id = absint( $image_id );
	if ( ! $image_id ) {
		return '';
	}

	$attrs = wp_parse_args( $attrs, array( 'loading' => 'lazy' ) );

	return wp_get_attachment_image( $image_id, $size, false, $attrs );
}
