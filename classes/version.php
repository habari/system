<?php
/**
 * Habari Version Class
 *
 * Requires PHP 5.1 or later
 * Base class for managing metadata about various Habari objects
 * 
 * @package Habari
 */

 class Version
 {
		const DB_VERSION= 673;
		const API_VERSION= 441;

		const HABARI_VERSION= 0.1;
		
		public static function get_dbversion() 
		{
			return Version::DB_VERSION;
		}
		
		public static function get_apiversion() {
			return Version::API_VERSION;
		}
		
		public static function get_habariversion() {
			return Version::HABARI_VERSION;
		}

		public static function save_dbversion() 
		{
			Options::set('db_version', Version::DB_VERSION);
		}

		public static function requires_upgrade() 
		{
			if ( Options::get('db_version') < Version::DB_VERSION ) {
				return true;
			}
			return false;
		}
 }
?>
