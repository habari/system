<?php
/**
 * @package Habari
 *
 */

/**
 * Contains the MemcacheCache class
 *
 * Stores cache data in Memcache
 */
class MemcacheCache extends Cache
{
	protected $enabled = false;
	protected $cache_index = array();

	/**
	 * Constructor for MemcacheCache
	 *
	 * Sets up paths etc. and reads cache index, if it exists.
	 */
	public function __construct()
	{
		$this->prefix = Options::get( 'GUID' );
		$this->enabled = extension_loaded( 'memcache' );
		if ( $this->enabled ) {
			$this->memcache = new Memcache;
			$this->memcache->connect(
				Config::get('memcache_host', 'localhost'),
				Config::get('memcache_port', 11211)
			);

			$this->cache_index = $this->memcache->get('habari:cache:index');
		}
		else {
			Session::error( _t( "The Memcache PHP module is not loaded - the cache is disabled.", "memcache" ), 'memcachecache' );
			EventLog::log( _t( "The Memcache PHP module is not loaded - the cache is disabled.", "memcache" ), 'notice', 'cache', 'memcachecache' );
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

		return
			isset( $this->cache_index[$group][$name] )
			&& ( $this->cache_index[$group][$name]['keep'] || $this->cache_index[$group][$name]['expires'] > time() )
			&& $this->memcache->get( $this->cache_index[$group][$name]['file'] ) !== false;
	}

	/**
	 * Is group in the cache?
	 *
	 * @param string $group Name of the group to detect
	 * @internal string $name name of the cached item
	 * @return boolean true if item is cached, false if not
	 */
	protected function _has_group( $group )
	{
		if ( !$this->enabled ) {
			return false;
		}

		$valid = true;
		$now = time();
		foreach ( $this->cache_index[$group] as $name => $record ) {
			if ( ! file_exists( $record['file'] ) || $record['expires'] <= $now ) {
				$valid = false;
				break;
			}
		}

		return ( isset( $this->cache_index[$group] ) && count( $this->cache_index[$group] ) > 1 ) && $valid;
	}

	/**
	 * Returns the group from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @return mixed The group or array() if it doesn't exist in cache
	 */
	protected function _get_group( $group )
	{
		if ( !$this->enabled ) {
			return null;
		}

		$group_data = array();
		if ( isset( $this->cache_index[$group] ) ) {
			foreach ( $this->cache_index[$group] as $name => $record ) {
				$group_data[$name] = $this->memcache->get( $record['file'] );
			}
		}

		return $group_data;
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


		if ( isset( $this->cache_index[$group][$name] ) && ($this->cache_index[$group][$name]['keep'] || $this->cache_index[$group][$name]['expires'] > time())) {
			return $this->memcache->get( $this->cache_index[$group][$name]['file'] );
		}
		return null;
	}

	protected function _set( $name, $value, $expiry, $group, $keep )
	{
		if ( !$this->enabled ) {
			return null;
		}

		Plugins::act( 'cache_set_before', $name, $group, $value, $expiry );

		$file = 'habari:cache:' . $group . ':' . $name;
		$this->memcache->set($file, $value, null, $expiry);
		$this->cache_index[$group][$name] = array( 'file' => $file, 'expires' => time() + $expiry, 'name' => $name, 'keep' => $keep );
		$this->clear_expired();
		$this->memcache->set('habari:cache:index', $this->cache_index, null, 0);

		Plugins::act( 'cache_set_after', $name, $group, $value, $expiry );

		return true;
	}

	/**
	 * Expires the named value from the cache.
	 *
	 * @param string $name The name of the cached item
	 * @param string $group The name of the cache group
	 * @param string $match_mode (optional) how to match bucket names ('strict', 'regex', 'glob') (default 'strict')
	 */
	protected function _expire( $name, $group, $match_mode = 'strict' )
	{
		if ( !$this->enabled ) {
			return null;
		}

		// prime the variable cache.
		// dirty, dirty hack. we should *never* load all the data in, especially when we only care about the expirey
		// alas, this crappy code requires it
		$this->_get_group($group);

		$keys = array();
		switch ( strtolower( $match_mode ) ) {
			case 'glob':
				if ( array_key_exists( $group, $this->cache_index ) ) {
					$keys = preg_grep( Utils::glob_to_regex( $name ), array_keys( $this->cache_index[$group] ) );
				}
				break;
			case 'regex':
				if ( array_key_exists( $group, $this->cache_index ) ) {
					$keys = preg_grep( $name, array_keys( $this->cache_index[$group] ) );
				}
				break;
			case 'strict':
			default:
				$keys = array( $name );
				break;
		}

		foreach ( $keys as $key ) {
			Plugins::act( 'cache_expire_before', $name, $group );

			if ( isset( $this->cache_index[$group][$name] ) ) {
				$this->memcache->delete($this->cache_index[$group][$name]['file'], 0);
				unset( $this->cache_index[$group][$name] );
			}

			Plugins::act( 'cache_expire_after', $name, $group );
		}

		$this->clear_expired();
		$this->memcache->set('habari:cache:index', $this->cache_index, null, 0);
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
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		// Do not check cached data, since we can return (and cache in this object) data if the cache is set to 'keep'
		if ( isset( $this->cache_index[$ghash][$hash] ) && $this->cache_index[$ghash][$hash]['expires'] > time() && file_exists( $this->cache_index[$ghash][$hash]['file'] ) ) {
			return false;
		}
		else {
			return true;
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

		if ( isset( $this->cache_index[$group][$name] ) ) {
			$this->cache_index[$group][$name]['expires'] = time() + $expiry;
			$this->clear_expired();
			$this->memcache->set('habari:cache:index', $this->cache_index);
		}

		Plugins::act( 'cache_extend_after', $name, $group, $expiry );
	}

	/**
	 * Remove all cache files
	 */
	protected function _purge()
	{
		Plugins::act( 'cache_purge_before' );

		$glob = Utils::glob( FILE_CACHE_LOCATION . '*.data' );
		foreach ( $glob as $file ) {
			unlink( $file );
		}
		$glob = Utils::glob( FILE_CACHE_LOCATION . '*.cache' );
		foreach ( $glob as $file ) {
			unlink( $file );
		}

		Plugins::act( 'cache_purge_after' );
	}

	/**
	 * Check whether a given record is still fresh (e.g. has not expired).
	 */
	private function record_fresh( $record )
	{
		if ( $record['expires'] > time() || $record['keep'] ) {
			return true;
		}
		elseif ( file_exists( $record['file'] ) ) {
			unlink( $record['file'] );
		}
		return false;
	}

	/**
	 * Purge expired items from the cache.
	 */
	private function clear_expired()
	{
		foreach ( $this->cache_index as $ghash => $records ) {
			$this->cache_index[$ghash] = array_filter( $records, array( $this, 'record_fresh' ) );
		}
	}

}

?>