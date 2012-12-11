<?php
/**
 * The Account - Downloads template.
 *
 * Displays the user account page.
 *
 * @package WPSC
 * @since WPSC 3.8
 */
global $files, $products; ?>

<?php if ( wpsc_has_downloads() ) : ?>

	<table class="logdisplay">
		<tr>
			<th><?php _e( 'File Names', 'wpsc' ); ?> </th>
			<th><?php _e( 'Downloads Left', 'wpsc' ); ?> </th>
			<th><?php _e( 'Date', 'wpsc' ); ?> </th>
		</tr>

		<?php
			$i = 0;
			foreach ( (array)$files as $file ) :

				$alternate = "";

				if ( ( $i % 2 ) != 1 )
					$alternate = "class='alt'";
		?>

				<tr <?php echo $alternate; ?>>
					<td>
		<?php
			if ( $products[$i]['downloads'] > 0 )
			
				echo "<a href = " . get_option('siteurl')."?downloadid=".$products[$i]['uniqueid'] . ">" . $file['post_title'] . "</a>";
			else
				echo $file['post_title'] . "";

		?>

					</td>
					<td><?php echo $products[$i]['downloads']; ?></td>
					<td><?php echo date( get_option( "date_format" ), strtotime( $products[$i]['datetime'] ) ); ?></td>
				</tr>
		<?php
				$i++;
			endforeach;
		?>

	</table>
<?php else : ?>

	<?php _e( 'You have not purchased any downloadable products yet.', 'wpsc' ); ?>

<?php endif; ?>

</div>