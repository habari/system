<?php
/**
 * Habari Posts Class
 *
 * @package Habari
 */
 

/**
 * class Posts
 * This class provides two key features.
 * 1: Posts contains static method get() that returns the
 * requested posts based on the passed criteria.  Depending on the type of
 * request, different types are returned. See the function for details 
 * 2: An instance of Posts functions as an array (by extending ArrayObject) and
 * is returned by Posts::get() as the results of a query.  This allows the 
 * result of Posts::get() to be iterated (for example, in a foreach construct)
 * and to have properties that can be accessed that describe the results
 * (for example, $posts->onepost).
 **/      
class Posts extends ArrayObject
{
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
	 * static function get
	 * Returns a post or posts based on supplied parameters
	 * THIS CLASS SHOULD CACHE QUERY RESULTS!	 
	 * @param array An associated array of parameters, or a querystring
	 * @param boolean If true, returns only the first result	 
	 * @return array An array of Post objects, or a single post object, depending on request
	 **/	 	 	 	 	
	static function get( $paramarray = array() )
	{
		global $db;
	
		$params = array();
		$fns = array('get_results',
					'get_row',
					'get_value');
		$select = '';
		// what to select -- by default, everything
		foreach ( Post::default_fields() as $field => $value )
		{
			$select .= ('' == $select) ? 'habari__posts.' . $field : ', habari__posts.' . $field;
		}
		// defaults
		$status = Post::STATUS_PUBLISHED;
		$orderby = 'pubdate DESC';
		$limit = is_numeric(Options::get('pagination')) ? Options::get('pagination') : 10;

		// Put incoming parameters into the local scope
		extract(Utils::get_params($paramarray));
		// safety mechanism to prevent empty queries
		$where = array(1);
		$join = '';
		if ( isset( $fetch_fn ) )
		{
			if ( ! in_array( $fetch_fn, $fns ) )
			{
				$fetch_fn = $fns[0];
			}
		}
		else
		{
			$fetch_fn = $fns[0];
		}
		if ( isset( $status ) ) {
			$where[] = "status = ?";
			$params[] = $status;
		}
		if ( isset( $slug ) ) {
			$where[] = "slug = ?";
			$params[] = $slug;
		}
		if ( isset( $user_id ) )
		{
			$where[] = "user_id = ?";
			$params[] = $user_id;
		}
		if ( isset( $tag ) ) {
			$join .= ' JOIN habari__tags ON habari__posts.slug = habari__tags.slug';
			// Need tag expression parser here.			
			$where[] = 'tag = ?';
			$params[] = $tag;
		}
		// is a count being request?
		if ( isset( $count ) )
		{
			$select = "COUNT($count)";
			$fetch_fn = 'get_value';
		}
		if ( isset( $limit ) )
		{
			$limit = " LIMIT $limit";
			if ( isset( $offset ) )
			{
				$limit .= " OFFSET $offset";
			}
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
			{$orderby}{$limit}";
			
		$results = DB::$fetch_fn( $query, $params, 'Post' );
	
		if ( 'get_results' != $fetch_fn )
		{
			// return the results
			return $results;
		}
		elseif ( is_array( $results ) )
		{
			$c = __CLASS__;
			return new $c( $results );
		}
	}

	/*
	 * static count_by_author
	 * return a count of the number of posts by the specified author
	 * @param int an author ID
	 * @param mixed a status value to filter posts by; if FALSE, then no filtering will be performed (default: Post::STATUS_PUBLISHED)
	 * @return int the number of posts by the specified author
	**/
	public static function count_by_author( $user_id = '', $status = Post::STATUS_PUBLISHED )
	{
		$params = array( 'user_id' => $user_id, 'count' => 'id');
		if ( FALSE !== $status )
		{
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * static count_by_tag
	 * return a count of the number of posts with the assigned tag
	 * @param string A tag
	 * @param mixed a status value to filter posts by; if FALSE, then no filtering will be performed (default: Post::STATUS_PUBLISHED)
	 * @return int the number of posts with the specified tag
	**/
	public static function count_by_tag( $tag = '', $status = Post::STATUS_PUBLISHED )
	{
		$params = array( 'tag' => $tag, 'count' => 'slug');
		if ( FALSE !== $status )
		{
			$params['status'] = $status;
		}
		return self::get( $params );
	}

}
?>
