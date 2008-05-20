<?php
/**
* Habari Tags Class
*
* @package Habari
*/
class Tags extends ArrayObject
{
	/**
	 * Returns all tags
	 * <b>THIS CLASS SHOULD CACHE QUERY RESULTS!</b>
	 *
	 * @return array An array of Tag objects
	 **/
	public static function get()
	{
		/*
		 * A LEFT JOIN is needed here in order to accomodate tags,
		 * such as the default "habari" tag added to the database, 
		 * which are not related (yet) to any post itself.  These
		 * tags are essentially lost to the world.
		 *
		 * This is a partial fix for Issue#375.  See get_by_id() for the 
		 * other part.
		 */
		$sql=<<<ENDOFSQL
SELECT 
	t.id AS id
, t.tag_text AS tag
, t.tag_slug AS slug
, COUNT(*) AS count 
FROM {tags} t 
LEFT JOIN {tag2post} tp 
ON t.id=tp.tag_id 
GROUP BY id, tag, slug 
ORDER BY tag ASC
ENDOFSQL;
		$tags= DB::get_results( $sql );
		return $tags;
	}

	/**
	 * Return a tag based on an id, tag text or slug
	 *
	 * @return QueryRecord A tag QueryRecord
	 **/
	public static function get_one($tag)
	{
 		return DB::get_row( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t LEFT JOIN {tag2post} tp ON t.id=tp.tag_id WHERE tag_slug = ? OR t.id = ? GROUP BY id, tag, slug', array( Utils::slugify( $tag ), $tag ) );
	}

	/**
	 * Deletes a tag
	 *
	 * @param Tag tag The tag to be deleted
	 **/
	public static function delete($tag)
	{
		DB::query( 'DELETE FROM {tag2post} WHERE tag_id = ?', array($tag->id) );
		DB::query( 'DELETE FROM {tags} WHERE id = ?', array($tag->id) );
		EventLog::log( sprintf(_t('Tag deleted: %s'), $tag->tag), 'info', 'tag', 'habari' );
	}

	/**
	 * TODO: be more careful
	 * INSERT INTO {tag2post} / SELECT $master_tag->ID,post_ID FROM {tag2post} WHERE tag_id = $tag->id" and then "DELETE FROM {tag2post} WHERE tag_id = $tag->id"
	 * Renames tags.
	 * If the master tag exists, the tags will be merged with it.
	 * If not, it will be created first.
	 *
	 * @param Array tags The tag text, slugs or ids to be renamed
	 * @param mixed master The Tag to which they should be renamed, or the slug, text or id of it
	 **/
	public static function rename($master, $tags)
	{

		$master_tag= Tags::get_one($master);

		// it didn't exist, so we assume it's tag text and create it
		if ( $master_tag === FALSE  ) {
			DB::query( 'INSERT INTO {tags} (tag_text, tag_slug) VALUES ( ?, ? )', array( $master, Utils::slugify($master) ) );
			$master_tag= Tags::get_one($master);
		}

		// if only one tag was passed in, make it an array so we don't duplicate code
		if ( !is_array( $tags ) ) {
			$tags= array( $tags );
		}

		$posts= array();
		$tag_names= array();

		foreach ( $tags as $tag ) {
			$tag= Tags::get_one( $tag );

			// get all the post ID's tagged with this tag
			$posts= DB::get_results( 'SELECT post_id FROM {tag2post} WHERE tag_id = ?', array( $tag->id ) );

			if ( count( $posts ) > 0 ) {

				// build a list of all the post_id's we need for the new tag
				foreach ( $posts as $post ) {
					$post_ids[ $post->post_id ]= $post->post_id;
				}
				$tag_names[]= $tag->tag;
			}

			// delete each tag
			Tags::delete( $tag );

		}

		if ( count( $post_ids ) > 0 ) {

			// link the master tag to each distinct post we removed tags from
			foreach ( $post_ids as $post_id ) {

				DB::query( 'INSERT INTO {tag2post} ( tag_id, post_id ) VALUES ( ?, ? )', array( $master_tag->id, $post_id ) );

			}

		}
		EventLog::log(sprintf(
			_n('Tag %s has been renamed to %s.',
				 'Tags %s have been renamed to %s.',
				  count($tags)
			), implode($tag_names, ', '), $master ), 'info', 'tag', 'habari'
		);

	}

	/**
	 * Returns the number of times the most used tag is used.
	 *
	 * @return int The number of times the most used tag is used.
	 **/
	public static function max_count() {
		return DB::get_value( 'SELECT count( t2.post_id ) AS max FROM {tags} t, {tag2post} t2 WHERE t2.tag_id = t.id GROUP BY t.id ORDER BY count( t2.post_id ) DESC LIMIT 1' );
	}

	/**
	 * Returns the count of times a tag is used.
	 *
	 * @param mixed The tag to count usage.
	 * @return int The number of times a tag is used.
	 **/
	public static function post_count($tag) {
		if ( is_int( $tag ) ) {
			$tag= Tags::get_by_id( $tag );
		}
		else if ( is_string( $tag ) ) {
			$tag= Tags::get_by_slug( Utils::slugify($tag) );
		}

		return DB::get_row( 'SELECT COUNT(tag_id) AS count FROM {tag2post} WHERE tag_id = ?', array($tag->id) );
	}

	public static function get_by_text($tag)
	{
		return DB::get_row( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id WHERE tag_text = ? GROUP BY id, tag, slug', array($tag) );
	}

	public static function get_by_slug($tag)
	{
		return DB::get_row( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id WHERE tag_slug = ? GROUP BY id, tag, slug', array($tag) );
	}

	/**
	 * Returns a Tag object based on a supplied ID
	 *
	 * @param		tag_id	The ID of the tag to retrieve
	 * @return	A Tag object
	 */
	public static function get_by_id( $tag_id )
	{
		/*
		 * A LEFT JOIN is needed here to accomodate tags not yet
		 * related to a post, like the default "habari" tag...
		 * Remaining fix for Issue#375
		 */
		$sql=<<<ENDOFSQL
SELECT 
  t.id AS id
, t.tag_text AS tag
, t.tag_slug AS slug
, COUNT(*) AS count 
FROM {tags} t 
LEFT JOIN {tag2post} tp 
ON t.id=tp.tag_id 
WHERE id = ? 
GROUP BY id, tag, slug
ENDOFSQL;
		return DB::get_row( $sql, array($tag_id) );
	}
}
?>
