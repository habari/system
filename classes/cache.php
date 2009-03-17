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

	/**
	 * Set up the static cache instance in __autoload()
	 */
	public static function __static()
	{
		if( !defined( 'CACHE_CLASS' ) ) {
			define( 'CACHE_CLASS', 'FileCache' );
		}
		$cache_class = CACHE_CLASS;
		self::$instance = new $cache_class();
	}


	/**
	 * Is record with $name in the cache?
	 *
 	 * @param mixed $name name of the cached item or an array of array( string $group, string $name )
	 * @return boolean TRUE if item is cached, FALSE if not
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
		return self::$instance->_has( $name, $group );
	}

	/**
	 * A cache instance implements this function to return whether a named cache exists.
	 *
	 * @param string $name The name of the cached item
	 * @return boolean TRUE if the item is cached, FALSE if not
	 */
	abstract protected function _has( $name, $group );
	
	/**
	 * Is group in the cache?
	 *
 	 * @param string $group name of the cached group
	 * @return boolean TRUE if group is cached, FALSE if not
	 */
	public static function has_group( $group )
	{
		return self::$instance->_has_group( $group );
	}

	/**
	 * A cache instance implements this function to return whether a group exists.
	 *
	 * @param string $name The name of the cached group
	 * @return boolean TRUE if the group is cached, FALSE if not
	 */
	abstract protected function _has_group( $group );

	/**
	 * Returns the named value from the cache.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 * @return mixed The item value or NULL if it doesn't exist in cache
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
		return self::$instance->_get( $name, $group );
	}

	/**
	 * A cache instance implements this to return the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The item value or NULL if it doesn't exist in cache
	 */
	abstract protected function _get( $name, $group );
	
	/**
	 * Returns the group from the cache.
	 *
	 * @param string $name The name of the cached group
	 * @return mixed The item value or NULL if it doesn't exist in cache
	 */
	public static function get_group( $group )
	{
		return self::$instance->_get_group( $group );
	}

	/**
	 * A cache instance implements this to return the group from the cache.
	 *
	 * @param string $name The name of the cached group
	 * @return mixed The item value or NULL if it doesn't exist in cache
	 */
	abstract protected function _get_group( $group );


	/**
	 * Set the named value in the cache with an expiration.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 * @param mixed $value The value to store
	 * @param integer $expiry Number of second after the call that the cache will expire
	 */
	public static function set( $name, $value, $expiry = 3600 )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		self::$instance->_set( $name, $value, $expiry, $group );
	}

	/**
	 * A cache instance implements this to set the named value in the cache with an expiration.
	 *
	 * @param string $name The name of the cached item
	 * @param mixed $value The value to store
	 * @param integer $expiry Number of second after the call that the cache will expire
	 */
	abstract protected function _set( $name, $value, $expiry, $group );


	/**
	 * Expires the named value from the cache.
	 *
	 * @param mixed $name The name of the cached item or an array of array( string $group, string $name )
	 */
	public static function expire( $name )
	{
		if ( is_array( $name ) ) {
			$array = $name;
			list( $group, $name ) = $array;
		}
		else {
			$group = self::$default_group;
		}
		self::$instance->_expire( $name, $group );
	}

	/**
	 * A cache instance implements this to expire the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 */
	abstract protected function _expire( $name, $group );


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
		self::$instance->_extend( $name, $expiry, $group );
	}

	/**
	 * A cache instance implements this to extend the expiration of the named cached value.
	 *
	 * @param string $name The name of the cached item
	 * @param integer $expiry The duration in seconds to extend the cache expiration by
	 */
	abstract protected function _extend( $name, $expiry, $group );

}

?>
