<?php

class WPSC_Purchase_Log_Notes extends WPSC_Query_Base implements Iterator {
	const TYPE_DEFAULT   = 0;
	const TYPE_ERROR     = 1;
	const STATUS_PUBLIC  = 0;
	const STATUS_PRIVATE = 1;
	const KEY_CONTENT = 0;
	const KEY_STATUS  = 1;
	const KEY_TIME    = 2;
	const KEY_TYPE    = 3;

	protected static $map_types = array(
		self::TYPE_DEFAULT   => 'default',
		self::TYPE_ERROR     => 'error',
	);

	protected static $map_statuses = array(
		self::STATUS_PUBLIC  => 'public',
		self::STATUS_PRIVATE => 'private',
	);

	protected static $map_keys = array(
		self::KEY_TYPE       => 'type',
		self::KEY_STATUS     => 'status',
		self::KEY_TIME       => 'time',
		self::KEY_CONTENT    => 'content',
	);

	protected static $map_text = array();
	protected $log = null;

	public function __construct( $log ) {
		if ( $log instanceof WPSC_Purchase_Log ) {
			$this->log = $log;
		} else {
			$this->log = wpsc_get_order( $log );
		}

		if ( empty( self::$map_text ) ) {
			self::$map_text = array(
				self::$map_types[ self::TYPE_DEFAULT ]      => __( 'Default', 'wp-e-commerce' ),
				self::$map_types[ self::TYPE_ERROR ]        => __( 'Error', 'wp-e-commerce' ),
				self::$map_statuses[ self::STATUS_PUBLIC ]  => __( 'Public', 'wp-e-commerce' ),
				self::$map_statuses[ self::STATUS_PRIVATE ] => __( 'Private', 'wp-e-commerce' ),
				self::$map_keys[ self::KEY_TYPE ]           => __( 'Note Type', 'wp-e-commerce' ),
				self::$map_keys[ self::KEY_STATUS ]         => __( 'Note Status', 'wp-e-commerce' ),
				self::$map_keys[ self::KEY_TIME ]           => __( 'Note Date', 'wp-e-commerce' ),
				self::$map_keys[ self::KEY_CONTENT ]        => __( 'Note Content', 'wp-e-commerce' ),
			);
		}
	}

	/**
	 * Fetches the actual record from the database
	 *
	 * @access protected
	 * @since 3.8.9
	 *
	 * @return WPSC_Purchase_Log
	 */
	protected function fetch() {

		if ( $this->fetched ) {
			return;
		}

		$this->fetched = true;

		$notes = array();
		$data  = $this->log->get_data();
		$meta  = $this->log->get_meta();

		if ( ! empty( $meta['notes'] ) ) {
			$notes = $meta['notes'];
		}

		if ( ! empty( $data['notes'] ) && ! is_array( $data['notes'] ) ) {
			$notes[] = array( self::KEY_CONTENT => $data['notes'] );
		}

		if ( ! empty( $notes ) ) {

			foreach ( $notes as $key => $args ) {
				// Ensure array is formatted properly
				$notes[ $key ] = self::parse_args_for_db( $args );
			}

			// Make keys and values human-readable.
			foreach ( $notes as $key => $args ) {
				foreach ( $args as $subkey => $value ) {
					$subkey = self::$map_keys[ $subkey ];
					switch ( $subkey ) {
						case 'type':
							$value = self::$map_types[ $value ];
							break;

						case 'status':
							$value = self::$map_statuses[ $value ];
							break;
					}
					$this->data[ $key ][ $subkey ] = $value;
				}
			}
		}

		usort( $this->data, array( $this, 'time_sort' ) );

		$this->exists = ! empty( $this->data );

		return $this;
	}

	public function time_sort( $a, $b ) {
		return $a['time'] < $b['time'];
	}

	/**
	 * Prepares the return value for get() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @param  mixed  $value Value fetched
	 * @param  string $key   Key for $data.
	 *
	 * @return mixed
	 */
	protected function prepare_get( $value, $key ) {
		return apply_filters( 'wpsc_get_purchase_log_note', $value, $key, $this );
	}

	/**
	 * Prepares the return value for get_data() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	protected function prepare_get_data() {
		return apply_filters( 'wpsc_get_purchase_log_notes', $this->data, $this );
	}

	/**
	 * Add a note to the log.
	 *
	 * @since 3.11.5
	 *
	 * @param mixed $note_args String to add note. Optionally Accepts an array to specify note attributes: {
	 *    @type string $type    The note type. Defaults to 'default', but can be 'error'.
	 *    @type string $status  The note status. Defaults to 'public'.
	 *    @type int    $time    The note timestamp. Defaults to time().
	 *    @type string $content The note text.
	 * }
	 */
	public function add( $note_args ) {
		if ( ! is_array( $note_args ) ) {
			$note_args = self::parse_args( array( self::$map_keys[ self::KEY_CONTENT ] => $note_args ) );
		}

		return $this->set( false, $note_args );
	}

	/**
	 * Remove a note from the log by the note_id (or index).
	 *
	 * @since  3.11.5
	 *
	 * @param  int $note_id    Note index.
	 *
	 * @return WPSC_Query_Base The current object (for method chaining)
	 */
	public function remove( $note_id ) {
		$this->fetch();
		unset( $this->data[ $note_id ] );

		return $this;
	}

