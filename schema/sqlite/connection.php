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
	 * @param sql database independent SQL
	 * @return  string	translated SQL string
	 */
	function sql_t( $sql )
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
		return parent::connect( $connect_string, $db_user, $db_pass );
	}

		/**
	 * automatic diffing function - used for determining required database upgrades
	 * based on Owen Winkler's microwiki upgrade function
	 *
	 * @param queries array of create table and insert statements which constitute a fresh install
	 * @param (optional)  execute should the queries be executed against the database or just simulated. default = true
	 * @param (optional) silent silent running with no messages printed? default = true
	 * @return  string			translated SQL string
	 *** FIXME: SQLite diffing is horribly terribly broken. There is varying support for alter table and mucking with columns
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
				$cqueries[strtolower( $matches[1] )]= $qry;
				$for_update[$matches[1]]= 'Created table '.$matches[1];
			}
			else if ( preg_match( "|CREATE (UNIQUE )?INDEX ([^ ]*)|", $qry, $matches ) ) {
				$indexqueries[] = $qry;
			}
			else if ( preg_match( "|INSERT INTO ([^ ]*)|", $qry, $matches ) ) {
				$iqueries[]= $qry;
			}
			else if ( preg_match( "|UPDATE ([^ ]*)|", $qry, $matches ) ) {
				$iqueries[]= $qry;
			}
			else if ( preg_match ( "|PRAGMA ([^ ]*)|", $qry, $matches ) ) {
				$pqueries[]= $qry;
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
				$sql = $this->get_value( "SELECT sql FROM sqlite_master WHERE type = 'table' AND name='{" . $tablename . "}';" );
				$sql = preg_replace( '%\s+%', ' ', $sql ) . ';';
				$query = preg_replace( '%\s+%', ' ', $query );
				if ( $sql != $query ) {
					$allqueries[]= "ALTER TABLE {$tablename} RENAME TO {$tablename}__temp;";
					$allqueries[]= $query;
					$allqueries[]= "INSERT INTO {$tablename} SELECT * FROM {$tablename}__temp;";
					$allqueries[]= "DROP TABLE {$tablename}__temp;";
				}
			}
			else {
				$allqueries[]= $query;
			}
		}

		$allqueries = array_merge($allqueries, $indexqueries, $iqueries);
		if ( $execute ) {
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
	 * Run all of the upgrades since the last database revision.
	 *
	 * @param integer $old_version The current version of the database that is being upgraded
	 * @return boolean True on success
	 */
	public function upgrade( $old_version, $upgrade_path = '' )
	{
		return parent::upgrade( $old_version, dirname(__FILE__) . '/upgrades');
	}

}
?>
