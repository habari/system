<?php
/**
 * @package Habari
 *
 */

/**
 * Habari DatabaseConnection Class
 *
 * Actual database connection.
 */
class DatabaseConnection
{
	private $fetch_mode = PDO::FETCH_CLASS;          // PDO Fetch mode
	private $fetch_class_name = 'QueryRecord';       // The default class name for fetching classes
	private $driver;                                 // PDO driver name
	private $keep_profile = DEBUG;                   // keep profiling and timing information?
	protected $pdo = null;                           // handle to the PDO interface
	private $pdo_statement = null;                   // handle for a PDOStatement
	private $pdo_transaction = false;                // handle for transaction status

	/**
	 * @var array tables Habari knows about
	 */
	private $tables = array(
		'blocks',
		'blocks_areas',
		'commentinfo',
		'comments',
		'crontab',
		'groups',
		'group_token_permissions',
		'log',
		'log_types',
		'object_terms',
		'object_types',
		'options',
		'post_tokens',
		'postinfo',
		'posts',
		'poststatus',
		'posttype',
		'rewrite_rules',
		'scopes',
		'sessions',
		'tags',
		'tag2post',
		'terms',
		'terminfo',
		'tokens',
		'userinfo',
		'users',
		'user_token_permissions',
		'users_groups',
		'vocabularies',
	);

	/**
	 * @var array mapping of table name -> prefixed table name
	 */
	private $sql_tables = array();
	private $sql_tables_repl = array();
	private $errors = array();                       // an array of errors related to queries
	private $profiles = array();                     // an array of query profiles

	protected $prefix = '';                          // class protected storage of the database table prefix, defaults to ''
	private $current_table;

	/**
	 * Returns the appropriate type of Connection class for the connect string passed or null on failure
	 *
	 * @param connection_string a PDO connection string
	 * @return  mixed returns appropriate DatabaseConnection child class instance or errors out if requiring the db class fails
	 */
	public static function ConnectionFactory( $connect_string )
	{
		list( $engine ) = explode( ':', $connect_string, 2 );
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
		if ( isset( Config::get( 'db_connection' )->prefix ) ) {
			$prefix = Config::get( 'db_connection' )->prefix;
		}
		else {
			$prefix = $this->prefix;
		}
		$this->prefix = $prefix;

		// build the mapping with prefixes
		foreach ( $this->tables as $t ) {
			$this->sql_tables[$t] = $prefix . $t;
			$this->sql_tables_repl[$t] = '{' . $t . '}';
		}
	}

	/**
	 * Connect to a database server
	 *
	 * @param string $connect_string a PDO connection string
	 * @param string $db_user the database user name
	 * @param string $db_pass the database user password
	 * @return boolean true on success, false on error
	 */
	public function connect ( $connect_string, $db_user, $db_pass )
	{
		$this->pdo = @new PDO( $connect_string, $db_user, $db_pass );
		$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		$this->load_tables();
		return true;
	}

	/**
	 * Disconnect from the database server.
	 *
	 * @return boolean true
	 */
	public function disconnect()
	{
		$this->pdo = null; // this is the canonical way :o

		return true;
	}

	/**
	 * Check whether there is an existing connection to a database.
	 *
	 * @return boolean
	 */
	public function is_connected()
	{
		return ( null != $this->pdo );
	}

	/**
	 * Check whether there is a transaction underway.
	 *
	 * @return boolean
	 */
	public function in_transaction()
	{
		return $this->pdo_transaction;
	}

	/**
	 * Get the full table name for the given table.
	 *
	 * @param string $name name of the table
	 * @return string the full table name, or the original value if the table was not found
	 */
	public function table( $name )
	{
		if ( isset( $this->sql_tables[$name] ) ) {
			return $this->sql_tables[$name];
		}
		else {
			return $name;
		}
	}

	/**
	 * Adds a table to the list of tables known to Habari.  Used
	 * by Theme and Plugin classes to inform the DB class about
	 * custom tables used by the plugin
	 *
	 * @param name the table name
	 */
	public function register_table( $name )
	{
		$this->tables[] = $name;
		$this->load_tables();
	}

	/**
	 * Sets the fetch mode for return calls from PDOStatement
	 *
	 * @param mode  One of the PDO::FETCH_MODE integers
	 */
	public function set_fetch_mode( $mode )
	{
		$this->fetch_mode = $mode;
	}

	/**
	 * Sets the class to fetch, if fetch mode is PDO::FETCH_CLASS
	 *
	 * @param class_name  Name of class to create during fetch
	 */
	public function set_fetch_class( $class_name )
	{
		$this->fetch_class_name = $class_name;
	}

