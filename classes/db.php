<?php
/**
 * @package Habari
 *
 */

/**
 * Habari DB Class
 *
 * Singleton class for database connection and manipulation
 *
 */
class DB extends Singleton
{
	/**
	 * @var DatabaseConnection $connection
	 */
	private $connection = null;

	/**
	 * Enables singleton working properly
	 *
	 * @see singleton.php
	 * @return DB
	 */
	protected static function instance()
	{
		return self::getInstanceOf( __CLASS__ );
	}

	/**
	 * Connects to the database server.  If no arguments are
	 * supplied, then the connection is attempted for the
	 * database authentication variables in config.php.
	 *
	 * @param (optional)  connection_string a PDO connection string
	 * @param (optional)  db_user           the database user name
	 * @param (optional)  db_pass           the database user password
	 * @return  bool
	 */
	public static function connect()
	{
		/*
			if connection has been instantiated (ie: not null), check if is already connected
		*/
		if ( null != DB::instance()->connection ) {
			if ( (func_num_args() == 0) && false != DB::instance()->connection->is_connected() ) {
				return true;
			}
		}

		if ( func_num_args() > 0 ) {
			$connect_string = func_get_arg( 0 );
			$db_user = func_get_arg( 1 );
			$db_pass = func_get_arg( 2 );
		}
		else {
			/* We use the config.php variables */
			$connect_string = Config::get( 'db_connection' )->connection_string;
			$db_user = Config::get( 'db_connection' )->username;
			$db_pass = Config::get( 'db_connection' )->password;
		}
		DB::instance()->connection = DatabaseConnection::ConnectionFactory( $connect_string );
		if ( null != DB::instance()->connection ) {
			return DB::instance()->connection->connect( $connect_string, $db_user, $db_pass );
		}
		else {
			// do some error handling here. The connect string does not have a corresponding DB connection object
			print _t( 'Panic! No database connection class appears to be found for the connection string specified. Please check config.php' );
		}
	}

	public static function disconnect()
	{
		if ( null == DB::instance()->connection ) {
			return true;
		}

		return DB::instance()->connection->disconnect();
	}

	/**
	 * Helper function to naturally return table names
	 *
	 * @param table name of the table
	 */
	public static function table( $name )
	{
		return DB::instance()->connection->table( $name );
	}

	/**
	 * Adds a table to the list of tables known to Habari.  Used
	 * by Theme and Plugin classes to inform the DB class about
	 * custom tables used by the plugin
	 *
	 * @param string $name  The table name
	**/
	public static function register_table( $name )
	{
		DB::instance()->connection->register_table( $name );
	}

	/**
	 * Sets the fetch mode for return calls from PDOStatement
	 *
	 * @param integer $mode  One of the PDO::FETCH_MODE integers
	 */
	public static function set_fetch_mode( $mode )
	{
		DB::instance()->connection->set_fetch_mode( $mode );
	}

	/**
	 * Sets the class to fetch, if fetch mode is PDO::FETCH_CLASS
	 *
	 * @param string $class_name  Name of class to create during fetch
	 */
	public static function set_fetch_class( $class_name )
	{
		DB::instance()->connection->set_fetch_class( $class_name );
	}

	public static function exec( $query )
	{
		return DB::instance()->connection->exec( $query );
	}

	/**
	 * Queries the database for a given SQL command.
	 * @param string $query the SQL query text
	 * @param array $args array of values to use for placeholder replacement
	 * @return bool
	 */
	public static function query( $query, $args = array() )
	{
		return DB::instance()->connection->query( $query, $args );
	}

	/**
	 * Executes a stored procedure against the database
	 *
	 * @param   procedure   name of the stored procedure
	 * @param   args        arguments for the procedure
	 * @return  mixed       whatever the procedure returns...
	 * @experimental
	 * @todo  EVERYTHING... :)
	 */
	public static function execute_procedure( $procedure, $args = array() )
	{
		return DB::instance()->connection->execute_procedure( $procedure, $args );
	}

	/**
	 * Start a transaction against the RDBMS in order to wrap multiple
	 * statements in a safe ACID-compliant container
	 */
	public static function begin_transaction()
	{
		DB::instance()->connection->begin_transaction();
	}

