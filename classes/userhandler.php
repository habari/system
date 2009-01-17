<?php
/**
 * @package Habari
 *
 */

/**
 * Habari UserHandler Class
 *
 */
class UserHandler extends ActionHandler
{
	// The active user theme
	private $theme = null;

	/**
	 * Checks a user's credentials, and creates a session for them
	 */
	public function act_login()
	{
		$name = $this->handler_vars['habari_username'];
		$pass = $this->handler_vars['habari_password'];

		if ( ( NULL != $name ) || ( NULL != $pass ) ) {
			$user = User::authenticate( $name, $pass );

			if ( ( $user instanceOf User ) && ( FALSE != $user ) ) {
				/* Successfully authenticated. */
				// Timestamp last login date and time.
				$user->info->authenticate_time = date( 'Y-m-d H:i:s' );
				$user->update();

				// Remove left over expired session error message.
				if ( Session::has_errors( 'expired_session' ) ) {
					Session::remove_error( 'expired_session' );
				}

				$login_session = Session::get_set( 'login' );
				if ( ! empty( $login_session ) ) {
					/* Now that we know we're dealing with the same user, transfer the form data so he does not lose his request */
					if ( ! empty( $login_session['post_data'] ) ) {
						Session::add_to_set( 'last_form_data', $last_form_data['post'], 'post' );
					}
					if ( ! empty( $login_session['get_data'] ) ) {
						Session::add_to_set( 'last_form_data', $last_form_data['get'], 'get' );
					}

					/* Redirect to the correct admin page */
					$dest = explode( '/', substr( $login_session['original'], strpos( $login_session['original'], 'admin/' ) ) );
					if ( '' == $dest[0] ) {
						Utils::redirect( Site::get_url( 'admin' ) );
					}
					else {
						// Replace '?' with '&' in $dest[1] before call URL::get()
						// Therefore calling URL::get() with a query string
						$dest[1] = str_replace( '?', '&', $dest[1] );
						Utils::redirect( URL::get( 'admin', 'page=' . $dest[1] ) );
					}
				}
				else {
					Utils::redirect( Site::get_url( 'admin' ) );
				}
				return TRUE;
			}

			/* Authentication failed. */
			// Remove submitted password, see, we're secure!
			$this->handler_vars['habari_password'] = '';
			$this->handler_vars['error'] = _t('Bad credentials');
		}

		// Display the login form.
		$this->login_form($name);
	}

	/**
	* function logout
	* terminates a user's session, and deletes the Habari cookie
	* @param string the Action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function act_logout()
	{
		// get the user from their cookie
		$user = User::identify();
		if ( $user->loggedin ) {
			Plugins::act( 'user_logout', $user );
			// delete the cookie, and destroy the object
			$user->forget();
			$user = null;
		}
		Utils::redirect(Site::get_url('habari'));
	}

	/**
	 * Display the login form
	 *
	 * @param string $name Pre-fill the name field with this name
	 */
	protected function login_form($name)
	{
		// Display the login form.
		$this->theme = Themes::create();
		if ( !$this->theme->template_exists( 'login' ) ) {
			$this->theme = Themes::create( 'admin', 'RawPHPEngine', Site::get_dir( 'admin_theme', TRUE ) );
			$this->theme->assign( 'admin_page', 'login' );
		}
		$request = new StdClass();
		foreach ( RewriteRules::get_active() as $rule ) {
			$request->{$rule->name}= ( $rule->name == URL::get_matched_rule()->name );
		}
		$this->theme->assign( 'request', $request );
		$this->theme->assign( 'habari_username', htmlentities($name, ENT_QUOTES, 'UTF-8') );
		$this->display( 'login' );
		return TRUE;
	}

  /**
   * Helper function which automatically assigns all handler_vars
   * into the theme and displays a theme template
   *
   * @param template_name Name of template to display (note: not the filename)
   */
	protected function display( $template_name )
	{
		$this->theme->display($template_name);
	}

	/**
	 * Handle requests for a password reset
	 */
	public function act_password_request()
	{
		$name = $this->handler_vars['username'];
		if( !is_numeric($name) && $user = User::get($name) ) {
			$hash = Utils::random_password();

			$user->info->password_reset = md5($hash);
			$user->info->commit();
			$message = _t('Please visit %1$s to reset your password.', array(URL::get('user', array('page' => 'password_reset', 'id' => $user->id, 'hash' => $hash))));

			Utils::mail($user->email, _t('[%1$s] Password reset request for %2$s', array(Options::get('title'), $user->displayname)), $message);
		}
		// Moving this inside the check for user existence would allow attackers to test usernames, so don't
		Session::notice(_t('A password reset request has been sent to the user.'));
		// Display the login form.
		$this->login_form($name);
	}

	/**
	 * Handle password reset confirmations
	 */
	public function act_password_reset()
	{
		$id = $this->handler_vars['id'];
		$hash = $this->handler_vars['hash'];
		$name = '';

		if( $user = User::get($id) ) {
			if( $user->info->password_reset == md5($hash)) {
				// Send a new random password
				$password = Utils::random_password();

				$user->password = Utils::crypt( $password );
				if( $user->update() ) {
					$message = _t("Your password for %1\$s has been reset.  Your credentials are as follows---\r\nUsername: %2\$s\r\nPassword: %3\$s", array(Site::get_url('habari'), $user->username, $password));

					Utils::mail($user->email, _t('[%1$s] Password has been reset for %2$s', array(Options::get('title'), $user->displayname)), $message);
					Session::notice(_t('A new password has been sent to the user.'));
				}
				else {
					Session::notice(_t('There was a problem resetting the password.  It was not reset.'));
				}

				// Clear the request - it should only work once
				unset($user->info->password_reset);
				$user->info->commit();

				$name = $user->username;
			}
			else {
				Session::notice(_t('The supplied password reset token has expired or is invalid.'));
			}
		}
		// Display the login form.
		$this->login_form($name);
	}

}
?>