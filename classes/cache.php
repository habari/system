<?php
/**
 * @package Habari
 *
 */

/**
 * Habari Cache Class
 *
 * Base abstract class for caching computationally expensive or bandwidth intensive data
 */
abstract class Cache
{
	protected static $default_group = 'default';
	protected static $instance;
	protected static $cache_class;

	/**
	 * Set up the static cache instance in __autoload()
	 */
	public static function __static()
	{
		self::$cache_class = Config::get('cache_class', defined('CACHE_CLASS') ? CACHE_CLASS : 'FileCache');
		self::$instance = new self::$cache_class();
	}


	/**
	 * Is record with $name in the cache?
	 *
 	 * @param mixed $name name of the cached item or an array of array( string $group, string $name )
	 * @return boolean true if item is cached, false if not
	 */
	public static function has( $name )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		$group = self::site_unique() . $group;
		return self::$instance->_has( $name, $group );
	}

	/**
	 * A cache instance implements this function to return whether a named cache exists.
	 *
	 * @param string $name The name of the cached item
	 * @return boolean true if the item is cached, false if not
	 */
	abstract protected function _has( $name, $group );

	/**
	 * Is group in the cache?
	 *
 	 * @param string $group name of the cached group
	 * @return boolean true if group is cached, false if not
	 */
	public static function has_group( $group )
	{
		$group = self::site_unique() . $group;
		return self::$instance->_has_group( $group );
	}

	/**
	 * A cache instance implements this function to return whether a group exists.
	 *
	 * @param string $name The name of the cached group
	 * @return boolean true if the group is cached, false if not
	 */
	abstract protected function _has_group( $group );

	/**
	 * Returns the named value from the cache.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 * @return mixed The item value or null if it doesn't exist in cache
	 */
	public static function get( $name )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		$group = self::site_unique() . $group;
		return self::$instance->_get( $name, $group );
	}

	/**
	 * A cache instance implements this to return the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The item value or null if it doesn't exist in cache
	 */
	abstract protected function _get( $name, $group );

	/**
	 * Returns the group from the cache.
	 *
	 * @param string $name The name of the cached group
	 * @return mixed The item value or null if it doesn't exist in cache
	 */
	public static function get_group( $group )
	{
		$group = self::site_unique() . $group;
		return self::$instance->_get_group( $group );
	}

	/**
	 * A cache instance implements this to return the group from the cache.
	 *
	 * @param string $name The name of the cached group
	 * @return mixed The item value or null if it doesn't exist in cache
	 */
	abstract protected function _get_group( $group );


	/**
	 * Set the named value in the cache with an expiration.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 * @param mixed $value The value to store
	 * @param integer $expiry Number of second after the call that the cache will expire
	 * @param boolean $keep If true, retain the cache value even after expiry but report the cache as expired
	 */
	public static function set( $name, $value, $expiry = 3600, $keep = false )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		$group = self::site_unique() . $group;
		return self::$instance->_set( $name, $value, $expiry, $group, $keep );
	}

	/**
	 * A cache instance implements this to set the named value in the cache with an expiration.
	 *
	 * @param string $name The name of the cached item
	 * @param mixed $value The value to store
	 * @param integer $expiry Number of second after the call that the cache will expire
	 */
	abstract protected function _set( $name, $value, $expiry, $group, $keep );


	/**
	 * Expires the named value from the cache.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 * @param string $match_mode (optional) how to match item names ('strict', 'regex', 'glob') (default 'strict')
	 */
	public static function expire( $name, $match_mode = 'strict' )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		$group = self::site_unique() . $group;
		self::$instance->_expire( $name, $group, $match_mode );
	}

	/**
	 * A cache instance implements this to expire the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 */
	abstract protected function _expire( $name, $group );

	/**
	 * Check if a named value in the cache has expired.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 */
	public static function expired( $name )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		$group = self::site_unique() . $group;
		return self::$instance->_expired( $name, $group );
	}

	/**
	 * A cache instance implements this to expire the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 */
	abstract protected function _expired( $name, $group );

	/**
	 * Extend the expiration of the named cached value.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 * @param integer $expiry The duration in seconds to extend the cache expiration by
	 */
	public static function extend( $name, $expiry )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		$group = self::site_unique() . $group;
		self::$instance->_extend( $name, $expiry, $group );
	}

	/**
	 * A cache instance implements this to extend the expiration of the named cached value.
	 *
	 * @param string $name The name of the cached item
	 * @param integer $expiry The duration in seconds to extend the cache expiration by
	 */
	abstract protected function _extend( $name, $expiry, $group );

	public static function debug() { return self::$instance->debug_data(); }

	/**
	 * Empty the cache completely
	 *
	 */
	public static function purge()
	{
		return self::$instance->_purge();
	}

	/**
	 * Retrieve the class used for caching
	 * @return string The class used for caching
	 */
	public static function get_class()
	{
		return self::$cache_class;
	}

	/**
	 * Return a string unique to this cache so that site caches don't collide
	 * @return string
	 */
	private static function site_unique()
	{
		static $unique = false;

		if(!$unique) {
			$unique = '';
			if(isset(Config::get( 'db_connection' )->connection_string)) {
				$unique .= Config::get( 'db_connection' )->connection_string;
			}
			if(isset(Config::get( 'db_connection' )->prefix)) {
				$unique .= Config::get( 'db_connection' )->prefix;
			}
			$unique = md5($unique);
		}
		return $unique;
	}
}

?>
