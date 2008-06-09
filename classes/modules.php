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
	private static $active_modules = array();
	private static $module_order = array();
	
	/**
	 * static intializer to setup base vars.
	 */
	public function __static()
	{
		self::$available_modules = Options::get( 'dash_available_modules' );
		self::$active_modules = User::identify()->info->dashboard_modules ?  User::identify()->info->dashboard_modules : array();
		
		// check if we have orphaned active modules.
		foreach( array_keys(self::$active_modules) as $module_name ) {
			if ( empty(self::$available_modules[$module_name]) ) {
				unset( self::$active_modules[$module_name] );
				self::commit();
			}
		}
	}
	
	/**
	 * Registers a module to make it available in the 'add item' module
	 * @param string $name module name
	 */
	public function register( $module )
	{
		self::$available_modules[Utils::slugify( $module, '_' )] = $module;
		self::commit();
	}
	
	/**
	 * Unregisters a module to make it available in the 'add item' module
	 * @param string $name module name
	 */

	public function unregister( $module )
	{
		if ( isset( self::$available_modules[Utils::slugify($module)] ) ) {
			unset( self::$available_modules[Utils::slugify($module)] );
		}
		if ( isset( self::$active_modules[Utils::slugify($module)] ) ) {
			unset( self::$active_modules[Utils::slugify($module)] );
		}
		self::commit();
	}
	
	/**
	 * DEPRECATED do not use this. use activate()
	 * @deprecated
	 */
	public static function add(){}
	
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
	 * Adds a module to the user's dashboard. Generate a unique module ID
	 * @param string $module_name the name of the module to add
	 * @return string A unique module id.
	 */
	public static function activate( $module_name )
	{
		if ( isset( self::$available_modules[$module_name] ) ) {
			self::$active_modules = array_unique( 
				array_merge(
					(array) self::$active_modules,
					array( $module_name => self::$available_modules[$module_name] )
					)
				);
			self::commit();
			return true;
		}
		return false;
	}
	
	/**
	 * Removes a module from the user's dashboard
	 * @param string $module_id The ID of the module to remove
	 */
	public static function deactivate( $module_name )
	{
		if ( isset( self::$active_modules[$module_name] ) ) {
			unset( self::$active_modules[$module_name] );
			return true;
		}
		return false;
	}
	
	/**
	 * Save the current state of all modules
	 * @param string $module_id The ID of the module to remove
	 */
	private function commit()
	{
		Options::set( 'dash_available_modules', self::$available_modules );
		$u = User::identify();
		$u->info->dashboard_modules = self::$active_modules;
		$u->info->commit();
	}
	
	/**
	 * Sort modules based on the provided array of "module slug names"
	 * @param string $module_id The ID of the module to remove
	 */
	public static function sort( $order )
	{
		$ordered_modules = array();
		foreach( $order as $module_name ) {
			if ( isset( self::$active_modules[$module_name] ) ) {
				$ordered_modules[$module_name] = self::$active_modules[$module_name];
			}
		}
		self::$active_modules = $ordered_modules;
		self::commit();
	}
	
	/**
	 * Gets the storage name for a module option
	 * @param string $module_id the module id
	 * @param string $option the option name
	 * @return string storage name for the module option
	 */
	private static function storage_name( $module_name, $option )
	{
		return 'dashboard_' . $module_name . '_' . $option;
	}
	
	/**
	 * Sets a module option
	 * @param string $module_id
	 * @param string $option
	 * @param mixed $value
	 */
	public static function set_option( $module_name, $option, $value )
	{
		$storage_name = self::storage_name( $module_name, $option );
		$u = User::identify();
		$u->info->$storage_name = $value;
		$u->info->commit();
	}

	/**
	 * Gets a module option
	 * @param string $module_id
	 * @param string $option
	 * @return mixed The option value
	 */
	public static function get_option( $module_name, $option )
	{
		$storage_name = self::storage_name( $module_name, $option );
		return User::identify()->info->$storage_name;	
	}
	
	/**
	 * Function used to set theme variables to the add module dashboard widget
	 */
	public function add_item_form()
	{
		$form = new FormUI( 'dash_additem' );
		$form->set_option( 'ajax', true );
		$form->set_option( 'form_action', URL::get('admin_ajax', array('context' => 'dashboard')) );
		$form->properties['onsubmit'] = "dashboard.add(); return false;";
		
		$form->append( 'select', 'module', 'null:unused' );
		$form->module->options = self::$available_modules;
		$form->module->id = 'dashboard-add-module';
		$form->append( 'submit', 'submit', _t('Add Item') );
		$form->on_success( create_function( '$form', 'Modules::activate( $form->module->value ); return false;' ) );
		return $form->get();
	}
}
?>
