<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari Plugins Class
 *
 * Provides an interface for the code to access plugins
 */
class Plugins
{
	private static $hooks = array();
	private static $plugins = array();
	private static $plugin_files = array();

	/**
	 * function __construct
	 * A private constructor method to prevent this class from being instantiated.
	 * Don't ever create this class as an object for any reason.  It is not a singleton.
	 */
	private function __construct()
	{
	}

	/**
	 * Autoload function to load plugin file from classname
	 */
	public static function _autoload( $class )
	{
		if ( isset( self::$plugin_files[$class] ) ) {
			require( self::$plugin_files[$class] );
			if( !class_exists($class, false)) {
				// The classname of a plugin changed.
				$filename = self::$plugin_files[$class];
				EventLog::log(_t('Plugin file "%s" has changed its plugin class name.', array($filename)));
				Session::error(_t('Plugin file "%s" has changed its plugin class name.', array($filename)));

				// Remove the plugin from the active list
				$active_plugins = Options::get('active_plugins');
				unset($active_plugins[$class]);
				self::$plugin_files = array();
				Options::set('active_plugins', $active_plugins);
				self::list_active(true); // Refresh the internal list

				// Reactivate it to try to get the new class loaded
				self::activate_plugin($filename);
				Utils::redirect();
			}
		}
	}

	/**
	 * function register
	 * Registers a plugin action for possible execution
	 * @param Callable $fn A reference to the function to register by string or array(object, string)
	 * @param string $type Usually either 'filter' or 'action' depending on the hook type.
	 * @param string $hook The plugin hook to register
	 * @param integer $priority An optional execution priority, from 1-16.  The lower the priority, the earlier the function will execute in the chain.  Default value = 8.
	 */
	public static function register( $fn, $type, $hook, $priority = 8 )
	{
		// add the plugin function to the appropriate array
		$index = array( $type, $hook, $priority );

		$ref =& self::$hooks;

		foreach ( $index as $bit ) {
			if ( !isset( $ref["{$bit}"] ) ) {
				$ref["{$bit}"] = array();
			}
			$ref =& $ref["{$bit}"];
		}

		$ref[] = $fn;
		ksort( self::$hooks[$type][$hook] );
	}

	/**
	 * Call to execute a plugin action
	 * @param string $hookname The name of the action to execute
	 * @param mixed $param Optional arguments needed for action
	 */
	public static function act($hookname, $param = null)
	{
		$args = func_get_args();
		$hookname = array_shift( $args );
		if ( isset( self::$hooks['action'][$hookname] ) ) {
			foreach ( self::$hooks['action'][$hookname] as $priority ) {
				foreach ( $priority as $action ) {
					// $action is an array of object reference
					// and method name
					call_user_func_array( $action, $args );
				}
			}
		}
	}

	/**
	 * Call to execute a plugin action, by id
	 * @param string $hookname The name of the action to execute
	 * @param mixed $param Optional arguments needed for action
	 */
	public static function act_id($hookname, $param = null)
	{
		$args = func_get_args();
		list( $hookname, $id ) = $args;
		$args = array_slice( func_get_args(), 2 );
		$hookname = $hookname . ':' . $id;
		if ( isset( self::$hooks['action'][$hookname] ) ) {
			foreach ( self::$hooks['action'][$hookname] as $priority ) {
				foreach ( $priority as $action ) {
					// $action is an array of object reference
					// and method name
					call_user_func_array( $action, $args );
				}
			}
		}
	}

	/**
	 * Call to execute a plugin filter
	 * @param string $hookname The name of the filter to execute
	 * @param mixed $param The value to filter.
	 * @return mixed The result of filtering the first $param value
	 */
	public static function filter($hookname, $param)
	{
		list( $hookname, $return ) = func_get_args();
		if ( isset( self::$hooks['filter'][$hookname] ) ) {
			$filterargs = array_slice( func_get_args(), 2 );
			foreach ( self::$hooks['filter'][$hookname] as $priority ) {
				foreach ( $priority as $filter ) {
					// $filter is an array of object reference and method name
					$callargs = $filterargs;
					array_unshift( $callargs, $return );
					$return = call_user_func_array( $filter, $callargs );
				}
			}
		}
		return $return;
	}

