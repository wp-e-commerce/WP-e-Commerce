<?php
/**
 * Mini view superclass
 *
 * @package Sputnik
 * @subpackage Admin View
 */

/**
 * Mini view superclass
 *
 * This is used for embedded views, appearing in an iframe
 *
 * @package Sputnik
 * @subpackage Admin View
 */
abstract class Sputnik_View_Mini extends Sputnik_View {
	protected $body_id = 'sputnik-unknown';

	protected function header() {
		define( 'IFRAME_REQUEST', true );

		global $body_id;
		$body_id = $this->body_id;
		iframe_header($this->title);
	}

	protected function footer() {
		iframe_footer();
		die();
	}
}