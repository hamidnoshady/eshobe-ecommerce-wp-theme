<?php
/**
 * Render callback for wm/stat-item.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'number' => 0,
		'prefix' => '',
		'suffix' => '+',
		'label'  => '',
	)
);

$number = max( 0, (float) $a['number'] );
if ( '' === trim( (string) $a['label'] ) && ! $number ) {
	return;
}

// Persian-locale display of the final value; the counter JS animates toward
// data-wm-count and re-formats with the same locale on each frame.
$formatted = function_exists( 'number_format_i18n' ) ? number_format_i18n( $number ) : number_format( $number );
?>
<div class="wm-company-stat">
	<span class="wm-company-stat__value" dir="ltr">
		<?php if ( '' !== trim( (string) $a['prefix'] ) ) : ?>
			<span class="wm-company-stat__prefix"><?php echo esc_html( $a['prefix'] ); ?></span>
		<?php endif; ?>
		<span class="wm-company-stat__number" data-wm-count="<?php echo esc_attr( $number ); ?>"><?php echo esc_html( $formatted ); ?></span>
		<?php if ( '' !== trim( (string) $a['suffix'] ) ) : ?>
			<span class="wm-company-stat__suffix"><?php echo esc_html( $a['suffix'] ); ?></span>
		<?php endif; ?>
	</span>
	<?php if ( '' !== trim( (string) $a['label'] ) ) : ?>
		<span class="wm-company-stat__label"><?php echo esc_html( $a['label'] ); ?></span>
	<?php endif; ?>
</div>
