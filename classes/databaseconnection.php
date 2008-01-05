<?php

/**
 * Habari DatabaseConnection Class
 *
 * @package Habari
 */


class DatabaseConnection
{
	private $fetch_mode= PDO::FETCH_CLASS;          // PDO Fetch mode
	private $fetch_class_name= 'QueryRecord';    	// The default class name for fetching classes
	private $keep_profile= DEBUG;                   	// keep profiling and timing information?
	private $pdo= NULL;                             // handle to the PDO interface
	private $pdo_statement= NULL;                   // handle for a PDOStatement

	/**
	 * @var array tables Habari knows about
	 */
	private $tables= array(
		'commentinfo',
		'comments',
		'crontab',
		'groups',
		'groups_permissions',
		'log',
		'log_types',
		'options',
		'permissions',
		'postinfo',
		'posts',
		'poststatus',
		'posttype',
		'rewrite_rules',
		'sessions',
		'tag2post',
		'tags',
		'userinfo',
		'users',
		'users_groups',
	);

	/**
	 * @var array mapping of table name -> prefixed table name
	 */
	private $sql_tables= array();
	private $sql_tables_repl = array();
	private $errors= array();                       // an array of errors related to queries
	private $profiles= array();                     	// an array of query profiles

	private $prefix= '';								// class private storage of the database table prefix, defaults to ''
	private $current_table;

	/**
	 * Returns the appropriate type of Connection class for the connect string passed or null on failure
	 *
	 * @param connection_string a PDO connection string
	 * @return  mixed returns appropriate DatabaseConnection child class instance or errors out if requiring the db class fails
	 */
	public static function ConnectionFactory( $connect_string )
	{
		list($engine) = explode(':', $connect_string, 2);
		require_once( HABARI_PATH . "/system/schema/{$engine}/connection.php" );
		$engine .= 'Connection';
		return new $engine();
	}

	/**
	 * Populate the table mapping.
	 *
	 * @return void
	 */
	protected function load_tables()
	{
		if ( isset ( $GLOBALS['db_connection']['prefix'] ) ) {
			$prefix= $GLOBALS['db_connection']['prefix'];
		} else if ( isset( $_POST['table_prefix'] ) ) {
			$prefix= $_POST['table_prefix'];
		} else {
			$prefix= $this->prefix;
		}

		// build the mapping with prefixes
		foreach ( $this->tables as $t ) {
			$this->sql_tables[$t]= $prefix . $t;
			$this->sql_tables_repl[$t]= '{' . $t . '}';
		}
	}

	/**
	 * Connect to a database server
	 *
	 * @param string $connect_string a PDO connection string
	 * @param string $db_user the database user name
	 * @param string $db_pass the database user password
	 * @return boolean TRUE on success, FALSE on error
	 */
	public function connect ( $connect_string, $db_user, $db_pass )
	{
		$this->pdo= new PDO( $connect_string, $db_user, $db_pass );
		$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		$this->load_tables();
		return true;
	}

	/**
	 * Disconnect from the database server.
	 *
	 * @return boolean TRUE
	 */
	public function disconnect()
	{
		$this->pdo= NULL; // this is the canonical way :o

		return TRUE;
	}

	/**
	 * Check whether there is an existing connection to a database.
	 *
	 * @return boolean
	 */
	public function is_connected()
	{
		return ( NULL != $this->pdo );
	}

	/**
	 * Get the full table name for the given table.
	 *
	 * @param string $name name of the table
	 * @return string the full table name, or FALSE if the table was not found
	 */
	public function table( $name )
	{
		if ( isset( $this->sql_tables[$name] ) ) {
			return $this->sql_tables[$name];
		}
		else {
			return false;
		}
	}

	/**
	 * Adds a table to the list of tables known to Habari.  Used
	 * by Theme and Plugin classes to inform the DB class about
	 * custom tables used by the plugin
	 *
	 * @param name the table name
	**/
	public function register_table( $name )
	{
		$this->tables[]= $name;
		$this->load_tables();
	}

	/**
	 * Sets the fetch mode for return calls from PDOStatement
	 *
	 * @param mode  One of the PDO::FETCH_MODE integers
	 */
	public function set_fetch_mode( $mode )
	{
		$this->fetch_mode= $mode;
	}

	/**
	 * Sets the class to fetch, if fetch mode is PDO::FETCH_CLASS
	 *
	 * @param class_name  Name of class to create during fetch
	 */
	public function set_fetch_class( $class_name )
	{
		$this->fetch_class_name= $class_name;
	}

