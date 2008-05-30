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

		if( isset($comment->info->spamcheck) && is_array($comment->info->spamcheck)) {
			$comment->info->spamcheck = array_unique(array_merge($comment->info->spamcheck, $spamcheck));
		}
		else {
			$comment->info->spamcheck = $spamcheck;
		}

		// otherwise everything looks good
		// so continue processing the comment
		return;
	}


	/**
	 * Add a rule to replace the existing rule for creating a comment post url
	 *
	 * @param array $rules The array of rewrite rules to route incoming URL requests to handlers
	 * @return array The modified rules array
	 */
	public function filter_rewrite_rules($rules)
	{
		$rules[] = new RewriteRule(array(
			'name' => 'submit_feedback',
			'parse_regex' => '/^(?P<id>([0-9]+))\/(?P<ccode>([0-9a-f]+))\/feedback[\/]{0,1}$/i',
			'build_str' => '{$id}/{$ccode}/feedback',
			'handler' => 'FeedbackHandler',
			'action' => 'add_comment',
			'priority' => 7,
			'is_active' => 1,
		));

		return $rules;
	}

	/**
	 * Change some outgoing arguments supplied to rewrite rules during URL generation
	 *
	 * @param array $args The arguments passed to build a URL
	 * @param string $rulename The name of the URL that is to be built
	 * @return array The modified arguments
	 */
	public function filter_rewrite_args($args, $rulename)
	{
		switch($rulename) {
		case 'submit_feedback':
			$args['ccode'] = $this->get_code($args['id']);
			if( !isset($_SESSION['comments_allowed'])) {
				$_SESSION['comments_allowed'] = array();
			}
			$_SESSION['comments_allowed'][] = $args['ccode'];
			// Only allow comments on the last 10 posts you look at
			$_SESSION['comments_allowed'] = array_slice($_SESSION['comments_allowed'], -10);
			break;
		}

		return $args;
	}

	/**
	 * Ensure that the code assigne dto this user for their commenting URL is genuine
	 *
	 * @param float $spam_rating The spamminess of the comment as detected by other plugins
	 * @param Comment $comment The submitted comment object
	 * @param array $handlervars And array of handlervars passed in via the comment submission URL
	 * @return float The original spam rating
	 */
	function filter_spam_filter( $spam_rating, $comment, $handlervars )
	{
		// This plugin ignores non-comments
		if($comment->type != Comment::COMMENT) {
			return $comment;
		}

		if(!$this->verify_code($handlervars['ccode'], $comment->post_id)) {
			ob_end_clean();
			header('HTTP/1.1 403 Forbidden');
			die('<h1>' . _t('The selected action is forbidden.') . '</h1>');
		}
		if(!isset($_SESSION['comments_allowed']) || ! in_array($handlervars['ccode'], $_SESSION['comments_allowed'])) {
			ob_end_clean();
			header('HTTP/1.1 403 Forbidden');
			die('<h1>' . _t('The selected action is forbidden.') . '</h1>');
		}

		return $spam_rating;
	}

	/**
	 * Get a 10-digit hex code that identifies the user submitting the comment
	 * @param A post id to which the comment will be submitted
	 * @param The IP address of the commenter
	 * @return A 10-digit hex code
	 **/
	public function get_code($post_id, $ip = '')
	{
		if( $ip == '' ) {
			$ip= sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
		}
		$code = substr(md5( $post_id . Options::get('GUID') . 'more salt' . $ip ), 0, 10);
		$code = Plugins::filter('comment_code', $code, $post_id, $ip);
		return $code;
	}

	/**
	 * Verify a 10-digit hex code that identifies the user submitting the comment
	 * @param A post id to which the comment has been submitted
	 * @param The IP address of the commenter
	 * @return True if the code is valid, false if not
	 **/
	public function verify_code($suspect_code, $post_id, $ip = '')
	{
		if( $ip == '' ) {
			$ip= sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
		}
		$code = substr(md5( $post_id . Options::get('GUID') . 'more salt' . $ip ), 0, 10);
		$code = Plugins::filter('comment_code', $code, $post_id, $ip);
		return ($suspect_code == $code);
	}

}

?>
