<?php
/**
 * The Account > Edit Profile template.
 *
 * Displays the user account page.
 *
 * @package WPSC
 * @since WPSC 3.8.10
 */
?>

<form method="post">
	<table>

		<?php wpsc_display_form_fields(); ?>

	</table>

	<table>
		<tr>
			<td>
				<input type="hidden" value="true" name="submitwpcheckout_profile" />
				<input type="submit" value="<?php _e( 'Save Profile', 'wpsc' ); ?>" name="submit" />
			</td>
		</tr>
	</table>



</form>