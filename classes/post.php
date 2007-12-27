<?php

/**
 * @package Habari
 *
 * Includes an instance of the PostInfo class; for holding inforecords about a Post
 * If the Post object describes an existing post; use the internal info object to
 * get, set, unset and test for existence (isset) of info records.
 * <code>
 * $this->info = new PostInfo ( 1 );  // Info records of post with id = 1
 * $this->info->option1= "blah"; // set info record with name "option1" to value "blah"
 * $info_value= $this->info->option1; // get value of info record with name "option1" into variable $info_value
 * if ( isset ($this->info->option1) )  // test for existence of "option1"
 * unset ( $this->info->option1 ); // delete "option1" info record
 * </code>
 *
 */
class Post extends QueryRecord
{
	// static variables to hold post status and post type values
	static $post_status_list= array();
	static $post_type_list_active= array();
	static $post_type_list_all= array();

	private $tags= null;
	private $comments_object= null;
	private $author_object= null;

	private $info= null;

	/**
	 * returns an associative array of active post types
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of post type names => integer values
	**/
	public static function list_active_post_types( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$post_type_list_active ) ) ) {
			return self::$post_type_list_active;
		}
		self::$post_type_list_active['any']= 0;
		$sql= 'SELECT * FROM ' . DB::table('posttype') . ' WHERE active = 1 ORDER BY id ASC';
		$results= DB::get_results( $sql );
		foreach ($results as $result) {
			self::$post_type_list_active[$result->name]= $result->id;
		}
		return self::$post_type_list_active;
	}

	/**
	 * returns an associative array of all post types
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of post type names => (integer values, active values)
	**/
	public static function list_all_post_types( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$post_type_list_all ) ) ) {
			return self::$post_type_list_all;
		}
		self::$post_type_list_all['any']= 0;
		$sql= 'SELECT * FROM ' . DB::table('posttype') . ' ORDER BY id ASC';
		$results= DB::get_results( $sql );
		foreach ($results as $result) {
			self::$post_type_list_all[$result->name]= array(
				'id' => $result->id,
				'active' => $result->active
				);
		}
		return self::$post_type_list_all;
	}

	public static function activate_post_type( $type )
	{
		$all_post_types = Post::list_all_post_types( true ); // We force a refresh

		// Check if it exists
		if ( array_key_exists( $type, $all_post_types ) ) {
			if ( ! $all_post_types[$type]['active'] == 1 ) {
				// Activate it
				$sql= 'UPDATE ' . DB::table('posttype') . ' SET active = 1 WHERE id = ' . $all_post_types[$type]['id'];
				DB::query( $sql );
			}
			return true;
		}
		else {
			return false; // Doesn't exist
		}
	}

	public static function deactivate_post_type( $type )
	{
		$active_post_types = Post::list_active_post_types( false ); // We force a refresh

		if ( array_key_exists( $type, $active_post_types ) ) {
			// $type is active so we'll deactivate it
			echo $active_post_types[$type];
			$sql= 'UPDATE ' . DB::table('posttype') . ' SET active = 0 WHERE id = ' . $active_post_types[$type];
			echo $sql;
			DB::query( $sql );
			return true;
		}
		return false;
	}

	/**
	 * returns an associative array of post statuses
	 * @param bool whether to force a refresh of the cached values
	 * @return array An array of post statuses names => interger values
	**/
	public static function list_post_statuses( $all= true, $refresh= false  )
	{
		$statuses= array();
		$statuses['any']= 0;
		if ( ( ! $refresh ) && ( ! empty( self::$post_status_list ) ) )
		{
			foreach ( self::$post_status_list as $status ) {
				if ( $all ) {
					$statuses[$status->name]= $status->id;
				} elseif ( ! $status->internal ) {
					$statuses[$status->name]= $status->id;
				}
			}
			return $statuses;
		}
		$sql= 'SELECT * FROM ' . DB::table('poststatus') . ' ORDER BY id ASC';
		$results= DB::get_results( $sql );
		self::$post_status_list= $results;
		foreach ( self::$post_status_list as $status ) {
			if ( $all ) {
				$statuses[$status->name]= $status->id;
			} elseif ( ! $status->internal ) {
				$statuses[$status->name]= $status->id;
			}
		}
		return $statuses;
	}

	/**
	 * returns the interger value of the specified post status, or false
	 * @param mixed a post status name or value
	 * @return mixed an integer or boolean false
	**/
	public static function status( $name )
	{
		$statuses= Post::list_post_statuses();
		if ( is_numeric( $name ) && ( FALSE !== in_array( $name, $statuses ) ) ) {
			return $name;
		}
		if ( isset( $statuses[strtolower($name)] ) )
		{
			return $statuses[strtolower($name)];
		}
		return false;
	}

	/**
	 * returns the friendly name of a post status, or null
	 * @param mixed a post status value, or name
	 * @return mixed a string of the status name, or null
	**/
	public static function status_name( $status )
	{
		$statuses= array_flip( Post::list_post_statuses() );
		if ( is_numeric( $status ) && isset( $statuses[$status] ) )
		{
			return $statuses[$status];
		}
		if ( FALSE !== in_array( $status, $statuses ) )
		{
			return $status;
		}
		return '';
	}

	/**
	 * returns the integer value of the specified post type, or false
	 * @param mixed a post type name or number
	 * @return mixed an integer or boolean false
	**/
	public static function type( $name )
	{
		$types= Post::list_active_post_types();
		if ( is_numeric( $name ) && ( FALSE !== in_array( $name, $types ) ) ) {
			return $name;
		}
		if ( isset( $types[strtolower($name)] ) ) {
			return $types[strtolower($name)];
		}
		return false;
	}

	/**
	 * returns the friendly name of a post type, or null
	 * @param mixed a post type number, or name
	 * @return mixed a string of the post type, or null
	**/
	public static function type_name( $type )
	{
		$types= array_flip( Post::list_active_post_types() );
		if ( is_numeric( $type ) && isset( $types[$type] ) )
		{
			return $types[$type];
		}
		if ( FALSE !== in_array( $type, $types ) )
		{
			return $type;
		}
		return '';
	}

	/**
	 * inserts a new post type into the database, if it doesn't exist
	 * @param string The name of the new post type
	 * @param bool Whether the new post type is active or not
	 * @return none
	**/
	public static function add_new_type( $type, $active= true )
	{
		// refresh the cache from the DB, just to be sure
		$types= self::list_all_post_types( true );

		if ( ! array_key_exists( $type, $types ) ) {
			// Doesn't exist in DB.. add it and activate it.
			DB::query( 'INSERT INTO ' . DB::table('posttype') . ' (name, active) VALUES (?, ?)', array( $type, $active ) );
		} elseif ( $types[$type]['active'] == 0 ) {
			// Isn't active so we activate it
			self::activate_post_type( $type );
		}

		// now force a refresh of the caches, so the new/activated type
		// is available for immediate use
		$types= self::list_active_post_types( true );
		$types= self::list_all_post_types( true );
	}

	/**
	 * inserts a new post status into the database, if it doesn't exist
	 * @param string The name of the new post status
	 * @param bool Whether this status is for internal use only.  If true,
	 *	this status will NOT be presented to the user
	 * @return none
	**/
	public static function add_new_status( $status, $internal= false )
	{
		// refresh the cache from the DB, just to be sure
		$statuses= self::list_post_statuses( true );
		if ( ! array_key_exists( $status, $statuses ) ) {
			// let's make sure we only insert an integer
			$internal= intval( $internal );
			DB::query( 'INSERT INTO ' . DB::table('poststatus') . ' (name, internal) VALUES (?, ?)', array( $status, $internal ) );
			// force a refresh of the cache, so the new status
			// is available for immediate use
			$statuses= self::list_post_statuses( true, true );
		}
	}

	/**
	 * Return the defined database columns for a Post.
	 * @return array Array of columns in the Post table
	**/
	public static function default_fields()
	{
		return array(
			'id' => 0,
			'slug' => '',
			'title' => '',
			'guid' => '',
			'content' => '',
			'cached_content' => '',
			'user_id' => 0,
			'status' => Post::status('draft'),
			'pubdate' => date( 'Y-m-d H:i:s' ),
			'updated' => date ( 'Y-m-d H:i:s' ),
			'content_type' => Post::type('entry')
		);
	}

	/**
	 * Constructor for the Post class.
	 * @param array $paramarray an associative array of initial Post field values.
	 **/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields,
			$this->newfields
		);

		parent::__construct( $paramarray );
		if ( isset( $this->fields['tags'] ) )
		{
			$this->tags = $this->parsetags($this->fields['tags']);
			unset( $this->fields['tags'] );
		}
		$this->exclude_fields('id');
		$this->info= new PostInfo ( $this->fields['id'] );
		 /* $this->fields['id'] could be null in case of a new post. If so, the info object is _not_ safe to use till after set_key has been called. Info records can be set immediately in any other case. */
	}

	/**
	 * Return a single requested post.
	 *
	 * <code>
	 * $post = Post::get( array( 'slug' => 'wooga' ) );
	 * </code>
	 *
	 * @param array $paramarray An associated array of parameters, or a querystring
	 * @return Post The first post that matched the given criteria
	 **/
	static function get( $paramarray = array() )
	{
		// Defaults
		$defaults= array (
			'where' => array(
				array(
					'status' => Post::status( 'published' ),
				),
			),
			'fetch_fn' => 'get_row',
		);
		if ( $user = User::identify() ) {
			$defaults['where'][]= array(
				'user_id' => $user->id,
			);
		}
		foreach ( $defaults['where'] as $index => $where ) {
			$defaults['where'][$index]= array_merge( Controller::get_handler()->handler_vars, $where, Utils::get_params( $paramarray ) );
		}
		// make sure we get at most one result
		$defaults['limit']= 1;

		return Posts::get( $defaults );
	}

	/**
	 * Create a post and save it.
	 *
	 * @param array $paramarray An associative array of post fields
	 * @return Post The new Post object
	 **/
	static function create( $paramarray )
	{
		$post= new Post( $paramarray );
		$post->insert();
		return $post;
	}

	/**
	 * Generate a new slug for the post.
	 *
	 * @return string The slug
	 */
	private function setslug()
	{
		// determine the base value from:
		// - the new slug
		if ( isset( $this->newfields['slug']) && $this->newfields['slug'] != '' ) {
			$value= $this->newfields['slug'];
		}
		// - the existing slug
		elseif ( $this->fields['slug'] != '' ) {
			$value= $this->fields['slug'];
		}
		// - the new post title
		elseif ( isset( $this->newfields['title'] ) && $this->newfields['title'] != '' ) {
			$value= $this->newfields['title'];
		}
		// - the existing post title
		elseif ( $this->fields['title'] != '' ) {
			$value= $this->fields['title'];
		}
		// - default
		else {
			$value= 'Post';
		}

		// make sure our slug is unique
		$slug= Plugins::filter('post_setslug', $value);
		$slug= Utils::slugify( $slug );
		$postfix= '';
		$postfixcount= 0;
		do {
			if (! $slugcount = DB::get_row( 'SELECT COUNT(slug) AS ct FROM ' . DB::table('posts') . ' WHERE slug = ?;', array( $slug . $postfix ) )) {
				Utils::debug( DB::get_errors() );
				exit;
			}
			if ( $slugcount->ct != 0 ) $postfix = "-" . ( ++$postfixcount );
		} while ( $slugcount->ct != 0 );

		return $this->newfields['slug'] = $slug . $postfix;
	}

	/**
	 * Generate the GUID for the new post.
	 */
	private function setguid()
	{
		if ( ! isset( $this->newfields['guid'] )
			|| ($this->newfields['guid'] == '')  // GUID is empty
			|| ($this->newfields['guid'] == '//?p=') // GUID created by WP was erroneous (as is too common)
		) {
			$result= 'tag:' . Site::get_url('hostname') . ',' . date('Y') . ':' . $this->setslug() . '/' . time();
			$this->newfields['guid']= $result;
		}
		return $this->newfields['guid'];
	}

	/**
	 * function setstatus
	 * @param mixed the status to set it to. String or integer.
	 * @return integer the status of the post
	 * Sets the status for a post, given a string or integer.
	 */
	private function setstatus($value)
	{
		$statuses= Post::list_post_statuses();
		if( is_numeric($value) && in_array($value, $statuses) ) {
			return $this->newfields['status'] = $value;
		}
		elseif ( array_key_exists( $value, $statuses ) ) {
			return $this->newfields['status'] = Post::status('publish');
		}

		return false;
	}

	private static function parsetags( $tags )
	{
		if ( is_string( $tags ) ) {
			if ( '' === $tags ) {
				return array();
			}
			// dirrty ;)
			$rez= array( '\\"'=>':__unlikely_quote__:', '\\\''=>':__unlikely_apos__:' );
			$zer= array( ':__unlikely_quote__:'=>'"', ':__unlikely_apos__:'=>"'" );
			// escape
			$tagstr= str_replace( array_keys( $rez ), $rez, $tags );
			// match-o-matic
			preg_match_all( '/((("|((?<= )|^)\')\\S([^\\3]*?)\\3((?=[\\W])|$))|[^,])+/', $tagstr, $matches );
			// cleanup
			$tags= array_map( 'trim', $matches[0] );
			$tags= preg_replace( array_fill( 0, count( $tags ), '/^(["\'])(((?!").)+)(\\1)$/'), '$2', $tags );
			// unescape
			$tags= str_replace( array_keys( $zer ), $zer, $tags );
			// hooray
			return $tags;
		}
		elseif ( is_array( $tags ) ) {
			return $tags;
		}
	}

	/**
	 * Save the tags associated to this post into the tags and tags2post tables
	 */
	private function save_tags()
	{
		DB::query( 'DELETE FROM ' . DB::table('tag2post') . ' WHERE post_id = ?', array( $this->fields['id'] ) );
		if ( count($this->tags) == 0) {return;}
		foreach( (array)$this->tags as $tag ) {
			$tag_slug= Utils::slugify( $tag );
			// @todo TODO Make this multi-SQL safe!
			if( DB::get_value( 'SELECT count(*) FROM ' . DB::table('tags') . ' WHERE tag_text = ?', array( $tag ) ) == 0 ) {
				DB::query( 'INSERT INTO ' . DB::table('tags') . ' (tag_text, tag_slug) VALUES (?, ?)', array( $tag, $tag_slug ) );
			}
			DB::query( 'INSERT INTO ' . DB::table('tag2post') . ' (tag_id, post_id) SELECT id AS tag_id, ? AS post_id FROM ' . DB::table('tags') . ' WHERE tag_text = ?',
				array( $this->fields['id'], $tag )
			);
		}
	}

	/**
	 * function insert
	 * Saves a new post to the posts table
	 */
	public function insert()
	{
		$this->newfields[ 'updated' ] = date( 'Y-m-d H:i:s' );
		$this->setslug();
		$this->setguid();

		$allow= true;
		$allow= Plugins::filter('post_insert_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('post_insert_before', $this);

		// Invoke plugins for all fields, since they're all "changed" when inserted
		foreach ( $this->fields as $fieldname => $value ) {
			Plugins::act('post_update_' . $fieldname, $this, ($this->id == 0) ? null : $value, $this->$fieldname );
		}
		// invoke plugins for status changes
		Plugins::act('post_status_' . self::status_name($this->status), $this, null );

		$result = parent::insertRecord( DB::table('posts') );
		$this->newfields['id'] = DB::last_insert_id(); // Make sure the id is set in the post object to match the row id
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->info->commit( DB::last_insert_id() );
		$this->save_tags();
		EventLog::log('New post ' . $this->id . ' (' . $this->slug . ');  Type: ' . Post::type_name($this->content_type) . '; Status: ' . $this->statusname, 'info', 'content', 'habari');
		Plugins::act('post_insert_after', $this);
		return $result;
	}

	/**
	 * function update
	 * Updates an existing post in the posts table
	 */
	public function update()
	{
		$this->updated = date('Y-m-d H:i:s');
		if(isset($this->fields['guid'])) unset( $this->newfields['guid'] );

		$allow= true;
		$allow= Plugins::filter('post_update_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('post_update_before', $this);

		// Call setslug() only when post slug is changed
		if ( isset( $this->newfields['slug'] ) && $this->newfields['slug'] != '' ) {
			if ( $this->fields['slug'] != $this->newfields['slug'] ) {
				$this->setslug();
			}
		}

		// invoke plugins for all fields which have been changed
		// For example, a plugin action "post_update_status" would be
		// triggered if the post has a new status value
		foreach ( $this->newfields as $fieldname => $value ) {
			Plugins::act('post_update_' . $fieldname, $this, $this->fields[$fieldname], $value );
		}

		// invoke plugins for status changes
		if(isset($this->newfields['status']) && $this->fields['status'] != $this->newfields['status']) {
		  Plugins::act('post_status_' . self::status_name($this->newfields['status']), $this, $this->fields['status'] );
		}

		$result = parent::updateRecord( DB::table('posts'), array('id'=>$this->id) );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->save_tags();
		$this->info->commit();

		Plugins::act('post_update_after', $this);
		return $result;
	}

	/**
	 * function delete
	 * Deletes an existing post
	 */
	public function delete()
	{
		$allow= true;
		$allow= Plugins::filter('post_delete_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		// invoke plugins
		Plugins::act('post_delete_before', $this);

		// Delete all comments associated with this post
		if ( $this->comments->count() > 0 ) {
			$this->comments->delete();
		}
		// Delete all info records associated with this post
		if ( isset( $this->info ) ) {
			$this->info->delete_all();
		}
		$result= parent::deleteRecord( DB::table('posts'), array('slug'=>$this->slug) );
		EventLog::log('Post ' . $this->id . ' (' . $this->slug . ') deleted.', 'info', 'content', 'habari');

		// invoke plugins on the after_post_delete action
		Plugins::act('post_delete_after', $this);
		return $result;
	}

	/**
	 * function publish
	 * Updates an existing post to published status
	 * @return boolean True on success, false if not
	 */
	public function publish()
	{
		if ( $this->status == Post::status('published') ) {
			return true;
		}
		$allow= true;
		$allow= Plugins::filter('post_publish_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('post_publish_before', $this);

		$this->status = Post::status('published');
		$this->pubdate= date( 'Y-m-d H:i:s' );
		$result= $this->update();
		EventLog::log('Post ' . $this->id . ' (' . $this->slug . ') published.', 'info', 'content', 'habari');

		// and call any final plugins
		Plugins::act('post_publish_after', $this);
		return $result;
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __get( $name )
	{
		$fieldnames = array_merge( array_keys($this->fields), array('permalink', 'tags', 'comments', 'comment_count', 'comment_feed_link', 'author') );
		if( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			preg_match('/^(.*)_([^_]+)$/', $name, $matches);
			list( $junk, $name, $filter ) = $matches;
		}
		else {
			$filter = false;
		}

		switch($name) {
		case 'statusname':
			$out= self::status_name( $this->status );
			break;
		case 'permalink':
			$out = $this->get_permalink();
			break;
		case 'tags':
			$out = $this->get_tags();
			break;
		case 'comments':
			$out = $this->get_comments();
			break;
		case 'comment_count':
			$out = $this->get_comments()->count();
			break;
		case 'comment_feed_link':
			$out= $this->get_comment_feed_link();
			break;
		case 'author':
			$out = $this->get_author();
			break;
		case 'info':
			$out = $this->get_info();
			break;
		default:
			$out = parent::__get( $name );
			break;
		}
		$out = Plugins::filter( "post_{$name}", $out, $this );
		if( $filter ) {
			$out = Plugins::filter( "post_{$name}_{$filter}", $out, $this );
		}
		return $out;
	}

	/**
	 * function __set
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __set( $name, $value )
	{
		switch($name) {
		case 'pubdate':
			$value = date( 'Y-m-d H:i:s', strtotime( $value ) );
			break;
		case 'tags':
			$this->tags = $this->parsetags( $value );
			return $this->get_tags();
		case 'status':
			return $this->setstatus($value);
		}
		return parent::__set( $name, $value );
	}

	/**
	 * function get_permalink
	 * Returns a permalink for the ->permalink property of this class.
	 * @return string A link to this post.
	 * @todo separate permalink rule?  (Not sure what this means - OW)
	 **/
	private function get_permalink()
	{
		$content_type= Post::type_name( $this->content_type );
		return URL::get(
			array(
				"display_{$content_type}",
			),
			$this,
			false
		);
	}

	/**
	 * function get_tags
	 * Gets the tags for the post
	 * @return &array A reference to the tags array for this post
	 **/
	private function get_tags() {
		if ( empty( $this->tags ) ) {
			$sql= "
				SELECT t.tag_text, t.tag_slug
				FROM " . DB::table('tags') . " t
				INNER JOIN " . DB::table('tag2post') . " t2p
				ON t.id = t2p.tag_id
				WHERE t2p.post_id = ?
				ORDER BY t.tag_slug ASC";
			$result= DB::get_results( $sql, array( $this->fields['id'] ) );
			if ($result)
			{
				foreach ($result as $t)
				{
					$this->tags[$t->tag_slug]= $t->tag_text;
				}
			}
		}
		if ( count( $this->tags ) == 0 ) {
			return '';
		}
		return $this->tags;
	}

	/**
	 * function get_comments
	 * Gets the comments for the post
	 * @return &array A reference to the comments array for this post
	**/
	private function &get_comments()
	{
		if ( ! $this->comments_object ) {
			$this->comments_object= Comments::by_post_id( $this->id );
		}
		return $this->comments_object;
	}

	/**
	 * private function get_comment_feed_link
	 * Returns the permalink for this post's comments Atom feed
	 * @return string The permalink of this post's comments Atom feed
	**/
	private function get_comment_feed_link()
	{
		return URL::get( array( 'atom_entry_comments' ), $this, false );
	}

	/**
	 * function get_info
	 * Gets the info object for this post, which contains data from the postinfo table
	 * related to this post.
	 * @return PostInfo object
	**/
	private function get_info()
	{
		if ( ! $this->info ) {
			// If this post isn't in the database yet...
			if ( $this->id == 0 ) {
				$this->info= new PostInfo();
			}
			else {
				$this->info= new PostInfo( $this->id );
			}
		}
		return $this->info;
	}

	/**
	 * private function get_author()
	 * returns a User object for the author of this post
	 * @return User a User object for the author of the current post
	**/
	private function get_author()
	{
		if ( ! isset( $this->author_object ) ) {
			// XXX for some reason, user_id is a string sometimes?
			$this->author_object= User::get_by_id( $this->user_id );
		}
		return $this->author_object;
	}

	/**
	 * Returns a set of properties used by URL::get to create URLs
	 * @return array Properties of this post used to build a URL
	 */
	public function get_url_args()
	{
		$arr= array( 'content_type_name' => Post::type_name( $this->content_type ) );
		$author= URL::extract_args( $this->author, 'author_' );
		$info= URL::extract_args( $this->info, 'info_' );
		return array_merge( $author, $info, $arr, $this->to_array(), Utils::getdate( strtotime( $this->pubdate ) ) );
	}
}
?>
