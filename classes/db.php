<?php
/**
 * Habari DB Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

/**
 * class db
 * The database class - singleton
 * Connects to the database and provides access to data 
 */  
class DB
{
	static $instance = null;  // A static instance of this class
	private $dbh;  // Database handle
	private $pdostatement = false;  // PDOStatement handle
	private $errors = array(); // Array of SQL errors 
	private $queries = array(); // Array of executed queries
	private $errormarker = 0; // Last cleared error index
	private $tables; // an array of table names that Habari knows
	public $queryok; // Boolean on last query success
	public $prefix; // the database prefix for all tables

	/**
	 * function __construct
	 * Connects to a PDO database.	 
	 * @param string A PDO connection string
	 * @param string The database username
	 * @param string The database password
	 */	 	 	
	public function __construct($connection_string, $user, $pass, $prefix) 
	{
		$this->dbh = new PDO($connection_string, $user, $pass);
		$this->prefix = $prefix;
		foreach (array('posts', 'postinfo', 'posttype','poststatus','options', 'users','userinfo', 'tags', 'comments','commentinfo','tag2post','themes') as $table) {
			$this->tables[$table] = $this->prefix . $table;
		}
	}

	/**
	 * function __get
	 * Returns a $db property if defined, or false
	 * @param string Name of a property to return
	 * @return mixed The requested field value
	**/
	public function __get( $name )
	{
		if ( isset( $this->tables[$name] ) ) {
			return $this->tables[$name];
		}
		return false;
	}
	
	/**
	 * function o
	 * Gets the static instance if the function was called statically,
	 * or $this if the function was called dynamically.
	 * @return DB The database instance
	 **/	 	 	 	 	
	public function &o()
	{
		if ( isset ($this) && get_class($this) == ((string)__CLASS__)) {
			return $this;
		}
		return DB::$instance;
	}
	
