<?php
/**
 * Habari DB Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

/**
 * class habari_db
 * The database class.
 * Connects to the database and provides access to data 
 */  
class habari_db
{
	private $dbh;  // Database handle
	private $pdostatement;  // PDOStatement handle
	private $errors = array(); // Array of SQL errors 
	public $queryok; // Boolean on last query success 
	private $queries = array(); // Array of executed queries

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
		$this->pdostatement = $this->dbh->prepare($query);
		if($this->pdostatement) {
			if($c_name == '') {
				$this->pdostatement->setFetchMode(PDO::FETCH_CLASS, 'QueryRecord', array());
			}
			else {
				$this->pdostatement->setFetchMode(PDO::FETCH_CLASS, $c_name, array());
			}
			if($this->pdostatement->execute($args)) {
				$this->queries[] = array($query, $args, true);
				$this->queryok = true;
				return true;
			}
		}
		$this->queryok = false;
		$this->errors[] = $this->dbh->errorInfo();
		$this->queries[] = array($query, $args, $this->dbh->errorInfo());
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
		call_user_func_array(array(&$this, 'query'), array($query, $args, $classname));
		if($this->queryok) {
			return $this->pdostatement->fetchAll();
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

		return call_user_func_array(array(&$this, query), array($query, $values));
	}	 	 	 	 	

	public function install_habari() {
	/**
	 * function install_habari
	 * Installs base tables and starter data.
	 */
	 global $db;
	 
		// Create the table
		if ( $this->query("CREATE TABLE habari__posts 
			(slug VARCHAR(255) NOT NULL PRIMARY KEY, 
			title VARCHAR(255), 
			guid VARCHAR(255) NOT NULL, 
			content LONGTEXT, 
			author VARCHAR(255) NOT NULL, 
			status VARCHAR(50) NOT NULL, 
			pubdate TIMESTAMP, 
			updated TIMESTAMP);")) {
				echo "Created Table<br />";
		} else {
			print_r( $db->get_last_error() );
		}
		
		// Insert records
	
		if($this->query("INSERT INTO habari__posts (slug, title, guid, content, author, status, pubdate, updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?);",
			array('first-post', 'First Post', 'tag:localhost/first-post/1935076', 'This is my first post', 'owen', 'publish', '2006-10-04 17:17:00', '2006-10-04 17:17:00'))) echo "Inserted Record 1<br/>";
	
		if($this->query("INSERT INTO habari__posts (slug, title, guid, content, author, status, pubdate, updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?);",
			array('second-post', 'Second Post', 'tag:localhost/second-post/5987120', 'This is my second post', 'owen', 'publish', '2006-10-04 17:18:00', '2006-10-04 17:18:00'))) echo "Inserted Record 2<br/>";
	
		if($this->insert('habari__posts', array (
			'slug'=>'third-post',
			'title'=>'Third Post',
			'guid'=>'tag:localhost/third-post/4981704',
			'content'=>'This is my third post',
			'author'=>'owen',
			'status'=>'publish',
			'pubdate'=>'2006-10-04 17:19:00',
			'updated'=>'2006-10-04 17:18:00'
		))) echo "Inserted Record 3<br/>";
	
		// Output any errors
		echo "Errors:<pre>" . print_r($this->get_errors(), 1) . "</pre>";
	}
}
?>