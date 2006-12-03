<?php
/**
 * Habari Plugins Class
 *
 * Provides an interface for the code to access plugins 
 * @package Habari
 */

class Plugins
{
	private $instance = null;
	private $hooks;
	private $action;
	private $filter;

	/**
	 * function __construct
	 * Plugins class constructor.  Singleton
	 **/	 	 	
	private function __construct()
	{
		$this->hooks = array(
			'action'=>array(),
			'filter'=>array(),
		);
		$action = array();
		$filter = array();
	}
	
	/**
	 * function instantiate
	 * Creates the private instance.  Do not call directly, called by do() and filter()
	 **/	 
	static private function instantiate()
	{
		if(self::$instance == null) {
			$c = __CLASS__;
			self::$instance = new $c(); 
		}
	}

	/**
	 * function register
	 * Registers a plugin action for possible execution
	 * @param object A reference to the plugin object containing the function to register
	 * @param string The plugin function to register
	 * @param hex An optional execution priority, in hex.  The lower the priority, the earlier the function will execute in the chain.  Default value = 8.
	**/
	public function register( $object, $fn, $priority = 8 )
	{
		// basic safety check to ensure that the supplied
		// function name is action_foo or filter_foo
		if ( ( 0 !== strpos( $fn, 'action_' ) ) ||
			( 0 !== strpos( $fn, 'filter_' ) ) )
		{
			return false;
		}
		// find out what type of function we're registering
		$type = substr( $fn, 0, strpos( $fn, '_' ) );
		$this->$type[$fn][$priority][] = array( $object, $fn );
	}
	
	/**
	 * function do
	 * Call to execute a plugin action
	 * @param string The name of the action to execute
	 * @param mixed Optional arguments needed for action
	 **/	 	 	
	static public function do()
	{
		self::instantiate();
		$args = func_get_args();
		$hookname = array_shift($args);
		if ( ! isset( $this->action[$hookname] ) )
		{
			return false;
		}
		foreach ( $this->action[$hookname] as $priority )
		{
			foreach ( $priority as $action )
			{
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
		self::instantiate();
		list( $hookname, $return ) = func_get_args();
		if ( ! isset( $this->filter[$hookname] ) )
		{
			return $return;
		}
		foreach ( $this->filter[$hookname] as $priority )
		{
			foreach ( $priority as $filter )
			{
			// $filter is an array of object reference
			// and method name
			if ( ! is_array( $return )
			{
				$return = array( $return );
			}
			$return = call_user_func_array( $filter, $return );	
			}
		}
	}

}

?>
