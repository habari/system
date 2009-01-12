<?php
/**
 * @package Habari
 *
 */

/**
 * Pluggable class
 * Implements methods that allow descendant classes to register functions to plugin hooks
 *
 * @version $Id$
 * @copyright 2008
 */
abstract class Pluggable
{
	private $_class_name = null;
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
		$this->info = new InfoObject( $this->info() );
		$this->plugin_id = $this->plugin_id();
	}

	/**
	 * Gets the filename that contains this pluggable class
	 * @return string The filename of the file that contains the pluggable class.
	 **/
	final public function get_file()
	{
		if(empty($this->_class_name)) {
			$class = new ReflectionClass( get_class( $this ) );
			$this->_class_name = $class->getFileName();
		}
		return $this->_class_name;
	}

	/**
	 * Gets a database schema associated with this pluggable
	 * @return string The database schema
	 **/
	final public function get_db_schema()
	{
		$db = DB::get_driver_name();
		$schema = dirname($this->get_file()) . '/schema/' . $db . '.sql';
		return file_get_contents($schema);
	}

	/**
	 * Get a fully-qualified URL directory that contains this pluggable class
	 *
	 * @param bool whether to include a trailing slash.  Default: No
	 * @return string URL
	 */
	public function get_url( $trail = false )
	{
		return URL::get_from_filesystem($this->get_file(), $trail);
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
	 * Load a translation domain/file for this pluggable
	 * @return boolean TRUE if data was successfully loaded, FALSE otherwise
	 */
	public function load_text_domain( $domain )
	{
		$base_dir = realpath(dirname( $this->get_file() ));
		
		return HabariLocale::load_pluggable_domain( $domain, $base_dir );
	}

	/**
	 * Called when a pluggable is loaded to register its actions and filters.
	 * Registers all of this pluggables action_ and filter_ functions with the Plugins dispatcher
	 * Registers xmlrpc_ functions with the Plugins dispatcher, and turns '__' into '.'
	 * for the purposes of matching dotted XMLRPC requests.
	 **/
	public function load()
	{
		// combine the array so we can have hooks => function
		$methods = get_class_methods($this);
		$methods = array_combine( $methods, $methods );
		// get the specific priority values for functions, as needed
		if ( method_exists( $this, 'set_priorities' ) ) {
			$priorities = $this->set_priorities();
		}
		// get the aliases.
		if ( method_exists( $this, 'alias' ) ) {
			$methods = array_merge_recursive( $methods, $this->alias() );
		}
		// loop over all the methods in this class
		foreach ( $methods as $fn => $hooks ) {
			// loop hooks and register callback for each
			foreach ( (array) $hooks as $hook ) {
				// make sure the method name is of the form
				// action_foo or filter_foo
				if (
					( 0 !== strpos( $hook, 'action_' ) )
					&& ( 0 !== strpos( $hook, 'filter_' ) )
					&& ( 0 !== strpos( $hook, 'xmlrpc_' ) )
					&& ( 0 !== strpos( $hook, 'theme_' ) )
				) {
					continue;
				}
				$priority = isset($priorities[$hook]) ? $priorities[$hook] :
					( isset($priorities[$fn]) ? $priorities[$fn] : 8 );
				$type = substr( $hook, 0, strpos( $hook, '_' ) );
				$hook = substr( $hook, strpos( $hook, '_' ) + 1 );
				if ( $type === 'xmlrpc' ) {
					$hook = str_replace('__', '.', $hook);
				}
				Plugins::register( array($this, $fn), $type, $hook, $priority );
			}
		}
	}
}

?>
