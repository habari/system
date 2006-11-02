<?php
/**
 * Habari Comment Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Comments extends QueryRecord
{

	static function default_fields()
	{
		return  array(
				'id' => '',
				'post_slug' => '',
				'name' => '',
				'email' => '',
				'url' => '',
				'ip' => '',
				'content' => '',
				'status' => '',
				'date' => ''
				);
	}

	/**
	* constructor __construct
	* Constructor for the Comments class
	* @param array an associative array of initial Comment field values
	**/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge( self::default_fields(), $this->fields );
		parent::__construct( $paramarray );
	}

	/* static function get
	* Returns a single comment
	* @param int a comment's ID
	* @return a Comment object
	*/
	static function get( $ID = 0 )
	{
		if ( ! $ID )
		{
			return false;
		}
		global $db;
		$comment = $db->get_row("SELECT * FROM habari__posts WHERE id = ?", array( $ID ) );
		return $comment;
	}

	/**
	 * function do_query
	 * Returns requested comments
	 * @param array An associated array of parameters, or a querystring
	 * @return array An array of Comment objects, one for each query result
	 *
	 * <code>
	 * $comments = comments::do_query( array ("author" => "skippy" ) );
	 * $comments = comments::do_query( array ("slug" => "first-post", "status" => "1", "orderby" => "date ASC" ) );
	 * </code>
	 *
	 **/	 	  
	static function do_query( $paramarray )
	{
		global $db;

		$orderby = "date ASC";
		foreach ($paramarray as $key => $value)
		{
			if ('orderby' == $key)
			{
				$orderby = $value;
				continue;
			}
			// only accept those keys that correspond to
			// table columns
			if ( ! array_key_exists ( $key, self::default_fields() ) )
			{
				continue;
			}
			$where[] = "$key = ?";
			$params[] = $value;
		}

		$sql = "SELECT * from habari__comments WHERE " . implode( ' AND ', $where ) . " ORDER BY $orderby";
		$query = $db->get_results($sql, $params);
		if ( is_array( $query ) ) {
			return $query;
		} else {
			return array();
		}
	}
	
	static function create() {
		global $db;
		// insert posts!
	}

	/**
	* function by_email
	* selects all comments from a given email address
	* @param string an email address
	* @return array an array of Comment objects written by that email address
	*/
	public function by_email($email = '')
	{
		if ( ! $email )
		{
			return array();
		}
		return self::do_query( array ( "email" => $email ) );
	}

	/**
	* function by_name
	* selects all comments from a given name
	* @param string a name
	* @return array an array of Comment objects written by the given name
	*/
	public function by_name ($name = '')
	{
		if ( ! $name )
		{
			return array();
		}
		return self::do_query( array ( "name" => $name ) );
	}

	/**
	* function by_ip
	* selects all comments from a given IP address
	* @param string an IP address
	* @return array an array of Comment objects written from the given IP
	*/
	public function by_ip ( $ip = '' )
	{
		if ( ! $ip ) 
		{
			return false;
		}
		return self::do_query( array ( "ip" => $ip ) );
	}

	/**
	* function by_url
	* select all comments from an author's URL
	* @param string a URL
	* @return array array an array of Comment objects with the same URL
	*/
	public function by_url ( $url = '' )
	{
		if ( ! $url )
		{
			return false;
		}
		return self::do_query( array( "url" => $url ) );
	}

	/**
	* function by_slug
	* select all comments for a given post slug
	* @param string a post slug
	* @return array array an array of Comment objects for the given post
	*/
	public function by_slug ( $slug = '' )
	{
		if ( ! $slug )
		{
			return false;
		}
		return self::do_query( array( "post_slug" => $slug ) );
	}

	/**
	* function by_status
	* select all comments of a given status
	* @param int a status value
	* @return array array an array of Comment objects with the same status
	*/
	public function by_status ( $status = 0 )
	{
		return self::do_query( array( "status" => $status ) );
	}

}
?>
