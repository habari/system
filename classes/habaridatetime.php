<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php
/**
 * @package Habari
 *
 */

/**
 * HabariDateTime class to wrap dates in.
 *
 * @property-read HabariDateTime $clone Returns a clonned object.
 * @property-read string $sql Returns a unix timestamp for inserting into DB.
 * @property-read int $int Returns a unix timestamp as integer.
 */
class HabariDateTime extends DateTime
{
	private static $default_timezone;
	private static $default_datetime_format = 'c';
	private static $default_date_format;
	private static $default_time_format;
	
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
		
		self::$default_date_format = Options::get('dateformat');
		self::$default_time_format = Options::get('timeformat');

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
	 * @param string The deafult timezone.
	 */
	public static function get_default_timezone()
	{
		return self::$default_timezone;
	}
	
	/**
	 * Helper function to create a HabariDateTime object for the given
	 * time and timezone. If no time is given, defaults to 'now'. If no
	 * timezone given defaults to timezone set in {@link set_default_timezone()}
	 *
	 * @static
	 * @see DateTime::__construct()
	 * @param string $time String in a format accepted by
	 * {@link http://ca.php.net/strtotime strtotime()}, defaults to "now".
	 * @param string $timezone A timezone name, not an abbreviation.
	 */
	public static function date_create( $time = null, $timezone = null )
	{
		if ( $time instanceOf HabariDateTime ) {
			return $time;
		}
		elseif ( $time instanceOf DateTime ) {
			$time = $time->format('U');
		}
		elseif ( $time == null ) {
			$time = 'now';
		}
		elseif ( is_numeric($time) ) {
			$time = '@' . $time;
		}

		if ( $timezone === null ) {
			$timezone = self::$default_timezone;
		}
		
		// passing the timezone to construct doesn't seem to do anything.
		$datetime = new HabariDateTime($time);
		$datetime->set_timezone($timezone);
		return $datetime;
	}
	
	/**
	 * Set the date of this object
	 *
	 * @see DateTime::setDate()
	 * @param int $year Year of the date
	 * @param int $month Month of the date
	 * @param int $day Day of the date
	 */
	public function set_date( $year, $month, $day )
	{
		parent::setDate($year, $month, $day);
		return $this;
	}
	
	/**
	 * Sets the ISO date
	 *
	 * @see DateTime::setISODate()
	 * @param int $year Year of the date
	 * @param int $month Month of the date
	 * @param int $day Day of the date
	 */
	public function set_isodate( $year, $week, $day = null )
	{
		parent::setISODate($year, $week, $day);
		return $this;
	}
	
	/**
	 * Set the time of this object
	 *
	 * @see DateTime::setTime()
	 * @param int $hour Hour of the time
	 * @param int $minute Minute of the time
	 * @param int $second Second of the time
	 */
	public function set_time( $hour, $minute, $second = null )
	{
		parent::setTime($hour, $minute, $second);
		return $this;
	}
	
	/**
	 * Set the timezone for this datetime object. Can be either string
	 * timezone identifier, or DateTimeZone object.
	 * 
	 * @see DateTime::setTimezone()
	 * @param mixed The timezone to use.
	 * @return HabariDateTime $this object.
	 */
	public function set_timezone( $timezone )
	{
		if ( ! $timezone instanceof DateTimeZone ) {
			$timezone = new DateTimeZone($timezone);
		}
		parent::setTimezone($timezone);
		return $this;
	}
	
	/**
	 * Get the timezone identifier that is set for this datetime object.
	 * 
	 * @return DateTimeZone The timezone object.
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
	public function format($format = null)
	{
		if ( $format === null ) {
			$format = self::$default_datetime_format;
		}
		return parent::format($format);
	}

	public function text_format($format) 
	{ 
		return preg_replace_callback('%\{(\w)\}%i', array($this, 'text_format_callback'), $format); 
	} 
	
	private function text_format_callback($matches) 
	{ 
		return $this->format($matches[1]); 
	}
	
	/**
	 * Alters the timestamp
	 *
	 * @param string $format A format accepted by {@link http://php.net/strtotime strtotime()}.
	 * @return HabariDateTime $this object.
	 */
	public function modify( $args )
	{
		parent::modify( $args );
		return $this;
	}
	
	/**
	 * @see format()
	 */
	public function get($format = null)
	{
		return $this->format($format);
	}
	
	/**
	 * Echos date formatted according to given format.
	 * 
	 * @see format()
	 * @param string $format Format accepted by {@link http://php.net/date date()}.
	 */
	public function out($format = null)
	{
		echo $this->format($format);
	}
	
	/**
	 * Magic method called when this object is cast to string. Returns the
	 * unix timestamp of this object.
	 * 
	 * @return string The unix timestamp
	 */
	public function __toString()
	{
		return $this->format('U');
	}
	
	/**
	 * Magic method to get magic ponies... properties, I mean.
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'clone':
				return clone $this;

			case 'sql':
				return $this->format('U');
				break;

			case 'int':
				return intval( $this->format('U') );
				break;
				
			case 'time':
				return $this->format( self::get_default_time_format() );
				break;
				
			case 'date':
				return $this->format( self::get_default_date_format() );
				break;

			default:
				$info = getdate($this->format('U'));
				$info['mon0'] = substr('0' . $info['mon'], -2, 2);
				$info['mday0'] = substr('0' . $info['mday'], -2, 2);
				if(isset($info[$property])) {
					return $info[$property];
				}
				return $this->$property;
		}
	}
	
	public static function get_default_date_format ( ) {
		
		return self::$default_date_format;
		
	}
	
	public static function get_default_time_format ( ) {
		
		return self::$default_time_format;
		
	}
	
	/**
	 * Returns an associative array containing the date information for
	 * this HabariDateTime object, as per {@link http://php.net/getdate getdate()}
	 *
	 * @return array Associative array containing the date information
	 */
	public function getdate()
	{
		$info = getdate($this->format('U'));
		$info['mon0'] = substr('0' . $info['mon'], -2, 2);
		$info['mday0'] = substr('0' . $info['mday'], -2, 2);
		return $info;
	}
}

?>
