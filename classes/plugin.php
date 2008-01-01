<?php
/**
 * Habari Plugin Class
 *
 * Template plugin object which specific plugin objects should extend
 * This object provides the basic constructor used to ensure that
 * plugin actions are registered against the appropriate dispatcher
 *
 * @package Habari
 */

abstract class Plugin extends Pluggable
{
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
}

?>
