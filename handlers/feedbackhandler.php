<?php
/**
 * @package Habari
 *
 */

/**
 * Habari FeedbackHandler Class
 * Deals with feedback mechnisms: Commenting, Pingbacking, and the like.
 *
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
		Utils::check_request_method( array( 'POST' ) );

		// We need to get the post anyway to redirect back to the post page.
		$post = Post::get( array( 'id' => $this->handler_vars['id'] ) );
		if ( ! $post ) {
			// trying to comment on a non-existent post?  Weirdo.
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
			die();
		}

		// Allow theme action hooks to work
		Themes::create();
		$form = $post->comment_form();
		$form->get( null, false );

		// Disallow non-FormUI comments
		if ( !$form->submitted ) {
			// Trying to submit a non-FormUI comment
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
			die();
		}
		else {

			// To be eventually incorporated more fully into FormUI.
			Plugins::act( 'comment_form_submit', $form );

			if ( $form->success ) {
				$this->add_comment(
					$post->id,
					$form->cf_commenter->value,
					$form->cf_email->value,
					$form->cf_url->value,
					$form->cf_content->value,
					$form->get_values()
				);
			}
			else {
				Session::error( _t( 'There was a problem submitting your comment.' ) );
				foreach ( $form->validate() as $error ) {
					Session::error( $error );
				}
				$form->bounce();
				//Utils::redirect( $post->permalink . '#respond' );
			}
		}
	}

	/**
	 * Add a comment to the site
	 *
	 * @param mixed $post A Post object instance or Post object id
	 * @param string $name The commenter's name
	 * @param string $email The commenter's email address
	 * @param string $url The commenter's website URL
	 * @param string $content The comment content
	 * @param array $extra An associative array of extra values that should be considered
	 */
	function add_comment( $post, $name = null, $email = null, $url = null, $content = null, $extra = null )
	{
		if ( is_numeric( $post ) ) {
			$post = Post::get( array( 'id' => $post ) );
		}

		if ( !$post instanceof Post ) {
			// Not sure what you're trying to pull here, but that's no good
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
			die();
		}

		// let's do some basic sanity checking on the submission
		if ( ( Options::get( 'comments_require_id' ) == true ) && ( empty( $name ) || empty( $email ) ) ) {
			Session::error( _t( 'Both name and e-mail address must be provided.' ) );
		}

		if ( empty( $content ) ) {
			Session::error( _t( 'You did not provide any content for your comment!' ) );
		}

		if ( Session::has_errors() ) {
			// save whatever was provided in session data
			Session::add_to_set( 'comment', $name, 'name' );
			Session::add_to_set( 'comment', $email, 'email' );
			Session::add_to_set( 'comment', $url, 'url' );
			Session::add_to_set( 'comment', $content, 'content' );
			// now send them back to the form
			Utils::redirect( $post->permalink . '#respond' );
		}

		if ( $post->info->comments_disabled ) {
			// comments are disabled, so let's just send
			// them back to the post's permalink
			Session::error( _t( 'Comments on this post are disabled!' ) );
			Utils::redirect( $post->permalink );
		}

		/* Sanitize data */
		foreach ( array( 'name', 'url', 'email', 'content' ) as $k ) {
			$$k = InputFilter::filter( $$k );
		}
		
		// there should never be any HTML in the name, so do some extra filtering on it
		$name = strip_tags( html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ) );

		/* Sanitize the URL */
		if ( !empty( $url ) ) {
			$parsed = InputFilter::parse_url( $url );
			if ( $parsed['is_relative'] ) {
				// guess if they meant to use an absolute link
				$parsed = InputFilter::parse_url( 'http://' . $url );
				if ( ! $parsed['is_error'] ) {
					$url = InputFilter::glue_url( $parsed );
				}
				else {
					// disallow relative URLs
					$url = '';
				}
			}
			if ( $parsed['is_pseudo'] || ( $parsed['scheme'] !== 'http' && $parsed['scheme'] !== 'https' ) ) {
				// allow only http(s) URLs
				$url = '';
			}
			else {
				// reconstruct the URL from the error-tolerant parsing
				// http:moeffju.net/blog/ -> http://moeffju.net/blog/
				$url = InputFilter::glue_url( $parsed );
			}
		}
		if ( preg_match( '/^\p{Z}*$/u', $content ) ) {
			Session::error( _t( 'Comment contains only whitespace/empty comment' ) );
			Utils::redirect( $post->permalink );
		}

		/* Create comment object*/
		$comment = new Comment( array(
			'post_id' => $post->id,
			'name' => $name,
			'email' => $email,
			'url' => $url,
			'ip' => Utils::get_ip(),
			'content' => $content,
			'status' => Comment::STATUS_UNAPPROVED,
			'date' => HabariDateTime::date_create(),
			'type' => Comment::COMMENT,
		) );

		// Should this really be here or in a default filter?
		// In any case, we should let plugins modify the status after we set it here.
		$user = User::identify();
		if ( ( $user->loggedin ) && ( $comment->email == $user->email ) ) {
			$comment->status = Comment::STATUS_APPROVED;
		}
		
		// Users need to have permission to add comments
		if ( !$user->can( 'comment' ) ) {
			Session::error( _t( 'You do not have permission to create comments.' ) );
			Utils::redirect( $post->permalink );
		}

		// Allow themes to work with comment hooks
		Themes::create();

		// Allow plugins to change comment data and add commentinfo based on plugin-added form fields
		Plugins::act( 'comment_accepted', $comment, $this->handler_vars, $extra );

		$spam_rating = 0;
		$spam_rating = Plugins::filter( 'spam_filter', $spam_rating, $comment, $this->handler_vars, $extra );
		
		if ( $spam_rating >= Options::get( 'spam_percentage', 100 ) ) {
			$comment->status = Comment::STATUS_SPAM;
		}

		$comment->insert();
		$anchor = '';

		// If the comment was saved
		if ( $comment->id && $comment->status != Comment::STATUS_SPAM ) { 
			$anchor = '#comment-' . $comment->id;

			// store in the user's session that this comment is pending moderation
			if ( $comment->status == Comment::STATUS_UNAPPROVED ) {
				Session::notice( _t( 'Your comment is pending moderation.' ), 'comment_' . $comment->id );
			}

			// if no cookie exists, we should set one
			// but only if the user provided some details
			$cookie_name = 'comment_' . Options::get( 'GUID' );
			
			// build the string we store for the cookie
			$cookie_content = implode( '#', array( $comment->name, $comment->email, $comment->url ) );
			
			// if the user is not logged in and there is no cookie OR the cookie differs from the current set
			if ( User::identify()->loggedin == false && ( !isset( $_COOKIE[ $cookie_name ] ) || $_COOKIE[ $cookie_name ] != $cookie_content ) ) {
				
				// update the cookie
				setcookie( $cookie_name, $cookie_content, time() + HabariDateTime::YEAR, Site::get_path( 'base', true ) );
				
			}
		}

		// Return the commenter to the original page.
		Utils::redirect( $post->permalink . $anchor );
	}

}
?>
