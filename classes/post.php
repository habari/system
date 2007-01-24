<?php
/**
 * Habari PostRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Post extends QueryRecord
{
	// public constants
	const STATUS_DRAFT = 0;
	const STATUS_PUBLISHED = 1;
	const STATUS_PRIVATE = 2;
	
	const STATUS_ANY = -1;  // For querying only, not for use as a stored value.
	
	const TYPE_POST = 0;
	const TYPE_ENTRY = 0;
	const TYPE_PAGE = 1;

	const TYPE_ANY = -1;  // For querying only, not for use as a stored value.

	private $tags = null;
	private $comments = null;
	private $author_object = null;
	private $info = null;	

	/**
	 * function default_fields
	 * Returns the defined database columns for a Post
	 * @return array Array of columns in the Post table
	**/
	public static function default_fields()
	{
		return array(
			'id' => '',
			'slug' => '',
			'content_type' => self::TYPE_POST,
			'title' => '',
			'guid' => '',
			'content' => '',
			'user_id' => '',
			'status' => self::STATUS_DRAFT,
			'pubdate' => date( 'Y-m-d H:i:s' ),
			'updated' => ( 'Y-m-d H:i:s' ),
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
		if ( isset( $this->fields['tags'] ) )
		{
			$this->tags = $this->parsetags($this->fields['tags']);
			unset( $this->fields['tags'] );
		}
		$this->exclude_fields('id');

		$this->info = new PostInfo ( $this->fields['id'] );
		// $this->info->option3 = "blah"; 
	}
	
	/**
	 * static function get
	 * Returns a single requested post
	 *
	 * <code>
	 * $post = Post::get( array( 'slug' => 'wooga' ) );
	 * </code>
	 *
	 * @param array An associated array of parameters, or a querystring
	 * @return array A single Post object, the first if multiple results match
	 **/	 	 	 	 	
	static function get($paramarray = array())
	{
		global $url;
		
		// Defaults
		$defaults = array (
			'where' => array(
				array(
					'status' => Post::STATUS_PUBLISHED,
				),
			),
			'fetch_fn' => 'get_row',
		);
		if( $user = User::identify() ) {
			$defaults['where'][] = array(
				'user_id' => $user->id,
			);
		}

		$paramarray = array_merge( $url->settings, $defaults, Utils::get_params($paramarray) ); 
		return Posts::get( $paramarray );
	}
	
	/**
	 * static function create
	 * Creates a post and saves it
	 * @param array An associative array of post fields
	 * $return Post The post object that was created	 
	 **/	 	 	
	static function create($paramarray) 
	{
		$post = new Post($paramarray);
		$post->insert();
		return $post;
	}

	
	/**
	 * function setslug
	 * Attempts to generate the slug for a post that has none
	 * @return The slug value	 
	 */	 	 	 	 	
	private function setslug()
	{
		if ( $this->fields[ 'slug' ] != '' && $this->fields[ 'slug' ] == $this->newfields[ 'slug' ]) {
			$value = $this->fields[ 'slug' ];
		}
		elseif ( isset( $this->newfields['slug']) && $this->newfields[ 'slug' ] != '' ) {
			$value = $this->newfields[ 'slug' ];
		}
		elseif ( ( $this->fields[ 'slug' ] != '' ) ) {
			$value = $this->fields[ 'slug' ];
		}
		elseif ( isset( $this->newfields['title'] ) && $this->newfields[ 'title' ] != '' ) {
			$value = $this->newfields[ 'title' ];
		}
		elseif ( $this->fields[ 'title' ] != '' ) {
			$value = $this->fields[ 'title' ];
		}
		else {
			$value = 'Post';
		}
		
		$slug= trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $value ) ), '-' );
		$postfix = '';
		$postfixcount = 0;
		do {
			$slugcount = DB::get_row( 'SELECT count(slug) AS ct FROM ' . DB::o()->posts . ' WHERE slug = ?;', array( "{$slug}{$postfix}" ) );
			if ( $slugcount->ct != 0 ) $postfix = "-" . ( ++$postfixcount );
		} while ( $slugcount->ct != 0 );
		$this->newfields[ 'slug' ]= $slug . $postfix;
		return $this->newfields[ 'slug' ];
	}

	/**
	 * function setguid
	 * Creates the GUID for the new post
	 */	 	 	 	 	
	private function setguid()
	{
		if( 
			!isset( $this->newfields['guid'] ) 
			|| ($this->newfields['guid'] == '')  // GUID is empty 
			|| ($this->newfields['guid'] == '//?p=') // GUID created by WP was erroneous (as is too common)
		) 
		{
			$result = 'tag:' . Options::get('hostname') . ',' 
							 . date('Y') . ':' . $this->setslug() . '/' . time();
			$this->newfields['guid'] = $result;
		}
		return $this->newfields['guid'];
	}
	
	private function parsetags($tags)
	{
		if( is_string( $tags ) )
		{
			preg_match_all('/(?<=")([\\S][^"]*)(?=")|([#-~]+)/', $tags, $matches);
			// we want to suppress duplicate tags
			return array_unique($matches[0]);
		}
		elseif( is_array( $tags ) )
		{
			// we want to suppress duplicate tags
			return array_unique($tags);
		}
	}

	private function savetags()
	{
		DB::query( 'DELETE FROM ' . DB::o()->tags . ' WHERE slug = ?', array( $this->fields['slug'] ) );
		foreach( (array)$this->tags as $tag ) { 
			DB::query( 'INSERT INTO ' . DB::o()->tags . ' (slug, tag) VALUES (?,?)', 
				array( $this->fields['slug'], $tag ) 
			); 
		}
	}
	
	/**
	 * function insert
	 * Saves a new post to the posts table
	 */	 	 	 	 	
	public function insert()
	{
		$this->newfields[ 'updated' ] = date( 'Y-m-d h:i:s' );
		$this->setslug();
		$this->setguid();
		$result= parent::insert( DB::o()->posts );
		$status_changed = ( $this->fields['status'] != $this->newfields['status'] );
		if($status_changed) {
			$orig_status = $this->fields['status'];
			$this->newfields['status'] = Plugins::filter('before_status_change', $this->newfields['status'], $this->fields['status'], $this);
		} 
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();

		$this->info->set_key ( DB::o()->last_insert_id() ); 
		// $this->info->option_default= "saved";

		$this->savetags();
		// XXX TODO this should be a hook
		if (Options::get('pingback_send') && $this->status == 1) { // why isn't this 'publish' here?
			Pingback::pingback_all_links($this->fields['content'], $this->get_permalink());
		}
		if($status_changed) {
			Plugins::act('after_status_change', $this, $orig_status);
		}
		return $result;
	}

	/**
	 * function update
	 * Updates an existing post in the posts table
	 */	 	 	 	 	
	public function update()
	{
		$this->updated = date('Y-m-d h:i:s');
		if(isset($this->fields['guid'])) unset( $this->newfields['guid'] );
		//$this->setslug();  // setslug() for an update?  Hmm.  No?
		$result = parent::update( DB::o()->posts, array('slug'=>$this->slug) );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->savetags();
		// XXX TODO this should be a hook
		if (Options::get('pingback_send') && $this->status == 1)
			Pingback::pingback_all_links($this->fields['content'], $this->get_permalink());
		return $result;
	}
	
	/**
	 * function delete
	 * Deletes an existing post
	 */	 	 	 	 	
	public function delete()
	{
		return parent::delete( DB::o()->posts, array('slug'=>$this->slug) );
	}
	
	/**
	 * function publish
	 * Updates an existing post to published status
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
		if( !isset($this->fields[$name]) && strpos( $name, '_' ) !== false ) {
			list( $filter, $name ) = explode( '_', $name, 2 );
		}
		else {
			$filter = false;
		}

		switch($name) {
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
		$out = Plugins::filter( "post_{$name}", $out );
		if( $filter ) {
			$out = Plugins::filter( "{$filter}_post_{$name}", $out );
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
		case 'author':
			if ( is_int( $value ) )
			{
				// a user ID was passed, so use it directly
				$this->user_id = $value;
				unset ( $this->author_object );
			}
			elseif ( is_string( $value ) )
			{
				// get the user ID of the user with this name
				$this->author_object = User::get( $value );
				$this->user_id = $this->author_object->id;
			}
			elseif ( if_object ( $value ) )
			{
				// a User object was passed, so just use the ID
				$this->user_id = $value->id;
				$this->author_object = $value;
			}
		}
		return parent::__set( $name, $value );
	}
	
	/**
	 * function get_permalink
	 * Returns a permalink for the ->permalink property of this class.
	 * @return string A link to this post.	 
	 **/	 	 	
	private function get_permalink()
	{
		$fields = array_merge(getdate(strtotime($this->pubdate)), $this->fields);
		$fields['strmonth'] = $fields['month'];
		$fields['month'] = $fields['mon'];
		$fields['day'] = $fields['mday'];
 		
		return URL::get(
 			'post',
			$fields,
			false
		);
	}
	
	/**
	 * function get_tags
	 * Gets the tags for the post
	 * @return &array A reference to the tags array for this post
	 **/	 	 	 	
	private function &get_tags()
	{
		$i = 0;
		if ( empty( $this->tags ) ) {
			$this->tags = DB::get_column( 'SELECT tag FROM ' . DB::o()->tags . ' WHERE slug = ? ', array( $this->fields['slug'] ) );
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
		if ( ! $this->comments )
		{
			$this->comments = Comments::by_slug( $this->slug );
		}
		return $this->comments;
	}

	/**
	 * private function get_author()
	 * returns a User object for the author of this post
	 * @param bool Whether to use the cached version or not.  Default to true
	 * @return User a User object for the author of the current post
	**/
	private function get_author( $use_cache = TRUE )
	{
		if ( ! isset( $this->author_object ) || ( ! $use_cache)  )
		{
			$this->author_object = User::get( $this->user_id );
		}
		return $this->author_object;
	}

	/**
	 * function get_info
	 * 
	 * Returns the post info array that is available for this post
	 * @return array Post info for this post
	 * @todo Create an info class to use instead of the array so that data written to the info "array" gets added to the database.	 	 
	 **/
	private function get_info()
	{
		if ( ! isset( $this->info_object ) ) {
			// See @todo^^^
			$this->info_object = DB::get_results('SELECT name, type, value FROM ' . DB::o()->postinfo . ' WHERE slug = ?', $this->slug);
		}
		return $this->info_object;
	}
}
?>
