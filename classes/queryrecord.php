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
	private $loaded = false;
	
	public function __construct($paramarray = array())
	{
		$this->loaded = true;

		// Defaults
		$this->fields = array_merge(
			$this->fields,
			Utils::get_params($paramarray)
		);
	}
	
	public function __get($name)
	{
		return isset($this->newfields[$name]) ? $this->newfields[$name] : $this->fields[$name];
	}
	
	public function __set($name, $value)
	{
		if($this->loaded) {
			$this->newfields[$name] = $value;
		}
		else {
			$this->fields[$name] = $value;
		}
	}
	
	public function insert($table)
	{
		global $db;

		$db->insert($table, $this->fields);
	}
	
	public function update($table, $updatekeyfields)
	{
		global $db;

		$qry = "UPDATE {$table} SET";
		$values = array();
		$comma = '';
		foreach($this->fields as $fieldname => $fieldvalue) {
			$qry .= $comma . " {$fieldname} = ?";
			$values[] = isset($this->newfields[$fieldname]) ? $this->newfields[$fieldname] : $fieldvalue;
			$comma = ' ,';
		} 
		$qry .= ' WHERE 1 ';
		foreach($updatekeyfields as $keyfield) {
			$qry .= "AND {$keyfield} = ? ";
			$values[] = $this->fields[$keyfield];
		}
		$db->query($qry, $values);
	}

}

?>
