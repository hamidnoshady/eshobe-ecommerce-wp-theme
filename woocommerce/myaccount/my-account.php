<?php
/**
 * Custom my account page.
 *
 * @package WM_Theme
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

?>

<section class="wm-account-page" dir="rtl">
	<div class="wm-account-page__container">
		<header class="wm-account-page__header">
			<div>
				<span class="wm-account-page__eyebrow"><?php echo esc_html__( 'پنل کاربری', 'eshobe-ecommerce' ); ?></span>
				<h2 class="wm-account-page__title"><?php echo esc_html__( 'حساب کاربری', 'eshobe-ecommerce' ); ?></h2>
			</div>
		</header>

		<div class="wm-account-layout">
			<?php
			/**
			 * My Account navigation.
			 *
			 * @since 2.6.0
			 */
			do_action( 'woocommerce_account_navigation' );
			?>

			<div class="wm-account-content">
				<div class="woocommerce-MyAccount-content">
					<?php
					/**
					 * My Account content.
					 *
					 * @since 2.6.0
					 */
					do_action( 'woocommerce_account_content' );
					?>
				</div>
			</div>
		</div>
	</div>
</section>
