<?php
/**
 * Habari UserHandler Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */
class UserHandler extends ActionHandler {

  private $theme= null;             // The active user theme

	/**
	 * Checks a user's credentials, and creates a session for them
	 */
	public function act_login() {
    $name= isset($this->handler_vars['name']) ? $this->handler_vars['name'] : '';
    $pass= isset($this->handler_vars['pass']) ? $this->handler_vars['pass'] : '';
    $user= User::authenticate($name, $pass);
    if ($user === FALSE) {
      //$url->settings['error'] = "badlogin";
			// unset the password the use tried
			$this->handler_vars['pass']= '';
      $this->handler_vars['error']= 'Invalid login'; /** @todo Use real error handling */ 
      /* Since we failed, display the theme's login template */
      $this->theme= new Theme();
      $this->display('login');
      return true;     
		}
    else {
      /* OK, so they authenticated.  What now?  Redirect to admin dashboard? */
      $this->handler_vars['user']= $user; // Assign into handler and theme
      $this->theme= new Theme('admin', 'RawPHPEngine', HABARI_PATH . '/system/admin/');
      $this->display('dashboard');
      return true;
    }
	}

	/**
	 * Adds a comment to a post, if the comment content is not NULL
	 */
	public function act_add_comment() {
		if( $this->handler_vars['content'] != '')  {
			$commentdata = array( 
								'post_id'	=>	$this->handler_vars['post_id'],
								'name'		=>	$this->handler_vars['name'],
								'email'		=>	$this->handler_vars['email'],
								'url'		=>	$this->handler_vars['url'],
								'ip'		=>	preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR'] ),
								'content'	=>	$this->handler_vars['content'],
								'status'	=>	Comment::STATUS_UNAPPROVED,
								'date'		=>	gmdate('Y-m-d H:i:s'),
								'type' => Comment::COMMENT
						 	);
			// Comment::create( $commentdata );  // This creates and saves, let's filter first
			$comment = new Comment( $commentdata );
			$comment = Plugins::filter('add_comment', $comment);
			if( $comment->email == User::identify()->email ) {
				$comment->status = Comment::STATUS_APPROVED;
			} elseif( Comments::get( array( 'email' => $comment->email, 'status' => 1 ) )->count ) {
				$comment->status = Comment::STATUS_APPROVED;
			}
			$comment->insert();
			// if no cookie exists, we should set one
			$cookie = 'comment_' . Options::get('GUID');
			if ( ( ! User::identify() ) && ( ! isset( $_COOKIE[$cookie] ) ) )
			{
				$cookie_content = $comment->name . '#' . $comment->email . '#' . $comment->url;
				setcookie( $cookie, $cookie_content, time() + 31536000, Options::get('siteurl') );
			}
			Utils::redirect(URL::get('display_posts_by_slug', array('slug'=>$this->handler_vars['post_slug'])));
		} 
		else
		{
			// do something more intelligent here
			echo 'You forgot to add some content to your comment, please <a href="' . URL::get( 'post', "slug={$this->handler_vars['post_slug']}" ) . '" title="go back and try again!">go back and try again</a>.';
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
