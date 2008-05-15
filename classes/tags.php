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
	public static function get() {
		$tags= DB::get_results( 'SELECT t.id AS id, t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id GROUP BY tag, slug ORDER BY tag ASC' );
		return $tags;
	}

	public static function delete($tag)
	{
		DB::query( 'DELETE FROM {tag2post} WHERE tag_id = ?', array($tag->id) );
		DB::query( 'DELETE FROM {tags} WHERE id = ?', array($tag->id) );
	}

	public static function get_by_text($tag)
	{
		return DB::get_row( 'SELECT id, tag_text, tag_slug FROM {tags} WHERE tag_text = ?', array($tag) );
	}

	public static function get_by_slug($tag)
	{
		return DB::get_row( 'SELECT id, tag_text, tag_slug FROM {tags} WHERE tag_slug = ?', array($tag) );
	}

	public static function get_by_id($tag)
	{
		return DB::get_row( 'SELECT id, tag_text, tag_slug FROM {tags} WHERE id = ?', array($tag) );
	}
}
?>
