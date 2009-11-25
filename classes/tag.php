<?php
/**
 * @package Habari
 *
 */

/**
 * Class which describes a single Tag object
 *
 */
class Tag
{

	public $tag_text = '';
	public $tag_slug = '';
	public $id = 0;


	/**
	 * Constructor for the Tag class.
	 * @param array $paramarray an associative array of initial Tag field values.
	 **/
	public function __construct( $paramarray = array() )
	{
		foreach($paramarray as $key => $value ){
			$this->$key = $value;
		}
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __get( $name )
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );
		$term = Term::get( $vocabulary->id, $this->id );

		switch ($name) {
			case 'tag':
			case 'tag_text':
				$out = $term->term_display;
				break;
			case 'slug':
			case 'tag_slug':
				$out = $term->term;
				break;
			case 'count':
				$out = $this->get_count();
				break;
			default:
				break;
		}
		return $out;
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __get( $name )
	{
		switch ($name) {
			case 'tag':
				$out = parent::__get( 'tag_text' );
				break;
			case 'slug':
				$out = parent::__get( 'tag_slug' );
				break;
			case 'count':
				$out = $this->get_count();
				break;
			default:
				$out = parent::__get( $name );
				break;
		}
		return $out;
	}

	/**
	 * Return a single requested tag.
	 *
	 * <code>
	 * $tag= Tag::get( 'Tag text' );
	 * $tag = Tag::get( 'tag-slug' );
	 * $tag = Tag::get( 23 ); // tag id
	 * </code>
	 *
	 * @param mixed $tag The tag's name, slug, or id
	 * @return Tag The first tag that matched the given criteria or FALSE on failure
	 **/
	static function get( $tag )
	{
		return Tags::get_one( $tag );
	}

	/**
	 * Create a tag and save it.
	 *
	 * @param array $paramarray An associative array of tag fields
	 * @return Tag The new Tag object
	 **/
	static function create( $paramarray )
	{
		$tag = new Tag( $paramarray );
		$tag = $tag->insert();
		return $tag;
	}

	/**
	 * Attaches (relates) a tag to a post
	 *
	 * @param		tag_id		The ID of the tag
	 * @param		post_id		The ID of the post
	 * @return	TRUE or FALSE depending if relation was created.
	 */
	public static function attach_to_post( $tag_id, $post_id )
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );
		$term = $vocabulary->get_term( $tag_id );
		$result = TRUE;

		Plugins::act( 'tag_attach_to_post_before', $tag_id, $post_id );

		$result = $term->associate( Tags::object_type(), $post_id);

		Plugins::act( 'tag_attach_to_post_after', $tag_id, $post_id );

		return $result;
	}

	/**
	 * Detaches a tag from a post (removes their association )
	 *
	 * @param		tag_id		The ID of the tag
	 * @param		post_id		The ID of the post
	 * @return	TRUE or FALSE depending if association was removed.
	 */
	public static function detatch_from_post( $tag_id, $post_id )
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );
		$term = $vocabulary->get_term( $tag_id );
		Plugins::act( 'tag_detatch_from_post_before', $tag_id, $post_id );

		$result = $term->dissociate( Tags::object_type(), $post_id );

		// should we delete the tag if it's the only one left?
		if( 0 == count( $term->objects( Tags::object_type() ) ) ) {
			$delete = true;
			$delete = Plugins::filter( 'tag_detach_from_post_delete_empty_tag', $delete, $tag_id );

			if ( $delete ) {
				$term->delete();
			}
		}

		Plugins::act( 'tag_detatch_from_post_after', $tag_id, $post_id, $result );

		return $result;

	}

	/**
	 * Generate a new slug for the tag.
	 *
	 * @return string The slug
	 */
	private function setslug()
	{
		// make sure our slug is unique
		$this->tag_slug = Plugins::filter( 'tag_setslug', $value );
		$this->tag_slug = Utils::slugify( $slug );
		return $this->tag_slug;
	}

	/**
	 * function insert
	 * Saves a new tag into the tags table
	 */
	public function insert()
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );

//		$this->setslug();

		$allow = true;
		$allow = Plugins::filter( 'tag_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'tag_insert_before', $this );

		$term = new Term( array( 'term' => $this->tag_slug, 'term_display' => $this->tag_text ) );
		$term = $vocabulary->add_term( $term );

		EventLog::log( sprintf(_t('New tag %1$s (%2$s);  Slug: %3$s'), $this->id, $this->tag_text, $this->tag_slug), 'info', 'content', 'habari' );
		Plugins::act( 'tag_insert_after', $this );

		return new Tag( array( 'tag_text' => $term->term_display, 'tag_slug' => $term->term, 'id' => $term->id ) );
	}

	/**
	 * function update
	 * Updates an existing tag in the tags table
	 */
	public function update()
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );

		$allow = true;
		$allow = Plugins::filter( 'tag_update_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'tag_update_before', $this );

		// Call setslug() only when tag slug is changed
		if ( isset( $this->tag_slug ) && $this->tag_slug != '' ) {
			$this->setslug();
		}


		$term = $vocabulary->get_term( $this->id );
		$term->term = $this->tag_slug;
		$term->term_display = $this->tag_text;
		$result = $term->update();

		Plugins::act( 'tag_update_after', $this );
		return $result;
	}

	/**
	 * function delete
	 * Deletes an existing tag and all relations to it (e.g. a post2tag relationship)
	 */
	public function delete()
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );

		$allow = true;
		$allow = Plugins::filter( 'tag_delete_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		// invoke plugins
		Plugins::act( 'tag_delete_before', $this );

		// Delete the actual term record
		$term = $vocabulary->get_term( $this->id );
		$result = $vocabulary->delete_term( $term );

		EventLog::log( sprintf(_t('Tag %1$s (%2$s) deleted.'), $this->id, $this->tag_text), 'info', 'content', 'habari' );

		Plugins::act( 'tag_delete_after', $this );
		return $result;
	}

	/**
	 * Handle calls to this Tag object that are implemented by plugins
	 * @param string $name The name of the function called
	 * @param array $args Arguments passed to the function call
	 * @return mixed The value returned from any plugin filters, null if no value is returned
	 **/
	public function __call( $name, $args )
	{
		array_unshift($args, 'tag_call_' . $name, null, $this);
		return call_user_func_array(array('Plugins', 'filter'), $args);
	}

	/**
	 * Get a count of how many times the tag has been used in a post
	 * @return integer The number of times the tag has been used
	 **/
	protected function get_count()
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );
		$term = $vocabulary->get_term( $this->id );
		return count( $term->objects( Tags::object_type() ) );
	}

	/**
	 * Get a count of how many times the tag has been used in a post
	 * @return integer The number of times the tag has been used
	 **/
	public function count( $object_type = 'post' )
	{
		$vocabulary = Vocabulary::get( Tags::vocabulary() );
		$term = $vocabulary->get_term( $this->id );
		return count( $term->objects( $object_type ) );
	}

}

?>
