<?php
/**
 * Habari ContentHandler Class
 *
 * @package Habari
 */

class ContentHandler extends ActionHandler
{

	/**
	* constructor __construct
	* verify that the page is being accessed by an admin
	* @param string The action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function __construct( $action, $settings )
	{
		parent::__construct( $action, $settings );
	}
	
	/**
	* function processhandler
	* called as admin/process/foo.  Eexecutes the method "foo" if it exists.
	* @param array An associative array of settings found in the URL by the URL 
	*/
	public function processhandler ($settings = null)
	{
		// now see if a method is registered to handle the POSTed action
		if ( method_exists ( $this, $settings['action'] ) )
		{
			call_user_func( array($this, $settings['action']), $settings );
		}
		else
		{
			// redirect to some useful error page
			echo "No such function.";
			die;
		}
	}	

	/**
	* function add_comment
	* adds a comment to a post, if the comment content is not NULL
	* @param array An associative array of content found in the $_POST array 
	*/
	public function add_comment()
	{
		if( $_POST['content'] != '') 
		{
			$settings = array( 
								'post_slug'	=>	$_POST['post_slug'],
								'name'		=>	$_POST['name'],
								'email'		=>	$_POST['email'],
								'url'		=>	$_POST['url'],
								'ip'		=>	preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR'] ),
								'content'	=>	$_POST['content'],
								'status'	=>	'0',
								'date'		=>	gmdate('Y-m-d H:i:s')
						 	);
		Comment::create( $settings );
		header("Location: " . URL::get( 'post', "slug={$_POST['post_slug']}" ) );
		} 
		else
		{
		// do something intelligent here
		echo 'You forgot to add some content to your comment, please <a href="' . URL::get( 'post', "slug={$_POST['post_slug']}" ) . '" title="go back and try again!">go back and try again</a>.';
		}
	}
}
?>
