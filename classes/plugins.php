<?php
/**
 * Habari Plugins Class
 *
 * Provides an interface for the code to access plugins 
 * @package Habari
 */

class Plugins
{
	private $instance = null;
	private $hooks;

	/**
	 * function __construct
	 * Plugins class constructor.  Singleton
	 **/	 	 	
	private function __construct()
	{
		$this->hooks = array(
			'do'=>array(),
			'filter'=>array(),
		);
	}
	
	/**
	 * function instantiate
	 * Creates the private instance.  Do not call directly, called by do() and filter()
	 **/	 
	static private function instantiate()
	{
		if(self::$instance == null) {
			$c = __CLASS__;
			self::$instance = new $c(); 
		}
	}
	
	/**
	 * function do
	 * Call to execute a plugin action
	 **/	 	 	
	static public function do()
	{
		self::instantiate();
		$args = func_get_args();
		$hookname = array_shift($args);
		foreach((array)self::$instance->hooks['do'][$hookname] as $hookfn) {
			
		}
	}

	/**
	 * function filter
	 * Call to execute a plugin filter
	 **/	 
	static public function filter()
	{
		self::instantiate();
	}

}

?>
