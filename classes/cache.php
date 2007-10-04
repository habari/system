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
 * 
 * @package Habari
 */
abstract class Cache
{
	protected $_cache_data;
	protected $_cache_expiry;	

	public function __construct()
	{
		$this->_cache_data = array();
		$this->_cache_expiry = array();		
	}
	
	/**
	 * Method to load a cached record. Should be overridden by child
	 * classes depending on the storage mechanism for the cache,
	 * i.e.: a DBCache would load cached records with SQL, a DiskCache from files
	 *
	 * @param $name string name of the cached record
	 * @param $force boolean (optional) force a reload from source instead of returning previously fetched data
	 * @return boolean TRUE if cached item is available, FALSE otherwise
	 **/
	abstract protected function _load( $name, $force = false ) { }

	/**
	 * Method to store a cached record. Should be overridden by child
	 * classes depending on the storage mechanism for the cache
	 * ie: a DBCache would store cached records within a database, a DiskCache into files
	 *
	 * Method should be idempotent, ie:, can be called multiple times for the same cached object
	 * 
	 * @param $name string name for the record
	 * @param $value mixed the record to store
	 * @param $expiry int time until expiration of the record, in seconds
	 * @return boolean TRUE if cached item is stored successfully, FALSE otherwise
	 **/
	abstract protected function _store( $name, $value, $expiry ) { }

	/**
	 * Renews cached item expiry time. Should be overridden by child
	 * classes depending on the storage mechanism for the cache,
	 * i.e.: a DBCache would store cached records within a database, a DiskCache into files
	 *
	 * @param $name string name for the record
	 * @param $expiry int time until expiration of the record, in seconds
	 * @return boolean TRUE if cached item is renewed successfully, FALSE otherwise
	 **/
	abstract protected function _renew( $name, $expiry ) { }

	/**
	 * Has cached record with $name expired?
	 * 
	 * @param $name string name of the cached item
	 * @return boolean TRUE if cached item is stale, FALSE if still valid
	 **/
	public function is_expired( $name )
	{
		$this->_load( $name );
		if( $this->_cache_expiry[$name] > time() ) {
			return false;
		}
		return true;
	}

	/**
	 * Is record with $name in the cache?
	 * 
 	 * @param $name string name of the cached item
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
 	 * @param $name string name of the cached item
	 * @param $force boolean (optional) forces return of an item, even if it is expired
	 * @return mixed cached item if available or null on failure. Will not return expired items unless force is set to true
	 **/
	public function __get( $name, $force= false )	
	{
		if( $this->_is_cached( $name ) ) {			
			if( $this->_is_expired( $name ) && $force == false ) {
				return null;
			}
			return $this->_cache_data[$name];
		}

		return null;
	}

	/**
	 * Caches item with $name, $value and with the expiry offset $expiry
	 * 
 	 * @param $name string name of the item to be cached
	 * @param $value mixed Item to be cached.
	 * @param $expiry int Time (in seconds) that cached item will remain valid. Offset from time(). Defaults to 1 hour.
	 * @return boolean TRUE on success, FALSE on failure
	 **/
	public function __set( $name, $value, $expiry = 3600 )
	{
		return $this->_store( $name, $value, $expiry );		
	}

}

?>
