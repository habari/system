<?php
/**
 * @package Habari
 *
 */

/**
* Habari Version Class
*
* Base class for managing metadata about various Habari objects
*
*/
class Version
{
	// DB and API versions are incremented by one as the DB structure or API change
	const DB_VERSION = 5116;
	const API_VERSION = 4958;

	const HABARI_MAJOR_MINOR = '0.9.2';
	const HABARI_RELEASE = '';

	/**
	 * Get the database version
	 *
	 * @return integer The revision in which the most recent database change took place
	 */
	public static function get_dbversion()
	{
		return Version::DB_VERSION;
	}

	/**
	 * Get the API version
	 *
	 * @return integer The revision in which the most recent API change took place
	 */
	public static function get_apiversion()
	{
		return Version::API_VERSION;
	}

	/**
	 * Get the Habari version
	 *
	 * @return string A version_compare()-compatible string of this version of Habari
	 * @see version_compare
	 */
	public static function get_habariversion()
	{
		return Version::HABARI_MAJOR_MINOR . Version::HABARI_RELEASE;
	}

	/**
	 * Determine whether this might possibly have a .git directory, based solely on the existence of a hyphen in the release version string.
	 *
	 * @return boolean True if this is a development version, false if not
	 */
	public static function is_devel()
	{
		return strpos( Version::HABARI_RELEASE, '-' ) !== false;
	}

	/**
	 * Store the current database version in the options table
	 */
	public static function save_dbversion()
	{
		Options::set( 'db_version', Version::DB_VERSION );
	}

	/**
	 * Determine if the database needs to be updated based on the source database version being newer than the schema last applied to the database
	 *
	 * @return boolean True if an update is needed
	 */
	public static function requires_upgrade()
	{
		if ( Options::get( 'db_version' ) < Version::DB_VERSION ) {
			return true;
		}
		return false;
	}

	/**
	 * Attempt to return the shortened git hash of any path Habari can access
	 * @static
	 *
	 * @param String $path Where to check for a .git directory
	 * @return String The first 7 chars of the revision hash
	 */
	public static function get_git_short_hash( $path = null )
	{
		$rev = '';
		$path = is_null( $path ) ? HABARI_PATH . '/system' : $path;
		$ref_file = $path . '/.git/HEAD';
		if ( file_exists( $ref_file ) ) {
			$info = file_get_contents( $ref_file );
			// If the contents of this file start with "ref: ", it means we need to look where it tells us for the hash.
			// CAVEAT: This is only really useful if the master branch is checked out
			if ( strpos( $info, 'ref: ' ) === false ) {
				$rev = substr( $info, 0, 7 );
			} else {
				preg_match( '/ref: (.*)/', $info, $match );
				$rev = substr( file_get_contents( $path . '/.git/' . $match[1] ), 0, 7 );
			}
		}
		return $rev;
	}
}

?>
