<?php
/**
 * @package Habari
 *
 */
// vim: le=unix syntax=php ts=4 noet sw=4

/**
 * Habari Config Class
 *
 * Configuration registry class
 *
 */
class Config
{
	/**
	 * Registry of configuration data
	 */
	protected static $registry = array();
	protected static $prefix = 'habari_';

	/**
	 * Static; private constructor
	 */
	private function __construct()
	{
	}

	/**
	 * See if a key exists
	 *
	 * @param string $key key name
	 * @return bool
	 */
	public static function exists( $key )
	{
		$result = isset( self::$registry[ $key ] ) || array_key_exists(self::$prefix . $key, $_SERVER);
		// If asking for the db_connection and a connection_string exists, the db_connection does, too.
		if($key == 'db_connection') {
			if(self::exists('connection_string')) {
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * Fetch data from registry
	 *
	 * @param string $key key name
	 * @return mixed (empty object on invalid key)
	 */
	public static function get( $key, $default = null )
	{
		$result = null;
		// If asking for the db_connection, but it's not stored that way...
		if($key == 'db_connection' && !isset( self::$registry[ 'db_connection' ] ) && self::exists('connection_string')) {
			$result = array(
				'connection_string' => self::get('connection_string'),
				'username' => self::get('username', ''),
				'password' => self::get('password', ''),
				'prefix' => self::get('prefix', ''),
			);
			$result = (object)$result;
		}
		// If the key doesn't exist, return the default
		elseif ( !self::exists( $key ) ) {
			$result = $default;
		}
		// Return the key value that is stored
		else {
			$result = isset(self::$registry[ $key ]) ? self::$registry[ $key ] : $_SERVER[self::$prefix . $key];
		}
		return $result;
	}

	/**
	 * Set data in registry
	 *
	 * Note: arrays become objects for easy fetching
	 *
	 * @param string $key key name
	 * @param mixed  $val value to store
	 * @return bool true if new key, false if key already exists
	 */
	public static function set( $key, $val )
	{
		$new = !self::exists( $key );
		if ( is_scalar( $val ) ) {
			self::$registry[ $key ] = $val;
		}
		else {
			$val = (object)$val;
			self::$registry[ $key ] = (object)$val;
		}
		return $new;
	}

	/**
	 * Unset data
	 *
	 * @param string $key key name
	 * @return void
	 */
	public static function clear( $key )
	{
		if ( self::exists( $key ) ) {
			unset( self::$registry[ $key ] );
		}
	}
}
