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

	/**
	 * Loads a theme's metadata from an XML file in theme's
	 * directory.
	 *
	 */
	final public function info()
	{
		static $info;
		if ( !isset( $info ) ) {
			$info = Plugins::load_info( $this->get_file() );
			if ( isset( $info->help ) ) {
				Plugins::register( array( $this, '_help_plugin_config_plugin' ), 'filter', 'plugin_config:' . $this->plugin_id(), 8 );
				Plugins::register( array( $this, '_help_plugin_ui_plugin' ), 'action', 'plugin_ui:' . $this->plugin_id(), 8 );
			}
		}
		return $info;
	}

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
	 * Registered to the plugin_config hook to supply help via a plugin's help in xml
	 *
	 * @param array $actions An array of actions applicable to this plugin
	 * @param string $plugin_id The plugin id to which the actions belong
	 * @return array The modified array of actions
	 */
	public function _help_plugin_config_plugin( $actions, $plugin_id )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			foreach ( $this->info->help as $help ) {
				$name = (string)$help['name'];
				if ( $name == '' ) {
					$name = '_help';
				}
				// @locale Displayed as an icon indicating there is help text available for a plugin.
				$actions[$name] = _t( '?' );
			}
		}
		return $actions;
	}

	/**
	 * Registered to the plugin_ui hook to supply help via a plugin's help in xml
	 *
	 * @param string $plugin_id The id of the plugin whose action was triggered
	 * @param string $action The action triggered
	 */
	public function _help_plugin_ui_plugin( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			foreach ( $this->info->help as $help ) {
				if ( ( $action == (string)$help['name'] && (string)$help['name'] != '' ) || ( $action == '_help' && (string)$help['name'] == '' ) ) {
					echo '<div class="help">' . ( (string)$help->value ) . '</div>';
				}
			}
		}
	}

	/** 
	 * Provide a method to return the version number from the plugin xml
	 * @return string The plugin version from XML
	 **/
	public function get_version()
	{
		return (string)$this->info->version;
	}
}

?>
