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

	public function __call($fn, $args)
	{
		global $options, $urlparser, $theme;
		
		$potential_template = THEME_DIR . $fn . '.php';
		if(file_exists($potential_template)) {
			include $potential_template;
		}
	}

}


?>
