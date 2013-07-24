<?php

abstract class Sputnik_View {
	protected $title = 'WATWATWATWAT';

	public function __construct($title) {
		$this->title = $title;
	}

	public function render() {
		$this->header();
		$this->display();
		$this->footer();
	}

	protected function header($title = false, $account = false) {
		if (func_num_args() !== 0) {
			debug_print_backtrace();
			die();
		}

		$title = $this->title;

		$account = false;
		try {
			$account = Sputnik::get_account();
		}
		catch (Exception $e) {
			//
		}
		if ($account !== false) {
			$tabs = array(
				'dash' => __('Store', 'sputnik'),
				'account' => __('Your Account', 'sputnik'),
			);
			$hrefs = array(
				'dash' => Sputnik_Admin::build_url(),
				'account' => menu_page_url( 'sputnik-account', false ),
			);

			$current = Sputnik_Admin::$page;
		}
?>
		<div class="wrap" id="sputnik-page">
<?php
		if ($account !== false) {
?>
			<?php screen_icon( 'sputnik' ); ?>
			<h2 class="nav-tab-wrapper">
<?php
			foreach ($tabs as $page => $title) {
?>
			<a href="<?php echo $hrefs[$page] ?>" class="nav-tab<?php if ($current === $page) echo ' nav-tab-active';?>"><?php echo $title ?></a>
<?php
			}
?>
			</h2>
<?php
		}
		elseif ($title !== false) {
?>
			<?php screen_icon( 'sputnik' ); ?>
			<h2><?php echo $title ?></h2>
<?php
		}
?>

<?php
		do_action('sputnik_messages');
	}

	protected function footer() {?>
			<div id="sputnik-footer">
				<p class="logo-holder"><a href="http://wpeconomy.org/" class="renku-logo">WPEconomy</a></p>
				<nav><p><a href="http://www.wpeconomy.org/documentation/developers/"><?php _e('Developer Tools', 'sputnik') ?></a> | <a href="http://twitter.com/WPEconomy">@WPEconomy</a> | <a href="http://www.wpeconomy.org/documentation/marketplace/faqs/"><?php _e('FAQ', 'sputnik') ?></a></p></nav>
			</div>
		</div>
<?php
	}
}