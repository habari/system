<?php
/**
 * Habari Posts Class
 *
 * @package Habari
 */
 

/**
 * class Posts
 * This class provides two key features.
 * 1: Posts contains static methods ( do_query() and get() ) that return the
 * requested posts based on the passed criteria.  Depending on the type of
 * request, different types are returned.  See the respective functions for
 * more details.
 * 2: An instance of Posts functions as an array (by extending ArrayObject) and
 * is returned by Posts::get() as the results of a query.  This allows the 
 * result of Posts::get() to be iterated (for example, in a foreach construct)
 * and to have properties that can be accessed that describe the results
 * (for example, $posts->onepost).
 **/      
class Posts extends ArrayObject
{
	/**
	 * static function get
	 * Returns requested posts.
	 * @param array An associated array of parameters, or a querystring
	 * @return array An array of Post objects, one for each query result
	 **/	 	 	 	 	
	static function get( $paramarray = array() ) 
	{
		global $url;
		// Defaults
		$defaults = array (
			'orderby' => 'pubdate DESC',
			'status' => 'publish',
			'stub' => '',
		);

		$paramarray = array_merge( $url->settings, $defaults, Utils::get_params( $paramarray ) ); 

		$c = __CLASS__;
		return new $c( self::do_query( $paramarray, false ) );
	}
	
	
	/**
	 * function __get
	 * Returns properties of a Posts object.
	 * This is the function that returns information about the set of posts that
	 * was requested.  This function should offer property names that are identical
	 * to properties of instances of the URL class.  A call to Posts::get() 
	 * without parameters should return mostly the same property values as the
	 * global $url object for the request.  The difference would occur when
	 * the data returned doesn't necessarily match the request, such as when
	 * several posts are requested, but only one is available to return.
	 * @param string The name of the property to return.
	 **/	 	  	 	
	public function __get($name)
	{
		switch($name) {
		case 'onepost':
			return (count( $this ) == 1);
		}
		return false;
	}
			
	/**
	 * static function do_query
	 * Returns a post or posts based on supplied parameters
	 * THIS CLASS SHOULD CACHE QUERY RESULTS!	 
	 * @param array An associated array of parameters, or a querystring
	 * @param boolean If true, returns only the first result	 
	 * @return array An array of Post objects, or a single post object, depending on request
	 **/	 	 	 	 	
	static function do_query($paramarray, $one_row_only)
	{
		global $db;
	
		$params = array();
		$selects = array(
			'habari__posts' => array (
				'id',
				'slug',
				'title',
				'guid',
				'content',
				'author',
				'status',
				'pubdate',
				'updated',
			),
		);
	
		// Put incoming parameters into the local scope
		extract(Utils::get_params($paramarray));
		$where = array(1);
		$join = '';
		if ( isset( $status ) ) {
			$where[] = "status = ?";
			$params[] = $status;
		}
		if ( isset( $slug ) ) {
			$where[] = "slug = ?";
			$params[] = $slug;
		}
		if ( isset( $tag ) ) {
			$join .= ' JOIN habari__tags ON habari__posts.slug = habari__tags.slug';
			// Need tag expression parser here.			
			$where[] = 'tag = ?';
			$params[] = $tag;
		}
		
	 	$select = '';	
		foreach($selects as $table=>$fields) {
			$select .= "{$table}." . implode( ", {$table}.", $fields );
		}
		
		$query = "
		SELECT 
		" . $select . "
		FROM 
			habari__posts 
		" . $join . "
		WHERE 
			" . implode( ' AND ', $where ) . "
		ORDER BY 
			{$orderby}";
			
		$fetch_fn = ($one_row_only) ? 'get_row' : 'get_results';
		$results = $db->$fetch_fn( $query, $params, 'Post' );

		if ( is_array( $results ) || $one_row_only) {
			return $results;
		}
		else {
			return false;
		}
	}

}
?>