	/**
	 * Rolls a currently running transaction back to the
	 * prexisting state, or, if the RDBMS supports it, whenever
	 * a savepoint was committed.
	 */
	public static function rollback()
	{
		DB::instance()->connection->rollback();
	}

	/**
	 * Commit a currently running transaction
	 */
	public static function commit()
	{
		DB::instance()->connection->commit();
	}

	/**
	 * Returns query profiles
	 *
	 * @return  array an array of query profiles
	 */
	public static function get_profiles()
	{
		return DB::instance()->connection->get_profiles();
	}

	/**
	 * Adds an error to the internal collection
	 *
	 * @param   error   array('query'=>query, 'error'=>errorInfo)
	 */
	private static function add_error( $error )
	{
		DB::instance()->connection->add_error( $error );
	}

	/**
	 * Returns error data gathered from database connection
	 * @return array An array of error data
	 */
	public static function get_errors()
	{
		return DB::instance()->connection->get_errors();
	}

	/**
	 * Determines if there have been errors since the last clear_errors() call
	 * @return boolean True if there were errors, false if not
	 **/
	public static function has_errors()
	{
		return DB::instance()->connection->has_errors();
	}

	/**
	 * Updates the last error pointer to simulate resetting the error array
	 **/
	public static function clear_errors()
	{
		DB::instance()->connection->clear_errors();
	}

	/**
	 * Returns only the last error info
	 * @return array Data for the last error
	 **/
	public static function get_last_error()
	{
		return DB::instance()->connection->get_last_error();
	}

	/**
	 * Execute a query and return the results as an array of objects
	 * @param string $query the query to execute
	 * @param array $args array of arguments to pass for prepared statements
	 * @param string $class_name Optional class name for row result objects
	 * @return array An array of QueryRecord or the named class each containing the row data
	 * <code>$ary = DB::get_results( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>
	 **/
	public static function get_results( $query, $args = array() )
	{
		if ( func_num_args() == 3 ) {
			$class_name = func_get_arg( 2 );
			return DB::instance()->connection->get_results( $query, $args, $class_name );
		}
		else {
			return DB::instance()->connection->get_results( $query, $args );
		}
	}

	/**
	 * Returns a single row (the first in a multi-result set) object for a query
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @param string Optional class name for row result object
	 * @return object A QueryRecord or an instance of the named class containing the row data
	 * <code>$obj = DB::get_row( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>
	 **/
	public static function get_row( $query, $args = array() )
	{
		if ( func_num_args() == 3 ) {
			$class_name = func_get_arg( 2 );
			return DB::instance()->connection->get_row( $query, $args, $class_name );
		}
		else {
			return DB::instance()->connection->get_row( $query, $args );
		}
	}

	/**
	 * Returns all values for a column for a query
	 *
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return array An array containing the column data
	 * <code>$ary = DB::get_column( 'SELECT col1 FROM tablename WHERE foo = ?', array('fieldvalue') );</code>
	 **/
	public static function get_column( $query, $args = array() )
	{
		return DB::instance()->connection->get_column( $query, $args );
	}

	/**
	 * Return a single value from the database
	 *
	 * @param string the query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return mixed a single value (int, string)
	**/
	public static function get_value( $query, $args = array() )
	{
		return DB::instance()->connection->get_value( $query, $args );
	}

	/**
	 * Returns an associative array using the first returned column as the array key and the second as the array value
	 *
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return array An array containing the associative data
	 * <code>$ary= DB::get_keyvalue( 'SELECT keyfield, valuefield FROM tablename');</code>
	 **/
	public static function get_keyvalue( $query, $args = array() )
	{
		return DB::instance()->connection->get_keyvalue( $query, $args );
	}

	/**
	 * Inserts into the specified table values associated to the key fields
	 * @param string $table The table name
	 * @param array $fieldvalues An associative array of fields and values to insert
	 * @return boolean True on success, false if not
	 * <code>DB::insert( 'mytable', array( 'fieldname' => 'value' ) );</code>
	 **/
	public static function insert( $table, $fieldvalues )
	{
		return DB::instance()->connection->insert( $table, $fieldvalues );
	}

