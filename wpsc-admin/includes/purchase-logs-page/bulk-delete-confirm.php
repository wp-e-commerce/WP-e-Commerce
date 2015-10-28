<h3>
	<?php esc_html_e( 'Are you sure you want to delete these purchase logs?', 'wp-e-commerce' ); ?><br />
</h3>
<div>
	<a href="<?php echo esc_url( wp_get_referer() ); ?>" class="button"><?php esc_html_e( 'Go Back', 'wp-e-commerce' ); ?></a>
	<input class="button-primary" type="submit" value="<?php esc_attr_e( 'Delete', 'wp-e-commerce' ); ?>" />
	<input type="hidden" name="confirm" value="1" />
	<input type="hidden" name="action" value="delete" />
</div>