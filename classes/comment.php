<?php
/**
 * Habari CommentRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Comment extends QueryRecord
{

	// our definitions for comment types and statuses
	const STATUS_UNAPPROVED = 0;
	const STATUS_APPROVED = 1;
	const STATUS_SPAM = 2;

	const COMMENT = 0;
	const PINGBACK =1;
	const TRACKBACK = 2;

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
		$result = parent::insert( DB::o()->comments );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
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
	public function delete($id)
	{
		return parent::delete( DB::o()->comments, array('id'=>$id) );
	}
	
	/**
	* function massdelete
	* Burninates all the comments currently awaiting moderation
	*/
	public function mass_delete()
	{
		return parent::delete( DB::o()->comments, array( 'status' => STATUS_UNAPPROVED ) );
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
