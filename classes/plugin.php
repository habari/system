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
	final public function info( )
	{
		static $info;
		if(!isset($info)) {
			$xml_file = preg_replace('%\.plugin\.php$%i', '.plugin.xml', $this->get_file());
			if ( file_exists($xml_file) && $xml_content = file_get_contents( $xml_file ) ) {
				$info = new SimpleXMLElement( $xml_content );
				if($info->getName() != 'pluggable') {
					$info = null;
				}					
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

}

?>