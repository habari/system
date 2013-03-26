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
	private $properties_loaded = array(); // Set [$name] to true after first load of properties

	/**
	 * constructor __construct
	 * Constructor for the QueryRecord class.
	 * @param array an associative array of initial field values.
	 */
	public function __construct( $paramarray = array() )
	{
		$params = Utils::get_params( $paramarray );
		if ( count( $params ) ) {
			// Defaults
			$this->fields = array_merge(
				$this->fields,
				$params
			);
			// mark any passed params as loaded when creating this object
			$this->properties_loaded = array_merge(
				$this->properties_loaded,
				array_combine( array_keys( $params ), array_fill( 0, count( $params ), true ) )
			);
		}
	}

	/**
	 * function __get
	 * Handles getting virtual properties for this class
	 * @param string Name of the property
	 * @return mixed The set value or null if none exists
	 **/
	public function __get( $name )
	{
		$return = null;
		if ( isset( $this->newfields[$name] ) ) {
			$return = $this->newfields[$name];
		}
		else if ( isset( $this->fields[$name] ) ) {
			$return = $this->fields[$name];
		}
		$classname = strtolower(get_class($this));
		return Plugins::filter('get_' . $classname . '_' . $name, $return, $this);
	}

	/**
	 * function __set
	 * Handles setting virtual properties for this class
	 * @param string Name of the property
	 * @param mixed Value to set it to
	 * @return mixed The set value
	 **/
	public function __set( $name, $value )
	{
		$classname = strtolower(get_class($this));
		$hook = 'set_' . $classname . '_' . $name;
		if(Plugins::implemented($hook, 'action')) {
			return Plugins::act('set_' . $classname . '_' . $name, $value, $this);
		}
		if ( isset( $this->properties_loaded[$name] ) ) {
			$this->newfields[$name] = $value;
		}
		else {
			$this->fields[$name] = $value;
			$this->properties_loaded[$name] = true;
		}
		return $value;
	}

	/**
	* Magic isset for QueryRecord, returns whether a property value is set.
	* @param string $name The name of the parameter
	* @return boolean True if the value is set, false if not
	*/
	public function __isset( $name )
	{
		return ( isset( $this->newfields[$name] ) || isset( $this->fields[$name] ) );
	}

	/**
	* Registers a (list of) fields(s) as being managed exclusively by the database.
	* @param mixed A database field name (string) or an array of field names
	*/
	public function exclude_fields( $fields )
	{
		if ( is_array( $fields ) ) {
			$this->unsetfields = array_flip( $fields );
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
	 * @param string Table to update, use table name without prefix and without braces
	 * @return integer The inserted record id on success, false if not
	 *
	 * Again, the parent class's method's signature must match that of the
	 * child class's signature
	 */
	protected function insertRecord( $table, $schema = null )
	{
		$merge = array_merge( $this->fields, $this->newfields );
		$ptable = DB::table($table);
		if(empty($schema)) {
			$result = DB::insert( $ptable, array_diff_key( $merge, $this->unsetfields ) );
			if($result) {
				$result = DB::last_insert_id();
			}
		}
		else {
			$result = false;
			if(DB::insert( $ptable, array_intersect_key(array_diff_key( $merge, $this->unsetfields ), $schema[$table]) )) {
				$result = true;
				$record_id = DB::last_insert_id();
				$merge['*id'] = $record_id;
				foreach($schema as $schema_table => $fields) {
					if($schema_table == '*' || $table == $schema_table) {
						continue;
					}
					$data = array();
					foreach($fields as $field => $value) {
						$data[$field] = $merge[$value];
					}
					$pschema_table = DB::table($schema_table);
					$result = $result && DB::insert( $pschema_table, array_intersect_key(array_diff_key($data, $this->unsetfields), $fields));
				}
			}
			if($result) {
				$result = $record_id;
			}
		}
		return $result;
	}

	/**
	 * function to_array
	 * Returns an array with the current field settings
	 * @return array The field settings as they would be saved
	 */
	public function to_array()
	{
		return array_merge( $this->fields, $this->newfields );
	}

	/**
	 * Convert record data to json
	 * Returns a string with the current field values in JSON format
	 * @return string The field settings as they would be saved in JSON
	 */
	public function to_json()
	{
		return $this->jsonSerialize();
	}

	/**
	 * Implements JsonSerializable, only available in PHP 5.4  :(
	 * @return string
	 */
	public function jsonSerialize()
	{
		$array = array_merge( $this->fields, $this->newfields );
		$array = Plugins::filter('queryrecord_to_json', $array, $this);
		return json_encode($array);
	}

	/**
	 * Returns an array with the current field settings
	 * @return array The field settings as they would be saved
	 */
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
	 * @param string Table to update, use table name without prefix and without braces
	 * @param array An associative array of field data to match
	 * @return boolean True on success, false if not
	 */
	protected function updateRecord( $table, $updatekeyfields = array(), $schema = null )
	{
		$merge = array_merge( $this->fields, $this->newfields );
		$ptable = DB::table($table);
		if(empty($schema)) {
			$result = DB::update( $ptable, array_diff_key( $merge, $this->unsetfields ), $updatekeyfields );
		}
		else {
			$result = false;
			if($result = DB::update( $ptable, array_intersect_key(array_diff_key( $merge, $this->unsetfields ), $schema[$table]), $updatekeyfields )) {
				foreach($updatekeyfields as $kf => $kd) {
					$merge['*'.$kf] = $kd;
				}
				foreach($schema as $schema_table => $fields) {
					if($schema_table == '*' || $table == $schema_table) {
						continue;
					}
					$data = array();
					$updatedata = array();
					foreach($fields as $field => $value) {
						if($value[0] == '*') {
							$updatedata[$field] = $merge[$value];
						}
						else {
							$data[$field] = $merge[$value];
						}
					}
					$pschema_table = DB::table($schema_table);
					$result = $result && DB::update( $pschema_table, array_intersect_key(array_diff_key($data, $this->unsetfields), $fields), $updatedata);
				}
			}
		}
		return $result;
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
	 */
	protected function deleteRecord( $table, $updatekeyfields )
	{
		return DB::delete( DB::table($table), $updatekeyfields );
	}

	/**
	 * This is the public interface to update a record with an array
	 */
	public function modify( $paramarray = array() )
	{
		$this->newfields = array_merge( $this->newfields, $paramarray );
	}
}

?>
