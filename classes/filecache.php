<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
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
			Session::error( sprintf( _t("The cache directory '%s' is not writable - the cache is disabled. The user, or group, which your web server is running as, needs to have read, write, and execute permissions on this directory."), $this->cache_location ), 'filecache' );
			EventLog::log( sprintf( _t("The cache directory '%s' is not writable - the cache is disabled."), $this->cache_location ), 'notice', 'cache', 'habari' );
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
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		return isset( $this->cache_files[$ghash][$hash] ) && $this->cache_files[$ghash][$hash]['expires'] > time() && file_exists( $this->cache_files[$ghash][$hash]['file'] );
	}

	/**
	 * Is group in the cache?
	 *
	 * @param string $name name of the cached item
	 * @return boolean TRUE if item is cached, FALSE if not
	 */
	protected function _has_group( $group )
	{
		if ( !$this->enabled ) {
			return false;
		}
		$ghash = $this->get_group_hash( $group );

		return ( isset( $this->cache_files[$ghash] ) && count($this->cache_files[$ghash]) > 1 );
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
			if ( isset( $this->cache_files[$ghash] ) ) {
				foreach ( $this->cache_files[$ghash] as $hash => $record ) {
					$this->cache_data[$group][$record['name']] = unserialize(
						file_get_contents( $record['file'] )
						);
				}
			}
			else {
				$this->cache_data[$group] = array();
			}
		}
		return $this->cache_data[$group];
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
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		if ( !isset( $this->cache_data[$group][$name] ) ) {
			if ( isset( $this->cache_files[$ghash][$hash] ) && $this->cache_files[$ghash][$hash]['expires'] > time() && file_exists( $this->cache_files[$ghash][$hash]['file'] ) ) {
				$this->cache_data[$group][$name] = unserialize( file_get_contents( $this->cache_files[$ghash][$hash]['file'] ) );
			}
			else {
				$this->cache_data[$group][$name] = null;
			}
		}
		return $this->cache_data[$group][$name];
	}

	protected function _set( $name, $value, $expiry, $group )
	{
		if ( !$this->enabled ) {
			return null;
		}
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		$this->cache_data[$group][$name] = $value;

		file_put_contents( $this->cache_location . $ghash . $hash, serialize( $value ) );
		$this->cache_files[$ghash][$hash] = array( 'file' => $this->cache_location . $ghash . $hash, 'expires' => time() + $expiry, 'name' => $name );
		$this->clear_expired();
		file_put_contents( $this->index_file, serialize( $this->cache_files ) );
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
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		if ( isset( $this->cache_files[$ghash][$hash] ) && file_exists( $this->cache_files[$ghash][$hash]['file'] ) ) {
			unlink( $this->cache_files[$ghash][$hash]['file'] );
			unset( $this->cache_files[$ghash][$hash] );
			$this->clear_expired();
			file_put_contents( $this->index_file, serialize( $this->cache_files ) );
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
		$hash = $this->get_name_hash( $name );
		$ghash = $this->get_group_hash( $group );

		if ( isset( $this->cache_files[$ghash][$hash] ) ) {
			$this->cache_files[$ghash][$hash]['expires'] = time() + $expiry;
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
		if ( $record['expires'] > time() ) {
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