	/**
	 * Call to execute a plugin filter on a specific plugin, by id
	 * @param string $hookname The name of the filter to execute
	 * @param string $id The id of the only plugin on which to execute
	 * @param mixed $param The value to filter.
	 * @return mixed The result of filtering the first $param value
	 */
	public static function filter_id($hookname, $id, $param)
	{
		list( $hookname, $id, $return ) = func_get_args();
		$hookname = $hookname . ':' . $id;
		if ( isset( self::$hooks['filter'][$hookname] ) ) {
			$filterargs = array_slice( func_get_args(), 3 );
			foreach ( self::$hooks['filter'][$hookname] as $priority ) {
				foreach ( $priority as $filter ) {
					// $filter is an array of object reference and method name
					$callargs = $filterargs;
					array_unshift( $callargs, $return );
					$return = call_user_func_array( $filter, $callargs );
				}
			}
		}
		return $return;
	}

	/**
	 * Call to execute an XMLRPC function
	 * @param string $hookname The name of the filter to execute
	 * @param mixed $param The value to filter.
	 * @return bool|mixed The result of the XMLRPC call
	 */
	public static function xmlrpc($hookname, $param)
	{
		list( $hookname, $return ) = func_get_args();
		if ( isset( self::$hooks['xmlrpc'][$hookname] ) ) {
			$filterargs = array_slice( func_get_args(), 2 );
			foreach ( self::$hooks['xmlrpc'][$hookname] as $priority ) {
				foreach ( $priority as $filter ) {
					// $filter is an array of object reference and method name
					return call_user_func_array( $filter, $filterargs );
				}
			}
		}
		return false;
	}

	/**
	 * Call to execute a theme function
	 * @param string $hookname The name of the filter to execute
	 * @param mixed $param The value to filter
	 * @return mixed The filtered value
	 */
	public static function theme($hookname, $param)
	{
		$filter_args = func_get_args();
		$hookname = array_shift( $filter_args );

		$filtersets = array();
		if ( !isset( self::$hooks['theme'][$hookname] ) ) {
			if ( substr( $hookname, -6 ) != '_empty' ) {
				array_unshift( $filter_args, $hookname . '_empty' );
				return call_user_func_array( Method::create( '\\Habari\\Plugins', 'theme' ), $filter_args );
			}
			return array();
		}

		$return = array();
		foreach ( self::$hooks['theme'][$hookname] as $priority ) {
			foreach ( $priority as $filter ) {
				// $filter is an array of object reference and method name
				$callargs = $filter_args;
				if ( is_array( $filter ) ) {
					if ( is_string( $filter[0] ) ) {
						$module = $filter[0];
					}
					else {
						$module = get_class( $filter[0] );
						// @todo Make sure this doesn't break after the namespace update
						if ( $filter[0] instanceof Theme && $module != get_class( $callargs[0] ) ) {
							continue;
						}
					}
				}
				else {
					$module = $filter;
				}
				$return[$module] = call_user_func_array( $filter, $callargs );
			}
		}
		if ( count( $return ) == 0 && substr( $hookname, -6 ) != '_empty' ) {
			array_unshift( $filter_args, $hookname . '_empty' );
			$result = call_user_func_array( Method::create( '\\Habari\\Plugins', 'theme' ), $filter_args );
		}
		array_unshift( $filter_args, 'theme_call_' . $hookname, $return );
		$result = call_user_func_array( Method::create( '\\Habari\\Plugins', 'filter' ), $filter_args );
		return $result;
	}

	/**
	 * Determine if any plugin implements the indicated theme hook
	 *
	 * @param string $hookname The name of the hook to check for
	 * @return boolean True if the hook is implemented
	 */
	public static function theme_implemented( $hookname )
	{
		return isset( self::$hooks['theme'][$hookname] );
	}

