<?php
/**
 * @package Habari
 *
 */

/**
 * Habari UserInfo class
 *
 * User metadata
 */
class UserInfo extends InfoRecords
{
	function __construct ( $user_id = null )
	{
		// call parent with appropriate  parameters
		parent::__construct( DB::table( 'userinfo' ), 'user_id', $user_id );
	}
}

?>
