<?php
/**
 * Authentication handler view
 *
 * @package Sputnik
 * @subpackage Admin View
 */

/**
 * Authentication handler view
 *
 * @package Sputnik
 * @subpackage Admin View
 */
class Sputnik_View_Auth extends Sputnik_View {
	public function __construct() {
		parent::__construct(false);
	}
	public function render() {
		if ( isset( $_GET['auth'] ) && $_GET['auth'] == 'denied' ) {
			Sputnik_Admin::add_message( __( 'Account linking cancelled. Please note that you need to link your account in order to access the store.', 'wp-e-commerce' ) );
		}

		$this->header();
		$oauth_url    = Sputnik_Admin::build_url(array('oauth' => 'request'));

?>
<div id="sputnik-auth">
	<iframe src="<?php echo $oauth_url; ?>"></iframe>
</div>
<?php
		$this->footer();
	}
}