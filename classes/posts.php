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
	protected $get_param_cache; // Stores info about the last set of data fetched that was not a single value

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
	 * Returns a post or posts based on supplied parameters.
	 * <b>THIS CLASS SHOULD CACHE QUERY RESULTS!</b>
	 *
	 * @param array $paramarry An associated array of parameters, or a querystring
	 * @return array An array of Post objects, or a single post object, depending on request
	 **/
	public static function get( $paramarray = array() )
	{
		$params= array();
		$fns= array( 'get_results', 'get_row', 'get_value' );
		$select= '';
		// what to select -- by default, everything
		foreach ( Post::default_fields() as $field => $value ) {
			$select.= ( '' == $select )
				? DB::table( 'posts' ) . ".$field"
				: ', ' . DB::table( 'posts' ) . ".$field";
		}
		// defaults
		$orderby= 'pubdate DESC';
		$limit= Options::get( 'pagination' );

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
				$where= array();
				$paramset= array_merge((array) $paramarray, (array) $paramset);

				if ( isset( $paramset['id'] ) && is_numeric( $paramset['id'] ) ) {
					$where[]= "id= ?";
					$params[]= $paramset['id'];
				}
				if ( isset( $paramset['status'] ) && ( Post::status_name( $paramset['status'] ) != 'any' ) ) {
					$where[]= "status= ?";
					$params[]= Post::status( $paramset['status'] );
				}
				if ( isset( $paramset['content_type'] ) && ( Post::type_name( $paramset['content_type'] ) != 'any' ) ) {
					$where[]= "content_type= ?";
					$params[]= Post::type( $paramset['content_type'] );
				}
				if ( isset( $paramset['slug'] ) ) {
	        if ( is_array( $paramset['slug'] ) ) {
					  $where[]= "slug IN (" . implode( ',', array_fill( 0, count( $paramset['slug'] ), '?' ) ) . ")";
					  $params = array_merge($params, $paramset['slug']);
	        } else {
					  $where[]= "slug= ?";
					  $params[]= $paramset['slug'];
				  }
				}
				if ( isset( $paramset['user_id'] ) ) {
					$where[]= "user_id= ?";
					$params[]= $paramset['user_id'];
				}
				if ( isset( $paramset['tag'] ) || isset( $paramset['tag_slug'] )) {
					$join .= ' JOIN ' . DB::table( 'tag2post' ) . ' ON ' . DB::table( 'posts' ) . '.id= ' . DB::table( 'tag2post' ) . '.post_id';
					$join .= ' JOIN ' . DB::table( 'tags' ) . ' ON ' . DB::table( 'tag2post' ) . '.tag_id= ' . DB::table( 'tags' ) . '.id';
					// Need tag expression parser here.
					if ( isset( $paramset['tag'] ) ) {
		        if ( is_array( $paramset['tag'] ) ) {
						  $where[]= "tag_text IN (" . implode( ',', array_fill( 0, count( $paramset['tag'] ), '?' ) ) . ")";
						  $params = array_merge($params, $paramset['tag']);
		        }
		        else {
							$where[]= 'tag_text= ?';
							$params[]= $paramset['tag'];
						}
					}
					if ( isset( $paramset['tag_slug'] ) ) {
		        if ( is_array( $paramset['tag_slug'] ) ) {
						  $where[]= "tag_slug IN (" . implode( ',', array_fill( 0, count( $paramset['tag_slug'] ), '?' ) ) . ")";
						  $params = array_merge($params, $paramset['tag_slug']);
		        }
		        else {
							$where[]= 'tag_slug= ?';
							$params[]= $paramset['tag_slug'];
						}
					}
				}
				if ( isset( $paramset['not:tag'] ) ) {
					$nottag = is_array($paramset['not:tag']) ? array_values($paramset['not:tag']) : array($paramset['not:tag']);

					$where[]= 'not exists (select 1
						FROM ' . DB::table( 'tag2post' ) . '
						INNER JOIN ' . DB::table( 'tags' ) . ' on ' . DB::table( 'tags' ) . '.id = ' . DB::table( 'tag2post' ) . '.tag_id
						WHERE ' . DB::table( 'tags' ) . '.tag_slug IN (' . implode( ',', array_fill( 0, count( $nottag ), '?' ) ) . ')
						AND ' . DB::table( 'tag2post' ) . '.post_id = ' . DB::table( 'posts' ) . '.id)
					';
					$params = array_merge($params, $nottag);
				}

				/* do searching */
				if ( isset( $paramset['criteria'] ) ) {
					preg_match_all( '/(?<=")(\\w[^"]*)(?=")|(\\w+)/', $paramset['criteria'], $matches );
					foreach ( $matches[0] as $word ) {
						$where[] .= "(title LIKE CONCAT('%',?,'%') OR content LIKE CONCAT('%',?,'%'))";
						$params[] = $word;
						$params[] = $word;  // Not a typo
					}
				}

				/*
				 * Build the pubdate
				 * If we've got the day, then get the date.
				 * If we've got the month, but no date, get the month.
				 * If we've only got the year, get the whole year.
				 * @todo Ensure that we've actually got all the needed parts when we query on them
				 * @todo Ensure that the value passed in is valid to insert into a SQL date (ie '04' and not '4')
				 */
				if ( isset( $paramset['day'] ) ) {
					/* Got the full date */
					$where[]= 'pubdate BETWEEN ? AND ?';
					$params[]= date('Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], $paramset['day'], $paramset['year'] ) );
					$params[]= date('Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'], $paramset['day'], $paramset['year'] ) );
				}
				elseif ( isset( $paramset['month'] ) ) {
					$where[]= 'pubdate BETWEEN ? AND ?';
					$params[]= date('Y-m-d', mktime( 0, 0, 0, $paramset['month'], 1, $paramset['year'] ) );
					$params[]= date('Y-m-d', mktime( 23, 59, 59, $paramset['month'] + 1, 0, $paramset['year'] ) );
				}
				elseif ( isset( $paramset['year'] ) ) {
					$where[]= 'pubdate BETWEEN ? AND ?';
					$params[]= date('Y-m-d', mktime( 0, 0, 0, 1, 1, $paramset['year'] ) );
					$params[]= date('Y-m-d', mktime( 0, 0, -1, 1, 1, $paramset['year'] + 1 ) );
				}

				if(count($where) > 0) {
					$wheres[]= ' (' . implode( ' AND ', $where ) . ') ';
				}
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
		$query.= ( ($orderby == '') ? '' : ' ORDER BY ' . $orderby ) . $limit;
		//Utils::debug($paramarray, $fetch_fn, $query, $params);

		DB::set_fetch_mode(PDO::FETCH_CLASS);
		DB::set_fetch_class('Post');
		$results= DB::$fetch_fn( $query, $params, 'Post' );

		if ( 'get_results' != $fetch_fn ) {
			// return the results
			return $results;
		}
		elseif ( is_array( $results ) ) {
			$c= __CLASS__;
			$return_value = new $c( $results );
			$return_value->get_param_cache= $paramarray;
			return $return_value;
		}
	}

	/**
	 * function by_status
	 * select all posts of a given status
	 * @param int a status value
	 * @return array an array of Comment objects with the same status
	**/
	public function by_status ( $status )
	{
		return self::get( array( 'status' => $status ) );
	}


	/**
	 * function by_slug
	 * select all post content by slug
	 * @param string a post slug
	 * @return array an array of post content
	**/
	public function by_slug ( $slug = '' )
	{
		return self::get( array( 'slug' => $slug ) );
	}

	/*
	 * static count_total
	 * return a count for the total number of posts
	 * @param mixed a status value to filter posts by; if FALSE, then no filtering will be performed
	 * @return int the number of posts of specified type ( published or draft )
	**/
	public static function count_total( $status )
	{
		$params= array( 'count' => 1, 'status' => $status );
		return self::get( $params );
	}

	/*
	 * return a count for the number of posts last queried
	 * @return int the number of posts of specified type ( published or draft )
	**/
	public function count_all()
	{
		$params= array_merge( (array) $this->get_param_cache, array( 'count' => '*', 'nolimit' => 1 ) );
		return Posts::get( $params );
	}

	/*
	 * static count_by_author
	 * return a count of the number of posts by the specified author
	 * @param int an author ID
	 * @param mixed a status value to filter posts by; if FALSE, then no filtering will be performed
	 * @return int the number of posts by the specified author
	**/
	public static function count_by_author( $user_id = '', $status )
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
	 * @param mixed a status value to filter posts by; if FALSE, then no filtering will be performed
	 * @return int the number of posts with the specified tag
	**/
	public static function count_by_tag( $tag = '', $status )
	{
		$params= array( 'tag' => $tag, 'count' => 'slug');
		if ( FALSE !== $status ) {
			$params['status'] = $status;
		}
		return self::get( $params );
	}

}
?>
