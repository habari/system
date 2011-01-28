<?php

/**
 * Attempt to load the class before PHP fails with an error.
 * This method is called automatically in case you are trying to use a class which hasn't been defined yet.
 *
 * We look for the undefined class in the following folders:
 * - /system/classes/*.php
 * - /user/classes/*.php
 * - /user/sites/x.y.z/classes/*.php
 *
 * @param string $class_name Class called by the user
 */
function habari_autoload( $class_name )
{
	static $files = null;

	$success = false;
	$class_file = strtolower( $class_name ) . '.php';

	if ( empty( $files ) ) {
		$files = array();
		$dirs = array( HABARI_PATH . '/system', HABARI_PATH . '/user' );

		// For each directory, save the available files in the $files array.
		foreach ( $dirs as $dir ) {
			$glob = glob( $dir . '/classes/*.php' );
			if ( $glob === false || empty( $glob ) ) continue;
			$fnames = array_map( create_function( '$a', 'return strtolower(basename($a));' ), $glob );
			$files = array_merge( $files, array_combine( $fnames, $glob ) );
		}

		// Load the Site class, a requirement to get files from a multisite directory.
		if ( isset( $files['site.php'] ) ) {
			require( $files['site.php'] );
		}

		// Verify if this Habari instance is a multisite.
		if ( ( $site_user_dir = Site::get_dir( 'user' ) ) != HABARI_PATH . '/user' ) {
			// We are dealing with a site defined in /user/sites/x.y.z
			// Add the available files in that directory in the $files array.
			$glob = glob( $site_user_dir . '/classes/*.php' );
			if ( $glob !== false && !empty( $glob ) ) {
				$fnames = array_map( create_function( '$a', 'return strtolower(basename($a));' ), $glob );
				$files = array_merge( $files, array_combine( $fnames, $glob ) );
			}
		}
	}

	// Search in the available files for the undefined class file.
	if ( isset( $files[$class_file] ) ) {
		require( $files[$class_file] );
		// If the class has a static method named __static(), execute it now, on initial load.
		if ( class_exists( $class_name, false ) && method_exists( $class_name, '__static' ) ) {
			call_user_func( array( $class_name, '__static' ) );
		}
		$success = true;
	}
}

?>
