<?php

/**
 * Habari QueryRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class QueryRecord implements URLProperties
{
	protected $fields = array();  // Holds field values from db
	protected $newfields = array(); // Holds updated field values to commit to db
	protected $unsetfields = array(); // Holds field names to remove when committing to the db
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
	* Magic isset for QueryRecord, returns whether a property value is set.
	* @param string $name The name of the parameter
	* @return boolean True if the value is set, false if not
	*/	 	 	 	
	public function __isset($name)
	{
		return ( isset( $this->newfields[$name] ) || isset( $this->fields[$name] ) );
	}

	/**
	* function exclude_fields
	* Registers a (list of) fields(s) as being managed exclusively by the database.
	* @param mixed A database field name (string) or an array of field names
	*/
	public function exclude_fields( $fields )
	{
		if(is_array($fields)) 
		{
			$this->unsetfields = array_flip($fields);
		}
		else
		{
			$this->unsetfields[$fields] = $fields;
		}
	}

	/**
	* public function list_excluded_fields
	* returns an array of fields that should not be included in any database insert operation
	* @return array an array of field names
	*/
	public function list_excluded_fields()
	{
		return $this->unsetfields;
	}
	
	/**
	 * function insert
	 * Inserts this record's fields as a new row
	 * @param string Table to update
	 * @return boolean True on success, false if not 
	 **/	 
	public function insert($table)
	{
		$merge =  array_merge($this->fields, $this->newfields);
		return DB::insert($table, array_diff_key($merge, $this->unsetfields));
	}
	
	/**
	 * function to_array
	 * Returns an array with the current field settings
	 * @return array The field settings as they would be saved
	 **/
	public function to_array()
	{
		return array_merge($this->fields, $this->newfields);
	}	 

	/**
	 * Returns an array with the current field settings
	 * @return array The field settings as they would be saved
	 **/
	public function get_url_args()
	{
		return $this->to_array();
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
		$merge = array_merge($this->fields, $this->newfields);
		return DB::update($table, array_diff_key($merge, $this->unsetfields), $updatekeyfields);
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
