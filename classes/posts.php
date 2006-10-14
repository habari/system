<?php
/**
 * Habari Post Creation & Retrieval Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Posts // Should extend something? 
{

	/**
	 * static function retrieve
	 * Returns a Posts object using a specific query.
	 * THIS CLASS SHOULD CACHE QUERY RESULTS!	 
	 * @param array An associated array of parameters, or a querystring
	 * @return Posts A Posts object with the results of the query.
	 **/	 	 	 	 	
	static function retrieve($paramarray = array()) 
	{
		global $db;
	
		// Defaults
		$orderby = 'pubdate DESC';
		$status = "status = 'publish'";
	
		// Overwrite defaults with incoming parameter array/querystring
		extract(Utils::get_params($paramarray));
		
		$where = array(1);
		$where[] = $status;
		
		$query = "
		SELECT 
			slug, title, guid, content, author, status, pubdate, updated 
		FROM 
			habari__posts 
		WHERE 
			" . implode( ' AND ', $where ) . "
		ORDER BY 
			{$orderby}";
		
		$results = $db->get_results( $query, array(), 'Post' );
		if ( is_array( $results ) ) {
			return $results;
		}
		else {
			return false;
		}
	}
	
	/**
	 * static function create
	 * Creates a post
	 **/	 	 	
	static function create() 
	{
		global $db;
		// do stuff
	}

}
?>