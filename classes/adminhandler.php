<?php

/**
 * Habari AdminHandler Class
 *
 * @package Habari
 */

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
		if (! user::identify() ) {
			die ("Tricksey, eh?");
		}
		parent::__construct( $action, $settings );
	}

	/**
	 * Description
	 *
	 * @param array Settings array from the URLParser
	 **/
	public function wooga( $settings )
	{
		echo 'Woooga!<br />';
		var_dump($settings);
	}

	/**
	* function dashboard
	* display an overview of current blog stats
	*/
	public function dashboard()
	{
		echo "Hiya! Welcome to your dashboard.";
	}

	/**
	* function admin
	* figures out what admin page to show, and displays it to the user
	*/
	public function admin( $settings = null)
	{
		// the selected page is stored in $settings['page']
		if (method_exists( $this, $settings['page'] ))
		{
			call_user_func( array($this, $settings['page']), $settings );
		} else {
			echo "No such page!";
			die;
		}
	}

}

?>
