<?php
/**
 * The Account - Purchase History template.
 *
 * @package WPSC
 * @since WPSC 3.9.4
 */
global $col_count; ?>

<?php if ( wpsc_has_purchases() ) : ?>

	<table class="logdisplay">

	<?php if ( wpsc_has_purchases_this_month() ) : ?>
		
			<tr class="toprow">
				<td><strong><?php _e( 'Status', 'wpsc' ); ?></strong></td>
				<td><strong><?php _e( 'Date', 'wpsc' ); ?></strong></td>
				<td><strong><?php _e( 'Price', 'wpsc' ); ?></strong></td>

				<?php if ( get_option( 'payment_method' ) == 2 ) : ?>

					<td><strong><?php _e( 'Payment Method', 'wpsc' ); ?></strong></td>

				<?php endif; ?>

			</tr>

			<?php wpsc_user_details(); ?>

	<?php else : ?>

			<tr>
				<td colspan="<?php echo $col_count; ?>">

					<?php _e( 'No transactions for this month.', 'wpsc' ); ?>

				</td>
			</tr>

	<?php endif; ?>

	</table>

<?php else : ?>

	<table>
		<tr>
			<td><?php _e( 'There have not been any purchases yet.', 'wpsc' ); ?></td>
		</tr>
	</table>

<?php endif; ?>