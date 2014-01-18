<?php
/**
 * Habari database specific connection class
 *
 * @package Habari
 */

class MySQLConnection extends DatabaseConnection
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
	public function connect ( $connect_string, $db_user, $db_pass )
	{
		// If something went wrong, we don't need to exec the specific commands.
		if ( !parent::connect( $connect_string, $db_user, $db_pass ) ) {
			return false;
		}
		$this->pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, true );

		// Everything is OK. Let's update the charset!
		if ( !defined('MYSQL_CHAR_SET') ) {
			define('MYSQL_CHAR_SET', 'UTF8');
		}

		// SET NAMES defines character_set_client, character_set_results, and character_set_connection (which implicitly sets collation_connection) and therefore covers everything SET CHARACTER SET does, but uses the character set we tell it to, ignoring what the database is configured to use
		// 	http://dev.mysql.com/doc/refman/5.0/en/charset-connection.html
		$this->exec('SET NAMES ' . MYSQL_CHAR_SET);

		return true;
	}

	/**
	 * Database specific SQL translation function, loosely modelled on the
	 * internationalization _t() function.
	 * Call with a database independent SQL string and it will be translated
	 * to a MySQL specific SQL string.
	 *
	 * @param string $sql database independent SQL
	 * @param array $args An array of SQL arguments
	 * @return string translated SQL string
	 */
	function sql_t( $sql, &$args )
	{
		return $sql;
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
		$queries = str_replace('{$prefix}', $this->prefix, $queries);	//Converts {$prefix}table_name to prefix__table_name
		$queries = $this->filter_tables( $queries );	//Converts {table_name} to prefix__table_name

		if ( !is_array($queries) ) {
			$queries = explode( ';', $queries );
			if ( '' == $queries[count($queries) - 1] ) {
				array_pop($queries);
			}
		}

		$cqueries = array();
		$iqueries = array();
		$for_update = array();
		$indices = array();

		foreach ( $queries as $qry ) {
			if ( preg_match("|CREATE TABLE\s+(\w*)|", $qry, $matches) ) {
				$cqueries[strtolower($matches[1])] = $qry;
				$for_update[$matches[1]] = 'Created table '.$matches[1];
			}
			else if ( preg_match("|CREATE DATABASE ([^ ]*)|", $qry, $matches) ) {
				array_unshift($cqueries, $qry);
			}
			else if ( preg_match("|INSERT INTO ([^ ]*)|", $qry, $matches) ) {
				$iqueries[] = $qry;
			}
			else if ( preg_match("|UPDATE ([^ ]*)|", $qry, $matches) ) {
				$iqueries[] = $qry;
			}
			else {
				// Unrecognized query type
			}
		}

		if ( $tables = $this->get_column('SHOW TABLES;') ) {
			foreach ( $tables as $table ) {
				if ( array_key_exists(strtolower($table), $cqueries) ) {
					unset($cfields);
					$cfields = array();
					unset($indices);
					$indices = array();
					preg_match("|\((.*)\)|ms", $cqueries[strtolower($table)], $match2);
					$qryline = trim($match2[1]);

					$flds = explode("\n", $qryline);
					foreach ( $flds as $fld ) {
						preg_match("|^([^ ]*)|", trim($fld), $fvals);
						$fieldname = $fvals[1];
						$validfield = true;
						switch ( strtolower($fieldname) ) {
							case '':
							case 'primary':
							case 'index':
							case 'fulltext':
							case 'unique':
							case 'key':
								$validfield = false;
								$indices[] = trim(trim($fld), ", \n");
								break;
						}
						$fld = trim($fld);
						if ( $validfield ) {
							$cfields[strtolower($fieldname)] = trim($fld, ", \n");
						}
					}
					$tablefields = $this->get_results("DESCRIBE {$table};");
					foreach ( (array)$tablefields as $tablefield ) {
						if ( array_key_exists(strtolower($tablefield->Field), $cfields) ) {
							preg_match("|".$tablefield->Field." ([^ ]*( unsigned)?)|i", $cfields[strtolower($tablefield->Field)], $matches);
							$fieldtype = $matches[1];
							// Use default field sizes
							$field_default_names = array('/(?'.'>\bint\s*)(?!\(.*$)/i','/(?'.'>smallint\s*)(?!\(.*$)/i','/(?'.'>tinyint\s*)(?!\(.*$)/i','/(?'.'>bigint\s*)(?!\(.*$)/i');
							$field_sized_names = array('int(10) ','smallint(5) ','tinyint(3) ','bigint(20) ');
							$fieldtype = preg_replace($field_default_names, $field_sized_names, $fieldtype);
							if ( strtolower($tablefield->Type) != strtolower($fieldtype) ) {
								$cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN {$tablefield->Field} " . $cfields[strtolower($tablefield->Field)];
								$for_update[$table.'.'.$tablefield->Field] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
							}
							if ( preg_match("| DEFAULT ([^ ]*)|i", $cfields[strtolower($tablefield->Field)], $matches) ) {
								$default_value = $matches[1];
								if ( $tablefield->Default != $default_value && !(is_null($tablefield->Default) && strtoupper($default_value) == 'NULL')) {
									$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->Field} SET DEFAULT {$default_value}";
									$for_update[$table.'.'.$tablefield->Field] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
								}
							}
							elseif ( strlen( $tablefield->Default) > 0 ) {
								$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->Field} DROP DEFAULT";
								$for_update[$table.'.'.$tablefield->Field] = "Dropped default value of {$table}.{$tablefield->Field}";
							}
							unset($cfields[strtolower($tablefield->Field)]);
						}
						else {
							// This field exists in the table, but not in the creation queries?
						}
					}
					foreach ( $cfields as $fieldname => $fielddef ) {
						$cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";
						$for_update[$table.'.'.$fieldname] = 'Added column '.$table.'.'.$fieldname;
					}
					$tableindices = $this->get_results("SHOW INDEX FROM {$table};");

					if ( $tableindices ) {
						unset($index_ary);
						$index_ary = array();
						foreach ( $tableindices as $tableindex ) {
							$keyname = $tableindex->Key_name;
							$index_ary[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
							$index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0)?true:false;
						}
						foreach ( $index_ary as $index_name => $index_data ) {
							$index_string = '';
							if ( $index_name == 'PRIMARY' ) {
								$index_string .= 'PRIMARY ';
							}
							else if ( $index_data['unique'] ) {
								$index_string .= 'UNIQUE ';
							}
							$index_string .= 'KEY ';
							if ( $index_name != 'PRIMARY' ) {
								$index_string .= $index_name;
							}
							$index_columns = '';
							foreach ( $index_data['columns'] as $column_data ) {
								if ( $index_columns != '' ) {
								 	$index_columns .= ',';
								}
								$index_columns .= $column_data['fieldname'];
								if ( $column_data['subpart'] != '' ) {
									$index_columns .= '('.$column_data['subpart'].')';
								}
							}
							$index_string = rtrim($index_string, ' ');
							$index_string .= ' ('.$index_columns.')';
							if ( !(($aindex = array_search($index_string, $indices)) === false) ) {
								unset($indices[$aindex]);
							}
							else {
								preg_match( '|(^.*)\((.*)\)|', $index_string, $matches );
								$tindextype = $matches[1];
								if ( preg_match( '/^KEY|UNIQUE KEY/i', $tindextype ) > 0 ) {
									$cqueries[] = "ALTER TABLE {$table} DROP INDEX {$index_name}";
								}
								else {
									$cqueries[] = "ALTER TABLE {$table} DROP PRIMARY KEY";
								}
							}
						}
					}
					foreach ( $indices as $index ) {
						$cqueries[] = "ALTER TABLE {$table} ADD $index";
						$for_update[$table.'.'.$fieldname] = 'Added index '.$table.' '.$index;
					}
					unset($cqueries[strtolower($table)]);
					unset($for_update[strtolower($table)]);
				}
				else {
				}
			}
		}

		$allqueries = $cqueries;
		if ( $doinserts ) {
			$allqueries = array_merge($allqueries, $iqueries);
		}
		if ( $execute ) {
			foreach ( $allqueries as $query ) {
				if ( !$this->exec($query) ) {
					$this->get_errors();
					return false;
				}
			}
		}

		if ( !$silent ) {
			if ( count($for_update) > 0) {
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
