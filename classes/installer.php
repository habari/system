<?php
class Installer {
	
	public static function load_schema_install() {
		$pdo_drivers = PDO::getAvailableDrivers();
		if ( ! empty( $pdo_drivers ) ) {
			$pdo_drivers = array_combine( $pdo_drivers, $pdo_drivers );
			// Include only those drivers that we include database support for
			$pdo_schemas = array_map( 'basename', Utils::glob( HABARI_PATH . '/system/schema/*' ) );
			$pdo_schemas = array_combine( $pdo_schemas, $pdo_schemas );
			
			$pdo_drivers = array_intersect_key(
				$pdo_drivers,
				$pdo_schemas
			);
			$pdo_missing_drivers = array_diff(
				$pdo_schemas,
				$pdo_drivers
			);
		
			// Load available schemas' install script
			$install_files = array();
			foreach ($pdo_drivers as $pdo_driver) {
				$install_file = HABARI_PATH . '/system/schema/' . $pdo_driver . '/install/' . $pdo_driver . 'install.php';
				$install_files[] = $install_file;
				if (file_exists($install_file)) {
					require_once($install_file);
				}
			}
			
			foreach ($install_files as $install_file) {
				Plugins::load($install_file);
			}
		}
	}

}
?>