<?php

/**
 * Habari UserHandler Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */



class UserHandler extends ActionHandler
{

	/**
	* function login
	* checks a user's credentials, and creates a session for them
	* @param string the Action that was in the URLParser rule
	* @param array An associative array of settings found in the URL by the URLParser
	*/
	public function login($settings) {
		global $urlparser;
		if ( $settings['action'] == 'login' ) {
			$user = user::authenticate($_POST['name'], $_POST['pass']);
		}
		new ThemeHandler( 'login', $settings );
	}

	/**
	* function logout
	* terminates a user's session, and deletes the Habari cookie
	* @param string the Action that was in the URLParser rule
	* @param array An associative array of settings found in the URL by the URLParser
	*/
	public function logout($settings) {
		global $urlparser;
		
		// get the user from their cookie
		if ( $user = user::identify() )
		{
			// delete the cookie, and destroy the object
			$user->forget();
			$user = null;
		}
		new ThemeHandler( 'logout', $settings );
	}

	/**
	* function changepass
	* changes a user's password
	* @param string the Action that was in the URLParser rule
	* @param array An associative array of settings found in the URL by the URLParser
	*/
	public function changepass($action, $settings = null) {
	}

}
?>
