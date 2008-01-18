<?php

/**
 * Pluggable class
 * Implements methods that allow descendant classes to register functions to plugin hooks
 *
 * @version $Id$
 * @copyright 2008
 */

abstract class Pluggable
{
	private $_class_name= null;
	public $info;
	public $plugin_id;

	/**
	 * Pluggable constructor.
	 * This function creates some internal structures that are required for plugin processing
	 * Plugins should not define their own constructors, because they are instantiated
	 * to extract plugin info.  Instead, include a sink for a "init" hook
	 * which is executed immediately after the plugin is loaded during normal execution.
	 **/
	public function __construct(){
		$this->info= new InfoObject( $this->info() );
		$this->plugin_id= $this->plugin_id();
	}

	/**
	 * Gets the filename that contains this pluggable class
	 * @return string The filename of the file that contains the pluggable class.
	 **/
	final public function get_file()
	{
		if(empty($this->_class_name)) {
			$class = new ReflectionClass( get_class( $this ) );
			$this->_class_name= $class->getFileName();
		}
		return $this->_class_name;
	}

	/**
	 * Returns a unique id for this pluggable
	 * @return string A plugin id
	 */
	final public function plugin_id()
	{
		return Plugins::id_from_file( str_replace('\\', '/', $this->get_file() ) );
	}

	/**
	 * Called when a pluggable is loaded to register its actions and filters.
	 * Registers all of this pluggables action_ and filter_ functions with the Plugins dispatcher
	 * Registers xmlrpc_ functions with the Plugins dispatcher, and turns '__' into '.'
	 * for the purposes of matching dotted XMLRPC requests.
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
			if (
				( 0 !== strpos( $fn, 'action_' ) )
				&& ( 0 !== strpos( $fn, 'filter_' ) )
				&& ( 0 !== strpos( $fn, 'xmlrpc_' ) )
				&& ( 0 !== strpos( $fn, 'theme_' ) )
			) {
				continue;
			}
			$priority = isset($priorities[$fn]) ? $priorities[$fn] : 8;
			$type = substr( $fn, 0, strpos( $fn, '_' ) );
			$hook = substr( $fn, strpos( $fn, '_' ) + 1 );
			if ( 0 === strpos( $fn, 'xmlrpc_' ) ) {
				$hook = str_replace('__', '.', $hook);
			}
			Plugins::register( array($this, $fn), $type, $hook, $priority );
		}
	}
}

?>