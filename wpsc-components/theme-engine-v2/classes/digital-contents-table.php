<?php

require_once( WPSC_TE_V2_CLASSES_PATH . '/table.php' );

class WPSC_Digital_Contents_Table extends WPSC_Table {

	public $per_page    = 10;
	public $offset      = 0;
	public $total_items = 0;
	private $digital_items;
	private static $instance;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new WPSC_Digital_Contents_Table();
		}

		return self::$instance;
	}

	public function fetch_items() {
		global $wpdb;

		$vars = array(
			WPSC_Purchase_Log::ACCEPTED_PAYMENT,
			WPSC_Purchase_Log::JOB_DISPATCHED,
			WPSC_Purchase_Log::CLOSED_ORDER,
			get_current_user_id(),
		);

		$sql = $wpdb->prepare( "
			SELECT
				d.*
			FROM " . WPSC_TABLE_DOWNLOAD_STATUS . " AS d
			INNER JOIN " . WPSC_TABLE_PURCHASE_LOGS . " AS p
			ON
				d.purchid = p.id
			WHERE
				d.active = 1 AND
				p.processed IN (%d, %d, %d) AND
				p.user_ID = %d
			ORDER BY p.id DESC
		", $vars );

		$downloadables = $wpdb->get_results( $sql );

		$product_ids = wp_list_pluck( $downloadables, 'product_id' );
		$product_ids = array_unique( array_map( 'absint', $product_ids ) );
		$this->items = get_posts( array(
			'post_type' => 'wpsc-product',
			'post__in'  => $product_ids )
		);
		$this->total_items = count( $this->items );

		$this->digital_items = array();

		foreach ( $downloadables as $file ) {
			if ( ! in_array( $file->product_id, $product_ids ) ) {
				continue;
			}

			if ( ! array_key_exists( $file->product_id, $this->digital_items ) ) {
				$this->digital_items[ $file->product_id ] = array();
			}

			$this->digital_items[ $file->product_id ][] = $file;
		}

		// cache files
		$files = wp_list_pluck( $downloadables, 'fileid' );

		get_posts( array(
			'post_type' => 'wpsc-product-file',
			'post__in'  => $files,
		) );

	}

	public function __construct() {
		parent::__construct();

		$this->columns = array(
			'product'  => _x( 'Product', 'customer account - digital contents - table header', 'wp-e-commerce' ),
			'contents' => _x( 'Digital Contents', 'customer account - digital contents - table header', 'wp-e-commerce' ),
		);
	}

	public function column_product( $item ) {
?>
	<div class="wpsc-thumbnail wpsc-product-thumbnail">
		<?php if ( wpsc_has_product_thumbnail( $item->ID ) ): ?>
			<?php echo wpsc_get_product_thumbnail( $item->ID, 'cart' ); ?>
		<?php else: ?>
			<?php wpsc_product_no_thumbnail_image( 'cart' ); ?>
		<?php endif; ?>
	</div>
	<div class="wpsc-digital-product-title">
		<strong><a href="<?php wpsc_product_permalink( $item->ID ); ?>"><?php wpsc_product_title( '', '', $item->ID ); ?></a></strong>
	</div>
<?php
	}

	public function column_contents( $item ) {

		if ( empty( $this->digital_items ) ) {
			return;
		}

		echo '<div class="wpsc-digital-product-items">';
		echo '<ul>';
		foreach ( $this->digital_items[ $item->ID ] as $file ) {
			echo '<li>';
			$post = get_post( $file->fileid );
			if ( ! $post ) {
				echo '<em class="deleted">' . sprintf( __( 'File ID #%s has been removed.', 'wp-e-commerce' ), $file->id ) . '</em>';
				continue;
			}

			$file_name  = get_the_title( $file->fileid );
			$downloadid = empty( $file->uniqueid ) ? $file->id : $file->uniqueid;
			$url = add_query_arg( 'downloadid', $downloadid, home_url() );

			echo '<a href="' . esc_url( $url ) . '"">' . $file_name . '</a>';

			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
}