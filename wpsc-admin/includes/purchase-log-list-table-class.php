<?php
 /* The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if(!class_exists('WPSC_Purchase_Log_Table')){
	class WPSC_Purchase_Log_Table extends WP_List_Table {
		//global $wpdb;
		//first we will get our data
		//var $purchase_logs;	
		
		function __construct(){
		
	    global $status, $page, $wpdb;
	        parent::__construct( array(
	        'singular'  => 'Purchase Log',     //singular name of the listed records
	        'plural'    => 'Purchase Logs',    //plural name of the listed records
	        'ajax'      => false        //does this table support ajax?
	    ) );
	    
	}
	
	//the movie stuff will need to come out of here
	 function column_date($item, $column_name){
	    //Build row actions
	    $actions = array(
	        'delete'    => sprintf('<a href="?page=%s&action=%s&post=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
	        'view'    => sprintf('<a href="?page=%s&action=%s&post=%s">View</a>',$_REQUEST['page'],'view',$item['id']),
	        'tracking'    => sprintf('<a href="?page=%s&wpsc_admin_action=%s&movie=%s">Add Tracking #</a>',$_REQUEST['page'],'tracking',$item['ID']),
	
	    );
	    $formated_date = date( 'M d Y,g:i a', $item[$column_name] );
	    
	    //Return the title contents
	    return sprintf('%1$s %2$s',
	        /*$1%s*/ $formated_date,
	        /*$2%s*/ $this->row_actions($actions)
	    );
	}
	
	function column_cb($item){
	    return sprintf(
	        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
	        /*$1%s*/ 'post', 
	        /*$2%s*/ $item['id']               
	    );
	}
	
	function column_email($item, $column_name){
	
		global $wpdb;
		$form_sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE  `unique_name` IN( 'billingemail', 'billingfirstname', 'billinglastname' ) AND `active` = '1'";
		$form_data = $wpdb->get_results( $form_sql, ARRAY_A );
		$purcahse_id = $item['id'];
		
		foreach ( (array)$form_data as $formdata ) {
			if ( in_array( 'billingemail', $formdata ) ) 
				$emailformid = $formdata['id'];
			
			if ( in_array( 'billingfirstname', $formdata ) ) 
				$fNameformid = $formdata['id'];
			
			if ( in_array( 'billinglastname', $formdata ) ) 
				$lNameformid = $formdata['id'];
			
		}
		
		$sql = "SELECT value FROM " . WPSC_TABLE_SUBMITED_FORM_DATA . " WHERE log_id=" . $purcahse_id . " AND form_id=" . $emailformid;
		$email = $wpdb->get_var( $sql );
		
		$sql = "SELECT value FROM " . WPSC_TABLE_SUBMITED_FORM_DATA . " WHERE log_id=" . $purcahse_id . " AND form_id=" . $fNameformid;
		$fname = $wpdb->get_var( $sql );
		
		$sql = "SELECT value FROM " . WPSC_TABLE_SUBMITED_FORM_DATA . " WHERE log_id=" . $purcahse_id . " AND form_id=" . $lNameformid;
		$lname = $wpdb->get_var( $sql );
		
		$namestring = esc_html( $fname ) . ' ' . esc_html( $lname ) . ' (<a href="mailto:' . esc_attr( $email ) . '?subject=Message From ' . get_option( 'siteurl' ) . '">' . esc_html( $email ) . '</a>) ';
		
		if ( $fname == '' && $lname == '' && $email == '' ) {
			$namestring = __('N/A', 'wpsc');
		}
		return $namestring;
	}
	 
	 	       
	     
	/* processed column - this is the order status column */
	function column_processed($item){
		   if(!wpsc_purchlogs_is_google_checkout()){ ?>
		 <img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" class="ajax-loading" alt="" style="position:relative; top:3px;" />
	     <select class='selector' name='<?php echo $item['id']; ?>' title='<?php echo $item['id']; ?>' >
	     <?php while(wpsc_have_purch_items_statuses()) : wpsc_the_purch_status(); ?>
	        <option value='<?php echo wpsc_the_purch_status_id(); ?>' <?php selected( wpsc_the_purch_status_id(), $item['processed'] ); ?> ><?php echo wpsc_the_purch_status_name(); ?> </option>
	     <?php endwhile; ?>
	     </select>
	  <?php }else { ?>
	     <a href='http://checkout.google.com/' rel=''><img class='google_checkout_logo' src='<?php echo WPSC_CORE_IMAGES_URL . "/checkout_logo.jpg"; ?>' alt='google checkout' /></a>
	  <?php }
	
	}
	       
