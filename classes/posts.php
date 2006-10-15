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
	 * Returns requested posts.
	 * THIS CLASS SHOULD CACHE QUERY RESULTS!	 
	 * @param array An associated array of parameters, or a querystring
	 * @return array An array of Post objects, one for each query result
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
	 * Creates a post and saves it
	 * @param array An associative array of post fields
	 * $return Post The post object that was created	 
	 **/	 	 	
	static function create($paramarray) 
	{
		$post = new Post($paramarray);
		$post->insert();
		return $post;
	}

}
?>
