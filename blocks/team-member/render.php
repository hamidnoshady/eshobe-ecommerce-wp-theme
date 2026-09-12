<?php
/**
 * Render callback for wm/team-member.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'imageId'   => 0,
		'name'      => '',
		'role'      => '',
		'bio'       => '',
		'instagram' => '',
		'telegram'  => '',
		'linkedin'  => '',
	)
);

if ( '' === trim( (string) $a['name'] ) ) {
	return;
}

$image  = wm_company_image_html( $a['imageId'], 'medium_large', array( 'class' => 'wm-company-member__img', 'alt' => $a['name'] ) );
$social = array_filter(
	array(
		'instagram' => array( 'url' => $a['instagram'], 'label' => __( 'اینستاگرام', 'eshobe-ecommerce' ), 'icon' => 'globe' ),
		'telegram'  => array( 'url' => $a['telegram'], 'label' => __( 'تلگرام', 'eshobe-ecommerce' ), 'icon' => 'chat' ),
		'linkedin'  => array( 'url' => $a['linkedin'], 'label' => __( 'لینکدین', 'eshobe-ecommerce' ), 'icon' => 'users' ),
	),
	function ( $item ) {
		return '' !== trim( (string) $item['url'] );
	}
);
?>
<div class="wm-company-member">
	<div class="wm-company-member__media<?php echo $image ? '' : ' wm-company-member__media--placeholder'; ?>">
		<?php
		if ( $image ) {
			echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image().
		} else {
			echo wm_company_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
		}
		?>
	</div>
	<div class="wm-company-member__body">
		<strong class="wm-company-member__name"><?php echo esc_html( $a['name'] ); ?></strong>
		<?php if ( '' !== trim( (string) $a['role'] ) ) : ?>
			<span class="wm-company-member__role"><?php echo esc_html( $a['role'] ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $a['bio'] ) ) : ?>
			<p class="wm-company-member__bio"><?php echo esc_html( $a['bio'] ); ?></p>
		<?php endif; ?>
		<?php if ( $social ) : ?>
			<div class="wm-company-member__social">
				<?php foreach ( $social as $item ) : ?>
					<a class="wm-company-member__social-link" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $item['label'] ); ?>">
						<?php echo wm_company_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
