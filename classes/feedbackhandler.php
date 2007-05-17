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
		
		if( $this->handler_vars['content'] != '') {
			if ( $post->info->comments_disabled ) {
				// comments are disabled, so let's just send
				// them back to the post's permalink
				Utils::redirect( URL::get( 'display_posts_by_slug', array('slug'=>$post->slug) ) );
			}

			if( $post && !$post->info->comments_disabled ) {
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
				
				$commentdata = array( 
									'post_id'	=>	$this->handler_vars['id'],
									'name'		=>	$this->handler_vars['name'],
									'email'		=>	$this->handler_vars['email'],
									'url'		=>	$url,
									'ip'		=>	ip2long( $_SERVER['REMOTE_ADDR'] ),
									'content'	=>	$this->handler_vars['content'],
									'status'	=>	Comment::STATUS_UNAPPROVED,
									'date'		=>	gmdate('Y-m-d H:i:s'),
									'type' => Comment::COMMENT
							 	);
	
				$comment = new Comment( $commentdata );
	
				// Should this really be here or in a default filter?
				// In any case, we should let plugins modify the status after we set it here.
				if( $comment->email == User::identify()->email ) {
					$comment->status = Comment::STATUS_APPROVED;
				}
	
				$spam_rating = 0; 			
				$spam_rating = Plugins::filter('spam_filter', $spam_rating, $comment, $this->handler_vars);
				$comment = Plugins::filter('add_comment', $comment, $this->handler_vars, $spam_rating);
			
				$comment->insert();
				
				// if no cookie exists, we should set one
				// but only if the user provided some details
				$cookie = 'comment_' . Options::get('GUID');
				if ( ( ! User::identify() ) 
					&& ( ! isset( $_COOKIE[$cookie] ) )
					&& ( ! empty($this->handler_vars['name'])
						|| ! empty($this->handler_vars['email'])
						|| ! empty($this->handler_vars['url'])
					)
				)
				{
					$cookie_content = $comment->name . '#' . $comment->email . '#' . $comment->url;
					$site_url= Site::get_path('base');
					if ( empty( $site_url ) )
					{
						$site_url= rtrim( $_SERVER['SCRIPT_NAME'], 'index.php' );
					}
					else
					{
						$site_url= '/' . $site_url . '/';
					}
					setcookie( $cookie, $cookie_content, time() + 31536000, $site_url );
				}
				
				// Return the commenter to the original page.
				// @todo We should probably add a method to display a message like, "your comment is in moderation"
				Utils::redirect( URL::get( 'display_posts_by_slug', array('slug'=>$post->slug) ) );
			}
			else {
				// do something more intelligent here
				echo 'Hey, that post doesn\'t exist, buddy.';
			}
		} 
		else {
			// do something more intelligent here
			echo 'You forgot to add some content to your comment, please <a href="' . URL::get( 'display_posts_by_slug', array('slug'=>$post->slug) ) . '" title="go back and try again!">go back and try again</a>.';
		}
	}
}
?>
