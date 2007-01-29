<?php

/**
 * Habari DB Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

if (!defined('DEBUG'))
	define('DEBUG', true);

class DB extends Singleton
{
	private $connection= null;
	
	/**
	 * Enables singleton working properly
	 * 
	 * @see singleton.php
	 */
	static protected function instance()
	{
		return parent::instance( get_class() );
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
			has private database connection instance been created yet? if not, do that first.
			then check if we have a pre-existing connection. If yes, short circuit processing
			if not; call the connect method on our private instance
		*/
		if ( NULL == DB::instance()->connection ) {
			DB::instance()->connection= new DatabaseConnection();
		}

		if ( FALSE != DB::instance()->connection->is_connected() ) {
			return TRUE;
		}
		
		if ( func_num_args() > 0 ) {
			$connect_string= func_get_arg( 0 );
			$db_user= func_get_arg( 1 );
			$db_pass= func_get_arg( 2 );
		}
		else {
			/* We use the config.php variables */
			$connect_string= $GLOBALS['db_connection']['connection_string'];
			$db_user= $GLOBALS['db_connection']['username'];
			$db_pass= $GLOBALS['db_connection']['password'];
		}
		return DB::instance()->connection->connect ($connect_string, $db_user, $db_pass);
	}
	
	public static function disconnect()
	{
		if ( NULL == DB::instance()->connection ) {
			return TRUE;
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
	 * @param name  the table name
	**/
	public static function register_table( $name )
	{		
		DB::instance()->connection->register_table( $name );
	}

	/**
	 * Sets the fetch mode for return calls from PDOStatement
	 *
	 * @param mode  One of the PDO::FETCH_MODE integers
	 */
	public static function set_fetch_mode( $mode )
	{
		DB::instance()->connection->set_fetch_mode( $mode );
	}

	/**
	 * Sets the class to fetch, if fetch mode is PDO::FETCH_CLASS
	 *
	 * @param class_name  Name of class to create during fetch
	 */
	public static function set_fetch_class( $class_name )
	{
		DB::instance()->connection->set_fetch_class( $class_name );
	}

	/**
	 * Queries the database for a given SQL command.
	 * @param query       the SQL query text
	 * @param args        array of values to use for placeholder replacement
	 * @param class_name  (optional) name of class name to wrangle returned data to
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
	public static function execute_procedure( $procedure, $args= array() )
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
	public static function commit() {
		DB::instance()->connection->commit();
	}

	/**
	 * Returns query profiles
	 *
	 * @return  array an array of query profiles
	 */
	public function get_profiles()
	{
		return DB::instance()->connection->get_profiles();
	}

	/**
	 * Adds an error to the internal collection
	 *
	 * @param   error   array('query'=>query, 'error'=>errorInfo)
	 */
	private function add_error( $error )
	{
		DB::instance()->connection->add_error( $error );
	}
	
	/**
	 * Returns error data gathered from database connection
	 * @return array An array of error data	 
	 */	  	 	
	public function get_errors()
	{
		return DB::instance()->connection->get_errors();
	}
	
	/**
	 * Determines if there have been errors since the last clear_errors() call
	 * @return boolean True if there were errors, false if not
	 **/	 	 	 	
	public function has_errors()
	{
		return DB::instance()->connection->has_errors();
	}
	
	/**
	 * Updates the last error pointer to simulate resetting the error array
	 **/	 	 	
	public function clear_errors()
	{
		DB::instance()->connection->clear_errors(); 
	}

	/**
	 * Returns only the last error info
	 * @return array Data for the last error	 
	 **/
	public function get_last_error()
	{		
		return DB::instance()->connection->get_last_error();
	}

	/**
	 * Execute a query and return the results as an array of objects
	 * @param query   the query to execute
	 * @param args    array of arguments to pass for prepared statements
	 * @param string Optional class name for row result objects	 
	 * @return array An array of QueryRecord or the named class each containing the row data
	 * <code>$ary = DB::get_results( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>
	 **/	 	 	 	 
	public function get_results( $query, $args = array() )
	{
		if ( func_num_args() == 3 ) {
			$class_name= func_get_arg( 2 );
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
	public function get_row( $query, $args = array() )
	{
		if ( func_num_args() == 3 ) {
			$class_name= func_get_arg( 2 );
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
	public function get_column( $query, $args = array() )
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
	public function get_value( $query, $args = array() )
	{
		return DB::instance()->connection->get_value( $query,  $args );
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
		return DB::instance()->connection->insert( $table, $fieldvalues );
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
		return DB::instance()->connection->exists( $table, $keyfieldvalues );
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
		 return DB::instance()->connection->update( $table, $fieldvalues, $keyfields );		
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
	public function last_insert_id() 
	{
		return DB::instance()->connection->last_insert_id( func_num_args() == 1 ? func_get_arg( 0 ) : '' );
	}
}

?>