	/**
	 * Determine if a hook of any type is implemented
	 * @static
	 * @param string $hookname The name of the hook to check for
	 * @param string $searchtype Optional. The type of hook to check for
	 * @return array|bool An array with the types of hook implemented, or false if not implemented
	 */
	public static function implemented( $hookname, $searchtype = null )
	{
		$result = array();
		foreach(self::$hooks as $type => $hooks) {
			if($searchtype && $searchtype != $type) continue;
			if(isset($hooks[$hookname])) {
				$result[$type] = $type;
			}
		}
		return count($result) > 0 ? $result : false;
	}

	/**
	 * function list_active
	 * Gets a list of active plugin filenames to be included
	 * @param boolean $refresh Whether to refresh the cached array.  Default false
	 * @return array An array of filenames
	 */
	public static function list_active( $refresh = false )
	{
		if ( empty( self::$plugin_files ) || $refresh ) {
			$plugins = Options::get( 'active_plugins' );
			if ( is_array( $plugins ) ) {
				foreach ( $plugins as $class => $filename ) {
					// add base path to stored path
					if(!preg_match('#^([^:]+://)#i', $filename, $matches)) {
						$filename = HABARI_PATH . $filename;
					}

					// if class is somehow empty we'll throw an error when trying to load it - deactivate the plugin instead
					if ( $class == '' ) {
						self::deactivate_plugin( $filename, true );
						EventLog::log( _t( 'An empty plugin definition pointing to file "%1$s" was removed.', array( $filename ) ), 'err', 'plugin', 'habari' );
						// and skip adding it to the active stack
						continue;
					}

					if ( file_exists( $filename ) ) {
						self::$plugin_files[$class] = $filename;
					}
					else {
						// file does not exist, deactivate plugin
						self::deactivate_plugin( $filename, true );
						EventLog::log( _t( 'Plugin "%1$s" deactivated because it could no longer be found.', array( $class ) ), 'err', 'plugin', 'habari', $filename );
					}
				}
			}
			// make sure things work on Windows
			self::$plugin_files = array_map( function($s) {return str_replace('\\', '/', $s);}, self::$plugin_files );
		}
		return self::$plugin_files;
	}

	/**
	 * Returns the internally stored references to all loaded plugins
	 * @return array An array of plugin objects
	 */
	public static function get_active()
	{
		return self::$plugins;
	}

	/**
	* Get references to plugin objects that implement a specific interface
	* @param string $interface The interface to check for
	* @return array An array of matching plugins
	*/
	public static function get_by_interface( $interface )
	{
		$interface = "Habari\\" . $interface;
		return array_filter( self::$plugins, function( $plugin_obj ) use ($interface) { return $plugin_obj instanceof $interface; } );
	}

	/**
	 * function list_all
	 * Gets a list of all plugin filenames that are available
	 * @return array An array of filenames
	 */
	public static function list_all()
	{
		$plugins = array();
		$plugindirs = array( HABARI_PATH . '/system/plugins/', HABARI_PATH . '/3rdparty/plugins/', HABARI_PATH . '/user/plugins/' );
		if ( Site::CONFIG_LOCAL != Site::$config_type ) {
			// include site-specific plugins
			$plugindirs[] = Site::get_dir( 'config' ) . '/plugins/';
		}
		$dirs = array();
		foreach ( $plugindirs as $plugindir ) {
			if ( file_exists( $plugindir ) ) {
				$dirs = array_merge( $dirs, Utils::glob( $plugindir . '*', GLOB_ONLYDIR | GLOB_MARK ) );
			}
		}
		foreach ( $dirs as $dir ) {
			$dirfiles = Utils::glob( $dir . '*.plugin.php' );
			if ( ! empty( $dirfiles ) ) {
				$dirfiles = array_combine(
					// Use the basename of the file as the index to use the named plugin from the last directory in $dirs
					array_map( 'basename', $dirfiles ),
					// massage the filenames so that this works on Windows
					array_map( function($s) {return str_replace('\\', '/', $s);}, $dirfiles )
				);
				$plugins = array_merge( $plugins, $dirfiles );
			}
			$dirfiles = Utils::glob( $dir . '*.phar' );
			foreach($dirfiles as $filename) {
				if(preg_match('/\.phar$/i', $filename)) {
					$d = new \DirectoryIterator('phar://' . $filename);
					foreach ($d as $fileinfo) {
						if ($fileinfo->isFile() && preg_match('/\.plugin.php$/i', $fileinfo->getFilename())) {
							$plugins[$fileinfo->getFilename()] = str_replace('\\', '/', $fileinfo->getPathname());
						}
					}

				}
			}
		}
		ksort( $plugins );
		return $plugins;
	}

