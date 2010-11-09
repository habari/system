<?php
/**
 * @package Habari
 *
 */

/**
* Habari Tags Class
*
*/
class Tags extends Vocabulary
{
	protected static $vocabulary = 'tags';
	protected static $object_type = 'post';

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
		return $term;
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
	public static function save_associations( $terms, $object_id, $object_type = 'post' )
	{
		if ( ! $terms instanceof Terms ) {
			$terms = Terms::parse( $terms );
		}
		return self::vocabulary()->set_object_terms( $object_type, $object_id, $terms );
	}

}
?>
