<?php
/**
 * Render callback for wm/contact-form.
 *
 * Markup only — submission goes through the AJAX endpoint in
 * inc/ajax/contact-form.php (nonce + honeypot + rate limit), with a
 * no-JS POST fallback handled by the same endpoint via admin-post.php.
 *
 * @package WM_Theme
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$attributes,
	array(
		'title'          => '',
		'subtitle'       => '',
		'showPhone'      => true,
		'showSubject'    => true,
		'buttonText'     => '',
		'successMessage' => '',
	)
);

$button_text = '' !== trim( (string) $a['buttonText'] ) ? $a['buttonText'] : __( 'ارسال پیام', 'eshobe-ecommerce' );
$success_msg = '' !== trim( (string) $a['successMessage'] ) ? $a['successMessage'] : __( 'پیام شما با موفقیت ارسال شد. به‌زودی با شما تماس می‌گیریم.', 'eshobe-ecommerce' );
$form_id     = 'wm-contact-form-' . wp_unique_id();

// no-JS fallback result (redirected back with ?wm_contact=sent|failed).
$fallback_status = isset( $_GET['wm_contact'] ) ? sanitize_key( wp_unslash( $_GET['wm_contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
?>
<section class="wm-company-section wm-company-form wm-section-decor wm-section-decor--filters wm-section-decor--ring">
	<?php wm_company_section_header( $a['title'], $a['subtitle'] ); ?>

	<form
		id="<?php echo esc_attr( $form_id ); ?>"
		class="wm-company-form__form"
		method="post"
		action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		data-wm-contact-form
		data-success-message="<?php echo esc_attr( $success_msg ); ?>"
		novalidate
	>
		<input type="hidden" name="action" value="wm_contact_form">
		<?php wp_nonce_field( 'wm_contact_form_nonce', 'wm_contact_nonce' ); ?>
		<input type="hidden" name="wm_contact_redirect" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php /* Honeypot: hidden from humans, bots fill it. */ ?>
		<div class="wm-company-form__hp" aria-hidden="true">
			<label for="<?php echo esc_attr( $form_id ); ?>-website"><?php esc_html_e( 'این فیلد را خالی بگذارید', 'eshobe-ecommerce' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $form_id ); ?>-website" name="wm_hp_website" tabindex="-1" autocomplete="off">
		</div>

		<div class="wm-company-form__grid">
			<p class="wm-company-form__field">
				<label for="<?php echo esc_attr( $form_id ); ?>-name"><?php esc_html_e( 'نام و نام خانوادگی', 'eshobe-ecommerce' ); ?> <span class="wm-company-form__req">*</span></label>
				<input type="text" id="<?php echo esc_attr( $form_id ); ?>-name" name="wm_contact_name" required maxlength="120" autocomplete="name">
			</p>
			<p class="wm-company-form__field">
				<label for="<?php echo esc_attr( $form_id ); ?>-email"><?php esc_html_e( 'ایمیل', 'eshobe-ecommerce' ); ?> <span class="wm-company-form__req">*</span></label>
				<input type="email" id="<?php echo esc_attr( $form_id ); ?>-email" name="wm_contact_email" required maxlength="160" autocomplete="email" dir="ltr">
			</p>
			<?php if ( ! empty( $a['showPhone'] ) ) : ?>
				<p class="wm-company-form__field">
					<label for="<?php echo esc_attr( $form_id ); ?>-phone"><?php esc_html_e( 'شماره تماس (اختیاری)', 'eshobe-ecommerce' ); ?></label>
					<input type="tel" id="<?php echo esc_attr( $form_id ); ?>-phone" name="wm_contact_phone" maxlength="20" autocomplete="tel" dir="ltr">
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $a['showSubject'] ) ) : ?>
				<p class="wm-company-form__field">
					<label for="<?php echo esc_attr( $form_id ); ?>-subject"><?php esc_html_e( 'موضوع (اختیاری)', 'eshobe-ecommerce' ); ?></label>
					<input type="text" id="<?php echo esc_attr( $form_id ); ?>-subject" name="wm_contact_subject" maxlength="160">
				</p>
			<?php endif; ?>
			<p class="wm-company-form__field wm-company-form__field--full">
				<label for="<?php echo esc_attr( $form_id ); ?>-message"><?php esc_html_e( 'متن پیام', 'eshobe-ecommerce' ); ?> <span class="wm-company-form__req">*</span></label>
				<textarea id="<?php echo esc_attr( $form_id ); ?>-message" name="wm_contact_message" rows="6" required maxlength="4000"></textarea>
			</p>
		</div>

		<div class="wm-company-form__footer">
			<button type="submit" class="wm-home-button wm-home-button--primary wm-company-form__submit">
				<span class="wm-company-form__submit-label"><?php echo esc_html( $button_text ); ?></span>
				<span class="wm-company-form__spinner" aria-hidden="true"></span>
			</button>
			<p class="wm-company-form__notice" role="status" aria-live="polite"
				<?php if ( 'sent' === $fallback_status ) : ?>
					data-state="success"><?php echo esc_html( $success_msg ); ?>
				<?php elseif ( 'failed' === $fallback_status ) : ?>
					data-state="error"><?php esc_html_e( 'ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.', 'eshobe-ecommerce' ); ?>
				<?php else : ?>
					>
				<?php endif; ?>
			</p>
		</div>
	</form>
</section>