	/**
	 * Sets a property to a certain value. This function accepts a key and a value
	 * as arguments, or an associative array containing key value pairs.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @param mixed $key             Name of the property (column), or an array containing
	 *                               key value pairs
	 * @param string|int|null $value Optional. Defaults to null. In case $key is a string,
	 *                               this should be specified.
	 * @return WPSC_Query_Base       The current object (for method chaining)
	 */
	public function set( $key, $value = null ) {
		$this->fetch();

		if ( is_array( $key ) ) {
			$this->data = array_map( array( __CLASS__, 'parse_args' ), $key );
		} else {
			if ( is_null( $value ) ) {
				return $this;
			}

			if ( is_numeric( $key ) ) {
				$this->data[ $key ] = self::parse_args( $value );
			} else {
				$this->data[] = self::parse_args( $value );
			}
		}

		$this->data = apply_filters( 'wpsc_set_purchase_log_notes', $this->data, $this );

		return $this;
	}

	/**
	 * Saves the object back to the database.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	public function save() {
		global $wpdb;

		$this->fetch();
		$this->data = apply_filters( 'wpsc_purchase_log_notes_to_save', $this->data, $this );

		$meta = $this->log->get_meta();

		$deleted = array();

		if ( ! empty( $meta['notes'] ) ) {
			foreach ( $meta['notes'] as $key => $note ) {
				$deleted[] = wpsc_delete_purchase_meta( $this->log->get( 'id' ), 'notes', $note );
			}
		}

		$notes = array();

		$data = $this->log->get_data();

		if ( ! empty( $data['notes'] ) ) {
			$this->log->set( 'notes', '' )->save();
		}

		if ( ! empty( $this->data ) ) {

			foreach ( $this->data as $key => $args ) {
				foreach ( $args as $subkey => $value ) {
					switch ( $subkey ) {
						case 'type':
							$value = array_search( $value, self::$map_types );
							break;

						case 'status':
							$value = array_search( $value, self::$map_statuses );
							break;
					}

					$subkey = array_search( $subkey, self::$map_keys );

					$notes[ $key ][ $subkey ] = $value;
				}
			}

			foreach ( $notes as $note ) {
				wpsc_add_purchase_meta( $this->log->get( 'id' ), 'notes', $note );
			}
		}

		$this->log->set_meta( 'notes', $notes );

		do_action( 'wpsc_purchase_log_notes_save', $this );

		return $this;
	}

	/**
	 * Merge arguments into defaults array.
	 *
	 * @since 3.11.5
	 *
	 * @param array $args Value to merge with defaults.
	 * @return array Merged arguments with defaults.
	 */
	public static function parse_args_for_db( array $args ) {
		return $args + array(
			self::KEY_TYPE    => self::TYPE_DEFAULT,
			self::KEY_STATUS  => self::STATUS_PUBLIC,
			self::KEY_TIME    => time(),
			self::KEY_CONTENT => '',
		);
	}

	/**
	 * Merge arguments into defaults array.
	 *
	 * @since 3.11.5
	 *
	 * @param array $args Value to merge with defaults.
	 * @return array Merged arguments with defaults.
	 */
	public static function parse_args( array $args ) {
		return $args + array(
			self::$map_keys[ self::KEY_TYPE ]    => self::$map_types[ self::TYPE_DEFAULT ],
			self::$map_keys[ self::KEY_STATUS ]  => self::$map_statuses[ self::STATUS_PUBLIC ],
			self::$map_keys[ self::KEY_TIME ]    => time(),
			self::$map_keys[ self::KEY_CONTENT ] => '',
		);
	}

	/**
	 * Get current for Iterator.
	 *
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	public function current() {
		$this->fetch();
		return current( $this->data );
	}

	/**
	 * Get key for Iterator.
	 *
	 * @since  3.11.5
	 *
	 * @return scalar
	 */
	public function key() {
		$this->fetch();
		return key( $this->data );
	}

	/**
	 * Get next for Iterator.
	 *
	 * @since  3.11.5
	 *
	 * @return void
	 */
	public function next() {
		$this->fetch();
		return next( $this->data );
	}

	/**
	 * Get prev for Iterator.
	 *
	 * @since  3.11.5
	 *
	 * @return void
	 */
	public function prev() {
		$this->fetch();
		return prev( $this->data );
	}

	/**
	 * Get rewind for Iterator.
	 *
	 * @since  3.11.5
	 *
	 * @return void
	 */
	public function rewind() {
		$this->fetch();
		return reset( $this->data );
	}

	/**
	 * Get valid for Iterator.
	 *
	 * @since  3.11.5
	 *
	 * @return boolean
	 */
	public function valid() {
		$this->fetch();
		return isset( $this->data[ $this->key() ] );
	}

	public function get_status_text( $note = array() ) {
		if ( empty( $note ) ) {
			$note = $this->current();
		}

		if ( ! isset( self::$map_text[ $note['status'] ] ) ) {
			return '';
		}

		return sprintf( __( 'Status: <b>%1$s</b>', 'wp-e-commerce' ), self::$map_text[ $note['status'] ] );
	}

	public function get_formatted_date( $note = array() ) {
		if ( empty( $note ) ) {
			$note = $this->current();
		}

		if ( empty( $note['time'] ) ) {
			return '';
		}

		/* translators: Purchase log motes metabox date format, see https://secure.php.net/date */
		$date = date_i18n( __( 'M j, Y @ H:i', 'wp-e-commerce' ), $note['time'] );

		return $date;
		return sprintf( __( 'Posted: <b>%1$s</b>', 'wp-e-commerce' ), $date );
	}

}
