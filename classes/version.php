<?php
/**
* Habari Version Class
*
* Base class for managing metadata about various Habari objects
*
* @package Habari
*/

class Version
{
	// DB and API versions are aligned with the SVN revision
	// number in which they last changed.
	const DB_VERSION = 1845;
	const API_VERSION = 1366;

	const HABARI_VERSION = '0.5-alpha';

	// This string contains the URL to the Habari SVN repository used for this working copy or export
	const HABARI_SVN_HEAD_URL = '$HeadURL$';
	// This string contains the SVN revision used for this working copy or export
	const HABARI_SVN_REV = '$Revision$';

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
		return Version::HABARI_VERSION;
	}

	/**
	 * Determine whether this working copy or export was created from a subversion development branch
	 *
	 * @return boolean True if this is a development version, false if not
	 */
	public static function is_devel()
	{
		return strpos(HABARI_SVN_HEAD_URL, '/trunk/') !== false && strpos(HABARI_SVN_HEAD_URL, '/branches/') !== false;
	}

	/**
	 * Store the current database version in the options table
	 */
	public static function save_dbversion()
	{
		Options::set('db_version', Version::DB_VERSION);
	}

	/**
	 * Determine if the database needs to be updated based on the source database version being newer than the schema last applied to the database
	 *
	 * @return boolean True if an update is needed
	 */
	public static function requires_upgrade()
	{
		if (Options::get('db_version') < Version::DB_VERSION){
			return true;
		}
		return false;
	}
}

?>
