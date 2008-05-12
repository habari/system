<?php
/**
 * Habari Tag Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */
class Tags extends ArrayObject
{
    public static function get() {
		$tags= DB::get_results( 'SELECT t.tag_text AS tag, t.tag_slug AS slug, COUNT(tp.tag_id) AS count FROM {tags} t INNER JOIN {tag2post} tp ON t.id=tp.tag_id GROUP BY tag, slug ORDER BY tag ASC' );
		return $tags;
	}
}
?>
