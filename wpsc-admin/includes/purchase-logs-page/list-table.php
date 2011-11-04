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

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="purchase-logs-filter" method="post" action="">
		<?php do_action( 'wpsc_purchase_logs_list_table_before' ); ?>
		<!-- For plugins, we also need to ensure that the form posts back to our current page -->
		<!-- Now we can render the completed list table -->

		<?php $this->list_table->display() ?>
		<?php do_action( 'wpsc_purchase_logs_list_table_after' ); ?>
	</form>

</div>