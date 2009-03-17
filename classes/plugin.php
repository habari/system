<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Plugin Class
 *
 * Template plugin object which specific plugin objects should extend
 * This object provides the basic constructor used to ensure that
 * plugin actions are registered against the appropriate dispatcher
 *
 */
abstract class Plugin extends Pluggable
{
	private $_added_templates = array();

	/**
	 * Returns information about this plugin
	 * @return array An associative array of information about this plugin
	 **/
	abstract public function info();

	/**
	 * Plugin constructor.
	 * Plugins should not define their own constructors, because they are instantiated
	 * to extract plugin info.  Instead, include a sink for a "init" hook
	 * which is executed immediately after the plugin is loaded during normal execution.
	 **/
	final public function __construct()
	{
		parent::__construct();
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
	protected function add_template($name, $filename, $override = false)
	{
		if(count($this->_added_templates) == 0) {
			Plugins::register(array(&$this, '_plugin_available_templates'), 'filter', 'available_templates');
			Plugins::register(array(&$this, '_plugin_include_template_file'), 'filter', 'include_template_file');
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
		$list = array_merge($list, array_keys($this->_added_templates));
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
		if(isset($this->_added_templates[$name])) {
			if($this->_added_templates[$name][1] || !file_exists($file)) {
				$file = $this->_added_templates[$name][0];
			}
		}
		return $file;
	}

}

?>
