<?php

class Sputnik_View_Install_Skin extends WP_Upgrader_Skin {
	public $api;
	public $type;

	function __construct($args = array()) {
		$defaults = array( 'type' => 'web', 'url' => '', 'plugin' => '', 'nonce' => '', 'title' => '' );
		$args = wp_parse_args($args, $defaults);

		$this->type = $args['type'];
		$this->api = isset($args['api']) ? $args['api'] : array();

		parent::__construct($args);
	}

	function before() {
		if ( ! empty( $this->api ) ) {
			$asset_type = $this->api->is_theme ? 'theme' : 'plugin';
			$this->upgrader->strings['process_success'] = sprintf( __('Successfully installed the %s <strong>%s %s</strong>.', 'wp-e-commerce' ), $asset_type, $this->api->name, $this->api->version);
		}

		echo '<script>if (window.parent.tb_showIframe) { window.parent.tb_showIframe(); }</script>';
	}

	function after() {

		$plugin_file = $this->upgrader->plugin_info();

		$install_actions = array();

		$from = isset($_GET['from']) ? stripslashes($_GET['from']) : 'plugins';

		// One-Click flow
		if (!empty($_GET['also']) && $_GET['also'] == 'activate') {
			if (!$this->result || is_wp_error($this->result)) {
				show_message(__('Cannot activate plugin.', 'wp-e-commerce'));
			}
			else {
				show_message(__('Activating the plugin&#8230;', 'wp-e-commerce'));
				echo '<iframe style="border:0;overflow:hidden" width="100%" height="170px" src="' . wp_nonce_url('update.php?action=activate-plugin&networkwide=0&plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) .'"></iframe>';
			}
		}
		else {
			$install_actions['activate_plugin'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin', 'wp-e-commerce') . '" target="_parent">' . __('Activate Plugin', 'wp-e-commerce') . '</a>';

			if ( is_multisite() && current_user_can( 'manage_network_plugins' ) ) {
				$install_actions['network_activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;networkwide=1&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin for all sites in this network', 'wp-e-commerce') . '" target="_parent">' . __('Network Activate', 'wp-e-commerce') . '</a>';
				unset( $install_actions['activate_plugin'] );
			}
		}

		$install_actions['store'] = '<a href="' . Sputnik_Admin::build_url() . '" title="' . esc_attr__('Return to Store', 'wp-e-commerce') . '" target="_parent" class="close">' . __('Return to Store', 'wp-e-commerce') . '</a>';


		if (!$this->result || is_wp_error($this->result)) {
			unset( $install_actions['activate_plugin'] );
			unset( $install_actions['network_activate'] );
		}
		$install_actions = apply_filters('install_plugin_complete_actions', $install_actions, $this->api, $plugin_file);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
}