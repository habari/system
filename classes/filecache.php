<?php

/**
 * @package Habari
 *
 * Contains the FileCache class
 */

/**
 * Stores cache data in local files
 */
class FileCache extends Cache
{
	protected $cache_location;
	protected $enabled= false;
	protected $cache_files= array();
	protected $cache_data= array();
	protected $index_file;

	/**
	 * Constructor for FileCache
	 *
	 * Sets up paths etc. and reads cache index, if it exists.
	 */
	public function __construct()
	{
		if ( !defined( 'FILE_CACHE_LOCATION' ) ) {
			define( 'FILE_CACHE_LOCATION', HABARI_PATH . '/system/cache/' );
		}
		$this->cache_location= FILE_CACHE_LOCATION;
		$this->index_file= $this->cache_location . md5( 'index' . Options::get( 'GUID' ) ) . '.data';
		$this->enabled= is_writeable( $this->cache_location );
		if ( $this->enabled ) {
			if ( file_exists( $this->index_file ) ) {
				$this->cache_files= unserialize( file_get_contents( $this->index_file ) );
			}
		}
		else {
			EventLog::log( 'Cache directory `' . $this->cache_location . '` not writable, filecache disabled', 'info', 'default', 'habari' );
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
		$hash= $this->get_name_hash( $name );

		return isset( $this->cache_files[$hash] ) && $this->cache_files[$hash]['expires'] > time();
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
		$hash= $this->get_name_hash( $name );

		if ( !isset( $this->cache_data[$hash] ) ) {
			if ( isset( $this->cache_files[$hash] ) && $this->cache_files[$hash]['expires'] > time() && file_exists( $this->cache_files[$hash]['file'] ) ) {
				$this->cache_data[$hash]= unserialize( file_get_contents( $this->cache_files[$hash]['file'] ) );
			}
			else {
				$this->cache_data[$hash]= null;
			}
		}
		return $this->cache_data[$hash];
	}

	protected function _set( $name, $value, $expiry )
	{
		if ( !$this->enabled ) {
			return null;
		}
		$hash= $this->get_name_hash( $name );

		$this->cache_data[$hash]= $value;

		file_put_contents( $this->cache_location . $hash, serialize( $value ) );
		$this->cache_files[$hash]= array( 'file'=>$this->cache_location . $hash, 'expires'=>time() + $expiry );
		$this->clear_expired();
		file_put_contents( $this->index_file, serialize( $this->cache_files ) );
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
		$hash= $this->get_name_hash( $name );
		if( isset( $this->cache_files[$hash] ) ) {
			unlink( $this->cache_files[$hash]['file'] );
		}
		unset($this->cache_files[$hash]);
		$this->clear_expired();
		file_put_contents( $this->index_file, serialize( $this->cache_files ) );
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
		$hash= $this->get_name_hash( $name );
		if( isset( $this->cache_files[$hash] ) ) {
			$this->cache_files[$hash]['expires'] = time() + $expiry;
			$this->clear_expired();
			file_put_contents( $this->index_file, serialize( $this->cache_files ) );
		}
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
	 * Check whether a given record is still fresh (e.g. has not expired).
	 */
	private function record_fresh( $record )
	{
		if ( $record['expires'] > time() ) {
			return true;
		}
		unlink( $record['file'] );
		return false;
	}

	/**
	 * Purge expired items from the cache.
	 */
	private function clear_expired()
	{
		$this->cache_files= array_filter( $this->cache_files, array( $this, 'record_fresh' ) );
	}

}

?>