	/**
	 * Get classes that extend Plugin.
	 * @param $class string A class name
	 * @return boolean true if the class extends Plugin
	 */
	public static function extends_plugin( $class )
	{
		$parents = class_parents( $class, false );
		$matches = array_intersect(array('Plugin', 'Habari\Plugin'), $parents);
		return count($matches) > 0;
	}

	/**
	 * function class_from_filename
	 * returns the class name from a plugin's filename
	 * @param string $file the full path to a plugin file
	 * @param bool $check_realpath whether or not to try realpath resolution
	 * @return string the class name
	 */
	public static function class_from_filename( $file, $check_realpath = false )
	{
		if ( $check_realpath ) {
			$file = realpath( $file );
		}
		foreach ( self::get_plugin_classes() as $plugin ) {
			$class = new \ReflectionClass( $plugin );
			$classfile = str_replace( '\\', '/', $class->getFileName() );
			if ( $classfile == $file ) {
				return $plugin;
			}
		}
		// if we haven't found the plugin class, try again with realpath resolution:
		if ( $check_realpath ) {
			// really can't find it
			return false;
		}
		else {
			return self::class_from_filename( $file, true );
		}
	}

	public static function get_plugin_classes()
	{
		$classes = get_declared_classes();
		return array_filter( $classes, Method::create( '\Habari\Plugins', 'extends_plugin' ) );
	}

	/**
	 * Initialize all loaded plugins by calling their load() method
	 * @param string $file the class name to load
	 * @param boolean $activate True if the plugin's load() method should be called
	 * @return \Habari\Plugin The instantiated plugin class
	 */
	public static function load_from_file( $file, $activate = true )
	{
		$class = self::class_from_filename( $file );
		return self::load( $class, $activate );
	}


	/**
	 * Return the info XML for a plugin based on a filename
	 *
	 * @param string $file The filename of the plugin file
	 * @return \SimpleXMLElement The info structure for the plugin, or null if no info could be loaded
	 */
	public static function load_info( $file )
	{
		$info = null;
		$xml_file = preg_replace( '%\.plugin\.php$%i', '.plugin.xml', $file );
		
		if ( file_exists( $xml_file ) && $xml_content = file_get_contents( $xml_file ) ) {
			
			// tell libxml to throw exceptions and let us check for errors
			$old_error = libxml_use_internal_errors( true );
			
			try {
				$info = new \SimpleXMLElement( $xml_content );
				
				// if the xml file uses a theme element name instead of pluggable, it's old
				if ( $info->getName() != 'pluggable' ) {
					$info = 'legacy';
				}
				else {

					$info['filename'] = $xml_file;

					// Translate the plugin description
					Locale::translate_xml( $info, $info->description );

					// Translate the plugin help
					foreach( $info->help as $help ) {
						Locale::translate_xml( $help, $help->value );
					}

				}
			}
			catch ( \Exception $e ) {
				
				EventLog::log( _t( 'Invalid plugin XML file: %1$s', array( $xml_file ) ), 'err', 'plugin' );
				$info = 'broken';
				
			}
			
			// restore the old error level
			libxml_use_internal_errors( $old_error );
			
		}
		else {
			EventLog::log( _t( 'Missing or empty plugin XML file: %1$s', array( $xml_file ) ), 'err', 'plugin' );
			$info = 'missingorempty';
		}
		
		return $info;
	}

