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

	private $tags = null;
	private $comments = null;

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
			'title' => '',
			'guid' => '',
			'content' => '',
			'author' => '',
			'status' => 'draft',
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
			'status' => 1,
			'limit' => 1,
		);

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
		global $db;
		if ( $this->fields[ 'slug' ] != '' && $this->fields[ 'slug' ] == $this->newfields[ 'slug' ]) {
			$value = $this->fields[ 'slug' ];
		}
		elseif ( $this->newfields[ 'slug' ] != '' ) {
			$value = $this->newfields[ 'slug' ];
		}
		elseif ( ( $this->fields[ 'slug' ] != '' ) ) {
			$value = $this->fields[ 'slug' ];
		}
		elseif ( $this->newfields[ 'title' ] != '' ) {
			$value = $this->newfields[ 'title' ];
		}
		elseif ( $this->fields[ 'title' ] != '' ) {
			$value = $this->fields[ 'title' ];
		}
		else {
			$value = 'Post';
		}
		
		$slug = strtolower( preg_replace( '/[^a-z]+/i', '-', $value ) );
		$postfix = '';
		$postfixcount = 0;
		do {
			$slugcount = $db->get_row( "SELECT count(slug) AS ct FROM habari__posts WHERE slug = ?;", array( "{$slug}{$postfix}" ) );
			if ( $slugcount->ct != 0 ) $postfix = "-" . ( ++$postfixcount );
		} while ($slugcount->ct != 0);
		$this->newfields[ 'slug' ] = $slug . $postfix;
		return $this->newfields[ 'slug' ];
	}

	/**
	 * function setguid
	 * Creates the GUID for the new post
	 */	 	 	 	 	
	private function setguid()
	{
		$result = 'tag:' . Options::get('hostname') . ',' 
						 . date('Y') . ':' . $this->setslug() . '/' . time();
		$this->newfields['guid'] = $result;
		return $this->newfields['guid'];
	}
	
	private function parsetags($tags)
	{
		if( is_string( $tags ) )
		{
			preg_match_all('/(?<=")(\\w[^"]*)(?=")|(\\w+)/', $tags, $matches);
			return $matches[0];
		}
		elseif( is_array( $tags ) )
		{
			return $tags;
		}
	}

	private function savetags()
	{
		global $db;
		$db->query( "DELETE FROM habari__tags WHERE slug = ?", array( $this->fields['slug'] ) );
		foreach( (array)$this->tags as $tag ) { 
			$db->query( "INSERT INTO habari__tags (slug, tag) VALUES (?,?)", 
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
		$result = parent::insert( 'habari__posts' );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->savetags();
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
		$result = parent::update( 'habari__posts', array('slug'=>$this->slug) );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
		$this->savetags();
		return $result;
	}
	
	/**
	 * function delete
	 * Deletes an existing post
	 */	 	 	 	 	
	public function delete()
	{
		return parent::delete( 'habari__posts', array('slug'=>$this->slug) );
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
		switch($name) {
		case 'permalink':
			return $this->get_permalink();
		case 'tags':
			return $this->get_tags();
		case 'comments':
			return $this->get_comments();
		case 'comment_count':
			return $this->get_comments()->count();
		default:
			return parent::__get( $name );
		}
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
			$value = date('Y-m-d H:i:s', strtotime($value));
			break;
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
		global $url;
		
		return $url->get_url(
			'post',
			$this->fields,
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
		global $db;
		$i = 0;
		if ( empty( $this->tags ) ) {
			$this->tags = $db->get_column( 'SELECT tag FROM habari__tags WHERE slug = ? ', array( $this->fields['slug'] ) );
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
}
?>
