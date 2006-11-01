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
	public function __construct( $action, $settings )
	{
		parent::__construct( $action, $settings );
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
	* function dashboard
	* display an overview of current blog stats
	*/
	public function dashboard()
	{
		$this->header();
		echo "Hiya! Welcome to your dashboard.";
		$this->footer();
	}

	/**
	* function admin
	* figures out what admin page to show, and displays it to the user
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function admin( $settings = null)
	{
		// check that the user is logged in, and redirect
		// to the login page, if not
		if (! user::identify() )
		{
		}
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

	/**
	* function posthandler
	* called from URL for /admin/post/foo URLS.  Ensures a user is logged in, and executes the method "foo" if it exists.
	* @param array An associative array of settings found in the URL by the URL 
	*/
	public function posthandler ($settings = null)
	{
		// is this request from a valid, logged-in user?
		if ( ! User::identify() )
		{
			// nope?  redirect to a login page, of some sort
			echo "Please log in.";
			die;
		}
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

	public function options()
	{
		foreach ($_POST as $option => $value)
		{
			if ( Options::get($option) != $value )
			{
				Options::set($option, $value);
			}
		}
		header("Location: " . Options::get('base_url') . "admin/options/");
	}

}

?>
