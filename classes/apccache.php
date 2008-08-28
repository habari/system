<?php

/**
 * @package Habari
 *
 * Contains the APCCache class
 */

class APCCache extends Cache
{
	protected $enabled= false;
	
	/**
	 * Constructor for APCCache
	 */
	public function __construct()
	{
		$this->enabled = extension_loaded( 'apc' );
		if ( !$this->enabled ) {
			Session::error( _t("The APC Cache PHP module is not loaded - the cache is disabled.", "apccache"), 'filecache' );
			EventLog::log( _t("The APC Cache PHP module is not loaded - the cache is disabled.", "apccache"), 'notice', 'cache', 'apccache' );
		}
	}

	/**
	 * Is record with $name in the cache?
	 *
	 * @param string $name name of the cached item
	 * @return boolean TRUE if item is cached, FALSE if not
	 */
	protected function _has( $name, $group )
	{
		if ( !$this->enabled ) {
			return false;
		}
		return apc_fetch( "$group:$name" ) !== false;
	}

	/**
	 * Returns the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The item value or NULL if it doesn't exist in cache
	 */
	protected function _get( $name, $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		return apc_fetch( "$group:$name" );
	}
	
	/**
	 * Returns the named values from a group of cache.
	 *
	 * @param string $name The name of the cached item
	 * @return array The cache records of the group
	 */
	protected function _get_group( $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		$cache_info = apc_cache_info( 'user' );
		$group_cache = array();
		
		foreach ( $cache_info['cache_list'] as $cache_item ) {
			if ( strpos( $cache_item['info'], "$group:" ) === 0 ) {
				$name = substr( $cache_item['info'], strlen( "$group:" ) );
				$group_cache[$name] = apc_fetch( $cache_item['info'] );
			}
		}
		return $group_cache;
	}
	
	/**
	 * Is group named $group in the cache?
	 *
	 * @param string $name name of the cached item
	 * @return boolean TRUE if group is cached, FALSE if not
	 */
	protected function _has_group( $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		$cache_info = apc_cache_info( 'user' );
		$group_cache = array();
		
		foreach ( $cache_info['cache_list'] as $cache_item ) {
			if ( strpos( $cache_item['info'], "$group:" ) === 0 ) {
				$group_cache[$cache_item['info']] = apc_fetch( $cache_item['info'] );
			}
		}
		return $this->_get_group( $group ) ? true : false;
	}

	protected function _set( $name, $value, $expiry, $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		apc_store( "$group:$name", $value, intval($expiry) );
	}

	/**
	 * Expires the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 */
	protected function _expire( $name, $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		apc_delete( "$group:$name" );
	}

	/**
	 * Extend the expiration of the named cached value.
	 *
	 * @param string $name The name of the cached item
	 * @param integer $expiry The duration in seconds to extend the cache expiration by
	 */
	protected function _extend( $name, $expiry, $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		if ( $this->_has( $name, $group ) ) {
			$cache_data = $this->_get( $name, $group );
			$this->_set( "$group:$name", $cache_data, time() + $expiry, $group );
		}
	}
}

?>