	/**
	 * Load a pluign into memory by class name
	 * 
	 * @param string $class The name of the class of the plugin to load
	 * @param boolean $activate True to run the load routine of the plugin and add it to the loaded plugins list
	 * @return Plugin The instance of the created plugin
	 */
	public static function load( $class, $activate = true )
	{
		$plugin = new $class;
		if(!$plugin instanceof Plugin) {
			EventLog::log(_t('The class "%s" is not a Plugin, but was queued to load as a plugin.', array($class)));
			Session::error(_t('The class "%s" is not a Plugin, but was queued to load as a plugin. It may not currently be active.', array($class)));
			self::deactivate_plugin(self::$plugin_files[$class], true);
			return false;
		}
		if ( $activate ) {
			self::$plugins[$plugin->plugin_id] = $plugin;
			$plugin->load();
			$plugin->upgrade();
		}
		return $plugin;
	}

	/**
	 * Upgrade all loaded plugins
	 */
	public static function upgrade( )
	{
		foreach(self::$plugins as $plugin) {
			$plugin->upgrade();
		}
	}

	/**
	 * Instantiate and load all active plugins
	 */
	public static function load_active()
	{
		foreach ( self::list_active() as $class => $filename ) {
			if ( file_exists( $filename ) ) {
				self::load( $class );
			}
		}
		Autoload::queue_dirs(Plugins::filter('autoload_dirs', array()));
	}


	/**
	 * Returns a plugin id for the filename specified.
	 * Used to unify the way plugin ids are generated, rather than spreading the
	 * calls internal to this function over several files.
	 *
	 * @param string $file The filename to generate an id for
	 * @return string A plugin id.
	 */
	public static function id_from_file( $file )
	{
		$file = str_replace( array( '\\', '/' ), PATH_SEPARATOR, realpath( $file ) );
		return sprintf( '%x', crc32( $file ) );
	}

	/**
	 * Activates a plugin file
	 */
	public static function activate_plugin( $file )
	{
		$ok = true;

		// check for a URL-looking filename, which probably indicates a PHAR-format plugin
		if(preg_match('#^([^:]+://)#i', $file, $matches)) {
			// we need the entire path to the file
			$short_file = $file;
		}
		else {
			// trim off the leading habari path from the filename
			$short_file = MultiByte::substr( $file, strlen( HABARI_PATH ) );
		}

		//Activate all dependency plugins first
		$provider_data = self::provided(null, true, true);
		$providing_data = self::provided();
		$info = self::load_info($file);
		if ( isset( $info->requires ) ) {
			$dependencies = array();
			foreach ( $info->requires->feature as $feature ) {
				// Does something already provide this required feature?
				if(isset($providing_data[(string) $feature])) {
					continue;
				}
				// Could one inactive thing provide this required feature?
				if(isset($provider_data[(string) $feature]) && count($provider_data[(string) $feature]) == 1) {
					$dependencies[] = reset($provider_data[(string) $feature]);
				}
				else {
					return false;
				}
			}
			// Try to activate the dependencies we accumulated just then
			foreach($dependencies as $dependency) {
				$result = self::activate_plugin($dependency);
				// Couldn't activate a dependency?  Can't activate this one.
				if(!$result) {
					return false;
				}
			}
		}


		$activated = Options::get( 'active_plugins' );
		if ( !is_array( $activated ) || !in_array( $short_file, $activated ) ) {
			include_once( $file );
			$class = Plugins::class_from_filename( $file );
			$plugin = Plugins::load( $class );
			$ok = Plugins::filter( 'activate_plugin', $ok, $file ); // Allow plugins to reject activation
		}
		else if ( is_array( $activated ) && in_array( $short_file, $activated ) ) {
			$ok = false;
		}
		if ( $ok ) {
			$activated[$class] = $short_file;
			Options::set( 'active_plugins', $activated );
			$versions = Options::get( 'pluggable_versions' );
			if(!isset($versions[$class])) {
				$versions[$class] = $plugin->get_version();
				Options::set( 'pluggable_versions', $versions );
			}

			if ( method_exists( $plugin, 'action_plugin_activation' ) ) {
				$plugin->action_plugin_activation( $file ); // For the plugin to install itself
			}
			Plugins::act( 'plugin_activated', $file ); // For other plugins to react to a plugin install
			EventLog::log( _t( 'Activated Plugin: %s', array( $plugin->info->name ) ), 'notice', 'plugin', 'habari' );
		}
		return $ok;
	}

