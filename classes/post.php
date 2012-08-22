<?php
/**
 * @package Habari
 *
 */

/**
 *
 * Includes an instance of the PostInfo class; for holding inforecords about a Post
 * If the Post object describes an existing post; use the internal info object to
 * get, set, unset and test for existence (isset) of info records.
 * <code>
 * $this->info= new PostInfo( 1 );  // Info records of post with id = 1
 * $this->info->option1= "blah"; // set info record with name "option1" to value "blah"
 * $info_value= $this->info->option1; // get value of info record with name "option1" into variable $info_value
 * if ( isset ( $this->info->option1 ) )  // test for existence of "option1"
 * unset ( $this->info->option1 ); // delete "option1" info record
 * </code>
 *
 * @property integer $id The unique id for this post in the posts table
 * @property string $slug The URL-readable identifier for this post
 * @property string $title The user-supplied title of this post
 * @property string $guid The globally-unique identifier for this post
 * @property string $content The content of this post
 * @property string $cached_content Nobody really knows what this is for
 * @property integer $user_id The User id of the author of this post
 * @property integer $status The integer status of this post
 * @property HabariDateTime $pubdate The published date of this post
 * @property HabariDateTime $updated The last publicly-accessible updated date of this post
 * @property HabariDateTime $modified The last modified date of this post
 * @property integer $content_type The integer representation of the content type of this post
 * @property string $permalink The URL for this single post
 * @property string $statusname The string representation of the status of the post
 * @property string $typename The string representation of the type of the post
 * @property Terms $tags A Terms object holding tag terms for this post
 * @property Comments $comments A Comments object holding Comment objects for this post
 * @property integer $comment_count The number of comments on this post
 * @property integer $approved_comment_cound The number of approved comments on this post
 * @property string $comment_feed_link The URL of the feed for comments on this post
 * @property User $author The User object for the author of this post
 * @property InfoRecords $info The InfoRecords for the postinfo of this post
 *
 */
class Post extends QueryRecord implements IsContent, FormStorage
{
	// static variables to hold post status and post type values
	static $post_status_list = array();
	static $post_type_list_active = array();
	static $post_type_list_all = array();

	private $tags_object = null;
	private $comments_object = null;
	private $author_object = null;
	/** @var array $tokens */
	private $tokens = null;

	/** @var InfoRecords $inforecords */
	private $inforecords = null;

	protected $url_args;
	public $schema;

