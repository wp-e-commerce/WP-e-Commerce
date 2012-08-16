<div class="wrap">
	<div id="icon-tools" class="icon32"><br></div>
	<h2><?php esc_html_e( 'WP e-Commerce Database Upgrade', 'wpsc' ); ?></h2>
	<form class="wpsc-db-upgrade" action="" method="post">
		<h3><?php echo esc_html( $update_title ); ?></h3>
		<p><?php esc_html_e( 'Click "Start Database Upgrade" when you are ready.', 'wpsc' ); ?></p>
		<?php wp_nonce_field( 'wpsc_db_upgrade' ); ?>
		<input type="hidden" name="action" value="start_upgrade" />
		<?php submit_button( __( 'Start Database Upgrade', 'wpsc' ), 'primary', 'submit', false ); ?>
	</form>
</div>