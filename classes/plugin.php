<?php
/**
 * Habari Plugin Class
 *
 * Template plugin object which specific plugin objects should extend
 * This object provides the basic constructor used to ensure that
 * plugin actions are registered against the appropriate dispatcher
 *
 * @package Habari
 */

class Plugin
{
	/**
	 * function __construct
	 * Plugin class constructor. 
	 * Registers all of this plugins action_ and filter_ functions with the Plugins dispatcher
	 **/	 	 	
	public function __construct()
	{
		// get the specific priority values for functions, as needed
		if ( method_exists ( $this, 'set_priorities' ) ) {
			$priorities = $this->set_priorities();
		}
		// loop over all the methods in this class
		foreach ( get_class_methods( $this ) as $fn ) {
			// make sure the method name is of the form
			// action_foo or filter_foo
			if ( ( 0 !== strpos( $fn, 'action_' ) ) && ( 0 !== strpos( $fn, 'filter_' ) ) ) {
				continue;
			}	
			$priority = isset($priorities[$fn]) ? $priorities[$fn] : 8;
			$type = substr( $fn, 0, strpos( $fn, '_' ) );
			$hook = substr( $fn, strpos( $fn, '_' ) + 1 );
			Plugins::register( $this, $fn, $type, $hook, $priority );
		}
	}
	
}

?>
