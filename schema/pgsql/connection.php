<?php
/**
 * Habari database specific connection class
 *
 * @package Habari
 */

class PGSQLConnection extends DatabaseConnection
{
	/**
	 * Extends default connection method. It will be useful in order to
	 * allow accents and other DB-centric global commands.
	 *
	 * @param string $connect_string a PDO connection string
	 * @param string $db_user the database user name
	 * @param string $db_pass the database user password
	 * @return boolean true on success, false on error
	 */
	public function connect( $connect_string, $db_user, $db_pass )
	{
		// If something went wrong, we don't need to exec the specific commands.
		if ( !parent::connect( $connect_string, $db_user, $db_pass ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Database specific SQL translation function, loosely modelled on the
	 * internationalization _t() function.
	 * Call with a database independent SQL string and it will be translated
	 * to a PostgreSQL specific SQL string.
	 *
	 * @param string $sql database independent SQL
	 * @param array $args An array of SQL arguments
	 * @return string translated SQL string
	 */
	function sql_t( $sql, &$args )
	{
		$sql = preg_replace_callback( '%concat\(([^)]+?)\)%i', array( &$this, 'replace_concat' ), $sql );
		$sql = preg_replace( '%DATE_SUB\s*\(\s*NOW\(\s*\)\s*,\s*INTERVAL\s+([0-9]+)\s+DAY\s*\)%ims', 'NOW() - INTERVAL \'${1} DAYS\'', $sql );
		$sql = preg_replace( '%OPTIMIZE TABLE ([^ ]*)%i', 'VACUUM ${1};', $sql );
		//$sql= preg_replace( '%YEAR\s*\(\s*([^ ]*)\s*\)%ims', 'date_part(\'year\', ${1})', $sql );
		//$sql= preg_replace( '%MONTH\s*\(\s*([^ ]*)\s*\)%ims', 'date_part(\'month\', ${1})', $sql );
		//$sql= preg_replace( '%DAY\s*\(\s*([^ ]*)\s*\)%ims', 'date_part(\'day\', ${1})', $sql );
		$sql = preg_replace( '%YEAR\s*\(\s*FROM_UNIXTIME\s*\(\s*([^ ]*)\s*\)\s*\)%ims', 'date_part(\'year\', \'epoch\'::timestamptz + ${1} * \'1 second\'::interval)', $sql );
		$sql = preg_replace( '%MONTH\s*\(\s*FROM_UNIXTIME\s*\(\s*([^ ]*)\s*\)\s*\)%ims', 'date_part(\'month\',  \'epoch\'::timestamptz + ${1} * \'1 second\'::interval)', $sql );
		$sql = preg_replace( '%DAY\s*\(\s*FROM_UNIXTIME\s*\(\s*([^ ]*)\s*\)\s*\)%ims', 'date_part(\'day\',  \'epoch\'::timestamptz + ${1} * \'1 second\'::interval)', $sql );
		$sql = preg_replace('%LIKE\s+((\'|").*\2)%iUms', 'ILIKE \1', $sql);
		$sql = preg_replace( '%RAND\s*\(\s*\)%i', 'RANDOM()', $sql );
		return $sql;
	}

	/**
	 * Replaces the MySQL CONCAT function with PostgreSQL-compatible statements
	 * @todo needs work, kind of sucky conversion
	 * @param array $matches Matches from the regex in sql_t()
	 * @return string The replacement for the MySQL SQL
	 * @see PGSQLConnection::sql_t()
	 */
	function replace_concat( $matches )
	{
		$innards = explode( ',', $matches[1] );
		return implode( ' || ', $innards );
	}

	/**
	 * automatic diffing function - used for determining required database upgrades
	 * based on Owen Winkler's microwiki upgrade function
	 *
	 * @param queries array of create table and insert statements which constitute a fresh install
	 * @param (optional)  execute should the queries be executed against the database or just simulated. default = true
	 * @param (optional) silent silent running with no messages printed? default = true
	 * @param boolean $doinserts (optional) Execute all insert queries found, default=false
	 * @return  array list of updates made
	 */
	function dbdelta( $queries, $execute = true, $silent = true, $doinserts = false )
	{
		if ( !is_array( $queries ) ) {
			$queries = explode( ';', $queries );
			if ( '' == $queries[count( $queries ) - 1] ) {
				array_pop( $queries );
			}
		}

		$cseqqueries = array();
		$cqueries = array();
		$alterseqqueries = array();
		$indexqueries = array();
		$iqueries = array();
		$for_update = array();
		$indices = array();

		foreach ( $queries as $qry ) {
			if ( preg_match( "|CREATE TABLE\s+(\w*)|", $qry, $matches ) ) {
				$cqueries[strtolower( $matches[1] )] = $qry;
				$for_update[$matches[1]] = 'Created table ' . $matches[1];
			}
			else if ( preg_match( "|CREATE (UNIQUE )?INDEX ([^ ]*) ON ([^ ]*)|", $qry, $matches ) ) {
				$indexqueries[strtolower( $matches[3] )] = $qry;
			}
			else if ( preg_match( "|CREATE DATABASE ([^ ]*)|", $qry, $matches ) ) {
				array_unshift( $cqueries, $qry );
			}
			else if ( preg_match( "|CREATE SEQUENCE ([^ ]*)|", $qry, $matches ) ) {
				$cseqqueries[strtolower( $matches[1] )] = $qry;
			}
			else if ( preg_match( "|ALTER SEQUENCE ([^ ]*)|", $qry, $matches ) ) {
				$alterseqqueries[] = $qry;
			}
			else if ( preg_match( "|INSERT INTO ([^ ]*)|", $qry, $matches ) ) {
				$iqueries[] = $qry;
			}
			else if ( preg_match( "|UPDATE ([^ ]*)|", $qry, $matches ) ) {
				$iqueries[] = $qry;
			}
			else {
				// Unrecognized query type
			}
		}

		// Checking sequence
		if ( $seqnames = $this->get_results(
			"SELECT c.relname as name,
				CASE c.relkind WHEN 'r' THEN 'table' WHEN 'v' THEN 'view' WHEN 'i' THEN 'index' WHEN 'S' THEN 'sequence' WHEN 's' THEN 'special' END AS type
			   FROM pg_catalog.pg_class c
			   JOIN pg_catalog.pg_roles r ON r.oid = c.relowner
				LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
				WHERE c.relkind IN ('S','')
				AND n.nspname NOT IN ('pg_catalog', 'pg_toast')
				AND pg_catalog.pg_table_is_visible(c.oid)
				ORDER BY 1,2;" ) ) {
			foreach ( ( array ) $seqnames as $seqname ) {
				if ( array_key_exists( strtolower( $seqname->name ), $cseqqueries ) ) {
					unset( $cseqqueries[$seqname->name] );
				}
			}
		}

		if ( $tables = $this->get_column(
			"SELECT table_name
				 FROM information_schema.tables
				 WHERE table_type = 'BASE TABLE'
				 AND table_schema NOT IN ('pg_catalog', 'information_schema');" ) ) {
			foreach ( $tables as $table ) {
				if ( array_key_exists( strtolower( $table ), $cqueries ) ) {
					unset( $cfields );
					$cfields = array();
					unset( $indices );
					$indices = array();
					preg_match( "|\((.*)\)|ms", $cqueries[strtolower( $table )], $match2 );
					$qryline = trim( $match2[1] );

					$flds = explode( "\n", $qryline );
					foreach ( $flds as $fld ) {
						preg_match( "|^([^ ]*)|", trim( $fld ), $fvals );
						$fieldname = $fvals[1];
						$validfield = true;
						switch ( strtolower( $fieldname ) ) {
							case '':
							case 'primary':
							case 'index':
							case 'fulltext':
							case 'unique':
							case 'key':
								$validfield = false;
								$indices[] = trim( trim( $fld ), ", \n" );
								break;
						}
						$fld = trim( $fld );
						if ( $validfield ) {
							$cfields[strtolower( $fieldname )] = trim( $fld, ", \n" );
						}
					}
					if ( isset( $indexqueries[$table] ) ) {
						preg_match( "|CREATE (UNIQUE )?INDEX ([^ ]*) ON ([^ ]*) \((.*)\)|ms", $indexqueries[$table], $matches );
						if ( $matches ) {
							$indices[] = ' (' . preg_replace( '/\s/ms', '', $matches[4] ) . ')';
						}
					}
					$tablefields = $this->get_results(
						"SELECT column_name AS field,
								  data_type AS type,
								  column_default AS default,
								  is_nullable AS null,
								  character_maximum_length AS length,
								  numeric_precision
						   FROM information_schema.columns
						   WHERE table_name = '{$table}'
						   ORDER BY ordinal_position;" );

					foreach ( ( array ) $tablefields as $tablefield ) {
						if ( array_key_exists( strtolower( $tablefield->field ), $cfields ) ) {
							preg_match( '/^(.*) (.*)( |$)/U', $cfields[strtolower( $tablefield->field )], $matches );
							$cfieldname = $matches[1];
							$cfieldtype = $matches[2];
							$fieldtype = $tablefield->type;

							if ( stripos( $fieldtype, 'character' ) === false ) {
								// do nothing
							}
							else {
								if ( stripos( $fieldtype, 'varying' ) > 0 ) {
									$fieldtype = 'varchar(' . $tablefield->length . ')';
								}
								else {
									$fieldtype = 'char(' . $tablefield->length . ')';
								}
							}
							if ( stripos( $fieldtype, 'timestamp' ) === false ) {
								// do nothing
							}
							else {
								$fieldtype = 'timestamp';
							}
							if ( strtolower( $fieldtype ) != strtolower( $cfieldtype ) ) {
								$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN " . $cfieldname . " TYPE " . $cfieldtype;
								$for_update[$table.'.'.$tablefield->field] = "Changed type of {$table}.{$tablefield->field} from {$tablefield->type} to {$fieldtype}";
							}
							if ( preg_match( "| DEFAULT ([^ ]*)|i", $cfields[strtolower( $tablefield->field )], $matches ) ) {
								$default_value = $matches[1];
								if ( strpos( '::', $tablefield->default) === false ) {
									$tablefield_default = $tablefield->default;
								}
								else {
									preg_match( '|(.*)::|', $tablefield->default, $matches );
									$tablefield_default = $matches[1] . ( preg_match( '|^nextval|i', $matches[1] ) > 0 ? ')' : '' );
								}
								if ( $tablefield_default != $default_value ) {
									$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->field} SET DEFAULT {$default_value}";
									$for_update[$table.'.'.$tablefield->field] = "Changed default value of {$table}.{$tablefield->field} from {$tablefield->default} to {$default_value}";
								}
							}
							elseif ( strlen( $tablefield->default) > 0 ) {
								$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->field} DROP DEFAULT";
								$for_update[$table.'.'.$tablefield->field] = "Dropped default value of {$table}.{$tablefield->field}";
							}
							unset( $cfields[strtolower( $tablefield->field )] );
						}
						else {
							// This field exists in the table, but not in the creation queries?
						}
					}
					foreach ( $cfields as $fieldname => $fielddef ) {
						$cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";
						$for_update[$table.'.'.$fieldname] = 'Added column '.$table.'.'.$fieldname;
					}
					$tableindices = $this->get_results(
						"SELECT c2.relname AS key_name,
									i.indisprimary AS is_primary,
									i.indisunique AS is_unique,
									i.indisclustered AS is_clustered,
									i.indisvalid AS is_valid,
									pg_catalog.pg_get_indexdef(i.indexrelid, 0, true) AS index_def,
									c2.reltablespace
							 FROM pg_catalog.pg_class c, pg_catalog.pg_class c2,
									pg_catalog.pg_index i
							 WHERE c.oid = (
								SELECT c.oid
									FROM pg_catalog.pg_class c
									LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
									WHERE c.relname = '{$table}'
									AND pg_catalog.pg_table_is_visible(c.oid)
								)
								AND c.oid = i.indrelid AND i.indexrelid = c2.oid
								ORDER BY i.indisprimary DESC, i.indisunique DESC,
									c2.relname;" );

					if ( $tableindices ) {
						unset( $index_ary );
						$index_ary = array();
						foreach ( ( array ) $tableindices as $tableindex ) {
							$keyname = $tableindex->key_name;
							preg_match( '/\((.*)\)/', $tableindex->index_def, $matches );
							$fieldnames = str_replace( '"', '', str_replace( ' ', '', $matches[1] ) );
							$index_ary[$keyname]['fieldnames'] = $fieldnames;
							$index_ary[$keyname]['unique'] = ( $tableindex->is_unique == true ) ? true : false;
							$index_ary[$keyname]['primary'] = ( $tableindex->is_primary == true ) ? true : false;
						}

						foreach ( $index_ary as $index_name => $index_data ) {
							$index_string = '';
							if ( $index_data['primary'] ) {
								$index_string .= 'PRIMARY KEY ';
							}
							else if ( $index_data['unique'] ) {
								$index_string .= 'UNIQUE ';
							}
							$index_columns = $index_data['fieldnames'];

							$index_string = rtrim( $index_string, ' ' );
							$index_string .= ' (' . $index_columns . ')';
							if ( !( ( $aindex = array_search( $index_string, $indices ) ) === false ) ) {
								unset( $indices[$aindex] );
								unset( $indexqueries[$table] );
							}
							else {
								if ( $index_data['unique'] ) {
									$cqueries[] = "ALTER TABLE {$table} DROP CONSTRAINT {$index_name}";
								}
								else {
									$cqueries[] = "DROP INDEX {$index_name}";
								}
							}
						}
					}
					foreach ( $indices as $index ) {
						$cqueries[] = "ALTER TABLE {$table} ADD $index";
						$for_update[$table . '.' . $fieldname] = 'Added index ' . $table . ' ' . $index;
					}
					unset( $cqueries[strtolower( $table )] );
					unset( $for_update[strtolower( $table )] );
				}
				else {
				}
			}
		}

		$allqueries = array_merge( $cseqqueries, $cqueries, $alterseqqueries );
		if ( $doinserts ) {
			$allqueries = array_merge( $allqueries, $iqueries );
		}
		foreach ( $indexqueries as $tablename => $indexquery ) {
			$allqueries = array_merge( $allqueries, ( array ) $indexquery );
		}

		if ( $execute ) {
			foreach ( $allqueries as $query ) {
				if ( !$this->exec( $query ) ) {
					$this->get_errors();
					return false;
				}
			}
		}

		if ( !$silent ) {
			if ( count( $for_update ) > 0 ) {
				echo "<ul>\n";
				foreach ( $for_update as $upgrade ) {
					echo "<li>{$upgrade}</li>\n";
				}
				echo "</ul>\n";
			}
			else {
				echo "<ul><li>" . _t('No Upgrades') . "</li></ul>";
			}
		}
		return $for_update;
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
}
?>
