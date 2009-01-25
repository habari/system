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
	private $_new_rules = array();

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
		// look for help with this
		if( method_exists( $this, 'help') ) {
			Plugins::register( array($this, '_help_plugin_config'), 'filter', 'plugin_config', 8);
			Plugins::register( array($this, '_help_plugin_ui'), 'action', 'plugin_ui', 8);
		}
		// look for a basic configure method
		if( method_exists( $this, 'configure') ) {
			Plugins::register( array($this, '_configure_plugin_config'), 'filter', 'plugin_config', 8);
			Plugins::register( array($this, '_configure_plugin_ui'), 'action', 'plugin_ui', 8);
		}
	}

	/**
	 * Registered to the plugin_config hook to supply help via a plugin's help() method
	 *
	 * @param array $actions An array of actions applicable to this plugin
	 * @param string $plugin_id The plugin id to which the actions belong
	 * @return array The modified array of actions
	 */
	public function _help_plugin_config( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$actions['_help']= _t( '?' );
		}
		return $actions;
	}

	/**
	 * Registered to the plugin_ui hook to supply help via a plugin's help() method
	 *
	 * @param string $plugin_id The id of the plugin whose action was triggered
	 * @param string $action The action triggered
	 */
	public function _help_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() && $action == '_help' ) {
			$output = $this->help();
			if($output instanceof FormUI) {
				$output->out();
			}
			else {
				echo "<div class=\"help\">{$output}</div>";
			}
		}
	}

	/**
	 * Registered to the plugin_config hook to supply a config via a plugin's configure() method
	 *
	 * @param array $actions An array of actions applicable to this plugin
	 * @param string $plugin_id The plugin id to which the actions belong
	 * @return array The modified array of actions
	 */
	public function _configure_plugin_config( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$actions['_configure']= _t( 'Configure' );
		}
		return $actions;
	}

	/**
	 * Registered to the plugin_ui hook to supply a config via a plugin's configure() method
	 *
	 * @param string $plugin_id The id of the plugin whose action was triggered
	 * @param string $action The action triggered
	 */
	public function _configure_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() && $action == '_configure' ) {
			$output = $this->configure();
			if($output instanceof FormUI) {
				$output->out();
			}
			else {
				echo $output;
			}
		}
	}


	/**
	 * Add a rewrite rule that dispatches entirely to a plugin hook
	 *
	 * @param mixed $rule An old-style rewrite rule string, where quoted segments are literals and unquoted segments are variable names, OR a RewriteRule object
	 * @param string $hook The suffix of the hook function: action_plugin_act_{$suffix}
	 */
	public function add_rule($rule, $hook)
	{
		if( count($this->_new_rules) == 0 ) {
			Plugins::register( array($this, '_filter_rewrite_rules'), 'filter', 'rewrite_rules', 7);
		}
		if( $rule instanceof RewriteRule ) {
			$this->_new_rules[] = $rule;
		}
		else {
			$this->_new_rules[] = RewriteRule::create_url_rule($rule, 'PluginHandler', $hook);
		}
	}

	/**
	 * Add the rewrite rules queued by add_rule() to the full rule set
	 *
	 * @param array $rules The array of current RewriteRules
	 * @return array The appended array of RewriteRules
	 */
	public function _filter_rewrite_rules( $rules )
	{
		$rules = array_merge( $rules, $this->_new_rules);
		return $rules;
	}
}

?>