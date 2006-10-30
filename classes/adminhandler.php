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
	* @param string The action that was in the URLParser rule
	* @param array An associative array of settings found in the URL by the URLParser
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

}

?>
