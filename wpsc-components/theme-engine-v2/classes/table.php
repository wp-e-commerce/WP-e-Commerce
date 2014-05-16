<?php

class WPSC_Table {
	public $columns = array();
	public $items   = array();

	public function __construct() {
	}

	public function print_column_headers() {
		foreach ( $this->columns as $name => $title ) {
			$class = str_replace( '_', '-', $name );
			echo "<th class='{$class}' scope='col'>" . esc_html( $title ) . "</th>";
		}
	}

	public function display_rows() {

		foreach ( $this->items as $key => $item ) {
			echo '<tr>';
			foreach( array_keys( $this->columns ) as $column ) {
				$class = str_replace( '_', '-', $column );
				echo '<td class="' . $class . '">';
				$callback = "column_{$column}";

				if ( is_callable( array( $this, "column_{$column}") ) ) {
					$this->$callback( $item, $key );
				} else {
					$this->column_default( $item, $key, $column );
				}

				echo '</td>';
			}
			echo '</tr>';
		}
	}

	protected function before_table() {
		// subclass should override this
	}

	protected function after_table() {
		// subclass should override this
	}

	protected function column_default( $item, $key, $column ) {
		// subclass should override this
	}

	protected function get_table_classes() {
		return array( 'wpsc-table' );
	}

	public function display() {
		$this->before_table();
		include( WPSC_TE_V2_SNIPPETS_PATH . '/table-display.php' );
		$this->after_table();
	}
}