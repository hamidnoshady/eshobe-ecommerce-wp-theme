<?php
/**
 * Render callback for wm/stats-row (InnerBlocks container for wm/stat-item).
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
<section class="wm-company-section wm-company-stats wm-section-decor wm-section-decor--filters wm-section-decor--ring">
	<?php wm_company_section_header( $a['title'], $a['subtitle'] ); ?>
	<div class="wm-company-stats__grid">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks. ?>
	</div>
</section>
