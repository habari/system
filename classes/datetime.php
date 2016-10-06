<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * DateTime class to wrap dates in.
 *
 * @property-read DateTime $clone Returns a clonned object.
 * @property-read string $sql Returns a unix timestamp for inserting into DB.
 * @property-read int $int Returns a unix timestamp as integer.
 * @property-read string $time Returns the time formatted according to the blog's settings.
 * @property-read string $date Returns the date formatted according to the blog's settings.
 * @property-read string $friendly Returns the time as a friendly string (ie: 4 months, 3 days ago, etc.).
 * @property-read string $fuzzy Returns the time as a short "fuzzy" string (ie: "just now", "yesterday", "2 weeks ago", etc.). 
 */
class DateTime extends \DateTime
{
	private static $default_timezone;
	private static $default_datetime_format = 'c';
	private static $default_date_format;
	private static $default_time_format;
	private static $last_exception;
	
	// various time increments in seconds
	const YEAR		= 31556926;
	const MONTH		= 2629744;
	const WEEK		= 604800;
	const DAY		= 86400;
	const HOUR		= 3600;
	const MINUTE	= 60;

	/**
	 * Set default timezone to system default on init.
	 *
	 * @static
	 */
	public static function __static()
	{
		if ( Options::get( 'timezone' ) ) {
			self::set_default_timezone( Options::get( 'timezone' ) );
		}

		self::$default_timezone = date_default_timezone_get();

		self::$default_date_format = Options::get( 'dateformat' );
		self::$default_time_format = Options::get( 'timeformat' );

		if ( self::$default_date_format || self::$default_time_format ) {
			self::set_default_datetime_format( self::$default_date_format . ' ' . self::$default_time_format );
		}
	}

	/**
	 * Set default date/time format. The format is the same as the
	 * internal php {@link http://ca.php.net/date date() function}.
	 *
	 * @static
	 * @param string $format The date format.
	 */
	public static function set_default_datetime_format( $format )
	{
		self::$default_datetime_format = $format;
	}

	/**
	 * Get the default date/time format set.
	 *
	 * @static
	 * @see set_default_datetime_format()
	 * @return string The date format set.
	 */
	public static function get_default_datetime_format()
	{
		$user_datetime_format = User::identify()->info->locale_date_format . ' ' . User::identify()->info->locale_time_format;
		if ( $user_datetime_format != self::$default_datetime_format ) {
			self::set_default_datetime_format( $user_datetime_format );
		}
		return self::$default_datetime_format;
	}

	/**
	 * Sets the timezone for Habari and PHP.
	 *
	 * @static
	 * @param string $timezone A timezone name, not an abbreviation, for example 'America/New York'
	 */
	public static function set_default_timezone( $timezone )
	{
		self::$default_timezone = $timezone;
		date_default_timezone_set( self::$default_timezone );
	}

	/**
	 * Get the timezone for Habari and PHP.
	 * Defaults to system timezone if not set.
	 *
	 * @static
	 * @see set_default_timezone()
	 */
	public static function get_default_timezone()
	{
		return self::$default_timezone;
	}

	/**
	 * Get the last exception thrown when trying to create
	 */
	public static function get_last_exception()
	{
		return self::$last_exception;
	}

	/**
	 * This function should not be used.  Use ::create() instead
	 * @deprecated
	 */
	public static function date_create( $time = null, $timezone = null ) {
		return self::create( $time, $timezone );
	}

	/**
	 * Helper function to create a DateTime object for the given
	 * time and timezone. If no time is given, defaults to 'now'. If no
	 * timezone given defaults to timezone set in {@link set_default_timezone()}
	 *
	 * @static
	 * @see DateTime::__construct()
	 * @param string $time String in a format accepted by
	 * {@link http://ca.php.net/strtotime strtotime()}, defaults to "now".
	 * @param string $timezone A timezone name, not an abbreviation.
	 * @return \Habari\DateTime
	 */
	public static function create( $time = null, $timezone = null )
	{
		if ( $time instanceOf DateTime ) {
			return $time;
		}
		elseif ( $time instanceOf \DateTime ) {
			$time = $time->format( 'U' );
		}
		elseif ( $time === null ) {
			$time = 'now';
		}
		elseif ( is_numeric( $time ) ) {
			$time = '@' . $time;
		}

		if ( $timezone === null ) {
			$timezone = self::$default_timezone;
		}

		try {
			$datetime = new DateTime( $time );
			// passing the timezone to construct doesn't seem to do anything.
			$datetime->set_timezone( $timezone );
		}
		catch(\Exception $e) {
			// Do not crash if invalid data has been passed but save the exception
			self::$last_exception = $e;
			return false;
		}
		return $datetime;
	}

