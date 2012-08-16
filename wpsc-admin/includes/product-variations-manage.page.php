<?php $this->list_table->display_messages(); ?>
<form action="" method="post">
	<?php $this->list_table->views(); ?>
	<input type="hidden" name="post_status" class="post_status_page" value="<?php echo !empty($_REQUEST['post_status']) ? esc_attr($_REQUEST['post_status']) : 'all'; ?>" />
	<?php wp_nonce_field( 'wpsc_save_variations_meta', '_wpsc_save_meta_nonce' ); ?>
	<?php $this->list_table->display(); ?>
</form>