<?php
/**
 * Habari Options Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */
 
class Options
{
 	const table = 'habari__options';
    static $options;
	
	/**
	 * constructor __construct
	 **/	 	
	public function __construct() 
	{
	}

	/**
	* function o
	* returns a singleton instance of the Options class. Use this to
	* retrieve values of options, like this:
	*
	* <code>
	* $foo = Options::o()->foo;
	* </code>
	*
	* @param string an option name
	* @return object Singleton Options object
	*/
	public static function o()
	{
		if (!isset(self::$options))
		{
			$c = __CLASS__;
			self::$options = new $c;
		}

		return self::$options;
	}

	/**
	* function e
	* echoes the value of an option
	* @param string the name of an option
	*/
	public static function e( $option = null )
	{
		echo self::o()->$option;
	}

	/**
	 * function __get
	 * Allows retrieval of option values
	 * @param string Name of the option to get
	 * @return mixed Stored value for specified option
	 **/	 	 	
	public function __get($name)
	{
		global $db;
		
		if(!isset($options[$name])) {
			$result = $db->get_row("SELECT value, type FROM habari__options WHERE name = ?", array($name));
		
			if ( is_object( $result) ) {
				if($result->type == 1) {
					$options[$name] = unserialize($result->value);
				}
				else {
					$options[$name] = $result->value;
				}
			} else {
				return null;
			}
		}
		return $options[$name];
	}
	
	/**
	 * function __set
	 * Applies the option value to the options table
	 * @param string Name of the option to set
	 * @param mixed Value to set
	 **/	 	 
	public function __set($name, $value) {
		global $db;
		
		self::$options[$name] = $value;
		
		if(is_array($value) || is_object($value)) {
			$db->update( Options::table, array('name'=>$name, 'value'=>serialize($value), 'type'=>1), array('name'=>$name) ); 
		}
		else {
			$db->update( Options::table, array('name'=>$name, 'value'=>$value, 'type'=>0), array('name'=>$name) ); 
		}
	}

}
 
?>
