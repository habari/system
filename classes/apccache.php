<?php
/**
 * @package Habari
 *
 */

/**
 *
 * Contains the APCCache class
 */
class APCCache extends Cache
{
	protected $enabled = false;
	protected $cache_data = array();
	protected $prefix;

	/**
	 * Constructor for APCCache
	 */
	public function __construct()
	{
		$this->prefix = Options::get( 'GUID' );
		$this->enabled = extension_loaded( 'apc' );
		if ( !$this->enabled ) {
			Session::error( _t( "The APC Cache PHP module is not loaded - the cache is disabled.", "apccache" ), 'filecache' );
			EventLog::log( _t( "The APC Cache PHP module is not loaded - the cache is disabled.", "apccache" ), 'notice', 'cache', 'apccache' );
		}
	}

	/**
	 * Is record with $name in the cache?
	 *
	 * @param string $name name of the cached item
	 * @return boolean true if item is cached, false if not
	 */
	protected function _has( $name, $group )
	{
		if ( !$this->enabled ) {
			return false;
		}
		return apc_fetch( implode( ':', array( $this->prefix, $group, $name ) ) ) !== false;
	}

	/**
	 * Returns the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The item value or null if it doesn't exist in cache
	 */
	protected function _get( $name, $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		return apc_fetch( implode( ':', array( $this->prefix, $group, $name ) ) );
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
			if ( strpos( $cache_item['info'], $this->prefix . ":$group:" ) === 0 ) {
				$name = substr( $cache_item['info'], strlen( $this->prefix . ":$group:" ) );
				$group_cache[$name] = apc_fetch( $cache_item['info'] );
			}
		}
		return $group_cache;
	}

	/**
	 * Is group named $group in the cache?
	 *
	 * @param string $name name of the cached item
	 * @return boolean true if group is cached, false if not
	 */
	protected function _has_group( $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		$cache_info = apc_cache_info( 'user' );
		$group_cache = array();

		foreach ( $cache_info['cache_list'] as $cache_item ) {
			if ( strpos( $cache_item['info'], $this->prefix . ":$group:" ) === 0 ) {
				$group_cache[$cache_item['info']] = apc_fetch( $cache_item['info'] );
			}
		}
		return $this->_get_group( $group ) ? true : false;
	}

	protected function _set( $name, $value, $expiry, $group, $keep )
	{
		if ( !$this->enabled ) {
			return null;
		}

		Plugins::act( 'cache_set_before', $name, $group, $value, $expiry );

		$this->cache_data[$group][$name] = $value;

		if ( $keep ) {
			$keepcache = apc_fetch( $this->prefix . ':keepcache' );
			if ( !is_array( $keepcache ) ) {
				$keepcache = array();
			}
			$keepcache[$group][$name] = intval( $expiry );
			apc_store( $this->prefix . ':keepcache', $keepcache );
			$expiry = 0;
		}

		apc_store( implode( ':', array( $this->prefix, $group, $name ) ), $value, intval( $expiry ) );

		Plugins::act( 'cache_set_after', $name, $group, $value, $expiry );

		return true;
	}

	/**
	 * Expires the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @param string $match_mode (optional) how to match bucket names ('strict', 'regex', 'glob') (default 'strict')
	 */
	protected function _expire( $name, $group, $match_mode = 'strict' )
	{
		if ( !$this->enabled ) {
			return null;
		}
		$keys = array();
		switch ( strtolower( $match_mode ) ) {
			case 'glob':
				if ( array_key_exists( $group, $this->cache_data ) ) {
					$keys = preg_grep( Utils::glob_to_regex( $name ), array_keys( $this->cache_data[$group] ) );
				}
				break;
			case 'regex':
				if ( array_key_exists( $group, $this->cache_data ) ) {
					$keys = preg_grep( $name, array_keys( $this->cache_data[$group] ) );
				}
				break;
			case 'strict':
			default:
				$keys = array( $name );
				break;
		}

		foreach ( $keys as $key ) {
			Plugins::act( 'cache_expire_before', $name, $group );
			apc_delete( implode( ':', array( $this->prefix, $group, $key ) ) );
			Plugins::act( 'cache_expire_after', $name, $group );
		}
	}

	/**
	 * Return whether a named cache value has expired
	 *
	 * @param string $name The name of the cached item
	 * @param string $group The group of the cached item
	 * @return boolean true if the stored value has expired
	 */
	protected function _expired( $name, $group )
	{
		if ( !$this->enabled ) {
			return null;
		}

		// Do not check cached data, since we can return (and cache in this object) data if the cache is set to 'keep'
		$keepcache = apc_fetch( $this->prefix . ':keepcache' );

		if ( !self::_has( $name, $group ) || !isset( $keepcache[$group][$name] ) || $keepcache[$group][$name] < time() ) {
			return true;
		}
		else {
			return false;
		}
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

		Plugins::act( 'cache_extend_before', $name, $group, $expiry );

		if ( $this->_has( $name, $group ) ) {
			$cache_data = $this->_get( $name, $group );
			$this->_set( implode( ':', array( $this->prefix, $group, $name ) ), $cache_data, time() + $expiry, $group );
		}

		Plugins::act( 'cache_extend_after', $name, $group, $expiry );
	}

	/**
	 * Remove all cached items
	 */
	protected function _purge()
	{
		Plugins::act( 'cache_purge_before' );

		$cache_info = apc_cache_info( 'user' );

		$delete = array();
		foreach ( $cache_info['cache_list'] as $cache_item ) {
			if ( strpos( $cache_item['info'], $this->prefix . ":" ) === 0 ) {
				$delete[] = $cache_item['info'];
			}
		}
		foreach ( $delete as $item ) {
			apc_delete( $item );
		}

		Plugins::act( 'cache_purge_after' );
	}
}

?>
