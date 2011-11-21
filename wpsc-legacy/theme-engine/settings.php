<?php

add_action( 'wpsc_legacy_theme_metabox', 'wpsc_settings_theme_metabox' );

function wpsc_settings_theme_metabox(){
	$wpsc_templates    = wpsc_list_product_templates();
	$themes_location   = wpsc_check_theme_location();
	$themes_copied     = false; //Check to see whether themes have been copied to selected Theme Folder
	$themes_backedup   = false; //Check to see whether themes have recently been backedup
	$themes_in_uploads = false; //Check to see whether themes live in the uploads directory

	if ( isset( $_SESSION['wpsc_themes_copied'] ) && ( true == $_SESSION['wpsc_themes_copied'] ) )
		$themes_copied = true;

	if ( isset( $_SESSION['wpsc_themes_backup'] ) && ( true == $_SESSION['wpsc_themes_backup'] ) )
		$themes_backedup = true;

	if ( wpsc_count_themes_in_uploads_directory() > 0 ) {
		$themes_in_uploads = true;

		foreach( (array)$themes_location as $location )
			$new_location[] = str_ireplace( 'wpsc-','', $location );

		$themes_location = $new_location;
	}

	// Used to flush transients - @since 3.8-development
	if ( true === $themes_copied )
		do_action( 'wpsc_move_theme' );

?>
	<div id="poststuff" class="metabox-holder">
		<div id="themes_and_appearance" class='postbox'>
			<h3 class="hndle"><span><?php _e( "Advanced Theme Settings", 'wpsc' ); ?></span></h3>
				<div class="inside">
				<?php

				if( isset( $_SESSION['wpsc_theme_empty'] ) && ($_SESSION['wpsc_theme_empty'] == true)  ) {
					?>

						<div class="updated fade below-h2" id="message" style="background-color: rgb(255, 251, 204);">
							<p><?php _e('You did not specify any template files to be moved.','wpsc'); ?></p>
						</div>
					<?php
					$_SESSION['wpsc_theme_empty'] = false;
					$themes_copied = false;
				}
				if ( isset( $_SESSION['wpsc_themes_copied'] ) && ($_SESSION['wpsc_themes_copied'] == true) ) {
					?>
						<div class="updated fade below-h2" id="message" style="background-color: rgb(255, 251, 204);">
							<?php if(in_array(false, $_SESSION['wpsc_themes_copied_results'], true)): ?>
								<p style="color:red;"><?php _e( "Error: some files could not be copied. Please make sure that theme folder is writable.", 'wpsc' ); ?></p>
							<?php else: ?>
								<p><?php _e( "Thanks, the themes have been copied.", 'wpsc' ); ?></p>
							<?php endif; ?>
						</div>
					<?php
						unset($_SESSION['wpsc_themes_copied']);
						unset($_SESSION['wpsc_themes_copied_results']);
					}
					if ( isset( $_SESSION['wpsc_themes_backup'] ) && ($_SESSION['wpsc_themes_backup'] == true) ) {
					?>
						<div class="updated fade below-h2" id="message" style="background-color: rgb(255, 251, 204);">
							<p><?php _e( "Thanks, you have made a succesful backup of your theme.  It is located at the URL below.  Please note each backup you create will replace your previous backups.", 'wpsc' ); ?></p>
							<p>URL: <?php echo "/" . str_replace( ABSPATH, "", WPSC_THEME_BACKUP_DIR ); ?></p>
						</div>
					<?php
						$_SESSION['wpsc_themes_backup'] = false;
					}
				?>
				<p>
				<?php if(false !== $themes_location)
						//Some themes have been moved to the themes folder
					_e('Some Theme files have been moved to your WordPress Theme Folder.','wpsc');
				else
				    _e('No Theme files have been moved to your WordPress Theme Folder.','wpsc');

				 ?>

				</p>
				<p>
					<?php _e('WP e-Commerce provides you the ability to move your theme files to a safe place for theming control.

If you want to change the look of your site, select the files you want to edit from the list and click the move button. This will copy the template files to your active WordPress theme. ','wpsc'); ?>
				</p>
				<ul>
				<?php
					foreach($wpsc_templates as $file){
						$id = str_ireplace('.', '_', $file);
						$selected = '';
						if(false !== array_search($file, (array)$themes_location))
							$selected = 'checked="checked"';
						?>
						<li><input type='checkbox' id='<?php echo $id; ?>' <?php echo $selected; ?> value='<?php esc_attr_e( $file ); ?>' name='wpsc_templates_to_port[]' />
						<label for='<?php echo $id; ?>'><?php esc_attr_e( $file ); ?></label></li>
				<?php }	 ?>
				 </ul>
				 <p>
				 <?php if(false !== $themes_location){
				 _e('To change the look of certain aspects of your shop, you can edit the moved files that are found here:','wpsc');
				 ?>
				 </p>
				 <p class="howto">	<?php echo  get_stylesheet_directory(); ?></p>
				<?php } ?>
				<p><?php
					wp_nonce_field('wpsc_copy_themes');
					?>
					<input type='submit' value='Move Template Files &rarr;' class="button" name='wpsc_move_themes' />
				</p>
				 <p><?php _e('You can create a copy of your WordPress Theme by clicking the backup button bellow. Once copied you can find them here:' ,'wpsc'); ?></p>
				<p class="howto"> /wp-content/uploads/wpsc/theme_backup/ </p>
				<p>
					<?php
					printf( __( '<a href="%s" class="button">Backup Your WordPress Theme</a>', 'wpsc' ), wp_nonce_url( 'admin.php?wpsc_admin_action=backup_themes', 'backup_themes' ) ); ?>
					<br style="clear:both" />
				</p>

				<br style="clear:both" />
				 <p><?php _e('If you have moved your files in some other way i.e FTP, you may need to click the Flush Theme Cache. This will refresh the locations WordPress looks for your templates.' ,'wpsc'); ?></p>
				<p><?php printf( __( '<a href="%s" class="button">Flush Theme Cache</a>', 'wpsc' ), wp_nonce_url( 'admin.php?wpsc_flush_theme_transients=true', 'wpsc_flush_theme_transients' ) ); ?></p>
				<br style="clear:both" />
				<br style="clear:both" />
				</div>
		</div>
	</div>
<?php
}