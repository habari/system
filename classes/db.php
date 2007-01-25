<?php
/**
 * Habari DB Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */
if (!defined('DEBUG'))
  define('DEBUG', true);

class DB extends Singleton {
  private $fetch_mode= PDO::FETCH_CLASS;          // PDO Fetch mode
  private $fetch_class_name= 'QueryRecord';    // The default class name for fetching classes
  private $keep_profile= DEBUG;                   // keep profiling and timing information?
	private $pdo= NULL;                             // handle to the PDO interface
	private $pdo_statement= NULL;                   // handle for a PDOStatement
	private $sql_tables= array();                   // an array of table names that Habari knows
  private $errors= array();                       // an array of errors related to queries
  private $profiles= array();                     // an array of query profiles

  /**
   * Enables singleton working properly
   * 
   * @see singleton.php
   */
  static protected function instance() {
    return parent::instance(get_class());
  }


  /**
   * Loads a list of habari tables from the database
   *
   * @todo  Wish there were a cross platform method of 
   *        simply getting the tables from the DB.  Using
   *        the INFORMATION_SCHEMA interface, for instance,
   *        but I don't think that SQLite currently supports
   *        it.
   */
  private function load_tables() {
    /* Local variable caching */
    $db= DB::instance();

    if ($db->pdo == NULL) 
      $db->connect();

    $prefix= (isset($GLOBALS['db_connection']['prefix']) ? $GLOBALS['db_connection']['prefix'] : '');
    $db->sql_tables['posts']= $prefix . 'posts';
    $db->sql_tables['postinfo']= $prefix . 'postinfo';
    $db->sql_tables['posttype']= $prefix . 'posttype';
    $db->sql_tables['poststatus']= $prefix . 'poststatus';
    $db->sql_tables['options']= $prefix . 'options';
    $db->sql_tables['users']= $prefix . 'users';
    $db->sql_tables['userinfo']= $prefix . 'userinfo';
    $db->sql_tables['tags']= $prefix . 'tags';
    $db->sql_tables['comments']= $prefix . 'comments';
    $db->sql_tables['commentinfo']= $prefix . 'commentinfo';
    $db->sql_tables['tag2post']= $prefix . 'tag2post';
    $db->sql_tables['themes']= $prefix . 'themes';
    $db->sql_tables['theme_vars']= $prefix . 'theme_vars';
    $db->sql_tables['rewrite_rules']= $prefix . 'rewrite_rules';
    $db->sql_tables['rewrite_rule_args']= $prefix . 'rewrite_rule_args';
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
  public static function connect() {
    /* 
      Short-circuit out if we're already connected
      and the caller hasn't supplied function args
    */
    if (func_num_args() == 0 && DB::instance()->pdo != NULL)
      return true;

    if (func_num_args() > 0) {
      $connect_string= func_get_arg(0);
      $db_user= func_get_arg(1);
      $db_pass= func_get_arg(2);
    }
    else {
      /* We use the config.php variables */
      $connect_string= $GLOBALS['db_connection']['connection_string'];
      $db_user= $GLOBALS['db_connection']['username'];
      $db_pass= $GLOBALS['db_connection']['password'];
    }
    try {
      if (! DB::instance()->pdo= new PDO($connect_string, $db_user, $db_pass)) {
        /** @todo Use standard Error class */
        print_r(DB::instance()->pdo->errorInfo());
        exit;
      }
        
      /**
       * @note  MySQL has issues caching queries that use the internal prepared
       *        statement API (server-side); therefore, we use prepared statement
       *        emulation in PDO to bypass this performance problem
       */
      if (DB::instance()->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
        DB::instance()->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
      DB::instance()->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
      DB::load_tables();
      return true;
    }
    catch (PDOException $e) {
      /** @todo Use standard Error class */
      echo $e->getMessage();
      return false;
    }
  }

  /**
   * Helper function to naturally return table names
   *
   * @param table name of the table
   */
  public static function table($name) {
    if (DB::instance()->pdo == NULL)
      DB::instance()->load_tables();
    if (isset(DB::instance()->sql_tables[$name]))
      return DB::instance()->sql_tables[$name];
    else
      return false;
  }

	/**
	 * Adds a table to the list of tables known to Habari.  Used
   * by Theme and Plugin classes to inform the DB class about
   * custom tables used by the plugin
   *
	 * @param name  the table name
	**/
	public static function register_table($name) {
    $prefix= (isset($GLOBALS['db_connection']['prefix']) ? $GLOBALS['db_connection']['prefix'] : '');
		DB::instance()->sql_tables[$name]= $prefix . $name;
  }

  /**
   * Sets the fetch mode for return calls from PDOStatement
   *
   * @param mode  One of the PDO::FETCH_MODE integers
   */
  public static function set_fetch_mode($mode) {
    DB::instance()->fetch_mode= $mode;
  }

  /**
   * Sets the class to fetch, if fetch mode is PDO::FETCH_CLASS
   *
   * @param class_name  Name of class to create during fetch
   */
  public static function set_fetch_class($class_name) {
    DB::instance()->fetch_class_name= $class_name;
  }

	/**
	 * Queries the database for a given SQL command.
	 * @param query       the SQL query text
	 * @param args        array of values to use for placeholder replacement
	 * @param class_name  (optional) name of class name to wrangle returned data to
	 * @return bool	 
	 */	 	 	 	 	
	public static function query($query, $args = array()) {
    /* Local scope caching */
    $db= DB::instance();
    $pdo= $db->pdo;

    /* Auto-connect */
    if ($pdo == NULL)
      if ($db->connect())
        $pdo= $db->pdo;

		if($db->pdo_statement != NULL) 
      $db->pdo_statement->closeCursor();

		if ($db->pdo_statement=  $pdo->prepare($query)) {
      /**
       * This section of code is EXTREMELY important, for the reasons I laid
       * out on php.net: @see http://us2.php.net/manual/en/function.pdostatement-setfetchmode.php
       *
       * In summary, PDO will *core dump* if the fetch mode is PDO::FETCH_CLASS and the
       * class supplied for instantiation is either a) not included, or b) included, but all
       * related classes are not included.  This is very annoying behaviour, and something that
       * took many hours to diagnose, as the core dump happens with no explanation as to the
       * source of the segfault.
       */
      if ($db->fetch_mode == PDO::FETCH_CLASS) {
        /* Ensure that the class is actually available and included already, otherwise segfault happens */
        if (class_exists(strtolower($db->fetch_class_name))) {
    			$db->pdo_statement->setFetchMode(PDO::FETCH_CLASS, $db->fetch_class_name, array());
        }
        else {
          /* Die gracefully before the segfault occurs */
          echo '<br /><br />Attempt to fetch in class mode with a non-included class<br /><br />';
          return false;
        }
      }
      else
        $db->pdo_statement->setFetchMode($db->fetch_mode);

      /* If we are profiling, then time the query */
      if ($db->keep_profile) {
        $profile= new QueryProfile($query);
        $profile->start();
      }
      if (! $db->pdo_statement->execute($args)) {
				$db->add_error(array('query'=>$query,'error'=>$db->pdo_statement->errorInfo()));
				return false;
			}
      if ($db->keep_profile) {
        $profile->stop();
        $db->profiles[]= $profile;
      }
      return true;
		}
    else {
  		$db->add_error(array('query'=>$query,'error'=>$pdo->errorInfo()));
  		return false;
    }
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
  public static function execute_procedure($procedure, $args= array()) {
    /* Local scope caching */
    $pdo= DB::instance()->pdo;
    $pdo_statement= DB::instance()->pdo_statement;

    /* Auto-connect */
    if ($pdo == NULL)
      DB::connect();

		if($pdo_statement != NULL) 
      $pdo_statement->closeCursor();

    /*
     * Since RDBMS handle the calling of procedures
     * differently, we need a simple abstraction
     * mechanism here to build the appropriate SQL
     * commands to call the procedure...
     */
    $driver= $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    switch ($driver) {
      case 'mysql':
      case 'db2':
        /*
         * These databases use ANSI-92 syntax for procedure calling:
         * CALL procname (param1, param2, ...);
         */
        $query= 'CALL ' . $procedure . '(';
        if (count($args) > 0) {
          $query.= str_repeat('?,', count($args)); // Add the placeholders
          $query= substr($query, 0, strlen($query) - 1); // Strip the last comma
        }
        $query.= ')';
        break;
      case 'pgsql':
      case 'oracle':
        die("not yet supported on $driver");
        break;
    }

		if ($pdo_statement= $pdo->prepare($query)) {
      /* If we are profiling, then time the query */
      if (DB::instance()->keep_profile) {
        $profile= new QueryProfile($query);
        $profile->start();
      }
			if (! $pdo_statement->execute($args)) {
				DB::add_error(array('query'=>$query,'error'=>$pdo_statement->errorInfo()));
				return false;
			}
      if (DB::instance()->keep_profile) {
        $profile->stop();
        DB::instance()->profiles[]= $profile;
      }
      return true;
		}
    else {
  		DB::add_error(array('query'=>$query,'error'=>$pdo_statement->errorInfo()));
  		return false;
    }
	}

  /**
   * Start a transaction against the RDBMS in order to wrap multiple
   * statements in a safe ACID-compliant container
   */
  public static function begin_transaction() {
    $pdo= DB::instance()->pdo;
    if ($pdo == NULL)
      DB::connect();

    $pdo->beginTransaction();
  }

  /**
   * Rolls a currently running transaction back to the 
   * prexisting state, or, if the RDBMS supports it, whenever
   * a savepoint was committed.
   */
  public static function rollback() {
    $pdo= DB::instance()->pdo;
    if ($pdo == NULL)
      DB::connect();

    $pdo->rollBack();
  }

  /**
   * Commit a currently running transaction
   */
  public static function commit() {
    $pdo= DB::instance()->pdo;
    if ($pdo == NULL)
      DB::connect();

    $pdo->commit();
  }

  /**
   * Returns query profiles
   *
   * @return  array an array of query profiles
   */
  public function get_profiles() {
    return DB::instance()->profiles;
  }

  /**
   * Adds an error to the internal collection
   *
   * @param   error   array('query'=>query, 'error'=>errorInfo)
   */
  private function add_error($error) {
    DB::instance()->errors[]= $error;
  }
	
	/**
	 * Returns error data gathered from database connection
	 * @return array An array of error data	 
	 */	  	 	
	public function get_errors() {
		return DB::instance()->errors;
	}
	
	/**
	 * Determines if there have been errors since the last clear_errors() call
	 * @return boolean True if there were errors, false if not
	 **/	 	 	 	
	public function has_errors() {
		return (count(DB::instance()->errors) > 0);
	}
	
	/**
	 * Updates the last error pointer to simulate resetting the error array
	 **/	 	 	
	public function clear_errors() {
		DB::instance()->errors= array(); 
	}

	/**
	 * Returns only the last error info
	 * @return array Data for the last error	 
	 **/
	public function get_last_error() {
    $error= end(DB::instance()->errors);
    return (array('query'=>$error['query'], 'message'=>$error['error'][2]));
	}

	/**
	 * Execute a query and return the results as an array of objects
	 * @param query   the query to execute
	 * @param args    array of arguments to pass for prepared statements
	 * @param string Optional class name for row result objects	 
	 * @return array An array of QueryRecord or the named class each containing the row data
	 * <code>$ary = DB::get_results( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>
	 **/	 	 	 	 
	public function get_results($query, $args = array()) {
    if (func_num_args() == 3) {
      /* Called expecting specific class return type */
      $class_name= func_get_arg(2);
      DB::set_fetch_mode(PDO::FETCH_CLASS);
      DB::set_fetch_class($class_name);
    }
		if (DB::instance()->query($query, $args))
			return DB::instance()->pdo_statement->fetchAll();
		else
      return false;
	}
	
	/**
	 * Returns a single row (the first in a multi-result set) object for a query
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @param string Optional class name for row result object
	 * @return object A QueryRecord or an instance of the named class containing the row data	 
	 * <code>$obj = DB::get_row( 'SELECT * FROM tablename WHERE foo = ?', array('fieldvalue'), 'extendedQueryRecord' );</code>	 
	 **/	 	 
	public function get_row($query, $args = array()) {
    if (func_num_args() == 3) {
      /* Called expecting specific class return type */
      $class_name= func_get_arg(2);
      DB::set_fetch_mode(PDO::FETCH_CLASS);
      DB::set_fetch_class($class_name);
    }
    if (DB::instance()->query($query, $args))
			return DB::instance()->pdo_statement->fetch();
		else
			return false;
	}
	
	/**
	 * Returns all values for a column for a query
   *
	 * @param string The query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return array An array containing the column data	 
	 * <code>$ary = DB::get_column( 'SELECT col1 FROM tablename WHERE foo = ?', array('fieldvalue') );</code>	 
	 **/	 	 
	public function get_column($query, $args = array()) {
		if (DB::instance()->query($query, $args)) 
			return DB::instance()->pdo_statement->fetchAll(PDO::FETCH_COLUMN);
		else
			return false;
	}

	/**
	 * Return a single value from the database
   *
	 * @param string the query to execute
	 * @param array Arguments to pass for prepared statements
	 * @return mixed a single value (int, string)
	**/
	public function get_value( $query, $args = array() ) {
		if (DB::instance()->query($query, $args)) {
      $result= DB::instance()->pdo_statement->fetch(PDO::FETCH_NUM);
      return $result[0];
    }
		else
			return false;
	}
	
	/**
	 * Inserts into the specified table values associated to the key fields
	 * @param string The table name
	 * @param array An associative array of fields and values to insert
	 * @return boolean True on success, false if not	  	 
	 * <code>DB::insert( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/
	public function insert($table, $fieldvalues) {
		ksort($fieldvalues);

		$query = "INSERT INTO {$table} (";
		$comma = '';
		
		foreach($fieldvalues as $field => $value) {
			$query .= $comma . $field;
			$comma = ', ';
			$values[] = $value;
		}
		$query .= ') VALUES (' . trim(str_repeat('?,', count($fieldvalues)), ',') . ');';

		return DB::instance()->query($query, $values);
	}
	
	/**
	 * Checks for a record that matches the specific criteria
	 * @param string Table to check
	 * @param array Associative array of field values to match
	 * @return boolean True if any matching record exists, false if not
	 * <code>DB::exists( 'mytable', array( 'fieldname' => 'value' ) );</code>	 
	 **/	 
	public function exists($table, $keyfieldvalues) {
		$qry= "SELECT 1 as c FROM {$table} WHERE 1 ";

		$values = array();
		foreach($keyfieldvalues as $keyfield => $keyvalue) {
			$qry .= " AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		$result = DB::instance()->get_row($qry, $values);
		return ($result !== false);
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
	public function update($table, $fieldvalues, $keyfields)
	{
		ksort($fieldvalues);
		ksort($keyfields);

		$keyfieldvalues = array();
		foreach($keyfields as $keyfield => $keyvalue) {
			if(is_numeric($keyfield)) {
				$keyfieldvalues[$keyvalue] = $fieldvalues[$keyvalue];
			}
			else {
				$keyfieldvalues[$keyfield] = $keyvalue;
			}
		}
		if(DB::instance()->exists($table, $keyfieldvalues)) {
			$qry = "UPDATE {$table} SET";
			$values = array();
			$comma = '';
			foreach($fieldvalues as $fieldname => $fieldvalue) {
				$qry .= $comma . " {$fieldname} = ?";
				$values[] = $fieldvalue;
				$comma = ' ,';
			} 
			$qry .= ' WHERE 1 ';
			
			foreach($keyfields as $keyfield => $keyvalue) {
				$qry .= "AND {$keyfield} = ? ";
				$values[] = $keyvalue;
			}
			return DB::instance()->query($qry, $values);
		}
		else {
			return DB::instance()->insert($table, $fieldvalues);
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
		$qry = "DELETE FROM {$table} WHERE 1 ";
		foreach ( $keyfields as $keyfield => $keyvalue ) {
			$qry .= "AND {$keyfield} = ? ";
			$values[] = $keyvalue;
		}
		
		return DB::instance()->query( $qry, $values );
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
  public function last_insert_id() {
    return DB::instance()->pdo->lastInsertId(func_num_args()==1 ? func_get_args(0) : '');
  }
}
?>
