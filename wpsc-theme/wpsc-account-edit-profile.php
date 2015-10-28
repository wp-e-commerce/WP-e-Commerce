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

	<?php echo validate_form_data(); ?>

	<table>

		<?php wpsc_display_form_fields(); ?>

		<tr>
			<td></td>
			<td>
				<input type="hidden" value="true" name="submitwpcheckout_profile" />
				<input type="submit" value="<?php _e( 'Save Profile', 'wp-e-commerce' ); ?>" name="submit" />
			</td>
		</tr>
	</table>
</form>