	/**
	 * Set the date of this object
	 *
	 * @see DateTime::setDate()
	 * @param int $year Year of the date
	 * @param int $month Month of the date
	 * @param int $day Day of the date
	 * @return \Habari\DateTime
	 */
	public function set_date( $year, $month, $day )
	{
		parent::setDate( $year, $month, $day );
		return $this;
	}

	/**
	 * Sets the ISO date
	 *
	 * @see DateTime::setISODate()
	 * @param int $year Year of the date
	 * @param int $month Month of the date
	 * @param int $day Day of the date
	 * @return \Habari\DateTime
	 */
	public function set_isodate( $year, $week, $day = null )
	{
		parent::setISODate( $year, $week, $day );
		return $this;
	}

	/**
	 * Set the time of this object
	 *
	 * @see DateTime::setTime()
	 * @param int $hour Hour of the time
	 * @param int $minute Minute of the time
	 * @param int $second Second of the time
	 * @return \Habari\DateTime
	 */
	public function set_time( $hour, $minute, $second = null )
	{
		parent::setTime( $hour, $minute, $second );
		return $this;
	}

	/**
	 * Set the timezone for this datetime object. Can be either string
	 * timezone identifier, or DateTimeZone object.
	 *
	 * @see DateTime::setTimezone()
	 * @param mixed $timezone The timezone to use.
	 * @return DateTime $this object.
	 */
	public function set_timezone( $timezone )
	{
		if ( ! $timezone instanceof \DateTimeZone ) {
			$timezone = new \DateTimeZone( $timezone );
		}
		parent::setTimezone( $timezone );
		return $this;
	}

	/**
	 * Get the timezone identifier that is set for this datetime object.
	 *
	 * @return \DateTimeZone The timezone object.
	 */
	public function get_timezone()
	{
		return parent::getTimezone();
	}

	/**
	 * Returns date formatted according to given format.
	 *
	 * @see DateTime::format()
	 * @param string $format Format accepted by {@link http://php.net/date date()}.
	 * @return string The formatted date, false on failure.
	 */
	public function format( $format = null )
	{
		$day_months = array(
			'January' => _t( 'January' ),
			'February' => _t( 'February' ),
			'March' => _t( 'March' ),
			'April' => _t( 'April' ),
			'May' => _t( 'May' ),
			'June' => _t( 'June' ),
			'July' => _t( 'July' ),
			'August' => _t( 'August' ),
			'September' => _t( 'September' ),
			'October' => _t( 'October' ),
			'November' => _t( 'November' ),
			'December' => _t( 'December' ),
			'Jan' => _t( 'Jan' ),
			'Feb' => _t( 'Feb' ),
			'Mar' => _t( 'Mar' ),
			'Apr' => _t( 'Apr' ),
			'May' => _t( 'May' ),
			'Jun' => _t( 'Jun' ),
			'Jul' => _t( 'Jul' ),
			'Aug' => _t( 'Aug' ),
			'Sep' => _t( 'Sep' ),
			'Oct' => _t( 'Oct' ),
			'Nov' => _t( 'Nov' ),
			'Dec' => _t( 'Dec' ),
			'Sunday' => _t( 'Sunday' ),
			'Monday' => _t( 'Monday' ),
			'Tuesday' => _t( 'Tuesday' ),
			'Wednesday' => _t( 'Wednesday' ),
			'Thursday' => _t( 'Thursday' ),
			'Friday' => _t( 'Friday' ),
			'Saturday' => _t( 'Saturday' ),
			'Sun' => _t( 'Sun' ),
			'Mon' => _t( 'Mon' ),
			'Tue' => _t( 'Tue' ),
			'Wed' => _t( 'Wed' ),
			'Thu' => _t( 'Thu' ),
			'Fri' => _t( 'Fri' ),
			'Sat' => _t( 'Sat' ),
			'am' => _t( 'am' ),
			'pm' => _t( 'pm' ),
			'AM' => _t( 'AM' ),
			'PM' => _t( 'PM' ),
		);

		if ( $format === null ) {
			$format = self::$default_datetime_format;
		}
		
		$result = parent::format( $format );
		
		if ( ! $result ) {
			return false;
		}
		
		$result = Multibyte::str_replace( array_keys( $day_months ), array_values( $day_months ), $result );
		
		return $result;
	}

