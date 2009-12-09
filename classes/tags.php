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
	 * Returns a tag or tags based on supplied parameters.
	 * @return array An array of Tag objects
	 **/
	public static function get()
	{
		$tags = array();
		$terms = Tags::vocabulary()->get_tree('term_display ASC');
		foreach( $terms as $term ) {
			$tags[] = new Tag( array( 'tag_text' => $term->term_display, 'tag_slug' => $term->term, 'id' => $term->id ) );
		}
		return $tags;

	}

	/**
	 * Return a tag based on an id, tag text or slug
	 *
	 * @return Tag The tag object
	 **/
	public static function get_one( $tag )
	{
		$term = Tags::vocabulary()->get_term( $tag );
		$tag = new Tag( array( 'tag_text' => $term->term_display, 'tag_slug' => $term->term, 'id' => $term->id ) );
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
		$vocabulary = Tags::vocabulary();
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
			$vocabulary->delete_term( $term->id );
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
		return DB::get_value( 'SELECT count( t2.object_id ) AS max FROM {terms} t, {object_terms} t2 WHERE t2.term_id = t.id AND t.vocabulary_id = ? GROUP BY t.id ORDER BY max DESC LIMIT 1', array( Tags::vocabulary()->id ) );
	}

	/**
	 * Returns the number of tags in the database.
	 *
	 * @return int The number of tags in the database.
	 **/
	public static function count_total()
	{
		return count( Tags::vocabulary()->get_tree() );
	}

	/**
	 * Returns the count of times a tag is used.
	 *
	 * @param mixed The tag to count usage.
	 * @return int The number of times a tag is used.
	 **/
	public static function post_count($tag, $object_type = 'post' )
	{
		$tag = Tags::get_one( $tag );
		return $tag->count( $object_type );
	}

	/**
	 * Return a tag based on a tag's text
	 *
	 * @return	A Tag object
	 **/
	public static function get_by_text($tag)
	{
		return Tags::get_one( $tag );
	}

	/**
	 * Return a tag based on a tag's text
	 *
	 * @return	A Tag object
	 **/
	public static function get_by_slug($tag)
	{
		return Tags::get_one( $tag );
	}

	/**
	 * Returns a Tag object based on a supplied ID
	 *
	 * @param Integer tag_id The ID of the tag to retrieve
	 * @return	A Tag object
	 */
	public static function get_by_id( $tag )
	{
		return Tags::get_one( $tag );
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
	 * @return boolean. Whether the associating succeeded or not. TRUE
	 */

	public static function save_associations( $tags, $object_id, $object_type = 'post' )
	{
		return Tags::vocabulary()->set_object_terms( $object_type, $object_id, $tags );
	}

	public static function get_associations( $object_id, $object_type = 'post' )
	{
		return Tags::vocabulary()->get_object_terms( $object_type, $object_id );
	}

}
?>