	/**
	 * Execute the given query on the database. Encapsulates PDO::exec.
	 * WARNING: Make sure you don't call this with a SELECT statement.
	 * PDO will buffer the results and leave your cursor dangling.
	 *
	 * @param string $query the query to run
	 * @return boolean TRUE on success, FALSE on error
	 */
	public function exec( $query )
	{
		return ( $this->pdo->exec( $query ) !== FALSE );
	}

	/**
	 * Execute a SQL statement.
	 *
	 * @param string $query the SQL statement
	 * @param array $args values for the bound parameters
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function query( $query, $args= array() )
	{
		if ( $this->pdo_statement != NULL ) {
			$this->pdo_statement->closeCursor();
		}

		// Allow plugins to modify the query
		$query = Plugins::filter( 'query', $query, $args );
		// Translate the query for the database engine
		$query = self::sql_t( $query, $args );
		// Replace braced table names in the query with their prefixed counterparts
		$query = self::filter_tables( $query );
		// Allow plugins to modify the query after it has been processed
		$query = Plugins::filter( 'query_postprocess', $query, $args );

		if ( $this->pdo_statement= $this->pdo->prepare( $query ) ) {
			if ( $this->fetch_mode == PDO::FETCH_CLASS ) {
				/* Try to get the result class autoloaded. */
				if ( ! class_exists( strtolower( $this->fetch_class_name ) ) ) {
					$tmp= $this->fetch_class_name;
					new $tmp();
				}
				/* Ensure that the class is actually available now, otherwise segfault happens (if we haven't died earlier anyway). */
				if ( class_exists( strtolower( $this->fetch_class_name ) ) ) {
					$this->pdo_statement->setFetchMode( PDO::FETCH_CLASS, $this->fetch_class_name, array() );
				}
				else {
					/* Die gracefully before the segfault occurs */
					echo '<br><br>Attempt to fetch in class mode with a non-included class<br><br>';
					return false;
				}
			}
			else {
				$this->pdo_statement->setFetchMode( $this->fetch_mode );
			}

			/* If we are profiling, then time the query */
			if ( $this->keep_profile ) {
				$profile= new QueryProfile( $query );
				$profile->start();
			}
			if ( ! $this->pdo_statement->execute( $args ) ) {
				$this->add_error( array( 'query'=>$query,'error'=>$this->pdo_statement->errorInfo() ) );
				return false;
			}
			if ( $this->keep_profile ) {
				$profile->stop();
				$this->profiles[]= $profile;
			}
			return true;
		}
		else {
			$this->add_error( array(
				'query' => $query,
				'error' => $this->pdo->errorInfo(),
			) );
			return false;
		}
	}

	/**
	 * Execute a stored procedure
	 *
	 * @param   procedure   name of the stored procedure
	 * @param   args        arguments for the procedure
	 * @return  mixed       whatever the procedure returns...
	 * @experimental
	 * @todo  EVERYTHING... :)
	 */
	public function execute_procedure( $procedure, $args= array() )
	{
		/* Local scope caching */
		$pdo= $this->pdo;
		$pdo_statement= $this->pdo_statement;

		if( $pdo_statement != NULL ) {
			$pdo_statement->closeCursor();
		}

		/*
		 * Since RDBMS handle the calling of procedures
		 * differently, we need a simple abstraction
		 * mechanism here to build the appropriate SQL
		 * commands to call the procedure...
		 */
		$driver= $pdo->getAttribute( PDO::ATTR_DRIVER_NAME );
		switch ( $driver ) {
			case 'mysql':
			case 'db2':
				/*
				 * These databases use ANSI-92 syntax for procedure calling:
				 * CALL procname ( param1, param2, ... );
				 */
				$query= 'CALL ' . $procedure . '( ';
				if ( count( $args ) > 0 ) {
					$query.= str_repeat( '?,', count( $args ) ); // Add the placeholders
					$query= substr( $query, 0, strlen( $query ) - 1 ); // Strip the last comma
				}
				$query.= ' )';
				break;
			case 'pgsql':
			case 'oracle':
				die( "not yet supported on $driver" );
				break;
		}

		if ( $pdo_statement= $pdo->prepare( $query ) ) {
			/* If we are profiling, then time the query */
			if ( $this->keep_profile ) {
				$profile= new QueryProfile( $query );
				$profile->start();
			}
			if ( ! $pdo_statement->execute( $args ) ) {
				$this->add_error( array( 'query'=>$query,'error'=>$pdo_statement->errorInfo() ) );
				return false;
			}
			if ( $this->keep_profile ) {
				$profile->stop();
				$this->profiles[]= $profile;
			}
			return true;
		}
		else {
			$this->add_error( array( 'query'=>$query,'error'=>$pdo_statement->errorInfo() ) );
			return false;
		}
	}

	/**
	 * Start a transaction against the RDBMS in order to wrap multiple
	 * statements in a safe ACID-compliant container
	 */
	public function begin_transaction()
	{
		$this->pdo->beginTransaction();
	}

	/**
	 * Rolls a currently running transaction back to the
	 * prexisting state, or, if the RDBMS supports it, whenever
	 * a savepoint was committed.
	 */
	public function rollback()
	{
		$this->pdo->rollBack();
	}

	/**
	 * Commit a currently running transaction
	 */
	public function commit()
	{
		$this->pdo->commit();
	}

	/**
	 * Returns query profiles
	 *
	 * @return  array an array of query profiles
	 */
	public function get_profiles()
	{
		return $this->profiles;
	}

	/**
	 * Adds an error to the internal collection
	 *
	 * @param   error   array( 'query'=>query, 'error'=>errorInfo )
	 */
	private function add_error( $error )
	{
		$backtrace1 = debug_backtrace();
		$backtrace = array();
		foreach($backtrace1 as $trace) {
			$backtrace[]= array_intersect_key( $trace, array('file'=>1, 'line'=>1, 'function'=>1, 'class'=>1) );
		}
		$this->errors[]= array_merge($error, array('backtrace'=> $backtrace)) ;
	}

	/**
	 * Returns error data gathered from database connection
	 * @return array An array of error data
	 */
	public function get_errors()
	{
		return $this->errors;
	}

	/**
	 * Determines if there have been errors since the last clear_errors() call
	 * @return boolean True if there were errors, false if not
	 **/
	public function has_errors()
	{
		return ( count( $this->errors ) > 0 );
	}

	/**
	 * Updates the last error pointer to simulate resetting the error array
	 **/
	public function clear_errors()
	{
		$this->errors= array();
	}

	/**
	 * Returns only the last error info
	 * @return array Data for the last error
	 **/
	public function get_last_error()
	{
		$error= end( $this->errors );
		return ( array( 'query'=>$error['query'], 'message'=>$error['error'][2] ) );
	}

	/**
	 * Execute a query and return the results as an array of objects
	 * @param query   the query to execute
	 * @param args    array of arguments to pass for prepared statements
	 * @param string Optional class name for row result objects
	 * @return array An array of QueryRecord or the named class each containing the row data
	 * <code>$ary= DB::get_results( 'SELECT * FROM tablename WHERE foo= ?', array( 'fieldvalue' ), 'extendedQueryRecord' );</code>
	 **/
	public function get_results( $query, $args= array() )
	{
		if ( func_num_args() == 3 ) {
			/* Called expecting specific class return type */
			$class_name= func_get_arg( 2 );
		}
		else {
			$class_name= 'QueryRecord';
		}
		$this->set_fetch_mode( PDO::FETCH_CLASS );
		$this->set_fetch_class( $class_name );
		if ( $this->query( $query, $args ) ) {
			return $this->pdo_statement->fetchAll();
		}
		else {
			return false;
		}
	}

	/**
	 * Returns a single row (the first in a multi-result set) object for a query
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @param string Optional class name for row result object
	 * @return object A QueryRecord or an instance of the named class containing the row data
	 * <code>$obj= DB::get_row( 'SELECT * FROM tablename WHERE foo= ?', array( 'fieldvalue' ), 'extendedQueryRecord' );</code>
	 **/
	public function get_row( $query, $args= array() )
	{
		if ( func_num_args() == 3 ) {
			/* Called expecting specific class return type */
			$class_name= func_get_arg( 2 );
			$this->set_fetch_mode( PDO::FETCH_CLASS );
			$this->set_fetch_class( $class_name );
		}
		if ( $this->query( $query, $args ) ) {
			return $this->pdo_statement->fetch();
		}
		else {
			return false;
		}
	}

	/**
	 * Returns all values for a column for a query
	 *
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return array An array containing the column data
	 * <code>$ary= DB::get_column( 'SELECT col1 FROM tablename WHERE foo= ?', array( 'fieldvalue' ) );</code>
	 **/
	public function get_column( $query, $args= array() )
	{
		if ( $this->query( $query, $args ) ) {
			return $this->pdo_statement->fetchAll( PDO::FETCH_COLUMN );
		}
		else {
			return false;
		}
	}

	/**
	 * Return a single value from the database
	 *
	 * @param string the query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return mixed a single value ( int, string )
	**/
	public function get_value( $query, $args= array() )
	{
		if ( $this->query( $query, $args ) ) {
			$result= $this->pdo_statement->fetch( PDO::FETCH_NUM );
			return $result[0];
		}
		else {
			return false;
		}
	}

	/**
	 * Inserts into the specified table values associated to the key fields
	 * @param string The table name
	 * @param array An associative array of fields and values to insert
	 * @return boolean True on success, false if not
	 * <code>DB::insert( 'mytable', array( 'fieldname' => 'value' ) );</code>
	 **/
	public function insert( $table, $fieldvalues )
	{
		ksort( $fieldvalues );

		$query= "INSERT INTO {$table} ( ";
		$comma= '';

		foreach( $fieldvalues as $field => $value ) {
			$query.= $comma . $field;
			$comma= ', ';
			$values[]= $value;
		}
		$query.= ' ) VALUES ( ' . trim( str_repeat( '?,', count( $fieldvalues ) ), ',' ) . ' );';

		// need to pass $table on to the $o singleton object;
			$this->current_table= $table;

		return $this->query( $query, $values );
	}

	/**
	 * Checks for a record that matches the specific criteria
	 * @param string Table to check
	 * @param array Associative array of field values to match
	 * @return boolean True if any matching record exists, false if not
	 * <code>DB::exists( 'mytable', array( 'fieldname' => 'value' ) );</code>
	 **/
	public function exists( $table, $keyfieldvalues )
	{
		$qry= "SELECT 1 as c FROM {$table} WHERE 1=1 ";

		$values= array();
		foreach( $keyfieldvalues as $keyfield => $keyvalue ) {
			$qry.= " AND {$keyfield}= ? ";
			$values[]= $keyvalue;
		}
		$result= $this->get_row( $qry, $values );
		return ( $result !== false );
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
	public function update( $table, $fieldvalues, $keyfields )
	{
		ksort( $fieldvalues );
		ksort( $keyfields );

		$keyfieldvalues= array();
		foreach( $keyfields as $keyfield => $keyvalue ) {
			if( is_numeric( $keyfield ) ) {
				$keyfieldvalues[$keyvalue]= $fieldvalues[$keyvalue];
			}
			else {
				$keyfieldvalues[$keyfield]= $keyvalue;
			}
		}
		if( $this->exists( $table, $keyfieldvalues ) ) {
			$qry= "UPDATE {$table} SET";
			$values= array();
			$comma= '';
			foreach( $fieldvalues as $fieldname => $fieldvalue ) {
				$qry.= $comma . " {$fieldname}= ?";
				$values[]= $fieldvalue;
				$comma= ' ,';
			}
			$qry.= ' WHERE 1=1 ';

			foreach( $keyfields as $keyfield => $keyvalue ) {
				$qry.= "AND {$keyfield}= ? ";
				$values[]= $keyvalue;
			}
			return $this->query( $qry, $values );
		}
		else {
			// We want the keyfieldvalues to be included in
			// the insert, with fieldvalues taking precedence.
			$fieldvalues = $fieldvalues + $keyfieldvalues;
			return $this->insert( $table, $fieldvalues );
		}
	}

	/**
	 * Deletes any record that matches the specific criteria
	 * @param string Table to delete from
	 * @param array Associative array of field values to match
	 * @return boolean True on success, false if not
	 * <code>DB::delete( 'mytable', array( 'fieldname' => 'value' ) );</code>
	 */
	public function delete( $table, $keyfields )
	{
		$qry= "DELETE FROM {$table} WHERE 1=1 ";
		foreach ( $keyfields as $keyfield => $keyvalue ) {
			$qry.= "AND {$keyfield}= ? ";
			$values[]= $keyvalue;
		}

		return $this->query( $qry, $values );
	}

	/**
	 * Helper function to return the last inserted sequence or
	 * auto_increment field.  Useful when doing multiple inserts
	 * within a single transaction -- for example, adding dependent
	 * related rows.
	 *
	 * @return  mixed The last sequence value ( RDBMS-dependent! )
	 * @see     http://us2.php.net/manual/en/function.pdo-lastinsertid.php
	 */
	public function last_insert_id()
	{
		if ( $this->pdo->getAttribute( PDO::ATTR_DRIVER_NAME ) == 'pgsql' ) {
			return $this->pdo->lastInsertId( $this->current_table. '_pkey_seq' ) ;
		}
		else {
			return $this->pdo->lastInsertId( func_num_args() == 1 ? func_get_arg( 0 ) : '' );
		}
	}

	/**
	 * Automatic diffing function, used for determining required database upgrades.
	 * Implemented in child classes.
	 */
	public function dbdelta( $queries, $execute = true, $silent = true ){}


	/**
	 * Translates the query for the current database engine
	 *
	 * @param string $query The query to translate for the current database engine
	 * @param array $args Arguments to the query
	 * @return string The translated query
	 */
	public function sql_t( $query )
	{
		return $query;
	}

	/**
	 * Replace braced table names with their prefixed counterparts
	 *
	 * @param string $query The query with {braced} table names
	 * @return string The translated query
	 */
	public function filter_tables( $query )
	{
		return str_replace($this->sql_tables_repl, $this->sql_tables, $query);
	}
}

?>
