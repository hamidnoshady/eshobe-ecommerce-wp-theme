<?php
/**
 * OTP login/registration modal (Kavenegar one-time password).
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! wm_technical_otp_enabled() || is_user_logged_in() ) {
	return;
}
?>
<div id="wm-otp-modal" class="wm-otp-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'ورود یا ثبت‌نام', 'watchmid' ); ?>" dir="rtl" hidden>
	<div class="wm-otp-modal__backdrop" data-otp-modal-close></div>
	<div class="wm-otp-modal__panel">
		<button type="button" class="wm-otp-modal__close" data-otp-modal-close aria-label="<?php echo esc_attr__( 'بستن', 'watchmid' ); ?>">×</button>

		<div class="wm-otp-modal__step" data-otp-step="phone">
			<h2 class="wm-otp-modal__title"><?php echo esc_html__( 'ورود / ثبت‌نام', 'watchmid' ); ?></h2>
			<p class="wm-otp-modal__desc"><?php echo esc_html__( 'برای ادامه، شماره موبایل خود را وارد کنید تا کد یکبارمصرف برایتان ارسال شود.', 'watchmid' ); ?></p>
			<form data-otp-phone-form>
				<label class="screen-reader-text" for="wm-otp-phone"><?php echo esc_html__( 'شماره موبایل', 'watchmid' ); ?></label>
				<input type="tel" id="wm-otp-phone" class="wm-otp-modal__input" name="phone" inputmode="numeric" autocomplete="tel" placeholder="09xxxxxxxxx" required>
				<button type="submit" class="wm-otp-modal__submit"><?php echo esc_html__( 'ارسال کد', 'watchmid' ); ?></button>
			</form>
			<p class="wm-otp-modal__error" data-otp-error hidden></p>
		</div>

		<div class="wm-otp-modal__step" data-otp-step="code" hidden>
			<h2 class="wm-otp-modal__title"><?php echo esc_html__( 'کد تایید', 'watchmid' ); ?></h2>
			<p class="wm-otp-modal__desc">
				<?php echo esc_html__( 'کد ارسال‌شده به', 'watchmid' ); ?>
				<strong data-otp-phone-display></strong>
				<?php echo esc_html__( 'را وارد کنید.', 'watchmid' ); ?>
			</p>
			<form data-otp-code-form>
				<label class="screen-reader-text" for="wm-otp-code"><?php echo esc_html__( 'کد تایید', 'watchmid' ); ?></label>
				<input type="tel" id="wm-otp-code" class="wm-otp-modal__input wm-otp-modal__input--code" name="code" inputmode="numeric" maxlength="5" autocomplete="one-time-code" required>
				<button type="submit" class="wm-otp-modal__submit"><?php echo esc_html__( 'ورود', 'watchmid' ); ?></button>
			</form>
			<div class="wm-otp-modal__actions">
				<button type="button" class="wm-otp-modal__resend" data-otp-resend disabled>
					<?php echo esc_html__( 'ارسال دوباره کد', 'watchmid' ); ?>
					(<span data-otp-resend-timer>60</span>)
				</button>
				<button type="button" class="wm-otp-modal__back" data-otp-back><?php echo esc_html__( 'تغییر شماره موبایل', 'watchmid' ); ?></button>
			</div>
			<p class="wm-otp-modal__error" data-otp-error hidden></p>
		</div>

		<div class="wm-otp-modal__step" data-otp-step="password" hidden>
			<h2 class="wm-otp-modal__title"><?php echo esc_html__( 'تعیین رمز عبور', 'watchmid' ); ?></h2>
			<p class="wm-otp-modal__desc"><?php echo esc_html__( 'برای استفاده‌های بعدی، یک رمز عبور برای حساب خود تعیین کنید.', 'watchmid' ); ?></p>
			<form data-otp-password-form>
				<label class="screen-reader-text" for="wm-otp-password"><?php echo esc_html__( 'رمز عبور', 'watchmid' ); ?></label>
				<input type="password" id="wm-otp-password" class="wm-otp-modal__input" name="password" autocomplete="new-password" minlength="6" placeholder="<?php echo esc_attr__( 'رمز عبور', 'watchmid' ); ?>" required>
				<button type="submit" class="wm-otp-modal__submit"><?php echo esc_html__( 'ثبت و ورود', 'watchmid' ); ?></button>
			</form>
			<p class="wm-otp-modal__error" data-otp-error hidden></p>
		</div>
	</div>
</div>
