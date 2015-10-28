<?php
/**
 * Plugin information view
 *
 * @package Sputnik
 * @subpackage Admin View
 */

/**
 * Plugin information view
 *
 * @package Sputnik
 * @subpackage Admin View
 */
class Sputnik_View_Info extends Sputnik_View_Mini {
	protected $body_id = 'sputnik-plugin-information';

	protected $plugin;
	protected $api;

	public function __construct() {
		parent::__construct( __('Plugin Information', 'wp-e-commerce') );
		$this->plugin = $_GET['info'];

		try {
			$account = Sputnik::get_account();
			$this->api = Sputnik::get_plugin($this->plugin, $account->ID);
		}
		catch (Exception $e) {
			status_header(500);
			iframe_header( __('', 'wp-e-commerce') );
			echo $e->getMessage();
			iframe_footer();
			die();
		}
	}
	public function display() {
		global $tab;
		require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

		$api = $this->api;

		$plugins_allowedtags = array('a' => array('href' => array(), 'title' => array(), 'target' => array()),
									'abbr' => array('title' => array()), 'acronym' => array('title' => array()),
									'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
									'div' => array(), 'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
									'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
									'img' => array('src' => array(), 'class' => array(), 'alt' => array()));

		$plugins_section_titles = array(
			'description'  => _x('Description',  'Plugin installer section title', 'wp-e-commerce'),
			'installation' => _x('Installation', 'Plugin installer section title', 'wp-e-commerce'),
			'faq'          => _x('FAQ',          'Plugin installer section title', 'wp-e-commerce'),
			'screenshots'  => _x('Screenshots',  'Plugin installer section title', 'wp-e-commerce'),
			'changelog'    => _x('Changelog',    'Plugin installer section title', 'wp-e-commerce'),
			'other_notes'  => _x('Other Notes',  'Plugin installer section title', 'wp-e-commerce')
		);
		//Sanitize HTML
		$api->sections = (array) $api->sections;
		$api->author = links_add_target($api->author, '_blank');
		foreach ( $api->sections as $section_name => $content )
			$api->sections[$section_name] = wp_kses($content, $plugins_allowedtags);

		$api->screenshots = (array) $api->screenshots;
		foreach ( $api->screenshots as &$data ) {
			if (!isset($data->caption) || !isset($data->location)) {
				continue;
			}

			$data->caption = wp_kses($data->caption, $plugins_allowedtags);
			$data->location = esc_url($data->location, array('http', 'https'));
		}
		unset($data);

		foreach ( array( 'version', 'requires', 'tested', 'homepage', 'downloaded', 'slug' ) as $key ) {
			if ( isset( $api->$key ) )
				$api->$key = wp_kses( $api->$key, $plugins_allowedtags );
		}

		$section = isset($_REQUEST['section']) ? stripslashes( $_REQUEST['section'] ) : 'description'; //Default to the Description tab, Do not translate, API returns English.
		if ( empty($section) || (!isset($api->sections[ $section ]) && ($section !== 'screenshots' || empty($api->screenshots)))  )
			$section = array_shift( $section_titles = array_keys((array)$api->sections) );

?>
		<div class="alignleft fyi">
			<h1><?php echo $api->name ?></h1>
			<?php if ( ! empty($api->download_link) && ( current_user_can('install_plugins') || current_user_can('update_plugins') ) ) : ?>
			<p class="action-button">
<?php
			$status = Sputnik_Admin::install_status($api);
			switch ( $status['status'] ) {
				case 'purchase':
				default:
					if ( $status['url'] )
						echo '<a href="' . $status['url'] . '" target="_parent" class="button-primary buy">' . sprintf(__('<span>$%.2f</span> Buy &amp; Install', 'wp-e-commerce'), $api->price) . '</a>';
					break;
				case 'install':
					if ( $status['url'] )
						echo '<a href="' . $status['url'] . '" class="button-primary install" title="' . __('You have already purchased, install now', 'wp-e-commerce') . '">' . __('Install Now', 'wp-e-commerce') . '</a>';
					break;
				case 'update_available':
					if ( $status['url'] )
						echo '<a href="' . $status['url'] . '" class="button-primary install">' . __('Install Update Now', 'wp-e-commerce') .'</a>';
					break;
				case 'newer_installed':
					echo '<a>' . sprintf(__('Newer Version (%s) Installed', 'wp-e-commerce'), $status['version']) . '</a>';
					break;
				case 'latest_installed':
					echo '<a>' . __('Latest Version Installed', 'wp-e-commerce') . '</a>';
					break;
			}
?>
			</p>
			<?php endif; ?>
<?php
		echo "<div id='plugin-information-header'>\n";
		echo "<ul id='sidemenu'>\n";
		foreach ( (array)$api->sections as $section_name => $content ) {
			if ( isset( $plugins_section_titles[ $section_name ] ) )
				$title = $plugins_section_titles[ $section_name ];
			else
				$title = ucwords( str_replace( '_', ' ', $section_name ) );

			$class = ( $section_name == $section ) ? ' class="current"' : '';
			$href = add_query_arg( array('tab' => $tab, 'section' => $section_name) );
			$href = esc_url($href);
			$san_section = esc_attr($section_name);
			echo "\t<li><a name='$san_section' href='$href'$class>$title</a></li>\n";
		}
		if (!empty($api->screenshots)) {
			$title = $plugins_section_titles['screenshots'];
			$class = ( 'screenshots' == $section ) ? ' class="current"' : '';
			$href = add_query_arg( array('tab' => $tab, 'section' => 'screenshots') );
			$href = esc_url($href);
			echo "\t<li><a name='screenshots' href='$href'$class>$title</a></li>\n";
		}
		echo "</ul>\n";
		echo "</div>\n";
?>
			<h2 class="mainheader"><?php /* translators: For Your Information */ _e('FYI', 'wp-e-commerce') ?></h2>
			<ul>
	<?php if ( ! empty($api->version) ) : ?>
				<li><strong><?php _e('Version:', 'wp-e-commerce') ?></strong> <?php echo $api->version ?></li>
	<?php endif; if ( ! empty($api->author) ) : ?>
				<li><strong><?php _e('Author:', 'wp-e-commerce') ?></strong> <?php echo $api->author ?></li>
	<?php endif; if ( ! empty($api->last_updated) ) : ?>
				<li><strong><?php _e('Last Updated:', 'wp-e-commerce') ?></strong> <span title="<?php echo $api->last_updated ?>"><?php
								printf( __('%s ago', 'wp-e-commerce'), human_time_diff(strtotime($api->last_updated)) ) ?></span></li>
	<?php endif; if ( ! empty($api->requires) ) : ?>
				<li><strong><?php _e('Requires WordPress Version:', 'wp-e-commerce') ?></strong> <?php printf(__('%s or higher', 'wp-e-commerce'), $api->requires) ?></li>
	<?php endif; if ( ! empty($api->tested) ) : ?>
				<li><strong><?php _e('Compatible up to:', 'wp-e-commerce') ?></strong> <?php echo $api->tested ?></li>
	<?php endif; if ( ! empty($api->downloaded) ) : ?>
				<li><strong><?php _e('Downloaded:', 'wp-e-commerce') ?></strong> <?php printf(_n('%s time', '%s times', $api->downloaded, 'wp-e-commerce'), number_format_i18n($api->downloaded)) ?></li>
	<?php endif; if ( ! empty($api->homepage) ) : ?>
				<li><a target="_blank" href="<?php echo $api->homepage ?>"><?php _e('Plugin Homepage  &#187;', 'wp-e-commerce') ?></a></li>
	<?php endif; ?>
			</ul>

		</div>
		<div id="section-holder" class="wrap">
		<?php
			if ( !empty($api->tested) && version_compare( substr($GLOBALS['wp_version'], 0, strlen($api->tested)), $api->tested, '>') )
				echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been tested</strong> with your current version of WordPress.', 'wp-e-commerce') . '</p></div>';

			else if ( !empty($api->requires) && version_compare( substr($GLOBALS['wp_version'], 0, strlen($api->requires)), $api->requires, '<') )
				echo '<div class="updated"><p>' . __('<strong>Warning:</strong> This plugin has <strong>not been marked as compatible</strong> with your version of WordPress.', 'wp-e-commerce') . '</p></div>';

			foreach ( $api->sections as $section_name => $content ) {
				if ( isset( $plugins_section_titles[ $section_name ] ) )
					$title = $plugins_section_titles[ $section_name ];
				else
					$title = ucwords( str_replace( '_', ' ', $section_name ) );

				$content = links_add_base_url($content, $api->permalink);
				$content = links_add_target($content, '_blank');

				$san_section = esc_attr($title);

				$display = ( $section_name == $section ) ? 'block' : 'none';

				echo "\t<div id='section-{$san_section}' class='section' style='display: {$display};'>\n";
				echo "\t\t<h2 class='long-header'>$title</h2>";
				echo $content;
				echo "\t</div>\n";
			}

			if (!empty($api->screenshots)) {
				$display = ( 'screenshots' == $section ) ? 'block' : 'none';
				echo "\t<div id='section-screenshots' class='section' style='display: {$display};'>\n";
				echo "\t\t<h2 class='long-header'>Screenshots</h2>\n";
				echo "\t\t<ol>\n";
				foreach ($api->screenshots as $data) {
					echo "\t\t\t<li><img src='{$data->location}' class='screenshot' /><p>{$data->caption}</p></li>\n";
				}
				echo "\t\t</ol>\n";
				echo "\t</div>\n";
			}

		echo "</div>\n";
	}
}
