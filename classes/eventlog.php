<?php
/**
 * @package Habari
 *
 * @property-read bool $onelogentry True if this object only has one entry
 */

namespace Habari;

/**
 * Habari EventLog class
 *
 * @todo Apply system error handling
 */
class EventLog extends \ArrayObject
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
	 * @param string $name The name of the property to return.
	 * @return bool
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
		catch( \Exception $e ) {
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
	 * @param null $module
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
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * The following keys are supported:
	 * - id => an entry id or array of post ids
	 * - user_id => id of the logged in user for which to return entries
	 * - severity => severity level for which to return entries
	 * - type_id => the numeric id or array of ids for the type of entries for which which to return entries
	 * - module => a name or array of names of modules for which to return entries
	 * - type => a single type name or array of type names for which to return entries
	 * - ip => the IP number for which to return entries
	 * - criteria => a literal search string to match entry message content or a special search
	 * - day => a day of entry creation, ignored if month and year are not specified
	 * - month => a month of entry creation, ignored if year isn't specified
	 * - year => a year of entry creation
	 * - orderby => how to order the returned entries
	 * - fetch_fn => the function used to fetch data, one of 'get_results', 'get_row', 'get_value'
	 * - count => return the number of entries that would be returned by this request
	 * - month_cts => return the number of entries created in each month
	 * - nolimit => do not implicitly set limit
	 * - limit => the maximum number of entries to return, implicitly set for many queries
	 * - index => 
	 * - offset => amount by which to offset returned entries, used in conjunction with limit
	 * - where => manipulate the generated WHERE clause
	 * - return_data => set to return the data associated with the entry
	 * 
	 * @return array An array of LogEntry objects, or a single LogEntry object, depending on request
	 */
	public static function get( $paramarray = array() )
	{
		$params = array();
		$fns = array( 'get_results', 'get_row', 'get_value' );
		$select_ary = array();
		$select_distinct = array();

		// Put incoming parameters into the local scope
		$paramarray = Utils::get_params( $paramarray );
		if($paramarray instanceof \ArrayIterator) {
			$paramarray = $paramarray->getArrayCopy();
		}

		$select_fields = LogEntry::default_fields();
		if ( !isset( $paramarray['return_data'] ) ) {
			unset( $select_fields['data'] );
		}

		foreach ( $select_fields as $field => $value ) {
			if(preg_match('/(?:(?P<table>[\w\{\}]+)\.)?(?P<field>\w+)(?:(?:\s+as\s+)(?P<alias>\w+))?/i', $field, $fielddata)) {
				if(empty($fielddata['table'])) {
					$fielddata['table'] = '{log}';
				}
				if(empty($fielddata['alias'])) {
					$fielddata['alias'] = $fielddata['field'];
				}
			}
			$select_ary[$fielddata['alias']] = "{$fielddata['table']}.{$fielddata['field']} AS {$fielddata['alias']}";
			$select_distinct[$fielddata['alias']] = "{$fielddata['table']}.{$fielddata['field']}";
		}

		// Transact on possible multiple sets of where information that is to be OR'ed
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets = $paramarray['where'];
		}
		else {
			$wheresets = array( array() );
		}

		$query = Query::create('{log}');
		$query->select($select_ary);

		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$query->where()->add($paramarray['where']);
		}
		foreach ( $wheresets as $paramset ) {
			$where = new QueryWhere();
			$paramset = array_merge( (array) $paramarray, (array) $paramset );

			if ( isset( $paramset['id'] ) ) {
				$where->in( '{log}.id', $paramset['id'], 'log_id', 'intval' );
			}

			if ( isset( $paramset['user_id'] ) ) {
				$where->in( '{log}.user_id', $paramset['user_id'], 'log_user_id', 'intval' );
			}

			if ( isset( $paramset['severity'] ) && ( 'any' != LogEntry::severity_name( $paramset['severity'] ) ) ) {
				$where->in( '{log}.severity_id', $paramset['severity'], 'log_severity_id', function($a) {return LogEntry::severity( $a );} );
			}

			if ( isset( $paramset['type_id'] ) ) {
				$where->in( '{log}.type_id', $paramset['type_id'], 'log_type_id', 'intval' );
			}

			if ( isset( $paramset['module'] ) ) {

				$paramset['module'] = Utils::single_array( $paramset['module'] );

				$qry = Query::create( '{log_types}' );
				$qry->select( '{log_types}.id')->distinct();
				$qry->where()->in( '{log_types}.module', $paramset['module'], 'log_subquery_module' );

				$where->in( '{log}.type_id', $qry, 'log_module' );

			}

			if ( isset( $paramset['type'] ) ) {

				$paramset['type'] = Utils::single_array( $paramset['type'] );

				$qry = Query::create( '{log_types}' );
				$qry->select( '{log_types}.id')->distinct();
				$qry->where()->in( '{log_types}.type', $paramset['type'], 'log_subquery_type' );

				$where->in( '{log}.type_id', $qry, 'log_type' );
			}

			if ( isset( $paramset['ip'] ) ) {
				$where->in( '{log}.ip', $paramset['ip'] );
			}

			/* do searching */
			if ( isset( $paramset['criteria'] ) ) {
				// this regex matches any unicode letters (\p{L}) or numbers (\p{N}) inside a set of quotes (but strips the quotes) OR not in a set of quotes
				preg_match_all( '/(?<=")(\w[^"]*)(?=")|([:\w]+)/u', $paramset['criteria'], $matches );
				foreach ( $matches[0] as $word ) {
					if( preg_match( '%^id:(\d+)$%i', $word, $special_crit ) ) {
						$where->in( '{log}.id', $special_crit[1], 'log_special_criteria' );
					}
					else {
						$crit_placeholder = $query->new_param_name('criteria');
						$where->add("( LOWER( {log}.message ) LIKE :{$crit_placeholder}", array($crit_placeholder => '%' . MultiByte::strtolower( $word ) . '%') );
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
			if ( isset( $paramset['day'] ) && isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
				$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], $paramset['day'] );
				$start_date = DateTime::create( $start_date );
				$where->add('timestamp BETWEEN :start_date AND :end_date', array('start_date' => $start_date->sql, 'end_date' => $start_date->modify( '+1 day -1 second' )->sql));
			}
			elseif ( isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
				$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], $paramset['month'], 1 );
				$start_date = DateTime::create( $start_date );
				$where->add('timestamp BETWEEN :start_date AND :end_date', array('start_date' => $start_date->sql, 'end_date' => $start_date->modify( '+1 month -1 second' )->sql));
			}
			elseif ( isset( $paramset['year'] ) ) {
				$start_date = sprintf( '%d-%02d-%02d', $paramset['year'], 1, 1 );
				$start_date = DateTime::create( $start_date );
				$where->add('timestamp BETWEEN :start_date AND :end_date', array('start_date' => $start_date->sql, 'end_date' => $start_date->modify( '+1 year -1 second' )->sql));
			}

			// Concatenate the WHERE clauses
			$query->where()->add($where);
		}

		// Default parameters.
		$orderby = 'timestamp DESC, id DESC';
