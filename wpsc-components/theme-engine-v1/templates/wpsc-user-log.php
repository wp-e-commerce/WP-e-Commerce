<?php
/**
 * The User Account template wrapper.
 *
 * Displays the user account page.
 *
 * @package WPSC
 * @since WPSC 3.8
 */
global $current_tab; ?>

<div class="wrap">
	<?php if ( is_user_logged_in() ) : ?>
		<div class="user-profile-links">

			<?php $default_profile_tab = apply_filters( 'wpsc_default_user_profile_tab', 'purchase_history' ); ?>
			<?php $current_tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : $default_profile_tab; ?>

			<?php wpsc_user_profile_links(); ?>

			<?php do_action( 'wpsc_additional_user_profile_links', '|' ); ?>

		</div>

		<?php do_action( 'wpsc_user_profile_section_' . $current_tab ); ?>

	<?php else : ?>

		<?php _e( 'You must be logged in to use this page. Please use the form below to log in to your account.', 'wp-e-commerce' ); ?>

		<form name="loginform" id="loginform" action="<?php echo esc_url( wp_login_url() ); ?>" method="post">
			<p>
				<label><?php _e( 'Username:', 'wp-e-commerce' ); ?><br /><input type="text" name="log" id="log" value="" size="20" tabindex="1" /></label>
			</p>

			<p>
				<label><?php _e( 'Password:', 'wp-e-commerce' ); ?><br /><input type="password" name="pwd" id="pwd" value="" size="20" tabindex="2" /></label>
			</p>

			<p>
				<label>
					<input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" />
					<?php _e( 'Remember me', 'wp-e-commerce' ); ?>
				</label>
			</p>

			<p class="submit">
				<input type="submit" name="submit" id="submit" value="<?php _e( 'Login &raquo;', 'wp-e-commerce' ); ?>" tabindex="4" />
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_option( 'user_account_url' ) ); ?>" />
			</p>
		</form>

	<?php endif; ?>

</div>
