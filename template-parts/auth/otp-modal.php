<?php
/**
 * Login / registration modal (phone-first, always multi-step).
 *
 * @package WM_Theme
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() ) {
	return;
}
?>
<div id="wm-otp-modal" class="wm-otp-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'ورود یا ثبت‌نام', 'eshobe-ecommerce' ); ?>" dir="rtl" hidden>
	<div class="wm-otp-modal__backdrop"></div>
	<div class="wm-otp-modal__panel">
		<button type="button" class="wm-otp-modal__close" data-otp-modal-close aria-label="<?php echo esc_attr__( 'بستن', 'eshobe-ecommerce' ); ?>">×</button>

		<!-- Confirm-exit bar: shown when × is clicked mid-flow -->
		<div class="wm-otp-modal__confirm-exit" data-otp-confirm-exit hidden>
			<span><?php echo esc_html__( 'فرآیند نیمه‌کاره است — خروج؟', 'eshobe-ecommerce' ); ?></span>
			<div class="wm-otp-modal__confirm-exit-actions">
				<button type="button" class="wm-otp-modal__confirm-exit-btn wm-otp-modal__confirm-exit-btn--cancel" data-otp-exit-cancel><?php echo esc_html__( 'ادامه فرآیند', 'eshobe-ecommerce' ); ?></button>
				<button type="button" class="wm-otp-modal__confirm-exit-btn wm-otp-modal__confirm-exit-btn--confirm" data-otp-exit-confirm><?php echo esc_html__( 'خروج', 'eshobe-ecommerce' ); ?></button>
			</div>
		</div>
		<span class="wm-otp-modal__accent" aria-hidden="true"></span>
		<h2 class="wm-otp-modal__title"><?php echo esc_html__( 'ورود یا ثبت‌نام', 'eshobe-ecommerce' ); ?></h2>

		<!-- Step 1: Phone number -->
		<div class="wm-otp-modal__step" data-otp-step="phone">
			<p class="wm-otp-modal__desc"><?php echo esc_html__( 'شماره موبایل خود را وارد کنید.', 'eshobe-ecommerce' ); ?></p>
			<!-- Resume banner: shown when sessionStorage has a saved phone from a prior incomplete flow -->
			<div class="wm-otp-modal__resume-banner" data-otp-resume-banner hidden>
				<div class="wm-otp-modal__resume-text">
					<?php echo esc_html__( 'فرآیند قبلی نیمه‌کاره است — شماره', 'eshobe-ecommerce' ); ?>
					<strong data-otp-phone-display></strong>
				</div>
				<div class="wm-otp-modal__resume-actions">
					<button type="button" class="wm-otp-modal__resume-btn wm-otp-modal__resume-btn--continue" data-otp-resume-continue><?php echo esc_html__( 'ادامه ثبت‌نام', 'eshobe-ecommerce' ); ?></button>
					<button type="button" class="wm-otp-modal__resume-btn wm-otp-modal__resume-btn--reset" data-otp-resume-reset><?php echo esc_html__( 'شماره جدید', 'eshobe-ecommerce' ); ?></button>
				</div>
			</div>
			<form data-otp-phone-form>
				<label class="screen-reader-text" for="wm-otp-phone"><?php echo esc_html__( 'شماره موبایل', 'eshobe-ecommerce' ); ?></label>
				<input type="tel" id="wm-otp-phone" class="wm-otp-modal__input" name="phone" inputmode="numeric" autocomplete="tel" placeholder="09xxxxxxxxx" required>
				<button type="submit" class="wm-otp-modal__submit"><?php echo esc_html__( 'ادامه', 'eshobe-ecommerce' ); ?></button>
			</form>
			<p class="wm-otp-modal__error" data-otp-error hidden></p>
		</div>

		<!-- Step 2a: Existing user — enter password -->
		<div class="wm-otp-modal__step" data-otp-step="existing-password" hidden>
			<h3 class="wm-otp-modal__subtitle"><?php echo esc_html__( 'ورود به حساب کاربری', 'eshobe-ecommerce' ); ?></h3>
			<p class="wm-otp-modal__desc">
				<?php echo esc_html__( 'رمز عبور حساب', 'eshobe-ecommerce' ); ?>
				<strong data-otp-phone-display></strong>
				<?php echo esc_html__( 'را وارد کنید.', 'eshobe-ecommerce' ); ?>
			</p>
			<form data-otp-existing-password-form>
				<label class="screen-reader-text" for="wm-otp-existing-password"><?php echo esc_html__( 'رمز عبور', 'eshobe-ecommerce' ); ?></label>
				<input type="password" id="wm-otp-existing-password" class="wm-otp-modal__input" name="password" autocomplete="current-password" placeholder="<?php echo esc_attr__( 'رمز عبور', 'eshobe-ecommerce' ); ?>" required>
				<button type="submit" class="wm-otp-modal__submit"><?php echo esc_html__( 'ورود', 'eshobe-ecommerce' ); ?></button>
			</form>
			<div class="wm-otp-modal__actions">
				<button type="button" class="wm-otp-modal__resend" data-otp-login-with-code><?php echo esc_html__( 'ورود با کد یکبارمصرف', 'eshobe-ecommerce' ); ?></button>
				<button type="button" class="wm-otp-modal__back" data-otp-back><?php echo esc_html__( 'تغییر شماره', 'eshobe-ecommerce' ); ?></button>
			</div>
			<p class="wm-otp-modal__error" data-otp-error hidden></p>
		</div>

		<!-- Step 2b: New user — OTP code verification -->
		<div class="wm-otp-modal__step" data-otp-step="code" hidden>
			<h3 class="wm-otp-modal__subtitle"><?php echo esc_html__( 'تأیید شماره موبایل', 'eshobe-ecommerce' ); ?></h3>
			<p class="wm-otp-modal__desc">
				<?php echo esc_html__( 'کد ارسال‌شده به', 'eshobe-ecommerce' ); ?>
				<strong data-otp-phone-display></strong>
				<?php echo esc_html__( 'را وارد کنید.', 'eshobe-ecommerce' ); ?>
			</p>
			<form data-otp-code-form>
				<label class="screen-reader-text" for="wm-otp-code"><?php echo esc_html__( 'کد تأیید', 'eshobe-ecommerce' ); ?></label>
				<input type="tel" id="wm-otp-code" class="wm-otp-modal__input wm-otp-modal__input--code" name="code" inputmode="numeric" maxlength="5" autocomplete="one-time-code" required>
				<button type="submit" class="wm-otp-modal__submit"><?php echo esc_html__( 'تأیید', 'eshobe-ecommerce' ); ?></button>
			</form>
			<div class="wm-otp-modal__actions">
				<button type="button" class="wm-otp-modal__resend" data-otp-resend disabled>
					<?php echo esc_html__( 'ارسال دوباره کد', 'eshobe-ecommerce' ); ?>
					(<span data-otp-resend-timer>60</span>)
				</button>
				<button type="button" class="wm-otp-modal__back" data-otp-back><?php echo esc_html__( 'تغییر شماره', 'eshobe-ecommerce' ); ?></button>
			</div>
			<p class="wm-otp-modal__error" data-otp-error hidden></p>
		</div>

		<!-- Step 3: New user — set password after OTP -->
		<div class="wm-otp-modal__step" data-otp-step="password" hidden>
			<h3 class="wm-otp-modal__subtitle"><?php echo esc_html__( 'تعیین رمز عبور', 'eshobe-ecommerce' ); ?></h3>
			<p class="wm-otp-modal__desc"><?php echo esc_html__( 'برای دفعات بعدی یک رمز عبور برای حساب خود تعیین کنید.', 'eshobe-ecommerce' ); ?></p>
			<form data-otp-password-form>
				<label class="screen-reader-text" for="wm-otp-new-password"><?php echo esc_html__( 'رمز عبور جدید', 'eshobe-ecommerce' ); ?></label>
				<input type="password" id="wm-otp-new-password" class="wm-otp-modal__input" name="password" autocomplete="new-password" minlength="6" placeholder="<?php echo esc_attr__( 'رمز عبور (حداقل ۶ کاراکتر)', 'eshobe-ecommerce' ); ?>" required>
				<button type="submit" class="wm-otp-modal__submit"><?php echo esc_html__( 'ثبت و ورود', 'eshobe-ecommerce' ); ?></button>
			</form>
			<p class="wm-otp-modal__error" data-otp-error hidden></p>
		</div>


</div>
</div>
