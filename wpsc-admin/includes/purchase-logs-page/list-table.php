<div class="wrap">
	<div id="icon-users" class="icon32"><br/></div>
	<h2>
		<?php esc_html_e( 'Sales Log' ); ?>

		<?php
			if ( isset($_REQUEST['s']) && $_REQUEST['s'] )
				printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( stripslashes( $_REQUEST['s'] ) ) ); ?>
	</h2>

	<form id="purchase-logs-search" method="get" action="">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<?php $this->list_table->search_box( 'Search Sales Logs', 'post' ); ?>
	</form>

	<?php $this->list_table->views(); ?>

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="purchase-logs-filter" method="get" action="">
		<?php do_action( 'wpsc_purchase_logs_list_table_before' ); ?>
		<!-- For plugins, we also need to ensure that the form posts back to our current page -->
		<!-- Now we can render the completed list table -->

		<?php $this->list_table->display() ?>
		<input type="hidden" name="page" value="wpsc-purchase-logs" />

		<?php if ( ! $this->list_table->is_pagination_enabled() && $this->list_table->get_pagenum() ):?>
			<input type="hidden" name="last_paged" value="<?php echo esc_attr( $this->list_table->get_pagenum() ); ?>" />
		<?php endif ?>

		<?php if ( ! $this->list_table->is_sortable() && isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ): ?>
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $_REQUEST['orderby'] ); ?>" />
			<input type="hidden" name="order" value="<?php echo esc_attr( $_REQUEST['order'] ); ?>" />
		<?php endif; ?>

		<?php if ( ! $this->list_table->is_search_box_enabled() && isset( $_REQUEST['s'] ) ): ?>
			<input type="hidden" name="s" value="<?php echo esc_attr( $_REQUEST['s'] ); ?>" />
		<?php endif; ?>
		<?php do_action( 'wpsc_purchase_logs_list_table_after' ); ?>
	</form>

</div>