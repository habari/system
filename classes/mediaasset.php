<?php
/**
 * @package Habari
 *
 */

/**
 * MediaAsset represents a file or directory in the media system
 *
 * @property mixed $content The contents of this asset
 * @property boolean $is_dir Whether or not this asset is a directory
 * @property string $path The path of the asset
 * @property-read string $basename The basename of this asset's path
 *
 */
class MediaAsset
{
	const MODE_NONE = 0;
	const MODE_DATA = 1;
	const MODE_FILE = 2;
	const MODE_STREAM = 3;
	const MODE_UPLOAD = 4;

	protected $path;
	protected $is_dir;
	protected $content = null;
	protected $props = array();
	public $icon = null;
	protected $filename;
	protected $mode = self::MODE_NONE;

	/**
	 * MediaAsset constructor
	 *
	 * @param string $path The path of the asset
	 * @param boolean $is_dir true if the asset is a directory
	 * @param array $properties An associative array of property values
	 */
	public function __construct( $path, $is_dir, $properties = array(), $icon = null )
	{
		$this->path = $path;
		$this->is_dir = $is_dir;
		$this->props = $properties;
		$this->icon = $icon;
	}

	/**
	 * Return the content of the asset
	 *
	 * @return mixed The asset content
	 */
	protected function _get()
	{
		if ( empty( $this->content ) ) {
			switch ( $this->mode ) {
				case self::MODE_DATA:
					$this->content = Media::get( $this->path );
					break;
				case self::MODE_FILE:
					$this->content = file_get_contents( $this->filename );
					break;
				case self::MODE_STREAM:
					$this->content = stream_get_contents( $this->stream );
					fclose( $this->stream );
					break;
			}
		}
		return $this->content;
	}

	/**
	 * Set the content of this asset
	 *
	 * @param mixed $content The asset content
	 */
	protected function _set( $content )
	{
		$this->content = $content;
	}

	public function get_props()
	{
		return array_merge(
			array(
				'path' => $this->path,
				'basename' => basename( $this->path ),
				'title' => basename( $this->path ),
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
	public function __get( $name )
	{
		switch ( $name ) {
			case 'content':
				$output = $this->_get();
				break;
			case 'is_dir':
				$output = $this->is_dir;
				break;
			case 'path':
				$output = $this->path;
				break;
			case 'basename':
				$output = basename( $this->path );
				break;
			default:
				$output = $this->props[$name];
				break;
		}

		$output = Plugins::filter( 'mediaasset', $output, $name );

		return $output;

	}

	/**
	 * Set attributes about this asset
	 *
	 * @param string $name The name of the property to set
	 * @param mixed $value The value to set
	 */
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'content':
				$this->mode = self::MODE_DATA;
				return $this->_set( $value );
			case 'is_dir':
			case 'path':
				break;
			default:
				$this->props[$name] = $value;
				break;
		}
	}

	/**
	 * Load the asset data from a file
	 *
	 * @param string $file The filename to load
	 */
	public function load( $file )
	{
		$this->mode = self::MODE_FILE;
		$this->filename = $file;
	}

	/**
	 * Save the asset data to a file
	 *
	 * @param string $file The destination filename
	 * @return boolean True on success
	 */
	public function save( $file )
	{
		switch ( $this->mode ) {
			case self::MODE_DATA:
				return file_put_contents( $file, $this->content ) !== false;
				break;
			case self::MODE_UPLOAD:
				return move_uploaded_file( $this->filename, $file ) !== false;
				break;
			case self::MODE_FILE:
				return copy( $this->filename, $file );
				break;
			case self::MODE_STREAM:
				$dest = fopen( $file, 'w+' );
				stream_copy_to_stream( $this->stream, $dest );
				fclose( $this->stream );
				return fclose( $dest );
				break;
		}
	}

	/**
	 * Load the asset data from an upload
	 *
	 * @param array $files The $_FILES array created when a file is uploaded
	 */
	public function upload( $files )
	{
		$this->mode = self::MODE_UPLOAD;
		$this->filename = $files['tmp_name'];
	}


	/**
	 * Shortcut for putting an asset into the correct silo based on its path
	 *
	 * @return boolean True on success
	 */
	public function put()
	{
		return Media::put( $this );
	}

}

?>
