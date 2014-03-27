<?php
/**
 * The Account > Downloads template.
 *
 * Displays the user account page.
 *
 * @package WPSC
 * @since WPSC 3.8.10
 */
?>

<?php if ( empty( $items ) ) : ?>
	<?php _e( 'You have not purchased any downloadable products yet.', 'wpsc' ); ?>
<?php else : ?>
	<table class="logdisplay">
		<thead>
			<tr>
				<th class="wpsc-user-log-file-name" scope="col"><?php _e( 'File Names', 'wpsc' ); ?> </th>
				<th class="wpsc-user-log-downloads-left" scope="col"><?php _e( 'Downloads Left', 'wpsc' ); ?> </th>
				<th class="wpsc-user-log-file-date" scope="col"><?php _e( 'Date', 'wpsc' ); ?> </th>
			</tr>
		</thead>

		<tbody>
			<?php foreach( $items as $key => $item ): ?>
				<tr class="wpsc-user-log-file<?php echo ( $key %2 == 1 ) ? '' : ' alt'; ?>">
					<td class="wpsc-user-log-file-name">
						<?php echo $item->title; ?>
					</td>
					<td class="wpsc-user-log-downloads-left">
						<?php echo esc_html( $item->downloads ); ?>
					</td>
					<td class="wpsc-user-log-file-date">
						<?php echo esc_html( $item->datetime ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>