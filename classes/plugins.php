<?php
/**
 * Habari Plugins Class
 *
 * Provides an interface for the code to access plugins 
 * @package Habari
 */

class Plugins
{
	private static $hooks = array();
	
	/**
	 * function __construct
	 * A private constructor method to prevent this class from being instantiated.
	 * Don't ever create this class as an object for any reason.  It is not a singleton.	 
	 **/	 
	private function __construct()
	{
	}

	/**
	 * function register
	 * Registers a plugin action for possible execution
	 * @param object A reference to the plugin object containing the function to register
	 * @param string The plugin function to register
	 * @param hex An optional execution priority, in hex.  The lower the priority, the earlier the function will execute in the chain.  Default value = 8.
	**/
	public function register( $object, $fn, $type, $hook, $priority = 8 )
	{
		// add the plugin function to the appropriate array
		$index = array($type, $hook, $priority);	
		
		$ref =& self::$hooks;
		
		foreach( $index as $bit ) {
		    if(!isset($ref["{$bit}"])) {
		    	$ref["{$bit}"] = array();
		    }
		    $ref =& $ref["{$bit}"];
		}
		 
		$ref[] = array( $object, $fn );
		ksort(self::$hooks[$type][$hook]);
	}
	
	/**
	 * function act
	 * Call to execute a plugin action
	 * @param string The name of the action to execute
	 * @param mixed Optional arguments needed for action
	 **/	 	 	
	static public function act()
	{
		$args = func_get_args();
		$hookname = array_shift($args);
		if ( ! isset( self::$instance->action[$hookname] ) ) {
			return false;
		}
		foreach ( self::$hooks['action'][$hookname] as $priority ) {
			foreach ( $priority as $action ) {
				// $action is an array of object reference
				// and method name
				call_user_func_array( $action, $args );
			}
		}
	}

	/**
	 * function filter
	 * Call to execute a plugin filter
	 * @param string The name of the filter to execute
	 * @param mixed The value to filter.
	 **/	 
	static public function filter()
	{
		list( $hookname, $return ) = func_get_args();
		if ( ! isset( self::$hooks['filter'][$hookname] ) ) {
			return $return;
		}

		foreach ( self::$hooks['filter'][$hookname] as $priority ) {
			foreach ( $priority as $filter ) {
				// $filter is an array of object reference
				// and method name
				if ( ! is_array( $return ) ) {
					$return = array( $return );
				}
				$return = call_user_func_array( $filter, $return );	
			}
		}
		return $return;
	}

}

?>
