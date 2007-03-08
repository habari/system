<?php
/**
 * Habari Comments Class
 *
 * Requires PHP 5.0.4 or later
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
		// defaults
		$fetch_fn= 'get_results';
		$select= '*';
		$orderby= 'ORDER BY date ASC';
		$limit= '';

		// safety mechanism to prevent an empty query
		$where= array('1=1');
		$params= array();

		// loop over each element of the $paramarray
		foreach ( $paramarray as $key => $value ) {
			if ( 'orderby' == $key ) {
				$orderby= 'ORDER BY ' . $value;
				continue;
			}
			if ( 'count' == $key ) {
				// we want a count of results, rather than the contents of the results
				$select= "COUNT($value)";
				// set the db method to get_row
				$fetch_fn= 'get_value';
				$orderby= '';
				continue;
			}
			if ( 'limit' == $key ) {
				$limit= " LIMIT " . $value;
			}
			if ( 'offset' == $key ) {
				$limit.= " OFFSET $value";
			}
			// check whether we should filter by status
			// a value of FALSE means don't filter
			if ( ( 'status' == $key ) && ( FALSE === $value ) ) {
				continue;
				// if the status is not FALSE, processing will
				// continue to the next if block
			}
			// only accept those keys that correspond to
			// table columns
			if ( array_key_exists ( $key, Comment::default_fields() ) ) {
				$where[]= "$key = ?";
				$params[]= $value;
			}
		}

		$sql= "SELECT {$select} from " . DB::table('comments') . ' WHERE ' . implode( ' AND ', $where ) . " {$orderby}{$limit}";
		$query= DB::$fetch_fn( $sql, $params, 'Comment' );
		if ( 'get_value' == $fetch_fn ) {
			return $query;
		}
		elseif ( is_array( $query ) ) {
			$c = __CLASS__;
			return new $c ( $query );
		}
	}

	/**
	 * Deletes comments from the database
	 * @param mixed Comment IDs to delete.  May be a single ID, or an array of IDs
	**/
	public static function delete_these( $comments )
	{
		if ( ! is_array( $comments ) ) {
			$comments = array( 'id' => $comments );
		}
		else {
			$comments = array_flip( array_fill_keys( $comments, 'id' ) );
		}
		if ( count( $comments ) == 0 ) {
			return;
		}
		$result= true;
		foreach($comments as $commentid) {
			$result&= DB::delete(DB::table('comments'), array('id' => $commentid ) );
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
		return self::get( array( "post_id" => $post_id ) );
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
		return self::get( array( "post_slug" => $slug ) );
	}

	/**
	 * function by_status
	 * select all comments of a given status
	 * @param int a status value
	 * @return array an array of Comment objects with the same status
	**/
	public static function by_status ( $status = 0 )
	{
		return self::get( array( "status" => $status ) );
	}

	/**
	 * private function sort_comments
	 * sorts all the comments in this set into several container buckets
	 * so that you can then call $comments->trackbacks() to receive an
	 * array of all trackbacks, for example
	**/
	private function sort_comments()
	{
		foreach ( $this as $c )
		{
			// first, divvy up approved and unapproved comments
			if ( Comment::STATUS_APPROVED == $c->status )
			{
				$this->sort['approved'][] = $c;
			}
			else
			{
				$this->sort['unapproved'][] = $c;
			}

			// now sort by comment type
			if ( Comment::COMMENT == $c->type )
			{
				$this->sort['comments'][] = $c;
			}
			elseif ( Comment::PINGBACK == $c->type )
			{
				$this->sort['pingbacks'][] = $c;
			}
			elseif ( Comment::TRACKBACK == $c->type )
			{
				$this->sort['trackbacks'][] = $c;
			}
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
		if ( ! isset( $this->sort[$what] ) ) {
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
			case 'comments':
			case 'pingbacks':
			case 'trackbacks':
				return new Comments( $this->only( $name ) );
		}
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
