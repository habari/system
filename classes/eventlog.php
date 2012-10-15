<?php
/**
 * @package Habari
 *
 * @property-read bool $onelogentry True if this object only has one entry
 */

/**
 * Habari EventLog class
 *
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
		switch ( $name ) {
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
	public static function register_type( $type = 'default', $module = null )
	{
		try {
			DB::query( 'INSERT INTO {log_types} (module, type) VALUES (?,?)', array( self::get_module( $module ), $type ) );
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
	public static function unregister_type( $type = 'default', $module = null )
	{
		$id = DB::get_value( "SELECT id FROM {log_types} WHERE module = ? and type = ?", array( self::get_module( $module ), $type ) );
		if ( $id ) {
			if ( !DB::exists( '{log}', array( 'type_id' => $id ) ) ) {
				DB::delete( '{log_types}', array( 'id' => $id ) );
			}
		}
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
	public static function log( $message, $severity = 'info', $type = 'default', $module = null, $data = null )
	{
		$module = self::get_module( $module );
		$log = new LogEntry( array(
			'message' => $message,
			'severity' => $severity,
			'module' => $module,
			'type' => $type,
			'data' => $data,
			'ip' => Utils::get_ip(),
		) );
		$user = User::identify();
		if ( $user->loggedin ) {
			$log->user_id = $user->id;
		}
		$log->insert();
		return $log;
	}

	/**
	 * Get the module in which the logged code was executed
	 *
	 * @param integer $level How many backtrace calls to go back through the trace
	 * @return string The classname or .php module in which the log code was called.
	 */
	public static function get_module( $module = null, $level = 2 )
	{
		if ( is_null( $module ) ) {
			$bt = debug_backtrace();
			$last = $bt[$level];
			$module = isset( $last['class'] ) ? $last['class'] : basename( $last['file'], '.php' );
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
	public static function get( $paramarray = array() )
	{
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value' );
		$select = '';

		// Put incoming parameters into the local scope
		$paramarray = Utils::get_params( $paramarray );

		$select_fields = LogEntry::default_fields();
		if ( !isset( $paramarray['return_data'] ) ) {
			unset( $select_fields['data'] );
		}
		foreach ( $select_fields as $field => $value ) {
			$select .= ( '' == $select )
				? "{log}.$field"
				: ", {log}.$field";
		}
		// Default parameters.
		$orderby = 'ORDER BY timestamp DESC, id DESC';
		$limit = Options::get( 'pagination' );

		// Get any full-query parameters
		$possible = array( 'orderby', 'fetch_fn', 'count', 'month_cts', 'nolimit', 'index', 'limit', 'offset' );
		foreach ( $possible as $varname ) {
			if ( isset( $paramarray[$varname] ) ) {
				$$varname = $paramarray[$varname];
			}
		}

		foreach ( $paramarray as $key => $value ) {
			if ( 'orderby' == $key ) {
				$orderby = ' ORDER BY ' . $value;
				continue;
			}
		}

		// Transact on possible multiple sets of where information that is to be OR'ed
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets = $paramarray['where'];
		}
		else {
			$wheresets = array( array() );
		}

		$wheres = array();
		$join = '';
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[] = $paramarray['where'];
		}
		else {
			foreach ( $wheresets as $paramset ) {
				// Safety mechanism to prevent empty queries
				$where = array( '1=1' );
				$paramset = array_merge( ( array ) $paramarray, ( array ) $paramset );

				if ( isset( $paramset['id'] ) && is_numeric( $paramset['id'] ) ) {
					$where[] = "id= ?";
					$params[] = $paramset['id'];
				}
				if ( isset( $paramset['user_id'] ) ) {
					$where[] = "user_id= ?";
					$params[] = $paramset['user_id'];
				}
				if ( isset( $paramset['severity'] ) && ( 'any' != LogEntry::severity_name( $paramset['severity'] ) ) ) {
					$where[] = "severity_id= ?";
					$params[] = LogEntry::severity( $paramset['severity'] );
				}
				if ( isset( $paramset['type_id'] ) ) {
					if ( is_array( $paramset['type_id'] ) ) {
						$types = array_filter( $paramset['type_id'], 'is_numeric' );
						if ( count( $types ) ) {
							$where[] = 'type_id IN (' . implode( ',', $types ) . ')';
						}
					}
					else {
						$where[] = 'type_id = ?';
						$params[] = $paramset['type_id'];
					}
				}

				if ( isset( $paramset['module'] ) ) {

					if ( !is_array( $paramset['module'] ) ) {
						$paramset['module'] = array( $paramset['module'] );
					}

					$where[] = 'type_id IN ( SELECT DISTINCT id FROM {log_types} WHERE module IN ( ' . implode( ', ', array_fill( 0, count( $paramset['module'] ), '?' ) ) . ' ) )';
					$params = array_merge( $params, $paramset['module'] );

				}

				if ( isset( $paramset['type'] ) ) {

					if ( !is_array( $paramset['type'] ) ) {
						$paramset['type'] = array( $paramset['type'] );
					}

					$where[] = 'type_id IN ( SELECT DISTINCT id FROM {log_types} WHERE type IN ( ' . implode( ', ', array_fill( 0, count( $paramset['type'] ), '?' ) ) . ' ) )';
					$params = array_merge( $params, $paramset['type'] );

				}

				if ( isset( $paramset['ip'] ) ) {
					$where[] = 'ip = ?';
					$params[] = $paramset['ip'];
				}

				/* do searching */
				if ( isset( $paramset['criteria'] ) ) {
					preg_match_all( '/(?<=")(\w[^"]*)(?=")|([:\w]+)/u', $paramset['criteria'], $matches );
					foreach ( $matches[0] as $word ) {
						if ( preg_match( '%^id:(\d+)$%i', $word, $special_crit ) ) {
							$where[] .= '(id = ?)';
							$params[] = $special_crit[1];
						}
						else {
							$where[] .= "( LOWER( message ) LIKE ? )";
							$params[] = '%' . MultiByte::strtolower( $word ) . '%';
						}
					}
				}

				/**
				 * Build the pubdate
				 * If we've got the day, then get the date.
				 * If we've got the month, but no date, get the month.
				 * If we've only got the year, get the whole year.
				 *
				 * @todo Ensure that we've actually got all the needed parts when we query on them
				 */
				if ( isset( $paramset['day'] ) ) {
					$where[] = 'timestamp BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], $paramset['day'] );
					$start_date = HabariDateTime::date_create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 day' )->sql;
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], $paramset['day'], $paramset['year'] ) );
					//$params[] = date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'], $paramset['day'], $paramset['year'] ) );
				}
				elseif ( isset( $paramset['month'] ) ) {
					$where[] = 'timestamp BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], 1 );
					$start_date = HabariDateTime::date_create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 month' )->sql;
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], 1, $paramset['year'] ) );
					//$params[] = date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'] + 1, 0, $paramset['year'] ) );
				}
				elseif ( isset( $paramset['year'] ) ) {
					$where[] = 'timestamp BETWEEN ? AND ?';
					$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], 1, 1 );
					$start_date = HabariDateTime::date_create( $start_date );
					$params[] = $start_date->sql;
					$params[] = $start_date->modify( '+1 year' )->sql;
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, 1, 1, $paramset['year'] ) );
					//$params[] = date( 'Y-m-d H:i:s', mktime( 0, 0, -1, 1, 1, $paramset['year'] + 1 ) );
				}

				$wheres[] = ' (' . implode( ' AND ', $where ) . ') ';
			}
		}

		if ( isset( $index ) && is_numeric( $index ) ) {
			$offset = ( intval( $index ) - 1 ) * intval( $limit );
		}

		if ( isset( $fetch_fn ) ) {
			if ( ! in_array( $fetch_fn, $fns ) ) {
				$fetch_fn = $fns[0];
			}
		}
		else {
			$fetch_fn = $fns[0];
		}

		if ( isset( $count ) ) {
			$select = "COUNT($count)";
			$fetch_fn = 'get_value';
			$orderby = '';
		}
		if ( isset( $limit ) ) {
			$limit = " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit .= " OFFSET $offset";
			}
		}
		// If the month counts are requested, replace the select clause
		if ( isset( $paramset['month_cts'] ) ) {
			// @todo shouldn't this hand back to habari to convert to DateTime so it reflects the right timezone?
			$select = 'MONTH(FROM_UNIXTIME(timestamp)) AS month, YEAR(FROM_UNIXTIME(timestamp)) AS year, COUNT(*) AS ct';
			$groupby = 'year, month';
			$orderby = ' ORDER BY year, month';
		}
		if ( isset( $nolimit ) || isset( $month_cts ) ) {
			$limit = '';
		}

		$query = '
			SELECT ' . $select . '
			FROM {log} ' . $join;

		if ( count( $wheres ) > 0 ) {
			$query .= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query .= ( ! isset( $groupby ) || $groupby == '' ) ? '' : ' GROUP BY ' . $groupby;
		$query .= $orderby . $limit;
		// Utils::debug( $paramarray, $fetch_fn, $query, $params );

		DB::set_fetch_mode( PDO::FETCH_CLASS );
		DB::set_fetch_class( 'LogEntry' );
		$results = DB::$fetch_fn( $query, $params, 'LogEntry' );

			// If the fetch callback function is not get_results,
			// return an EventLog ArrayObject filled with the results as LogEntry objects.
		if ( 'get_results' != $fetch_fn ) {
			return $results;
		}
		elseif ( is_array( $results ) ) {
			$c = __CLASS__;
			$return_value = new $c( $results );
			$return_value->get_param_cache = $paramarray;
			return $return_value;
		}
	}

	/*
	 * Trim the EventLog down to the defined number of days to prevent it getting massively large.
	 */
	public static function trim()
	{
		// allow an option to be set to override the log retention - in days
		$retention = Options::get( 'log_retention', 14 );		// default to 14 days

		// make it into the string we'll use
		$retention = '-' . intval( $retention ) . ' days';

		// Trim the log table down
		$date = HabariDateTime::date_create()->modify( $retention );

		return DB::query( 'DELETE FROM {log} WHERE timestamp < ?', array( $date->sql ) );

	}

	public static function purge ()
	{
		$result = DB::query( 'DELETE FROM {log}' );

		if ( $result ) {
			EventLog::log( _t( 'Logs purged.' ), 'info' );
		}

		return $result;

	}

}

?>
