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
	protected $url = array();

	/**
	 * MediaAsset constructor
	 *
	 * @param string $path The path of the asset
	 * @param boolean $is_dir true if the asset is a directory
	 */
	public function __construct($path, $is_dir = false)
	{
		$this->path = $path;
		$this->is_dir = $is_dir;
	}

	/**
	 * Return the content of the asset
	 *
	 * @return mixed The asset content
	 */
	public function get()
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
	public function set($content)
	{
		$this->content = $content;
	}

	/**
	 * Get the url where this asset can be viewed
	 *
	 * @param array $options Qualities of the URL to return (such as 'thumbnail' or 'size')
	 * @return string The URL of the asset
	 */
	public function url($options = array())
	{
		$hash = md5(serialize($options));
		if(empty($this->url[$hash])) {
			$this->url[$hash] = Media::url($this->path, $options);
		}
		return $this->url[$hash];
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
			case 'url':
				return $this->url();
			case 'content':
				return $this->get($content);
			case 'is_dir':
				return $this->is_dir;
			case 'thumbnail_url':
				return $this->url(array('size'=>'thumbnail'));
			case 'path':
				return $this->path;
		}
	}

}

?>