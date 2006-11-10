<?php

/**
 * Habari QueryRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class QueryRecord
{
	protected $fields = array();  // Holds field values from db
	protected $newfields = array(); // Holds updated field values to commit to db
	private $loaded = false;  // Set to true after the constructor executes, is false when PDO fills data fields
	
	/**
	 * constructor __construct
	 * Constructor for the QueryRecord class.
	 * @param array an associative array of initial field values.
	 **/	 	 	 	
	public function __construct($paramarray = array())
	{
		$this->loaded = true;

		// Defaults
		$this->fields = array_merge(
			$this->fields,
			Utils::get_params($paramarray)
		);
	}
	
	/**
	 * function __get
	 * Handles getting virtual properties for this class
	 * @param string Name of the property
	 * @return mixed The set value
	 **/	 
	public function __get($name)
	{
		return isset($this->newfields[$name]) ? $this->newfields[$name] : $this->fields[$name];
	}
	
	/**
	 * function __set
	 * Handles setting virtual properties for this class
	 * @param string Name of the property
	 * @param mixed Value to set it to	 
	 * @return mixed The set value 
	 **/	 
	public function __set($name, $value)
	{
		if($this->loaded) {
			$this->newfields[$name] = $value;
		}
		else {
			$this->fields[$name] = $value;
		}
		return $this->__get($name);
	}
	
	/**
	 * function insert
	 * Inserts this record's fields as a new row
	 * @param string Table to update
	 * @return boolean True on success, false if not 
	 **/	 
	public function insert($table)
	{
		global $db;

		return DB::insert($table, array_merge($this->fields, $this->newfields));
	}

	/**
	 * function update
	 * Updates this record's fields using the new data
	 * @param string Table to update
	 * @param array An associative array of field data to match	 	 	 		
	 * @return boolean True on success, false if not 
	 **/	 
	public function update($table, $updatekeyfields = array() )
	{
		global $db;
		
		return DB::update($table, array_merge($this->fields, $this->newfields), $updatekeyfields);
	}
	
	/**
	 * function delete
	 * Deletes a record based on the match array
	 * @param string Table to delete from
	 * @param array An associative array of field data to match	 	 	 		
	 * @return boolean True on success, false if not 
	 **/	 
	public function delete($table, $updatekeyfields)
	{
		global $db;
		
		return DB::delete($table, $updatekeyfields);
	}

}

?>
