<?php
/**
 * Habari Modules Class
 *
 * Provides an interface for the code to access modules and module options
 * @package Habari
 */

class Modules
{
	private static $available_modules = array();
	private static $modules = array();
	
	/**
	 * function get_all
	 * returns a list of all available modules
	 * @return array an array of module names
	 */
	public static function get_all()
	{
		if ( empty( self::$available_modules ) ) {
			$modules = Options::get( 'dash_available_modules' );
			self::$available_modules = ( isset( $modules ) ) ? $modules : array();
		}

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
		if ( empty ( self::$modules ) ) {
			$modules = User::identify()->info->dash_modules;
			self::$modules = ( isset( $modules ) ) ? $modules : array();
		}

		return self::$modules;
	}
	
	/**
	 * function set_active
	 * Saves the list of active modules to the userinfo table
	 * @param array An associative array of module names with the moduleIDs at keys.
	 */
	public static function set_active( $modules )
	{
		self::$modules = $modules;
		$u = User::identify();
		$u->info->dash_modules = $modules;
		$u->info->commit();
	}
	
	/**
	 * function register
	 * Registers a module to make it available in the 'add item' module
	 * @param string $name module name
	 */
	public static function register( $name )
	{
		$modules = Modules::get_all();
		$modules[] = $name;
		self::$available_modules = array_unique( $modules );
		Options::set( 'dash_available_modules', self::$available_modules );
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
		
		// remove from available modules list
		$modules = Modules::get_all();
		self::$available_modules = array_diff( $modules, array( $name ) );
		Options::set( 'dash_available_modules', self::$available_modules );
	}
	
	/**
	 * function add
	 * Adds a module to the user's dashboard. Generate a unique module ID
	 * @param string $module_name the name of the module to add
	 * @return string A unique module id.
	 */
	public static function add( $module_name )
	{
		$modules = self::get_active();
		if ( ! isset( $modules ) || empty( $modules) ) {
			$modules = array();
			$id = '1';
		}
		else {
			// create a unique id for the module
			$ids = array_keys( $modules );
			foreach( $ids as $key => $id ) {
				$ids[$key] = (int) $id;
			}
			$id = max( $ids ) + 1;
			$id = '' + $id;
		}

		$modules[$id] = $module_name;
		self::set_active( $modules );
		return $id;
	}
	
	/**
	 * function remove
	 * Removes a module from the user's dashboard
	 * @param string $module_id The ID of the module to remove
	 */
	public static function remove( $module_id )
	{
		$modules = self::get_active();
		unset( $modules[$module_id] );
		self::set_active( $modules );
	}
	
	/**
	 * function remove_by_name
	 * Removes all modules with a given name from the user's dashboard
	 * @param string $module_name
	 */
	public static function remove_by_name( $module_name )
	{
		$modules = self::get_active();
		
		foreach( $modules as $key => $module ) {
			if ( $module == $module_name ) {
				unset( $modules[$key] );
			}
		}
		
		self::set_active( $modules );
	}
	
	/**
	 * function storage_name ( $module_id, $option )
	 * Gets the storage name for a module option
	 * @param string $module_id the module id
	 * @param string $option the option name
	 * @return string storage name for the module option
	 */
	private static function storage_name( $module_id, $option )
	{
		$modules = self::get_active();
		$module_name = $modules[$module_id];
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
}
?>