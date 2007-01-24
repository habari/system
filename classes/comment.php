<?php
/**
 * Habari CommentRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 *
  * includes the CommentInfo object.
 * $existing_comment = new Comment(id=>1);
 * $existing_comment->info->browser_ua= "Netscape 2.0";
 *	print $existing_comment->info->browser_ua;
 */

class Comment extends QueryRecord
{

	// our definitions for comment types and statuses
	const STATUS_UNAPPROVED = 0;
	const STATUS_APPROVED = 1;
	const STATUS_SPAM = 2;
	
	const STATUS_DELETED = 3;  // These will eventually need to be deleted!

	const COMMENT = 0;
	const PINGBACK =1;
	const TRACKBACK = 2;
	
	private $info= null;
	/**
	* static function default_fields
	* Returns the defined database columns for a comment
	**/
	public static function default_fields()
	{
		return array(
			'id' => '',
			'post_slug' => '',
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
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields );
		parent::__construct( $paramarray );
		$this->exclude_fields('id');
		$this->info = new CommentInfo ( $this->fields['id'] );
		// $this->fields['id'] could be null. That's ok, provided $this->info::set_key is called before setting any info records		
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
		if ( ! $ID )
		{
			return false;
		}
		return DB::get_row( 'SELECT * FROM ' . DB::o()->comments . ' WHERE id = ?', $ID, 'Comment' );
	}
	
	/**
	 * static function create
	 * Creates a comment and saves it
	 * @param $paramarry array An associative array of comment fields
	 * @return The comment object that was created	 
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
		$result = parent::insert( DB::o()->comments );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();

		// if there is an insert called, it must be a new user, so find the user_id and set it. 
		// Now the $info object is safe to use for new comments
		$this->info->set_key ( DB::o()->last_insert_id() ); 
		// $this->info->option_default= "saved";
		return $result;
	}

	/**
	 * function update
	 * Updates an existing comment in the posts table
	 */	 	 	 	 	
	public function update()
	{
		$result = parent::update( DB::o()->comments, array('id'=>$this->id) );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		return $result;
	}
	
	/**
	 * function delete
	 * Deletes an existing comment
	 */	 	 	 	 	
	public function delete( $id )
	{
		return parent::delete( DB::o()->comments, array('id'=>$id) );
	}
	
	/**
	* function massdelete
	* Burninates all the comments currently awaiting moderation
	*/
	public function mass_delete( $status = STATUS_UNAPPROVED )
	{
		return parent::delete( DB::o()->comments, array( 'status' => $status ) );
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
		if ( ( 'name' == $name ) && ( '' == parent::__get( $name ) ) )
		{
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
