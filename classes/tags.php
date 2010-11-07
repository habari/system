<?php
/**
 * @package Habari
 *
 */

/**
* Habari Tags Class
*
*/
class Tags extends ArrayObject
{
	protected static $vocabulary = 'tags';
	protected static $object_type = 'post';

	/**
	 * Constructor for the Tags class.
	 * @param mixed $tags String or array of tags
	 */
	public function __construct( $tags = array() )
	{
		$tags = self::parse_tags( $tags );

		// Turn each of the tags into a Tag
		if ( count( $tags ) ) {
			if ( is_string( $tags[0] ) || $tags[0] instanceof Term ) {
				array_walk( $tags, create_function('&$tag', '$tag = new Tag($tag);') );
			}
		}
		parent::__construct( $tags );
	}

	/**
	 * Returns a tag or tags based on supplied parameters.
	 * @return array An array of Tag objects
	 **/
	public static function get()
	{
		$tags = self::vocabulary()->get_tree('term_display ASC');
		return new Tags( $tags );

	}

	/**
	 * Return a tag based on an id, tag text or slug
	 *
	 * @return Tag The tag object
	 **/
	public static function get_one( $tag )
	{
		$term = self::vocabulary()->get_term( $tag );
		if ( !$term instanceOf Term ) {
			return false;
		}
		$tag = new Tag( $term );
		return $tag;
	}

	/**
	 * Deletes a tag
	 *
	 * @param Tag $tag The tag to be deleted
	 **/
	public static function delete($tag)
	{
		$tag->delete();
	}

	/**
	 * Renames tags.
	 * If the master tag exists, the tags will be merged with it.
	 * If not, it will be created first.
	 *
	 * @param Array tags The tag text, slugs or ids to be renamed
	 * @param mixed master The Tag to which they should be renamed, or the slug, text or id of it
	 **/
	public static function rename($master, $tags, $object_type = 'post' )
	{
		$vocabulary = self::vocabulary();
		$type_id = Vocabulary::object_type_id( $object_type );

		$post_ids = array();
		$tag_names = array();

		// get array of existing tags first to make sure we don't conflict with a new master tag
		foreach ( $tags as $tag ) {

			$posts = array();
			$term = $vocabulary->get_term( $tag );

			// get all the post ID's tagged with this tag
			$posts = $term->objects( $object_type );

			if ( count( $posts ) > 0 ) {
				// merge the current post ids into the list of all the post_ids we need for the new tag
				$post_ids = array_merge( $post_ids, $posts );
			}

			$tag_names[] = $tag;
			if ( $tag != $master ) {
				$vocabulary->delete_term( $term->id );
			}
		}

		// get the master term
		$master_term = $vocabulary->get_term( $master );

		if ( !isset($master_term->term ) ) {
			// it didn't exist, so we assume it's tag text and create it
			$master_term = $vocabulary->add_term( $master );

			$master_ids = array();
		}
		else {
			// get the posts the tag is already on so we don't duplicate them
			$master_ids = $master_term->objects( $object_type );

		}

		if ( count( $post_ids ) > 0 ) {
			// only try and add the master tag to posts it's not already on
			$post_ids = array_diff( $post_ids, $master_ids );
		}
		else {
			$post_ids = $master_ids;
		}
		// link the master tag to each distinct post we removed tags from
		foreach ( $post_ids as $post_id ) {
			$master_term->associate( $object_type, $post_id );
		}

		EventLog::log(sprintf(
			_n('Tag %s has been renamed to %s.',
				 'Tags %s have been renamed to %s.',
				  count( $tags )
			), implode( $tag_names, ', ' ), $master ), 'info', 'tag', 'habari'
		);

	}

	/**
	 * Returns the number of times the most used tag is used.
	 *
	 * @return int The number of times the most used tag is used.
	 **/
	public static function max_count()
	{
		return DB::get_value( 'SELECT count( t2.object_id ) AS max FROM {terms} t, {object_terms} t2 WHERE t2.term_id = t.id AND t.vocabulary_id = ? GROUP BY t.id ORDER BY max DESC LIMIT 1', array( self::vocabulary()->id ) );
	}

	/**
	 * Returns the number of tags in the database.
	 *
	 * @return int The number of tags in the database.
	 **/
	public static function count_total()
	{
		return count( self::vocabulary()->get_tree() );
	}