	/**
	 * Execute the given query on the database. Encapsulates PDO::exec.
	 * WARNING: Make sure you don't call this with a SELECT statement.
	 * PDO will buffer the results and leave your cursor dangling.
	 *
	 * @param string $query the query to run
	 * @return boolean true on success, false on error
	 */
	public function exec( $query )
	{
		// Allow plugins to modify the query
		$query = Plugins::filter( 'db_exec', $query, array() );
		// Translate the query for the database engine
		$ary = array();
		$query = $this->sql_t( $query, $ary );
		// Replace braced table names in the query with their prefixed counterparts
		$query = self::filter_tables( $query );
		// Allow plugins to modify the query after it has been processed
		$query = Plugins::filter( 'db_exec_postprocess', $query, array() );

		return ( $this->pdo->exec( $query ) !== false );
	}

	/**
	 * Execute a SQL statement.
	 *
	 * @param string $query the SQL statement
	 * @param array $args values for the bound parameters
	 * @return boolean true on success, false on failure
	 */
	public function query( $query, $args = array() )
	{
		if ( $this->pdo_statement != null ) {
			$this->pdo_statement->closeCursor();
		}

		// Allow plugins to modify the query
		$query = Plugins::filter( 'query', $query, $args );
		// Translate the query for the database engine
		$query = $this->sql_t( $query, $args );
		// Replace braced table names in the query with their prefixed counterparts
		$query = self::filter_tables( $query );
		// Allow plugins to modify the query after it has been processed
		$query = Plugins::filter( 'query_postprocess', $query, $args );

		if ( $this->pdo_statement = $this->pdo->prepare( $query ) ) {
			if ( $this->fetch_mode == PDO::FETCH_CLASS ) {
				/* Try to get the result class autoloaded. */
				if ( ! class_exists( strtolower( $this->fetch_class_name ) ) ) {
					$tmp = $this->fetch_class_name;
					new $tmp();
				}
				/* Ensure that the class is actually available now, otherwise segfault happens (if we haven't died earlier anyway). */
				if ( class_exists( strtolower( $this->fetch_class_name ) ) ) {
					$this->pdo_statement->setFetchMode( PDO::FETCH_CLASS, $this->fetch_class_name, array() );
				}
				else {
					/* Die gracefully before the segfault occurs */
					echo '<br><br>' . _t( 'Attempt to fetch in class mode with a non-included class' ) . '<br><br>';
					return false;
				}
			}
			else {
				$this->pdo_statement->setFetchMode( $this->fetch_mode );
			}

			/* If we are profiling, then time the query */
			if ( $this->keep_profile ) {
				$profile = new QueryProfile( $query );
				$profile->params = $args;
				$profile->start();
			}
			if ( ! $this->pdo_statement->execute( $args ) ) {
				$this->add_error( array( 'query'=>$query,'error'=>$this->pdo_statement->errorInfo() ) );
				return false;
			}
			if ( $this->keep_profile ) {
				$profile->stop();
				$this->profiles[] = $profile;
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
	 * Implemented in child classes. Most RDBMS use ANSI-92 syntax,
	 * @todo Make sure it's MultiByte safe
	 * ( CALL procname ( param1, param2, ... ),
	 * so they return the call to here. Those that don't, handle the call individually
	 */
	public function execute_procedure( $procedure, $args = array() )
	{
		/* Local scope caching */
		$pdo = $this->pdo;
		$pdo_statement = $this->pdo_statement;

		if ( $pdo_statement != null ) {
			$pdo_statement->closeCursor();
		}

		$query = 'CALL ' . $procedure . '( ';
		if ( count( $args ) > 0 ) {
			$query .= str_repeat( '?,', count( $args ) ); // Add the placeholders
			$query = substr( $query, 0, strlen( $query ) - 1 ); // Strip the last comma
		}
		$query .= ' )';
		$query = $this->sql_t( $query, $args );

		if ( $pdo_statement = $pdo->prepare( $query ) ) {
			/* If we are profiling, then time the query */
			if ( $this->keep_profile ) {
				$profile = new QueryProfile( $query );
				$profile->start();
			}
			if ( ! $pdo_statement->execute( $args ) ) {
				$this->add_error( array( 'query'=>$query,'error'=>$pdo_statement->errorInfo() ) );
				return false;
			}
			if ( $this->keep_profile ) {
				$profile->stop();
				$this->profiles[] = $profile;
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
		if ( ! $this->pdo_transaction ) {
			if( $this->pdo->beginTransaction() ) {
				$this->pdo_transaction = true;
			}
		}
	}

	/**
	 * Rolls a currently running transaction back to the
	 * prexisting state, or, if the RDBMS supports it, whenever
	 * a savepoint was committed.
	 */
	public function rollback()
	{
		if ( $this->pdo_transaction ) {
			if( $this->pdo->rollBack() ) {
				$this->pdo_transaction = false;
			}
		}
	}

	/**
	 * Commit a currently running transaction
	 */
	public function commit()
	{
		if ( $this->pdo_transaction ) {
			if( $this->pdo->commit() ) {
				$this->pdo_transaction = false;
			}
		}
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
	public function add_error( $error )
	{
		$backtrace1 = debug_backtrace();
		$backtrace = array();
		foreach ( $backtrace1 as $trace ) {
			$backtrace[] = array_intersect_key( $trace, array('file'=>1, 'line'=>1, 'function'=>1, 'class'=>1) );
		}
		$this->errors[] = array_merge( $error, array( 'backtrace'=> $backtrace ) );
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
	 */
	public function has_errors()
	{
		return ( count( $this->errors ) > 0 );
	}

	/**
	 * Updates the last error pointer to simulate resetting the error array
	 */
	public function clear_errors()
	{
		$this->errors = array();
	}

	/**
	 * Returns only the last error info
	 * @return array Data for the last error
	 */
	public function get_last_error()
	{
		$error = end( $this->errors );
		return ( array( 'query'=>$error['query'], 'message'=>$error['error'][2] ) );
	}

	/**
	 * Execute a query and return the results as an array of objects
	 * @param query   the query to execute
	 * @param args    array of arguments to pass for prepared statements
	 * @param string Optional class name for row result objects
	 * @return array An array of QueryRecord or the named class each containing the row data
	 * <code>$ary= DB::get_results( 'SELECT * FROM tablename WHERE foo= ?', array( 'fieldvalue' ), 'extendedQueryRecord' );</code>
	 */
	public function get_results( $query, $args = array() )
	{
		if ( func_num_args() == 3 ) {
			/* Called expecting specific class return type */
			$class_name = func_get_arg( 2 );
		}
		else {
			$class_name = 'QueryRecord';
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
	 */
	public function get_row( $query, $args = array() )
	{
		if ( func_num_args() == 3 ) {
			/* Called expecting specific class return type */
			$class_name = func_get_arg( 2 );
		}
		else {
			$class_name = 'QueryRecord';
		}

		$this->set_fetch_mode( PDO::FETCH_CLASS );
		$this->set_fetch_class( $class_name );

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
	 */
	public function get_column( $query, $args = array() )
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
	 */
	public function get_value( $query, $args = array() )
	{
		if ( $this->query( $query, $args ) ) {
			$result = $this->pdo_statement->fetch( PDO::FETCH_NUM );
			return $result[0];
		}
		else {
			return false;
		}
	}

	/**
	 * Returns an associative array using the first returned column as the array key and the second as the array value
	 *
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return array An array containing the associative data
	 * <code>$ary= $dbconnection->get_keyvalue( 'SELECT keyfield, valuefield FROM tablename');</code>
	 */
	public function get_keyvalue( $query, $args = array() )
	{
		if ( $this->query( $query, $args ) ) {
			$result = $this->pdo_statement->fetchAll( PDO::FETCH_NUM );
			$output = array();
			foreach ( $result as $item ) {
				$output[$item[0]] = $item[1];
			}
			return $output;
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
	 */
	public function insert( $table, $fieldvalues )
	{
		ksort( $fieldvalues );

		$fields = array_keys( $fieldvalues );
		$values = array_values( $fieldvalues );
		
		$query = "INSERT INTO {$table} ( " . implode( ', ', $fields ) . ' ) VALUES ( ' . implode( ', ', array_fill( 0, count( $values ), '?' ) ) . ' )';

		// need to pass $table on to the $o singleton object;
		$this->current_table = $table;

		return $this->query( $query, $values );
	}

	/**
	 * Checks for a record that matches the specific criteria
	 * @param string Table to check
	 * @param array Associative array of field values to match
	 * @return boolean True if any matching record exists, false if not
	 * <code>DB::exists( 'mytable', array( 'fieldname' => 'value' ) );</code>
	 */
	public function exists( $table, $keyfieldvalues )
	{
		$qry = "SELECT 1 as c FROM {$table} WHERE 1=1 ";

		$values = array();
		foreach ( $keyfieldvalues as $keyfield => $keyvalue ) {
			$qry .= " AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		$result = $this->get_row( $qry, $values );
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
	 */
	public function update( $table, $fieldvalues, $keyfields )
	{
		ksort( $fieldvalues );
		ksort( $keyfields );

		$keyfieldvalues = array();
		foreach ( $keyfields as $keyfield => $keyvalue ) {
			if ( is_numeric( $keyfield ) ) {
				// if the key is numeric, assume we were handed a simple list of fields that are keys and fetch its value from $fieldvalues
				$keyfieldvalues[$keyvalue] = $fieldvalues[$keyvalue];
			}
			else {
				// otherwise we were handed a key => value pair, use it as-is
				$keyfieldvalues[$keyfield] = $keyvalue;
			}
		}
		if ( $this->exists( $table, $keyfieldvalues ) ) {
			$qry = "UPDATE {$table} SET";
			$values = array();
			$comma = '';
			foreach ( $fieldvalues as $fieldname => $fieldvalue ) {
				$qry .= $comma . " {$fieldname} = ?";
				$values[] = $fieldvalue;
				$comma = ' ,';
			}
			$qry .= ' WHERE 1=1 ';

			foreach ( $keyfieldvalues as $keyfield => $keyvalue ) {
				$qry .= "AND {$keyfield} = ? ";
				$values[] = $keyvalue;
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
		$qry = "DELETE FROM {$table} WHERE 1=1 ";
		foreach ( $keyfields as $keyfield => $keyvalue ) {
			$qry .= "AND {$keyfield} = ? ";
			$values[] = $keyvalue;
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
			return $this->pdo->lastInsertId( $this->current_table. '_pkey_seq' );
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

	public function upgrade_pre( $old_version ){}

	public function upgrade_post( $old_version ){}


	/**
	 * Updates the content of the database between versions.
	 * Implemented in child classes.
	 *
	 * @param integer $old_version The old Version::DB_VERSION
	 */
	public function upgrade( $old_version, $upgrade_path = '' )
	{
		// Get all the upgrade files
		$upgrade_files = Utils::glob( "{$upgrade_path}/*.sql" );

		// Put the upgrade files into an array using the 0-padded revision + '_0' as the key
		$upgrades = array();
		foreach ( $upgrade_files as $file ) {
			if ( intval( basename( $file, '.sql' ) ) > $old_version ) {
				$upgrades[ sprintf( '%010s_0', basename( $file, '.sql' ) )] = $file;
			}
		}
		// Put the upgrade functions into an array using the 0-padded revision + '_1' as the key
		$upgrade_functions = get_class_methods( $this );
		foreach ( $upgrade_functions as $fn ) {
			if ( preg_match( '%^upgrade_([0-9]+)$%i', $fn, $matches ) ) {
				if ( intval( $matches[1] ) > $old_version ) {
					$upgrades[ sprintf( '%010s_1', $matches[1] )] = array( $this, $fn );
				}
			}
		}

		// Sort the upgrades by revision, ascending
		ksort( $upgrades );

		// Execute all of the upgrade functions
		$result = true;
		foreach ( $upgrades as $upgrade ) {
			if ( is_array( $upgrade ) ) {
				$result &= $upgrade();
			}
			else {
				$result &= $this->query_file( $upgrade );
			}
			if ( !$result ) {
				break;
			}
		}

		return $result;
	}

	/**
	 * Load a file containing queries, replace the prefix, execute all queries present
	 *
	 * @param string $file The filename containing the queries
	 * @return boolean True on successful execution of all queries
	 */
	public function query_file( $file )
	{
		$upgrade_sql = trim( file_get_contents( $file ) );
		$upgrade_sql = str_replace( '{$prefix}', $this->prefix, $upgrade_sql );

		// Split up the queries
		$queries = explode( ';', $upgrade_sql );

		foreach ( $queries as $query ) {
			if ( trim( $query ) != '' ) {
				if ( !$this->query( $query ) ) {
					return false;
				}
			}
		}

		return true;
	}


	/**
	 * Translates the query for the current database engine
	 *
	 * @param string $query The query to translate for the current database engine
	 * @param array $args An array of SQL arguments
	 * @return string The translated query
	 */
	public function sql_t( $query, &$args )
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
		return str_replace( $this->sql_tables_repl, $this->sql_tables, $query );
	}

	public function get_driver_name()
	{
		if ( ! $this->driver ) {
			$this->driver = $this->pdo->getAttribute( PDO::ATTR_DRIVER_NAME );
		}
		return $this->driver;
	}

	public function get_driver_version()
	{
		return $this->pdo->getAttribute( PDO::ATTR_SERVER_VERSION );
	}

	/**
	 * Returns number of rows affected by the last DELETE, INSERT, or UPDATE
	 *
	 * @return int The number of rows affected.
	 */
	public function row_count()
	{
		return $this->pdo_statement->rowCount();
	}

	/**
	 * Returns a list of tables the DB currently knows about.
	 *
	 * @return array The list of tables.
	 */
	public function list_tables()
	{
		return $this->sql_tables;
	}

	/**
	 * Return a PDO-quoted string appropriate for the DB backend we're using.
	 *
	 * If you're using this then there's 99+% probability you're building your queries the wrong way!
	 *
	 * @param string $string The string to quote.
	 * @return string A DB-safe quoted string.
	 */
	public function quote ( $string )
	{
		return $this->pdo->quote( $string );
	}

}

?>