	/**
	 * returns an associative array of active post types
	 * @param bool $refresh whether to force a refresh of the cached values
	 * @return array An array of post type names => integer values
	 */
	public static function list_active_post_types( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$post_type_list_active ) ) ) {
			return self::$post_type_list_active;
		}
		
		// clear out the previous cache
		self::$post_type_list_active = array( 'any' => 0 );
		
		$sql = 'SELECT * FROM {posttype} WHERE active = 1 ORDER BY id ASC';
		$results = DB::get_results( $sql );
		foreach ( $results as $result ) {
			self::$post_type_list_active[$result->name] = $result->id;
		}
		return self::$post_type_list_active;
	}

	/**
	 * returns an associative array of all post types
	 * @param bool $refresh whether to force a refresh of the cached values
	 * @return array An array of post type names => (integer values, active values)
	 */
	public static function list_all_post_types( $refresh = false )
	{
		if ( ( ! $refresh ) && ( ! empty( self::$post_type_list_all ) ) ) {
			return self::$post_type_list_all;
		}
		
		// clear out the previous cache
		self::$post_type_list_all = array( 'any' => 0 );
		
		$sql = 'SELECT * FROM {posttype} ORDER BY id ASC';
		$results = DB::get_results( $sql );
		foreach ( $results as $result ) {
			self::$post_type_list_all[$result->name] = array(
				'id' => $result->id,
				'active' => $result->active
				);
		}
		return self::$post_type_list_all;
	}

	/**
	 * Activate an existing post type
	 *
	 * @param string $type The post type to activate
	 * @return bool True on success
	 */
	public static function activate_post_type( $type )
	{
		$all_post_types = Post::list_all_post_types( true ); // We force a refresh

		// Check if it exists
		if ( array_key_exists( $type, $all_post_types ) ) {
			if ( ! $all_post_types[$type]['active'] == 1 ) {
				// Activate it
				$sql = 'UPDATE {posttype} SET active = 1 WHERE id = ' . $all_post_types[$type]['id'];
				DB::query( $sql );
			}
			return true;
		}
		else {
			return false; // Doesn't exist
		}
	}

	/**
	 * Deactivate a post type
	 *
	 * @param string $type The post type to deactivate
	 * @return bool True on success
	 */
	public static function deactivate_post_type( $type )
	{
		$active_post_types = Post::list_active_post_types( false ); // We force a refresh

		if ( array_key_exists( $type, $active_post_types ) ) {
			// $type is active so we'll deactivate it
			$sql = 'UPDATE {posttype} SET active = 0 WHERE id = ' . $active_post_types[$type];
			DB::query( $sql );
			return true;
		}
		return false;
	}

	/**
	 * returns an associative array of post statuses
	 * @param mixed $all true to list all statuses, not just external ones, Post to list external and any that match the Post status
	 * @param boolean $refresh true to force a refresh of the cached values
	 * @return array An array of post statuses names => integer values
	 */
	public static function list_post_statuses( $all = true, $refresh = false )
	{
		$statuses = array();
		$statuses['any'] = 0;
		if ( $refresh || empty( self::$post_status_list ) ) {
			
			self::$post_status_list = array( 'any' => 0 );
			
			$sql = 'SELECT * FROM {poststatus} ORDER BY id ASC';
			$results = DB::get_results( $sql );
			self::$post_status_list = $results;
		}
		foreach ( self::$post_status_list as $status ) {
			if ( $all instanceof Post ) {
				if ( ! $status->internal || $status->id == $all->status ) {
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
	 * returns the integer value of the specified post status, or false
	 * @param string|integer $name a post status name or value
	 * @return integer|boolean an integer or boolean false
	 */
	public static function status( $name )
	{
		$statuses = Post::list_post_statuses();
		if ( is_numeric( $name ) && ( false !== in_array( $name, $statuses ) ) ) {
			return $name;
		}
		if ( isset( $statuses[ MultiByte::strtolower( $name ) ] ) ) {
			return $statuses[MultiByte::strtolower( $name ) ];
		}
		return false;
	}

	/**
	 * returns the friendly name of a post status, or null
	 * @param string|integer $status a post status value, or name
	 * @return string|null a string of the status name, or null
	 */
	public static function status_name( $status )
	{
		$statuses = array_flip( Post::list_post_statuses() );
		if ( is_numeric( $status ) && isset( $statuses[$status] ) ) {
			return $statuses[$status];
		}
		if ( false !== in_array( $status, $statuses ) ) {
			return $status;
		}
		return '';
	}

	/**
	 * returns the integer value of the specified post type, or false
	 * @param string|integer $name a post type name or id
	 * @return boolean|integer the id of the type or false if not found
	 */
	public static function type( $name )
	{
		$types = Post::list_active_post_types();
		if ( is_numeric( $name ) && ( false !== in_array( $name, $types ) ) ) {
			return $name;
		}
		if ( isset( $types[ MultiByte::strtolower( $name ) ] ) ) {
			return $types[ MultiByte::strtolower( $name ) ];
		}
		return false;
	}

	/**
	 * returns the friendly name of a post type, or null
	 * @param string|integer $type a post type name or id
	 * @return string the string of the type or emptystring if not found
	 */
	public static function type_name( $type )
	{
		$types = array_flip( Post::list_active_post_types() );
		if ( is_numeric( $type ) && isset( $types[$type] ) ) {
			return $types[$type];
		}
		if ( false !== in_array( $type, $types ) ) {
			return $type;
		}
		return '';
	}

	/**
	 * inserts a new post type into the database, if it doesn't exist
	 * @param string $type The name of the new post type
	 * @param bool $active Whether the new post type is active or not
	 */
	public static function add_new_type( $type, $active = true )
	{
		// refresh the cache from the DB, just to be sure
		$types = self::list_all_post_types( true );

		if ( ! array_key_exists( $type, $types ) ) {
			// Doesn't exist in DB.. add it and activate it.
			DB::query( 'INSERT INTO {posttype} (name, active) VALUES (?, ?)', array( $type, $active ) );
		}
		elseif ( $types[$type]['active'] == 0 ) {
			// Isn't active so we activate it
			self::activate_post_type( $type );
		}
		ACL::create_token( 'post_' . Utils::slugify( $type ), _t( 'Permissions to posts of type "%s"', array( $type ) ), _t( 'Content' ), true );

		// now force a refresh of the caches, so the new/activated type
		// is available for immediate use
		self::list_active_post_types( true );
		self::list_all_post_types( true );
	}

	/**
	 * removes a post type from the database, if it exists and there are no posts
	 * of the type
	 * @param string $type The post type name
	 * @return boolean
	 *   true if post type has been deleted
	 *   false if it has not been deleted (does not exist or there are posts using
	 *   this content type)
	 */
	public static function delete_post_type( $type )
	{
		// refresh the cache from the DB, just to be sure
		$types = self::list_all_post_types( true );

		if ( array_key_exists( $type, $types ) ) {

			// Exists in DB.. check if there are content with this type.
			if ( ! DB::exists( '{posts}', array( 'content_type' => Post::type( $type ) ) ) ) {

				// Finally, remove from database and destroy tokens
				DB::delete( '{posttype}', array( 'name' => $type ) );
				ACL::destroy_token( 'post_' . Utils::slugify( $type ) );

				// now force a refresh of the caches, so the removed type is no longer
				// available for use
				self::list_active_post_types( true );
				self::list_all_post_types( true );

				return true;
			}
		}

		return false;
	}

	/**
	 * inserts a new post status into the database, if it doesn't exist
	 * @param string $status The name of the new post status
	 * @param bool $internal Whether this status is for internal use only.  If true, this status will NOT be presented to the user
	 */
	public static function add_new_status( $status, $internal = false )
	{
		// refresh the cache from the DB, just to be sure
		$statuses = self::list_post_statuses( true );
		if ( ! array_key_exists( $status, $statuses ) ) {
			// let's make sure we only insert an integer
			$internal = intval( $internal );
			DB::query( 'INSERT INTO {poststatus} (name, internal) VALUES (?, ?)', array( $status, $internal ) );
			// force a refresh of the cache, so the new status
			// is available for immediate use
			self::list_post_statuses( true, true );
		}
	}

	/**
	 * Delete a post status
	 * @static
	 * @param string $status The name of the status to delete
	 */
	public static function delete_post_status( $status )
	{
		
		$statuses = self::list_post_statuses( true, true );
		
		if ( array_key_exists( $status, $statuses ) ) {
			
			DB::query( 'DELETE FROM {poststatus} WHERE name = :name', array( ':name' => $status ) );
			
			// force a refresh of the cache, so the status is removed immediately
			self::list_post_statuses( true, true );
			
		}
		
	}

	/**
	 * Return the defined database columns for a Post.
	 * @return array Array of columns in the Post table
	 */
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
			'status' => Post::status( 'draft' ),
			'pubdate' => HabariDateTime::date_create(),
			'updated' => HabariDateTime::date_create(),
			'modified' => HabariDateTime::date_create(),
			'content_type' => Post::type( 'entry' )
		);
	}

	/**
	 * Constructor for the Post class.
	 * @param array $paramarray an associative array of initial Post field values.
	 */
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields
		);

		parent::__construct( $paramarray );
		if ( isset( $this->fields['tags'] ) ) {
			$this->tags_object = Terms::parse( $this->fields['tags'], 'Tag', Tags::vocabulary() );
			unset( $this->fields['tags'] );
		}

		$this->exclude_fields( 'id' );
		/* $this->fields['id'] could be null in case of a new post. If so, the info object is _not_ safe to use till after set_key has been called. Info records can be set immediately in any other case. */
	}

	/**
	 * Register plugin hooks
	 * @static
	 */
	public static function __static()
	{
		Pluggable::load_hooks('Post');
	}

	/**
	 * Return a single requested post.
	 *
	 * <code>
	 * $post= Post::get( array( 'slug' => 'wooga' ) );
	 * </code>
	 *
	 * @param array $paramarray An associative array of parameters, or a querystring
	 * @return Post The first post that matched the given criteria
	 */
	static function get( $paramarray = array() )
	{
		// Defaults
		$defaults = array (
			'fetch_fn' => 'get_row',
		);
		if(is_array($paramarray)) {
			$defaults = array_merge( $defaults, Utils::get_params( $paramarray ) );
		}
		elseif(is_numeric($paramarray)) {
			$defaults['id'] = $paramarray;
		}
		elseif(is_string($paramarray)) {
			$defaults['slug'] = $paramarray;
		}

		// make sure we get at most one result
		$defaults['limit'] = 1;

		return Posts::get( $defaults );
	}

	/**
	 * Create a post and save it.
	 *
	 * @param array $paramarray An associative array of post fields
	 * @return Post The new Post object
	 */
	static function create( $paramarray )
	{
		$post = new Post( $paramarray );
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
		// If the slug is new and has a length
		if ( isset( $this->newfields['slug'] ) && $this->newfields['slug'] != '' ) {
			$value = $this->newfields['slug'];
		}
		// - the new empty slug whilst in draft or progressing directly to published or scheduled from draft.
		// - Also allow changing of slug whilst in scheduled state
		//
		// This happens when a draft is being updated, or a post is being directly published or scheduled,
		// or an existing scheduled or published post is being updated, but not made into a draft
		//
		// If a new slug is set, and it doesn't have a length
		elseif ( isset( $this->newfields['slug'] ) && $this->newfields['slug'] == '' ) {
			// If the existing status of the post is draft, no matter what status it is being changed to
			if ( $this->fields['status'] == Post::status( 'draft' )
				|| (
					// or the existing status is not draft and the new status is not draft
					$this->fields['status'] != Post::status( 'draft' ) && $this->newfields['status'] != Post::status( 'draft' )
				)
			) {
				// And a new title is set, use the new title
				if ( isset( $this->newfields['title'] ) && $this->newfields['title'] != '' ) {
					$value = $this->newfields['title'];
				}
				// Otherwise, use the existing title
				else {
					$value = $this->fields['title'];
				}
			}
		}
		// - the existing slug
		//  If there is an existing slug, and it has a length
		elseif ( $this->fields['slug'] != '' ) {
			$value = $this->fields['slug'];
		}
		// - the new post title
		// If there is a new title, and it has a length
		elseif ( isset( $this->newfields['title'] ) && $this->newfields['title'] != '' ) {
			$value = $this->newfields['title'];
		}
		// - the existing post title
		// If there is an existing title, and it has a length
		elseif ( $this->fields['title'] != '' ) {
			$value = $this->fields['title'];
		}
		// - default
		//Nothing else worked. Default to 'Post'
		else {
			$value = 'Post';
		}

		// make sure our slug is unique
		$slug = Plugins::filter( 'post_setslug', $value );
		$slug = Utils::slugify( $slug );
		$postfix = '';
		$postfixcount = 0;
		do {
			if ( ! $slugcount = DB::get_row( 'SELECT COUNT(slug) AS ct FROM {posts} WHERE slug = ?;', array( $slug . $postfix ) ) ) {
				Utils::debug( DB::get_errors() );
				exit;
			}
			if ( $slugcount->ct != 0 ) {
				$postfix = "-" . ( ++$postfixcount );
			}
		} while ( $slugcount->ct != 0 );

		return $this->newfields['slug'] = $slug . $postfix;
	}

	/**
	 * Generate the GUID for the new post.
	 */
	private function setguid()
	{
		if ( ! isset( $this->newfields['guid'] )
			|| ( $this->newfields['guid'] == '' )  // GUID is empty
			|| ( $this->newfields['guid'] == '//?p=' ) // GUID created by WP was erroneous (as is too common)
		) {
			$result = 'tag:' . Site::get_url( 'hostname' ) . ',' . date( 'Y' ) . ':' . rawurlencode( $this->setslug() ) . '/' . time();
			$this->newfields['guid'] = $result;
		}
		return $this->newfields['guid'];
	}

	/**
	 * Sets the status for a post, given a string or integer.
	 * @param string|integer $value the status to set it to
	 * @return integer|boolean the status of the post, or false if the new status isn't valid
	 */
	private function setstatus( $value )
	{
		$statuses = Post::list_post_statuses();
		$fieldname = isset( $this->fields['status'] ) ? 'newfields' : 'fields';
		if ( is_numeric( $value ) && in_array( $value, $statuses ) ) {
			return $this->{"$fieldname"}['status'] = $value;
		}
		elseif ( array_key_exists( $value, $statuses ) ) {
			return $this->{"$fieldname"}['status'] = Post::status( $value );
		}

		return false;
	}


	/**
	 * Save the tags associated to this post into the terms and object_terms tables
	 * @return bool True on success
	 */
	private function save_tags()
	{
		return Tags::save_associations( $this->get_tags(), $this->id );
	}

	/**
	 * Get the schema data for this post
	 * @return array An array of schema data for this post
	 */
	public function get_schema_map()
	{
		if(empty($this->schema)) {
			$default_fields = Post::default_fields();
			$schema = array('posts' => array_combine(array_keys($default_fields), array_keys($default_fields)));
			$schema['*'] = array();
			$fields = array_merge( $this->fields, $this->newfields );
			foreach($fields as $field => $value) {
				if(!isset($default_fields[$field])) {
					$schema['*'][$field] = $field;
				}
			}
			$schema = Plugins::filter('post_schema_map_' . Utils::slugify(Post::type_name($fields['content_type']), '_'), $schema, $this);
			$schema = Plugins::filter('post_schema_map', $schema, $this);
			$this->schema = $schema;
		}
		return $this->schema;
	}

	/**
	 * Saves a new post to the posts table
	 * @return int|null The new post id on success, or false on failure
	 */
	public function insert()
	{
		$this->newfields['updated'] = HabariDateTime::date_create();
		$this->newfields['modified'] = $this->newfields['updated'];
		$this->setguid();
		
		if ( $this->pubdate->int > HabariDateTime::date_create()->int && $this->status == Post::status( 'published' ) ) {
			$this->status = Post::status( 'scheduled' );
		}

		$allow = true;
		$allow = Plugins::filter( 'post_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return false;
		}
		
		Plugins::act( 'post_insert_before', $this );

		// Invoke plugins for all fields, since they're all "changed" when inserted
		foreach ( $this->fields as $fieldname => $value ) {
			Plugins::act( 'post_update_' . $fieldname, $this, ( $this->id == 0 ) ? null : $value, $this->$fieldname );
		}
		// invoke plugins for status changes
		Plugins::act( 'post_status_' . self::status_name( $this->status ), $this, null );


		$result = parent::insertRecord( 'posts', $this->get_schema_map() );
		$this->newfields['id'] = $result; // Make sure the id is set in the post object to match the row id
		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();
		$this->info->commit( DB::last_insert_id() );
		$this->save_tags();
		// This fires __call() which dispatches to any plugins on action_post_call_create_default_permissions()
		$this->create_default_permissions();
		$this->create_default_tokens();
		EventLog::log( _t( 'New post %1$s (%2$s);  Type: %3$s; Status: %4$s', array( $this->id, $this->slug, Post::type_name( $this->content_type ), $this->statusname ) ), 'info', 'content', 'habari' );
		Plugins::act( 'post_insert_after', $this );

		//scheduled post
		if ( $this->status == Post::status( 'scheduled' ) ) {
			Posts::update_scheduled_posts_cronjob();
		}

		return $result;
	}

	/**
	 * Updates an existing post in the posts table
	 * @param bool $minor Indicates if this is a major or minor update
	 * @return bool True on success
	 */
	public function update( $minor = true )
	{
		$this->modified = HabariDateTime::date_create();
		if ( ! $minor && $this->status != Post::status( 'draft' ) ) {
			$this->updated = $this->modified;
		}

		if ( isset( $this->fields['guid'] ) ) {
			unset( $this->newfields['guid'] );
		}
		
		if ( $this->pubdate->int > HabariDateTime::date_create()->int && $this->status == Post::status( 'published' ) ) {
			$this->status = Post::status( 'scheduled' );
		}

		$allow = true;
		$allow = Plugins::filter( 'post_update_allow', $allow, $this );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act( 'post_update_before', $this );

		$this->newfields = Plugins::filter( 'post_update_change', $this->newfields, $this, $this->fields);

		// Call setslug() only when post slug is changed
		if ( isset( $this->newfields['slug'] ) ) {
			if ( $this->fields['slug'] != $this->newfields['slug'] ) {
				$this->setslug();
			}
		}

		// invoke plugins for all fields which have been changed
		// For example, a plugin action "post_update_status" would be
		// triggered if the post has a new status value
		foreach ( $this->newfields as $fieldname => $value ) {
			Plugins::act( 'post_update_' . $fieldname, $this, $this->fields[$fieldname], $value );
		}

		// invoke plugins for status changes
		if ( isset( $this->newfields['status'] ) && $this->fields['status'] != $this->newfields['status'] ) {
			Plugins::act( 'post_status_' . self::status_name( $this->newfields['status'] ), $this, $this->fields['status'] );
		}

		$result = parent::updateRecord( 'posts', array( 'id' => $this->id ), post::get_schema_map() );

		//scheduled post
		if ( $this->fields['status'] == Post::status( 'scheduled' ) || $this->status == Post::status( 'scheduled' ) ) {
			Posts::update_scheduled_posts_cronjob();
		}

		$this->fields = array_merge( $this->fields, $this->newfields );
		$this->newfields = array();
		$this->save_tags();
		$this->info->commit();
		Plugins::act( 'post_update_after', $this );
		return $result;
	}

	/**
	 * Deletes this post instance
	 * @return bool True on success
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter( 'post_delete_allow', $allow, $this );
		if ( ! $allow ) {
			return false;
		}
		// invoke plugins
		Plugins::act( 'post_delete_before', $this );

		// delete all the tags associated with this post
		Tags::save_associations( new Terms(), $this->id );

		// Delete all comments associated with this post
		if ( $this->comments->count() > 0 ) {
			$this->comments->delete();
		}
		// Delete all info records associated with this post
			$this->info->delete_all();
		// Delete all post_tokens associated with this post
		$this->delete_tokens();

		$result = parent::deleteRecord( 'posts', array( 'slug'=>$this->slug ) );
		EventLog::log( _t( 'Post %1$s (%2$s) deleted.', array( $this->id, $this->slug ) ), 'info', 'content', 'habari' );

		//scheduled post
		if ( $this->status == Post::status( 'scheduled' ) ) {
			Posts::update_scheduled_posts_cronjob();
		}

		// invoke plugins on the after_post_delete action
		Plugins::act( 'post_delete_after', $this );
		return $result;
	}

	/**
	 * Updates an existing post to published status
	 * @return boolean True on success, false if not
	 */
	public function publish()
	{
		if ( $this->status == Post::status( 'published' ) ) {
			return true;
		}
		$allow = true;
		$allow = Plugins::filter( 'post_publish_allow', $allow, $this );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act( 'post_publish_before', $this );

		if ( $this->status != Post::status( 'scheduled' ) ) {
			$this->pubdate = HabariDateTime::date_create();
		}

		if ( $this->status == Post::status( 'scheduled' ) ) {
			$msg = _t( 'Scheduled Post %1$s (%2$s) published at %3$s.', array( $this->id, $this->slug, $this->pubdate->format() ) );
		}
		else {
			$msg = _t( 'Post %1$s (%2$s) published.', array( $this->id, $this->slug ) );
		}

		$this->status = Post::status( 'published' );
		$result = $this->update( false );
		EventLog::log( $msg, 'info', 'content', 'habari' );

		// and call any final plugins
		Plugins::act( 'post_publish_after', $this );
		return $result;
	}

	/**
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string $name Name of property to return
	 * @return mixed The requested field value
	 */
	public function __get( $name )
	{
		// some properties are considered special and accidentally filtering them would be bad, so we exclude those
		$fieldnames = array_merge( array_keys( $this->fields ), array( 'permalink', 'tags', 'comments', 'comment_count', 'approved_comment_count', 'comment_feed_link', 'author', 'editlink', 'info' ) );
		$filter = false;
		if ( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			$field_matches = implode('|', $fieldnames);
			if(preg_match( '/^(' . $field_matches . ')_(.+)$/', $name, $matches )) {
				list( $junk, $name, $filter )= $matches;
			}
		}

		switch ( $name ) {
			case 'statusname':
				$out = self::status_name( $this->status );
				break;
			case 'typename':
				$out = self::type_name( $this->content_type );
				break;
			case 'permalink':
				$out = $this->get_permalink();
				break;
			case 'editlink':
				$out = $this->get_editlink();
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
			case 'approved_comment_count':
				$out = Comments::count_by_id( $this->id );
				break;
			case 'comment_feed_link':
				$out = $this->get_comment_feed_link();
				break;
			case 'author':
				$out = $this->get_author();
				break;
			case 'info':
				$out = $this->get_info();
				break;
			case 'excerpt':
				$field = 'content' . ($filter ? '_' . $filter : '_out');
				$out = $this->__get($field);
				if(!Plugins::implemented('post_excerpt', 'filter')) {
					$out = Format::more($out, $this, Options::get('excerpt_settings', array('max_paragraphs' => 2)));
				}
				break;
			default:
				$out = parent::__get( $name );
				break;
		}
		if ( $filter != 'internal' ) {
			$out = Plugins::filter( "post_get", $out, $name, $this );
			$out = Plugins::filter( "post_{$name}", $out, $this );
		}
		if ( $filter ) {
			$out = Plugins::filter( "post_{$name}_{$filter}", $out, $this );
		}
		return $out;
	}

	/**
	 * Overrides QueryRecord __set to implement custom object properties
	 * @param string $name Name of property to return
	 * @param mixed $value Value to set
	 * @return mixed The requested field value
	 */
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'pubdate':
			case 'updated':
			case 'modified':
				if ( !( $value instanceOf HabariDateTime ) ) {
					$value = HabariDateTime::date_create( $value );
				}
				break;
			case 'tags':
				if ( $value instanceof Terms ) {
					return $this->tags_object = $value;
				}
				elseif ( is_array( $value ) ) {
					return $this->tags_object = new Terms($value);
				}
				else {
					return $this->tags_object = Terms::parse( $value, 'Term', Tags::vocabulary() );
				}
			case 'status':
				return $this->setstatus( $value );
		}
		return parent::__set( $name, $value );
	}

	/**
	 * Handle calls to this Post object that are implemented by plugins
	 * @param string $name The name of the function called
	 * @param array $args Arguments passed to the function call
	 * @return mixed The value returned from any plugin filters, null if no value is returned
	 */
	public function __call( $name, $args )
	{
		array_unshift( $args, 'post_call_' . $name, null, $this );
		return call_user_func_array( array( 'Plugins', 'filter' ), $args );
	}

	/**
	 * A field accessor that doesn't filter, for use in plugins that filter field values
	 * @param string $name Name of the field to get
	 * @return mixed Value of the field, unfiltered
	 */
	public function get_raw_field( $name )
	{
		return parent::__get( $name );
	}

	/**
	 * Returns a form for editing this post
	 * @param string $context The context the form is being created in, most often 'admin'
	 * @return FormUI A form appropriate for creating and updating this post.
	 */
	public function get_form( $context )
	{
		$form = new FormUI( 'create-content' );
		$form->class[] = 'create';

		$newpost = ( 0 === $this->id );

		// If the post has already been saved, add a link to its permalink
		if ( !$newpost ) {
			$post_links = $form->append( 'wrapper', 'post_links' );
			$permalink = ( $this->status != Post::status( 'published' ) ) ? $this->permalink . '?preview=1' : $this->permalink;
			$post_links->append( 'static', 'post_permalink', '<a href="'. $permalink .'" class="viewpost" >'.( $this->status != Post::status( 'published' ) ? _t( 'Preview Post' ) : _t( 'View Post' ) ).'</a>' );
			$post_links->class ='container';
		}

		// Store this post instance into a hidden field for later use when saving data
		$form->append( 'hidden', 'post', $this, _t( 'Title' ), 'admincontrol_text' );

		// Create the Title field
		$form->append( 'text', 'title', 'null:null', _t( 'Title' ), 'admincontrol_text' );
		$form->title->class[] = 'important';
		$form->title->class[] = 'check-change';
		$form->title->tabindex = 1;
		$form->title->value = $this->title_internal;

		// Create the silos
		if ( count( Plugins::get_by_interface( 'MediaSilo' ) ) ) {
			$form->append( 'silos', 'silos' );
			$form->silos->silos = Media::dir();
		}

		// Create the Content field
		$form->append( 'textarea', 'content', 'null:null', _t( 'Content' ), 'admincontrol_textarea' );
		$form->content->class[] = 'resizable';
		$form->content->class[] = 'check-change';
		$form->content->tabindex = 2;
		$form->content->value = $this->content_internal;
		$form->content->raw = true;

		// Create the tags field
		$form->append( 'text', 'tags', 'null:null', _t( 'Tags, separated by, commas' ), 'admincontrol_text' );
		$form->tags->class = 'check-change';
		$form->tags->tabindex = 3;

		$tags = (array)$this->get_tags();
		array_walk($tags, function(&$element, $key) {
			$element->term_display = MultiByte::strpos( $element->term_display, ',' ) === false ? $element->term_display : $element->tag_text_searchable;
		});

		$form->tags->value = implode( ', ', $tags );

		// Create the splitter
		$publish_controls = $form->append( 'tabs', 'publish_controls' );

		// Create the publishing controls
		// pass "false" to list_post_statuses() so that we don't include internal post statuses
		$statuses = Post::list_post_statuses( $this );
		unset( $statuses[array_search( 'any', $statuses )] );
		$statuses = Plugins::filter( 'admin_publish_list_post_statuses', $statuses );

		$settings = $publish_controls->append( 'fieldset', 'settings', _t( 'Settings' ) );

		$settings->append( 'select', 'status', 'null:null', _t( 'Content State' ), array_flip( $statuses ), 'tabcontrol_select' );
		$settings->status->value = $this->status;

		// hide the minor edit checkbox if the post is new
		if ( $newpost ) {
			$settings->append( 'hidden', 'minor_edit', 'null:null' );
			$settings->minor_edit->value = false;
		}
		else {
			$settings->append( 'checkbox', 'minor_edit', 'null:null', _t( 'Minor Edit' ), 'tabcontrol_checkbox' );
			$settings->minor_edit->value = true;
			$form->append( 'hidden', 'modified', 'null:null' )->value = $this->modified;
		}

		$settings->append( 'checkbox', 'comments_enabled', 'null:null', _t( 'Comments Allowed' ), 'tabcontrol_checkbox' );
		$settings->comments_enabled->value = $this->info->comments_disabled ? false : true;

		$settings->append( 'text', 'pubdate', 'null:null', _t( 'Publication Time' ), 'tabcontrol_text' );
		$settings->pubdate->value = $this->pubdate->format( 'Y-m-d H:i:s' );
		$settings->pubdate->helptext = _t( 'YYYY-MM-DD HH:MM:SS' );

		$settings->append( 'hidden', 'updated', 'null:null' );
		$settings->updated->value = $this->updated->int;

		$settings->append( 'text', 'newslug', 'null:null', _t( 'Content Address' ), 'tabcontrol_text' );
		$settings->newslug->id = 'newslug';
		$settings->newslug->value = $this->slug;

		// Create the button area
		$buttons = $form->append( 'fieldset', 'buttons' );
		$buttons->template = 'admincontrol_buttons';
		$buttons->class[] = 'container';
		$buttons->class[] = 'buttons';
		$buttons->class[] = 'publish';

		// Create the Save button
		$require_any = array( 'own_posts' => 'create', 'post_any' => 'create', 'post_' . Post::type_name( $this->content_type ) => 'create' );
		if ( ( $newpost && User::identify()->can_any( $require_any ) ) || ( !$newpost && ACL::access_check( $this->get_access(), 'edit' ) ) ) {
			$buttons->append( 'submit', 'save', _t( 'Save' ), 'admincontrol_submit' );
			$buttons->save->tabindex = 4;
		}

		// Add required hidden controls
		$form->append( 'hidden', 'content_type', 'null:null' );
		$form->content_type->id = 'content_type';
		$form->content_type->value = $this->content_type;
		$form->append( 'hidden', 'post_id', 'null:null' );
		$form->post_id->id = 'id';
		$form->post_id->value = $this->id;
		$form->append( 'hidden', 'slug', 'null:null' );
		$form->slug->value = $this->slug;
		$form->slug->id = 'originalslug';

		$form->on_success(array($this, 'form_publish_success'));

		// Let plugins alter this form
		Plugins::act( 'form_publish', $form, $this, $context );
		$content_types = array_flip(Post::list_active_post_types());
		Plugins::act( 'form_publish_' . Utils::slugify($content_types[$this->content_type], '_'), $form, $this, $context );

		// Return the form object
		return $form;
	}

	public function form_publish_success( FormUI $form )
	{
		$user = User::identify();

		// Get the Post object from the hidden 'post' control on the form
		/** @var Post $post */
		$post = $form->post->storage;

		// Do some permission checks
		// @todo REFACTOR: These probably don't work and should be refactored to use validators on the form fields instead
		// sorry, we just don't allow changing posts you don't have rights to
		if ( $post->id != 0 && ! ACL::access_check( $post->get_access(), 'edit' ) ) {
			Session::error( _t( 'You don\'t have permission to edit that post' ) );
			$this->get_blank();
		}

		// sorry, we just don't allow changing content types to types you don't have rights to
		$type = 'post_' . Post::type_name( $form->content_type->value );
		if ( $form->content_type->value != $post->content_type && ( $user->cannot( $type ) || ! $user->can_any( array( 'own_posts' => 'edit', 'post_any' => 'edit', $type => 'edit' ) ) ) ) {
			Session::error( _t( 'Changing content types is not allowed' ) );
			// @todo This isn't ideal at all, since it loses all of the changes...
			Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
			exit;
		}

		// If we're creating a new post...
		if( $post->id == 0 ) {
			// check the user can create new posts of the set type.
			$type = 'post_'  . Post::type_name( $form->content_type->value );
			if ( ACL::user_cannot( $user, $type ) || ( ! ACL::user_can( $user, 'post_any', 'create' ) && ! ACL::user_can( $user, $type, 'create' ) ) ) {
				Session::error( _t( 'Creating that post type is denied' ) );
				Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
				exit;
			}

			// Only the original author is associated with a new post
			$post->user_id = $user->id;
		}
		// If we're updating an existing post...
		else {
			// check the user can create new posts of the set type.
			$type = 'post_'  . Post::type_name( $form->content_type->value );
			if ( ! ACL::access_check( $post->get_access(), 'edit' ) ) {
				Session::error( _t( 'Editing that post type is denied' ) );
				Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
				exit;
			}

			// Verify that the post hasn't already been updated since the form was loaded
			if ( $post->modified != $form->modified->value ) {
				Session::notice( _t( 'The post %1$s was updated since you made changes.  Please review those changes before overwriting them.', array( sprintf( '<a href="%1$s">\'%2$s\'</a>', $post->permalink, Utils::htmlspecialchars( $post->title ) ) ) ) );
				Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
				exit;
			}

			// Prevent a published post from having its slug zeroed
			if ( $form->newslug->value == '' && $post->status == Post::status( 'published' ) ) {
				Session::notice( _t( 'A post slug cannot be empty. Keeping old slug.' ) );
				$form->newslug->value = $form->slug->value;
			}
		}

		// if not previously published and the user wants to publish now, change the pubdate to the current date/time unless a date has been explicitly set
		if ( ( $post->status != Post::status( 'published' ) )
			&& ( $form->status->value == Post::status( 'published' ) )
			&& ( HabariDateTime::date_create( $form->pubdate->value )->int == $form->updated->value )
		) {
			$post->pubdate = HabariDateTime::date_create();
		}
		// else let the user change the publication date.
		//  If previously published and the new date is in the future, the post will be unpublished and scheduled. Any other status, and the post will just get the new pubdate.
		// This will result in the post being scheduled for future publication if the date/time is in the future and the new status is published.
		else {
			$post->pubdate = HabariDateTime::date_create( $form->pubdate->value );
		}

		// Minor updates are when the user has checked the minor update box and the post isn't in draft or new
		$minor = $form->minor_edit->value && ( $post->status != Post::status( 'draft' ) ) && $post->id != 0;

		// Don't try to update form values that have been removed by plugins,
		// look for these fields before committing their values to the post
		$expected = array(
			'title' => 'title',
			'tags' => 'tags',
			'content' => 'content',
			'slug' => 'newslug',
			'content_type' => 'content_type',
			'status' => 'status',
		);
		foreach ( $expected as $field => $control ) {
			if ( isset( $form->$field ) ) {
				$post->$field = $form->$control->value;
			}
		}

		// This seems cheesy
		$post->info->comments_disabled = !$form->comments_enabled->value;

		// This plugin hook allows changes to be made to the post object prior to its save to the database
		Plugins::act( 'publish_post', $post, $form );

		// Insert or Update
		if($post->id == 0) {
			$post->insert();
		}
		else {
			$post->update( $minor );
		}
		// Calling $form->save() calls ->save() on any controls that might have been added to the form by plugins
		$form->save();

		$permalink = ( $post->status != Post::status( 'published' ) ) ? $post->permalink . '?preview=1' : $post->permalink;
		Session::notice( _t( 'The post %1$s has been saved as %2$s.', array( sprintf( '<a href="%1$s">\'%2$s\'</a>', $permalink, Utils::htmlspecialchars( $post->title ) ), Post::status_name( $post->status ) ) ) );
		Utils::redirect( URL::get( 'admin', 'page=publish&id=' . $post->id ) );
	}


	/**
	 * Manage this post's comment form
	 *
	 * @param string $context The context in which the form is used, used to facilitate plugin alteration of the comment form in different circumstances
	 * @return FormUI The comment form for this post
	 */
	public function comment_form( $context = 'public' )
	{
		// Handle comment submissions and default commenter id values
		$cookie = 'comment_' . Options::get( 'GUID' );
		$commenter_name = '';
		$commenter_email = '';
		$commenter_url = '';
		$commenter_content = '';
		$user = User::identify();
		if ( isset( $_SESSION['comment'] ) ) {
			$details = Session::get_set( 'comment' );
			$commenter_name = $details['name'];
			$commenter_email = $details['email'];
			$commenter_url = $details['url'];
			$commenter_content = $details['content'];
		}
		elseif ( $user->loggedin ) {
			$commenter_name = $user->displayname;
			$commenter_email = $user->email;
			$commenter_url = Site::get_url( 'habari' );
		}
		elseif ( isset( $_COOKIE[$cookie] ) ) {
			// limit to 3 elements so a # in the URL stays appended
			$commenter = explode( '#', $_COOKIE[ $cookie ], 3 );
			
			// make sure there are always at least 3 elements
			$commenter = array_pad( $commenter, 3, null );
			
			list( $commenter_name, $commenter_email, $commenter_url ) = $commenter;
		}

		// Now start the form.
		$form = new FormUI( 'comment-' . $context, 'comment' );
		$form->class[] = $context;
		$form->class[] = 'commentform';

		// Enforce commenting rules
		if(Options::get('comments_disabled')) {
			$form->append(new FormControlStatic('message', _t('Comments are disabled site-wide.')));
			$form->class[] = 'comments_disabled';
			$form->set_option( 'form_action', '/' );
		}
		elseif($this->info->comments_disabled) {
			$form->append(new FormControlStatic('message', _t('Comments for this post are disabled.')));
			$form->class[] = 'comments_disabled';
			$form->set_option( 'form_action', '/' );
		}
		elseif(Options::get('comments_require_logon') && !User::identify()->loggedin) {
			$form->append(new FormControlStatic('message', _t('Commenting on this site requires authentication.')));
			$form->class[] = 'comments_require_logon';
			$form->set_option( 'form_action', '/' );
		}
		elseif(User::identify()->cannot('comment')) {
			$form->append(new FormControlStatic('message', _t('You do not have permission to comment on this site.')));
			$form->class[] = 'comments_require_permission';
			$form->set_option( 'form_action', '/' );
		}
		else {

			$form->set_option( 'form_action', URL::get( 'submit_feedback', array( 'id' => $this->id ) ) );

			// Create the Name field
			$form->append(
				'text',
				'cf_commenter',
				'null:null',
				_t( 'Name <span class="required">*Required</span>' ),
				'formcontrol_text'
			)->add_validator( 'validate_required', _t( 'The Name field value is required' ) )
			->id = 'comment_name';
			$form->cf_commenter->tabindex = 1;
			$form->cf_commenter->value = $commenter_name;

			// Create the Email field
			$form->append(
				'text',
				'cf_email',
				'null:null',
				_t( 'Email' ),
				'formcontrol_text'
			)->add_validator( 'validate_email', _t( 'The Email field value must be a valid email address' ) )
			->id = 'comment_email';
			$form->cf_email->type = 'email';
			$form->cf_email->tabindex = 2;
			if ( Options::get( 'comments_require_id' ) == 1 ) {
				$form->cf_email->add_validator(  'validate_required', _t( 'The Email field value must be a valid email address' ) );
				$form->cf_email->caption = _t( 'Email <span class="required">*Required</span>' );
			}
			$form->cf_email->value = $commenter_email;

			// Create the URL field
			$form->append(
				'text',
				'cf_url',
				'null:null',
				_t( 'Website' ),
				'formcontrol_text'
			)->add_validator( 'validate_url', _t( 'The Website field value must be a valid URL' ) )
			->id = 'comment_url';
			$form->cf_url->type = 'url';
			$form->cf_url->tabindex = 3;
			$form->cf_url->value = $commenter_url;

			// Create the Comment field
			$form->append(
				'text',
				'cf_content',
				'null:null',
				_t( 'Comment' ),
				'formcontrol_textarea'
			)->add_validator( 'validate_required', _t( 'The Comment field value is required' ) )
			->id = 'comment_content';
			$form->cf_content->tabindex = 4;
			$form->cf_content->value = $commenter_content;

			// Create the Submit button
			$form->append( 'submit', 'cf_submit', _t( 'Submit' ), 'formcontrol_submit' );
			$form->cf_submit->tabindex = 5;
		}

		// Let plugins alter this form
		Plugins::act( 'form_comment', $form, $this, $context );

		// Return the form object
		return $form;
	}


	/**
	 * Returns a URL for the ->permalink property of this class.
	 * @return string A URL to this post.
	 * @todo separate permalink rule?  (Not sure what this means - OW)
	 */
	private function get_permalink()
	{
		$content_type = Post::type_name( $this->content_type );
		return URL::get(
			array(
				"display_{$content_type}",
				"display_post"
			),
			$this,
			false
		);
	}
	
	/**
	 * Returns a list of CSS classes for the post
	 * 
	 * @param string|array $append Additional classes that should be added to the ones generated
 	 * @return string The resultant classes
	 */
	public function css_class ( $append = array() ) {
		
		$classes = $append;
		
		$classes[] = 'post';
		$classes[] = 'post-' . $this->id;
		$classes[] = 'type-' . $this->typename;
		$classes[] = 'status-' . $this->statusname;
				
		foreach ( $this->tags as $tag ) {
			$classes[] = 'tag-' . $tag->term;
		}

		return implode( ' ', $classes );
		
	}

	/**
	 * Returns a URL for the ->editlink property of this class.
	 * @return string A url to edit this post in the admin.
	 */
	private function get_editlink()
	{
		return URL::get( 'admin', 'page=publish&id=' . $this->id );
	}

	/**
	 * function get_tags
	 * Gets the tags for the post
	 * @return Terms The tags object for this post
	 */
	private function get_tags()
	{
		if ( $this->tags_object == null ) {
			$result = Tags::vocabulary()->get_associations( $this->id );
			if ( $result ) {
				$tags = new Terms($result);
			}
			else {
				$tags = new Terms();
			}
			$this->tags_object = $tags;
		}
		else {
			$tags = $this->tags_object;
		}
		return $tags;

	}


	/**
	 * Gets the comments for the post
	 * @return Comments A reference to the comments array for this post
	 */
	private function &get_comments()
	{
		if ( ! $this->comments_object ) {
			$this->comments_object = Comments::by_post_id( $this->id );
		}
		return $this->comments_object;
	}

	/**
	 * private function get_comment_feed_link
	 * Returns the permalink for this post's comments Atom feed
	 * @return string The permalink of this post's comments Atom feed
	 */
	private function get_comment_feed_link()
	{
		$content_type = Post::type_name( $this->content_type );
		return URL::get( array( "atom_feed_{$content_type}_comments" ), $this, false );
	}

	/**
	 * function get_info
	 * Gets the info object for this post, which contains data from the postinfo table
	 * related to this post.
	 * @return PostInfo object
	 */
	private function get_info()
	{
		if ( ! isset( $this->inforecords ) ) {
			// If this post isn't in the database yet...
			if (  0 == $this->id ) {
				$this->inforecords = new PostInfo();
			}
			else {
				$this->inforecords = new PostInfo( $this->id );
			}
		}
		else {
			$this->inforecords->set_key( $this->id );
		}
		return $this->inforecords;
	}

	/**
	 * private function get_author()
	 * returns a User object for the author of this post
	 * @return User a User object for the author of the current post
	 */
	private function get_author()
	{
		if ( ! isset( $this->author_object ) ) {
			// XXX for some reason, user_id is a string sometimes?
			$this->author_object = User::get_by_id( $this->user_id );
		}
		return $this->author_object;
	}

	/**
	 * Returns a set of properties used by URL::get to create URLs
	 * @return array Properties of this post used to build a URL
	 */
	public function get_url_args()
	{
		if ( !$this->url_args ) {
			$arr = array( 'content_type_name' => Post::type_name( $this->content_type ) );
			$author = URL::extract_args( $this->author, 'author_' );
			$info = URL::extract_args( $this->info, 'info_' );
			$this->url_args = array_merge( $author, $info, $arr, $this->pubdate->getdate() );
		}
		return array_merge($this->url_args, parent::get_url_args());
	}

	/**
	 * Returns the ascending post, relative to this post, according to params
	 * @param null $params The params by which to work out what is the ascending post
	 * @return Post The ascending post
	 */
	public function ascend( $params = null )
	{
		return Posts::ascend( $this, $params );
	}

	/**
	 * Returns the descending post, relative to this post, according to params
	 * @param $params The params by which to work out what is the descending post
	 * @return Post The descending post
	 */
	public function descend( $params = null )
	{
		return Posts::descend( $this, $params );
	}

	/**
	 * Return the content type of this object
	 *
	 * @return array An array of content types that this object represents, starting with the most specific
	 * @see IsContent
	 */
	public function content_type()
	{
		$defaults = array( Post::type_name( $this->content_type ), 'Post' );
		$result = Plugins::filter('content_type', $defaults, $this);
		return $result;
	}

	/**
	 * Adds the default tokens to this post when it's saved
	 */
	public function create_default_tokens()
	{
		$tokens = $this->content_type();
		$tokens = Plugins::filter( 'post_tokens', $tokens, $this );
		$this->add_tokens( $tokens );
	}

	/**
	 * Checks if this post has one or more tokens
	 *
	 * @param mixed $tokens A single token string or an array of tokens
	 * @return mixed false if no tokens match, an array of matching token ids if any match
	 */
	public function has_tokens( $tokens )
	{
		$this->get_tokens();
		$tokens = Utils::single_array( $tokens );
		$tokens = array_map( array( 'ACL', 'token_id' ), $tokens );
		$tokens = array_intersect( $tokens, $this->tokens );
		if ( count( $tokens ) == 0 ) {
			return false;
		}
		return $tokens;
	}

	/**
	 * Add a token to a post
	 * @param array|string $tokens The name of the permission to add, or an array of permissions to add
	 */
	public function add_tokens( $tokens )
	{
		$this->get_tokens();
		$tokens = Utils::single_array( $tokens );
		$tokens = array_map( array( 'ACL', 'token_id' ), $tokens );
		$tokens = array_filter($tokens);
		$add_tokens = array_diff( $tokens, $this->tokens );
		$add_tokens = array_unique( $add_tokens );
		foreach ( $add_tokens as $token_id ) {
			DB::insert( '{post_tokens}', array( 'post_id' => $this->id, 'token_id' => $token_id ) );
		}
		$this->tokens = array_merge( $this->tokens, $add_tokens );
		$this->tokens = array_unique( $this->tokens );
	}

	/**
	 * Deletes all tokens from a post
	 */
	public function delete_tokens()
	{
		DB::delete( '{post_tokens}', array( 'post_id' => $this->id ) );
		$this->tokens = array();
	}

	/**
	 * Deletes tokens from a post
	 * @param array|string $tokens The name of the permission to remove, or an array of permissions to remove
	 */
	public function remove_tokens( $tokens )
	{
		$this->get_tokens();
		$tokens = Utils::single_array( $tokens );
		$tokens = array_map( array( 'ACL', 'token_id' ), $tokens );
		$remove_tokens = array_intersect( $tokens, $this->tokens );
		foreach ( $remove_tokens as $token_id ) {
			DB::delete( '{post_tokens}', array( 'post_id' => $this->id, 'token_id' => $token_id ) );
		}
		$this->tokens = array_diff( $this->tokens, $remove_tokens );
	}

	/**
	 * Applies a new set of specific tokens to a post
	 * @param mixed $tokens A string token, or an array of tokens to apply to this post
	 */
	public function set_tokens( $tokens )
	{
		$tokens = Utils::single_array( $tokens );
		$new_tokens = array_map( array( 'ACL', 'token_id' ), $tokens );
		$new_tokens = array_unique( $new_tokens );
		DB::delete( '{post_tokens}', array( 'post_id' => $this->id ) );
		foreach ( $new_tokens as $token_id ) {
			DB::insert( '{post_tokens}', array( 'post_id' => $this->id, 'token_id' => $token_id ) );
		}
		$this->tokens = $new_tokens;
	}

	/**
	 * Returns an array of token ids that are associated with this post
	 * Also initializes the internal token array for use by other token operations
	 *
	 * @return array An array of token ids
	 */
	public function get_tokens()
	{
		if ( empty( $this->tokens ) ) {
			$this->tokens = DB::get_column( 'SELECT token_id FROM {post_tokens} WHERE post_id = ?', array( $this->id ) );
		}
		return $this->tokens;
	}

	/**
	 * Returns an access Bitmask for the given user on this post
	 * @param User $user The user mask to fetch
	 * @return Bitmask
	 */
	public function get_access( $user = null )
	{
		if ( ! $user instanceof User ) {
			$user = User::identify();
		}

		if ( $user->can( 'super_user' ) ) {
			return ACL::get_bitmask( 'full' );
		}

		// Collect a list of applicable tokens
		$tokens = array(
			'post_any',
			'post_' . Post::type_name( $this->content_type ),
		);

		if ( $user->id == $this->user_id ) {
			$tokens[] = 'own_posts';
		}

		$tokens = array_merge( $tokens, $this->get_tokens() );

		// collect all possible token accesses on this post
		$token_accesses = array();
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
		return ACL::get_bitmask( Utils::array_or( $token_accesses ) );
	}

	/**
	 * How to display the built-in post types.
	 *
	 * @param string $type The type of Post
	 * @param string $foruse Either 'singular' or 'plural'
	 * @return string The translated type name, or the built-in name if there is no translation
	 */
	public static function filter_post_type_display_4( $type, $foruse )
	{
		$names = array(
			'entry' => array(
				'singular' => _t( 'Entry' ),
				'plural' => _t( 'Entries' ),
			),
			'page' => array(
				'singular' => _t( 'Page' ),
				'plural' => _t( 'Pages' ),
			),
		);
		return isset( $names[$type][$foruse] ) ? $names[$type][$foruse] : $type;
	}

	/**
	 * How to display the built-in post statuses.
	 *
	 * @param string $status The built-in status name
	 * @return string The translated status name, or the built-in name if there is no translation
	 */
	public static function filter_post_status_display_4( $status )
	{
		$names = array(
			'draft' => _t( 'draft' ),
			'published' => _t( 'published' ),
			'scheduled' => _t( 'scheduled' ),
		);
		return isset( $names[$status] ) ? $names[$status] : $status;
	}

	/**
	 * Filter post content and titles for shortcodes
	 * @static
	 * @param string $content The field value to filter
	 * @param string $field The name of the field to filter
	 * @param Post $post The post that is being filtered
	 * @return string The fitlered field value
	 */
	public static function filter_post_get_7( $content, $field, $post )
	{
		$shortcode_fields = Plugins::filter('shortcode_fields', array('title', 'content'), $post);
		if(in_array($field, $shortcode_fields)) {
			$content = Utils::replace_shortcodes($content, $post);
		}
		return $content;
	}

	/**
	 * Stores a form value into this post's info records
	 *
	 * @param string $key The name of a form component that will be stored
	 * @param mixed $value The value of the form component to store
	 */
	public function field_save( $key, $value )
	{
		$this->info->$key = $value;
		$this->info->commit();
	}


	/**
	 * Loads form values from this post
	 *
	 * @param string $key The name of a form component that will be loaded
	 * @return mixed The stored value returned
	 */
	function field_load( $key ) {
		return $this->info->$key;
	}
}
?>
