<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php
/**
 * @package Habari
 *
 */

/**
 * Habari QueryRecord Class
 *
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
	 * @return mixed The set value or NULL if none exists
	 **/
	public function __get($name)
	{
		if ( isset( $this->newfields[$name] ) ) {
			return $this->newfields[$name];
		}
		else if ( isset( $this->fields[$name] ) ) {
			return $this->fields[$name];
		}
		else {
			return NULL;
		}
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
		return $value;
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
	* Registers a (list of) fields(s) as being managed exclusively by the database.
	* @param mixed A database field name (string) or an array of field names
	*/
	public function exclude_fields( $fields )
	{
		if(is_array($fields))
		{
			$this->unsetfields = array_flip($fields);
		}
		else {
			$this->unsetfields[$fields] = $fields;
		}
	}

	/**
	* returns an array of fields that should not be included in any database insert operation
	* @return array an array of field names
	*/
	public function list_excluded_fields()
	{
		return $this->unsetfields;
	}

	/**
	 * This is the public interface that inserts a record
	 */
	public function insert()
	{
		return null;
	}
	
	/**
	 * function insertRecord(
	 * Inserts this record's fields as a new row
	 * @param string Table to update
	 * @return boolean True on success, false if not
	 **/
	/*
	 * Again, the parent class's method's signature must match that of the
	 * child class's signature
	 */
	protected function insertRecord($table)
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
	 * This is the public interface that updates a record
	 */
	public function update()
	{
		return null;
	}
	/**
	 * function updateRecord
	 * Updates this record's fields using the new data
	 * @param string Table to update
	 * @param array An associative array of field data to match
	 * @return boolean True on success, false if not
	 **/
	protected function updateRecord($table, $updatekeyfields = array() )
	{
		$merge = array_merge($this->fields, $this->newfields);
		return DB::update($table, array_diff_key($merge, $this->unsetfields), $updatekeyfields);
	}

	/**
	 * This is the public interface that deletes a record
	 */
	public function delete()
	{
		return null;
	}
	/**
	 * function deleteRecord
	 * Deletes a record based on the match array
	 * @param string Table to delete from
	 * @param array An associative array of field data to match
	 * @return boolean True on success, false if not
	 **/
	protected function deleteRecord($table, $updatekeyfields)
	{
		return DB::delete($table, $updatekeyfields);
	}

}

?>
