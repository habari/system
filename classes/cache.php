<?php
/**
 * Habari Cache Class
 *
 * Requires PHP 5.1 or later
 * Base class for caching computationally expensive or bandwidth intensive data
 * 
 * @package Habari
 */
abstract class Cache
{
	protected $_cache_data;
	protected $_cache_expiry;	

	public __construct()
	{
		$this->_cache_data = array();
		$this->_cache_expiry = array();		
	}
	
	/**
	 * Method to load a cached record. Should be overridden by child
	 * classes depending on the storage mechanism for the cache
	 * ie: a DBCache would load cached records with SQL, a DiskCache from files
	 *
	 * @param $name name of the cached record
	 * @param optional $force forces a reload from source instead of returning previously fetched data
	 * @return boolean TRUE if cached item is available, FALSE otherwise
	 **/

	abstract protected function _load( $name, $force = false ) { }

	/**
	 * Method to store a cached record. Should be overridden by child
	 * classes depending on the storage mechanism for the cache
	 * ie: a DBCache would store cached records within a database, a DiskCache into files
	 *
	 * Method should be idempotent, ie:, can be called multiple times for the same cached object
	 * @return boolean TRUE if cached item is stored successfully, FALSE otherwise
	 **/

	abstract protected function _store ( $name, $value, $expiry ) { }

	/**
	 * Renews cached item expiry time. Should be overridden by child
	 * classes depending on the storage mechanism for the cache
	 * ie: a DBCache would store cached records within a database, a DiskCache into files
	 *
	 * @return boolean TRUE if cached item is renewed successfully, FALSE otherwise
	 **/

	abstract protected function _renew ( $name, $expiry ) { }

	/**
	 * Has cached record with $name expired?
	 * 
	 * @param $name name of the cached item
	 * @return boolean TRUE if cached item is stale, FALSE if still valid
	 **/

	public function is_expired( $name )
	{
		$this->_load( $name );
		if ( $this->_cache_expiry[$name] > time() ) {
			return false;
		}
		return true;
	}

	/**
	 * Is record with $name in the cache?
	 * 
 	 * @param $name name of the cached item
	 * @return boolean TRUE if item is cached, FALSE if not
	 **/

	public function is_cached( $name )
	{
		$this->_load( $name );
		return in_array( $name, array_keys( $this->_cache_data ) );
	}	

	/**
	 * Get cached item with $name
	 * 
 	 * @param $name name of the cached item
	 * @param optional $force forces return of an item, even if it is expired
	 * @return mixed cached item if available or null on failure. Will not return expired items unless force is set to true
	 **/
	public function __get ( $name, $force = false )	
	{
		if ( $this->_is_cached( $name ) ) {			
			if ( $this->_is_expired( $name ) && $force == false ) {
				return null;
			}
			return $this->_cache_data[$name];
		}

		return null;
	}

	/**
	 * Caches item with $name, $value and with the expiry offset $expiry
	 * 
 	 * @param string $name name of the item to be cached
	 * @param mixed $value Item to be cached.
	 * @param int $expiry Time (in seconds) that cached item will remain valid. Offset from time(). Defaults to 1 hour
	 * @return returns true on success, false on failure
	 **/
	public function __set ( $name, $value, $expiry = 3600 )
	{
		return $this->_store( $name, $value, $expiry );		
	}
}