/* 	default colum display - this is for all the columns that don't require special formating */
	function column_default($item, $column_name){
		switch($column_name){
		    case 'id':
		        return $item[$column_name];
		    default:
		        return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
	}
	
	function column_totalprice($item){
		 return wpsc_currency_display( $item['totalprice'], array( 'display_as_html' => false ) );
	}
	
	function get_columns(){
	    $columns = array(
	        'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
	        'id'     => 'ID',
	        'date'     => 'Date',
	        'email'    => '',
	        'totalprice'  => 'Order Total',
	        'processed'  => 'Status',
	    );
	    return $columns;
	}
	
	
		function get_bulk_actions() {
		    $actions = array(
		        'delete'    => 'Delete',
		        '1'    => 'Incomplete Sale',
		        '2'    => 'Order Recieved',
		        '3'    => 'Accepted Payment',
		        '4'    => 'Job dispatched',
		        '5'    => 'Closed Order',
		        '6'    => 'Payment Declined',
		    );
		    return $actions;
		}	
		
		/* 
		This also includes the hover actions 
		would of thought they would be called something else
		*/
		function process_bulk_action() {
			global $wpdb;
			
			if( 'view'===$this->current_action() ) {
				exit('This will be the single view page that i need to think about creating...');
			}

			//Detect when a bulk action is being triggered...
			if( 'delete'===$this->current_action() ) {
				/* this needs some js "are you sure you want to delete this" */
				
				if ( isset($_POST['post']) )
					$post_ids = array_map( 'intval', $_POST['post'] ); // pull out the items that need updating
				
				//if there are no post ids then the id will 
				//be in the url from the hover link
				//if( empty($post_ids) )
				if ( isset( $_GET['post'] ) && $_GET['action'] === 'delete' )
				$post_ids = array(1 => $_GET['post']);
				
				$wpdb->query($wpdb->prepare('DELETE FROM ' . WPSC_TABLE_PURCHASE_LOGS . ' WHERE `id` IN(' . implode(',' , $post_ids ).')'));	
			}
		
			/*
			if numeric then we know we are updating the order status the 
			current_action will be the status number to update
			*/
			if( is_numeric($this->current_action())  ) {
				$post_ids = array_map( 'intval', $_POST['post'] ); // pull out the items that need updating
				$wpdb->query($wpdb->prepare('UPDATE ' . WPSC_TABLE_PURCHASE_LOGS . ' SET `processed` = ' . $this->current_action() . ' WHERE `id` IN(' . implode(',' , $post_ids ).')'));	

			}

		}
		
		/*
		need to figure out how you proess the search
		this just seems to generate the box??
		*/
		function search_box( $text, $input_id ) {
			$text = 'Search Purchase Logs';
			$input_id = $input_id . '-search-input';
			?>
			<p class="search-box">
				<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
				<input type="text" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
				<?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>
			</p>
			<?php
		}

	
	
		function prepare_items() {	
			//Get the columns, hidden and sortable @TODO make some columns sortable
			$columns = $this->get_columns();
			//$hidden = array();
			// $sortable = $this->get_sortable_columns();
			
			
			/**
			 * REQUIRED. Finally, we build an array to be used by the class for column 
			 * headers. The $this->_column_headers property takes an array which contains
			 * 3 other arrays. One for all columns, one for hidden columns, and one
			 * for sortable columns.
			 */
			$this->_column_headers = array($columns, $hidden, $sortable);
			
			$this->process_bulk_action();
		
			
			/* Get out our data for the table order by DESc so we always have the most recent at the top*/
			global $wpdb;
			$data = $wpdb->get_results($wpdb->prepare("SELECT `id`,`date`,`totalprice`, `processed` FROM `" . WPSC_TABLE_PURCHASE_LOGS  ."` ORDER BY `id` DESC"), ARRAY_A);
			
			/*  Pagination options and settings */
			$per_page = 2;
			$current_page = $this->get_pagenum();
			$total_items = count($data);
			/**
			 * The WP_List_Table class does not handle pagination for us, so we need
			 * to ensure that the data is trimmed to only the current page. We can use
			 * array_slice() to 
			 */
			$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
			/**
			 * REQUIRED. Now we can add our *sorted* data to the items property, where 
			 * it can be used by the rest of the class.
			 */
			$this->items = $data;
			
			/* register the pagination and args */
			$this->set_pagination_args( array(
			    'total_items' => $total_items,                  //WE have to calculate the total number of items
			    'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			    'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
			) );
		}
	}
}

 function ttt_add_menu_items(){
    add_menu_page('Example Plugin List Table2', 'New Purchase Logs', 'activate_plugins', 'tt_list_testtt', 'ttt_render_list_page');
} add_action('admin_menu', 'ttt_add_menu_items');


/***************************** RENDER TEST PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function ttt_render_list_page(){
  
    //Create an instance of our package class...
    $test = new WPSC_Purchase_Log_Table();
    //Fetch, prepare, sort, and filter our data...
    $test->prepare_items();
    
    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>List Table Test purch logs</h2>
        

        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="post">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $test->search_box($text, $input_id); ?>
            <?php $test->display() ?>
            
        </form>
        
    </div>
   
    <?php
}  
?>