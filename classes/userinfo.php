<?php

class UserInfo extends InfoRecords {

	function __construct ( $user_id ) {
		parent::__construct ( DB::o()->userinfo, 'user_id', $user_id ); // call parent with appropriate  parameters
	}	

}

?>
