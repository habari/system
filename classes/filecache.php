<?php

/**
 * @pacakge Habari
 *
 * Contains the FileCache class
 */

/**
 * Stores cache data in local files
 */
class FileCache extends Cache
{
	protected $cache_location;
	protected $enabled = false;
	protected $cache_files = array();
	protected $cache_data = array();
	protected $index_file;

	public function __construct()
	{
		if(!defined('FILE_CACHE_LOCATION')) {
			define('FILE_CACHE_LOCATION', HABARI_PATH . '/system/cache/');
		}
		$this->cache_location = FILE_CACHE_LOCATION;
		$this->index_file = $this->cache_location . md5('index' . Options::get('GUID')) . '.data';
		$this->enabled = is_writeable($this->cache_location);
		if($this->enabled) {
			if(file_exists($this->index_file)) {
				$this->cache_files = unserialize(file_get_contents($this->index_file));
			}
		}
	}

	protected function _has( $name )
	{
		if(!$this->enabled) {
			return false;
		}
		$hash = $this->get_name_hash($name);

		return isset($this->cache_files[$hash]) && $this->cache_files[$hash]['expires'] > time();
	}

	protected function _get( $name )
	{
		if(!$this->enabled) {
			return null;
		}
		$hash = $this->get_name_hash($name);

		if(!isset($this->cache_data[$hash])) {
			if(isset($this->cache_files[$hash])) {
				if($this->cache_files[$hash]['expires'] > time()) {
					$this->cache_data[$hash] = unserialize(file_get_contents($this->cache_files[$hash]['file']));
				}
				else {
					$this->cache_data[$hash] = null;
				}
			}
			else {
				$this->cache_data[$hash] = null;
			}
			$this->cache_data[$hash] = isset($this->cache_files[$hash]) ? unserialize(file_get_contents($this->cache_files[$hash]['file'])) : null;
		}
		return $this->cache_data[$hash];
	}

	protected function _set( $name, $value, $expiry )
	{
		if(!$this->enabled) {
			return null;
		}
		$hash = $this->get_name_hash($name);

		$this->cache_data[$hash] = $value;

		file_put_contents($this->cache_location . $hash, serialize($value));
		$this->cache_files[$hash] = array('file'=>$this->cache_location . $hash, 'expires'=>time() + $expiry);
		$this->clear_expired();
		file_put_contents($this->index_file, serialize($this->cache_files));
	}

	protected function _expire( $name )
	{
		if(!$this->enabled) {
			return null;
		}

	}

	protected function _extend( $name, $expiry)
	{
		if(!$this->enabled) {
			return null;
		}

	}

	private function get_name_hash( $name )
	{
		return md5($name . Options::get('GUID')) . '.cache';
	}

	private function record_fresh($record)
	{
		if($record['expires'] > time()) {
			return true;
		}
		unlink($this->cache_location . $record['file']);
		return false;
	}

	private function clear_expired()
	{
		$this->cache_files = array_filter($this->cache_files, array($this, 'record_fresh'));
	}

}

?>