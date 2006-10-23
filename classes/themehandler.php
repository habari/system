<?php

/**
 * Habari ThemeHandler Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */



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
		global $options, $urlparser;
	
		// What this handler handles and how
		$handle = array(
			'post'=>'post.php', 
			'home'=>'index.php',
		);
	
		if(isset($handle[$action])) {
			include( HABARI_PATH . '/themes/k2/' . $handle[$action] );
		}
		else {
			throw new Exception("ThemeHandler does not handle the action {$action}.");
		}
	
	}

}
?>
