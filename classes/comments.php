<?php
/**
 * Habari Comment Creation & Retrieval Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

// Needs to be sorted out once we have a schema

class Comments {

	/**
	 * function retrieve
	 * Returns requested comments
	 * @return array An array of Comment objects, one for each query result
	 **/	 	  
	static function retrieve() {
		global $db;
		$query = $db->get_results( "SELECT "stuff" from habari__comments ORDER BY 'pubdate' DESC" );
			if ( is_array( $query ) ) {
				return $query;
			} else {
				return array();
			}
	}
	
	static function create() {
		global $db;
		// insert posts!
	}
}
?>
