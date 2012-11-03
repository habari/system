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
 * @property-write mixed $status The status of the comment. Can be a string or an integer
 * @property-write mixed $date The date of the comment. Can be a HabariDateTime object or any of the formats accepted by HabariDateTime::date_create()
 * @property mixed $post The post with which the comment is associated. Can be an integer, a string, or a Post object on write. Always a Post object on read
 * @property-read string $name The comment author's name, Anonymous if empty
 * @property-read CommentInfo $info The CommentInfo associated with the comment
 * @property-read string $statusname The friendly name of the comment's status
 * @property-read string $typename The friendly name of the comment's type
 * @property-read string $editlink Edit URL for the comment
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

	private $inforecords = null;

	// static variables to hold comment status and comment type values
	static $comment_status_list = array();
	static $comment_type_list = array();
	static $comment_status_actions = array();

	/**
	 * static function default_fields
	 * Returns the defined database columns for a comment
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
			'status' => self::STATUS_UNAPPROVED,
			'date' => HabariDateTime::date_create(),
			'type' => self::COMMENT
		);
	}

	/**
	 * constructor __construct
	 * Constructor for the Post class.
	 * @param array an associative array of initial Post field values.
	 */
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge( self::default_fields(), $this->fields );
		parent::__construct( $paramarray );
		$this->exclude_fields( 'id' );
		/* $this->fields['id'] could be null in case of a new comment. If so, the info object is _not_ safe to use till after set_key has been called. Info records can be set immediately in any other case. */

	}

	/**
	 * Register plugin hooks
	 * @static
	 */
	public static function __static()
	{
		Pluggable::load_hooks('Comment');
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
	 */
	static function get( $id = 0 )
	{
		if ( ! $id ) {
			return false;
		}
		return DB::get_row( 'SELECT * FROM {comments} WHERE id = ?', array( $id ), 'Comment' );
	}

	/**
	 * static function create
	 * Creates a comment and saves it
	 * @param array An associative array of comment fields
	 * $return Comment The comment object that was created
	 */
	static function create( $paramarray )
	{
		$comment = new Comment( $paramarray );
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
		$allow = Plugins::filter( 'comment_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return;
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
	 * function update
	 * Updates an existing comment in the posts table
	 */
	public function update()
	{
		$allow = true;
		$allow = Plugins::filter( 'comment_update_allow', $allow, $this );
		if ( ! $allow ) {
			return;
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
	 * function delete
	 * Deletes this comment
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter( 'comment_delete_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'comment_delete_before', $this );

		// Delete all info records associated with this comment
		$this->info->delete_all();

		$result = parent::deleteRecord( DB::table( 'comments' ), array( 'id'=>$this->id ) );
		Plugins::act( 'comment_delete_after', $this );
		return $result;
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 */
	public function __get( $name )
	{
		$fieldnames = array_merge( array_keys( $this->fields ), array('post', 'info', 'editlink' ) );
		$filter = false;
		if ( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			$field_matches = implode('|', $fieldnames);
			if(preg_match( '/^(' . $field_matches . ')_(.+)$/', $name, $matches )) {
				list( $junk, $name, $filter ) = $matches;
			}
		}

		if ( $name == 'name' && parent::__get( $name ) == '' ) {
			return _t( 'Anonymous' );
		}
		switch ( $name ) {
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
				$out = parent::__get( $name );
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
	 * function __set
	 * Overrides QueryRecord __set to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 */
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'status':
				return $this->setstatus( $value );
			case 'date':
				if ( !( $value instanceOf HabariDateTime ) ) {
					$value = HabariDateTime::date_create( $value );
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
	 */
	private function get_post( $use_cache = true )
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
 	 * function setstatus
 	 * @param mixed the status to set it to. String or integer.
 	 * @return integer the status of the comment
 	 * Sets the status for a comment, given a string or integer.
 	 */
	private function setstatus( $value )
	{
		if ( is_numeric( $value ) ) {
			$this->newfields['status'] = $value;
		}
		else {
			switch ( strtolower( $value ) ) {
				case "approved":
				case "approve":
				case "ham":
					$this->newfields['status'] = self::STATUS_APPROVED;
					break;
				case "unapproved":
				case "unapprove":
					$this->newfields['status'] = self::STATUS_UNAPPROVED;
					break;
				case "spam":
					$this->newfields['status'] = self::STATUS_SPAM;
					break;
				case "deleted":
					$this->newfields['status'] = self::STATUS_DELETED;
					break;
			}
		}
		return $this->newfields['status'];
	}

	/**
	 * returns an associative array of comment types
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of comment type names => integer values
	 */
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
	 */
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
		self::$comment_status_list = Plugins::filter( 'list_comment_statuses', self::$comment_status_list );
		return self::$comment_status_list;
	}

	/**
	 * returns the action name of the comment status
	 * @param mixed a comment status value, or name
	 * @return string a string of the status action, or null
	 */
	public static function status_action( $status )
	{
		if ( empty( self::$comment_status_actions ) ) {
			self::$comment_status_actions = array(
				self::STATUS_UNAPPROVED => _t( 'Unapprove' ),
				self::STATUS_APPROVED => _t( 'Approve' ),
				self::STATUS_SPAM => _t( 'Spam' ),
			);
			self::$comment_status_actions = Plugins::filter( 'list_comment_actions', self::$comment_status_actions );
		}
		if ( is_numeric( $status ) && isset( self::$comment_status_actions[$status] ) ) {
			return self::$comment_status_actions[$status];
		}
		$statuses = array_flip( Comment::list_comment_statuses() );
		if ( isset( $statuses[$status] ) ) {
			return self::$comment_status_actions[$statuses[$status]];
		}

		return '';
	}


	/**
	 * returns the integer value of the specified comment status, or false
	 * @param mixed a comment status name or value
	 * @return mixed an integer or boolean false
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
	 * returns the friendly name of a comment status, or null
	 * @param mixed a comment status value, or name
	 * @return mixed a string of the status name, or null
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
	 * returns the integer value of the specified comment type, or false
	 * @param mixed a comment type name or number
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
	 * returns the friendly name of a comment type, or null
	 * @param mixed a comment type number, or name
	 * @return mixed a string of the comment type, or null
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
		return URL::get( 'admin', "page=comment&id={$this->id}" );
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
	 * @param string The name of the status we want to translate
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
