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
	static private $get_param_cache; // Stores info about the last set of data fetched that was not a single value

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
	public function __get( $name )
	{
		switch( $name ) {
			case 'onepost':
				return ( count( $this ) == 1 );
		}
		
		return false;
	}
			
	/**
	 * static function get
	 * Returns a post or posts based on supplied parameters
	 * THIS CLASS SHOULD CACHE QUERY RESULTS!	 
	 * @param array An associated array of parameters, or a querystring
	 * @return array An array of Post objects, or a single post object, depending on request
	 **/	 	 	 	 	
	static function get( $paramarray = array() )
	{
		$params= array();
		$fns= array('get_results',
					'get_row',
					'get_value');
		$select= '';
		// what to select -- by default, everything
		foreach ( Post::default_fields() as $field => $value ) {
			$select.= ( '' == $select )
				? DB::table( 'posts' ) . ".$field"
				: ', ' . DB::table( 'posts' ) . ".$field";
		}
		// defaults
		//$status= Post::STATUS_PUBLISHED;  // Default (unset) is now the same as Post::STATUS_ANY
		$orderby= 'ORDER BY pubdate DESC';
		$limit= Options::get('pagination');

		// Put incoming parameters into the local scope
		$paramarray= Utils::get_params( $paramarray );
		
		// Transact on possible multiple sets of where information that is to be OR'ed
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets= $paramarray['where'];
		}
		else {
			$wheresets= array( array() );
		}

		$wheres= array();
		$join= '';
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[]= $paramarray['where'];
		}
		else {
			foreach( $wheresets as $paramset ) {
				// safety mechanism to prevent empty queries
				$where= array('1=1');
				$paramset= array_merge((array) $paramarray, (array) $paramset);

				if ( isset( $paramset['status'] ) && ( $paramset['status'] != Post::STATUS_ANY ) ) {
					$where[]= "status= ?";
					$params[]= $paramset['status'];
				}
				if ( isset( $paramset['slug'] ) ) {
					$where[]= "slug= ?";
					$params[]= $paramset['slug'];
				}
				if ( isset( $paramset['user_id'] ) ) {
					$where[]= "user_id= ?";
					$params[]= $paramset['user_id'];
				}
				if ( isset( $paramset['tag'] ) ) {
					$join .= ' JOIN ' . DB::table( 'tag2post' ) . ' ON ' . DB::table( 'posts' ) . '.id= ' . DB::table( 'tag2post' ) . '.post_id';
					// Need tag expression parser here.			
					$where[]= 'tag= ?';
					$params[]= $paramset['tag'];
				}
				
				$wheres[]= ' (' . implode( ' AND ', $where ) . ') ';
			}
		}
		
		// Get any full-query parameters
		extract( $paramarray );

		if ( isset( $page ) && is_numeric($page) ) {
			$offset= ( intval( $page ) - 1 ) * intval( $limit );
		}

		if ( isset( $fetch_fn ) ) {
			if ( ! in_array( $fetch_fn, $fns ) ) {
				$fetch_fn= $fns[0];
			}
		}
		else {
			$fetch_fn= $fns[0];
		}
		
		// is a count being request?
		if ( isset( $count ) ) {
			$select= "COUNT($count)";
			$fetch_fn= 'get_value';
			$orderby= '';
		}
		if ( isset( $limit ) ) {
			$limit= " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit.= " OFFSET $offset";
			}
		}
		if ( isset( $nolimit ) ) {
			$limit= '';
		}
		
		$query= '
			SELECT ' . $select . '
			FROM ' . DB::table('posts') .
			' ' . $join;

		if ( count( $wheres ) > 0 ) {  
			$query.= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query.= $orderby . $limit;
		//Utils::debug($fetch_fn, $query, $params);			
		DB::set_fetch_mode(PDO::FETCH_CLASS);
		DB::set_fetch_class('Post');
		$results= DB::$fetch_fn( $query, $params, 'Post' );

		if ( 'get_results' != $fetch_fn ) {
			// return the results
			return $results;
		}
		elseif ( is_array( $results ) ) {
			self::$get_param_cache= $paramarray;
			$c= __CLASS__;
			return new $c( $results );
		}
	}

	/**
	 * function by_status
	 * select all posts of a given status
	 * @param int a status value
	 * @return array an array of Comment objects with the same status
	**/
	public function by_status ( $status = 0 )
	{
		return self::get( array( "status" => $status ) );
	}
	
	
	/**
	 * function by_slug
	 * select all post content by slug
	 * @param string a post slug
	 * @return array an array of post content
	**/
	public function by_slug ( $slug = '' )
	{
		return self::get( array( "slug" => $slug ) );
	}

	/*
	 * static count_total
	 * return a count for the total number of posts
	 * @param mixed a status value to filter posts by; if FALSE, then no filtering will be performed (default: Post::STATUS_PUBLISHED)
	 * @return int the number of posts of specified type ( published or draft )
	**/
	public static function count_total( $status = Post::STATUS_PUBLISHED )
	{
		$params = array( 'count' => 1, 'status' => $status );
		return self::get( $params );
	}

	/*
	 * static count_last
	 * return a count for the number of posts last queried
	 * @return int the number of posts of specified type ( published or draft )
	**/
	public static function count_last()
	{
		$params = array_merge((array) self::$get_param_cache, array( 'count' => 'id', 'nolimit' => 1));
		return self::get( $params );
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
		$params= array( 'user_id' => $user_id, 'count' => 'id' );
		if ( FALSE !== $status ) {
			$params['status']= $status;
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
	
	/**
	 * function search
	 * Returns a Posts containing posts that match the search criteria
	 * @param string criteria	 
	 **/	 	 	
	public static function search( $criteria, $page = 1 )
	{
		preg_match_all('/(?<=")(\\w[^"]*)(?=")|(\\w+)/', $criteria, $matches);
		$words = $matches[0];
		
		$where = 'status = ?';
		$params = array(Post::STATUS_PUBLISHED);
		foreach($words as $word) {
			$where .= " AND (title LIKE CONCAT('%',?,'%') OR content LIKE CONCAT('%',?,'%'))";
			$params[] = $word;
			$params[] = $word;  // Not a typo
		}

		return self::get( 
			array(
				'where'=>$where,
				'params'=>$params,
				'page'=>$page,
			) 
		);
	}

}
?>
