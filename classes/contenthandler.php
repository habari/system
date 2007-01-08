<?php
/**
 * Habari ContentHandler Class
 *
 * @package Habari
 */

class ContentHandler extends ActionHandler
{
	/**
	* function add_comment
	* adds a comment to a post, if the comment content is not NULL
	* @param array An associative array of content found in the $_POST array 
	*/
	public function add_comment( $settings )
	{
		if( $_POST['content'] != '') 
		{
			$commentdata = array( 
								'post_slug'	=>	$_POST['post_slug'],
								'name'		=>	$_POST['name'],
								'email'		=>	$_POST['email'],
								'url'		=>	$_POST['url'],
								'ip'		=>	preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR'] ),
								'content'	=>	$_POST['content'],
								'status'	=>	Comment::STATUS_UNAPPROVED,
								'date'		=>	gmdate('Y-m-d H:i:s'),
								'type' => Comment::COMMENT
						 	);
			// Comment::create( $commentdata );  // This creates and saves, let's filter first
			$comment = new Comment( $commentdata );
			$comment = Plugins::filter('add_comment', $comment);
			if( $comment->email == User::identify()->email ) {
				$comment->status = Comment::STATUS_APPROVED;
			} elseif( Comments::get( array( 'email' => $comment->email, 'status' => 1 ) )->count ) {
				$comment->status = Comment::STATUS_APPROVED;
			}
			$comment->insert();
			// if no cookie exists, we should set one
			$cookie = 'comment_' . Options::get('GUID');
			if ( ( ! User::identify() ) && ( ! isset( $_COOKIE[$cookie] ) ) )
			{
				$cookie_content = $comment->name . '#' . $comment->email . '#' . $comment->url;
				setcookie( $cookie, $cookie_content, time() + 31536000, Options::get('siteurl') );
			}
			Utils::redirect( URL::get( 'post', "slug={$_POST['post_slug']}", false ) );
		} 
		else
		{
			// do something more intelligent here
			echo 'You forgot to add some content to your comment, please <a href="' . URL::get( 'post', "slug={$_POST['post_slug']}" ) . '" title="go back and try again!">go back and try again</a>.';
		}
	}
}
?>
