<?php
/**
 * Habari Plugins Class
 *
 * Provides an interface for the code to access plugins
 * @package Habari
 */

class Plugins
{
	private static $hooks= array();
	private static $plugins= array();
	private static $plugin_files= array();

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
	 * @param mixed A reference to the function to register by string or array(object, string)
	 * @param string Usually either 'filter' or 'action' depending on the hook type.
	 * @param string The plugin hook to register
	 * @param hex An optional execution priority, in hex.  The lower the priority, the earlier the function will execute in the chain.  Default value = 8.
	**/
	public function register( $fn, $type, $hook, $priority = 8 )
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

		$ref[] = $fn;
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
	 * @param boolean Whether to refresh the cached array.  Default FALSE
	 * @return array An array of filenames
	 **/
	static public function list_active( $refresh= false )
	{
		if ( ! empty( self::$plugin_files ) && ! $refresh )
		{
			return self::$plugin_files;
		}
		$plugins = Options::get( 'active_plugins' );
		if( is_array($plugins) ) {
			foreach( $plugins as $plugin ) {
				if( file_exists( $plugin ) ) {
					self::$plugin_files[] = $plugin;
				}
			}
		}
		// make sure things work on Windows
		self::$plugin_files= array_map( create_function( '$s', 'return str_replace(\'\\\\\', \'/\', $s);' ), self::$plugin_files );
		return self::$plugin_files;
	}

	/**
	 * function get_active
	 * Returns the internally stored references to all loaded plugins
	 * @return array An array of plugin objects
	 **/
	static public function get_active()
	{
		return self::$plugins;
	}

	/**
	* Get references to plugin objects that implement a specific interface
	* @param string $interface The interface to check for
	* @return array An array of matching plugins
	*/
	static public function get_by_interface($interface)
	{
		return array_filter(self::$plugins, create_function('$a', 'return $a instanceof ' . $interface . ';'));
	}

	/**
	 * function list_all
	 * Gets a list of all plugin filenames that are available
	 * @return array An array of filenames
	 **/
	static public function list_all()
	{
		$plugins= array();
		$files= array();
		$plugindir= HABARI_PATH . '/user/plugins/';
		$dirs= glob( $plugindir . '*', GLOB_ONLYDIR | GLOB_MARK );
		if ( Site::CONFIG_LOCAL != Site::$config_type )
		{
			// include site-specific plugins
			$site_dirs= glob( Site::get_dir('config') . '/plugins/*', GLOB_ONLYDIR | GLOB_MARK );
			if ( is_array( $site_dirs ) && ! empty( $site_dirs ) ) {
				$dirs= array_merge( $dirs, $site_dirs );
			}
		}
		foreach( $dirs as $dir ) {
			$dirfiles = glob( $dir . '*.plugin.php' );
			$files = array_merge($dirfiles, $files);
		}
		// return $files;
		// massage the return value so that this works on Windows
		return array_map( create_function( '$s', 'return str_replace(\'\\\\\', \'/\', $s);' ), $files );
	}

	/**
	 * function class_from_filename
	 * returns the class name from a plugin's filename
	 * @param string the full path to a plugin file
	 * @return string the class name
	**/
	static public function class_from_filename( $file )
	{
		return str_replace( '.plugin.php', '',  substr( $file, ( strrpos( $file, '/') + 1 ) ) );
	}

	/**
	 * function load
	 * Initialize all loaded plugins by calling their load() method
	 * @param string the class name to load
	 **/
	static public function load( $file )
	{
		$class= Plugins::class_from_filename( $file );
		self::$plugins[$file]= new $class;
		$plugin= self::$plugins[$file];
		$plugin->load();
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
		$index= array_search( $file, $activated );
		if ( is_array( $activated ) && ( FALSE !== $index ) )
		{
			unset($activated[$index]);
			Options::set( 'active_plugins', $activated );
		}
	}
}

?>
