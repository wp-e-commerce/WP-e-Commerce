<div class='wrap'>

<h2><?php esc_html_e( 'Sales Upgrade Fix', 'wpsc' ); ?> </h2>

<?php if ( $numChanged != 0 && $numQueries != 0 ): ?>
	<div class="updated">
		<p><?php printf( __( 'Your purchase logs have been updated! <a href="%s">Click here</a> to return.' , 'wpsc' ), remove_query_arg( 'c' ) ); ?></p>
	</div>
<?php else: ?>
	<p><?php _e( 'Upgrading to WP e-Commerce 3.7 and later requires you to run this fix once. The following boxes correspond to the form fields in your current checkout page.  All you have to do is select from the drop-down menu box what each of the following fields represent. Sorry for any inconvenience caused, but we\'re sure you\'ll agree that the new purchase logs are worth this minor hassle.', 'wpsc' ); ?> </p>

	<div class="metabox-holder" style="width:700px">
	<form action='' method='post'>

		<?php

		$duplicate = array();
		foreach($formfields as $fields){
			if(!in_array($fields->name,$duplicate) && $fields->name != 'State'){
			echo '<div class="postbox" style="width:70%">';
			echo '<h3 class="handle">Billing '.$fields->name.'</h3>';
			echo '<div class="inside" style="padding:20px;">';
			echo '<label style="width:120px;float:left;" for="'.$fields->id.'">'.$fields->value.'</label>';
			echo $this->purchase_logs_fix_options( $fields->id );
			echo '</div>';
			echo '</div>';
			$duplicate[] = $fields->name;
			}else{
			echo '<div class="postbox" style="width:70%">';
			echo '<h3 class="handle">Shipping '.$fields->name.'</h3>';
			echo '<div class="inside" style="padding:20px;">';
			echo '<label style="width:120px;float:left;" for="'.$fields->id.'">'.$fields->value.'</label>';
			echo $this->purchase_logs_fix_options( $fields->id );
			echo '</div>';
			echo '</div>';

			}

		}
		?>
		<input type='submit' value='<?php _e( 'Apply', 'wpsc' ); ?>' class='button-secondary action' />
	</form>
	</div>
<?php endif; ?>
</div>