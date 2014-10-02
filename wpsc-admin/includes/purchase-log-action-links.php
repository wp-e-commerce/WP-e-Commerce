<?php

/**
 * Purchase Log Action Links Class
 *
 * Manages and displays a links of action links when editing a puchase log.
 */
class WPSC_Purchase_Log_Action_Links {

	/**
	 * Purchase Log ID.
	 * @var  int
	 */
	protected $log_id;

	/**
	 * An array of WPSC_Purchase_Log_Action_Link objects.
	 * @var  array
	 */
	protected $links;

	/**
	 * Setup all action links.
	 *
	 * @param  int  $log_id  Purchase log ID.
	 */
	public function __construct( $log_id ) {

		$this->log_id = absint( $log_id );

		// Create and validate links.
		$this->_create_links();
		$this->_validate_links();

	}

	/**
	 * Create Action Links
	 *
	 * Packing slip and email receipt links are available by default.
	 * Action links are filterable via the 'wpsc_purchlogitem_links' filter which passes the purchase log ID.
	 * Delete and back links are always required and added after the filter.
	 */
	private function _create_links() {

		// Add default links.
		if ( wpsc_purchlogs_have_downloads_locked() != false ) {
			$this->links[] = $this->_get_downloads_lock_link();
		}
		$this->links[] = $this->_get_packing_slip_link();
		$this->links[] = $this->_get_email_receipt_link();

		// Filter action links.
		$this->links = apply_filters( 'wpsc_purchlogitem_links', $this->links, $this->log_id );

		// Add delete and back links.
		$this->links[] = $this->_get_delete_link();
		$this->links[] = $this->_get_back_link();

	}

	/**
	 * Validate Links
	 *
	 * Validates all links are WPSC_Purchase_Log_Action_Link objects.
	 */
	private function _validate_links() {

		$this->links = array_map( array( $this, '_validate_link' ), $this->links );
		$this->links = array_filter( $this->links );

	}

	/**
	 * Validate Link
	 *
	 * Validates a WPSC_Purchase_Log_Action_Link object.
	 *
	 * @todo  Check that the WPSC_Purchase_Log_Action_Link ID is unique and reject if not - maybe not here.
	 *
	 * @param   object       $action_link  WPSC_Purchase_Log_Action_Link object.
	 * @return  object|bool                If valid, the WPSC_Purchase_Log_Action_Link object, otherwise false.
	 */
	private function _validate_link( $action_link ) {

		if ( is_a( $action_link, 'WPSC_Purchase_Log_Action_Link' ) ) {
			return $action_link;
		}

		return false;

	}

	/**
	 * Downloads Lock Action Link
	 *
	 * @return  object  Instance of WPSC_Purchase_Log_Action_Link.
	 */
	private function _get_downloads_lock_link() {

		return new WPSC_Purchase_Log_Action_Link( 'downloads_lock', wpsc_purchlogs_have_downloads_locked(), $this->log_id, array(
			'url'      => esc_url( add_query_arg( 'wpsc_admin_action', 'clear_locks' ) ),
			'dashicon' => 'dashicons-lock'
		) );

	}

	/**
	 * View Packing Slip Action Link
	 *
	 * @return  object  Instance of WPSC_Purchase_Log_Action_Link.
	 */
	private function _get_packing_slip_link() {

		return new WPSC_Purchase_Log_Action_Link( 'packing_slip', __( 'View Packing Slip', 'wpsc' ), $this->log_id, array(
			'url'        => esc_url( add_query_arg( 'c', 'packing_slip' ) ),
			'dashicon'   => 'dashicons-format-aside',
			'attributes' => array(
				'target' => 'wpsc_packing_slip'
			)
		) );

	}

	/**
	 * Resend Email Receipt Action Link
	 *
	 * @return  object  Instance of WPSC_Purchase_Log_Action_Link.
	 */
	private function _get_email_receipt_link() {

		return new WPSC_Purchase_Log_Action_Link( 'email_receipt', __( 'Resend Receipt to Buyer', 'wpsc' ), $this->log_id, array(
			'url'      => esc_url( add_query_arg( 'email_buyer_id', $this->log_id ) ),
			'dashicon' => 'dashicons-migrate dashicons-email-alt'
		) );

	}

	/**
	 * Delete Action Link
	 *
	 * @return  object  Instance of WPSC_Purchase_Log_Action_Link.
	 */
	private function _get_delete_link() {

		return new WPSC_Purchase_Log_Action_Link( 'delete', __( 'Remove this record', 'wpsc' ), $this->log_id, array(
			'url'        => esc_url( wp_nonce_url( add_query_arg( 'purchlog_id', $this->log_id, 'admin.php?wpsc_admin_action=delete_purchlog' ), 'delete_purchlog_' . $this->log_id ) ),
			'dashicon'   => 'dashicons-dismiss',
			'attributes' => array(
				'onclick' => "if ( confirm('" . esc_js( sprintf( __( "You are about to delete this log '%s'\n 'Cancel' to stop, 'OK' to delete.", 'wpsc' ), wpsc_purchaselog_details_date() ) ) . "') ) { return true; } return false;"
			)
		) );

	}

	/**
	 * Back Action Link
	 *
	 * @return  object  Instance of WPSC_Purchase_Log_Action_Link.
	 */
	private function _get_back_link() {

		return new WPSC_Purchase_Log_Action_Link( 'back', __( 'Go Back', 'wpsc' ), $this->log_id, array(
			'url'      => wp_get_referer(),
			'dashicon' => 'dashicons-arrow-left-alt'
		) );

	}

