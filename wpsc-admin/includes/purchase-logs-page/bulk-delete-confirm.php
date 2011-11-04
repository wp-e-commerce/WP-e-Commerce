<h3>
	<?php esc_html_e( 'Are you sure you want to delete these purchase logs?', 'wpsc'); ?><br />
</h3>
<div>
	<a href="<?php echo esc_url( wp_get_referer() ); ?>" class="button">Go Back</a>
	<input class="button-primary" type="submit" value="Delete" />
	<input type="hidden" name="confirm" value="1" />
	<input type="hidden" name="action" value="delete" />
</div>