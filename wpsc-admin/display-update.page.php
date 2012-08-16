<?php
/**
 * WP eCommerce database updating page functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.8
 */

global $wpdb,$wp_version;
$show_update_page = 1;

// if there's nothing in the children variation cache, refresh it, just to make sure.
if ( 0 == count( get_option( 'wpsc-variation_children' ) ) ) {
	delete_option( 'wpsc-variation_children' );
	_get_term_hierarchy( 'wpsc-variation' );
}

// if there's nothing in the children variation cache, refresh it, just to make sure.
if ( 0 == count( get_option( 'wpsc_product_category_children' ) ) ) {
	delete_option( 'wpsc_product_category_children' );
	_get_term_hierarchy( 'wpsc_product_category_children' );
}

$wpsc_version = get_option( 'wpsc_version', '0' );

// If database is already updated, then no need to update
if ( ! get_option( 'wpsc_needs_update', false ) ) {
	$show_update_page = 0;
} else {

	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '" . WPSC_TABLE_PRODUCT_LIST . "'" );
	$product_count = empty( $table_exists ) ? 0 : $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PRODUCT_LIST );

	if ( $product_count > 0 ) {

		function wpsc_display_update_notice() {
			echo "<div id='wpsc-warning' class='error fade'><p><strong>" . __( 'WP e-Commerce is almost ready.', 'wpsc' ) . "</strong> " . sprintf( __( 'You must <a href="%1$s">update your database</a> to import all of your products.', 'wpsc' ), "admin.php?page=wpsc-update") . "</p></div>";
		}

		if ( ! isset( $_GET['page'] ) || $_GET['page'] != 'wpsc-update' )
			add_action( 'admin_notices', 'wpsc_display_update_notice' );

	// There weren't any products, so mark the update as complete
	} else {
		update_option( 'wpsc_version', WPSC_VERSION );
	}
}

if ( version_compare( PHP_VERSION, '5.0.0', '<' ) ) {
	add_action( 'admin_notices', 'wpsc_display_php_version_notice' );
}

function wpsc_display_php_version_notice() {
?>
	<div id='wpsc-warning' class='error fade'><p><?php printf( __( 'You are using PHP %s. WP e-Commerce %s requires PHP 5.0 or above. Please contact your hosting provider for further assistance.', 'wpsc' ), PHP_VERSION, WPSC_VERSION ); ?></p></div>
<?php
}

function wpsc_display_update_page() {
	global $wpdb;

	?>

	<div class="wrap">
		<h2><?php esc_html_e( 'Update WP e-Commerce', 'wpsc' ); ?> </h2>
		<br />
	<?php
		if ( isset( $_REQUEST['run_updates'] ) ) :
			ob_implicit_flush( true );
			$wpsc_update = WPSC_Update::get_instance();
			$update_stages = array(
				'convert_category_groups'        => __( 'Updating Categories...'    , 'wpsc' ),
				'convert_variation_sets'         => __( 'Updating Variations...'    , 'wpsc' ),
				'convert_products_to_posts'      => __( 'Updating Products ...'     , 'wpsc' ),
				'convert_variation_combinations' => __( 'Updating Child Products...', 'wpsc' ),
				'update_files'                   => __( 'Updating Product Files...' , 'wpsc' ),
				'update_purchase_logs'           => __( 'Updating Purchase Logs... ', 'wpsc' ),
				'create_or_update_tables'        => __( 'Updating Database...'      , 'wpsc' ),
				'update_database'                => '',
			);

			foreach ( $update_stages as $function => $message ) {
				$wpsc_update->run( $function, $message );
			}

			echo '<br /><br /><strong>' . esc_html__( 'WP e-Commerce updated successfully!', 'wpsc' ) . '</strong><br />';
			if( '' != get_option('permalink_structure')){ ?>
				<em><?php echo esc_html( sprintf( __( 'Note: It looks like you have custom permalinks, you will need to refresh your permalinks <a href="%s">here</a>', 'wpsc' ) , admin_url( 'options-permalink.php' ) ) ); ?></em>
			<?php
			}
			update_option('wpsc_version', 3.8);
			update_option('wpsc_hide_update', true);
			update_option( 'wpsc_needs_update', false );
			$wpsc_update->clean_up();
			ob_implicit_flush( false );
		else:


		esc_html_e( 'Your WP e-Commerce database needs to be updated for WP e-Commerce 3.8.  To perform this update, press the button below.  It is highly recommended that you back up your database before performing this update.', 'wpsc' );
?>		<br />
		<br />
		<em><?php esc_html_e( 'Note: If the server times out or runs out of memory, just reload this page, the server will pick up where it left off.', 'wpsc' ); ?></em>
		<br />

		<form action="" method="post" id="setup">
			<input type="hidden" name="run_updates" value="true" id="run_updates">
			<p class="step"><input type="submit" class="button" value="<?php esc_attr_e( 'Update WP e-Commerce', 'wpsc' ); ?>" name="Submit"></p>
		</form>
	<?php
		endif;
	?>
	</div>

<?php
}

?>