	/**
	 * Display Link List Items
	 *
	 * Outputs action links as a series of <li> tags to be included in an HTML list.
	 */
	public function display_link_list_items() {

		foreach ( $this->links as $link ) {
			echo '<li>' . $link->get_link_display() . '</li>';
		}

	}

}

/**
 * Purchase Log Action Link Class
 *
 * Creates, styles and handles a purchase log action link.
 */
class WPSC_Purchase_Log_Action_Link {

	/**
	 * Action Link ID.
	 * @var  string
	 */
	private $id;

	/**
	 * Action Link Title Text.
	 * @var  string
	 */
	private $title;

	/**
	 * Purchase Log ID.
	 * @var  int
	 */
	private $log_id;

	/**
	 * Action Link Settings.
	 * @var  array
	 */
	private $args;

	/**
	 * Define the action link.
	 *
	 * @param  string  $id     Action link ID (will be sanitized).
	 * @param  string  $title  Link text.
	 * @param  array   $args   Action link settings.
	 */
	public function __construct( $id, $title, $log_id, $args = array() ) {

		$this->id = sanitize_key( $id );
		$this->title = $title;
		$this->log_id = absint( $log_id );
		$this->args = $this->_validate_settings( $args );

	}

	/**
	 * Validate Settings
	 *
	 * Checks settings and adds defaults where required.
	 *
	 * The 'attributes' setting allows additional attributes to be added to the link tag if required.
	 * 'title' and 'href' attributes are removed as these are created via the 'url' and 'description' settings.
	 *
	 * Any class attributes are added to the 'wpsc-purchlog-action-{$id}' class we generate.
	 *
	 * @param   array  $args  Supplied settings.
	 * @return  array         Validated settings.
	 */
	private function _validate_settings( $args ) {

		$args = wp_parse_args( $args, array(
			'url'         => '',
			'description' => '',
			'dashicon'    => '',
			'attributes'  => array()
		) );

		// Use title if no description.
		if ( empty( $args['description'] ) ) {
			$args['description'] = $this->title;
		}

		// Use default arrow dashicon if none specified.
		if ( empty( $args['dashicon'] ) ) {
			$args['dashicon'] = 'dashicons-arrow-right-alt';
		}

		// Remove href and title attributes.
		if ( is_array( $args['attributes'] ) ) {
			if ( array_key_exists( 'title', $args['attributes'] ) ) {
				unset( $args['attributes']['title'] );
			}
			if ( array_key_exists( 'href', $args['attributes'] ) ) {
				unset( $args['attributes']['href'] );
			}
		} else {
			$args['attributes'] = array();
		}

		// Add class and append any extra classes.
		if ( ! array_key_exists( 'class', $args['attributes'] ) ) {
			$args['attributes']['class'] = '';
		}
		$args['attributes']['class'] = trim( $this->get_html_class() . ' ' . $args['attributes']['class'] );

		return $args;

	}

	/**
	 * Get HTML Class
	 *
	 * @return  string  Action link class.
	 */
	public function get_html_class() {

		return 'wpsc-purchlog-action-' . sanitize_html_class( $this->id );

	}

	/**
	 * Get Link Display
	 *
	 * @return  string  HTML action link.
	 */
	public function get_link_display() {

		return sprintf( '<a href="%s" title="%s" %s data-purchase-log-action="%s">%s%s</a></li>',
			esc_attr( $this->get_link_url() ),
			esc_attr( $this->args['description'] ),
			$this->_get_link_attributes_string(),
			$this->id,
			$this->_get_dashicon_display(),
			esc_html( $this->title )
		);

	}

	/**
	 * Get Link URL
	 *
	 * Returns the custom URL if specified.
	 * Otherwise returns a callback URL.
	 *
	 * @return  string  URL.
	 */
	public function get_link_url() {

		// Custom URL
		if ( ! empty( $this->args['url'] ) ) {
			return $this->args['url'];
		}

		// Callback URL
		return add_query_arg( array( 'wpsc_purchase_log_action' => $this->id, 'id' => $this->log_id ) );

	}

	/**
	 * Get Link Attributes String
	 *
	 * @return  string  Link attributes HTML.
	 */
	private function _get_link_attributes_string() {

		$atts = array();
		foreach ( $this->args['attributes'] as $att => $val ) {
			$att_key = sanitize_html_class( $att );

			// Don't override attributes that we set elsewhere
			if ( in_array( $att_key, array( 'href', 'title', 'data-purchase-log-action' ) ) ) {
				continue;
			}

			$atts[] = $att_key . '="' . esc_attr( $val ) . '"';
		}
		return implode( ' ', $atts );

	}

	/**
	 * Get Dashicon Display
	 * 
	 * @return  string  Dashicon HTML element.
	 */
	private function _get_dashicon_display() {

		return '<span class="dashicons ' . $this->_sanitize_html_classes( $this->args['dashicon'] ) . '"></span>';

	}

	/**
	 * Sanitize HTML Classes
	 *
	 * Handles sanitizing multiple classes provided as a string.
	 *
	 * @param   string|array  $classes  Classes.
	 * @return  string                  Santized classes.
	 */
	private function _sanitize_html_classes( $classes ) {

		// Convert multiple classes string to an array.
		if ( ! is_array( $classes ) && strpos( $classes, ' ' ) !== false ) {
			$classes = explode( ' ', $classes );
		}

		// Sanitize and return multiple classes.
		if ( is_array( $classes ) ) {
			$classes = array_map( 'sanitize_html_class', $classes );
			return implode( ' ', $classes );
		}

		// Sanitize single class.
		return sanitize_html_class( $classes );

	}

}
