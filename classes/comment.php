<?php
/**
 * @package Habari
 *
 */

/**
 * Habari CommentRecord Class
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
class Comment extends QueryRecord implements IsContent
{
	// our definitions for comment types and statuses
	const STATUS_UNAPPROVED = 0;
	const STATUS_APPROVED = 1;
	const STATUS_SPAM = 2;
	const STATUS_DELETED = 3;

	const COMMENT = 0;
	const PINGBACK = 1;
	const TRACKBACK = 2;

	private $post_object = null;

	private $info = null;

	// static variables to hold comment status and comment type values
	static $comment_status_list = array();
	static $comment_type_list = array();
	static $comment_status_actions = array();

	/**
	 * returns an associative array of active comment types
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of comment type names => integer values
	**/
	public static function list_active_comment_types( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$comment_type_list_active ) ) ) {
			return self::$comment_type_list_active;
		}
		self::$comment_type_list_active['any'] = 0;
		$sql = 'SELECT * FROM {commenttype} WHERE active = 1 ORDER BY id ASC';
		$results = DB::get_results( $sql );
		foreach ( $results as $result ) {
			self::$comment_type_list_active[$result->name] = $result->id;
		}
		return self::$comment_type_list_active;
	}

	/**
	 * returns an associative array of all comment types
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of comment type names => (integer values, active values)
	**/
	public static function list_all_comment_types( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$comment_type_list_all ) ) ) {
			return self::$post_type_list_all;
		}
		self::$comment_type_list_all['any'] = 0;
		$sql = 'SELECT * FROM {commenttype} ORDER BY id ASC';
		$results = DB::get_results( $sql );
		foreach ( $results as $result ) {
			self::$comment_type_list_all[$result->name] = array(
				'id' => $result->id,
				'active' => $result->active
				);
		}
		return self::$post_type_list_all;
	}
	
	public static function activate_comment_type( $type )
	{
		$all_post_types = Comment::list_all_comment_types( true ); // We force a refresh

		// Check if it exists
		if ( array_key_exists( $type, $all_post_types ) ) {
			if ( ! $all_comment_types[$type]['active'] == 1 ) {
				// Activate it
				$sql = 'UPDATE {commenttype} SET active = 1 WHERE id = ' . $all_comment_types[$type]['id'];
				DB::query( $sql );
			}
			return true;
		}
		else {
			return false; // Doesn't exist
		}
	}

	public static function deactivate_comment_type( $type )
	{
		$active_comment_types = Post::list_active_comment_types( false ); // We force a refresh

		if ( array_key_exists( $type, $active_post_types ) ) {
			// $type is active so we'll deactivate it
			$sql = 'UPDATE {commenttype} SET active = 0 WHERE id = ' . $active_comment_types[$type];
			DB::query( $sql );
			return true;
		}
		return false;
	}

	/**
	 * returns an associative array of comment statuses
	 * @param mixed $all true to list all statuses, not just external ones, comment to list external and any that match the comment status
	 * @param boolean $refresh true to force a refresh of the cached values
	 * @return array An array of comment statuses names => interger values
	**/
	public static function list_comment_statuses( $all = true, $refresh = false )
	{
		$statuses = array();
		$statuses['any'] = 0;
		if ( $refresh || empty( self::$comment_status_list ) ) {
			$sql = 'SELECT * FROM {commentstatus} ORDER BY id ASC';
			$results = DB::get_results( $sql );
			self::$comment_status_list = $results;
		}
		foreach ( self::$comment_status_list as $status ) {
			if ( $all instanceof Comment ) {
				if( ! $status->internal || $status->id == $all->status ) {
					$statuses[$status->name] = $status->id;
				}
			}
			elseif ( $all ) {
				$statuses[$status->name] = $status->id;
			}
			elseif ( ! $status->internal ) {
				$statuses[$status->name] = $status->id;
			}
		}
		return $statuses;
	}

	/**
	 * returns the interger value of the specified comment status, or false
	 * @param mixed a comment status name or value
	 * @return mixed an integer or boolean false
	**/
	public static function status( $name )
	{
		$statuses = Comment::list_comment_statuses();
		if ( is_numeric( $name ) && ( FALSE !== in_array( $name, $statuses ) ) ) {
			return $name;
		}
		if ( isset( $statuses[strtolower( $name )] ) ) {
			return $statuses[strtolower( $name )];
		}
		return false;
	}

	/**
	 * returns the friendly name of a comment status, or null
	 * @param mixed a comment status value, or name
	 * @return mixed a string of the status name, or null
	**/
	public static function status_name( $status )
	{
		$statuses = array_flip( Comment::list_comment_statuses() );
		if ( is_numeric( $status ) && isset( $statuses[$status] ) ) {
			return $statuses[$status];
		}
		if ( FALSE !== in_array( $status, $statuses ) ) {
			return $status;
		}
		return '';
	}

	/**
	 * returns the integer value of the specified comment type, or false
	 * @param mixed a post type name or number
	 * @return mixed an integer or boolean false
	**/
	public static function type( $name )
	{
		$types = Comment::list_active_comment_types();
		if ( is_numeric( $name ) && ( FALSE !== in_array( $name, $types ) ) ) {
			return $name;
		}
		if ( isset( $types[strtolower( $name )] ) ) {
			return $types[strtolower( $name )];
		}
		return false;
	}

	/**
	 * returns the friendly name of a comment type, or null
	 * @param mixed a comment type number, or name
	 * @return mixed a string of the post type, or null
	**/
	public static function type_name( $type )
	{
		$types = array_flip( Comment::list_active_comment_types() );
		if ( is_numeric( $type ) && isset( $types[$type] ) ) {
			return $types[$type];
		}
		if ( FALSE !== in_array( $type, $types ) ) {
			return $type;
		}
		return '';
	}

	/**
	 * inserts a new comment type into the database, if it doesn't exist
	 * @param string The name of the new comment type
	 * @param bool Whether the new comment type is active or not
	 * @return none
	**/
	public static function add_new_type( $type, $active = true )
	{
		// refresh the cache from the DB, just to be sure
		$types = self::list_all_comment_types( true );

		if ( ! array_key_exists( $type, $types ) ) {
			// Doesn't exist in DB.. add it and activate it.
			DB::query( 'INSERT INTO {commenttype} (name, active) VALUES (?, ?)', array( $type, $active ) );
		}
		elseif ( $types[$type]['active'] == 0 ) {
			// Isn't active so we activate it
			self::activate_comment_type( $type );
		}
		ACL::create_token( 'post_' . Utils::slugify($type), _t('Permissions to posts of type "%s"', array($type) ), _t('Content'), TRUE );

		// now force a refresh of the caches, so the new/activated type
		// is available for immediate use
		$types = self::list_active_comment_types( true );
		$types = self::list_all_comment_types( true );
	}

	/**
	 * inserts a new comment status into the database, if it doesn't exist
	 * @param string The name of the new comment status
	 * @param bool Whether this status is for internal use only.  If true,
	 *	this status will NOT be presented to the user
	 * @return none
	**/
	public static function add_new_status( $status, $internal = false )
	{
		// refresh the cache from the DB, just to be sure
		$statuses = self::list_comment_statuses( true );
		if ( ! array_key_exists( $status, $statuses ) ) {
			// let's make sure we only insert an integer
			$internal = intval( $internal );
			DB::query( 'INSERT INTO {commenttype} (name, internal) VALUES (?, ?)', array( $status, $internal ) );
			// force a refresh of the cache, so the new status
			// is available for immediate use
			$statuses = self::list_comment_statuses( true, true );
		}
	}

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
			'ip' => 0,
			'content' => '',
			'status' => self::STATUS_UNAPPROVED,
			'date' => HabariDateTime::date_create(),
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
		$this->info = new CommentInfo ( $this->fields['id'] );
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
		return DB::get_row( 'SELECT * FROM {comments} WHERE id = ?', array( $ID ), 'Comment' );
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
		$allow = true;
		$allow = Plugins::filter('comment_insert_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('comment_insert_before', $this);
		// Invoke plugins for all fields, since they're all "chnaged" when inserted
		foreach ( $this->fields as $fieldname => $value ) {
			Plugins::act('comment_update_' . $fieldname, $this, $this->$fieldname, $value );
		}
		$result = parent::insertRecord( DB::table('comments') );
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
		$allow = true;
		$allow = Plugins::filter('comment_update_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('comment_update_before', $this);
		// invoke plugins for all fields which have been updated
		foreach ($this->newfields as $fieldname => $value ) {
			Plugins::act('comment_update_' . $fieldname, $this, $this->fields[$fieldname], $value);
		}
		$result = parent::updateRecord( DB::table('comments'), array('id'=>$this->id) );
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
		$allow = true;
		$allow = Plugins::filter('comment_delete_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('comment_delete_before', $this);
		// Delete all info records associated with this comment
		if ( isset( $this->info ) ) {
			$this->info->delete_all();
		}
		return parent::deleteRecord( DB::table('comments'), array('id'=>$this->id) );
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
		$fieldnames = array_merge( array_keys( $this->fields ), array('post', 'info' ) );
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
			case 'statusname':
				$out = self::status_name( $this->status );
				break;
			case 'typename':
				$out = self::type_name( $this->type );
				break;
			default:
				$out = parent::__get( $name );
				break;
		}
		//$out = parent::__get( $name );
		$out = Plugins::filter( "comment_{$name}", $out, $this );
		if( $filter ) {
			$out = Plugins::filter( "comment_{$name}_{$filter}", $out, $this );
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
		case 'date':
			if ( !($value instanceOf HabariDateTime) ) {
				$value = HabariDateTime::date_create($value);
			}
			break;
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
		if ( ! isset( $this->post_object ) || ( ! $use_cache)  ) {
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
			$this->info = new CommentInfo( $this->id );
		}
		return $this->info;
	}
	
	/**
	 * function setstatus
	 * @param mixed the status to set it to. String or integer.
	 * @return integer the status of the post
	 * Sets the status for a comment, given a string or integer.
	 */
	private function setstatus( $value )
	{
		$statuses = Post::list_comment_statuses();
		if ( is_numeric( $value ) && in_array( $value, $statuses ) ) {
			return $this->newfields['status'] = $value;
		}
		elseif ( array_key_exists( $value, $statuses ) ) {
			return $this->newfields['status'] = Comment::status( 'publish' );
		}

		return false;
	}

	/**
	 * returns an associative array of comment types
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of comment type names => integer values
	**/
	public static function list_comment_types( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$comment_type_list ) ) ) {
			return self::$comment_type_list;
		}
		self::$comment_type_list = array(
			self::COMMENT => 'comment',
			self::PINGBACK => 'pingback',
			self::TRACKBACK => 'trackback',
		);
		return self::$comment_type_list;
	}

	/**
	 * returns an associative array of comment statuses
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of comment statuses names => interger values
	**/
	public static function list_comment_statuses( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$comment_status_list ) ) ) {
			return self::$comment_status_list;
		}
		self::$comment_status_list = array(
			self::STATUS_UNAPPROVED => 'unapproved',
			self::STATUS_APPROVED => 'approved',
			self::STATUS_SPAM => 'spam',
			// 'STATUS_DELETED' => self::STATUS_DELETED, // Not supported
		);
		self::$comment_status_list = Plugins::filter('list_comment_statuses', self::$comment_status_list);
		return self::$comment_status_list;
	}

	/**
	 * returns the action name of the comment status
	 * @param mixed a comment status value, or name
	 * @return string a string of the status action, or null
	**/
	public static function status_action( $status )
	{
		if ( empty( self::$comment_status_actions ) ) {
			self::$comment_status_actions = array(
				self::STATUS_UNAPPROVED => _t('Unapprove'),
				self::STATUS_APPROVED => _t('Approve'),
				self::STATUS_SPAM => _t('Spam'),
			);
			self::$comment_status_actions = Plugins::filter('list_comment_actions', self::$comment_status_actions);
		}
		if ( is_numeric( $status ) && isset( self::$comment_status_actions[$status] ) ) {
			return self::$comment_status_actions[$status];
		}
		$statuses = array_flip( Comment::list_comment_statuses() );
		if ( isset($statuses[$name]) ) {
			return self::$comment_status_actions[$statuses[$name]];
		}

		return '';
	}


	/**
	 * returns the integer value of the specified comment status, or false
	 * @param mixed a comment status name or value
	 * @return mixed an integer or boolean false
	**/
	public static function status( $name )
	{
		$statuses = Comment::list_comment_statuses();
		if ( is_numeric( $name ) && ( isset( $statuses[$name] ) ) ) {
			return $name;
		}
		$statuses = array_flip( $statuses );
		if ( isset($statuses[$name]) ) {
			return $statuses[$name];
		}
		return false;
	}

	/**
	 * returns the friendly name of a comment status, or null
	 * @param mixed a comment status value, or name
	 * @return mixed a string of the status name, or null
	**/
	public static function status_name( $status )
	{
		$statuses = Comment::list_comment_statuses();
		if ( is_numeric( $status ) && isset( $statuses[$status] ) ) {
			return $statuses[$status];
		}
		$statuses = array_flip( $statuses );
		if ( isset( $statuses[$status] ) ) {
			return $status;
		}
		return '';
	}

	/**
	 * returns the integer value of the specified comment type, or false
	 * @param mixed a comment type name or number
	 * @return mixed an integer or boolean false
	**/
	public static function type( $name )
	{
		$types = Comment::list_comment_types();
		if ( is_numeric( $name ) && ( isset( $types[$name] ) ) ) {
			return $name;
		}
		$types = array_flip($types);
		if ( isset( $types[$name] ) ) {
			return $types[$name];
		}
		return false;
	}

	/**
	 * returns the friendly name of a comment type, or null
	 * @param mixed a comment type number, or name
	 * @return mixed a string of the comment type, or null
	**/
	public static function type_name( $type )
	{
		$types = Comment::list_comment_types();
		if ( is_numeric( $type ) && isset( $types[$type] ) ) {
			return $types[$type];
		}
		$types = array_flip($types);
		if ( isset($types[$type]) ) {
			return $type;
		}
		return '';
	}

	/**
	 * Return the content type of this object
	 *
	 * @return string The content type of this object
	 * @see IsContent
	 */
	public function content_type()
	{
		return Comment::type_name($this->type);
	}
}

?>
