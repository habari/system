<?php
/**
 * @package Habari
 *
 */

/**
 * Media access
 *
 * @version $Id$
 * @copyright 2008
 *
 * @todo Handle all error conditions using exceptions
 */
class Media
{
	static $silos = null;
	const THUMBNAIL_WIDTH = 200;
	const THUMBNAIL_HEIGHT = 100;

	/**
	 * Retrieve an array of media assets stored at a virtual path
	 *
	 * @param string $path The virtual path of the directory to retrieve
	 * @return array A list of files and directories in that path
	 */
	public static function dir( $path = '' )
	{
		if ( $path == '' ) {
			self::init_silos();
			$dirs = array();
			foreach ( self::$silos as $siloname => $silo ) {
				$info = $silo->silo_info();
				if ( isset( $info['icon'] ) ) {
					$dirs[] = new MediaAsset( $siloname, true, array(), $info['icon'] );
				}
				else {
					$dirs[] = new MediaAsset( $siloname, true, array(), null );
				}
				
			}
			return $dirs;
		}
		else {
			$silo = Media::get_silo( $path, true );
			return $silo->silo_dir( $path );
		}
	}

	/**
	 * Get the MediaAsset stored at a virtual path
	 *
	 * @param string $path The virtual path of the file to retrieve
	 * @param array $qualities Qualities of the image to return (such as 'thumbnail' or 'size')
	 * @return MediaAsset The requested asset
	 */
	public static function get( $path, $qualities = null )
	{
		$silo = Media::get_silo( $path, true );
		return $silo->silo_get( $path, $qualities );
	}

	/**
	 * Fetch an empty MediaAsset with the available metadata keys pre-allocated
	 * <code>
	 * $asset = Media::prepare('silotype/foo/bar');
	 * foreach($asset->get_info() as $key => $value) echo "$key : $value";
	 * </code>
	 *
	 * @param string $path The virtual path at which the asset will be stored
	 * @return MediaAsset An empty, intialized asset instance
	 */
	public static function prepare( $path )
	{
		$silo = Media::get_silo( $path, true );
		return $silo->silo_new( $path );
	}

	/**
	 * Store the asset at the specified virtual path
	 *
	 * @param MediaAsset $filedata The asset to store
	 * @param string $path The virtual path where the asset will be stored
	 * @return boolean true on success
	 */
	public static function put( $filedata, $path = null )
	{
		if ( !$path ) {
			$path = $filedata->path;
		}
		$silo = Media::get_silo( $path, true );
		if ( $path == '' ) {
			return false;
		}
		else {
			return $silo->silo_put( $path, $filedata );
		}
	}

	/**
	 * Delete the asset at the specified virtual path
	 *
	 * @param string $path The virtual path of the asset to delete
	 * @return boolean true on success
	 */
	public static function delete( $path )
	{
		$silo = Media::get_silo( $path, true );
		if ( $path == '' ) {
			return false;
		}
		else {
			return $silo->silo_delete( $path );
		}
	}

	/**
	 * Copy the asset using the specified from and to paths
	 *
	 * @param string $pathfrom The virtual path source
	 * @param string $pathto The virtual path destination
	 * @return boolean true on success
	 */
	public static function copy( $pathfrom, $pathto )
	{
		if ( $source = Media::get( $pathfrom ) ) {
			return Media::put( $pathto, $source );
		}
		else {
			return false;
		}
	}

	/**
	 * Move the asset using the specified from and to paths
	 * A shortcut for Media::copy() then Media::delete()
	 *
	 * @param string $pathfrom The virtual path source
	 * @param string $pathto The virtual path destination
	 * @return boolean true on success
	 */
	public static function move( $pathfrom, $pathto )
	{
		if ( Media::copy( $pathfrom, $pathto ) ) {
			return Media::delete( $pathfrom );
		}
		else {
			return false;
		}
	}

	/**
	 * Return an array of highlighted (featured) assets from all silos
	 *
	 * @param mixed $path The name of a silo or a silo instance.  If empty, all silos are returned.
	 * @return array An array of MediaAsset highlight assets
	 */
	public static function highlights( $path = null )
	{
		$highlights = array();
		if ( isset( $path ) ) {
			$silo = Media::get_silo( $path );
			return $silo->silo_highlights();
		}
		else {
			self::init_silos();
			foreach ( self::$silos as $silo ) {
				$highlights = $highlights + self::highlights( $silo );
			}
		}
		return $highlights;
	}

	/**
	 * Return the permissions available to the current user on the specified path
	 *
	 * @param mixed $path The name of a silo or a silo instance.
	 * @return array An array of permission constants (read, write, etc.)
	 */
	public static function permissions( $path )
	{
		$silo = Media::get_silo( $path, true );
		return $silo->silo_permissions( $path );
	}

	/**
	 * Return the instance of a silo
	 *
	 * @param mixed $silo A silo instance or the name of a silo
	 * @param boolean $parse_path If true, parse the siloname from the path and return the remainder path by reference
	 * @return MediaSilo The requested silo
	 */
	public static function get_silo( &$silo, $parse_path = false )
	{
		if ( $silo instanceof MediaSilo ) {
			return $silo;
		}
		$siloname = $silo;
		if ( $parse_path ) {
			$exp = explode( '/', $silo, 2 );
			if ( count( $exp ) > 1 ) {
				list( $siloname, $silo ) = $exp;
			}
			else {
				$siloname = $exp[0];
				$silo = '';
			}
		}
		self::init_silos();
		return self::$silos[$siloname];
	}

	/**
	 * Initialize the internal list of silo instances
	 */
	public static function init_silos()
	{
		if ( empty( self::$silos ) ) {
			$tempsilos = Plugins::get_by_interface( 'MediaSilo' );
			self::$silos = array();
			foreach ( $tempsilos as $eachsilo ) {
				$info = $eachsilo->silo_info();
				if ( isset( $info['name'] ) ) {
					self::$silos[$info['name']] = $eachsilo;
				}
			}
		}
	}
}

?>
