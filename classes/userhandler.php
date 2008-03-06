<?php

/**
 * Habari UserHandler Class
 *
 * @package Habari
 */
class UserHandler extends ActionHandler
{
	// The active user theme
	private $theme= null;

	/**
	 * Checks a user's credentials, and creates a session for them
	 */
	public function act_login()
	{
		$name= Controller::get_var( 'habari_username' );
		$pass= Controller::get_var( 'habari_password' );

		if ( ( NULL != $name ) || ( NULL != $pass ) ) {
			$user= User::authenticate( $name, $pass );

			if ( ( $user instanceOf User ) && ( FALSE != $user ) ) {
				/* Successfully authenticated. */
				// Timestamp last login date and time.
				$user->info->authenticate_time= date( 'Y-m-d H:i:s' );
				$user->update();

				$login_session= Session::get_set( 'login' );
				if ( ! empty( $login_session ) ) {
					/* Now that we know we're dealing with the same user, transfer the form data so he does not lose his request */
					if ( ! empty( $login_session['post_data'] ) ) {
						Session::add_to_set( 'last_form_data', $last_form_data['post'], 'post' );
					}
					if ( ! empty( $login_session['get_data'] ) ) {
						Session::add_to_set( 'last_form_data', $last_form_data['get'], 'get' );
					}
					
					/* Redirect to the correct admin page */
					$dest= explode( '/', substr( $login_session['original'], strpos( $login_session['original'], 'admin/' ) ) );
					if ( '' == $dest[0] ) {
						Utils::redirect( Site::get_url( 'admin' ) );
					}
					else {
						// Replace '?' with '&' in $dest[1] before call URL::get()
						// Therefore calling URL::get() with a query string
						$dest[1]= str_replace( '?', '&', $dest[1] );
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
			$this->handler_vars['habari_password']= '';
			$this->handler_vars['error']= 'Bad credentials';
		}

		// Display the login form.
		$this->theme= Themes::create();
		if ( !$this->theme->template_exists( 'login' ) ) {
			$this->theme= Themes::create( 'admin', 'RawPHPEngine', Site::get_dir( 'admin_theme', TRUE ) );
			$this->theme->assign( 'admin_page', 'login' );
		}
		$request= new StdClass();
		foreach ( RewriteRules::get_active() as $rule ) {
			$request->{$rule->name}= ( $rule->name == URL::get_matched_rule()->name );
		}
		$this->theme->assign( 'request', $request );
		$this->display( 'login' );
		return TRUE;
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
		if ( $user = User::identify() ) {
			// delete the cookie, and destroy the object
			$user->forget();
			$user= null;
		}
		Utils::redirect(Site::get_url('habari'));
	}

  /**
   * Helper function which automatically assigns all handler_vars
   * into the theme and displays a theme template
   *
   * @param template_name Name of template to display (note: not the filename)
   */
	protected function display( $template_name )
	{
    /*
     * Assign internal variables into the theme (and therefore into the theme's template
     * engine.  See Theme::assign().
     */
		foreach ( $this->handler_vars as $key=>$value ) {
			$this->theme->assign($key, $value);
		}
		$this->theme->display($template_name);
	}

}
?>