	/**
	 * Deactivates a plugin file
	 * @param string $file the Filename of the plugin to deactivate
	 * @param boolean $force If true, deactivate this plugin regardless of what filters may say about it.
	 * @return bool If true, the plugin was deactivated
	 */
	public static function deactivate_plugin( $file, $force = false )
	{
		$ok = true;
		$name = '';
		$ok = Plugins::filter( 'deactivate_plugin', $ok, $file );  // Allow plugins to reject deactivation
		if ( $ok || $force == true ) {
			// normalize directory separator
			$file = str_replace( '\\', '/', $file );
			// strip base path from stored path
			$short_file = MultiByte::substr( $file, MultiByte::strlen( HABARI_PATH ) );

			$activated = Options::get( 'active_plugins' );
			$index = array_search( $short_file, $activated );
			if ( is_array( $activated ) && ( false !== $index ) ) {
				
				if ( $force != true ) {
					// Get plugin name for logging
					$name = self::$plugins[Plugins::id_from_file( $file )]->info->name;
					if ( method_exists( self::$plugins[Plugins::id_from_file( $file )], 'action_plugin_deactivation' ) ) {
						self::$plugins[Plugins::id_from_file( $file )]->action_plugin_deactivation( $file ); // For the plugin to uninstall itself
					}
				}
				
				unset( $activated[$index] );
				Options::set( 'active_plugins', $activated );
				
				if ( $force != true ) {
					Plugins::act( 'plugin_deactivated', $file );  // For other plugins to react to a plugin uninstallation
					EventLog::log( _t( 'Deactivated Plugin: %s', array( $name ) ), 'notice', 'plugin', 'habari' );
				}
			}
		}
		
		if ( $force == true ) {
			// always return true for forced deactivations
			return true;
		}
		else {
			return $ok;
		}
	}

