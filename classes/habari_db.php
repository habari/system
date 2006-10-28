<?php
/**
 * Habari DB Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

/**
 * class habari_db
 * The database class - singleton
 * Connects to the database and provides access to data 
 */  
class habari_db
{
	private $dbh;  // Database handle
	private $pdostatement = false;  // PDOStatement handle
	private $errors = array(); // Array of SQL errors 
	public $queryok; // Boolean on last query success 
	private $queries = array(); // Array of executed queries
	private $errormarker = 0; // Last cleared error index

	/**
	 * function __construct
	 * Connects to a PDO database.	 
	 * @param string A PDO connection string
	 * @param string The database username
	 * @param string The database password
	 */	 	 	
	public function __construct($connection_string, $user, $pass) 
	{
		$this->dbh = new PDO($connection_string, $user, $pass);
	}

	/**
	 * function query
	 * Executes a query and returns success
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @param string Optional class name for row result objects	 
	 * @return boolean True on success, false if not	 
	 */	 	 	 	 	
	public function query($query, $args = array(), $c_name = '')
	{
		if($this->pdostatement) $this->pdostatement->closeCursor();
		$this->pdostatement = $this->dbh->prepare($query);
		if($this->pdostatement) {
			if($c_name == '') $c_name = 'QueryRecord';
			$this->pdostatement->setFetchMode(PDO::FETCH_CLASS, $c_name, array());
			if($this->pdostatement->execute($args)) {
				$this->queries[] = array($query, $args);
				$this->queryok = true;
				return true;
			}
			else {
				$this->queryok = false;
				$this->errors[] = array_merge($this->pdostatement->errorInfo(), array($query, $args));
				return false;
			}
		}
		$this->queryok = false;
		$this->errors[] = array_merge($this->dbh->errorInfo(), array($query, $args));
		return false;
	}
	
	/**
	 * function get_errors
	 * Returns error data gathered from database connection
	 * @return array An array of error data	 
	 */	  	 	
	public function get_errors()
	{
		return $this->errors;
	}
	
	/**
	 * function has_errors
	 * Determines if there have been errors since the last clear_errors() call
	 * @return boolean True if there were errors, false if not
	 **/	 	 	 	
	public function has_errors()
	{
		return count($this->errors) > $this->errormarker;
	}
	
	/**
	 * function clear_errors
	 * Updates the last error pointer to simulate resetting the error array
	 **/	 	 	
	public function clear_errors()
	{
		$this->errormarker = count($this->errors); 
	}

	/**
	 * function get_last_error
	 * Returns only the last error info
	 * @return array Data for the last error	 
	 **/
	public function get_last_error()
	{
		return end($this->errors);
	}

	/**
	 * function get_results
	 * Execute a query and return the results as an array of objects
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @param string Optional class name for row result objects	 
	 * @return array An array of QueryRecord or the named class each containing the row data
	 * <code>$ary = $db->get_results( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>
	 **/	 	 	 	 
	public function get_results($query, $args = array(), $classname = '')
	{
		$this->query($query, $args, $classname);
		if($this->queryok) {
			return $this->pdostatement->fetchAll();
		}
		else
			return false;
	}
	
	/**
	 * function get_row
	 * Returns a single row (the first in a multi-result set) object for a query
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @param string Optional class name for row result object
	 * @return object A QueryRecord or an instance of the named class containing the row data	 
	 * <code>$obj = $db->get_row( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>	 
	 **/	 	 
	public function get_row($query, $args = array(), $classname = '')
	{
		$this->query($query, $args, $classname);
		if($this->queryok) {
			return $this->pdostatement->fetch();
		}
		else
			return false;
	}
	
	/**
	 * function get_column
	 * Returns all values for a column for a query
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return array An array containing the column data	 
	 * <code>$ary = $db->get_column( 'SELECT col1 FROM tablename WHERE foo = ?', array('fieldvalue') );</code>	 
	 **/	 	 
	public function get_column($query, $args = array())
	{
		$this->query($query, $args);
		if($this->queryok) {
			return $this->pdostatement->fetchAll(PDO::FETCH_COLUMN);
		}
		else
			return false;
	}
	
	/**
	 * function insert
	 * Inserts into the specified table values associated to the key fields
	 * @param string The table name
	 * @param array An associative array of fields and values to insert
	 * @return boolean True on success, false if not	  	 
	 * <code>$db->insert( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/
	public function insert($table, $fieldvalues)
	{
		ksort($fieldvalues);

		$query = "INSERT INTO {$table} (";
		$comma = '';
		
		foreach($fieldvalues as $field => $value) {
			$query .= $comma . $field;
			$comma = ', ';
			$values[] = $value;
		}
		$query .= ') VALUES (' . trim(str_repeat('?,', count($fieldvalues)), ',') . ');';

		return $this->query($query, $values);
	}
	
	/**
	 * function exists
	 * Checks for a record that matches the specific criteria
	 * A new row is inserted if no existing record matches the criteria	 
	 * @param string Table to check
	 * @param array Associative array of field values to match
	 * @return boolean True if any matching record exists, false if not
	 * <code>$db->exists( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/	 
	public function exists($table, $keyfieldvalues)
	{
		ksort($keyfieldvalues);
		reset($keyfieldvalues);
		$qry = "SELECT " . key($keyfieldvalues) . " FROM {$table} WHERE 1 ";

        $values = array();
		foreach($keyfieldvalues as $keyfield => $keyvalue) {
			$qry .= " AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		$result = $this->get_results($qry, $values);
		return is_array($result) && (count($result) > 0);
	}
	
	/**
	 * function update
	 * Updates any record that matches the specific criteria
	 * @param string Table to update
	 * @param array Associative array of field values to set	 
	 * @param array Associative array of field values to match
	 * @return boolean True on success, false if not
	 * <code>$db->update( 'mytable', array( 'fieldname' => 'newvalue' ), array( 'fieldname' => 'value' ) );</code>	 
	 **/	 
	public function update($table, $fieldvalues, $keyfields)
	{
		ksort($fieldvalues);
		ksort($keyfields);

        $keyfieldvalues = array();
		foreach($keyfields as $keyfield => $keyvalue) {
			if(is_numeric($keyfield)) {
				$keyfieldvalues[$keyvalue] = $fieldvalues[$keyvalue];
			}
			else {
				$keyfieldvalues[$keyfield] = $keyvalue;
			}
		}
		if($this->exists($table, $keyfieldvalues)) {
			$qry = "UPDATE {$table} SET";
			$values = array();
			$comma = '';
			foreach($fieldvalues as $fieldname => $fieldvalue) {
				$qry .= $comma . " {$fieldname} = ?";
				$values[] = $fieldvalue;
				$comma = ' ,';
			} 
			$qry .= ' WHERE 1 ';
			
			foreach($keyfields as $keyfield => $keyvalue) {
				$qry .= "AND {$keyfield} = ? ";
				$values[] = $keyvalue;
			}
			return $this->query($qry, $values);
		}
		else {
			return $this->insert($table, $fieldvalues);
		}
	}

	/**
	 * function delete
	 * Deletes any record that matches the specific criteria
	 * @param string Table to delete from
	 * @param array Associative array of field values to match
	 * @return boolean True on success, false if not
	 * <code>$db->delete( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/	 
	public function delete( $table, $keyfields )
	{
		ksort( $keyfields );
		
		$qry = "DELETE FROM {$table} WHERE 1 ";
		foreach ( $keyfields as $keyfield => $keyvalue ) {
			$qry .= "AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		
		return $this->query( $qry, $values );
	}

}
?>
