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
	public function wooga( $settings ) {
	}

	/**
	* function dashboard
	* display an overview of current blog stats
	*/
	public function dashboard() {
		echo "Hiya! Welcome to your dashboard.";
	}
}

?>
