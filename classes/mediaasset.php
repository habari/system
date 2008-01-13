<?php

/**
 * MediaAsset represents a file or directory in the media system
 *
 */

class MediaAsset
{
	protected $path;
	protected $is_dir;
	protected $content = null;
	protected $props = array();

	/**
	 * MediaAsset constructor
	 *
	 * @param string $path The path of the asset
	 * @param boolean $is_dir true if the asset is a directory
	 * @param array $properties An associative array of property values
	 */
	public function __construct($path, $is_dir, $properties = array())
	{
		$this->path = $path;
		$this->is_dir = $is_dir;
		$this->props = $properties;
	}

	/**
	 * Return the content of the asset
	 *
	 * @return mixed The asset content
	 */
	protected function _get()
	{
		if(empty($this->content)) {
			$this->content = Media::get($this->path);
		}
		return $this->content;
	}

	/**
	 * Set the content of this asset
	 *
	 * @param mixed $content The asset content
	 */
	protected function _set($content)
	{
		$this->content = $content;
	}

	public function get_props()
	{
		return array_merge(
			array(
				'path' => $this->path,
				'basename' => basename($this->path),
				'title' => basename($this->path),
			),
			$this->props
		);
	}

	/**
	 * Retrieve attributes about this asset
	 *
	 * @param string $name The name of the property to retrieve
	 * @return mixed The value requested
	 */
	public function __get($name)
	{
		switch($name) {
			case 'content':
				return $this->_get($content);
			case 'is_dir':
				return $this->is_dir;
			case 'path':
				return $this->path;
			case 'basename':
				return basename($this->path);
			default:
				return $this->props[$name];
		}
	}

	public function __set($name, $value)
	{
		switch($name) {
			case 'content':
				return $this->_set($value);
			case 'is_dir':
			case 'path':
				break;
			default:
				$this->props[$name] = $value;
				break;
		}
	}

}

?>