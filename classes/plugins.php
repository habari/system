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
	private static $plugins = array();
	
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
		if ( ! isset( self::$hooks['action'][$hookname] ) ) {
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

		$filterargs = array_slice(func_get_args(), 2);
		foreach ( self::$hooks['filter'][$hookname] as $priority ) {
			foreach ( $priority as $filter ) {
				// $filter is an array of object reference and method name
				$callargs = $filterargs;
				array_unshift( $callargs, $return );
				$return = call_user_func_array( $filter, $callargs );	
			}
		}
		return $return;
	}

	/**
	 * function list_active
	 * Gets a list of active plugin filenames to be included
	 * @return array An array of filenames
	 **/	 
	static public function list_active()
	{
		$res = array();
		$plugins = Options::get( 'active_plugins' );
		if( is_array($plugins) ) {
			foreach( $plugins as $plugin ) {
				if( file_exists( $plugin ) ) {
					$res[] = $plugin;
				}
			}
		}
		return $res;
	}
	
	/**
	 * function list_all
	 * Gets a list of all plugin filenames that are available
	 * @return array An array of filenames
	 **/	 
	static public function list_all()
	{
		$plugindir = HABARI_PATH . '/user/plugins/';
		$files = glob( $plugindir . '*.plugin.php' );
		$dirs = glob( $plugindir . '*', GLOB_ONLYDIR | GLOB_MARK );
		foreach( $dirs as $dir ) {
			$dirfiles = glob( $dir . '*.plugin.php' );
			$files = array_merge($dirfiles, $files);
		}
		return $files;
	}

	/**
	 * function list_loaded
	 * Returns an array of loaded plugin class objects
	 * @return array An array of Plugin descendants
	 **/	 
	static public function list_loaded()
	{
		return self::$plugins;
	}
	
	/**
	 * function load
	 * Initialize all loaded plugins by calling their load() method
	 **/
	static public function load()
	{
		$classes = get_declared_classes();
		foreach( $classes as $class ) {
			if( get_parent_class($class) == 'Plugin' ) {
				self::$plugins[] = new $class();
				$plugin = end(self::$plugins); 
				$info = $plugin->info(); // Compare minversion and maxversion to Habari svn rev?
				$plugin->load();
			}
		}
	}
	
	/**
	 * function activate_plugin
	 * Activates a plugin file
	 **/	 	 	
	static public function activate_plugin( $file )
	{
		$activated = Options::get( 'active_plugins' );
		if( !is_array( $activated ) || !in_array( $file, $activated ) ) {
			$activated[] = $file;
			Options::set( 'active_plugins', $activated );
		}
	}

	/**
	 * function deactivate_plugin
	 * Deactivates a plugin file
	 **/	 	 	
	static public function deactivate_plugin( $file )
	{
		$activated = Options::get( 'active_plugins' );
		if( is_array( $activated ) && ($index = array_search( $file, $activated ) ) ) {
			unset($activated[$index]);
			Options::set( 'active_plugins', $activated );
		}
	}
}

?>
