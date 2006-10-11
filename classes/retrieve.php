<?php
/**
 * Habari Content Retrieval Class
 *
 * Requires PHP5.0.4 or later
 * @package Habari
 */

class retrieve {
	function posts() {
		global $db;
		$query = $db->get_results( "SELECT slug, title, guid, content, author, status, pubdate, updated from habari__posts ORDER BY 'pubdate' DESC" );
			if ( is_array( $query ) ) {
				return $query;
			} else {
				return array();
			}
	}
	
	/*
	function comments() {
		$global $db;
		$query = $db->get_results( "SELECT ID, post_slug, content, user, URL, status, pubdate, updated from comments ORDER BY 'pubdate' ASC" );
				if ( is_array( $query ) ) {
					return $query;
				} else {
					return array();
				}
		} */
	}

?>