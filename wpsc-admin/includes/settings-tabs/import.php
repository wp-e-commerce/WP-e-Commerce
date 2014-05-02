<?php

class WPSC_Settings_Tab_Import extends WPSC_Settings_Tab {

	private $file           = false;
	private $step           = 1;
	private $display_data   = array();
	private $completed      = false;
	private $default_fields = array();

	public function __construct() {

		parent::__construct();

		$file = get_transient( 'wpsc_settings_tab_import_file' );

		if ( $file ) {
			$this->file = $file;
		}

		$this->step = empty( $_REQUEST['step'] ) ? 1 : (int) $_REQUEST['step'];

		if ( $this->step < 1 || $this->step > 3 ) {
			$this->step = 1;
		}

		$this->default_fields = apply_filters( 'wpsc_product_import_default_fields', array(
			'column_name'                   => __( 'Product Name'          , 'wpsc' ),
			'column_description'            => __( 'Description'           , 'wpsc' ),
			'column_additional_description' => __( 'Additional Description', 'wpsc' ),
			'column_price'                  => __( 'Price'                 , 'wpsc' ),
			'column_sku'                    => __( 'SKU'                   , 'wpsc' ),
			'column_weight'                 => __( 'Weight'                , 'wpsc' ),
			'column_weight_unit'            => __( 'Weight Unit'           , 'wpsc' ),
			'column_quantity'               => __( 'Stock Quantity'        , 'wpsc' ),
			'column_quantity_limited'       => __( 'Stock Quantity Limit'  , 'wpsc' )
		) );

		switch ( $this->step ) {
			case 2:
				$this->prepare_import_columns();
				break;
			case 3:
				$this->import_data();
				break;
		}

		$this->hide_submit_button();
	}

	private function prepare_import_columns() {
		$this->hide_update_message();

		ini_set( 'auto_detect_line_endings', 1 );
		$handle = @fopen( $this->file, 'r' );

		if ( ! $handle ) {
			$this->reset_state();
			return;
		}

		$rows = array();

		while ( count( $rows ) < 5 && ( false !== ( $data = fgetcsv( $handle ) ) ) ) {
        	array_push( $rows, $data );
		}

		$sample_row_data = array();

		foreach ( $rows as $row => $columns ) {
			foreach ( $columns as $column => $data ) {

				if ( ! isset( $sample_row_data[ $column ] ) ) {
					$sample_row_data[ $column ] = array();
				}

				array_push( $sample_row_data[ $column ], $data );
			}
		}

		$categories = get_terms( 'wpsc_product_category', 'hide_empty=0' );

		$this->display_data = array(
			'sample_row_data' => $sample_row_data,
			'categories'      => $categories,
		);
	}

	private function reset_state() {
		delete_transient( 'wpsc_settings_tab_import_file' );
		$this->file         = false;
		$this->completed    = false;
		$this->display_data = array();
	}

	private function import_data() {
		ini_set( 'auto_detect_line_endings', 1 );

		$handle = @fopen( $this->file, 'r' );

		if ( ! $handle ) {
			$this->reset_state();
			return;
		}

		$length = filesize( $this->file );

		$column_map = array_flip( $_POST['value_name'] );

		extract( $column_map, EXTR_SKIP );

		$record_count = 0;

		while ( $row = @fgetcsv( $handle, $length, ',' ) ) {

			$product = array(
				'post_title'             => isset( $row[ $column_name ] )        ? $row[ $column_name ] : '',
				'content'                => isset( $row[ $column_description ] ) ? $row[ $column_description ] : '',
				'price'                  => isset( $row[ $column_price ] )       ? str_replace( '$', '', $row[ $column_price ] ) : 0,
				'weight'                 => isset( $row[ $column_weight ] )      ? $row[ $column_weight] : '',
				'weight_unit'            => isset( $row[ $column_weight_unit ] ) ? $row[ $column_weight_unit ] : '',
				'additional_description' => isset( $row[ $column_additional_description ] ) ? $row[ $column_additional_description ] : '',
				'pnp'                    => null,
				'international_pnp'      => null,
				'file'                   => null,
				'image'                  => '0',
				'quantity_limited'       => isset( $row[ $column_quantity_limited ] ) ? $row[ $column_quantity_limited ] : '',
				'quantity'               => isset( $row[ $column_quantity ] )         ? $row[ $column_quantity ] : null,
				'special'                => null,
				'special_price'          => null,
				'display_frontpage'      => null,
				'notax'                  => null,
				'active'                 => null,
				'donation'               => null,
				'no_shipping'            => null,
				'thumbnail_image'        => null,
				'thumbnail_state'        => null,
				'meta'                   => array(
					'_wpsc_price'            => isset( $row[$column_price] ) ? str_replace( '$', '', $row[$column_price] ) : 0,
					'_wpsc_special_price'    => '',
					'_wpsc_sku'              => isset( $row[$column_sku] ) ? $row[$column_sku] : '',
					'_wpsc_stock'            => isset( $row[$column_quantity] ) ? $row[$column_quantity] : null,
					'_wpsc_limited_stock'    => isset( $row[$column_quantity_limited] ) ? $row[$column_quantity_limited] : '',
					'_wpsc_product_metadata' => array(
						'weight'      => isset( $row[$column_weight] ) ? $row[$column_weight] : '',
						'weight_unit' => isset( $row[$column_weight_unit] ) ? $row[$column_weight_unit] : '',
					)
				)
			);

			$product = apply_filters( 'wpsc_product_import_row', $product, $row, $this );

			if ( empty( $product['post_title'] ) && apply_filters( 'wpsc_product_import_require_title', true, $product, $row, $this ) ) {
				continue;
			}

			$product = wpsc_sanitise_product_forms( $product );

			// status needs to be set here because wpsc_sanitise_product_forms overwrites it :/
			$product['post_status'] = $_POST['post_status'];

			$product_id = wpsc_insert_product( $product );

			if ( (int) $_POST['category'] > 0 ) {
				wp_set_object_terms( $product_id , array( (int) $_POST['category'] ) , 'wpsc_product_category' );
			}

			$record_count += 1;
		}

		$this->reset_state();
		$this->completed = true;
		add_settings_error( 'wpsc-settings', 'settings_updated', sprintf( __( 'CSV file successfully processed. %s record(s) imported.', 'wpsc' ), $record_count ), 'updated' );
	}

