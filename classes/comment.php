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

	const COMMENT= 0;
	const PINGBACK= 1;
	const TRACKBACK= 2;
	
	
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
		return DB::get_row( 'SELECT * FROM ' . DB::o()->comments . ' WHERE id = ?', array( $ID ), 'Comment' );
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
		$result = parent::insert( DB::table('comments') );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->info->set_key( DB::last_insert_id() ); 
		 /* If a new comment is being created and inserted into the db, info is only safe to use _after_ this set_key call. */
		// $this->info->option_default= "saved";
		return $result;
	}

	/**
	 * function update
	 * Updates an existing comment in the posts table
	 */	 	 	 	 	
	public function update()
	{
		$result = parent::update( DB::table('comments'), array('id'=>$this->id) );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		return $result;
	}
	
	/**
	 * function delete
	 * Deletes an existing comment
	 */	 	 	 	 	
	public function delete($id)
	{
		return parent::delete( DB::table('comments'), array('id'=>$id) );
	}
	
	/**
	* function massdelete
	* Burninates all the comments currently awaiting moderation
	*/
	public function mass_delete()
	{
		return parent::delete( DB::table('comments'), array( 'status' => STATUS_UNAPPROVED ) );
	}
	
	/**
	 * function publish
	 * Updates an existing comment to published status
	 * @return boolean True on success, false if not	 
	 */
	public function publish()
	{
		$this->status = 'publish';
		return $this->update();
	}
	
	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value	 
	 **/	 	 
	public function __get( $name )
	{
		if ( $name == 'name' && parent::__get( $name ) == '' ) {
			return __('Anonymous');
		}
		return parent::__get( $name );
	}

	/**
	 * function __set
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value	 
	 **/	 	 
	public function __set( $name, $value )
	{
		return parent::__set( $name, $value );
	}
	
}

?>
