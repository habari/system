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
	
	private $options = array();

	/**
	 * constructor __construct
	 **/	 	
	public function __construct() 
	{
		// Preload some options here?
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
		
		if(!isset($this->options[$name])) {
			$result = $db->get_row("SELECT value, type FROM habari__options WHERE name = ?", array($name));
			
			if($result->type == 1) {
				$this->options[$name] = unserialize($result->value);
			}
			else {
				$this->options[$name] = $result->value;
			}
		}
		return $this->options[$name];
	}
	
	/**
	 * function __set
	 * Applies the option value to the options table
	 * @param string Name of the option to set
	 * @param mixed Value to set
	 **/	 	 
	public function __set($name, $value) {
		global $db;
		
		$this->options[$name] = $value;
		
		if(is_array($value) || is_object($value)) {
			$db->update( Options::table, array('name'=>$name, 'value'=>serialize($value), 'type'=>1), array('name'=>$name) ); 
		}
		else {
			$db->update( Options::table, array('name'=>$name, 'value'=>$value, 'type'=>0), array('name'=>$name) ); 
		}
	}

}
 
?>