	/**
	 * Returns the count of times a tag is used.
	 *
	 * @param mixed The tag to count usage.
	 * @return int The number of times a tag is used.
	 **/
	public static function post_count($tag, $object_type = 'post' )
	{
		$tag = self::get_one( $tag );
		return $tag->count( $object_type );
	}

	/**
	 * Return a tag based on a tag's text
	 *
	 * @return	A Tag object
	 **/
	public static function get_by_text($tag)
	{
		return self::get_one( $tag );
	}

	/**
	 * Return a tag based on a tag's text
	 *
	 * @return	A Tag object
	 **/
	public static function get_by_slug($tag)
	{
		return self::get_one( $tag );
	}

	/**
	 * Returns a Tag object based on a supplied ID
	 *
	 * @param Integer tag_id The ID of the tag to retrieve
	 * @return	A Tag object
	 */
	public static function get_by_id( $tag )
	{
		return self::get_one( $tag );
	}

	/**
	 * Returns the tags vocabulary
	 *
	 * @return String The tags vocabulary
	 */
	public static function vocabulary()
	{
		return Vocabulary::get( self::$vocabulary );
	}

	/**
	 * Returns the default type Tags uses
	 *
	 * @return String The default type name
	 */
	public static function object_type()
	{
		return self::$object_type;
	}

	/**
	 * Save the tags associated to this object into the terms and object_terms tables
	 *
	 * @param Array $tags strings. The tag names to associate to the object
	 * @param Integer $object_id. The id of the object being tagged
	 * @param String $object_type. The name of the type of the object being tagged. Defaults to post
	 *
	 * @return boolean. Whether the associating succeeded or not. true
	 */
	public static function save_associations( $tags, $object_id, $object_type = 'post' )
	{
		$terms = array();
		if ( ! $tags instanceof Tags ) {
			$tags = new Tags( $tags );
		}
		foreach ( $tags as $tag ) {
			$terms[] = new Term( array( 'term' => $tag->tag_slug, 'term_display' => $tag->tag_text, 'id' => $tag->id ) );
		}
		return self::vocabulary()->set_object_terms( $object_type, $object_id, $terms );
	}

	/**
	 * Get the tags associated with this object
	 *
	 * @param Integer $object_id. The id of the tagged object
	 * @param String $object_type. The name of the type of the object being tagged. Defaults to post
	 *
	 * @return Tags. The tags associated with this object
	 */
	public static function get_associations( $object_id, $object_type = 'post' )
	{
		$tags = self::vocabulary()->get_object_terms( $object_type, $object_id );
		if ( $tags )  {
			$tags = new Tags( $tags );
		}

		return $tags;
	}

	public static function parse_tags( $tags )
	{
		if ( is_string( $tags ) ) {
			if ( '' === $tags ) {
				return array();
			}
			// dirrty ;)
			$rez = array( '\\"'=>':__unlikely_quote__:', '\\\''=>':__unlikely_apos__:' );
			$zer = array( ':__unlikely_quote__:'=>'"', ':__unlikely_apos__:'=>"'" );
			// escape
			$tagstr = str_replace( array_keys( $rez ), $rez, $tags );
			// match-o-matic
			preg_match_all( '/((("|((?<= )|^)\')\\S([^\\3]*?)\\3((?=[\\W])|$))|[^,])+/u', $tagstr, $matches );
			// cleanup
			$tags = array_map( 'trim', $matches[0] );
			$tags = preg_replace( array_fill( 0, count( $tags ), '/^(["\'])(((?!").)+)(\\1)$/'), '$2', $tags );
			// unescape
			$tags = str_replace( array_keys( $zer ), $zer, $tags );
			// hooray
		}
		return $tags;
	}

	/**
	 * See if a tag or set of tags is in the current set of tags
	 *
	 * @param mixed $tags. A string containing a string or a comma separated list of strings,
	 *  or an array of strings, Terms, or Tags
	 * @return boolean. Whether the tag(s) is in the current set of tags.
	 */
	public function has( $tags )
	{
		$tags = (array)new Tags( $tags );

		$diff = array_diff( $tags, (array)$this );
		foreach( $tags as $tag ) {
			if ( in_array( $tag, $diff ) ) {
				return false;
			}
		}

		return true;
	}

}
?>
