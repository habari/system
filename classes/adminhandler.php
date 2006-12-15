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
	* @param array An associative array of content found in the url, $_POST array, and $_GET array 
	*/
	public function post_dashboard($settings)
	{
		// do something intelligent here
		_e('Nothing sends POST requests to the dashboard. Yet.');
	}

	/**
	* function post_publish
	* Handles post requests from the publish page.
	* Adds a post to the site, if the post content is not NULL.
	* @param array An associative array of content found in the url, $_POST array, and $_GET array 
	*/
	public function post_publish($settings)
	{
		if( $_POST['content'] != '' )
		{
			if( isset( $_POST['slug'] ) ) {
				$post = Post::get( array( 'slug' => $_POST['slug'], 'status' => Post::STATUS_ANY ) );
				$post->title = $_POST['title'];
				$post->tags = $_POST['tags'];
				$post->content = $_POST['content'];
				$post->status = $_POST['status'];
				$post->update();
			}
			else {
				$postdata = array(
									'title'		=>	$_POST['title'],
									'tags'		=>	$_POST['tags'],
									'content'	=>	$_POST['content'],
									'user_id'	=>	User::identify()->id,
									'pubdate'	=>	date( 'Y-m-d H:i:s' ),
									'status'	=>	$_POST['status']
								 );
				$post = Post::create( $postdata );
			}
			Utils::redirect( Utils::de_amp( URL::get( 'admin', 'page=publish&result=success&slug=' . $post->slug ) ) );
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
		$currentuser = User::identify();
		$user = $currentuser;
		if ( $currentuser->id != $settings['user_id'] )
		{
			// user is editing someone else's profile
			// load that user account
			$user = User::get( $settings['user_id'] );
			$results = '/' . $user->username;
		}
		// are we deleting a user?
		if ( isset( $settings['delete'] ) && ( 'user' == $settings['delete'] ) )
		{
			// extra safety check here
			if ( isset( $settings['user_id'] ) && ( $currentuser->id != $settings['user_id'] ) )
			{
				$username = $user->username;
				$user->delete();
				$results = 'deleted';
			}
		}
		if ( isset( $settings['username'] ) && ( $user->username != $settings['username'] ) )
		{
			$user->username = $settings['username'];
			$update = 1;
			$results = '/' . $settings['username'];
		}
		if ( isset( $settings['email'] ) && ( $user->email != $settings['email'] ) )
		{
			$user->email = $settings['email'];
			$update = 1;
		}
		// see if a password change is being attempted
		if ( isset( $settings['pass1'] ) && ( '' != $settings['pass1'] ) )
		{
			if ( isset( $settings['pass2'] ) && ( $settings['pass1'] == $settings['pass2'] ) )
			{
				$user->password = sha1($settings['pass1']);
				if ( $user == $currentuser )
				{
					// update the cookie for the current user
					$user->remember();
				}
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

	/**
	 * public function post_users
	 * Handles post requests from the Users listing (ie: creating a new user)
	 * @param array An associative array of content found in the url, $_POST array, and $_GET array
	**/
	public function post_users( $settings )
	{
		$user = User::identify();
		if ( ! $user )
		{
			die ('Naughty naughty!');
		}
		$error = '';
		if ( isset( $settings['action'] ) && ( 'newuser' == $settings['action'] ) )
		{
			// basic safety checks
			if ( ! isset( $settings['username'] ) || '' == $settings['username'] )
			{
				$error .= 'Please supply a user name!<br />';
			}
			if ( ! isset( $settings['email'] ) || 
				( '' == $settings['username'] ) ||
				( ! strstr($settings['email'], '@') ) )
			{
				$error .= 'Please supply a valid email address!<br />';
			}
			if ( ( ! isset( $settings['pass1'] ) ) ||
				( ! isset( $settings['pass2'] ) ) ||
				( '' == $settings['pass1'] ) ||
				( '' == $settings['pass2'] ) )
			{
				$error .= 'Password mis-match!<br />';
			}
			if ( ! $error )
			{
				$user = new User ( array(
					'username' => $settings['username'],
					'email' => $settings['email'],
					'password' => sha1($settings['pass1']),
					) );
				if ( $user->insert() )
				{
					$settings['message'] = 'User ' . $settings['username'] . ' created!<br />';
					// clear out the other variables
					$settings['username'] = '';
					$settings['email'] = '';
					$settings['pass1'] = '';
					$settings['pass2'] = '';
				}
				else
				{
					$dberror = DB::get_last_error();
					$error .= $dberror[2];
				}
			}
		}
	}	

}

?>
