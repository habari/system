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
								'status'	=>	'0',
								'date'		=>	gmdate('Y-m-d H:i:s')
						 	);
		Comment::create( $commentdata );
		Utils::redirect( URL::get( 'post', "slug={$_POST['post_slug']}" ) );
		} 
		else
		{
		// do something intelligent here
		echo 'You forgot to add some content to your comment, please <a href="' . URL::get( 'post', "slug={$_POST['post_slug']}" ) . '" title="go back and try again!">go back and try again</a>.';
		}
	}
}
?>
