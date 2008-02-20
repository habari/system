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
		$tags= DB::get_results( 'SELECT tag_text as tag FROM ' . DB::table('tags') . ' ORDER BY tag_text ASC' );
		return $tags;
	}
}
?>
