<?php
/**
 * Habari database specific connection class
 *
 * @package Habari
 */

class SQLiteConnection extends DatabaseConnection
{
	 /**
	 * database specific SQL translation function, loosely modelled on the
	 * internationalization _t()  function
	 * Call with a database independent SQL string and it will be translated
	 * to a SQLite specific SQL string
	 *
	 * @param string $sql database independent SQL
	 * @param array $args An array of SQL arguments
	 * @return  string	translated SQL string
	 */
	function sql_t( $sql, &$args )
	{
		$sql = preg_replace_callback( '%concat\(([^)]+?)\)%i', array( &$this, 'replace_concat' ), $sql );
		$sql = preg_replace( '%DATE_SUB\s*\(\s*NOW\(\s*\)\s*,\s*INTERVAL\s+([0-9]+)\s+DAY\s*\)%ims', 'date(\'now\', \'-${1} days\')', $sql );
		$sql = preg_replace( '%OPTIMIZE TABLE ([^ ]*)%i', 'VACUUM;', $sql );
		//$sql= preg_replace( '%YEAR\s*\(\s*([^ ]*)\s*\)%ims', 'strftime(\'%Y\', ${1})', $sql );
		//$sql= preg_replace( '%MONTH\s*\(\s*([^ ]*)\s*\)%ims', 'strftime(\'%m\', ${1})', $sql );
		//$sql= preg_replace( '%DAY\s*\(\s*([^ ]*)\s*\)%ims', 'strftime(\'%d\', ${1})', $sql );
		$sql = preg_replace( '%YEAR\s*\(\s*FROM_UNIXTIME\s*\(\s*([^ ]*)\s*\)\s*\)%ims', 'strftime(\'%Y\', ${1}, \'unixepoch\')', $sql );
		$sql = preg_replace( '%MONTH\s*\(\s*FROM_UNIXTIME\s*\(\s*([^ ]*)\s*\)\s*\)%ims', 'strftime(\'%m\', ${1}, \'unixepoch\')', $sql );
		$sql = preg_replace( '%DAY\s*\(\s*FROM_UNIXTIME\s*\(\s*([^ ]*)\s*\)\s*\)%ims', 'strftime(\'%d\', ${1}, \'unixepoch\')', $sql );
		$sql = preg_replace( '%TRUNCATE \s*([^ ]*)%i', 'DELETE FROM ${1}', $sql );
		$sql = preg_replace( '%RAND\s*\(\s*\)%i', 'RANDOM()', $sql );
		foreach($args as &$arg) {
			if($arg === false) {
				$arg = 0;
			}
		}
		return $sql;
	}

	/**
	 * Replaces the MySQL CONCAT function with SQLite-compatible statements
	 * @todo needs work, kind of sucky conversion
	 * @param array $matches Matches from the regex in sql_t()
	 * @return string The replacement for the MySQL SQL
	 * @see SQLiteConnection::sql_t()
	 */
	function replace_concat( $matches )
	{
		$innards = explode( ',', $matches[1] );
		return implode( ' || ', $innards );
	}

	/**
	 * Connect to SQLite
	 * Overrides the DatabaseConnection to return false if the SQLite file doesn't exist.
	 *
	 * @param connection_string string a PDO connection string
	 * @param db_user string the database user name
	 * @param db_pass string the database user password
	 * @return boolean True if connection succeeded, false if not.
	 */
	public function connect( $connect_string, $db_user, $db_pass )
	{
		list( $type, $file )= explode( ':', $connect_string, 2 );
		if ( $file == basename( $file ) ) {
			if ( file_exists( HABARI_PATH . '/' . $file ) ) {
				$file = HABARI_PATH . '/' . $file;
			}
			else {
				$file = HABARI_PATH . '/' . Site::get_path( 'user', true ) . $file;
			}
			$connect_string = implode( ':', array( $type, $file ) );
		}
		if ( file_exists( $file ) && !is_writable( $file ) ) {
			die( _t( 'Database file "%s" must be writable.', array($file) ) );
		}
		$conn = parent::connect( $connect_string, $db_user, $db_pass );
		$this->exec( 'PRAGMA synchronous = OFF' );
		return $conn;
	}

