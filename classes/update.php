<?php
/**
 * Habari Update class
 *
 * Checks for updates to Habari and its libraries
 *
 * @access public
 */
class Update extends Singleton
{
	const UPDATE_URL = 'https://beacon.habariproject.org/';
	
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
	public static function add( $name, $beaconid, $current_version )
	{
		if ( empty( $name ) || empty( $beaconid ) || empty( $current_version ) ) {
			throw new Exception( _t( 'Invalid Beacon information added' ) );
		}
		
		self::instance()->beacons[ (string) $beaconid] = array( 'name' => (string) $name, 'version' => (string) $current_version );
	}

	/**
	 * Return true if the beacon data contains updates from the server
	 *
	 * @param array $beacon the beacon data from the $beacons array
	 * @return boolean true if there are updates available for this beacon
	 */
	private static function filter_unchanged( $beacon )
	{
		return isset( $beacon['latest_version'] );
	}


	/**
	 * Perform a check of all beaconids.
	 * Notifies update_check plugin hooks when checking so that they can add their beaconids to the list.
	 * @return array An array of update beacon information for components that have updates
	 * @throws Exception
	 */
	public static function check()
	{
		
		try {
			
			// get a local version of the instance to save typing
			$instance = self::instance();
			
			// load beacons
			self::register_beacons();

			// setup the remote request
			$request = new RemoteRequest( self::UPDATE_URL, 'POST' );
			
			// add all the beacon versions as parameters
			$request->set_params(
				array_map(
					create_function( '$a', 'return $a["version"];' ),
					$instance->beacons
				)
			);
			// we're not desperate enough to wait too long
			$request->set_timeout( 5 );
			
			// execute the request
			$result = $request->execute();
			
			// grab the body of the response, which has our xml in it
			$update_data = $request->get_response_body();
			
			// i don't know why we hold the XML in a class variable, but we'll keep doing that in this rewrite
			$instance->update = new SimpleXMLElement( $update_data );
			
			foreach ( $instance->update as $beacon ) {
				
				$beacon_id = (string)$beacon['id'];
				$beacon_url = (string)$beacon['url'];
				$beacon_type = isset( $beacon['type'] ) ? (string)$beacon['type'] : 'addon';
				
				// do we have this beacon? if not, don't process it
				// even though we POST all our beacons to the update script right now, it still hands back the whole list
				if ( empty( $instance->beacons[ $beacon_id ] ) ) {
					continue;
				}
				
				// add the beacon's basic info
				$instance->beacons[ $beacon_id ]['id'] = $beacon_id;
				$instance->beacons[ $beacon_id ]['url'] = $beacon_url;
				$instance->beacons[ $beacon_id ]['type'] = $beacon_type;
				
				foreach ( $beacon->update as $update ) {

					// pick out and cast all the values from the XML
					$u = array(
						'severity' => (string)$update['severity'],
						'version' => (string)$update['version'],
						'date' => isset( $update['date'] ) ? (string)$update['date'] : '',
						'url' => isset( $update['url'] ) ? (string)$update['url'] : '',
						'text' => (string)$update,
					);
					
					
					// if the remote update info version is newer... we want all newer versions
					if ( version_compare( $u['version'], $instance->beacons[ $beacon_id ]['version'] ) > 0 ) {
						
						// if this version is more recent than all the other versions
						if ( !isset( $instance->beacons[ $beacon_id ]['latest_version'] ) || version_compare( $u['version'], $instance->beacons[ $beacon_id ]['latest_version'] ) > 0 ) {
							
							// set this as the latest version
							$instance->beacons[ $beacon_id ]['latest_version'] = $u['version'];
							
						}
						
						// add the version to the list
						$instance->beacons[ $beacon_id ]['updates'][ $u['version'] ] = $u;
						
					}
					
				}
				
			}
			
			// return an array of beacons that have updates
			return array_filter( $instance->beacons, array( 'Update', 'filter_unchanged' ) );
			
		}
		catch ( Exception $e ) {
			// catches any RemoteRequest errors or XML parsing problems, etc.
			// bubble up
			throw $e;
		}
		
	}
	
