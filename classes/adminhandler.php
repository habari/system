<?php

/**
 * Habari AdminHandler Class
 *
 * @package Habari
 */

define('ADMIN_DIR', HABARI_PATH . '/system/admin/');

class AdminHandler extends ActionHandler
{

	/**
	* constructor __construct
	* verify that the page is being accessed by an admin
	* @param string The action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function __construct($action, $settings)
	{
		// check that the user is logged in, and redirect
		// to the login page, if not
		if (! User::identify() )
		{
			$settings['redirect'] = URL::get($action, $settings);
			Utils::redirect( URL::get( 'login', $settings ) );
			exit;
		}
		
		parent::__construct($action, $settings);
	}

	/**
	* function header
	* display the admin header
	*/
	public function header()
	{
		include ADMIN_DIR . 'header.php';
	}

	/**
	* function footer
	* display the amdin footer
	*/
	public function footer()
	{
		include ADMIN_DIR . 'footer.php';
	}

	/**
	* function admin
	* Figures out what admin page to show, and displays it to the user.
	* Calls post_{page}() function for post requests to the specific page.	
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function admin( $settings = null)
	{
		switch( $_SERVER['REQUEST_METHOD'] ) {
		case 'POST':
			// Handle POSTs to the admin pages
			$page = ( isset( $settings['page'] ) ) ? $page = $settings['page'] : $page = 'dashboard';
			$page = ( $page == '' ) ? 'dashboard' : $page;
			$fn = 'post_' . $page;
			if ( method_exists( $this, $fn ) ) { 
				$this->$fn( $settings );
				//call_user_func( array(&$this, $fn), $settings);
			}
			else {
				$classname = get_class($this);
				echo sprintf( __( "\n%s->%s() does not exist.\n" ), $classname, $fn );
				exit;
			}
			break;
		default:
			// Handle GETs of the admin pages
			$files = glob(ADMIN_DIR . '*.php');
			$filekeys = array_map(
			  create_function(
			    '$a',
			    'return basename($a, ".php");'
			  ),
			  $files
			);
			$map = array_combine($filekeys, $files);
			// Allow plugins to modify or add to $map here,
			// since plugins will not be installed to /system/admin
			if ( ! isset( $settings['page'] ) )
			{
				$settings['page'] = 'dashboard';
			}
			if ( isset( $map[$settings['page']] ) ) {
				$this->header();
				include $map[$settings['page']];
				$this->footer();
			}
			else
			{
			  // The requested console page doesn't exist
				$this->header();
				echo "Whooops!";
				$this->footer();
			}
		}
	}

	/**
	 * function post_options
	 * Handles post requests from the options admin page.
	 * Sets all of the set options.
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array 
	 **/	 	 	 	
	public function post_options($settings)
	{
		foreach ($_POST as $option => $value)
		{
			if ( Options::get($option) != $value )
			{
				Options::set($option, $value);
			}
		}
		Utils::redirect( URL::get('admin', 'page=options&result=success') );
	}
	
	/**
	* function post_dashboard
	* Handles post requests from the dashboard.
	* Adds a post to the site, if the post content is not NULL.
	* @param array An associative array of content found in the url, $_POST array, and $_GET array 
	*/
	public function post_dashboard($settings)
	{
		if( $_POST['content'] != '' )
		{
			$postdata = array(
								'title'		=>	$_POST['title'],
								'tags'		=>	$_POST['tags'],
								'content'	=>	$_POST['content'],
								'user_id'	=>	User::identify()->id,
								'pubdate'	=>	date( 'Y-m-d H:i:s' ),
								'status'	=>	'publish'
							 );
			Post::create( $postdata );
			Utils::redirect( URL::get( 'admin', 'result=success' ) );
		} 
		else 
		{
			// do something intelligent here
			_e('Danger Wil Robinson!  Danger!');
		}
	}

	/**
	 * function post_user
	 * Handles post requests from the user profile page
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array
	*/
	function post_user ( $settings )
	{
		// keep track of whether we actually need to update any fields
		$update = 0;
		$results = '';
		$user = User::identify();
		foreach ( array ('nickname', 'email' ) as $field )
		{
			// for each of the other fields in the user table,
			// see if it needs to be updated
			if ( $user->$field != $settings[$field] )
			{
				$user->$field = $settings[$field];
				$update = 1;
			}
		}
		// see if the user is changing their password
		if ( '' != $settings['pass1'] )
		{
			if ( $settings['pass1'] == $settings['pass2'])
			{
				$user->password = sha1($settings['pass1']);
				$user->remember();
				$update = 1;
			}
			else
			{
				$results = '&error=pass';
			}
		}
		if ( $update )
		{
			$user->update();
			$results .= "&results=success";
		}
		Utils::redirect( URL::get( 'admin', "page=user$results" ) );
	}

}

?>