	/**
	 * Returns date components inserted into a string
	 * 
	 * Example:
	 * echo DateTime::create('2010-01-01')->text_format('The year was {Y}.');
	 * // Expected output:  The year was 2010.	 	  	
	 *	
	 * @param string $format A string with single-character date format codes {@link http://php.net/date date()} surrounded by braces
	 * @return string The string with date components inserted	 
	 */	 
	public function text_format( $format )
	{
		return preg_replace_callback( '%\{(\w)\}%iu', array( $this, 'text_format_callback' ), $format );
	}

	/**
	 * Callback method for supplying replacements for HabariDatTime::text_format()
	 * 
	 * @param array $matches The matches found in the regular expression.
	 * @return string The date component value for the matched character.
	 */
	private function text_format_callback( $matches )
	{
		return $this->format( $matches[1] );
	}

	/**
	 * Alters the timestamp
	 *
	 * @param string $args A format accepted by {@link http://php.net/strtotime strtotime()}..
	 * @return DateTime $this object.
	 */
	public function modify( $args )
	{
		parent::modify( $args );
		return $this;
	}

	/**
	 * @see format()
	 */
	public function get( $format = null )
	{
		return $this->format( $format );
	}

	/**
	 * Echos date formatted according to given format.
	 *
	 * @see format()
	 * @param string $format Format accepted by {@link http://php.net/date date()}.
	 */
	public function out( $format = null )
	{
		echo $this->format( $format );
	}

	/**
	 * Magic method called when this object is cast to string. Returns the
	 * unix timestamp of this object.
	 *
	 * @return string The unix timestamp
	 */
	public function __toString()
	{
		return is_string($result = $this->format( 'U' )) ? $result : '0';
	}

	/**
	 * Magic method to get magic ponies... properties, I mean.
	 */
	public function __get( $property )
	{
		// if you add more cases to this list, please also add the repsective @property to the top of the class so it shows up propertly in IDEs!
		switch ( $property ) {
			case 'clone':
				return clone $this;

			case 'sql':
				return $this->format( 'U' );
				break;

			case 'int':
				return intval( $this->format( 'U' ) );
				break;

			case 'time':
				return $this->format( self::get_default_time_format() );
				break;

			case 'date':
				return $this->format( self::get_default_date_format() );
				break;
				
			case 'friendly':
				return $this->friendly();
				break;
				
			case 'fuzzy':
				return $this->fuzzy();
				break;

			default:
				$info = getdate( $this->format( 'U' ) );
				$info['mon0'] = substr( '0' . $info['mon'], -2, 2 );
				$info['mday0'] = substr( '0' . $info['mday'], -2, 2 );
				if ( isset( $info[$property] ) ) {
					return $info[$property];
				}
				return $this->$property;
		}
	}

	/**
	 * Return the default date format, as set in the Options table
	 *
	 * @return string The default date format
	 **/
	public static function get_default_date_format()
	{
		if ( isset(User::identify()->info->local_date_format) && User::identify()->info->locale_date_format != Options::get( 'dateformat' ) ) {
			self::set_default_date_format( User::identify()->info->locale_date_format );
		}
		return self::$default_date_format;
	}
	
	/**
	 * Set default date format. The format is the same as the
	 * internal php {@link http://ca.php.net/date date() function}.
	 *
	 * @static
	 * @param string $format The date format.
	 */
	public static function set_default_date_format( $format )
	{
		self::$default_date_format = $format;
	}

	/**
	 * Return the default time format, as set in the Options table
	 *
	 * @return string The default time format
	 **/
	public static function get_default_time_format()
	{
		if ( isset(User::identify()->info->local_time_format) && User::identify()->info->locale_time_format != Options::get( 'timeformat' ) ) {
			self::set_default_time_format( User::identify()->info->locale_time_format );
		}
		return self::$default_time_format;
	}
	