	/**
	 * Checks for a record that matches the specific criteria
	 * @param string Table to check
	 * @param array Associative array of field values to match
	 * @return boolean True if any matching record exists, false if not
	 * <code>DB::exists( 'mytable', array( 'fieldname' => 'value' ) );</code>
	 **/
	public static function exists( $table, $keyfieldvalues )
	{
		return DB::instance()->connection->exists( $table, $keyfieldvalues );
	}

	/**
	 * function update
	 * Updates any record that matches the specific criteria
	 * A new row is inserted if no existing record matches the criteria
	 * @param string $table Table to update
	 * @param array $fieldvalues Associative array of field values to set
	 * @param array $keyfields Associative array of field values to match
	 * @return boolean True on success, false if not
	 * <code>DB::update( 'mytable', array( 'fieldname' => 'newvalue' ), array( 'fieldname' => 'value' ) );</code>
	 **/
	public static function update( $table, $fieldvalues, $keyfields )
	{
		return DB::instance()->connection->update( $table, $fieldvalues, $keyfields );
	}

	/**
	 * Deletes any record that matches the specific criteria
	 * @param string Table to delete from
	 * @param array Associative array of field values to match
	 * @return boolean True on success, false if not
	 * <code>DB::delete( 'mytable', array( 'fieldname' => 'value' ) );</code>
	 */
	public static function delete( $table, $keyfields )
	{
		return DB::instance()->connection->delete( $table, $keyfields );
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
	public static function last_insert_id()
	{
		return DB::instance()->connection->last_insert_id( func_num_args() == 1 ? func_get_arg( 0 ) : '' );
	}

	/**
	 * Returns number of rows affected by the last DELETE, INSERT, or UPDATE
	 *
	 * @return int The number of rows affected.
	 */
	public static function row_count()
	{
		return DB::instance()->connection->row_count();
	}

	/**
	 * Automatic database diffing function, used for determining required database upgrades.
	 *
	 * @param queries array of create table and insert statements which constitute a fresh install
	 * @param (optional)  execute should the queries be executed against the database or just simulated. default = true
	 * @param (optional) silent silent running with no messages printed? default = true
	 * @return  string			translated SQL string
	 */
	public static function dbdelta( $queries, $execute = true, $silent = true, $doinserts = false )
	{
		return DB::instance()->connection->dbdelta( $queries, $execute, $silent, $doinserts );
	}

	/**
	 * Upgrade data in the database between database revisions
	 *
	 * @param integer $old_version Optional version to upgrade to
	 */
	public static function upgrade( $old_version )
	{
		return DB::instance()->connection->upgrade( $old_version );
	}

	public static function upgrade_pre( $old_version )
	{
		return DB::instance()->connection->upgrade_pre( $old_version );
	}

	public static function upgrade_post( $old_version )
	{
		return DB::instance()->connection->upgrade_post( $old_version );
	}

	public static function get_driver_name()
	{
		return DB::instance()->connection->get_driver_name();
	}

	public static function get_driver_version()
	{
		return DB::instance()->connection->get_driver_version();
	}

	/**
	 * Returns a list of tables the DB currently knows about.
	 *
	 * @return array The list of tables.
	 */
	public static function list_tables()
	{
		return DB::instance()->connection->list_tables();
	}

	/**
	 * Check whether there is an existing connection to a database.
	 *
	 * @return boolean
	 */
	public static function is_connected()
	{
		return (DB::instance()->connection instanceof DatabaseConnection && DB::instance()->connection->is_connected());
	}

	/**
	 * Check whether there is a transaction underway.
	 *
	 * @return boolean
	 */
	public static function in_transaction()
	{
		return DB::instance()->connection->in_transaction();
	}

	/**
	 * Return a PDO-quoted string appropriate for the DB backend we're using.
	 *
	 * If you're using this then there's 99+% probability you're building your queries the wrong way!
	 *
	 * @param string $string The string to quote.
	 * @return string A DB-safe quoted string.
	 */
	public static function quote( $string )
	{
		return DB::instance()->connection->quote( $string );
	}
}

?>
