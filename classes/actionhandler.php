<?php

/**
 * class ActionHandler
 *  
 * A base class dispatcher for URL-based actions.
 * The class name specified in a URL rule should be an extension of this class,
 * and should implement a function named the same as the action value of the
 * rule.
 *  
 * @package Habari
 * @see URL::init_rules()
 * @version $Id$ 
 **/  
class ActionHandler
{

	/**
	 * function __construct
	 * 	 
	 * Constructor for ActionHandler and derived classes
	 * Attempts to find a method in the object that matches the requested action,
	 * and call it with the settings that are provided.
	 * 	 
	 * @param string The action that was in the URL rule
	 * @param array An associative array of settings found in the URL by the URL
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