	/**
	 * function create
	 * Connects the static instance to a PDO database.	 
	 * @param string A PDO connection string
	 * @param string The database username
	 * @param string The database password
	 */	 	 	
	static function create($connection_string, $user, $pass, $prefix)
	{
		if ( empty( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c( $connection_string, $user, $pass, $prefix );
		}
		return self::$instance;
	}

	/**
	 * function register_table
	 * Adds a table to the list of tables known to Habari.
	 * @param string A table name
	 * @oaram string A prefix for the table name.  Can be null.
	**/
	public function register_table( $name, $prefix )
	{
		if ( $name )
		{
			$this->tables[$name] = $prefix . $name;
		}
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
		$t = microtime(true);
		$o =& DB::o();
		if($o->pdostatement) $o->pdostatement->closeCursor();
		$o->pdostatement = $o->dbh->prepare($query);
		if($o->pdostatement) {
			if($c_name == '') $c_name = 'QueryRecord';
			$o->pdostatement->setFetchMode(PDO::FETCH_CLASS, $c_name, array());
			if($o->pdostatement->execute($args)) {
				$o->queries[] = array($query, $args, array($t, microtime(true)));
				$o->queryok = true;
				return true;
			}
			else {
				$o->queryok = false;
				$o->errors[] = array_merge($o->pdostatement->errorInfo(), array($query, $args));
				return false;
			}
		}
		$o->queryok = false;
		$o->errors[] = array_merge($o->dbh->errorInfo(), array($query, $args));
		return false;
	}
	
	/**
	 * function record_time
	 * Add a start and stop time to the query record to help determine execution time
	 * @param float Microtime of start
	 * @param float Microtime of stop
	 **/	 	 	 	 	
	public function record_time($start, $stop)
	{
		if(is_string($start)) {
			$start = (double)substr( $start, 11 ) + (double)substr( $start, 0, 8 );
		}
		if(is_string($stop)) {
			$stop = (double)substr( $stop, 11 ) + (double)substr( $stop, 0, 8 );
		}
		$o =& DB::o();
		$qrec &= end($o->queries);
		$qrec[] = array($start, $stop);
	}
	
	/**
	 * function calc_time
	 * Calculates query execution time using a query record
	 * @param array A query record from DB::o()->queries
	 * @returns float Seconds of execution
	 **/
	public function calc_time($queryrecord)
	{
		$times = array_slice($queryrecord, 2);
		$total = 0;
		foreach($times as $time) {
			$total += $time[1] - $time[0];
		}
		return $total;
	}
	
	/**
	 * function calc_query_time
	 * Calculates the execution time of all executed queries
	 * @returns array Seconds of execution for each query
	 **/
	public function calc_query_time()
	{
		$o =& DB::o();
		$times = array();
		foreach($o->queries as $query) {
			$times[] = DB::calc_time($query);
		}
		return $times;
	}	 	 	 	 	
	
	/**
	 * function get_errors
	 * Returns error data gathered from database connection
	 * @return array An array of error data	 
	 */	  	 	
	public function get_errors()
	{
		$o =& DB::o();
		return $o->errors;
	}
	
	/**
	 * function has_errors
	 * Determines if there have been errors since the last clear_errors() call
	 * @return boolean True if there were errors, false if not
	 **/	 	 	 	
	public function has_errors()
	{
		$o =& DB::o();
		return count($o->errors) > $o->errormarker;
	}
	
	/**
	 * function clear_errors
	 * Updates the last error pointer to simulate resetting the error array
	 **/	 	 	
	public function clear_errors()
	{
		$o =& DB::o();
		$o->errormarker = count($o->errors); 
	}

	/**
	 * function get_last_error
	 * Returns only the last error info
	 * @return array Data for the last error	 
	 **/
	public function get_last_error()
	{
		$o =& DB::o();
		return end($o->errors);
	}

	/**
	 * function get_results
	 * Execute a query and return the results as an array of objects
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @param string Optional class name for row result objects	 
	 * @return array An array of QueryRecord or the named class each containing the row data
	 * <code>$ary = DB::get_results( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>
	 **/	 	 	 	 
	public function get_results($query, $args = array(), $classname = '')
	{
		$o =& DB::o();
		$o->query($query, $args, $classname);
		if($o->queryok) {
			return $o->pdostatement->fetchAll();
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
	 * <code>$obj = DB::get_row( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>	 
	 **/	 	 
	public function get_row($query, $args = array(), $classname = '')
	{
		$o =& DB::o();
		$o->query($query, $args, $classname);
		if($o->queryok) {
			return $o->pdostatement->fetch();
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
	 * <code>$ary = DB::get_column( 'SELECT col1 FROM tablename WHERE foo = ?', array('fieldvalue') );</code>	 
	 **/	 	 
	public function get_column($query, $args = array())
	{
		$o =& DB::o();
		$o->query($query, $args);
		if($o->queryok) {
			return $o->pdostatement->fetchAll(PDO::FETCH_COLUMN);
		}
		else
			return false;
	}

	/**
	 *
	 * function get_value
	 * Return a single value from the database
	 * @param string the query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return mixed a single value (int, string)
	**/
	public function get_value( $query, $args = array() )
	{
		$o =& DB::o();
		$o->query($query, $args);
		if ( $o->queryok )
		{
			$result = $o->pdostatement->fetch(PDO::FETCH_NUM);
			return $result[0];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * function insert
	 * Inserts into the specified table values associated to the key fields
	 * @param string The table name
	 * @param array An associative array of fields and values to insert
	 * @return boolean True on success, false if not	  	 
	 * <code>DB::insert( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/
	public function insert($table, $fieldvalues)
	{
		$o =& DB::o();
		ksort($fieldvalues);

		$query = "INSERT INTO {$table} (";
		$comma = '';
		
		foreach($fieldvalues as $field => $value) {
			$query .= $comma . $field;
			$comma = ', ';
			$values[] = $value;
		}
		$query .= ') VALUES (' . trim(str_repeat('?,', count($fieldvalues)), ',') . ');';

		return $o->query($query, $values);
	}
	
	/**
	 * function exists
	 * Checks for a record that matches the specific criteria
	 * @param string Table to check
	 * @param array Associative array of field values to match
	 * @return boolean True if any matching record exists, false if not
	 * <code>DB::exists( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/	 
	public function exists($table, $keyfieldvalues)
	{
		$o =& DB::o();
		ksort($keyfieldvalues);
		reset($keyfieldvalues);
		$qry = "SELECT 1 as c FROM {$table} WHERE 1 ";

		$values = array();
		foreach($keyfieldvalues as $keyfield => $keyvalue) {
			$qry .= " AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		$result = $o->get_row($qry, $values);
		return ($result !== false);
	}
	
	/**
	 * function update
	 * Updates any record that matches the specific criteria
	 * A new row is inserted if no existing record matches the criteria	 
	 * @param string Table to update
	 * @param array Associative array of field values to set	 
	 * @param array Associative array of field values to match
	 * @return boolean True on success, false if not
	 * <code>DB::update( 'mytable', array( 'fieldname' => 'newvalue' ), array( 'fieldname' => 'value' ) );</code>	 
	 **/	 
	public function update($table, $fieldvalues, $keyfields)
	{
		$o =& DB::o();
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
		if($o->exists($table, $keyfieldvalues)) {
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
			return $o->query($qry, $values);
		}
		else {
			return $o->insert($table, $fieldvalues);
		}
	}

	/**
	 * function delete
	 * Deletes any record that matches the specific criteria
	 * @param string Table to delete from
	 * @param array Associative array of field values to match
	 * @return boolean True on success, false if not
	 * <code>DB::delete( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/	 
	public function delete( $table, $keyfields )
	{
		$o =& DB::o();
		ksort( $keyfields );
		
		$qry = "DELETE FROM {$table} WHERE 1 ";
		foreach ( $keyfields as $keyfield => $keyvalue ) {
			$qry .= "AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		
		return $o->query( $qry, $values );
	}

  /**
   * Helper function to return the last inserted sequence or 
   * auto_increment field.  Useful when doing multiple inserts
   * within a single transaction -- for example, adding dependent
   * related rows.
   *
   * @return  mixed The last sequence value (RDBMS-dependent!)
   * @see     http://us2.php.net/manual/en/function.pdo-lastinsertid.php
   */
  public function last_insert_id() {
    return $this->dbh->lastInsertId((func_num_args()==1 ? func_get_args(1) : ));
  }
}
?>
