<?php

/**
 * Habari ThemeEngine Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 *
 * The ThemeEngine is an ancestor class that new theme engines should extend
 * The whole thing should be virtual, but it's not right now 
 *   
 */


class ThemeEngine
{
	/**
	 * function __construct
	 * Constructor for theme engine
	 * This class should really be a base class for different rendering engines,
	 * as defined by the loaded theme.
	 **/	 	 	 	 	
	public function __construct( ) {
		$this->themedir = HABARI_PATH . '/themes/' . Options::get('theme_dir') . '/';
		if( file_exists($this->themedir . 'theme_vars.php') ) {
			include($this->themedir . 'theme_vars.php');
		}
	}

	/**
	 * function __call
	 * An interesting way to handle included files that are part of a theme.
	 **/	 	 	
	public function __call($fn, $args)
	{
		global $url, $theme, $post;
		
		$potential_template = $this->themedir . $fn . '.php';
		if(file_exists($potential_template)) {
			if( isset($args[0]) && is_array($args[0]) ) {
				extract($args[0]);
			}
			include $potential_template;
		}
	}

}


?>
