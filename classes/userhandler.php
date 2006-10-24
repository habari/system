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
	public function login($action, $settings = null) {
		$user = user::identify();
		if ($user) {
			echo "Hi again, " . $user->username . "!<br />";
			echo 'Would you care to <a href="http://localhost/habari/logout">logout</a>?';
			die;
		}
		if (! isset( $_POST['name'] ) ) {
			?>
<form method="post">
Name: <input type="text" size="25" name="name" /><br />
Pass: <input type="password" size="25" name="pass" /><br />
<input type="submit" value="GO!" />
</form>
			<?php
		} else {
			$user = user::authenticate($_POST['name'], $_POST['pass']);
			if (! $user)
			{
				 header("Location: http://localhost/habari/login?error");
			}
			echo "Welcome back, " . $user->username . "<br />";
			echo 'Would you care to <a href="http://localhost/habari/logout">logout</a>?';
		}
		die;
	}

	/**
        * function logout
        * terminates a user's session, and deletes the Habari cookie
        * @param string the Action that was in the URLParser rule
        * @param array An associative array of settings found in the URL by the URLParser
        */
	public function logout($action, $settings = null) {
		// get the user from their cookie
		$user = user::identify();
		if ( $user )
		{
			// delete the cookie, and destroy the object
			$user->forget();
			$user = null;
		}
		header("Location: http://localhost/habari/");
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
