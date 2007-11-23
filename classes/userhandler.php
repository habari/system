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

				Utils::redirect( Site::get_url('admin') );
				return TRUE;
			}

			/* Authentication failed. */
			// Remove submitted password, see, we're secure!
			$this->handler_vars['habari_password']= '';
			$this->handler_vars['error']= 'Bad credentials';
		}

		// Display the login form.
		$this->theme= Themes::create();
		if(!$this->theme->template_engine->template_exists( 'login' )) {
			$this->theme= Themes::create( 'admin', 'RawPHPEngine', Site::get_dir( 'admin_theme', TRUE ) );
		}
		$this->display( 'login' );
		return TRUE;
	}

	/**
	* function logout
	* terminates a user's session, and deletes the Habari cookie
	* @param string the Action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function act_logout() {
		// get the user from their cookie
		if ( $user = user::identify() )
		{
			// delete the cookie, and destroy the object
			Utils::debug( $user );
			Utils::debug( $user->forget() );
			$user = null;
		}
		Utils::debug( $user );
		//$theme= new ThemeHandler( 'logout', $settings );
		die;
	}

  /**
   * Helper function which automatically assigns all handler_vars
   * into the theme and displays a theme template
   *
   * @param template_name Name of template to display (note: not the filename)
   */
  protected function display($template_name) {
    /*
     * Assign internal variables into the theme (and therefore into the theme's template
     * engine.  See Theme::assign().
     */
    foreach ($this->handler_vars as $key=>$value)
      $this->theme->assign($key, $value);
    $this->theme->display($template_name);
  }

}
?>
