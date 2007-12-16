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
		return $sql;
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
		list($type, $file) = explode(':', $connect_string, 2);
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
	function dbdelta( $queries, $execute = true, $silent = true, $doinserts= false )
	{
		if( !is_array($queries) ) {
			$queries = explode( ';', $queries );
			if('' == $queries[count($queries) - 1]) array_pop($queries);
		}

		$cqueries = array();
		$iqueries = array();
		$for_update = array();

		foreach($queries as $qry) {
			if(preg_match("|CREATE TABLE ([^ ]*)|", $qry, $matches)) {
				$cqueries[strtolower($matches[1])] = $qry;
				$for_update[$matches[1]] = 'Created table '.$matches[1];
			}
			else if(preg_match("|INSERT INTO ([^ ]*)|", $qry, $matches)) {
				$iqueries[] = $qry;
			}
			else if(preg_match("|UPDATE ([^ ]*)|", $qry, $matches)) {
				$iqueries[] = $qry;
			}
			else {
				// Unrecognized query type
			}
		}

		if( $execute ) {
			$allqueries = $cqueries;
			if( $doinserts ) {
				$allqueries = array_merge($allqueries, $iqueries);
			}
			foreach($allqueries as $query) {
				if(!$this->query($query)) {
					$this->get_error(true);
					return false;
				}
			}
		}

		return $allqueries;
	}

}
?>
