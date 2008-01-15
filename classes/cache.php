<?php
/**
 * @package Habari
 *
 * Contains the abstract Cache class.
 */

/**
 * Habari Cache Class
 *
 * Base class for caching computationally expensive or bandwidth intensive data
 */
abstract class Cache
{
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
 	 * @param string $name name of the cached item
	 * @return boolean TRUE if item is cached, FALSE if not
	 */
	public static function has( $name )
	{
		return self::$instance->_has( $name );
	}

	/**
	 * A cache instance implements this function to return whether a named cache exists.
	 *
	 * @param string $name The name of the cached item
	 * @return boolean TRUE if the item is cached, FALSE if not
	 */
	abstract protected function _has( $name );


	/**
	 * Returns the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The item value or NULL if it doesn't exist in cache
	 */
	public static function get( $name )
	{
		return self::$instance->_get( $name );
	}

	/**
	 * A cache instance implements this to return the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The item value or NULL if it doesn't exist in cache
	 */
	abstract protected function _get( $name );


	/**
	 * Set the named value in the cache with an expiration.
	 *
	 * @param string $name The name of the cached item
	 * @param mixed $value The value to store
	 * @param integer $expiry Number of second after the call that the cache will expire
	 */
	public static function set( $name, $value, $expiry = 3600 )
	{
		self::$instance->_set( $name, $value, $expiry );
	}

	/**
	 * A cache instance implements this to set the named value in the cache with an expiration.
	 *
	 * @param string $name The name of the cached item
	 * @param mixed $value The value to store
	 * @param integer $expiry Number of second after the call that the cache will expire
	 */
	abstract protected function _set( $name, $value, $expiry );


	/**
	 * Expires the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 */
	public static function expire( $name )
	{
		self::$instance->_expire( $name );
	}

	/**
	 * A cache instance implements this to expire the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 */
	abstract protected function _expire( $name );


	/**
	 * Extend the expiration of the named cached value.
	 *
	 * @param string $name The name of the cached item
	 * @param integer $expiry The duration in seconds to extend the cache expiration by
	 */
	public static function extend( $name, $expiry )
	{
		self::$instance->_extend( $name, $expiry );
	}

	/**
	 * A cache instance implements this to extend the expiration of the named cached value.
	 *
	 * @param string $name The name of the cached item
	 * @param integer $expiry The duration in seconds to extend the cache expiration by
	 */
	abstract protected function _extend( $name, $expiry );

}

?>