	/**
	 * Detects whether the plugins that exist have changed since they were last
	 * activated.
	 * @return boolean true if the plugins have changed, false if not.
	 */
	public static function changed_since_last_activation()
	{
		$old_plugins = Options::get( 'plugins_present' );

		// If the plugin list was never stored, then they've changed.
		if ( !is_array( $old_plugins ) ) {
			return true;
		}
		// add base path onto stored path
		foreach ( $old_plugins as $old_plugin ) {
			$old_plugin = HABARI_PATH . $old_plugin;
		}
		// If the file list is not identical, then they've changed.
		$new_plugin_files = Plugins::list_all();
		$old_plugin_files = Utils::array_map_field($old_plugins, 'file');
		if ( count( array_intersect( $new_plugin_files, $old_plugin_files ) ) != count( $new_plugin_files ) ) {
			return true;
		}
		// If the files are not identical, then they've changed.
		$old_plugin_checksums = Utils::array_map_field($old_plugins, 'checksum');
		$new_plugin_checksums = array_map( 'md5_file', $new_plugin_files );
		if ( count( array_intersect( $old_plugin_checksums, $new_plugin_checksums ) ) != count( $new_plugin_checksums ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Stores the list of plugins that are present (not necessarily active) in
	 * the Options table for future comparison.
	 */
	public static function set_present()
	{
		$plugin_files = Plugins::list_all();
		// strip base path
		foreach ( $plugin_files as $plugin_file ) {
			$plugin_file = MultiByte::substr( $plugin_file, MultiByte::strlen( HABARI_PATH ) );
		}

		$plugin_data = array_map( function($a) {return array( 'file' => $a, 'checksum' => md5_file( $a ) ); }, $plugin_files );
		Options::set( 'plugins_present', $plugin_data );
	}

	/**
	 * Verify if a plugin is loaded.
	 * You may supply an optional argument $version as a minimum version requirement.
	 *
	 * @param string $name Name or class name of the plugin to find.
	 * @param string $version Optional minimal version of the plugin.
	 * @return bool Returns true if name is found and version is equal or higher than required.
	 */
	public static function is_loaded( $name, $version = null )
	{
		foreach ( self::$plugins as $plugin ) {
			if ( is_null( $plugin->info ) || $plugin->info == 'broken' || $plugin->info == 'invalid' ) {
				continue;
			}
			if ( MultiByte::strtolower( $plugin->info->name ) == MultiByte::strtolower( $name ) || $plugin instanceof $name || ( isset( $plugin->info->guid ) && MultiByte::strtolower( $plugin->info->guid ) == MultiByte::strtolower( $name ) ) ) {
				if ( isset( $version ) ) {
					if ( isset( $plugin->info->version ) ) {
						return version_compare( $plugin->info->version, $version, '>=' );
					}
					else {
						return $version == null;
					}
				}
				else {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check the PHP syntax of every plugin available, activated or not.
	 *
	 * @see Utils::php_check_file_syntax()
	 * @return bool Returns true if all plugins were valid, return false if a plugin (or more) failed.
	 */
	public static function check_every_plugin_syntax()
	{
		$failed_plugins = array();
		$all_plugins = self::list_all();

		foreach ( $all_plugins as $file ) {
			$error = '';
			if ( !Utils::php_check_file_syntax( $file, $error ) ) {
				Session::error( _t( 'Attempted to load the plugin file "%s", but it failed with syntax errors. <div class="reveal">%s</div>', array( basename( $file ), $error ) ) );
				$failed_plugins[] = $file;
			}
		}

		Options::set( 'failed_plugins', $failed_plugins );
		Plugins::set_present();

		return ( count( $failed_plugins ) > 0 ) ? false : true;
	}

	/**
	 * Produce the UI for a plugin based on the user's selected config option
	 *
	 * @param string $configure The id of the configured plugin
	 * @param $configaction
	 * @internal param string $configuration The selected configuration option
	 */
	public static function plugin_ui( $configure, $configaction )
	{
		Plugins::act_id( 'plugin_ui_' . $configaction, $configure, $configure, $configaction );
		Plugins::act( 'plugin_ui_any_' . $configaction, $configure, $configaction );
		Plugins::act_id( 'plugin_ui', $configure, $configure, $configaction );
		Plugins::act( 'plugin_ui_any', $configure, $configaction );
	}

	/**
	 * Get a list of features and the active plugins that provide that feature
	 * @param null|string $exclude A plugin id to exclude from the results
	 * @param bool $include_inactive Default false.  If true, include inactive plugins in the list.
	 * @param bool $use_file If true, return a filename as the depenedency. If false (default), return the name of the plugin.
	 * @return array An array with keys of the feature name, values are an array of plugin names providing that feature
	 */
	public static function provided($exclude = null, $include_inactive = false, $use_file = false)
	{
		if($include_inactive) {
			$all_plugins = Plugins::list_all();
			$plugin_list = array();
			foreach($all_plugins as $plugin => $plugin_file) {
				$pdata = new \stdClass();
				$pdata->info = self::load_info($plugin_file);
				$pdata->filename = $plugin_file;
				$plugin_list[self::id_from_file($plugin_file)] = $pdata;
			}
		}
		else {
			$plugin_list = Plugins::get_active();
		}

		$provided = array();
		foreach($plugin_list as $plugin_id => $plugin) {
			if($plugin->info->name == $exclude || $plugin_id == $exclude) {
				continue;
			}
			if(isset($plugin->info->provides)) {
				foreach($plugin->info->provides->feature as $provide) {
					if($use_file) {
						$provided[(string)$provide][] = isset(self::$plugin_files[get_class($plugin)]) ? self::$plugin_files[get_class($plugin)] : $plugin->filename;
					}
					else {
						$provided[(string)$provide][] = (string)$plugin->info->name;
					}
				}
			}
		}
		return Plugins::filter( 'provided', $provided );
	}
}

?>
