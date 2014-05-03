<?php

class _WPSC_Settings_Tab_Form extends WPSC_Settings_Tab {

	protected $sections     = array();
	protected $form_array   = array();
	protected $extra_fields = array();
	protected $form         = array();

	public function __construct() {
		require_once( WPSC_TE_V2_CLASSES_PATH . '/settings-form.php' );

		$this->form = new WPSC_Settings_Form( $this->sections, $this->form_array, $this->extra_fields );

		add_filter( 'wpsc_settings_page_submit_url', array( $this, '_filter_settings_page_submit_url' ), 10, 2 );
		remove_action( 'wpsc_after_settings_tab'   , '_wpsc_action_after_settings_tab' );
	}

	public function display() {
		$this->form->display();
	}

	public function _filter_settings_page_submit_url( $url, $page_instance ) {
		$url = add_query_arg(
			'tab',
			$page_instance->get_current_tab_id(),
			admin_url( 'options.php' )
		);
		return $url;
	}
}