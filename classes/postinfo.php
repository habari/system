<?php

class PostInfo extends InfoRecords {

	function __construct ( $post_id ) 
	{
		parent::__construct ( DB::o()->postinfo, "post_id", $post_id ); // call parent with appropriate  parameters
	}		
}
?>
