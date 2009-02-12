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
 * MediaSilo interface
 *
 * @version $Id$
 * @copyright 2007
 */
interface MediaSilo
{
	const PERM_READ = 1;
	const PERM_WRITE = 2;

	/**
	 * Return basic information about this silo
	 *   name- The name of the silo, used as the root directory for media in this silo
	 **/
	public function silo_info();

	/**
	 * Return directory contents for the silo path
	 * @param string $path The path to retrieve the contents of
	 * @return array An array of MediaAssets describing the contents of the directory
	 **/
	public function silo_dir( $path );

	/**
	 * Get the file from the specified path
	 * @param string $path The path of the file to retrieve
	 * @param array $qualities Qualities that specify the version of the file to retrieve.
	 * @return MediaAsset The requested asset
	 **/
	public function silo_get( $path, $qualities = null );

	/**
	 * Store the specified media at the specified path
	 * @param string $path The path of the file to retrieve
	 * @param MediaAsset The asset to store
	 **/
	public function silo_put( $path, $filedata );

	/**
	 * Delete the file at the specified path
	 * @param string $path The path of the file to retrieve
	 **/
	public function silo_delete( $path );

	/**
	 * Retrieve a set of highlights from this silo
	 * This would include things like recently uploaded assets, or top downloads
	 * @return array An array of MediaAssets to highlihgt from this silo
	 **/
	public function silo_highlights();

	/**
	 * Retrieve the permissions for the current user to access the specified path
	 * @param string $path The path to retrieve permissions for
	 * @return array An array of permissions constants (MediaSilo::PERM_READ, MediaSilo::PERM_WRITE)
	 **/
	public function silo_permissions( $path );

}

?>
