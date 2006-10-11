<?php
/**
 * Habari Post Creation & Retrieval Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class posts {
	function retrieve() {
		global $db;
		$query = $db->get_results( "SELECT slug, title, guid, content, author, status, pubdate, updated from habari__posts ORDER BY 'pubdate' DESC" );
			if ( is_array( $query ) ) {
				return $query;
			} else {
				return array();
			}
	}
	
	function create() {
		global $db;
		// insert posts!
	}
}
?>