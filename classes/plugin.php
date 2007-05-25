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

abstract class Plugin
{
	/**
	 * Plugin constructor.
	 * Plugins should not define their own constructors, because they are instantiated
	 * to extract plugin info.  Instead, include a sink for a "init" hook
	 * which is executed immediately after the plugin is loaded during normal execution.
	 **/
	final public function __construct(){}	 

	/**
	 * function get_file
	 * Gets the filename that contains the plugin
	 * @return string The filename of the file that contains the plugin class.	 
	 **/
	final public function get_file()
	{
		$class = new ReflectionClass( get_class( $this ) );
		return $class->getFileName();
	}	 	 	

	/**
	 * abstract function info
	 * Returns information about this plugin
	 * @return array An associative array of information about this plugin
	 **/
	abstract public function info();

	/**
	 * function load
	 * Called when a plugin is loaded to register its actions and filters.	 
	 * Registers all of this plugins action_ and filter_ functions with the Plugins dispatcher
	 **/	 	 	 		
	public function load()
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
			Plugins::register( array($this, $fn), $type, $hook, $priority );
		}
	}
}

?>
