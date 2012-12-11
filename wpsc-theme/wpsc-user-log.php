<?php
/**
 * The User Account template wrapper.
 *
 * Displays the user account page.
 *
 * @package WPSC
 * @since WPSC 3.8
 */
global $files, $separator, $col_count, $products; ?>

<div class="wrap">
	<?php if ( is_user_logged_in() ) : ?>
		<?php do_action( 'wpsc_additional_before_user_profile_links' ); ?>

		<div class="user-profile-links">
			<?php

			$profile_tabs = apply_filters( 'wpsc_user_profile_tabs', array(
				'purchase_history'	=> __( 'Purchase History', 'wpsc' ),
				'edit_profile'		=> __( 'Your Details', 'wpsc' ),
				'downloads'			=> __( 'Your Downloads', 'wpsc' )
			) );

			$default_profile_tab = apply_filters( 'wpsc_default_user_profile_tab', current( array_keys( $profile_tabs ) ) );
			$current_tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : $default_profile_tab;

			$i = 0;
			foreach ( $profile_tabs as $tab_id => $tab_title ) : ?>
				<a href="<?php echo get_option( 'user_account_url' ) . $separator . 'tab=' . $tab_id; ?>" class="<?php if ( $current_tab == $tab_id ) echo 'current'; ?>"><?php echo $tab_title; ?></a>
				<?php if ( ++$i < count( $profile_tabs ) ) echo '|'; ?>
			<?php endforeach; ?>

			<?php do_action( 'wpsc_additional_user_profile_links', '|' ); ?>

		</div>

		<?php do_action( 'wpsc_additional_after_user_profile_links' ); ?>
		<?php do_action( 'wpsc_additional_before_user_profile_section' ); ?>

		<?php
		if ( isset( $profile_tabs[$current_tab] ) )
			do_action( 'wpsc_additional_user_profile_section_' . $current_tab );
		else
			do_action( 'wpsc_additional_user_profile_section_' . $default_profile_tab );

		do_action( 'wpsc_additional_after_user_profile_section' );
		?>

	<?php else : ?>

		<?php _e( 'You must be logged in to use this page. Please use the form below to login to your account.', 'wpsc' ); ?>

		<form name="loginform" id="loginform" action="<?php echo wp_login_url(); ?>" method="post">
			<p>
				<label><?php _e( 'Username:', 'wpsc' ); ?><br /><input type="text" name="log" id="log" value="" size="20" tabindex="1" /></label>
			</p>

			<p>
				<label><?php _e( 'Password:', 'wpsc' ); ?><br /><input type="password" name="pwd" id="pwd" value="" size="20" tabindex="2" /></label>
			</p>

			<p>
				<label>
					<input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" />
					<?php _e( 'Remember me', 'wpsc' ); ?>
				</label>
			</p>

			<p class="submit">
				<input type="submit" name="submit" id="submit" value="<?php _e( 'Login &raquo;', 'wpsc' ); ?>" tabindex="4" />
				<input type="hidden" name="redirect_to" value="<?php echo get_option( 'user_account_url' ); ?>" />
			</p>
		</form>

	<?php endif; ?>

</div>