	/**
	 * Set default time format. The format is the same as the
	 * internal php {@link http://ca.php.net/date date() function}.
	 *
	 * @static
	 * @param string $format The time format.
	 */
	public static function set_default_time_format( $format )
	{
		self::$default_time_format = $format;
	}

	/**
	 * Returns an associative array containing the date information for
	 * this DateTime object, as per {@link http://php.net/getdate getdate()}
	 *
	 * @return array Associative array containing the date information
	 */
	public function getdate()
	{
		$info = getdate( $this->format( 'U' ) );
		$info['mon0'] = substr( '0' . $info['mon'], -2, 2 );
		$info['mday0'] = substr( '0' . $info['mday'], -2, 2 );
		return $info;
	}
	
	/**
	 * Returns a friendlier string version of the time, ie: 3 days, 1 hour, and 5 minutes ago
	 * 
	 * @param int $precision Only display x intervals. Note that this does not round, it only limits the display length.
	 * @param boolean $include_suffix Include the 'ago' or 'from now' suffix?
	 * @return string Time passed in the specified units.
	 */
	public function friendly ( $precision = 7, $include_suffix = true, $comparison_date = 'now' )
	{
				
		$difference = self::difference( $comparison_date, $this );
				
		
		$result = array();
		
		if ( $difference['y'] ) {
			$result[] = sprintf( '%d %s', $difference['y'], _n( 'year', 'years', $difference['y'] ) );
		}
		
		if ( $difference['m'] ) {
			$result[] = sprintf( '%d %s', $difference['m'], _n( 'month', 'months', $difference['m'] ) );
		}
		
		if ( $difference['w'] ) {
			$result[] = sprintf( '%d %s', $difference['w'], _n( 'week', 'weeks', $difference['w'] ) );
		}
		
		if ( $difference['d'] ) {
			$result[] = sprintf( '%d %s', $difference['d'], _n( 'day', 'days', $difference['d'] ) );
		}
		
		if ( $difference['h'] ) {
			$result[] = sprintf( '%d %s', $difference['h'], _n( 'hour', 'hours', $difference['h'] ) );
		}
		
		if ( $difference['i'] ) {
			$result[] = sprintf( '%d %s', $difference['i'], _n( 'minute', 'minutes', $difference['i'] ) );
		}
		
		if ( $difference['s'] ) {
			$result[] = sprintf( '%d %s', $difference['s'], _n( 'second', 'seconds', $difference['s'] ) );
		}
		
		// limit the precision
		$result = array_slice( $result, 0, $precision );
		
		$result = Format::and_list( $result );
		
		if ( $include_suffix ) {
			
			if ( $difference['invert'] == true ) {
				$result = _t( '%s from now', array( $result ) );
			}
			else {
				$result = _t( '%s ago', array( $result ) );
			}
			
		}
		
		return $result;
		
	}
	
	/**
	 * Similar to friendly(), but much more... fuzzy.
	 * 
	 * Returns a very short version of the difference in time between now and the current HDT object.
	 */
	public function fuzzy ( $comparison_date = 'now' )
	{

		// make sure the comparison date is something we can use
		if ( !$comparison_date instanceof DateTime ) {
			$comparison_date = DateTime::create( $comparison_date );
		}

		$difference = $comparison_date->int - $this->int;
		
		if ( $difference < 0 ) {
			$future = true;
		}
		else {
			$future = false;
		}

		$difference = abs( $difference );

		if ( $difference < self::MINUTE ) {
			$result = _t( 'just now' );
		}
		else if ( $difference < self::HOUR ) {
			$minutes = round( $difference / self::MINUTE );
			if ( $future ) {
				$result = sprintf( _n( 'in just a minute', 'in %d minutes', $minutes ), $minutes );
			}
			else {
				$result = sprintf( _n( '%d minute ago', '%d minutes ago', $minutes ), $minutes );
			}
		}
		else if ( $difference < self::DAY ) {
			$hours = round( $difference / self::HOUR );
			if ( $future ) {
				$result = sprintf( _n( 'in %d hour', 'in %d hours', $hours ), $hours );
			}
			else {
				$result = sprintf( _n( '%d hour ago', '%d hours ago', $hours ), $hours );
			}
		}
		else if ( $difference < self::WEEK ) {
			$days = round( $difference / self::DAY );
			if ( $future ) {
				$result = sprintf( _n( 'tomorrow', 'in %d days', $days ), $days );
			}
			else {
				$result = sprintf( _n( 'yesterday', '%d days ago', $days ), $days );
			}
		}
		else if ( $difference < self::MONTH ) {
			$weeks = round( $difference / self::WEEK );
			if ( $future ) {
				$result = sprintf( _n( 'next week', 'in %d weeks', $weeks ), $weeks );
			}
			else {
				$result = sprintf( _n( 'last week', '%d weeks ago', $weeks ), $weeks );
			}
		}
		else if ( $difference < self::YEAR ) {
			$months = round( $difference / self::MONTH );
			if ( $future ) {
				$result = sprintf( _n( 'next month', 'in %d months', $months ), $months );
			}
			else {
				$result = sprintf( _n( 'last month', '%d months ago', $months ), $months );
			}
		}
		else {
			$years = round( $difference / self::YEAR );
			if ( $future ) {
				$result = sprintf( _n( 'next year', 'in %d years', $years ), $years );
			}
			else {
				$result = sprintf( _n( 'last year', '%d years ago', $years ), $years );
			}
		}
		
		return $result;
		
	}
	
