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
		return isset( self::$registry[ $key ] );
	}

	/**
	 * Fetch data from registry
	 *
	 * @param string $key key name
	 * @return mixed (empty object on invalid key)
	 */
	public static function get( $key, $default = null )
	{
		if ( !self::exists( $key ) ) {
			return $default;
		}
		return self::$registry[ $key ];
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
