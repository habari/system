<?php

/**
 * Habari ThemeHandler Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

// TODO: Make this not use a specific theme
define('THEME_DIR', HABARI_PATH . '/themes/k2/');
//define('THEME_DIR', HABARI_PATH . '/themes/so-fresh/');
class ThemeHandler extends ActionHandler
{

	/**
	 * function __call
	 * Handles all calls to this class for methods that don't exist.
	 * In this class, method names correlated with action names set in the URLParser rules.
	 * Throws an execption if the requested action isn't valid.
	 * @param string The action that was called.
	 * @param array Settings passed in from the URL
	 **/	 	 
	public function __call($action, $settings) {
		global $urlparser, $theme;
	
		// What this handler handles and how
		$handle = array(
			'post'=>'post.php', 
			'home'=>'index.php',
			'login'=>'login.php',
			'logout'=>'login.php',
		);
		
		$theme = new ThemeEngine();
	
		if(isset($handle[$action])) {
			$potential_template = THEME_DIR . $handle[$action];
			if(file_exists($potential_template)) {
				include $potential_template;
			}
		}
		else {
			throw new Exception("ThemeHandler does not handle the action {$action}.");
		}
	
	}

}
?>
