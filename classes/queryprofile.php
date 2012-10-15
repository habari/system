<?php
/**
 * @package Habari
 *
 * @property-read integer $totaltime The time it took for the query to run in microseconds
 */

/**
 * Class to assist in profiling queries
 *
 */
class QueryProfile
{
	public $start_time;     // time that query started execution
	public $end_time;       // time that query ended execution
	public $query_text;     // SQL text
	public $backtrace = '';  // stack backtrace for debugging.

	/**
	 * Constructor for the query profile.  Automatically sets the
	 * start time for the query
	 *
	 * @param query SQL being executed
	 */
	public function __construct( $query )
	{
		$this->query_text = $query;
		/* Backtracing is very verbose. Enable only if set via query string */
		if ( isset( $_GET['db_profile'] )
			&& $_GET['db_profile'] == 'verbose' )
			$this->backtrace = debug_backtrace();
	}

	public function start()
	{
		$this->start_time = $this->get_time_in_microseconds();
	}

	public function stop()
	{
		$this->end_time = $this->get_time_in_microseconds();
	}

	public function __get( $name )
	{
		switch ( $name ) {
			case 'total_time':
				return $this->end_time - $this->start_time;
			default:
				return $this->$name;
		}
	}

	/**
	 * Returns an integer representing the current time
	 * in microseconds from Epoch
	 *
	 * @return  int the number of microseconds since epoch.
	 */
	public static function get_time_in_microseconds()
	{
		list( $usec, $sec ) = explode( ' ', microtime() );
		return ( (float) ( 1000*$sec )  + $usec );
	}

}
