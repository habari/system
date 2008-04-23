<?php
/**
 * Habari EventLog class
 *
 * @package Habari
 * @todo Apply system error handling
 */

class EventLog extends ArrayObject
{
	protected $get_param_cache; // Stores info about the last set of data fetched that was not a single value

	/**
	 * Returns properties of a EventLog object.
	 * This is the function that returns information about the set of log entries that
	 * was requested.  This function should offer property names that are identical
	 * to properties of instances of the URL class.  A call to EventLog::get()
	 * without parameters should return mostly the same property values as the
	 * global $url object for the request.  The difference would occur when
	 * the data returned doesn't necessarily match the request, such as when
	 * several log entries are requested, but only one is available to return.
	 *
	 * @param string The name of the property to return.
	 */
	public function __get( $name )
	{
		switch( $name ) {
			case 'onelogentry':
				return ( count( $this ) == 1 );
		}

		return false;
	}

	/**
	 * Adds a logging type to the lookup table
	 *
	 * @param string $type The type of the error
	 * @param string $module The module of the error
	 */
	public static function register_type( $type= 'default', $module= null )
	{
		try {
			DB::query( 'INSERT INTO ' . DB::Table( 'log_types' ) . ' (module, type) VALUES (?,?)', array( self::get_module( $module ), $type ) );
		}
		catch( Exception $e ) {
			// Don't really care if there's a duplicate.
		}
	}

	/**
	 * Removes a logging type from the lookup table
	 *
	 * @param string $type The type of the error
	 * @param string $module The module of the error
	 */
	public static function unregister_type( $type= 'default', $module= null )
	{
		DB::query( 'DELETE FROM ' . DB::Table( 'log_types' ) . ' WHERE module = ? AND type = ?', array( self::get_module( $module ), $type ) );
	}

	/**
	 * Write an entry to the event log.
	 *
	 * @param string $message The message
	 * @param string $severity The severity
	 * @param string $type The type
	 * @param string $module The module
	 * @param mixed $data The data
	 * @return object LogEntry The inserted LogEntry object
	 */
	public static function log( $message, $severity= 'info', $type= 'default', $module= null, $data= null )
	{
		$module= self::get_module( $module );
		$log= new LogEntry( array(
			'message' => $message,
			'severity' => $severity,
			'module' => $module,
			'type' => $type,
			'data' => $data,
			'ip' => sprintf("%u", ip2long( $_SERVER['REMOTE_ADDR'] ) ),
		) );
		if ( $user= User::identify() ) {
			$log->user_id= $user->id;
		}
		$log->insert();
		if ( LogEntry::severity( $severity ) >= LogEntry::severity( 'warning' ) ) {
			Session::error( $message, $module );
		}
		return $log;
	}

	/**
	 * Get the module in which the logged code was executed
	 *
	 * @param integer $level How many backtrace calls to go back through the trace
	 * @return string The classname or .php module in which the log code was called.
	 */
	public static function get_module( $module= null, $level= 2 )
	{
		if ( is_null( $module ) ) {
			$bt= debug_backtrace();
			$last= $bt[$level];
			$module= isset( $last['class'] ) ? $last['class'] : basename( $last['file'], '.php' );
		}
		return $module;
	}

	/**
	 * Returns a LogEntry or EventLog array based on supplied parameters.
	 * By default,fetch as many entries as pagination allows and order them in a descending fashion based on timestamp.
	 *
	 * @todo Cache query results.
	 * @param array $paramarry An associated array of parameters, or a querystring
	 * @return array An array of LogEntry objects, or a single LogEntry object, depending on request
	 */
	public static function get( $paramarray= array() )
	{
		$params= array();
		$fns= array( 'get_results', 'get_row', 'get_value' );
		$select= '';

		foreach ( LogEntry::default_fields() as $field => $value ) {
			$select.= ( '' == $select )
				? DB::table( 'log' ) . ".$field"
				: ', ' . DB::table( 'log' ) . ".$field";
		}
		// Default parameters.
		$orderby= 'ORDER BY timestamp DESC';
		$limit= Options::get( 'pagination' );

		// Put incoming parameters into the local scope
		$paramarray= Utils::get_params( $paramarray );

		foreach ( $paramarray as $key => $value ) {
			if ( 'orderby' == $key ) {
				$orderby= 'ORDER BY ' . $value;
				continue;
			}

			if ( 'limit' == $key ) {
				$limit= " LIMIT " . $value;
			}
		}

		// Transact on possible multiple sets of where information that is to be OR'ed
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets= $paramarray['where'];
		}
		else {
			$wheresets= array( array() );
		}

