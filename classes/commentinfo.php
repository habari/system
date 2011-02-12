<?php
/**
 * @package Habari
 *
 */

/**
 * Comment metadata
 */
class CommentInfo extends InfoRecords
{

	function __construct ( $comment_id = null )
	{
		parent::__construct( DB::table( 'commentinfo' ), 'comment_id', $comment_id ); // call parent with appropriate  parameters
	}

}

?>
