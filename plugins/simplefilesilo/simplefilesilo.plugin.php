<?php

/**
 * Simple file access silo
 *
 */

class SimpleFileSilo extends Plugin implements MediaSilo
{
	protected $root = null;
	protected $url = null;

	const SILO_NAME = 'simple_file';

	const DERIV_DIR = '.deriv';

	/**
	 * Provide plugin info to the system
	 */
	public function info()
	{
		return array(
			'name' => 'Simple File Media Silo',
			'version' => '1.0',
			'url' => 'http://habariproject.org/',
			'author' =>	'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'license' => 'Apache License 2.0',
			'description' => 'Demonstrates basic silo functionality',
			'copyright' => '2008',
		);
	}

	/**
	 * Initialize some internal values when plugin initializes
	 */
	public function action_init()
	{
		$this->root = Options::get('simple_file_root');
		$this->url = Options::get('simple_file_url');
	}

	/**
	 * Return basic information about this silo
	 *   name- The name of the silo, used as the root directory for media in this silo
	 **/
	public function silo_info()
	{
		return array(
			'name' => self::SILO_NAME,
		);
	}

	/**
	 * Return directory contents for the silo path
	 * @param string $path The path to retrieve the contents of
	 * @return array An array of MediaAssets describing the contents of the directory
	 **/
	public function silo_dir( $path )
	{
		if(!isset( $this->root )) {
			return array();
		}

		$path = preg_replace('%\.{2,}%', '.', $path);
		$result = array();

		$dir = glob($this->root . $path . '/*');
		foreach($dir as $item) {
			if( substr(basename($item), 0, 1) == '.' ) {
				continue;
			}
			$result[] = new MediaAsset( self::SILO_NAME . '/' . $path . ($path == '' ? '' : '/') . basename($item), is_dir($item) );
		}
		return $result;
	}


	/**
	 * Get the file from the specified path
	 * @param string $path The path of the file to retrieve
	 * @param array $qualities Qualities that specify the version of the file to retrieve.
	 * @return MediaAsset The requested asset
	 **/
	public function silo_get( $path, $qualities = null )
	{
		if(!isset( $this->root )) {
			return false;
		}

		$path = preg_replace('%\.{2,}%', '.', $path);

		$file = $this->root . '/' . $path;

		if(file_exists($file)) {
			$asset = new MediaAsset( self::SILO_NAME . '/' . $path );
			$asset->set(file_get_contents($file));
			return $asset;
		}
		return false;
	}

	/**
	 * Get the direct URL of the file of the specified path
	 * @param string $path The path of the file to retrieve
	 * @param array $qualities Qualities that specify the version of the file to retrieve.
	 * @return string The requested url
	 **/
	public function silo_url( $path, $qualities = null )
	{
		if(!isset( $this->url )) {
			return false;
		}

		$path = preg_replace('%\.{2,}%', '.', $path);

		// Return a thumbnail URL?
		if(isset($qualities) && isset($qualities['size']) && $qualities['size'] == 'thumbnail') {
			$file = $this->root . '/' . $path;
			$url = $this->url . '/' . dirname($path) . '/'.SimpleFileSilo::DERIV_DIR.'/' . basename($path) . '.thumbnail.jpg' ;
			if(!file_exists(dirname($file) . '/'.SimpleFileSilo::DERIV_DIR.'/' . basename($file) . '.thumbnail.jpg')) {
				if(!$this->create_thumbnail($file)) {
					// Do something if we can't create a thumbnail, like return a default image
				}
			}
		}
		else {
			$url = $this->url . '/' . $path;
		}

		return $url;
	}

	/**
	 * Create a thumbnail in the derivative directory
	 *
	 * @param string $src_filename The source filename
	 * @param integer $max_width The maximum width of the output image
	 * @param integer $max_height The maximum height of the output image
	 * @return boolean true if the thumbnail creation succeeded
	 */
	private function create_thumbnail($src_filename, $max_width = Media::THUMBNAIL_WIDTH, $max_height = Media::THUMBNAIL_HEIGHT)
	{
		// Does derivative directory not exist?
		$thumbdir = dirname($src_filename) . '/'.SimpleFileSilo::DERIV_DIR.'';
		if(!is_dir($thumbdir)) {
			// Create the derivative driectory
			if(!mkdir($thumbdir, 0766)){
				// Couldn't make derivative directory
				return false;
			}
		}

    // Get information about the image
    list($src_width, $src_height, $type, $attr) = getimagesize( $src_filename );

    // Load the image based on filetype
    switch( $type ) {
    case IMAGETYPE_JPEG:
      $src_img = imagecreatefromjpeg( $src_filename );
      break;
    case IMAGETYPE_PNG:
      $src_img = imagecreatefrompng( $src_filename );
      break;
    case IMAGETYPE_GIF:
      $src_img = imagecreatefromgif( $src_filename );
      break;
    default:
      return false;
    }
    // Did the image fail to load?
    if ( !$src_img ) {
      return false;
    }

    // Calculate the output size based on the original's aspect ratio
    if ( $src_width / $src_height > $max_width / $max_height ) {
      $thumb_w = $max_width;
      $thumb_h = $src_height * $max_width / $src_width;
    }
    else {
      $thumb_w = $src_width * $max_height / $src_height;
      $thumb_h = $max_height;
    }

    // Create the output image and copy to source to it
    $dst_img = ImageCreateTrueColor( $thumb_w, $thumb_h );
    imagecopyresampled( $dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $src_width, $src_height );

    /* Sharpen before save?
    $sharpenMatrix = array( array(-1, -1, -1), array(-1, 16, -1), array(-1, -1, -1) );
    $divisor = 8;
    $offset = 0;
    imageconvolution( $dst_img, $sharpenMatrix, $divisor, $offset );
    //*/

		// Define the thumbnail filename
		$dst_filename = $thumbdir . '/' . basename($src_filename) . ".thumbnail.jpg";

    // Save the thumbnail as a JPEG
    imagejpeg( $dst_img, $dst_filename );

    // Clean up memory
    imagedestroy( $dst_img );
    imagedestroy( $src_img );

    return true;
	}

	/**
	 * Create a new asset instance for the specified path
	 * @param string $path The path of the new file to create
	 * @return MediaAsset The requested asset
	 **/
	public function silo_new( $path )
	{
	}

	/**
	 * Store the specified media at the specified path
	 * @param string $path The path of the file to retrieve
	 * @param MediaAsset The asset to store
	 **/
	public function silo_put( $path, $filedata )
	{
	}

	/**
	 * Delete the file at the specified path
	 * @param string $path The path of the file to retrieve
	 **/
	public function silo_delete( $path )
	{
	}

	/**
	 * Retrieve a set of highlights from this silo
	 * This would include things like recently uploaded assets, or top downloads
	 * @return array An array of MediaAssets to highlihgt from this silo
	 **/
	public function silo_highlights()
	{
	}

	/**
	 * Retrieve the permissions for the current user to access the specified path
	 * @param string $path The path to retrieve permissions for
	 * @return array An array of permissions constants (MediaSilo::PERM_READ, MediaSilo::PERM_WRITE)
	 **/
	public function silo_permissions( $path )
	{
	}


}

?>
