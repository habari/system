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
	* @param string the Action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function login($settings) {
		global $url;
		if ( isset($settings['action']) && ( 'login' == $settings['action'] ) ) {
			if( !($user = user::authenticate( $_POST['name'], $_POST['pass'] ) )  ) {
				// set an error.
				$url->settings['error'] = "badlogin";
				// unset the password the user tried
				$_POST['pass'] = '';
			}
		}
		new ThemeHandler( 'login', $settings );
	}

	/**
	* function logout
	* terminates a user's session, and deletes the Habari cookie
	* @param string the Action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function logout($settings) {
		global $url;
		
		// get the user from their cookie
		if ( $user = user::identify() )
		{
			// delete the cookie, and destroy the object
			$user->forget();
			$user = null;
		}
		new ThemeHandler( 'logout', $settings );
	}

}
?>
