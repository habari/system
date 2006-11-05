<?php
/**
 * Habari Comments Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Comments extends ArrayObject
{

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
	static function get( $paramarray = array() )
	{
		global $db;

		$orderby = "date ASC";
		$where = array(1);
		foreach ($paramarray as $key => $value)
		{
			if ('orderby' == $key)
			{
				$orderby = $value;
				continue;
			}
			// only accept those keys that correspond to
			// table columns
			if ( array_key_exists ( $key, Comment::default_fields() ) )
			{
				$where[] = "$key = ?";
				$params[] = $value;
			}
		}

		$sql = "SELECT * from habari__comments WHERE " . implode( ' AND ', $where ) . " ORDER BY $orderby";
		$query = $db->get_results( $sql, $params, 'Comment' );
		if ( is_array( $query ) ) {
			$c = __CLASS__;
			return new $c ( $query );
		} else {
			return array();
		}
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
		return self::get( array ( "email" => $email ) );
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
		return self::get( array ( "name" => $name ) );
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
		return self::get( array ( "ip" => $ip ) );
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
		return self::get( array( "url" => $url ) );
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
		return self::get( array( "post_slug" => $slug ) );
	}

	/**
	* function by_status
	* select all comments of a given status
	* @param int a status value
	* @return array an array of Comment objects with the same status
	**/
	public function by_status ( $status = 0 )
	{
		return self::get( array( "status" => $status ) );
	}

	/**
	* function trackbacks
	* returns all of the comments from the current Comments object that are of type "trackback"
	* @ return array an array of Comment objects that are trackbacks
	**/
	public function trackbacks()
	{
	}

	/**
	 * function __get
	 * Implements custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value	 
	 **/	 	 
	public function __get($name)
	{
		switch($name) {
		case 'count':
			return count($this);
		}
	}

}
?>
