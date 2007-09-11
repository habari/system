<?php

/**
 * Habari CommentRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 *
 * Includes an instance of the CommentInfo class; for holding inforecords about the comment
 * If the Comment object describes an existing user; use the internal info object to get, set, unset and test for existence (isset) of
 * info records
 * <code>
 * $this->info = new CommentInfo ( 1 );  // Info records of comment with id = 1
 * $this->info->browser_ua= "Netscape 2.0"; // set info record with name "browser_ua" to value "Netscape 2.0"
 * $info_value= $this->info->browser_ua; // get value of info record with name "browser_ua" into variable $info_value
 * if ( isset ($this->info->browser_ua) )  // test for existence of "browser_ua"
 * unset ( $this->info->browser_ua ); // delete "browser_ua" info record
 * </code>
 *
 */
class Comment extends QueryRecord
{

	// our definitions for comment types and statuses
	const STATUS_UNAPPROVED= 0;
	const STATUS_APPROVED= 1;
	const STATUS_SPAM= 2;
	const STATUS_DELETED= 3;

	const COMMENT= 0;
	const PINGBACK= 1;
	const TRACKBACK= 2;

	private $post_object = null;

	private $info= null;
	/**
	* static function default_fields
	* Returns the defined database columns for a comment
	**/
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'post_id' => 0,
			'name' => '',
			'email' => '',
			'url' => '',
			'ip' => '',
			'content' => '',
			'status' => self::STATUS_UNAPPROVED,
			'date' => '',
			'type' => self::COMMENT
		);
	}

	/**
	 * constructor __construct
	 * Constructor for the Post class.
	 * @param array an associative array of initial Post field values.
	 **/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge( self::default_fields(), $this->fields );
		parent::__construct( $paramarray );
		$this->exclude_fields('id');
		$this->info= new CommentInfo ( $this->fields['id'] );
		 /* $this->fields['id'] could be null in case of a new comment. If so, the info object is _not_ safe to use till after set_key has been called. Info records can be set immediately in any other case. */

	}

	/**
	 * static function get
	 * Returns a single comment, by ID
	 *
	 * <code>
	 * $post = Post::get( 10 );
	 * </code>
	 *
	 * @param int An ID
	 * @return array A single Comment object
	 **/
	static function get( $ID = 0 )
	{
		if ( ! $ID ) {
			return false;
		}
		return DB::get_row( 'SELECT * FROM ' . DB::table('comments') . ' WHERE id = ?', array( $ID ), 'Comment' );
	}

	/**
	 * static function create
	 * Creates a comment and saves it
	 * @param array An associative array of comment fields
	 * $return Comment The comment object that was created
	 **/
	static function create($paramarray)
	{
		$comment = new Comment($paramarray);
		$comment->insert();
		return $comment;
	}

	/**
	 * function insert
	 * Saves a new comment to the posts table
	 */
	public function insert()
	{
		$allow= true;
		$allow= Plugins::filter('comment_insert_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('comment_insert_before', $this);
		// Invoke plugins for all fields, since they're all "chnaged" when inserted
		foreach ( $this->fields as $fieldname => $value ) {
			Plugins::act('comment_update_' . $fieldname, $this, $this->$fieldname, $value );
		}
		$result = parent::insert( DB::table('comments') );
		$this->newfields['id'] = DB::last_insert_id(); // Make sure the id is set in the comment object to match the row id
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->info->commit( $this->fields['id'] );
		Plugins::act('comment_insert_after', $this);
		return $result;
	}

	/**
	 * function update
	 * Updates an existing comment in the posts table
	 */
	public function update()
	{
		$allow= true;
		$allow= Plugins::filter('comment_update_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('comment_update_before', $this);
		// invoke plugins for all fields which have been updated
		foreach ($this->newfields as $fieldname => $value ) {
			Plugins::act('comment_update_' . $fieldname, $this, $this->fields[$fieldname], $value);
		}
		$result = parent::update( DB::table('comments'), array('id'=>$this->id) );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->info->commit();
		Plugins::act('comment_update_after', $this);
		return $result;
	}

	/**
	 * function delete
	 * Deletes this comment
	 */
	public function delete()
	{
		$allow= true;
		$allow= Plugins::filter('comment_delete_allow', $allow, $this);
		if ( ! $allow ) { 
			return;
		}
		Plugins::act('comment_delete_before', $this);
		// Delete all info records associated with this comment
		if ( isset( $this->info ) ) {
			$this->info->delete_all();
		}
		return parent::delete( DB::table('comments'), array('id'=>$this->id) );
		Plugins::act('comment_delete_after', $this);
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __get( $name )
	{
		$fieldnames= array_merge( array_keys( $this->fields ), array('post', 'info' ) );
		if( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			preg_match('/^(.*)_([^_]+)$/', $name, $matches);
			list( $junk, $name, $filter ) = $matches;
		}
		else {
			$filter = false;
		}
		
		if ( $name == 'name' && parent::__get( $name ) == '' ) {
			return _t('Anonymous');
		}
		switch($name)
		{
			case 'post':
				$out = $this->get_post();
				break;
			case 'info':
				$out = $this->get_info();
				break;
			default:
				$out = parent::__get( $name );
				break;
		}
		//$out = parent::__get( $name );
		$out = Plugins::filter( "comment_{$name}", $out );
		if( $filter ) {
			$out = Plugins::filter( "comment_{$name}_{$filter}", $out );
		}
		return $out;
	}

	/**
	 * function __set
	 * Overrides QueryRecord __set to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __set( $name, $value )
	{
		switch($name) {
		case 'status':
			return $this->setstatus($value);
		case 'post':
			if ( is_int( $value ) )
			{
				// a post ID was passed
				$p = Post::get(array('id'=>$value));
				$this->post_id = $p->id;
				$this->post_object = $p;
			}
			elseif ( is_string( $value ) )
			{
				// a post Slug was passed
				$p = Post::get(array('slug'=>$value));
				$this->post_id = $p->id;
				$this->post_object = $p;
			}
			elseif ( is_object ( $value ) )
			{
				// a Post object was passed, so just use it directly
				$this->post_id = $p->id;
				$this->post_object = $value;
			}
			return $value;
		}
		return parent::__set( $name, $value );
	}

	/**
	 * private function get_post()
	 * returns a Post object for the post of this comment
	 * @param bool Whether to use the cached version or not.  Default to true
	 * @return Post a Post object for the post of the current comment
	**/
	private function get_post( $use_cache = TRUE )
	{
		if ( ! isset( $this->post_object ) || ( ! $use_cache)  )
		{
			$this->post_object = Posts::get( array('id' => $this->post_id, 'fetch_fn' => 'get_row') );
		}
		return $this->post_object;
	}

	/**
	 * function get_info
	 * Gets the info object for this comment, which contains data from the commentinfo table
	 * related to this comment.
	 * @return CommentInfo object
	**/
	private function get_info()
	{
		if ( ! $this->info ) {
			$this->info= new CommentInfo( $this->id );
		}
		return $this->info;
	}

 	/**
 	 * function setstatus
 	 * @param mixed the status to set it to. String or integer.
 	 * @return integer the status of the comment
 	 * Sets the status for a comment, given a string or integer.
 	 */
 	private function setstatus($value)
 	{
 		if(is_numeric($value))
 			$this->newfields['status']= $value;
 		else
 		{
 			switch(strtolower($value))
 			{
 				case "approved":
 				case "approve":
 				case "ham":
 					$this->newfields['status']= self::STATUS_APPROVED;
 					break;
 				case "unapproved":
 				case "unapprove":
 					$this->newfields['status']= self::STATUS_UNAPPROVED;
 					break;
 				case "spam":
 					$this->newfields['status']= self::STATUS_SPAM;
 					break;
 				case "deleted":
 					$this->newfields['status']= self::STATUS_DELETED;
 					break;
 			}
 		}
 		return $this->newfields['status'];
 	}

}

?>
