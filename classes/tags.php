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
		$tags= DB::get_results( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id GROUP BY tag, slug ORDER BY tag ASC' );
		return $tags;
	}

	/**
	 * Return a tag based on an id, tag text or slug
	 *
	 * @return QueryRecord A tag QueryRecord
	 **/
	public static function get_one($tag)
	{
		return DB::get_row( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id WHERE slug = ? OR id = ? GROUP BY tag', array(Utils::slugify($tag), $tag) );
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
		if ( !$master_tag  ) {
			DB::query( 'INSERT INTO {tags} (tag_text, tag_slug) VALUES (?, ?)', array( $master, Utils::slugify($master) ) );
			$master_tag= Tags::get_one($master);
		}

		foreach ( $tags as $tag ) {
			$tag= Tags::get_one($tag);

			if ( $tag && $tag != $master_tag ) {
				DB::query( 'UPDATE {tag2post} SET tag_id = ? WHERE tag_id = ?', array($master_tag->id, $tag->id) );
				Tags::delete( $tag );
			}
		}
	}

	/**
	 * Returns the number of times the most used tag is used.
	 *
	 * @return int The number of times the most used tag is used.
	 **/
	public static function max_count() {
		return DB::get_value( 'SELECT count( t2.post_id ) AS max FROM {tags} t, {tag2post} t2 WHERE t2.tag_id = t.id GROUP BY t.id ORDER BY count( t2.post_id ) DESC LIMIT 0, 1' );
	}

	/**
	 * Returns the count of times a tag is used.
	 *
	 * @param mixed The tag to count usage.
	 * @return int The number of times a tag is used.
	 **/
	public static function post_count($tag) {
		if ( is_int( $tag ) ) {
			$tag= self::get_by_id( $tag );
		}
		else if ( is_string( $tag ) ) {
			$tag= self::get_by_slug( Utils::slugify($tag) );
		}

		return DB::get_row( 'SELECT COUNT(tag_id) AS count FROM {tag2post} WHERE tag_id = ?', array($tag->id) );
	}

	public static function get_by_text($tag)
	{
		return DB::get_row( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id WHERE tag = ? GROUP BY id', array($tag) );
	}

	public static function get_by_slug($tag)
	{
		return DB::get_row( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id WHERE slug = ? GROUP BY id', array($tag) );
	}

	public static function get_by_id($tag)
	{
		return DB::get_row( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id WHERE id = ? GROUP BY id', array($tag) );
	}
}
?>
