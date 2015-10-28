<?php

/**
 * Purchase Log Action Links Class
 *
 * Manages and displays a links of action links when editing a puchase log.
 *
 * @package wp-e-commerce
 * @since  3.9.0
 *
 * @link  https://github.com/wp-e-commerce/WP-e-Commerce/pull/1562
 */
class WPSC_Purchase_Log_Action_Links {

	/**
	 * Purchase Log ID.
	 *
	 * @since   3.9.0
	 * @access  private
	 * @var     int
	 */
	protected $log_id;

	/**
	 * An array of WPSC_Purchase_Log_Action_Link objects.
	 *
	 * @since   3.9.0
	 * @access  private
	 * @var     array
	 */
	protected $links;

	/**
	 * Setup all action links.
	 *
	 * @since   3.9.0
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
	 *
	 * @since   3.9.0
	 * @access  private
	 */
	private function _create_links() {

		// Add default links.
		if ( wpsc_purchlogs_have_downloads_locked() != false ) {
			$this->links[] = $this->get_downloads_lock_link();
		}
		$this->links[] = $this->get_packing_slip_link();
		$this->links[] = $this->get_email_receipt_link();

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
	 *
	 * @since   3.9.0
	 * @access  private
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
	 * @since   3.9.0
	 * @access  private
	 *
	 * @todo  Check that the WPSC_Purchase_Log_Action_Link ID is unique and reject if not - maybe not here.
	 *
	 * @param   WPSC_Purchase_Log_Action_Link          $action_link  Action link object.
	 * @return  WPSC_Purchase_Log_Action_Link|boolean                If valid, the action link object, otherwise false.
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
	 * @since   3.9.0
	 * @access  private
	 *
	 * @return  WPSC_Purchase_Log_Action_Link  Instance of an action link object.
	 */
	private function get_downloads_lock_link() {

		return new WPSC_Purchase_Log_Action_Link( 'downloads_lock', wpsc_purchlogs_have_downloads_locked(), $this->log_id, array(
			'ajax'     => true,
			'dashicon' => 'dashicons-lock'
		) );

	}

	/**
	 * View Packing Slip Action Link
	 *
	 * @since   3.9.0
	 * @access  private
	 *
	 * @return  WPSC_Purchase_Log_Action_Link  Instance of an action link object.
	 */
	private function get_packing_slip_link() {

		return new WPSC_Purchase_Log_Action_Link( 'packing_slip', __( 'View Packing Slip', 'wp-e-commerce' ), $this->log_id, array(
			'url'        => esc_url( add_query_arg( array(
				'c'  => 'packing_slip',
				'id' => $this->log_id
			) ) ),
			'dashicon'   => 'dashicons-format-aside',
			'attributes' => array(
				'target' => 'wpsc_packing_slip'
			)
		) );

	}

	/**
	 * Resend Email Receipt Action Link
	 *
	 * @since   3.9.0
	 * @access  private
	 *
	 * @return  WPSC_Purchase_Log_Action_Link  Instance of an action link object.
	 */
	private function get_email_receipt_link() {

		return new WPSC_Purchase_Log_Action_Link( 'email_receipt', __( 'Resend Receipt to Buyer', 'wp-e-commerce' ), $this->log_id, array(
			'ajax'     => true,
			'dashicon' => 'dashicons-migrate dashicons-email-alt'
		) );

	}

	/**
	 * Delete Action Link
	 *
	 * @since   3.9.0
	 * @access  private
	 *
	 * @return  WPSC_Purchase_Log_Action_Link  Instance of an action link object.
	 */
	private function _get_delete_link() {

		return new WPSC_Purchase_Log_Action_Link( 'delete', _x( 'Remove this record', 'purchase log action link', 'wp-e-commerce' ), $this->log_id, array(
			'dashicon'   => 'dashicons-dismiss',
			'attributes' => array(
				'onclick' => "if ( confirm('" . esc_js( sprintf( __( "You are about to delete this log '%s'\n 'Cancel' to stop, 'OK' to delete.", 'wp-e-commerce' ), wpsc_purchaselog_details_date() ) ) . "') ) { return true; } return false;"
			)
		) );

	}

	/**
	 * Back Action Link
	 *
	 * @since   3.9.0
	 * @access  private
	 *
	 * @return  WPSC_Purchase_Log_Action_Link  Instance of an action link object.
	 */
	private function _get_back_link() {

		return new WPSC_Purchase_Log_Action_Link( 'back', _x( 'Go Back', 'purchase log action link', 'wp-e-commerce' ), $this->log_id, array(
			'url'      => wp_get_referer(),
			'dashicon' => 'dashicons-arrow-left-alt'
		) );

	}

	/**
	 * Display Link List Items
	 *
	 * @since  3.9.0
	 *
	 * Outputs action links as a series of list item tags to be included in an HTML list.
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
 *
 * @since  3.9.0
 */
class WPSC_Purchase_Log_Action_Link {

	/**
	 * Action Link ID.
	 *
	 * @since   3.9.0
	 * @access  private
	 * @var     string
	 */
	private $id;

	/**
	 * Action Link Title Text.
	 *
	 * @since   3.9.0
	 * @access  private
	 * @var     string
	 */
	private $title;

	/**
	 * Purchase Log ID.
	 *
	 * @since   3.9.0
	 * @access  private
	 * @var     int
	 */
	private $log_id;

	/**
	 * Action Link Settings.
	 *
	 * @since   3.9.0
	 * @access  private
	 * @var     array
	 */
	private $args;

	/**
	 * Define the action link.
	 *
	 * @since  3.9.0
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
	 * @since   3.9.0
	 * @access  private
	 *
	 * @param   array  $args  Supplied settings.
	 * @return  array         Validated settings.
	 */
	private function _validate_settings( $args ) {

		$args = wp_parse_args( $args, array(
			'url'         => '',
			'description' => '',
			'dashicon'    => '',
			'attributes'  => array(),
			'ajax'        => false
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
		$args['attributes']['class'] = 'wpsc-purchlog-action-link ' . trim( $this->get_html_class() . ' ' . $args['attributes']['class'] );

		// Add AJAX class
		if ( $args['ajax'] ) {
			$args['attributes']['class'] .= ' is-ajax';
		}

		return $args;

	}

	/**
	 * Get HTML Class
	 *
	 * @since  3.9.0
	 *
	 * @return  string  Action link class.
	 */
	public function get_html_class() {

		return 'wpsc-purchlog-action-link-' . sanitize_html_class( $this->id );

	}

	/**
	 * Get Link Display
	 *
	 * @since  3.9.0
	 *
	 * @return  string  HTML action link.
	 */
	public function get_link_display() {

		return sprintf( '<a href="%s" title="%s" %s>%s%s</a>',
			esc_attr( $this->get_link_url() ),
			esc_attr( $this->args['description'] ),
			$this->_get_link_attributes_string(),
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
	 * @since  3.9.0
	 *
	 * @return  string  URL.
	 */
	public function get_link_url() {

		// Custom URL
		if ( ! empty( $this->args['url'] ) ) {
			return $this->args['url'];
		}

		// Callback URL
		$url = add_query_arg( array( 'wpsc_purchase_log_action' => $this->id, 'id' => $this->log_id ) );
		$url = wp_nonce_url( $url, 'wpsc_purchase_log_action_' . $this->id );

		return esc_url( $url );

	}

	/**
	 * Get Link Attributes String
	 *
	 * @since   3.9.0
	 * @access  private
	 *
	 * @return  string  Link attributes HTML.
	 */
	private function _get_link_attributes_string() {

		$atts = array();
		foreach ( $this->args['attributes'] as $att => $val ) {
			$att_key = sanitize_html_class( $att );

			// Don't override attributes that we set elsewhere
			if ( in_array( $att_key, array( 'href', 'title' ) ) ) {
				continue;
			}

			$atts[] = $att_key . '="' . esc_attr( $val ) . '"';
		}

		// Data attributes for JS/AJAX
		$atts[] = 'data-purchase-log-action="' . esc_attr( $this->id ) . '"';
		$atts[] = 'data-nonce="' . esc_attr( wp_create_nonce( 'wpsc_purchase_log_action_ajax_' . $this->id ) ) . '"';

		return implode( ' ', $atts );

	}

	/**
	 * Get Dashicon Display
	 *
	 * @since   3.9.0
	 * @access  private
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
	 * @since   3.9.0
	 * @access  private
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
