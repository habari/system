<?php
/**
 * Habari Comments Class
 *
 * @package Habari
 */

class Comments extends ArrayObject
{
	private $sort;

	/**
	 * function get
	 * Returns requested comments
	 * @param array An associated array of parameters, or a querystring
	 * @return array An array of Comment objects, one for each query result
	 *
	 * <code>
	 * $comments = comments::get( array ("author" => "skippy" ) );
	 * $comments = comments::get( array ("slug" => "first-post", "status" => "1", "orderby" => "date ASC" ) );
	 * </code>
	 *
	 **/
	public static function get( $paramarray = array() )
	{
		$params= array();
		$fns= array( 'get_results', 'get_row', 'get_value' );
		$select= '';
		// what to select -- by default, everything
		foreach ( Comment::default_fields() as $field => $value ) {
			$select.= ( '' == $select )
				? DB::table( 'comments' ) . ".$field"
				: ', ' . DB::table( 'comments' ) . ".$field";
		}
		// defaults
		$orderby= 'date DESC';
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
				$where= array('1=1');
				$paramset= array_merge((array) $paramarray, (array) $paramset);

				if ( isset( $paramset['id'] ) && ( is_numeric( $paramset['id'] ) || is_array( $paramset['id'] ) ) ) {
					if ( is_numeric( $paramset['id'] ) ) {
						$where[]= "id= ?";
						$params[]= $paramset['id'];
					}
					else if ( is_array( $paramset['id'] ) ) {
						$id_list= implode( ',', $paramset['id'] );
						// Clean up the id list - remove all non-numeric or comma information
						$id_list= preg_replace("/[^0-9,]/","",$id_list);
						// You're paranoid, ringmaster! :P
						$limit= count( $paramset['id'] );
						$where[]= 'id IN (' . addslashes($id_list) . ')';
					}
				}
				if ( isset( $paramset['status'] ) && ( Comment::status_name( $paramset['status'] ) != 'any' ) ) {
					$where[]= "status= ?";
					$params[]= Comment::status( $paramset['status'] );
				}
				if ( isset( $paramset['type'] ) && ( Comment::type_name( $paramset['type'] ) != 'any' ) ) {
					$where[]= "type= ?";
					$params[]= Comment::type( $paramset['type'] );
				}
				if ( isset( $paramset['name'] ) ) {
					$where[]= "name= ?";
					$params[]= $paramset['name'];
				}
				if ( isset( $paramset['email'] ) ) {
					$where[]= "email= ?";
					$params[]= $paramset['email'];
				}
				if ( isset( $paramset['post_id'] ) ) {
					$where[]= "post_id= ?";
					$params[]= $paramset['post_id'];
				}
				if ( isset( $paramset['ip'] ) ) {
					$where[]= "ip= ?";
					$params[]= $paramset['ip'];
				}				/* do searching */
				if ( isset( $paramset['criteria'] ) ) {
					if ( isset( $paramset['criteria_fields'] ) ) {
						// Support 'criteria_fields' => 'author,ip' rather than 'criteria_fields' => array( 'author', 'ip' )
						if ( !is_array( $paramset['criteria_fields'] ) && is_string( $paramset['criteria_fields'] ) ) {
							$paramset['criteria_fields']= explode( ',', $paramset['criteria_fields'] );
						}
					}
					else {
						$paramset['criteria_fields']= array( 'content' );
					}
					$paramset['criteria_fields']= array_unique( $paramset['criteria_fields'] );
					
					preg_match_all( '/(?<=")(\\w[^"]*)(?=")|(\\w+)/', $paramset['criteria'], $matches );
					foreach ( $matches[0] as $word ) {
						foreach ( $paramset['criteria_fields'] as $criteria_field ) {
							$where_search[] .= "($criteria_field LIKE CONCAT('%',?,'%'))";
							$params[] = $word;
						}
					}
					$where[]= '('.implode( " \nOR\n ", $where_search ).')';
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
					$where[]= 'date BETWEEN ? AND ?';
					$params[]= date('Y-m-d H:i:s', mktime( 0, 0, 0, $paramset['month'], $paramset['day'], $paramset['year'] ) );
					$params[]= date('Y-m-d H:i:s', mktime( 23, 59, 59, $paramset['month'], $paramset['day'], $paramset['year'] ) ); 
				}
				elseif ( isset( $paramset['month'] ) ) {
					$where[]= 'date BETWEEN ? AND ?';
					$params[]= date('Y-m-d', mktime( 0, 0, 0, $paramset['month'], 1, $paramset['year'] ) );
					$params[]= date('Y-m-d', mktime( 23, 59, 59, $paramset['month'] + 1, 0, $paramset['year'] ) ); 
				}
				elseif ( isset( $paramset['year'] ) ) { 
					$where[]= 'date BETWEEN ? AND ?';
					$params[]= date('Y-m-d', mktime( 0, 0, 0, 1, 1, $paramset['year'] ) );
					$params[]= date('Y-m-d', mktime( 0, 0, -1, 1, 1, $paramset['year'] + 1 ) );
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
			FROM ' . DB::table('comments') .
			' ' . $join;

		if ( count( $wheres ) > 0 ) {  
			$query.= ' WHERE ' . implode( " \nOR\n ", $wheres );
		}
		$query.= ( ($orderby == '') ? '' : ' ORDER BY ' . $orderby ) . $limit;
		//Utils::debug($paramarray, $fetch_fn, $query, $params);

		DB::set_fetch_mode(PDO::FETCH_CLASS);
		DB::set_fetch_class('Comment');
		$results= DB::$fetch_fn( $query, $params, 'Comment' );

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
	 * Deletes comments from the database
	 * @param mixed Comments to delete.  An array of or a single ID/Comment object
	**/
	public static function delete_these( $comments )
	{
		if ( ! is_array( $comments ) ) {
			$comments= array( $comments );
		}

		if(count($comments) == 0)
			return true;

		if($comments[0] instanceOf Comment)	{
			// We were passed an array of comment objects. Use them directly.
			$result= true;
			foreach($comments as $comment)
				$result&= $comment->delete();
				EventLog::log( 'Comment deleted from ' . $comment->post->title, 'info', 'comment', 'habari' );
		}
		else if(is_numeric($comments[0])) {
			// We were passed an array of ID's. Get their objects and delete them.

			// Get all of the comments objects
			$comments= self::get(array('id'=>$comments));

			$result= true;
			foreach($comments as $comment)
				$result&= $comment->delete();
				EventLog::log( 'Comment deleted from ' . $comment->post->title, 'info', 'comment', 'habari' );
		}
		else {
			// We were passed a type we could not understand.
			return false;
		}

		return $result;
	}

	/**
	 * Changes the status of comments
	 * @param mixed Comment IDs to moderate.  May be a single ID, or an array of IDs
	**/
	public static function moderate_these( $comments, $status = Comment::STATUS_UNAPPROVED )
	{
		if ( ! is_array( $comments ) ) {
			$comments = array( $comments );
		}
		if( count( $comments ) == 0 ) {
			return;
		}
		$result= true;
		foreach($comments as $commentid) {
			$result&= DB::update(DB::table('comments'), array('status' => $status), array('id' => $commentid ) );
			EventLog::log( 'Comment Moderated on ' . $comment->post->title, 'info', 'comment', 'habari' );
		}
		return $result;
	}

	/**
	 * function by_email
	 * selects all comments from a given email address
	 * @param string an email address
	 * @return array an array of Comment objects written by that email address
	**/
	public static function by_email($email = '')
	{
		if ( ! $email )
		{
			return array();
		}
		return self::get( array ( "email" => $email ) );
	}

	/**
	 * function by_name
	 * selects all comments from a given name
	 * @param string a name
	 * @return array an array of Comment objects written by the given name
	**/
	public static function by_name ($name = '')
	{
		if ( ! $name )
		{
			return array();
		}
		return self::get( array ( "name" => $name ) );
	}

	/**
	 * function by_ip
	 * selects all comments from a given IP address
	 * @param string an IP address
	 * @return array an array of Comment objects written from the given IP
	**/
	public static function by_ip ( $ip = '' )
	{
		if ( ! $ip )
		{
			return false;
		}
		return self::get( array ( "ip" => $ip ) );
	}

	/**
	 * function by_url
	 * select all comments from an author's URL
	 * @param string a URL
	 * @return array array an array of Comment objects with the same URL
	**/
	public static function by_url ( $url = '' )
	{
		if ( ! $url )
		{
			return false;
		}
		return self::get( array( "url" => $url ) );
	}

	/**
	 * Returns all comments for a supplied post ID
	 * @param post_id ID of the post
	 * @return array  an array of Comment objects for the given post
	**/
	public static function by_post_id($post_id) {
		return self::get( array( 'post_id' => $post_id, 'nolimit' => 1, 'orderby' => 'date ASC' ) );
	}

	/**
	 * function by_slug
	 * select all comments for a given post slug
	 * @param string a post slug
	 * @return array array an array of Comment objects for the given post
	**/
	public static function by_slug ( $slug = '' )
	{
		if ( ! $slug )
		{
			return false;
		}
		return self::get( array( 'post_slug' => $slug, 'nolimit' => 1, 'orderby' => 'date ASC' ) );
	}

	/**
	 * function by_status
	 * select all comments of a given status
	 * @param int a status value
	 * @return array an array of Comment objects with the same status
	**/
	public static function by_status ( $status = 0 )
	{
		return self::get( array( 'status' => $status, 'nolimit' => 1, 'orderby' => 'date ASC' ) );
	}

	/**
	 * private function sort_comments
	 * sorts all the comments in this set into several container buckets
	 * so that you can then call $comments->trackbacks() to receive an
	 * array of all trackbacks, for example
	**/
	private function sort_comments()
	{
		$type_sort = array(
			Comment::COMMENT => 'comments',
			Comment::PINGBACK => 'pingbacks',
			Comment::TRACKBACK => 'trackbacks',
		);

		foreach ( $this as $c ) {
			// first, divvy up approved and unapproved comments
			switch( $c->status ) {
				case Comment::STATUS_APPROVED:
					$this->sort['approved'][] = $c;
					$this->sort['moderated'][] = $c;
					break;
				case Comment::STATUS_UNAPPROVED:
					if($c->ip == ip2long( $_SERVER['REMOTE_ADDR'] ) ) {
						$this->sort['moderated'][] = $c;
					}
					$this->sort['unapproved'][] = $c;
					break;
				case Comment::STATUS_SPAM:
					$this->sort['spam'][] = $c;
					break;
			}

			// now sort by comment type
			$this->sort[$type_sort[$c->type]][] = $c;
		}
	}

	/**
	 * function only
	 * returns all of the comments from the current Comments object of the specified type
	 * <code>$tb = $comments->only('trackbacks')</code>
	 * @return array an array of Comment objects of the specified type
	**/
	public function only( $what = 'approved' )
	{
		if ( ! isset( $this->sort ) || count( $this->sort ) == 0 ) {
			$this->sort_comments();
		}
		if ( ! isset($this->sort[$what]) || ! is_array( $this->sort[$what] ) ) {
			$this->sort[$what] = array();
		}
		return $this->sort[$what];
	}

	/**
	 * function __get
	 * Implements custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	*/
	public function __get( $name )
	{
		switch ( $name ) {
			case 'count':
				return count( $this );
			case 'approved':
			case 'unapproved':
			case 'moderated':
			case 'comments':
			case 'pingbacks':
			case 'trackbacks':
				return new Comments( $this->only( $name ) );
		}
	}

	/**
	 * function delete
	 * Deletes all comments in this object
	 */	 	 	 	 	
	public function delete()
	{
		$result= true;
		foreach($this as $c)
			$result&= $c->delete();
		// Clear ourselves.
		$this->exchangeArray(array());
		EventLog::log( 'Comment deleted: ' . $this->id, 'info', 'comment', 'habari' );
		return $result;
	}
	
	/**
	 * static count_by_name
	 * returns the number of comments attributed to the specified name
	 * @param string a commenter's name
	 * @param mixed A comment status value, or FALSE to not filter on status (default: Comment::STATUS_APPROVED)
	 * @return int a count of the comments from the specified name
	**/
	public static function count_total( $status = Comment::STATUS_APPROVED )
	{
		$params= array( 'count' => 1, 'status' => $status );
		return self::get( $params );
	}

	/**
	 * static count_by_name
	 * returns the number of comments attributed to the specified name
	 * @param string a commenter's name
	 * @param mixed A comment status value, or FALSE to not filter on status (default: Comment::STATUS_APPROVED)
	 * @return int a count of the comments from the specified name
	**/
	public static function count_by_name( $name = '', $status = Comment::STATUS_APPROVED )
	{
		$params = array ('name' => $name, 'count' => 'name');
		if ( FALSE !== $status )
		{
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * static count_by_email
	 * returns the number of comments attributed ot the specified email
	 * @param string an email address
	 * @param mixed A comment status value, or FALSE to not filter on status (default: Comment::STATUS_APPROVED)
	 * @return int a count of the comments from the specified email
	**/
	public static function count_by_email( $email = '', $status = Comment::STATUS_APPROVED )
	{
		$params = array( 'email' => $email, 'count' => 'email');
		if ( FALSE !== $status )
		{
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * static count_by_url
	 * returns the number of comments attributed to the specified URL
	 * @param string a URL
	 * @param mixed a comment status value, or FALSE to not filter on status (default: Comment::STATUS_APPROVED)
	 * @return int a count of the comments from the specified URL
	**/
	public static function count_by_url( $url = '', $status = Comment::STATUS_APPROVED )
	{
		$params = array( 'url' => $url, 'count' => 'url');
		if ( FALSE !== $status )
		{
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/** static count_by_ip
	 * returns the number of comments from the specified IP address
	 * @param string an IP address
	 * @param mixed A comment status value, or FALSE to not filter on status (default: Comment::STATUS_APPROVED)
	 * @return int a count of the comments from the specified IP address
	**/
	public static function count_by_ip( $ip = '', $status = Comment::STATUS_APPROVED )
	{
		$params = array( 'ip' => $ip, 'count' => 'ip');
		if ( FALSE !== $status )
		{
			$params['status'] = $status;
		}
		return self::get( $params );
	}

	/**
	 * static count_by_slug
	 * returns the number of comments attached to the specified post
	 * @param string a post slug
	 * @param mixed A comment status value, or FALSE to not filter on status (default: Comment::STATUS_APPROVED)
	 * @return int a count of the comments attached to the specified post
	**/
	public static function count_by_slug( $slug = '', $status = Comment::STATUS_APPROVED )
	{
		$params = array( 'post_slug' => $slug, 'count' => 'id');
		if ( FALSE !== $status )
		{
			$params['status'] = $status;
		}
		return self::get( $params );
	}

}
?>