	/**
	 * Returns an array representing the difference between two times by interval.
	 * 
	 * <code>
	 * 	print_r( DateTime::difference( 'now', 'January 1, 2010' ) );
	 * 	// output (past): Array ( [invert] => [y] => 0 [m] => 9 [w] => 3 [d] => 5 [h] => 22 [i] => 33 [s] => 5 )
	 * 	print_r( DateTime::difference( 'now', 'January 1, 2011' ) );
	 * 	// output (future): Array ( [invert] => 1 [y] => 0 [m] => 2 [w] => 0 [d] => 3 [h] => 5 [i] => 33 [s] => 11 ) 
	 * </code>
	 * 
	 *  If 'invert' is true, the time is in the future (ie: x from now). If it is false, the time is in the past (ie: x ago).
	 *  
	 *  For more information, see PHP's DateInterval class, which this and friendly() attempt to emulate for < PHP 5.3
	 *  
	 *  @todo Add total_days, total_years, etc. values?
	 * 
	 * @param mixed $start_date The start date, as a HDT object or any format accepted by DateTime::create().
	 * @param mixed $end_date The end date, as a HDT object or any format accepted by DateTime::create().
	 * @return array Array of each interval and whether the interval is inverted or not.
	 */
	public static function difference( $start_date, $end_date )
	{
		
		// if the dates aren't HDT objects, try to convert them to one. this lets you pass in just about any format
		if ( !$start_date instanceof DateTime ) {
			$start_date = DateTime::create( $start_date );
		}
		
		if ( !$end_date instanceof DateTime ) {
			$end_date = DateTime::create( $end_date );
		}
		
		$result = array();
		
		// calculate the difference, in seconds
		$difference = $end_date->int - $start_date->int;
		
		if ( $difference < 0 ) {
			// if it's negative, time AGO
			$result['invert'] = false;
		}
		else {
			// if it's positive, time UNTIL
			$result['invert'] = true;
		}
		
		$difference = abs( $difference );
		
		// we'll progressively subtract from the seconds left, so initialize it
		$seconds_left = $difference;
		
		$result['y'] = floor( $seconds_left / self::YEAR );
		$seconds_left = $seconds_left - ( $result['y'] * self::YEAR );
		
		$result['m'] = floor( $seconds_left / self::MONTH );
		$seconds_left = $seconds_left - ( $result['m'] * self::MONTH );
		
		$result['w'] = floor( $seconds_left / self::WEEK );
		$seconds_left = $seconds_left - ( $result['w'] * self::WEEK );
		
		$result['d'] = floor( $seconds_left / self::DAY );
		$seconds_left = $seconds_left - ( $result['d'] * self::DAY );
		
		$result['h'] = floor( $seconds_left / self::HOUR );
		$seconds_left = $seconds_left - ( $result['h'] * self::HOUR );
		
		$result['i'] = floor( $seconds_left / self::MINUTE );
		$seconds_left = $seconds_left - ( $result['i'] * self::MINUTE );
		
		$result['s'] = $seconds_left;
		
		return $result;
		
	}

}

?>
