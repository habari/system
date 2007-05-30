<?php
/**
 * Habari database specific connection class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class MySQLConnection extends DatabaseConnection 
{	
	/** 
	 * Database specific SQL translation function, loosely modelled on the 
	 * internationalization _t() function. 
	 * Call with a database independent SQL string and it will be translated
	 * to a MySQL specific SQL string.
	 * 
	 * @param $sql database independent SQL
	 * @return string translated SQL string
	 * @todo Actually implement this.
	 */
	function sql_t( $sql ) 
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
	 * @return  array list of updates made
	 */
	function dbdelta( $queries, $execute= true, $silent= true ) 
	{
		if( !is_array($queries) ) {
			$queries = explode( ';', $queries );
			if('' == $queries[count($queries) - 1]) array_pop($queries);
		}

		$cqueries = array();
		$iqueries = array();
		$for_update = array();
		$indices = array();

		foreach($queries as $qry) {
			if(preg_match("|CREATE TABLE\s+(\w*)|", $qry, $matches)) {
				$cqueries[strtolower($matches[1])] = $qry;
				$for_update[$matches[1]] = 'Created table '.$matches[1];
			}
			else if(preg_match("|CREATE DATABASE ([^ ]*)|", $qry, $matches)) {
				array_unshift($cqueries, $qry);
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

		if($tables = $this->get_column('SHOW TABLES;')) {
			foreach($tables as $table) {
				if( array_key_exists(strtolower($table), $cqueries) ) {
					unset($cfields);
					$cfields = array();
					unset($indices);
					$indices = array();
					preg_match("|\((.*)\)|ms", $cqueries[strtolower($table)], $match2);
					$qryline = trim($match2[1]);

					$flds = explode("\n", $qryline);
					foreach($flds as $fld) {
						preg_match("|^([^ ]*)|", trim($fld), $fvals);
						$fieldname = $fvals[1];
						$validfield = true;
						switch(strtolower($fieldname))
						{
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
						if($validfield) {
							$cfields[strtolower($fieldname)] = trim($fld, ", \n");
						}
					}
					$tablefields = $this->get_results("DESCRIBE {$table};"); // SQLite specific pragma
					foreach((array)$tablefields as $tablefield) {
						if(array_key_exists(strtolower($tablefield->Field), $cfields)) {
							preg_match("|".$tablefield->Field." ([^ ]*( unsigned)?)|i", $cfields[strtolower($tablefield->Field)], $matches);
							$fieldtype = $matches[1];
							// Use default field sizes
							$field_default_names = array('/(?'.'>\bint\s*)(?!\(.*$)/i','/(?'.'>smallint\s*)(?!\(.*$)/i','/(?'.'>tinyint\s*)(?!\(.*$)/i','/(?'.'>bigint\s*)(?!\(.*$)/i');
							$field_sized_names = array('int(10) ','smallint(5) ','tinyint(3) ','bigint(20) ');
							$fieldtype = preg_replace($field_default_names, $field_sized_names, $fieldtype);							
							if(strtolower($tablefield->Type) != strtolower($fieldtype)) {
								$cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN {$tablefield->Field} " . $cfields[strtolower($tablefield->Field)];
								$for_update[$table.'.'.$tablefield->Field] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
							}
							if(preg_match("| DEFAULT '(.*)'|i", $cfields[strtolower($tablefield->Field)], $matches)) {
								$default_value = $matches[1];
								if($tablefield->Default != $default_value)
								{
									$cqueries[] = "ALTER TABLE {$table} ALTER COLUMN {$tablefield->Field} SET DEFAULT '{$default_value}'";
									$for_update[$table.'.'.$tablefield->Field] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
								}
							}
							unset($cfields[strtolower($tablefield->Field)]);
						}
						else {
							// This field exists in the table, but not in the creation queries?
						}
					}
					foreach($cfields as $fieldname => $fielddef) {
						$cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";
						$for_update[$table.'.'.$fieldname] = 'Added column '.$table.'.'.$fieldname;
					}
					$tableindices = $this->get_results("SHOW INDEX FROM {$table};");

					if($tableindices) {
						unset($index_ary);
						$index_ary= array();
						foreach($tableindices as $tableindex) {
							$keyname = $tableindex->Key_name;
							$index_ary[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
							$index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0)?true:false;
						}
						foreach($index_ary as $index_name => $index_data) {
							$index_string = '';
							if($index_name == 'PRIMARY') {
								$index_string .= 'PRIMARY ';
							}
							else if($index_data['unique']) {
								$index_string .= 'UNIQUE ';
							}
							$index_string .= 'KEY ';
							if($index_name != 'PRIMARY') {
								$index_string .= $index_name;
							}
							$index_columns = '';
							foreach($index_data['columns'] as $column_data) {
								if($index_columns != '') $index_columns .= ', ';
								$index_columns .= $column_data['fieldname'];
								if($column_data['subpart'] != '') {
									$index_columns .= '('.$column_data['subpart'].')';
								}
							}
							$index_string = rtrim($index_string, ' ');
							$index_string .= ' ('.$index_columns.')';
							if(!(($aindex = array_search($index_string, $indices)) === false)) {
								unset($indices[$aindex]);
							}
						}
					}
					foreach($indices as $index) {
						$cqueries[] = "ALTER TABLE {$table} ADD $index";
						$for_update[$table.'.'.$fieldname] = 'Added index '.$table.' '.$index;
					}
					unset($cqueries[strtolower($table)]);
					unset($for_update[strtolower($table)]);
				} else {
				}
			}
		}

		$allqueries= $cqueries; //  Don't insert yet:  array_merge($cqueries, $iqueries);
		if($execute) {
			foreach($allqueries as $query) {
				if(!$this->exec($query))
				{
					$this->get_errors();
					return false;
				}
			}
		}

		if(!$silent)
		{
			if(count($for_update) > 0)
			{
				echo "<ul>\n";
				foreach($for_update as $upgrade)
				{
					echo "<li>{$upgrade}</li>\n";
				}
				echo "</ul>\n";
			}
			else
			{
			echo "<ul><li>No Upgrades</li></ul>";
			}
		}
		return $for_update;
	}	
}
?>