		/**
	 * automatic diffing function - used for determining required database upgrades
	 * based on Owen Winkler's microwiki upgrade function
	 *
	 * @param queries array of create table and insert statements which constitute a fresh install
	 * @param (optional)  execute should the queries be executed against the database or just simulated. default = true
	 * @param (optional) silent silent running with no messages printed? default = true
	 * @return  string			translated SQL string
	 */
	function dbdelta( $queries, $execute = true, $silent = true, $doinserts = false )
	{
		if ( !is_array( $queries ) ) {
			$queries = explode( ';', $queries );
			if ( '' == $queries[count( $queries ) - 1] ) {
				array_pop( $queries );
			}
		}

		$cqueries = array();
		$indexqueries = array();
		$iqueries = array();
		$pqueries = array();
		$for_update = array();
		$allqueries = array();

		foreach ( $queries as $qry ) {
			if ( preg_match( "|CREATE TABLE ([^ ]*)|", $qry, $matches ) ) {
				$cqueries[strtolower( $matches[1] )] = $qry;
				$for_update[$matches[1]] = 'Created table '.$matches[1];
			}
			else if ( preg_match( "|CREATE (UNIQUE )?INDEX ([^ ]*)|", $qry, $matches ) ) {
				$indexqueries[] = $qry;
			}
			else if ( preg_match( "|INSERT INTO ([^ ]*)|", $qry, $matches ) ) {
				$iqueries[] = $qry;
			}
			else if ( preg_match( "|UPDATE ([^ ]*)|", $qry, $matches ) ) {
				$iqueries[] = $qry;
			}
			else if ( preg_match ( "|PRAGMA ([^ ]*)|", $qry, $matches ) ) {
				$pqueries[] = $qry;
			}
			else {
				// Unrecognized query type
			}
		}

		// Merge the queries into allqueries; pragmas MUST go first
		$allqueries = array_merge($pqueries);

		$tables = $this->get_column( "SELECT name FROM sqlite_master WHERE type = 'table';" );

		foreach ( $cqueries as $tablename => $query ) {
			if ( in_array( $tablename, $tables ) ) {
				$sql = $this->get_value( "SELECT sql FROM sqlite_master WHERE type = 'table' AND name='" . $tablename . "';" );
				$sql = preg_replace( '%\s+%', ' ', $sql ) . ';';
				$query = preg_replace( '%\s+%', ' ', $query );
				if ( $sql != $query ) {
					$this->query("ALTER TABLE {$tablename} RENAME TO {$tablename}__temp;");
					$this->query($query);

					$new_fields_temp = $this->get_results( "pragma table_info({$tablename});" );
					$new_fields = array();
					foreach ( $new_fields_temp as $field ) {
						$new_fields[$field->name] = $field;
					}
					$old_fields = $this->get_results( "pragma table_info({$tablename}__temp);" );
					$new_field_names = array_map(array($this, 'filter_fieldnames'), $new_fields);
					$old_field_names = array_map(array($this, 'filter_fieldnames'), $old_fields);
					$used_field_names = array_intersect($new_field_names, $old_field_names);
					$used_field_names = implode(',', $used_field_names);
					$needed_fields = array_diff($new_field_names, $old_field_names);
					foreach ( $needed_fields as $needed_field_name ) {
						$used_field_names .= ",'" . $new_fields[$needed_field_name]->dflt_value . "' as " . $needed_field_name;
					}

					$this->query("INSERT INTO {$tablename} SELECT {$used_field_names} FROM {$tablename}__temp;");
					$this->query("DROP TABLE {$tablename}__temp;");
				}
			}
			else {
				$allqueries[] = $query;
			}
		}

		// Drop all indices that we created, they'll get recreated by indexqueries below.
		// The other option would be to loop through the indices, comparing with indexqueries, and only drop the ones that have changed.
		$indices = DB::get_column( "SELECT name FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_autoindex_%'" );
		foreach ( $indices as $name ) {
			DB::exec( 'DROP INDEX ' . $name );
		}

		$allqueries = array_merge( $allqueries, $indexqueries );
		if ( $doinserts ) {
			$allqueries = array_merge( $allqueries, $iqueries );
		}

		if ( $execute ) {
			DB::exec( 'PRAGMA cache_size=4000' );
			foreach ( $allqueries as $query ) {
				if ( !$this->query( $query ) ) {
					$this->get_errors();
					return false;
				}
			}
		}

		return $allqueries;
	}

	/**
	 * Execute a stored procedure
	 *
	 * @param   procedure   name of the stored procedure
	 * @param   args        arguments for the procedure
	 * @return  mixed       whatever the procedure returns...
	 * Not supported with SQLite
	 */
	public function execute_procedure( $procedure, $args = array() )
	{
		die( _t( 'not yet supported on SQLite' ) );
	}

	/**
	 * Run all of the upgrades slated for pre-dbdelta since the last database revision.
	 *
	 * @param integer $old_version The current version of the database that is being upgraded
	 * @return boolean True on success
	 */
	public function upgrade_pre( $old_version, $upgrade_path = '' )
	{
		return parent::upgrade( $old_version, dirname(__FILE__) . '/upgrades/pre');
	}

	/**
	 * Run all of the upgrades slated for post-dbdelta since the last database revision.
	 *
	 * @param integer $old_version The current version of the database that is being upgraded
	 * @return boolean True on success
	 */
	public function upgrade_post( $old_version, $upgrade_path = '' )
	{
		return parent::upgrade( $old_version, dirname(__FILE__) . '/upgrades/post');
	}

	/**
	 * Filter out the fieldnames from whole pragma rows
	 *
	 * @param StdClass $row A row result from a SQLite PRAGMA table_info query
	 * @return string The name of the associated field
	 */
	protected function filter_fieldnames($row)
	{
		return $row->name;
	}

}
?>
