<?php
/**
 * @package Habari
 *
 */

namespace Habari;

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
 * @property-write mixed $status The status of the comment. Can be a string or an integer
 * @property-write mixed $date The date of the comment. Can be a DateTime object or any of the formats accepted by DateTime::date_create()
 * @property mixed $post The post with which the comment is associated. Can be an integer, a string, or a Post object on write. Always a Post object on read
 * @property-read string $name The comment author's name, Anonymous if empty
 * @property-read CommentInfo $info The CommentInfo associated with the comment
 * @property-read string $statusname The friendly name of the comment's status
 * @property-read string $typename The friendly name of the comment's type
 * @property-read string $editlink Edit URL for the comment
 * @property integer id The id of this comment in the database
 * @property integer type The type id of this comment in the database
 * @property integer post_id The id of the post to which this comment is associated
 */
class Comment extends QueryRecord implements IsContent
{
	private $post_object = null;

	private $inforecords = null;

	// static variables to hold comment status and comment type values
	static $comment_status_list = array();
	static $comment_type_list = array();
	static $comment_status_actions = array();

	/**
	 * Returns the defined database columns for a comment
	 * @return array The requested array
	 */
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
			'status' => self::status('unapproved'),
			'date' => DateTime::create(),
			'type' => self::type('comment')
		);
	}

	/**
	 * Constructor for the Comment class.
	 * @param array $paramarray an associative array of initial Post field values.
	 */
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge( self::default_fields(), $this->fields );
		parent::__construct( $paramarray );
		$this->exclude_fields( 'id' );
	}

	/**
	 * Register plugin hooks
	 * @static
	 */
	public static function __static()
	{
		Pluggable::load_hooks(__CLASS__);
	}

	/**
	 * static function get
	 * Returns a single comment, by ID
	 *
	 * <code>
	 * $post = Post::get( 10 );
	 * </code>
	 *
	 * @param int $id An ID
	 * @return array A single Comment object
	 */
	static function get( $id = 0 )
	{
		if ( ! $id ) {
			return false;
		}
		return DB::get_row( 'SELECT * FROM {comments} WHERE id = ?', array( $id ), 'Comment' );
	}

	/**
	 * Creates a comment and saves it
	 * @param array $paramarray An associative array of comment fields
	 * $return Comment The comment object that was created
	 * @return \Habari\Comment The new comment
	 */
	static function create( $paramarray )
	{
		$comment = new Comment( $paramarray );
		$comment->insert();
		return $comment;
	}

	/**
	 * Saves a new comment to the comments table
	 * @return integer|boolean The inserted record id on success, false if not
	 */
	public function insert()
	{
		$allow = true;
		$allow = Plugins::filter( 'comment_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act( 'comment_insert_before', $this );
		// Invoke plugins for all fields, since they're all "changed" when inserted
		foreach ( $this->fields as $fieldname => $value ) {
			Plugins::act( 'comment_update_' . $fieldname, $this, $this->$fieldname, $value );
		}
		$result = parent::insertRecord( DB::table( 'comments' ) );
		$this->newfields['id'] = DB::last_insert_id(); // Make sure the id is set in the comment object to match the row id
		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();
		$this->info->commit( $this->fields['id'] );
		Plugins::act( 'comment_insert_after', $this );
		return $result;
	}

	/**
	 * Register a new comment type
	 * @param string $type The name of the new comment type
	 * @return integer The id of the new comment type
	 */
	public static function add_type($type)
	{
		DB::insert(
			DB::table('commenttype'),
			array(
				'name' => $type,
				'active' => 1,
			)
		);
		return DB::last_insert_id();
	}

	/**
	 * Register a new comment status
	 * @param string $status The name of the new comment status
	 * @param bool $internal True if the status is one that was added by core
	 * @return integer The id of the new comment type
	 */
	public static function add_status($status, $internal = false)
	{
		DB::insert(
			DB::table('commentstatus'),
			array(
				'name' => $status,
				'internal' => $internal ? 1 : 0,
			)
		);
		return DB::last_insert_id();
	}

	/**
	 * Remove a comment type from the database
	 * @param integer|string $type The type of the comment
	 * @param bool $delete If true, delete the type and all comments of that type instead of deactivating it
	 */
	public static function remove_type($type, $delete = false)
	{
		if($delete) {
			// Delete comments of this type, delete type
			$type_id = Comment::type($type);
			DB::delete(
				DB::table('comments'),
				array('type' => $type_id)
			);
			DB::exec('DELETE FROM {commentinfo} WHERE comment_id IN (SELECT {commentinfo}.comment_id FROM {commentinfo} LEFT JOIN {comments} ON {commentinfo}.comment_id = {comments}.id WHERE {comments}.id IS NULL)');
			DB::delete(
				DB::table('commenttype'),
				array('name' => Comment::type_name($type))
			);
		}
		else {
			DB::update(
				DB::table('commenttype'),
				array(
					'name' => Comment::type_name($type),
					'active' => 0,
				),
				array('name')
			);
		}
	}

	/**
	 * Remove a comment type from the database
	 * @param integer|string $status The type of the comment
	 * @param null|integer|string $newstatus If provided, the new status to change all of the comments with the deleted status to
	 */
	public static function remove_status($status, $newstatus = null)
	{
		// Delete comments of this status, delete status
		$status_id = Comment::status($status);
		if(is_null($newstatus)) {
			DB::delete(
				DB::table('comments'),
				array('status' => $status_id)
			);
			DB::exec('DELETE FROM {commentinfo} WHERE comment_id IN (SELECT {commentinfo}.comment_id FROM {commentinfo} LEFT JOIN {comments} ON {commentinfo}.comment_id = {comments}.id WHERE {comments}.id IS NULL)');
		}
		else {
			DB::update(
				DB::table('comments'),
				array('status' => Comment::status($newstatus)),
				array('status' => $status_id)
			);
		}
		DB::delete(
			DB::table('commentstatus'),
			array('name' => Comment::status_name($status))
		);
	}

	/**
	 * Updates an existing comment in the comments table
	 * @return boolean True on success, false if not
	 */
	public function update()
	{
		$allow = true;
		$allow = Plugins::filter( 'comment_update_allow', $allow, $this );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act( 'comment_update_before', $this );
		// invoke plugins for all fields which have been updated
		foreach ( $this->newfields as $fieldname => $value ) {
			Plugins::act( 'comment_update_' . $fieldname, $this, $this->fields[$fieldname], $value );
		}
		$result = parent::updateRecord( DB::table( 'comments' ), array( 'id'=>$this->id ) );
		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();
		$this->info->commit();
		Plugins::act( 'comment_update_after', $this );
		return $result;
	}

	/**
	 * Deletes this comment
	 * @return boolean True on success, false if not
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter( 'comment_delete_allow', $allow, $this );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act( 'comment_delete_before', $this );

		// Delete all info records associated with this comment
		$this->info->delete_all();

		$result = parent::deleteRecord( DB::table( 'comments' ), array( 'id'=>$this->id ) );
		Plugins::act( 'comment_delete_after', $this );
		return $result;
	}

	/**
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string $name Name of property to return
	 * @return mixed The requested field value
	 */
	public function __get( $name )
	{
		$fieldnames = array_merge( array_keys( $this->fields ), array('post', 'info', 'editlink' ) );
		$filter = false;
		if ( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			$field_matches = implode('|', $fieldnames);
			if(preg_match( '/^(' . $field_matches . ')_(.+)$/', $name, $matches )) {
				list( $unused, $name, $filter ) = $matches;
			}
		}

		switch ( $name ) {
			case 'name':
				if ( parent::__get( $name ) == '' ) {
					$out = _t( 'Anonymous' );
				}
				else {
					$out = parent::__get( $name );
				}
				break;
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
			case 'editlink':
				$out = $this->get_editlink();
				break;
			default:
				if(preg_match('#^is_(.+)$#i', $name, $matches)) {
					$ts_field = $matches[1];
					if($index = array_search($ts_field, Comment::list_comment_statuses())) {
						$out = $this->status == $index;
					}
					if($index = array_search($ts_field, Comment::list_comment_types())) {
						$out = $this->type == $index;
					}
					// Dumb check for plurals
					$pluralize = function($s) {
						return $s . 's';
					};
					if($index = array_search($ts_field, array_map($pluralize, Comment::list_comment_statuses()))) {
						$out = $this->status == $index;
					}
					if($index = array_search($ts_field, array_map($pluralize, Comment::list_comment_types()))) {
						$out = $this->type == $index;
					}
				}
				else {
					$out = parent::__get( $name );
				}
				break;
		}
		//$out = parent::__get( $name );
		$out = Plugins::filter( "comment_{$name}", $out, $this );
		if ( $filter ) {
			$out = Plugins::filter( "comment_{$name}_{$filter}", $out, $this );
		}
		return $out;
	}

	/**
	 * Overrides QueryRecord __set to implement custom object properties
	 * @param string $name Name of property to set
	 * @param mixed $value Value of the property
	 * @return mixed The set field value
	 */
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'status':
				$value = self::status($value);
				break;
			case 'date':
				if ( !( $value instanceOf DateTime ) ) {
					$value = DateTime::create( $value );
				}
				break;
			case 'post':
				if ( is_int( $value ) ) {
					// a post ID was passed
					$p = Post::get( array( 'id'=>$value ) );
					$this->post_id = $p->id;
					$this->post_object = $p;
				}
				elseif ( is_string( $value ) ) {
					// a post Slug was passed
					$p = Post::get( array( 'slug'=>$value ) );
					$this->post_id = $p->id;
					$this->post_object = $p;
				}
				elseif ( is_object( $value ) ) {
					// a Post object was passed, so just use it directly
					$this->post_id = $value->id;
					$this->post_object = $value;
				}
				return $value;
		}
		return parent::__set( $name, $value );
	}

	/**
	 * Obtain the Post object for the post of this comment
	 * @param bool $use_cache Whether to use the cached version or not.  Default to true
	 * @return Post a Post object for the post of the current comment
	 */
	private function get_post( $use_cache = true )
	{
		if ( ! isset( $this->post_object ) || ( ! $use_cache)  ) {
			$this->post_object = Posts::get( array('id' => $this->post_id, 'fetch_fn' => 'get_row') );
		}
		return $this->post_object;
	}

	/**
	 * Gets the info object for this comment, which contains data from the commentinfo table
	 * related to this comment.
	 * @return CommentInfo object
	 */
	private function get_info()
	{
		if ( ! $this->inforecords ) {
			if ( 0 == $this->id ) {
				$this->inforecords = new CommentInfo();
			}
			else {
				$this->inforecords = new CommentInfo( $this->id );
			}
		}
		return $this->inforecords;
	}

	/**
	 * Obtain an associative array of comment types
	 * @param boolean $refresh Whether to force a refresh of the cached values
	 * @return array An array mapping comment type names to integer values
	 */
	public static function list_comment_types( $refresh = false )
	{
		if ( $refresh || empty( self::$comment_type_list ) ) {
			self::$comment_type_list = DB::get_keyvalue('SELECT id, name FROM {commenttype} WHERE active = 1;');
		}
		self::$comment_type_list = Plugins::filter( 'list_comment_types', self::$comment_type_list );
		return self::$comment_type_list;
	}

	/**
	 * Obtain an associative array of comment statuses
	 * @param bool $refresh Whether to force a refresh of the cached values
	 * @return array An array mapping comment statuses names to interger values
	 */
	public static function list_comment_statuses( $refresh = false )
	{
		if ( $refresh || empty( self::$comment_status_list ) ) {
			self::$comment_status_list = DB::get_keyvalue('SELECT id, name FROM {commentstatus};');
		}
		self::$comment_status_list = Plugins::filter( 'list_comment_statuses', self::$comment_status_list );
		return self::$comment_status_list;
	}

	/**
	 * Obtain the action name of the comment status
	 * @param integer|string $status A comment status value, or name
	 * @return string A string of the status action, or null
	 */
	public static function status_action( $status )
	{
		if ( empty( self::$comment_status_actions ) ) {
			self::$comment_status_actions = array(
				'unapproved' => _t( 'Unapprove' ),
				'approved' => _t( 'Approve' ),
				'spam' => _t( 'Spam' ),
			);
			self::$comment_status_actions = Plugins::filter( 'list_comment_actions', self::$comment_status_actions );
		}
		if ( isset( self::$comment_status_actions[$status] ) ) {
			return self::$comment_status_actions[$status];
		}
		if ( isset(self::$comment_status_actions[Comment::status_name($status)]) ) {
			return self::$comment_status_actions[Comment::status_name($status)];
		}

		return '';
	}


	/**
	 * Obtain the integer value of the specified comment status, or false
	 * @param string|integer $name A comment status name or value
	 * @return integer|boolean The valid integer status value or false if there was no match
	 */
	public static function status( $name )
	{
		$statuses = Comment::list_comment_statuses();
		if ( is_numeric( $name ) && ( isset( $statuses[$name] ) ) ) {
			return $name;
		}
		$statuses = array_flip( $statuses );
		if ( isset( $statuses[$name] ) ) {
			return $statuses[$name];
		}
		return false;
	}

	/**
	 * Obtain the friendly name of a comment status
	 * @param integer|string $status A comment status value or name
	 * @return string The status name, or null
	 */
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
	 * Obtain the integer value of the specified comment type, or false
	 * @param integer|string $name a comment type name or number
	 * @return mixed an integer or boolean false
	 */
	public static function type( $name )
	{
		$types = Comment::list_comment_types();
		if ( is_numeric( $name ) && ( isset( $types[$name] ) ) ) {
			return $name;
		}
		$types = array_flip( $types );
		if ( isset( $types[$name] ) ) {
			return $types[$name];
		}
		return false;
	}

	/**
	 * Obtain the friendly name of a comment type, or null
	 * @param string|integer A comment type number, or name
	 * @return string A string of the comment type, or emptystring
	 */
	public static function type_name( $type )
	{
		$types = Comment::list_comment_types();
		if ( is_numeric( $type ) && isset( $types[$type] ) ) {
			return $types[$type];
		}
		$types = array_flip( $types );
		if ( isset( $types[$type] ) ) {
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
		return Comment::type_name( $this->type );
	}

	/**
	 * Returns an access Bitmask for the given user on this comment. Read access is determined
	 * by the associated post. Update/delete is determined by the comment management tokens.
	 * @param User $user The user mask to fetch
	 * @return Bitmask
	 */
	public function get_access( $user = null )
	{
		if ( ! $user instanceof User ) {
			$user = User::identify();
		}

		// these tokens automatically grant full access to the comment
		if ( $user->can( 'super_user' ) || $user->can( 'manage_all_comments' ) ||
			( $user->id == $this->post->user_id && $user->can( 'manage_own_post_comments' ) ) ) {
			return ACL::get_bitmask( 'full' );
		}

		/* If we got this far, we can't update or delete a comment. We still need to check if we have
		 * read access to it. Collect a list of applicable tokens
		 */
		$tokens = array(
			'post_any',
			'post_' . Post::type_name( $this->post->content_type ),
		);

		if ( $user->id == $this->post->user_id ) {
			$tokens[] = 'own_posts';
		}

		$tokens = array_merge( $tokens, $this->post->get_tokens() );

		$token_accesses = array();

		// grab the access masks on these tokens
		foreach ( $tokens as $token ) {
			$access = ACL::get_user_token_access( $user, $token );
			if ( $access instanceof Bitmask ) {
				$token_accesses[] = ACL::get_user_token_access( $user, $token )->value;
			}
		}

		// now that we have all the accesses, loop through them to build the access to the particular post
		if ( in_array( 0, $token_accesses ) ) {
			return ACL::get_bitmask( 0 );
		}

		if ( ACL::get_bitmask( Utils::array_or( $token_accesses ) )->read ) {
			return ACL::get_bitmask( 'read' );
		}

		// if we haven't returned by this point, we can neither manage the comment nor read it
		return ACL::get_bitmask( 0 );
	}

	/**
	 * Returns a URL for the ->editlink property of this class.
	 * @return string A url to edit this comment in the admin.
	 */
	private function get_editlink()
	{
		return URL::get( 'edit_comment', $this, false );
	}

	/**
	 * Returns a list of CSS classes for the comment
	 *
	 * @param string|array $append Additional classes that should be added to the ones generated
 	 * @return string The resultant classes
	 */
	public function css_class ( $append = array() ) {

		$classes = $append;

		$classes[] = 'comment';
		$classes[] = 'comment-' . $this->id;
		$classes[] = 'type-' . $this->typename;
		$classes[] = 'status-' . $this->statusname;

		$classes[] = 'comment-post-' . $this->post->id;

		return implode( ' ', $classes );

	}

	/**
	 * How to display the built-in comment types.
	 *
	 * @param string $type The type of comment
	 * @param string $foruse Can be 'singular' or 'plural'
	 * @return string The translated type name. This is always lowercase.
	 *	It is up to the caller to uppercase it
	 */
	public static function filter_comment_type_display_4( $type, $foruse )
	{
		$names = array(
			'comment' => array(
				'singular' => _t( 'comment' ),
				'plural' => _t( 'comments' ),
			),
			'pingback' => array(
				'singular' => _t( 'pingback' ),
				'plural' => _t( 'pingbacks' ),
			),
			'trackback' => array(
				'singular' => _t( 'trackback' ),
				'plural' => _t( 'trackbacks' ),
			),
		);
		return isset( $names[$type][$foruse] ) ? $names[$type][$foruse] : $type;
	}

	/**
	 * How to display the built-in comment statuses.
	 *
	 * @param string $status The name of the status we want to translate
	 * @return string The translated status name. This is always lowercase.
	 *	It is up to the caller to uppercase it.
	 */
	public static function filter_comment_status_display_4( $status )
	{
		$names = array(
			'unapproved' => _t( 'unapproved' ),
			'approved' => _t( 'approved' ),
			'spam' => _t( 'spam' ),
		);
		return isset( $names[$status] ) ? $names[$status] : $status;
	}
}

?>
