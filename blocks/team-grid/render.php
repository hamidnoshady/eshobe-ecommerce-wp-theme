<?php
/**
 * Render callback for wm/team-grid (InnerBlocks container for wm/team-member).
 *
 * @package WM_Theme
 * @var array  $attributes
 * @var string $content Rendered inner blocks.
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
	return;
}

$a = wp_parse_args( $attributes, array( 'title' => '', 'subtitle' => '' ) );
?>
<section class="wm-company-section wm-company-team wm-section-decor wm-section-decor--styles wm-section-decor--soft-grid">
	<?php wm_company_section_header( $a['title'], $a['subtitle'] ); ?>
	<div class="wm-company-team__grid">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks. ?>
	</div>
</section>
