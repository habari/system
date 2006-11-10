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
		if ( $settings['action'] == 'login' ) {
			if( !($user = user::authenticate( $_POST['name'], $_POST['pass'] ) )  ) {
				// set an error.
				$url->settings['error'] = "badlogin";
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

	/**
	* function changepass
	* changes a user's password
	* @param string the Action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function changepass( $settings ) {

		if ( isset( $settings['password'] ) && $user = user::identify() ) {
			$user->password = sha1($settings['password']);
			$user->update();
		}
	}

	/**
	* function newuser
	* adds a new user
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function newuser($settings = null) {
		global $db, $url;
		$user = User::identify();
		if ( ! $user ) {
			die ('Naughty Naughty!');
		}
		$error = '';
		if ( $settings['action'] == 'newuser' ) {
			// sanity check the post
			if ( $_POST['password'] != $_POST['checkpass'] ) {
				$error .= "Password mis-match!<br />";
			}
			if ( '' == $_POST['username'] ) {
				$error .= "Please supply a user name!<br />";
			}
			if ( '' == $_POST['password'] ) {
				$error .= "Please supply a password!<br />";
			}
			if ( '' == $_POST['email'] ) {
				$error .= "Please supply an email address!<br />";
			}
			if ( ! $error ) {
				$user = new User ( array (
						'username' => $settings['username'],
						'password' => sha1($settings['password']),
						'email' => $settings['password']  )
						);
				if ( $user->insert() ) {
					echo "User " . $settings['username'] . " created!<br />";
					echo "Click <a href='" . $url->get_url('home') . "'>here</a> to return to the home page.";
					die();
				} else {
					$dberror = DB::get_last_error();
					$error .= $dberror[2];
				}
			}
		} 
		// display the form to create the new user
		if ( $error ) {
			echo "<p><strong>$error</strong></p>";
		}
		echo "<form method='post'>";
		echo "Login: <input type='text' size='40' name='username' value='" . $settings['username'] . "' /><br />";
		echo "Email: <input type='text' size='40' name='email' value='" . $settings['email'] . "' /><br />";
		echo "Password: <input type='password' name='password' size='35' /><br />";
		echo "Confirm:&nbsp;&nbsp;&nbsp; <input type='password' name='checkpass' size='35' /><br />";
		echo "<input type='hidden' name='action' value='newuser' />";
		echo "<input type='submit' value='Create!' />";
		echo "</form>";
	}

}
?>
