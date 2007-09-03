<?php

/**
 * Habari UserHandler Class
 *
 * Requires PHP 5.0.4 or later
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
		$name= isset($this->handler_vars['habari_username']) ? $this->handler_vars['habari_username'] : '';
		$pass= isset($this->handler_vars['habari_password']) ? $this->handler_vars['habari_password'] : '';
		$user= User::authenticate($name, $pass);
		
		if ( FALSE === $user ) {
			//$url->settings['error'] = "badlogin";
			// unset the password the use tried
			$this->handler_vars['habari_password']= '';
			$this->handler_vars['error']= 'Invalid login'; /** @todo Use real error handling */ 
			/* Since we failed, display the theme's login template */
			$this->theme= Themes::create();
			$this->display('login');
			return true;
		}
		else
		{
			/* Successfully authenticated. */
			$this->handler_vars['user']= $user; // Assign into handler and theme
			$user->info->authenticate_time= date( 'Y-m-d H:i:s' ); // keep track of last login time
			$user->update();
			// Redirect to admin dashboard
			// XXX do we want to redirect to the referring page? 
			Utils::redirect( Site::get_url('admin') );
			return true;
		}
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
