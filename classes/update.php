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

define('UPDATE_URL', 'http://www.habariproject.org/beacon/');

/**
 * Habari Update class
 *
 * Checks for updates to Habari and its libraries
 *
 * @access public
 */
class Update extends Singleton
{
	private $beacons = array();
	private $update; // SimpleXMLElement

	/**
	 * Enables singleton working properly
	 *
	 * @see singleton.php
	 */
	protected static function instance()
	{
		return self::getInstanceOf( get_class() );
	}

	/**
	 * Add a beaconid to the list of beaconids to version-check.
	 *
	 * @param string $name the name of the component that will be checked
	 * @param string $beaconid the id of the beacon to check
	 * @param string $current_version the current version of the resource represented by this beaconid
	 */
	public static function add($name, $beaconid, $current_version)
	{
		self::instance()->beacons[$beaconid] = array('name'=>$name, 'version'=>$current_version);
	}

	/**
	 * Return true if the beacon data contains updates from the server
	 *
	 * @param array $beacon the beacon data from the $beacons array
	 * @return boolean true if there are updates available for this beacon
	 */
	private static function filter_unchanged($beacon)
	{
		return isset($beacon['latest_version']);
	}


	/**
	 * Perform a check of all beaconids.
	 * Notifies update_check plugin hooks when checking so that they can add their beaconids to the list.
	 * @return array An array of update beacon information for components that have updates
	 */
	public static function check()
	{
		try {
			$instance = self::instance();
			if(count($instance->beacons) == 0) {
				Update::add('Habari', '7a0313be-d8e3-11db-8314-0800200c9a66', Version::get_habariversion());
				Plugins::act('update_check');
			}

			$request = new RemoteRequest(UPDATE_URL, 'POST');
			$request->set_params(
				array_map(
					create_function('$a', 'return $a["version"];'),
					$instance->beacons
				)
			);
			$request->set_timeout( 10 );
			$result = $request->execute();
			if ( Error::is_error( $result ) ) {
				throw $result;
			}
			$updatedata = $request->get_response_body();
			if ( Error::is_error( $updatedata ) ) {
				throw $updatedata;
			}
			$instance->update = new SimpleXMLElement($updatedata);
			foreach($instance->update as $beacon) {
				$beaconid = (string)$beacon['id'];
				foreach($beacon->update as $update) {
					// Do we have this beacon?  If not, don't process it.
					if(empty($instance->beacons[$beaconid])) {
						continue;
					}
					// If the remote update info version is newer...
					if( version_compare($update['version'], $instance->beacons[$beaconid]['version']) > 0 ) {
						// If this version is more recent than all other newer versions...
						if(
							empty($instance->beacons[$beaconid]['latest_version']) ||
							version_compare
							(
								(string)$update['version'],
								$instance->beacons[$beaconid]['latest_version']
							) > 0
						)
						{
							$instance->beacons[$beaconid]['latest_version'] = (string)$update['version'];
						}
						if(isset($instance->beacons[$beaconid]['severity'])) {
							$instance->beacons[$beaconid]['severity'][] = (string)$update['severity'];
							array_unique($instance->beacons[$beaconid]['severity']);
						}
						else {
							$instance->beacons[$beaconid]['severity'] = array((string)$update['severity']);
						}
						$instance->beacons[$beaconid]['url'] = (string)$beacon['url'];
						$instance->beacons[$beaconid]['changes'][(string)$update['version']] = (string)$update;
					}
				}
			}
			return array_filter($instance->beacons, array('Update', 'filter_unchanged'));
		} catch (Exception $e) {
			return $e;
		}
	}

}

?>
