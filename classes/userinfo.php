<?php

/**
 * @todo TODO document
 */
class UserInfo extends InfoRecords
{
	function __construct ( $user_id )
	{
		// call parent with appropriate  parameters
		parent::__construct ( DB::table('userinfo'), 'user_id', $user_id );
	}	
}

?>
