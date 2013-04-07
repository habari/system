<?php

namespace Habari;

/**
 * Attempt to load the class before PHP fails with an error.
 * This method is called automatically in case you are trying to use a class which hasn't been defined yet.
 *
 * We look for the undefined class in the following folders:
 * - /system/classes/*.php
 * - /system/handlers/*.php
 * - /user/classes/*.php
 * - /user/handlers/*.php
 * - /user/sites/x.y.z/classes/*.php
 * - /user/sites/x.y.z/handlers/*.php
 *
 * @param string $class_name Class called by the user
 */
class Autoload
{
	static $files = null;

	/**
	 * Queue directories for class autoloading
	 * @param Array|string $dirs An array of directories or a single directory to autoload classes from
	 * @return array The full list of files that could be used for autoloading
	 */
	public static function queue_dirs($dirs) {
		static $loaded_dirs = array();
		$files = array();
		if ( !is_array( $dirs ) && !$dirs instanceof \Traversable ) {
			$dirs = array( $dirs );
		}

		$lower_basename = function($string) { return strtolower(basename($string)); };

		// For each directory, save the available files in the $files array.
		foreach ( $dirs as $dir ) {
			if(!in_array($dir, $loaded_dirs)) {
				$glob = glob( $dir . '/*.php' );
				if ( $glob === false || empty( $glob ) ) continue;
				$fnames = array_map( $lower_basename, $glob );
				$files = array_merge( $files, array_combine( $fnames, $glob ) );
				$loaded_dirs[] = $dir;
			}
		}

		if(is_array(self::$files)) {
			self::$files = array_merge(self::$files, $files);
		}
		else {
			self::$files = $files;
		}
		return self::$files;
	}

	/**
	 * SPL Autoload function, includes a file to meet requirement of loading a class by name
	 * @param string $class_name The name of a class, including (if present) a namespace
	 * @return bool True if this function successfully autoloads the class in question
	 */
	public static function habari_autoload( $class_name )
	{
		$success = false;
		$full_class_name = $class_name;
		if(!preg_match('#^\\\\?Habari\\\\#', $class_name)) {
			return false;
		}

		$class_name = preg_replace('#^\\\\?Habari\\\\#', '', $class_name);
		$class_file = strtolower( $class_name ) . '.php';

		if ( empty( self::$files ) ) {
			$dirs = array(
				HABARI_PATH . '/system/classes',
				HABARI_PATH . '/system/controls',
				HABARI_PATH . '/system/handlers',
				HABARI_PATH . '/user/classes',
				HABARI_PATH . '/user/controls',
				HABARI_PATH . '/user/handlers',
			);

			// Queue these directories to find the Site class
			self::queue_dirs($dirs);

			// Load the Site class, a requirement to get files from a multisite directory.
			if ( isset( self::$files['site.php'] ) ) {
				require( self::$files['site.php'] );
				unset(self::$files['site.php']);
			}

			// Verify if this Habari instance is a multisite.
			if ( ( $site_user_dir = Site::get_dir( 'user' ) ) != HABARI_PATH . '/user' ) {
				// We are dealing with a site defined in /user/sites/x.y.z
				// Add those directories to the end of the $dirs array so they can override previous entries
				$dirs[] = $site_user_dir . '/classes';
				$dirs[] = $site_user_dir . '/controls';
				$dirs[] = $site_user_dir . '/handlers';
			}

			self::queue_dirs($dirs);
		}

		// Search in the available files for the undefined class file.
		if ( isset( self::$files[$class_file] ) ) {
			require( self::$files[$class_file] );
			unset(self::$files[$class_file]);  // Remove the file from the list to expose duplicate class names // @todo remove this line
			// If the class has a static method named __static(), execute it now, on initial load.
			if ( class_exists( $full_class_name, false ) && method_exists( $full_class_name, '__static' ) ) {
				call_user_func( array( $full_class_name, '__static' ) );
			}
			$success = true;
		}

		return $success;
	}
}
?>