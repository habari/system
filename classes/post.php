<?php
/**
 * Habari PostRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Post extends QueryRecord
{
	private $tags = null;

	/**
	 * constructor __construct
	 * Constructor for the Post class.
	 * @param array an associative array of initial Post field values.
	 **/	 	 	 	
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			array(
				'id' => '',
				'slug' => '', 
				'title' => '', 
				'guid' => '', 
				'content' => '', 
				'author' => '', 
				'status' => 'draft', 
				'pubdate' => date( 'Y-m-d H:i:s' ), 
				'updated' => date( 'Y-m-d H:i:s' )
			),
			$this->fields
		);
		parent::__construct( $paramarray );
	}

	/**
	 * static function get_posts
	 * Returns requested posts.
	 * @param array An associated array of parameters, or a querystring
	 * @return array An array of Post objects, one for each query result
	 **/	 	 	 	 	
	static function get_posts($paramarray = array()) 
	{
		global $urlparser;

		// Defaults
		$defaults = array (
			'orderby' => 'pubdate DESC',
			'status' => 'publish',
			'stub' => '',
		);

		$paramarray = array_merge( $urlparser->settings, $defaults, Utils::get_params($paramarray) ); 

		return self::do_query($paramarray, false);
	}

	
	/**
	 * static function get_post
	 * Returns a single requested post
	 * @param array An associated array of parameters, or a querystring
	 * @return array A single Post object, the first if multiple results match
	 **/	 	 	 	 	
	static function get_post($paramarray = array())
	{
		global $urlparser;
		
		// Defaults
		$defaults = array (
			'orderby' => 'pubdate DESC',
			'status' => 'publish',
		);

		$paramarray = array_merge( $urlparser->settings, $defaults, Utils::get_params($paramarray) ); 
		return self::do_query($paramarray, true);
	}
		
	/**
	 * static function do_query
	 * Returns a post or posts based on supplied parameters
	 * THIS CLASS SHOULD CACHE QUERY RESULTS!	 
	 * @param array An associated array of parameters, or a querystring
	 * @param boolean If true, returns only the first result	 
	 * @return array An array of Post objects, or a single post object, depending on request
	 **/	 	 	 	 	
	private function do_query($paramarray, $one_row_only)
	{
		global $db;
	
		$params = array();
		$selects = array(
			'habari__posts' => array (
				'id',
				'slug',
				'title',
				'guid',
				'content',
				'author',
				'status',
				'pubdate',
				'updated',
			),
		);
	
		// Put incoming parameters into the local scope
		extract(Utils::get_params($paramarray));
		$where = array(1);
		$join = '';
		if ( isset( $status ) ) {
			$where[] = "status = ?";
			$params[] = $status;
		}
		if ( isset( $slug ) ) {
			$where[] = "slug = ?";
			$params[] = $slug;
		}
		if ( isset( $tag ) ) {
			$join .= ' JOIN habari__tags ON habari__posts.slug = habari__tags.slug';
			// Need tag expression parser here.			
			$where[] = 'tag = ?';
			$params[] = $tag;
		}
		
	 	$select = '';	
		foreach($selects as $table=>$fields) {
			$select .= "{$table}." . implode( ", {$table}.", $fields );
		}
		
		$query = "
		SELECT 
		" . $select . "
		FROM 
			habari__posts 
		" . $join . "
		WHERE 
			" . implode( ' AND ', $where ) . "
		ORDER BY 
			{$orderby}";
			
		$fetch_fn = ($one_row_only) ? 'get_row' : 'get_results';
		$results = $db->$fetch_fn( $query, $params, 'Post' );

		if ( is_array( $results ) || $one_row_only) {
			return $results;
		}
		else {
			return false;
		}
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
	 * function insert
	 * Saves a new post to the posts table
	 */	 	 	 	 	
	public function insert()
	{
		$this->newfields[ 'updated' ] = date( 'Y-m-d h:i:s' );
		$this->setslug();
		$result = parent::insert( 'habari__posts' );
		$this->fields = array_merge($this->fields, $this->newfields);
		$this->newfields = array();
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
		global $urlparser;
		
		return $urlparser->get_url(
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
		
		if ( empty( $this->tags ) ) {
			$this->tags = $db->get_column( 'SELECT tag FROM habari__tags WHERE slug = ? ', array( $this->fields['slug'] ) );
		}
		return $this->tags;
	}

}


?>
