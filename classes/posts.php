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
	public static function get( $paramarray= array() )
	{
		$params= array();
		$fns= array( 'get_results', 'get_row', 'get_value' );
		$select= '';
		
		// Default fields to select, everything by default
		foreach ( Post::default_fields() as $field => $value ) {
			$select.= ( '' == $select )
				? DB::table( 'posts' ) . ".$field"
				: ', ' . DB::table( 'posts' ) . ".$field";
		}
		
		// Default parameters
		$orderby= 'pubdate DESC';
		$limit= Options::get( 'pagination' );

		// If $paramarray is a querystring, convert it to an array
		$paramarray= Utils::get_params( $paramarray );

		// Define the WHERE sets to process and OR in the final SQL statement
		if ( isset( $paramarray['where'] ) && is_array( $paramarray['where'] ) ) {
			$wheresets= $paramarray['where'];
		}
		else {
			$wheresets= array( array() );
		}

		/* Start building the WHERE clauses */

		$wheres= array();
		$joins= array();
		
		// If the request as a textual WHERE clause, skip the processing of the $wheresets since it's empty
		if ( isset( $paramarray['where'] ) && is_string( $paramarray['where'] ) ) {
			$wheres[]= $paramarray['where'];
		}
		else {
			foreach ( $wheresets as $paramset ) {
				// Safety mechanism to prevent empty queries
				$where= array();
				$paramset= array_merge( (array) $paramarray, (array) $paramset );
				// $nots= preg_grep( '%^not:(\w+)$%i', (array) $paramset );

				if ( isset( $paramset['id'] ) ) {
					if ( is_array( $paramset['id'] ) ) {
						array_walk( $paramset['id'], create_function( '$a,$b,&$c', '$c[$b]= intval($a);' ), $paramset['id'] );
						$where[]= "id IN (" . implode( ',', array_fill( 0, count( $paramset['id'] ), '?' ) ) . ")";
						$params= array_merge( $params, $paramset['id'] );
					}
					else {
						$where[]= "id= ?";
						$params[]= (int) $paramset['id'];
					}
				}
				if ( isset( $paramset['status'] ) && ( $paramset['status'] != 'any' ) && ( 0 !== $paramset['status'] )) {
					if ( is_array( $paramset['status'] ) ) {
						array_walk( $paramset['status'], create_function( '$a,$b,&$c', 'if ($a = \'any\') { $c[$b]= Post::status($a); } else { unset($c[$b]); }' ), $paramset['status'] );
						$where[]= "status IN (" . implode( ',', array_fill( 0, count( $paramset['status'] ), '?' ) ) . ")";
						$params= array_merge( $params, $paramset['status'] );
					}
					else {
						$where[]= "status= ?";
						$params[]= (int) Post::status( $paramset['status'] );
					}
				}
				if ( isset( $paramset['content_type'] ) && ( $paramset['content_type'] != 'any' ) && ( 0 !== $paramset['content_type'] ) ) {
					if ( is_array( $paramset['content_type'] ) ) {
						array_walk( $paramset['content_type'], create_function( '$a,$b,&$c', 'if ($a = \'any\') { $c[$b]= Post::type($a); } else { unset($c[$b]); }' ), $paramset['content_type'] );
						$where[]= "content_type IN (" . implode( ',', array_fill( 0, count( $paramset['content_type'] ), '?' ) ) . ")";
						$params= array_merge( $params, $paramset['content_type'] );
					}
					else {
						$where[]= "content_type= ?";
						$params[]= (int) Post::type( $paramset['content_type'] );
					}
				}
				if ( isset( $paramset['slug'] ) ) {
					if ( is_array( $paramset['slug'] ) ) {
						$where[]= "slug IN (" . implode( ',', array_fill( 0, count( $paramset['slug'] ), '?' ) ) . ")";
						$params= array_merge( $params, $paramset['slug'] );
					}
					else {
						$where[]= "slug= ?";
						$params[]= (string) $paramset['slug'];
					}
				}
				if ( isset( $paramset['user_id'] ) ) {
					if ( is_array( $paramset['user_id'] ) ) {
						array_walk( $paramset['user_id'], create_function( '$a,$b,&$c', '$c[$b]= intval($a);' ), $paramset['user_id'] );
						$where[]= "user_id IN (" . implode( ',', array_fill( 0, count( $paramset['user_id'] ), '?' ) ) . ")";
						$params= array_merge( $params, $paramset['user_id'] );
					}
					else {
						$where[]= "user_id= ?";
						$params[]= (int) $paramset['user_id'];
					}
				}
				if ( isset( $paramset['tag'] ) || isset( $paramset['tag_slug'] )) {
					$joins['tag2post_posts']= ' JOIN {tag2post} ON ' . DB::table( 'posts' ) . '.id= ' . DB::table( 'tag2post' ) . '.post_id';
					$joins['tags_tag2post']= ' JOIN {tags} ON ' . DB::table( 'tag2post' ) . '.tag_id= ' . DB::table( 'tags' ) . '.id';
					// Need tag expression parser here.
					// ^^ What does this mean? -freakerz
					if ( isset( $paramset['tag'] ) ) {
						if ( is_array( $paramset['tag'] ) ) {
							$where[]= "tag_text IN (" . implode( ',', array_fill( 0, count( $paramset['tag'] ), '?' ) ) . ")";
							$params= array_merge( $params, $paramset['tag'] );
						}
						else {
							$where[]= 'tag_text= ?';
							$params[]= (string) $paramset['tag'];
						}
					}
					if ( isset( $paramset['tag_slug'] ) ) {
						if ( is_array( $paramset['tag_slug'] ) ) {
							$where[]= "tag_slug IN (" . implode( ',', array_fill( 0, count( $paramset['tag_slug'] ), '?' ) ) . ")";
							$params= array_merge( $params, $paramset['tag_slug'] );
						}
						else {
							$where[]= 'tag_slug= ?';
							$params[]= (string) $paramset['tag_slug'];
						}
					}
				}

				if ( isset( $paramset['not:tag'] ) ) {
					$nottag= is_array( $paramset['not:tag'] ) ? array_values( $paramset['not:tag'] ) : array( $paramset['not:tag'] );

					$where[]= 'NOT EXISTS (SELECT 1
						FROM ' . DB::table( 'tag2post' ) . '
						INNER JOIN ' . DB::table( 'tags' ) . ' ON ' . DB::table( 'tags' ) . '.id = ' . DB::table( 'tag2post' ) . '.tag_id
						WHERE ' . DB::table( 'tags' ) . '.tag_slug IN (' . implode( ',', array_fill( 0, count( $nottag ), '?' ) ) . ')
						AND ' . DB::table( 'tag2post' ) . '.post_id = ' . DB::table( 'posts' ) . '.id)
					';
					$params= array_merge( $params, $nottag );
				}

				if ( isset( $paramset['criteria'] ) ) {
					preg_match_all( '/(?<=")(\\w[^"]*)(?=")|(\\w+)/', $paramset['criteria'], $matches );
					foreach ( $matches[0] as $word ) {
						$where[].= "(title LIKE CONCAT('%',?,'%') OR content LIKE CONCAT('%',?,'%'))";
						$params[]= $word;
						$params[]= $word;  // Not a typo (there are two ? in the above statement)
					}
				}
				
				if ( isset( $paramset['all:info'] ) || isset( $paramset['info'] ) ) {
					
					// merge the two possibile calls together
					$infos= array_merge( isset( $paramset['all:info'] ) ? $paramset['all:info'] : array(), isset( $paramset['info'] ) ? $paramset['info'] : array() );
					
					if ( is_array( $infos ) ) {
												
						foreach ( $infos as $info_key => $info_value ) {

							$the_ins[]= ' CONCAT( ?, \'**\', ? ) ';
							$params[]= $info_key;
							$params[]= $info_value;
							
						}
						
						$where[]= DB::table( 'posts' ) . '.id IN ( 
										SELECT post_id FROM ' . DB::table( 'postinfo' ) . '
										WHERE CONCAT(name,\'**\',value) IN ( ' . implode( ', ', $the_ins ) . ' )
										GROUP BY post_id 
										HAVING COUNT(*) = ' . count( $infos ) . ' )';
										// see that hard-coded number? sqlite wets itself if we use a bound parameter... don't change that

					}
					
				}
				
				if ( isset( $paramset['any:info'] ) ) {
					
					if ( is_array( $paramset['any:info'] ) ) {
												
						foreach ( $paramset['any:info'] as $info_key => $info_value ) {

							$the_ins[]= ' CONCAT( ?, \'**\', ? ) ';
							$params[]= $info_key;
							$params[]= $info_value;
							
						}
												
						$where[]= DB::table( 'posts' ) . '.id IN ( 
										SELECT post_id FROM ' . DB::table( 'postinfo' ) . ' 
										WHERE CONCAT( name, \'**\', value ) IN ( ' . implode( ', ', $the_ins ) . ' ) ) ';
						
						
					}
					
				}
				
				if ( isset( $paramset['not:all:info'] ) || isset( $paramset['not:info'] ) ) {
					
					// merge the two possible calls together
					$infos= array_merge( isset( $paramset['not:all:info'] ) ? $paramset['not:all:info'] : array(), isset( $paramset['not:info'] ) ? $paramset['not:info'] : array() );
					
					if ( is_array( $infos ) ) {
												
						foreach ( $infos as $info_key => $info_value ) {

							$the_ins[]= ' CONCAT( ?, \'**\', ? ) ';
							$params[]= $info_key;
							$params[]= $info_value;
							
						}
						
						$where[]= DB::table( 'posts' ) . '.id NOT IN ( 
										SELECT post_id FROM ' . DB::table( 'postinfo' ) . '
										WHERE CONCAT(name,\'**\',value) IN ( ' . implode( ', ', $the_ins ) . ' )
										GROUP BY post_id 
										HAVING COUNT(*) = ' . count( $infos ) . ' )';
										// see that hard-coded number? sqlite wets itself if we use a bound parameter... don't change that

					}
					
				}
				
				if ( isset( $paramset['not:any:info'] ) ) {
					
					if ( is_array( $paramset['not:any:info'] ) ) {
												
						foreach ( $paramset['not:any:info'] as $info_key => $info_value ) {

							$the_ins[]= ' CONCAT( ?, \'**\', ? ) ';
							$params[]= $info_key;
							$params[]= $info_value;
							
						}
												
						$where[]= DB::table( 'posts' ) . '.id NOT IN ( 
										SELECT post_id FROM ' . DB::table( 'postinfo' ) . ' 
										WHERE CONCAT( name, \'**\', value ) IN ( ' . implode( ', ', $the_ins ) . ' ) ) ';
						
						
					}
					
				}

				/**
				 * Build the statement needed to filter by pubdate:
				 * If we've got the day, then get the date;
				 * If we've got the month, but no date, get the month;
				 * If we've only got the year, get the whole year.
				 */
				if ( isset( $paramset['day'] ) && isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
					$where[]= 'pubdate BETWEEN ? AND ?';
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], $paramset['day'], $paramset['year'] ) );
					$params[]= date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'], $paramset['day'], $paramset['year'] ) );
				}
				elseif ( isset( $paramset['month'] ) && isset( $paramset['year'] ) ) {
					$where[]= 'pubdate BETWEEN ? AND ?';
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], 1, $paramset['year'] ) );
					$params[]= date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'] + 1, 0, $paramset['year'] ) );
				}
				elseif ( isset( $paramset['year'] ) ) {
					$where[]= 'pubdate BETWEEN ? AND ?';
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, 0, 1, 1, $paramset['year'] ) );
					$params[]= date( 'Y-m-d H:i:s', mktime( 0, 0, -1, 1, 1, $paramset['year'] + 1 ) );
				}

				// Concatenate the WHERE clauses
				if ( count( $where ) > 0 ) {
					$wheres[]= ' (' . implode( ' AND ', $where ) . ') ';
				}
			}
		}

		// Extract the remaining parameters which will be used onwards
		// For example: page number, fetch function, limit
		extract( $paramarray );

		// Calculate the OFFSET based on the page number
		if ( isset( $page ) && is_numeric( $page ) ) {
			$offset= ( intval( $page ) - 1 ) * intval( $limit );
		}

		/**
		 * Determine which fetch function to use:
		 * If it is specified, make sure it is valid (based on the $fns array defined at the beginning of this function);
		 * Else, use 'get_results' which will return a Posts array of Post objects.
		 */
		if ( isset( $fetch_fn ) ) {
			if ( ! in_array( $fetch_fn, $fns ) ) {
				$fetch_fn= $fns[0];
			}
		}
		else {
			$fetch_fn= $fns[0];
		}

		/**
		 * If a count is requested:
		 * Replace the current fields to select with a COUNT();
		 * Change the fetch function to 'get_value';
		 * Remove the ORDER BY since it's useless.
		 */
		if ( isset( $count ) ) {
			$select= "COUNT($count)";
			$fetch_fn= 'get_value';
			$orderby= '';
		}

		// Define the LIMIT and add the OFFSET if it exists
		if ( isset( $limit ) ) {
			$limit= " LIMIT $limit";
			if ( isset( $offset ) ) {
				$limit.= " OFFSET $offset";
			}
		}

		// Remove the LIMIT if 'nolimit' is set
		if ( isset( $nolimit ) ) {
			$limit= '';
		}

		/* All SQL parts are constructed, on to real business! */

		/**
		 * Build the final SQL statement
		 */
		$query= '
			SELECT ' . $select . '
			FROM {posts} ' . implode(' ', $joins);

		if ( count( $wheres ) > 0 ) {
			$query.= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query.= ( ( $orderby == '' ) ? '' : ' ORDER BY ' . $orderby ) . $limit;

		/**
		 * DEBUG: Uncomment the following line to display everything that happens in this function
		 */
		// Utils::debug( $paramarray, $fetch_fn, $query, $params );

		/**
		 * Execute the SQL statement using the PDO extension
		 */
		DB::set_fetch_mode( PDO::FETCH_CLASS );
		DB::set_fetch_class( 'Post' );
		$results= DB::$fetch_fn( $query, $params, 'Post' );

		/**
		 * Return the results
		 */
		if ( 'get_results' != $fetch_fn ) {
			// Since a single result was requested, return a single Post object.
			return $results;
		}
		elseif ( is_array( $results ) ) {
			// With multiple results, return a Posts array of Post objects.
			$c= __CLASS__;
			$return_value= new $c( $results );
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
	public static function by_status ( $status )
	{
		return self::get( array( 'status' => $status ) );
	}


	/**
	 * function by_slug
	 * select all post content by slug
	 * @param string a post slug
	 * @return array an array of post content
	**/
	public static function by_slug ( $slug= '' )
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
		$params= array_merge( ( array ) $this->get_param_cache, array( 'count' => '*', 'nolimit' => 1 ) );
		return Posts::get( $params );
	}

	/*
	 * static count_by_author
	 * return a count of the number of posts by the specified author
	 * @param int an author ID
	 * @param mixed a status value to filter posts by; if FALSE, then no filtering will be performed
	 * @return int the number of posts by the specified author
	**/
	public static function count_by_author( $user_id= '', $status )
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
	public static function count_by_tag( $tag= '', $status )
	{
		$params= array( 'tag' => $tag, 'count' => 'slug');
		if ( FALSE !== $status ) {
			$params['status']= $status;
		}
		return self::get( $params );
	}

	/**
	 * Reassigns the author of a specified set of posts
	 * @param mixed a user ID or name
	 * @param mixed an array of post IDs, an array of Post objects, or an instance of Posts
	 * @return bool Whether the rename operation succeeded or not
	**/
	public static function reassign( $user, $posts )
	{
		// allow plugins the opportunity to prevent reassignment
		$allow= true;
		$allow= Plugins::filter( 'posts_reassign_allow', $allow );
		if ( ! $allow ) {
			return false;
		}

		if ( ! is_int( $user ) ) {
			$u= User::get( $user );
			$user= $u->id;
		}
		// safety checks
		if ( ( $user == 0 ) || empty( $posts ) ) {
			return false;
		}
		switch( true ) {
			case is_integer( reset( $posts ) ):
				break;
			case reset( $posts ) instanceof Post:
				$ids= array();
				foreach ( $posts as $post ) {
					$ids[]= $post->id;
				}
				$posts= $ids;
				break;
			default:
				return false;
		}
		$ids= implode( ',', $posts );
		Plugins::act( 'posts_reassign_before', array( $user, $posts ) );
		$results= DB::query( "UPDATE {posts} SET user_id=? WHERE id IN ({$ids})", array( $user ) );
		Plugins::act( 'posts_reassign_after', array( $user, $posts ) );

		return $results;
	}

	/**
	 * function publish_scheduled_posts
	 *
	 * Callback function to publish scheduled posts
	 */
	public static function publish_scheduled_posts( $params ) 
	{
		$posts= DB::get_results('SELECT * FROM {posts} WHERE status = ? AND pubdate <= ? ORDER BY pubdate DESC', array( Post::status( 'scheduled' ), date( 'Y-m-d H:i:s' ) ), 'Post' );
		foreach( $posts as $post ) {
			$post->publish();
		}
	}

	/**
	 * function update_scheduled_posts_cronjob
	 *
	 * Creates or recreates the cronjob to publish
	 * scheduled posts. It is called whenever a post
	 * is updated or created
	 * 
	 */
	public static function update_scheduled_posts_cronjob()
	{
		$min_time= DB::get_value( 'SELECT MIN(pubdate) FROM {posts} WHERE status = ?', array( Post::status( 'scheduled' ) ) );

		CronTab::delete_cronjob( 'publish_scheduled_posts' );
		if( $min_time ) {
			CronTab::add_single_cron( 'publish_scheduled_posts', array( 'Posts', 'publish_scheduled_posts'),  strtotime( $min_time ), 'Next run: ' . $min_time );
		}
	}
	
	/**
	 * Returns an ascending post
	 *
	 * @params The Post from which to start
	 * @params The params by which to work out what is the next ascending post
	 * @return Post The ascending post
	 */
	public static function ascend( $post, $params= null)
	{
		$posts= null;
		$ascend= false;
		if ( !$params ) {
			$params= array( 'where' => "pubdate >= '{$post->pubdate}' AND content_type = {$post->content_type} AND status = {$post->status}", 'limit' => 2, 'orderby' => 'pubdate ASC' );
			$posts= Posts::get($params);			
		}
		elseif ( $params instanceof Posts ) {			
			$posts= $params;
		}
		else {			
			if ( !array_key_exists( 'orderby', $params ) ) {
				$params['orderby']= 'pubdate ASC';
			}
			$posts= Posts::get($params);
		}
		// find $post and return the next one.
		$index= $posts->search( $post );
		$target= $index + 1;
		if ( array_key_exists( $target, $posts ) ) {
			$ascend= $posts[$target];
		}
		return $ascend;
	}

	/**
	 * Returns a descending post
	 *
	 * @params The Post from which to start
	 * @params The params by which to work out what is the next descending post
	 * @return Post The descending post
	 */
	public static function descend( $post, $params= null)
	{
		$posts= null;
		$descend= false;
		if ( !$params ) {
			$params= array( 'where' => "pubdate <= '{$post->pubdate}' AND content_type = {$post->content_type} AND status = {$post->status}", 'limit' => 2, 'orderby' => 'pubdate DESC' );
			$posts= Posts::get($params);
		}
		elseif ( $params instanceof Posts ) {
			$posts= array_reverse($params);
		}
		else {
			if ( !array_key_exists( 'orderby', $params ) ) {
				$params['orderby']= 'pubdate DESC';
			}
			$posts= Posts::get($params);
		}
		// find $post and return the next one.
		$index= $posts->search( $post );
		$target= $index + 1;
		if ( array_key_exists( $target, $posts ) ) {
			$descend= $posts[$target];
		}
		return $descend;
	}
	
	/**
	 * Search this Posts object for the needle, returns its key if found
	 *
	 * @param Post $needle Post object to find within this Posts object
	 * @return mixed Returns the index of the needle, on failure, null is returned
	 */
	public function search( $needle )
	{
		return array_search( $needle, $this->getArrayCopy() );
	}

}
?>
