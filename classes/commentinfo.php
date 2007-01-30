<?php

class CommentInfo extends InfoRecords {

	function __construct ( $comment_id ) {
		parent::__construct ( DB::table('commentinfo'), 'comment_id', $comment_id ); // call parent with appropriate  parameters
	}	

}

?>
