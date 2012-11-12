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
	protected $_added_templates = array();

	/**
	 * Pluggable constructor.
	 * This function creates some internal structures that are required for plugin processing
	 * Plugins should not define their own constructors, because they are instantiated
	 * to extract plugin info.  Instead, include a sink for a "init" hook
	 * which is executed immediately after the plugin is loaded during normal execution.
	 */
	public function __construct()
	{
		$this->info = $this->info();
		$this->plugin_id = $this->plugin_id();
	}

	/**
	 * Gets the filename that contains this pluggable class
	 * @return string The filename of the file that contains the pluggable class.
	 */
	final public function get_file()
	{
		if ( empty( $this->_class_name ) ) {
			$class = new ReflectionClass( get_class( $this ) );
			$this->_class_name = $class->getFileName();
		}
		return $this->_class_name;
	}

	/**
	 * Gets a database schema associated with this pluggable
	 * @return string The database schema
	 */
	final public function get_db_schema()
	{
		$db = DB::get_driver_name();
		$schema = dirname( $this->get_file() ) . '/schema/' . $db . '.sql';
		return file_get_contents( $schema );
	}

	/**
	 * Get a fully-qualified URL directory that contains this pluggable class
	 *
	 * @param bool|string $trail If true, include a trailing slash.  If string, append this to the requested url.  Default: Add nothing.
	 * @return string URL
	 */
	public function get_url( $trail = false )
	{
		return URL::get_from_filesystem( $this->get_file(), $trail );
	}

	/**
	 * Returns a unique id for this pluggable
	 * @return string A plugin id
	 */
	final public function plugin_id()
	{
		static $id;
		if ( !isset( $id ) ) {
			$id = Plugins::id_from_file( str_replace( '\\', '/', $this->get_file() ) );
		}
		return $id;
	}

	/**
	 * Load a translation domain/file for this pluggable
	 * @return boolean true if data was successfully loaded, false otherwise
	 */
	public function load_text_domain( $domain )
	{
		$base_dir = realpath( dirname( $this->get_file() ) );

		return HabariLocale::load_pluggable_domain( $domain, $base_dir );
	}
	
	/**
	 * Registers all of this class' action_ and filter_ functions with the Plugins dispatcher
	 * Registers xmlrpc_ functions with the Plugins dispatcher, and turns '__' into '.'
	 *   for the purposes of matching dotted XMLRPC requests.
	 * If the class is an instance of Pluggable, registers the hooks with a plugin id also.
	 * @param mixed $object The object or class name to register the hooks of
	 **/
	public static function load_hooks($object)
	{
		static $registered = array();
		if(is_object($object)) {
			$hash = spl_object_hash($object);
			if(isset($registered[$hash])) {
				return;
			}
			else {
				$registered[$hash] = true;
			}
		}
		else {
			$registered[$object] = true;
		}
		
		// combine the array so we can have hooks => function
		$methods = get_class_methods( $object );
		$methods = array_combine( $methods, $methods );
		// get the specific priority values for functions, as needed
		if ( method_exists( $object, 'set_priorities' ) ) {
			$priorities = $object->set_priorities();
		}
		// get the aliases.
		if ( method_exists( $object, 'alias' ) ) {
			$methods = array_merge_recursive( $methods, $object->alias() );
		}
		// loop over all the methods in this class
		foreach ( $methods as $fn => $hooks ) {
			// loop hooks and register callback for each
			foreach ( (array) $hooks as $hook ) {
				// make sure the method name is of the form
				// action_foo or filter_foo of xmlrpc_foo or theme_foo
				if ( preg_match( '#^(action|filter|xmlrpc|theme|rest)_#i', $hook ) ) {
					$priority = 8;
					if(isset($priorities[$hook])) {
						$priority = $priorities[$hook];
					}
					elseif(preg_match('#^(.+)_(\d+)$#', $hook, $priority_match)) {
						$hook = $priority_match[1];
						$priority = intval($priority_match[2]);
					}
					elseif(isset( $priorities[$fn])) {
						$priority = $priorities[$fn];
					}
					list( $type, $hook ) = explode( '_', $hook, 2 );
					if ( $type === 'xmlrpc' ) {
						$hook = str_replace( '__', '.', $hook );
					}
					if ( $type === 'rest' ) {
						self::add_rest_rule($hook, $object, $fn);
					}
					else {
						Plugins::register( array( $object, $fn ), $type, $hook, $priority );
						if($object instanceof Pluggable) {
							Plugins::register( array( $object, $fn ), $type, $hook . ':' . $object->plugin_id(), $priority );
						}
					}
				}
			}
		}
	}

	/**
	 * Adds a RewriteRule to the REST handler for the rule provided
	 * @param string $hook The hook name to add a RewriteRule for
	 * @param Pluggable $object The pluggable object holding the hook
	 * @param Callable $fn The hook function to use to dispatch the request
	 */
	protected static function add_rest_rule($hook, Pluggable $object, $fn)
	{
		$hook_ary = preg_split('#(?<!_)_#', $hook);

		$verb = array_shift($hook_ary);

		$hook_regex = '/^' . implode(
			'\/',
			array_map(
				function($a){
					if($a[0] === '_')
						return '(?P<' . substr($a, 1) . '>[^\/]+)';
					return $a;
				},
				$hook_ary
			)
		) . '\/?$/i';
		$hook_build = implode(
			'/',
			array_map(
				function($a){
					if($a[0] === '_')
						return '{$' . substr($a, 1) . '}';
					return $a;
				},
				$hook_ary
			)
		);

		$rule = new RewriteRule( array(
			'name' => implode($hook_ary),
			'parse_regex' => $hook_regex,
			'build_str' => $hook_build,
			'handler' => 'RestHandler',
			'action' => 'rest',
			'priority' => 1,
			'is_active' => 1,
			'rule_class' => RewriteRule::RULE_CUSTOM,
			'description' => 'Rule to dispatch REST hook.',
			'parameters' => array(
				'verb' => $verb,
				'hook' => array($object, $fn),
			)
		) );

		$object->add_rule($rule, implode($hook_ary));

	}

	/**
	 * Called when a pluggable is loaded to register its actions and filters.
	 */
	public function load()
	{
		self::load_hooks($this);
		// look for help with this
		if ( method_exists( $this, 'help' ) ) {
			Plugins::register( array( $this, '_help_plugin_config' ), 'filter', 'plugin_config:' . $this->plugin_id(), 8 );
			Plugins::register( array( $this, '_help_plugin_ui' ), 'action', 'plugin_ui:' . $this->plugin_id(), 8 );
		}
		// look for a basic configure method
		if ( method_exists( $this, 'configure' ) ) {
			Plugins::register( array( $this, '_configure_plugin_config' ), 'filter', 'plugin_config:' . $this->plugin_id(), 8 );
			Plugins::register( array( $this, '_configure_plugin_ui' ), 'action', 'plugin_ui:' . $this->plugin_id(), 8 );
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
			// @locale Displayed as an icon indicating there is help text available for a plugin.
			$actions['_help'] = _t( '?' );
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
			if ( $output instanceof FormUI ) {
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
			$actions['_configure'] = _t( 'Configure' );
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
			if ( $output instanceof FormUI ) {
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
	 * #param Callback $fn A potential function/method to register directly to the newly created hook
	 */
	public function add_rule( $rule, $hook, $fn = null )
	{
		if ( count( $this->_new_rules ) == 0 ) {
			Plugins::register( array( $this, '_filter_rewrite_rules' ), 'filter', 'rewrite_rules', 7 );
		}
		if ( $rule instanceof RewriteRule ) {
			$this->_new_rules[] = $rule;
		}
		else {
			$this->_new_rules[] = RewriteRule::create_url_rule( $rule, 'PluginHandler', $hook );
		}
		if(!is_null($fn)) {
			Plugins::register($fn, 'theme', 'route_' . $hook);
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
		$rules = array_merge( $rules, $this->_new_rules );
		return $rules;
	}

	/**
	 * Adds a template to the default theme that is stored in a specified path.
	 * Use this function as a shortcut to make available additional templates to a theme
	 * from within the plugin directory.
	 *
	 * @param string $name The name of the template that will be displayed, sans extension
	 * @param string $filename The full path of the template file used for the specified name
	 * @param boolean $override If false, allow a template with the same name in the active theme directory to override this one.
	 * If true, always override the active theme's template with this one.
	 */
	protected function add_template( $name, $filename, $override = false )
	{
		if ( count( $this->_added_templates ) == 0 ) {
			Plugins::register( array( &$this, '_plugin_available_templates' ), 'filter', 'available_templates' );
			Plugins::register( array( &$this, '_plugin_include_template_file' ), 'filter', 'include_template_file' );
		}

		$this->_added_templates[$name] = array( $filename, $override );
	}

	/**
	 * Add plugin templates to the list of templates that are present in the current theme
	 *
	 * @param array $list List of template names in the current theme
	 * @return array The modified list of template names
	 */
	public function _plugin_available_templates( $list )
	{
		$list = array_merge( $list, array_keys( $this->_added_templates ) );
		return $list;
	}

	/**
	 * Potentially serve a different file for the requested template name
	 *
	 * @param string $file The filename of the template the theme will display
	 * @param string $name The name of the template requested
	 * @return string The potentially modified filename to use for the requested template.
	 */
	public function _plugin_include_template_file( $file, $name )
	{
		if ( isset( $this->_added_templates[$name] ) ) {
			if ( $this->_added_templates[$name][1] || !file_exists( $file ) ) {
				$file = $this->_added_templates[$name][0];
			}
		}
		return $file;
	}

	/** 
	 * Provide a method to return the version number from a pluggable's info
	 * @return string The version of the pluggable
	 **/
	public abstract function get_version();
	
	/**
	 * Execute the upgrade action on any pluggable that has a version number change
	 * Update the version number of the pluggable in the database to what is installed
	 */
	public function upgrade()
	{
		if(DB::is_connected() && @ Options::get( 'installed' )) {
			$pluggable_class = get_class($this);
			$versions = Options::get( 'pluggable_versions' );
			if(isset($versions[$pluggable_class])) {
				$old_version = $versions[$pluggable_class];
				if($old_version != $this->get_version()) {
					Plugins::act_id('upgrade', $this->plugin_id(), $old_version);
					$versions[$pluggable_class] = $this->get_version();
					Options::set( 'pluggable_versions', $versions );
				}
			}
			else {
				$versions[$pluggable_class] = $this->get_version();
				Options::set( 'pluggable_versions', $versions );
			}
		}
	}
}

?>
