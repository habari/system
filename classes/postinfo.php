<?php
/**
 * @package Habari
 *
 */

/**
 * Post metadata
 */
class PostInfo extends InfoRecords
{
	function __construct ( $post_id )
	{
		parent::__construct ( DB::table('postinfo'), "post_id", $post_id ); // call parent with appropriate  parameters
	}
}
?>
