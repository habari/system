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
	protected function _has( $name )
	{
		if ( !$this->enabled ) {
			return false;
		}
		return apc_fetch( $name ) !== false;
	}

	/**
	 * Returns the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The item value or NULL if it doesn't exist in cache
	 */
	protected function _get( $name )
	{
		if ( !$this->enabled ) {
			return null;
		}
		return apc_fetch( $name );
	}

	protected function _set( $name, $value, $expiry )
	{
		if ( !$this->enabled ) {
			return null;
		}
		apc_store( $name, $value, $expiry );
	}

	/**
	 * Expires the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 */
	protected function _expire( $name )
	{
		if ( !$this->enabled ) {
			return null;
		}
		apc_delete( $name );
	}

	/**
	 * Extend the expiration of the named cached value.
	 *
	 * @param string $name The name of the cached item
	 * @param integer $expiry The duration in seconds to extend the cache expiration by
	 */
	protected function _extend( $name, $expiry )
	{
		if ( !$this->enabled ) {
			return null;
		}
		if ( $this->_has( $name ) ) {
			$cache_data = $this->_get( $name );
			$cache_info = apc_cache_info( 'user' );
			foreach ( $cache_info['cache_list'] as $cache_item ) {
				if ( $cache_item['info'] == $name ) {
					$expiry = ( ($cache_item['creation_time'] + $cache_item['ttl']) - time() ) + $expiry;
					$this->_set( $name, $cache_data, $expiry );
					return;
				}
			}
		}
	}
}

?>