//		$limit = Options::get( 'pagination' );

		// Get any full-query parameters
		$paramarray = new SuperGlobal( $paramarray );
		$extract = $paramarray->filter_keys( 'orderby', 'fetch_fn', 'count', 'month_cts', 'nolimit', 'index', 'limit', 'offset' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
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
			$query->set_select( "COUNT({$count})" );
			$fetch_fn = isset($paramarray['fetch_fn']) ? $fetch_fn : 'get_value';
			$orderby = null;
			$groupby = null;
			$having = null;
		}


		// If the month counts are requested, replace the select clause
		if ( isset( $paramset['month_cts'] ) ) {
			// @todo shouldn't this hand back to habari to convert to DateTime so it reflects the right timezone?
			$query->set_select ( 'MONTH(FROM_UNIXTIME(timestamp)) AS month, YEAR(FROM_UNIXTIME(timestamp)) AS year, COUNT(*) AS ct' );
			$groupby = 'year, month';
			if( !isset( $paramarray['orderby'] ) ) {
				$orderby = 'year, month';
			}
		}
		if ( isset( $nolimit ) || isset( $month_cts ) ) {
			$limit = null;
		}

		// Define the LIMIT, OFFSET, ORDER BY, GROUP BY if they exist
		if(isset($limit)) {
			$query->limit($limit);
		}
		if(isset($offset)) {
			$query->offset($offset);
		}
		if(isset($orderby)) {
			$query->orderby($orderby);
		}
		if(isset($groupby)) {
			$query->groupby($groupby);
		}
/*
if(isset($paramarray['type'])) {
	print_r($query->params());
	print_r($query->get());die();
}
*/
		/* All SQL parts are constructed, on to real business! */
		DB::set_fetch_mode( \PDO::FETCH_CLASS );
		DB::set_fetch_class( 'LogEntry' );
		$results = DB::$fetch_fn( $query->get(), $query->params(), 'LogEntry' );

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
		$retention_days = Options::get( 'log_retention', 14 );		// default to 14 days

		// make it into the string we'll use
		$retention = '-' . intval( $retention_days ) . ' days';

		// Trim the log table down
		$date = DateTime::create()->modify( $retention );

		$result = DB::query( 'DELETE FROM {log} WHERE timestamp < ?', array( $date->sql ) );

		if ( $result ) {
			EventLog::log( _t( 'Entries over %d days old were trimmed from the EventLog', array( $retention_days ) ), 'info' );
		}
		else {
			EventLog::log( _t( 'There was an error trimming old entries from the EventLog!' ), 'err' );
		}

		return $result;

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
