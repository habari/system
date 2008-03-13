<?php
/**
 * Habari FeedbackHandler Class
 * Deals with feedback mechnisms: Commenting, Pingbacking, and the like.
 *
 * @package Habari
 */

class FeedbackHandler extends ActionHandler
{
	/**
	* function add_comment
	* adds a comment to a post, if the comment content is not NULL
	* @param array An associative array of content found in the $_POST array
	*/
	public function act_add_comment()
	{
		// We need to get the post anyway to redirect back to the post page.
		$post= Post::get( array( 'id'=>$this->handler_vars['id'] ) );
		if( !$post ) {
			// trying to comment on a non-existant post?  Weirdo.
			header('HTTP/1.1 403 Forbidden', true, 403);
			die();
		}

		// let's do some basic sanity checking on the submission
		if ( ( 1 == Options::get( 'comments_require_id' ) ) && ( empty( $this->handler_vars['name'] ) || empty( $this->handler_vars['email'] ) ) ) {
			Session::error(_t( 'Both name and e-mail address must be provided.' ) );
		}
		
		// is the user logged in?
		if (!User::identify() || (User::identify()->username == $this->handler_vars['name']) || (User::identify()->email == $this->handler_vars['email'])) {
			// let's check if we're dealing with a con artist, but we'll be polite and offer an advice
			$user_match= false;
			if ( Users::get( array( 'count' => true, 'username' => $this->handler_vars['name'] ) ) ) {
				Session::error(_t('The name you have supplied matches an existing user, please provide a different name.'));
				$user_match= true;
			}
		
			if ( Users::get( array( 'count' => true, 'email' => $this->handler_vars['email'] ) ) ) {
				Session::error(_t('The email address you have supplied matches an existing user, please provide a different email address.'));
				$user_match= true;
			}
		
			if ( $user_match ) {
				Session::notice(_t('You must log in if you wish to post your comment using those credentials.'));
			}
		}

		if ( empty( $this->handler_vars['content'] ) ) {
			Session::error( _t('You did not provide any content for your comment!') );
		}

		if ( Session::has_errors() ) {
			// save whatever was provided in session data
			Session::add_to_set('comment', $this->handler_vars['name'], 'name');
			Session::add_to_set('comment', $this->handler_vars['email'], 'email');
			Session::add_to_set('comment', $this->handler_vars['url'], 'url');
			Session::add_to_set('comment', $this->handler_vars['content'], 'content');
			// now send them back to the form
			Utils::redirect( $post->permalink . '#respond' );
			exit();
		}

		if ( $post->info->comments_disabled ) {
			// comments are disabled, so let's just send
			// them back to the post's permalink
			Session::error( _t( 'Comments on this post are disabled!' ) );
			Utils::redirect( $post->permalink );
			exit();
		}

		/* Sanitize data */
		foreach ( array( 'name', 'email', 'url', 'content' ) as $k ) {
			$this->handler_vars[$k]= InputFilter::filter( $this->handler_vars[$k] );
		}

		/* Sanitize the URL */
		$url= $this->handler_vars['url'];
		$parsed= InputFilter::parse_url( $url );
		if ( $parsed['is_relative'] ) {
			// guess if they meant to use an absolute link
			$parsed= InputFilter::parse_url( 'http://' . $url );
			if ( ! $parsed['is_error'] ) {
				$url= InputFilter::glue_url( $parsed );
			}
			else {
				// disallow relative URLs
				$url= '';
			}
		}
		elseif ( $parsed['scheme'] !== 'http' && $parsed['scheme'] !== 'https' ) {
			// allow only http(s) URLs
			$url= '';
		}
		else {
			// reconstruct the URL from the error-tolerant parsing
			// http:moeffju.net/blog/ -> http://moeffju.net/blog/
			$url= InputFilter::glue_url( $parsed );
		}
		$this->handler_vars['url']= $url;
		
		$cleaned_content= preg_replace( '/^\s+/', '', $this->handler_vars['content'] );
		if ( $cleaned_content === '' ) {
		    Session::error( _t( 'Comment contains only whitespace/empty comment' ) );
			Utils::redirect( $post->permalink );
	        exit();
		}

		/* Create comment object*/
		$comment= new Comment( array(
			'post_id'	=> $this->handler_vars['id'],
			'name' => $this->handler_vars['name'],
			'email' => $this->handler_vars['email'],
			'url' => $this->handler_vars['url'],
			'ip' => ip2long( $_SERVER['REMOTE_ADDR'] ),
			'content'	=> $this->handler_vars['content'],
			'status' =>	Comment::STATUS_UNAPPROVED,
			'date' =>	date( 'Y-m-d H:i:s' ),
			'type' => Comment::COMMENT,
	 	) );

		// Should this really be here or in a default filter?
		// In any case, we should let plugins modify the status after we set it here.
		if( ( $user = User::identify() ) && ( $comment->email == $user->email ) ) {
			$comment->status= Comment::STATUS_APPROVED;
		}

		$spam_rating= 0;
		$spam_rating= Plugins::filter('spam_filter', $spam_rating, $comment, $this->handler_vars);

		$comment->insert();
		if ( $comment->id ) {
			$anchor= '#comment-' . $comment->id;
		} else {
			$anchor= '';
		}

		// store in the user's session that this comment is pending moderation
		if ( $comment->status == Comment::STATUS_UNAPPROVED ) {
			Session::notice(_t('Your comment is pending moderation.'), 'comment_' . $comment->id );
		}

		// if no cookie exists, we should set one
		// but only if the user provided some details
		// and the comment was actually saved
		$cookie= 'comment_' . Options::get('GUID');
		if ( ( ! User::identify() )
			&& ( $comment->id )
			&& ( ! isset( $_COOKIE[$cookie] ) )
			&& ( ! empty( $this->handler_vars['name'] )
				|| ! empty( $this->handler_vars['email'] )
				|| ! empty( $this->handler_vars['url'] )
			)
		)
		{
			$cookie_content = $comment->name . '#' . $comment->email . '#' . $comment->url;
			$site_url= Site::get_path('base',true);
			setcookie( $cookie, $cookie_content, time() + 31536000, $site_url );
		}

		// Return the commenter to the original page.
		Utils::redirect( $post->permalink . $anchor );
	}
}
?>
