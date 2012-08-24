<?php
/**
 * @package Habari
 *
 */

/**
 * Contains the FileCache class
 *
 * Stores cache data in local files
 */
class FileCache extends Cache
{
	protected $cache_location;
	protected $enabled = false;
	protected $cache_files = array();
	protected $cache_data = array();
	protected $index_file;

	/**
	 * Constructor for FileCache
	 *
	 * Sets up paths etc. and reads cache index, if it exists.
	 */
	public function __construct()
	{
		if ( !defined( 'FILE_CACHE_LOCATION' ) ) {
			define( 'FILE_CACHE_LOCATION', HABARI_PATH . '/user/cache/' );
		}
		$this->cache_location = FILE_CACHE_LOCATION;
		$this->index_file = $this->cache_location . md5( 'index' . Options::get( 'GUID' ) ) . '.data';
		$this->enabled = is_writeable( $this->cache_location );
		if ( $this->enabled ) {
			if ( file_exists( $this->index_file ) ) {
				$this->cache_files = unserialize( file_get_contents( $this->index_file ) );
			}
		}
		else {
			Session::error( _t( "The cache directory '%s' is not writable - the cache is disabled. The user, or group, which your web server is running as, needs to have read, write, and execute permissions on this directory.", array( $this->cache_location ) ), 'filecache' );
			EventLog::log( _t( "The cache directory '%s' is not writable - the cache is disabled.", array( $this->cache_location ) ), 'notice', 'cache', 'habari' );
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
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		return isset( $this->cache_files[$ghash][$hash] )
			&& ( $this->cache_files[$ghash][$hash]['keep'] || $this->cache_files[$ghash][$hash]['expires'] > time() )
			&& file_exists( $this->cache_files[$ghash][$hash]['file'] );
	}

	/**
	 * Is group in the cache?
	 *
	 * @param string $name name of the cached item
	 * @return boolean true if item is cached, false if not
	 */
	protected function _has_group( $group )
	{
		if ( !$this->enabled ) {
			return false;
		}
		$ghash = $this->get_group_hash( $group );

		$valid = true;
		$now = time();
		foreach ( $this->cache_files[$ghash] as $hash => $record ) {
			if ( ! file_exists( $record['file'] ) || $record['expires'] <= $now ) {
				$valid = false;
				break;
			}
		}

		return ( isset( $this->cache_files[$ghash] ) && count( $this->cache_files[$ghash] ) > 1 ) && $valid;
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
		$ghash = $this->get_group_hash( $group );

		if ( !isset( $this->cache_data[$group] ) ) {
			$this->cache_data[$group] = array();
			if ( isset( $this->cache_files[$ghash] ) ) {
				foreach ( $this->cache_files[$ghash] as $hash => $record ) {
					$this->cache_data[$group][$record['name']] = unserialize(
						file_get_contents( $record['file'] )
					);
				}
			}
		}
		return $this->cache_data[$group];
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
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		if ( !isset( $this->cache_data[$group][$name] ) ) {
			$this->cache_data[$group][$name] = null;
			if ( isset( $this->cache_files[$ghash][$hash] ) && ($this->cache_files[$ghash][$hash]['keep'] || $this->cache_files[$ghash][$hash]['expires'] > time()) && file_exists( $this->cache_files[$ghash][$hash]['file'] ) ) {
				$this->cache_data[$group][$name] = unserialize( file_get_contents( $this->cache_files[$ghash][$hash]['file'] ) );
			}
		}
		return $this->cache_data[$group][$name];
	}

	protected function _set( $name, $value, $expiry, $group, $keep )
	{
		if ( !$this->enabled ) {
			return null;
		}

		Plugins::act( 'cache_set_before', $name, $group, $value, $expiry );

		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		if ( !isset( $this->cache_data[$group] ) ) {
			// prime our cache so the local version is up-to-date and complete
			$this->_get_group( $group );
		}
		$this->cache_data[$group][$name] = $value;

		file_put_contents( $this->cache_location . $ghash . $hash, serialize( $value ) );
		$this->cache_files[$ghash][$hash] = array( 'file' => $this->cache_location . $ghash . $hash, 'expires' => time() + $expiry, 'name' => $name, 'keep' => $keep );
		$this->clear_expired();
		file_put_contents( $this->index_file, serialize( $this->cache_files ) );

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

		$ghash = $this->get_group_hash( $group );
		foreach ( $keys as $key ) {
			Plugins::act( 'cache_expire_before', $name, $group );

			$hash = $this->get_name_hash( $key );

			if ( isset( $this->cache_files[$ghash][$hash] ) && file_exists( $this->cache_files[$ghash][$hash]['file'] ) ) {
				unlink( $this->cache_files[$ghash][$hash]['file'] );
				unset( $this->cache_files[$ghash][$hash] );
				unset( $this->cache_data[$group][$name] );
			}

			Plugins::act( 'cache_expire_after', $name, $group );
		}

		$this->clear_expired();
		file_put_contents( $this->index_file, serialize( $this->cache_files ) );
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
		if ( isset( $this->cache_files[$ghash][$hash] ) && $this->cache_files[$ghash][$hash]['expires'] > time() && file_exists( $this->cache_files[$ghash][$hash]['file'] ) ) {
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

		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		if ( isset( $this->cache_files[$ghash][$hash] ) ) {
			$this->cache_files[$ghash][$hash]['expires'] = time() + $expiry;
			$this->clear_expired();
			file_put_contents( $this->index_file, serialize( $this->cache_files ) );
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
	 * Get the unique hash for a given key.
	 *
	 * @param string $name The name of the cached item.
	 */
	private function get_name_hash( $name )
	{
		return md5( $name . Options::get( 'GUID' ) ) . '.cache';
	}

	/**
	 * Get the unique hash for a given key.
	 *
	 * @param string $name The name of the cached group.
	 */
	private function get_group_hash( $group )
	{
		return md5( $group . Options::get( 'GUID' ) ) . '.';
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
		foreach ( $this->cache_files as $ghash => $records ) {
			$this->cache_files[$ghash] = array_filter( $records, array( $this, 'record_fresh' ) );
		}
	}

}

?>
