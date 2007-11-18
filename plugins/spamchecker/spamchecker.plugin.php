<?php

/**
 * SpamChecker Class
 * 
 * This class implements first round spam checking.
 *
 **/

class SpamChecker extends Plugin
{
	/**
	 * function info
	 * Returns information about this plugin
	 * @return array Plugin info array
	 **/
	function info()
	{
		return array (
			'name' => 'Spam Checker',
			'url' => 'http://habariproject.org/',
			'author' => 'Habari Community',
			'authorurl' => 'http://habariproject.org/',
			'version' => '1.0',
			'description' => 'Flags as spam obvious comment spam.',
			'license' => 'Apache License 2.0',
		);
	}
	
	/**
	 * function actions_plugins_loaded
	 * Executes after all plugins are loaded
	 **/	 	 	
	public function action_plugins_loaded()
	{
		//Utils::debug('ok');
	}
	
	/**
	 * function act_comment_insert_before
	 * This function is executed when the action "comment_insert_before"
	 * is invoked from a Comment object.
	 * The parent class, Plugin, handles registering the action 
	 * and hook name using the name of the function to determine 
	 * where it will be applied.
	 * You can still register functions as hooks without using
	 * this method, but boy, is it handy.
	 * @param Comment The comment that will be processed before storing it in the database.
	 * @return Comment The comment result to store.
	 **/	 	 	 	 	
	function action_comment_insert_before ( $comment )
	{
		// This plugin ignores non-comments
		if($comment->type != Comment::COMMENT) {
			return $comment;
		}
		
		$spamcheck = array();
		
		// <script> is bad, mmmkay?
		$comment->content = InputFilter::filter($comment->content);

		// first, check the commenter's name
		// if it's only digits, then we can discard this comment
		if ( preg_match( "/^\d+$/", $comment->name ) ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('Commenters with numeric names are spammy.'); 
		}

		// now look at the comment text
		// if it's digits only, discard it
		$textonly = strip_tags( $comment->content );

		if ( preg_match( "/^\d+$/", $textonly ) ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('Comments that are only numeric are spammy.'); 
		}

		// is the content the single word "array"?
		if ( 'array' == strtolower( $textonly ) ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('Comments that are only "array" are spammy.'); 
		}

		// is the content the same as the name?
		if ( strtolower( $textonly ) == strtolower( $comment->name ) ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('Comments that consist of only the commenters name are spammy.'); 
		}

		// a lot of spam starts with "<strong>some text...</strong>"
		if ( preg_match( "#^<strong>[^.]+\.\.\.</strong>#", $comment->content ) )
		{
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('Comments that start with strong text are spammy.');
		}

		// are there more than 3 URLs posted?  If so, it's almost certainly spam
		if ( preg_match_all( "#http://#", strtolower( $comment->content ), $matches, PREG_SET_ORDER ) > 3 ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('There is a 3 URL limit in comments.'); 
		}

		// are there more than 3 URLencoded characters in the content?
		if ( preg_match_all( "/%[0-9a-f]{2}/", strtolower( $comment->content ), $matches, PREG_SET_ORDER ) > 3 ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('There is a 3 URL-encoded character limit in comments.'); 
		}

		// Was the tcount high enough?
		/* // This only works with special javascript running on comment form
		if ( empty($handlervars['tcount']) || $handlervars['tcount'] < 10 ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('Commenter did not actually type content.'); 
		}
		*/

		// We don't allow bbcode here, silly
		if ( stripos($comment->content, '[url=') !== false ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('We do not accept BBCode here.');
		}
		
		// Must have less than half link content
		$nonacontent = strip_tags(preg_replace('/<a.*?<\/a/i', '', $comment->content));
		if ( strlen($nonacontent) / strlen($textonly) < 0.5 ) {
			$comment->status = Comment::STATUS_SPAM;
			$spamcheck[] = _t('Too much text that is a link compared to that which is not.'); 
		}
		
		// Only do db checks if it's not already spam
		if($comment->status != Comment::STATUS_SPAM) {
			$spams = DB::get_value('SELECT count(*) FROM ' . DB::table('comments') . ' WHERE status = 2 AND ip = ?', array($comment->ip));
			// If you've already got two spams on your IP address, all you ever do is spam
			if($spams > 1) {
				$comment->status= Comment::STATUS_SPAM;
				$spamcheck[] = sprintf(_t('Too many existing spams from this IP: %s'), long2ip($comment->ip)); 
			}
		}

		if ( $comment->status != Comment::STATUS_SPAM ) {
			$spamcheck[] = sprintf(_t('No spam detected by spamchecker on comment #%s'), $comment->id); 
		}
		
		if(is_array($comment->info->spamcheck)) {
			$comment->info->spamcheck = array_unique(array_merge($comment->info->spamcheck, $spamcheck));
		}
		else {
			$comment->info->spamcheck = $spamcheck;
		}

		// otherwise everything looks good
		// so continue processing the comment
		return;
	}
}

?>