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

	private $term = null;

	/**
	 * Constructor for the Tag class.
	 * @param mixed $params an associative array of initial Tag field values or a Term object.
	 *
	 * @todo Should we disallow array construction?
	 **/
	public function __construct( $params )
	{
		$term = null;

		if ( $params instanceOf Term ) {
			if ( $params->id == 0 ) {
				// The term isn't from the db, try and retrieve it
				$term = Tags::vocabulary()->get_term( $params->term );
				if ( !$term ) {
					// We have a new term
					$term = $params;
				}
			}
			else {
				// This is an existing term
				$term = $params;
			}
		}
		else {
			if ( is_string($params) ) {
				// See if the string matches a tag in the db
				$term = Tags::vocabulary()->get_term( $params );

				if ( !$term ) {
					// The term didn't exist, create one
					$term = new Term( $params );
				}
			}
			else {
				if ( is_array($params) ) {
					// See if these params match a tag in the db
					// This means we ignore a slug if it was passed in. Is that bad?
					$term = Tags::vocabulary()->get_term( $params['tag_text'] );

					if ( !$term ) {
						// The term didn't exist, create one
						$params['term_display'] = $params['tag_text'];
						unset($params['tag_text']);
						if ( array_key_exists('tag_slug', $params) ) {
							$params['term'] = $params['tag_slug'];
							unset($params['tag_slug']);
						}
						$term = new Term( $params );
					}
				}
			}
		}
		// If this is a new term, id will still be 0 and will get set when insert() is called
		$this->term = $term;
	}

	/**
	 * function __get
	 * Overrides QueryRecord __get to implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 **/
	public function __get( $name )
	{
		switch ( $name ) {
			case 'id':
				$out = $this->term->id;
				break;
			case 'tag':
			case 'tag_text':
				$out = $this->term->term_display;
				break;
			case 'tag_text_searchable':
				// if it's got spaces, then quote it.
				if ( strpos($this->term->term_display, ' ') !== FALSE ) {
					$out = '\'' . str_replace("'", "\'", $this->term->term_display) . '\'';
				}
				else {
					$out = $this->term->term_display;
				}
				break;
			case 'slug':
			case 'tag_slug':
				$out = $this->term->term;
				break;
			case 'count':
				$out = $this->get_count();
				break;
			default:
				$out = null;
				break;
		}
		return $out;
	}

	/**
	 * function __set
	 * Implement custom object properties
	 * @param string Name of property to return
	 * @return mixed The requested field value
	 */
	public function __set( $name, $value )
	{
		switch ( $name ) {
			case 'tag':
			case 'tag_text':
				$this->term->term_display = $value;
				break;
			case 'slug':
			case 'tag_slug':
				$this->term->term = $value;;
				break;
			default:
				break;
		}
	}

	/**
	 * function __toString
	 * Implement custom string output when the Tag object is used in a context where a string is expected
	 * @param None
	 * @return The tag's display name
	 */
	public function __toString()
	{
		return $this->tag;
	}

	/**
	 * Return a single requested tag.
	 *
	 * <code>
	 * $tag = Tag::get( 'Tag text' );
	 * $tag = Tag::get( 'tag-slug' );
	 * $tag = Tag::get( 23 ); // tag id
	 * </code>
	 *
	 * @param mixed $tag The tag's name, slug, or id
	 * @return Tag The first tag that matched the given criteria or FALSE on failure
	 **/
	public static function get( $tag )
	{
		return Tags::get_one( $tag );
	}

	/**
	 * Check if a tag exists on a published post, to see if we should match this rewrite rule.
	 *
	 * @return Boolean Whether the tag exists on a published post.
	 **/
	public static function rewrite_tag_exists($rule, $slug, $parameters)
	{
		$tags = explode(' ', $rule->named_arg_values['tag']);
		$tags = array_map('trim', $tags, array_fill(0, count($tags), '-') );
		$tags = array_map(array('Tag', 'get'), $tags);
		$initial_tag_count = count($tags);
		$tags = array_filter($tags);
		// Are all of the tags we asked for actual tags on this site?
		if ( count($tags) != $initial_tag_count ) {
			return false;
		}
		$tag_params = array();
		foreach ( $tags as $tag ) {
			$tag_params[] = $tag->tag_text;
		}
		return ($tag instanceOf Tag && Posts::count_by_tag($tag_params, Post::status('published')) > 0);
	}

	/**
	 * Create a tag and save it.
	 *
	 * @param array $paramarray An associative array of tag fields
	 * @return Tag The new Tag object
	 **/
	public static function create( $paramarray )
	{
		$tag = new Tag( $paramarray );
		$tag = $tag->insert();
		return $tag;
	}

	/**
	 * function insert
	 * Saves a new tag's data into the terms table
	 */
	public function insert()
	{
		$allow = true;
		$allow = Plugins::filter( 'tag_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'tag_insert_before', $this );

		$term = Tags::vocabulary()->add_term( $this->term );

		if ( $term ) {
			$this->term = $term;
			EventLog::log( sprintf(_t('New tag %1$s (%2$s);  Slug: %3$s'), $this->id, $this->tag_text, $this->tag_slug), 'info', 'content', 'habari' );
			Plugins::act( 'tag_insert_after', $this );
			return new Tag( $this->term );
		}
		else {
			return FALSE;
		}

	}

	/**
	 * function update
	 * Update an existing tag's data in the terms table
	 */
	public function update()
	{
		$allow = true;
		$allow = Plugins::filter( 'tag_update_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'tag_update_before', $this );

		$result = $this->term->update();

		Plugins::act( 'tag_update_after', $this );
		return $result;
	}

	/**
	 * function delete
	 * Deletes an existing tag and all relations to it
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter( 'tag_delete_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		// invoke plugins
		Plugins::act( 'tag_delete_before', $this );

		// Delete the actual term record
		$result = Tags::vocabulary()->delete_term( $this->term );

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
		return count( $this->term->objects( Tags::object_type() ) );
	}

	/**
	 * Get a count of how many times the tag has been used in a post
	 * @return integer The number of times the tag has been used
	 **/
	public function count( $object_type = 'post' )
	{
		return count( $this->term->objects( $object_type ) );
	}

}

?>
