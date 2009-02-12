<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
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

		$defaults = array(
			'name' => '',
			'email' => '',
			'url' => '',
			'content' => ''
		);

		// We need to get the post anyway to redirect back to the post page.
		$post = Post::get( array( 'id'=>$this->handler_vars['id'] ) );
		if( !$post ) {
			// trying to comment on a non-existent post?  Weirdo.
			header('HTTP/1.1 403 Forbidden', true, 403);
			die();
		}

		// make sure all our default values are set so we don't throw undefined index errors
		foreach ( $defaults as $k => $v ) {
			if ( !isset( $this->handler_vars[ $k ] ) ) {
				$this->handler_vars[ $k ] = $v;
			}
		}

		// let's do some basic sanity checking on the submission
		if ( ( 1 == Options::get( 'comments_require_id' ) ) && ( empty( $this->handler_vars['name'] ) || empty( $this->handler_vars['email'] ) ) ) {
			Session::error(_t( 'Both name and e-mail address must be provided.' ) );
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
		}

		if ( $post->info->comments_disabled ) {
			// comments are disabled, so let's just send
			// them back to the post's permalink
			Session::error( _t( 'Comments on this post are disabled!' ) );
			Utils::redirect( $post->permalink );
		}

		/* Sanitize data */
		foreach ( $defaults as $k => $v ) {
			$this->handler_vars[$k] = InputFilter::filter( $this->handler_vars[$k] );
		}

		/* Sanitize the URL */
		if (!empty($this->handler_vars['url'])) {
			$url = $this->handler_vars['url'];
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
			$this->handler_vars['url'] = $url;
		}
		if ( preg_match( '/^\p{Z}*$/u', $this->handler_vars['content'] ) ) {
			Session::error( _t( 'Comment contains only whitespace/empty comment' ) );
			Utils::redirect( $post->permalink );
		}

		/* Create comment object*/
		$comment = new Comment( array(
			'post_id'	=> $this->handler_vars['id'],
			'name' => $this->handler_vars['name'],
			'email' => $this->handler_vars['email'],
			'url' => $this->handler_vars['url'],
			'ip' => sprintf("%u", ip2long( $_SERVER['REMOTE_ADDR'] ) ),
			'content'	=> $this->handler_vars['content'],
			'status' =>	Comment::STATUS_UNAPPROVED,
			'date' => HabariDateTime::date_create(),
			'type' => Comment::COMMENT,
		) );

		// Should this really be here or in a default filter?
		// In any case, we should let plugins modify the status after we set it here.
		$user = User::identify();
		if( ( $user->loggedin ) && ( $comment->email == $user->email ) ) {
			$comment->status = Comment::STATUS_APPROVED;
		}

		// Allow themes to work with comment hooks
		Themes::create();

		$spam_rating = 0;
		$spam_rating = Plugins::filter('spam_filter', $spam_rating, $comment, $this->handler_vars);

		$comment->insert();
		$anchor = '';

		// If the comment was saved
		if ( $comment->id ) {
			$anchor = '#comment-' . $comment->id;

			// store in the user's session that this comment is pending moderation
			if ( $comment->status == Comment::STATUS_UNAPPROVED ) {
				Session::notice(_t('Your comment is pending moderation.'), 'comment_' . $comment->id );
			}

			// if no cookie exists, we should set one
			// but only if the user provided some details
			$cookie = 'comment_' . Options::get('GUID');
			if ( ( ! $user->loggedin )
				&& ( ! isset( $_COOKIE[$cookie] ) )
				&& ( ! empty( $this->handler_vars['name'] )
					|| ! empty( $this->handler_vars['email'] )
					|| ! empty( $this->handler_vars['url'] )
				)
			)
			{
				$cookie_content = $comment->name . '#' . $comment->email . '#' . $comment->url;
				$site_url = Site::get_path('base',true);
				setcookie( $cookie, $cookie_content, time() + 31536000, $site_url );
			}
		}

		// Return the commenter to the original page.
		Utils::redirect( $post->permalink . $anchor );
	}
}
?>
