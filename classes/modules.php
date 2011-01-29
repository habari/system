<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Modules Class
 *
 * Provides an interface for the code to access modules and module options
 */
class Modules
{
	private static $available_modules = array();
	private static $active_modules = array();
	private static $status_data;

	/**
	 * static initializer to setup base vars.
	 */
	public static function __static()
	{
		self::$available_modules = (array) Plugins::filter( 'dash_modules', array() );
		self::$active_modules = isset( User::identify()->info->dash_modules ) ?  User::identify()->info->dash_modules : array();
		self::setup_status_module();

		// check if we have orphaned active modules.
		foreach ( self::$active_modules as $id => $module_name ) {
			if ( ! in_array( $module_name, self::$available_modules ) ) {
				unset( self::$active_modules[$id] );
				self::commit_active();
			}
		}
	}

	/**
	 * function get_all
	 * returns a list of all available modules
	 * @return array an array of module names
	 */
	public static function get_all()
	{
		return self::$available_modules;
	}

	/**
	 * function get_active
	 * Returns a list of modules currently active on the dashboard, sorted by
	 * display order
	 * @return array Array of modules
	 */
	public static function get_active()
	{
		return self::$active_modules;
	}

	/**
	 * function set_active
	 * Saves the list of active modules to the userinfo table
	 * @param array An associative array of module names with the moduleIDs at keys.
	 */
	public static function set_active( $modules )
	{
		self::$active_modules = $modules;
		self::commit_active();
	}

	/**
	 * function register
	 * Registers a module to make it available in the 'add item' module
	 * @param string $name module name
	 */
	public static function register( $name )
	{
		self::$available_modules[] = $name;
		self::$available_modules = array_unique( self::$available_modules );
		self::commit();
	}

	/**
	 * function unregister
	 * Unregisters a module to remove it from the 'add item' module list
	 * @param string $name module name
	 */
	public static function unregister( $name )
	{
		// remove any instances currently on the dashboard
		Modules::remove_by_name( $name );
		self::$available_modules = array_diff( self::$available_modules, array( $name ) );
		self::commit_active();
		self::commit();
	}

	/**
	 * function add
	 * Adds a module to the user's dashboard. Generate a unique module ID
	 * @param string $module_name the name of the module to add
	 * @return string A unique module id.
	 */
	public static function add( $module_name )
	{
		if ( empty( self::$active_modules ) ) {
			$id = '1';
		}
		else {
			// create a unique id for the module
			$ids = array_keys( self::$active_modules );
			// convert IDs to integers so we can find the max
			foreach ( $ids as $key => $id ) {
				$ids[$key] = (int) $id;
			}
			$id = max( $ids ) + 1;
			$id = '' + $id;
		}

		self::$active_modules[$id] = $module_name;
		self::commit_active();
		return $id;
	}

	/**
	 * function remove
	 * Removes a module from the user's dashboard
	 * @param string $module_id The ID of the module to remove
	 */
	public static function remove( $module_id )
	{
		unset( self::$active_modules[$module_id] );
		self::commit_active();
	}

	/**
	 * function remove_by_name
	 * Removes all modules with a given name from the user's dashboard
	 * @param string $module_name
	 */
	public static function remove_by_name( $module_name )
	{
		foreach ( self::$active_modules as $key => $module ) {
			if ( $module == $module_name ) {
				unset( self::$active_modules[$key] );
			}
		}
		self::commit_active();
	}

	/**
	 * function storage_name ( $module_id, $option )
	 * Gets the storage name for a module option
	 * @param string $module_id the module id
	 * @param string $option the option name
	 * @return string storage name for the module option
	 */
	public static function storage_name( $module_id, $option )
	{
		$module_name = self::$active_modules[$module_id];
		return Utils::slugify( $module_name ) . ':' . $module_id . ':' . $option;
	}

	/**
	 * function set_option
	 * Sets a module option
	 * @param string $module_id
	 * @param string $option
	 * @param mixed $value
	 */
	public static function set_option( $module_id, $option, $value )
	{
		$storage_name = self::storage_name( $module_id, $option );
		$u = User::identify();
		$u->info->$storage_name = $value;
		$u->info->commit();
	}

	/**
	 * function get_option
	 * Gets a module option
	 * @param string $module_id
	 * @param string $option
	 * @return mixed The option value
	 */
	public static function get_option( $module_id, $option )
	{
		$storage_name = self::storage_name( $module_id, $option );
		return User::identify()->info->$storage_name;
	}

	/**
	 * function commit
	 * Saves the available modules list to the options table
	 */
	public static function commit()
	{
		Options::set( 'dash_available_modules', self::$available_modules );
	}

	/**
	 * function commit_active
	 * Saves the active modules list to the userinfo table.
	 */
	public static function commit_active()
	{
		$u = User::identify();
		$u->info->dash_modules = self::$active_modules;
		$u->info->commit();
	}

	/**
	* Retrieve any system status info, if available
	*/
	public static function setup_status_module()
	{
		if ( isset( self::$status_data ) ) {
			return;
		}

		self::$status_data = Plugins::filter( 'dashboard_status', array() );

		if ( count( self::$status_data ) > 0 ) {
			self::$available_modules['Status'] = _t( 'System Status' );
			if ( array_search( _t( 'System Status' ), self::$active_modules ) === false ) {
				self::$active_modules[] = _t( 'System Status' );
			}
			Plugins::register( array( 'Modules', 'filter_dash_module_status' ), 'filter', 'dash_module_system_status' );
		}
	}

	/**
	 * Provide the content for the System Status dashboard module
	 *
	 * @param array $module the module data
	 * @param string $id the id of the module
	 * @param Theme $theme an associated theme for outputting a template
	 * @return array An array of module data
	 */
	public static function filter_dash_module_status( $module, $id, $theme )
	{
		$theme->status_data = self::$status_data;
		$module['content'] = $theme->fetch( 'dash_status' );
		return $module;
	}
}
?>
