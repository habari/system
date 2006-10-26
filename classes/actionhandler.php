<?php

/**
 * Habari ActionHandler Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 * 
 * A base class to derive Handlers from.
 * Handlers are used by the URLParser to dispatch actions found in the request URL   
 */

class ActionHandler
{

	/**
	 * function __construct
	 * Constructor for ActionHandler and derived classes
	 * Attempts to find a method in the object that matches the requested action,
	 * and call it with the settings that are provided.
	 * @param string The action that was in the URLParser rule
	 * @param array An associative array of settings found in the URL by the URLParser
	 **/	 	 	 	 
	public function __construct($action, $settings)
	{
		try {
			call_user_func(array($this, $action), $settings);
			//$this->$action($settings);
		}
		catch ( Exception $e ) {
			$classname = get_class($this);
			echo "\n{$classname}->{$action}() does not exist.\n";
			$methods = get_class_methods($classname);
			foreach($methods as $method) echo "{$method}\n";
			Utils::debug($settings);
		}
	}

}
?>