		$wheres= array();
		$join= '';
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[]= $paramarray['where'];
		}
		else {
			foreach ( $wheresets as $paramset ) {
				// Safety mechanism to prevent empty queries
				$where= array( '1=1' );
				$paramset= array_merge( ( array ) $paramarray, ( array ) $paramset );

				if ( isset( $paramset['id'] ) && is_numeric( $paramset['id'] ) ) {
					$where[]= "id= ?";
					$params[]= $paramset['id'];
				}
				if ( isset( $paramset['user_id'] ) ) {
					$where[]= "user_id= ?";
					$params[]= $paramset['user_id'];
				}
				if ( isset( $paramset['severity'] ) && ( 'any' != LogEntry::severity_name( $paramset['severity'] ) ) ) {
					$where[]= "severity_id= ?";
					$params[]= LogEntry::severity( $paramset['severity'] );
				}
				if ( isset( $paramset['type_id'] ) ) {
					if ( is_array( $paramset['type_id'] ) ) {
						$types= array_filter( $paramset['type_id'], 'is_numeric' );
						if ( count( $types ) ) {
							$where[]= 'type_id IN (' . implode( ',', $types ) . ')';
						}
					}
					else {
						$where[]= 'type_id = ?';
						$params[]= $paramset['type_id'];
					}
				}
				if ( isset( $paramset['ip'] ) ) {
					$where[]= 'ip = ?';
					$params[]= $paramset['ip'];
				}

				/* do searching */
				if ( isset( $paramset['criteria'] ) ) {
					preg_match_all( '/(?<=")(\\w[^"]*)(?=")|(\\w+)/', $paramset['criteria'], $matches );
					foreach ( $matches[0] as $word ) {
						$where[].= "(message LIKE CONCAT('%',?,'%'))";
						$params[]= $word;
					}
				}

				/**
				 * Build the pubdate
				 * If we've got the day, then get the date.
				 * If we've got the month, but no date, get the month.
				 * If we've only got the year, get the whole year.
				 *
				 * @todo Ensure that we've actually got all the needed parts when we query on them
				 * @todo Ensure that the value passed in is valid to insert into a SQL date (ie '04' and not '4')
				 */
				if ( isset( $paramset['day'] ) ) {
					$where[]= 'timestamp BETWEEN ? AND ?';
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], $paramset['day'], $paramset['year'] ) );
					$params[]= date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'], $paramset['day'], $paramset['year'] ) );
				}
				elseif ( isset( $paramset['month'] ) ) {
					$where[]= 'timestamp BETWEEN ? AND ?';
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], 1, $paramset['year'] ) );
					$params[]= date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'] + 1, 0, $paramset['year'] ) );
				}
				elseif ( isset( $paramset['year'] ) ) {
					$where[]= 'timestamp BETWEEN ? AND ?';
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, 0, 1, 1, $paramset['year'] ) );
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, -1, 1, 1, $paramset['year'] + 1 ) );
				}

				$wheres[]= ' (' . implode( ' AND ', $where ) . ') ';
			}

		// Get any full-query parameters
		extract( $paramarray );

		if ( isset( $page ) && is_numeric( $page ) ) {
			$offset= ( intval( $page ) - 1 ) * intval( $limit );
		}

		if ( isset( $fetch_fn ) ) {
			if ( ! in_array( $fetch_fn, $fns ) ) {
				$fetch_fn= $fns[0];
			}
		}
		else {
			$fetch_fn= $fns[0];
		}

		if ( isset( $count ) ) {
			$select= "COUNT($count)";
			$fetch_fn= 'get_value';
			$orderby= '';
		}
		if ( isset( $limit ) ) {
			$limit= " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit.= " OFFSET $offset";
			}
		}
		if ( isset( $nolimit ) ) {
			$limit= '';
		}

		$query= '
			SELECT ' . $select . '
			FROM ' . DB::table( 'log' ) .
			' ' . $join;

		if ( count( $wheres ) > 0 ) {
			$query.= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query.= $orderby . $limit;
		// Utils::debug( $paramarray, $fetch_fn, $query, $params );

		DB::set_fetch_mode( PDO::FETCH_CLASS );
		DB::set_fetch_class( 'LogEntry' );
		$results= DB::$fetch_fn( $query, $params, 'LogEntry' );

			// If the fetch callback function is not get_results,
			// return an EventLog ArrayObject filled with the results as LogEntry objects.
		if ( 'get_results' != $fetch_fn ) {
			return $results;
		}
		elseif ( is_array( $results ) ) {
			$c= __CLASS__;
			$return_value= new $c( $results );
			$return_value->get_param_cache= $paramarray;
			return $return_value;
		}
	}
	}

}

?>
