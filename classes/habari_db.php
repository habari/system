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
		}
		$this->queryok = false;
		$this->errors[] = array_merge($this->dbh->errorInfo(), array($query, $args));
		return false;
	}
	
	/**
	 * function get_errors
	 * Returns error data gathered from database connection
	 */	  	 	
	public function get_errors()
	{
		return $this->errors;
	}
	
	public function has_errors()
	{
		return count($this->errors) > $this->errormarker;
	}
	
	public function clear_errors()
	{
		$this->errormarker = count($this->errors); 
	}

	/**
	 * function get_last_error
	 * Returns only the last error info
	 */	  	 	
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
	 */	 	 	 	 
	public function get_results($query, $args = array(), $classname = '')
	{
		$this->query($query, $args, $classname);
		if($this->queryok) {
			return $this->pdostatement->fetchAll();
		}
		else
			return false;
	}
	
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
	 * function insert
	 * Inserts into the specified table values associated to the key fields
	 * @param string The table name
	 * @param array An associative array of fields and values to insert
	 */
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
	
	public function exists($table, $keyfieldvalues)
	{
		ksort($keyfieldvalues);
		reset($keyfieldvalues);
		$qry = "SELECT " . key($keyfieldvalues) . " FROM {$table} WHERE 1 ";
		foreach($keyfieldvalues as $keyfield => $keyvalue) {
			$qry .= " AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		$result = $this->get_results($qry, $values);
		return is_array($result) && (count($result) > 0);
	}
	
	public function update($table, $fieldvalues, $keyfields)
	{
		ksort($fieldvalues);
		ksort($keyfields);
		
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

}
?>