	public function callback_submit_options() {
		if ( isset( $_FILES['csv_file'] ) && isset( $_FILES['csv_file']['name'] ) && ($_FILES['csv_file']['name'] != '') ) {
			$this->hide_update_message();
			ini_set( 'auto_detect_line_endings', 1 );
			$file = $_FILES['csv_file'];
			$file_path = WPSC_FILE_DIR . $file['name'];
			if ( move_uploaded_file( $file['tmp_name'], WPSC_FILE_DIR . $file['name'] ) ) {
				set_transient( 'wpsc_settings_tab_import_file', $file_path );
				return array( 'step' => 2 );
			}
		}

		if ( $this->completed ) {
			return array( 'step' => 1 );
		}

		return array( 'step' => $this->step + 1 );
	}

	private function display_imported_columns() {
		extract( $this->display_data );
		?>
			<h3 class='hndle'><?php esc_html_e( 'Assign CSV Columns to Product Fields', 'wpsc'); ?></h3>
			<p><?php esc_html_e( 'For each column, select the field it corresponds to in \'Product Field\'.', 'wpsc' ); ?></p>
			<p><?php esc_html_e( 'Note: In this view we only show sample data from the first 5 records. All records in the uploaded import file will actually be imported.', 'wpsc' ); ?></p>
			<table class='wp-list-table widefat plugins' id="wpsc_imported_columns">
				<thead>
					<tr>
						<th scope="col" class="manage-column"><?php _e( 'Column', 'wpsc' ); ?></th>
						<th scope="col" class="manage-column"><?php _e( 'Sample Data from Column', 'wpsc' ); ?></th>
						<th scope="col" class="manage-column"><?php _e( 'Product Field', 'wpsc' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sample_row_data as $key => $sample_data ) : ?>
						<tr>
							<td>
								<p><?php printf( __('Column %s', 'wpsc' ), $this->num_to_alphacolumn( $key ) ); ?></p>
							</td>
							<td>
								<ol>
								<?php foreach ($sample_data as $datum) : ?>
									<li>
										<?php if ( $datum != "" ): ?>
											<code><?php echo esc_html( $datum ); ?></code>
										<?php else: ?>
											<?php _e( '<em class="empty">empty</em>', 'wpsc' ); ?>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
								</ol>
							</td>
							<td>
								<p>
									<select name='value_name[<?php echo $key; ?>]'>
										<?php
											$i = 0;
											foreach ( $this->default_fields as $value => $label ) :
										?>
											<option <?php selected( $key, $i ); ?> value='<?php echo esc_attr( $value ); ?>'><?php echo esc_html( $label ); ?></option>
										<?php
											$i++;
											endforeach;
										?>
									</select>
								</p>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<h3><?php esc_html_e( 'Import Options', 'wpsc' ); ?></h3>
			<table class='form-table'>
				<tr>
					<th>
						<label for='post_status'><?php esc_html_e( 'Product Status' , 'wpsc' ); ?>
					</th>
					<td>
						<select name='post_status' id='post_status'>
							<option value='publish'><?php esc_html_e( 'Publish', 'wpsc' ); ?></option>
							<option value='draft'  ><?php esc_html_e( 'Draft'  , 'wpsc' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Set your imported products as drafts, or publish them right away.' , 'wpsc' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th><label for="category"><?php esc_html_e( 'Import to Category', 'wpsc' ); ?></label></th>
					<td>
						<select id='category' name='category'>
							<option value=""><?php esc_html_e( "No Category", 'wpsc' ); ?></option>
							<?php foreach ( $categories as $category ): ?>
								<option value="<?php echo $category->term_id; ?>"><?php echo esc_html( $category->name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Products imported from this CSV file will be placed in the selected category.', 'wpsc' ); ?></p>
						</p>
					</td>
				</tr>
			</table>
			<input type="hidden" name="step" value="3" />
			<input type='submit' value='<?php echo esc_html_x( 'Import Products', 'import csv', 'wpsc' ); ?>' class='button-primary'>
		<?php
	}

	private function num_to_alphacolumn($n) {
		// from http://stackoverflow.com/questions/3302857/algorithm-to-get-the-excel-like-column-name-of-a-number

    	for( $r = ""; $n >= 0; $n = intval( $n / 26 ) - 1 ) {
        	$r = chr( $n % 26 + 0x41) . $r;
    	}

    	return $r;
	}

	private function display_default() {
		extract( $this->display_data );
		?>
			<h3><?php _e( 'Import Products', 'wpsc' ); ?></h3>
			<p><?php _e( 'You can import your products from a <a href="http://en.wikipedia.org/wiki/Comma-separated_values"><abbr title="Comma-separated values">CSV</abbr> (Comma-separated values) file</a>, exportable from most spread-sheet programs or other software.</p>', 'wpsc' ); ?></p>

			<h4><?php _e( 'Import New Products from CSV', 'wpsc' ); ?></h4>
			<table class='form-table'>
				<tr>
					<th><label for='wpsc_csv_file'><?php _e( 'CSV File', 'wpsc' ); ?><label></th>
					<td>
						<input type='file' name='csv_file' id='wpsc_csv_file' />
					</td>
				</tr>
			</table>

			<?php submit_button( esc_html_x( 'Upload', 'import csv', 'wpsc' ) ); ?>

			<h4><?php _e( 'Useful Information', 'wpsc' ); ?></h4>
			<table class='form-table'>
				<tr>
					<th><?php echo _e( 'Supported Fields', 'wpsc' ); ?></th>
					<td>
						<?php _e( 'Columns supported are, in their default order:', 'wpsc'); ?><br />
						<code>
							<?php echo implode( ', ', $this->default_fields ); ?>
						</code>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Understood Weight Units', 'wpsc' ); ?></th>
					<td>
						<?php _e( 'Metric', 'wpsc' ); ?>: <code>kilogram</code>,<code>kilograms</code>,<code>kg</code>,<code>kgs</code>,<code>gram</code>,<code>grams</code>,<code>g</code>,<code>gs</code><br />
						<?php _e( 'Imperial', 'wpsc' ); ?>: <code>ounce</code>,<code>ounces</code>,<code>oz</code>,<code>pound</code>,<code>pounds</code>,<code>lb</code>,<code>lbs</code>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Stock Fields', 'wpsc' ); ?></th>
					<td>
						<?php _e( '<code>Stock Quantity</code> values are used only when <code>Stock Quantity Limited</code> is blank or <code>""</code>.', 'wpsc' ); ?>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'HTML', 'wpsc' ); ?></th>
					<td>
						<?php _e( 'Supported in <code>Description</code> and <code>Additional Description</code>. Be sure you "quote" the whole description, and slash-escape \"quotes\" inside the description itself.', 'wpsc' ); ?>
				</tr>
				<tr>
					<th><?php _e( 'Example CSV File'); ?></th>
					<td>
						<ol>
							<li><code><?php esc_html_e( 'Banana, The Yellow Fruit, Contains Potassium, 0.67, "BANANA", 150, "g", 0, ""', 'wpsc' ); ?></code></li>
							<li><code><?php esc_html_e( '"Apple, red", "Red, round, juicy. Isn\'t an <a href=\"http://example.com\">orange</a>.", "Red Delicious", 0.25, "RED_DELICIOUS", 5, "oz", 10, true', 'wpsc' ); ?></code></li>
						</ol>
					</td>
				</tr>
			</table>

		<?php
	}

	public function display() {
		switch ( $this->step ) {
			case 1:
				$this->display_default();
				break;
			case 2:
				$this->display_imported_columns();
				break;
			default:
				$this->display_default();
				break;
		}
	}
}