	/**
	 * Loop through all the active plugins and add their information to the list of plugins to check for updates.
	 */
	private static function add_plugins()
	{
		
		$plugins = Plugins::get_active();
		
		foreach ( $plugins as $plugin ) {
			
			// name and version are required in the XML file, make sure GUID is set
			if ( !isset( $plugin->info->guid ) ) {
				continue;
			}
			
			Update::add( $plugin->info->name, $plugin->info->guid, $plugin->info->version );
			
		}
		
	}
	
	/**
	 * Endpoint for the update-check cronjob.
	 * Loads beacons, checks for updates from hp.o, and saves any updates to the DB.
	 * 
	 * @param null $cronjob Unused. The CronJob object being executed when being run as cron.
	 * @return boolean True on successful check, false on any failure (so cron runs again).
	 */
	public static function cron( $cronjob = null )
	{
		
		// register the beacons
		self::register_beacons();
		
		// save the list of beacons we are using to check with
		Options::set( 'updates_beacons', self::instance()->beacons );
		
		try {
			// run the check
			$updates = Update::check();
			
			// save the list of updates
			Options::set( 'updates_available', $updates );
			
			EventLog::log( _t( 'Updates check CronJob completed successfully.' ), 'info', 'update', 'habari' );
			
			// return true, we succeeded
			return true;
		}
		catch ( Exception $e ) {
			// catch any exceptions generated by RemoteRequest or XML parsing
			
			EventLog::log( _t( 'Updates check CronJob failed!' ), 'err', 'update', 'habari', $e->getMessage() );
			
			// tell cron the check failed
			return false;
		}
		
	}
	
	/**
	 * Register beacons to check for updates.
	 * Includes Habari core, all active plugins, and any pluggable that implements the update_check hook.
	 */
	private static function register_beacons()
	{
		
		// if there are already beacons, don't run again
		if ( count( self::instance()->beacons ) > 0 ) {
			return;
		}
		
		Update::add( 'Habari', '7a0313be-d8e3-11db-8314-0800200c9a66', Version::get_habariversion() );
		
		// add the active theme
		self::add_theme();
				
		// add all active plugins
		self::add_plugins();
		
		Plugins::act( 'update_check' );
		
	}
	
	/**
	 * Add the currently active theme's information to the list of beacons to check for updates.
	 */
	private static function add_theme()
	{
		
		// get the active theme
		$theme = Themes::get_active_data( true );
		
		// name and version are required in the XML file, make sure GUID is set
		if ( isset( $theme['info']->guid ) ) {
			Update::add( $theme['info']->name, $theme['info']->guid, $theme['info']->version );
		}
		
	}
	
	/**
	 * Compare the current set of plugins with those we last checked for updates.
	 * This is run by AdminHandler on every page load to make sure we always have fresh data on the dashboard.
	 */
	public static function check_plugins()
	{
		
		// register the beacons
		self::register_beacons();
		
		// get the list we checked last time
		$checked_list = Options::get( 'updates_beacons' );
		
		// if the lists are different
		if ( $checked_list != self::instance()->beacons ) {
			
			// remove any stored updates, just to avoid showing stale data
			Options::delete( 'updates_available' );
			
			// schedule an update check the next time cron runs
			CronTab::add_single_cron( 'update_check_single', array( 'Update', 'cron' ), HabariDateTime::date_create()->int, _t( 'Perform a single check for plugin updates, the plugin set has changed.' ) );
			
		}
		
	}
	
	/**
	 * Return all available updates, or the updates available for a single GUID.
	 * 
	 * @param string $guid A GUID to return available updates for.
	 * @return array Array of all available updates if no GUID is specified.
	 * @return array A single GUID's updates, if GUID is specified and they are available.
	 * @return false If a single GUID is specified and there are no updates available for it.
	 */
	public static function updates_available( $guid = null )
	{
		
		$updates = Options::get( 'updates_available', array() );
		
		if ( $guid == null ) {
			return $updates;
		}
		else {
			
			if ( isset( $updates[ $guid ] ) ) {
				return $updates[ $guid ];
			}
			else {
				return false;
			}
			
		}
		
	}

}

?>
