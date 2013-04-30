<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari UserHandler Class
 *
 */
class UserHandler extends ActionHandler
{

	/**
	 * Either just display the login form; or check a user's credentials, and
	 * create a session for them; or handle a password reset request.
	 */
	public function act_login()
	{
		// Display the login form.
		$this->login_form();
	}

	public function loginform_do_reset($form)
	{
		$name = $form->habari_username->value;
		if ( empty($name) ) {
			Session::error( _t( 'You must supply a username to reset its password.' ) );
		}
		else {
			if ( !is_numeric( $name ) && $user = User::get( $name ) ) {
				$hash = Utils::random_password();

				$user->info->password_reset = md5( $hash );
				$user->info->commit();
				$message = _t( 'Please visit %1$s to reset your password.', array( URL::get( 'auth', array( 'page' => 'password_reset', 'id' => $user->id, 'hash' => $hash ) ) ) );

				Utils::mail( $user->email, _t( '[%1$s] Password reset request for %2$s', array( Options::get( 'title' ), $user->displayname ) ), $message );
			}
			// Moving this inside the check for user existence would allow attackers to test usernames, so don't
			Session::notice( _t( 'A password reset request has been sent to the user.' ) );
		}
	}
	
	public function loginform_do_login($form)
	{
		$name = $form->habari_username->value;
		$pass = $form->habari_password->value;

		if ( ( null != $name ) || ( null != $pass ) ) {
			$user = User::authenticate( $name, $pass );

			if ( ( $user instanceOf User ) && ( $user != false ) ) {

				// if there's an unused password reset token, unset it to make sure there's no possibility of a compromise that way
				if ( isset( $user->info->password_reset ) ) {
					unset( $user->info->password_reset );
				}

				/* Successfully authenticated. */
				// Timestamp last login date and time.
				$user->info->authenticate_time = DateTime::create()->format( 'Y-m-d H:i:s' );
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
					$dest = explode( '/', MultiByte::substr( $login_session['original'], MultiByte::strpos( $login_session['original'], 'admin/' ) ) );
					if ( '' == $dest[0] ) {
						$login_dest = Site::get_url( 'admin' );
					}
					else {
						// Replace '?' with '&' in $dest[1] before call URL::get()
						// Therefore calling URL::get() with a query string
						$dest[1] = str_replace( '?', '&', $dest[1] );
						$login_dest = URL::get( 'admin', 'page=' . $dest[1] );
					}
				}
				else {
					$login_session = null;
					$login_dest = Site::get_url( 'admin' );
				}

				// filter the destination
				$login_dest = Plugins::filter( 'login_redirect_dest', $login_dest, $user, $login_session );

				// finally, redirect to the destination
				Utils::redirect( $login_dest );

				return true;
			}

			/* Authentication failed. */
			// Remove submitted password, see, we're secure!
			$form->habari_password->value = '';
			$this->handler_vars['error'] = _t( 'Bad credentials' );
		}
	}

	/**
	* function logout
	* terminates a user's session, and deletes the Habari cookie
	* @param string the Action that was in the URL rule
	* @param array An associative array of settings found in the URL by the URL
	*/
	public function act_logout()
	{
		Utils::check_request_method( array( 'GET', 'HEAD', 'POST' ) );

		// get the user from their cookie
		$user = User::identify();
		if ( $user->loggedin ) {
			Plugins::act( 'user_logout', $user );
			// delete the cookie, and destroy the object
			$user->forget();
			$user = null;
		}
		Utils::redirect( Site::get_url( 'site' ) );
	}

	/**
	 * Display the login form
	 *
	 * @param string $name Pre-fill the name field with this name
	 */
	protected function login_form()
	{
		// Build theme and login page template
		$this->setup_theme();
		if ( !$this->theme->template_exists( 'login' ) ) {
			$this->theme = Themes::create( 'admin', 'RawPHPEngine', Site::get_dir( 'admin_theme', true ) );
			$this->theme->assign( 'admin_page', 'login' );
		}
		
		// Build the login form
		$form = new FormUI( 'habari_login' );
		//$form->on_success( array( $this, 'loginform_success' ) );
		$login_form_title = sprintf('<h1><a href="%s" title="%s"><img src="%s" style="height:1em;margin-right:10px;vertical-align:top;">%s</a></h1>', Site::get_url('site'), _t('Go to Site'), Site::get_url('habari', '/system/admin/images/habari.logo.png'), Options::get('title') );
		$form->append( FormControlStatic::create('title')->set_static($login_form_title) );
		$form->append( FormControlStatic::create('reset_message')->set_static('<p id="reset_message" class="on_reset">' . _t('Please enter the username you wish to reset the password for.  A unique password reset link will be emailed to that user.') . '</p>' ) );
		$form->append( FormControlLabel::wrap(_t('Name'), FormControlText::create('habari_username'))->set_template('control.label.outsideleft'));
		$form->append(
			FormControlLabel::wrap(
				_t('Password'),
				FormControlPassword::create(
					'habari_password',
					null,
					array(
						'class'=>'off_reset',
					)
				)
			)->set_template('control.label.outsideleft')
				->set_properties(array('class'=>'off_reset'))
		);
		$form->append( $drop_button = FormControlDropbutton::create('submit_button')->add_template_class('ul', 'off_reset'));
		$drop_button->append(FormControlSubmit::create('login')->on_success(array($this, 'loginform_do_login'))->set_caption(_t('Login')));
		$form->append( FormControlStatic::create('reset_link')->set_static('<a href="#" class="off_reset reset_link">' . _t('Reset password') . '</a>') );
		$form->append( FormControlStatic::create('login_link')->set_static('<a href="#" class="on_reset reset_link">' . _t('Login') . '</a>') );
		$form->append( FormControlSubmit::create('reset_button')->set_caption(_t('Reset password'))->set_properties(array('class'=>'on_reset'))->on_success(array($this, 'loginform_do_reset')) );

		// Use the dropbutton's visualizer to select the primary action for form submission upon pressing enter
		$form->set_settings(array(
			'prefix_html' => '<script>$(function(){$("body").on("keypress", "form[name=' . $form->input_name() . ']", function(e){if(e.which==13){$(this).find("#' . $form->submit_button->get_visualizer() . ' .primary").click();return e.preventDefault()&&false;}});})</script>',
		));

		// Let plugins alter this form
		Plugins::act( 'form_login', $form );
		
		// Assign login form and display the page
		$this->theme->form = $form;
		$this->display( 'login' );
		
		return true;
	}

	/**
	 * Helper function which automatically assigns all handler_vars
	 * into the theme and displays a theme template
	 *
	 * @param template_name Name of template to display (note: not the filename)
	 */
	protected function display( $template_name )
	{
		$this->theme->display( $template_name );
	}

	/**
	 * Handle password reset confirmations
	 */
	public function act_password_reset()
	{
		Utils::check_request_method( array( 'GET', 'HEAD', 'POST' ) );

		$id = $this->handler_vars['id'];
		$hash = $this->handler_vars['hash'];

		if ( $user = User::get( $id ) ) {
			if ( is_string( $hash ) && ( $user->info->password_reset == md5( $hash ) ) ) {
				// Send a new random password
				$password = Utils::random_password();

				$user->password = Utils::crypt( $password );
				if ( $user->update() ) {
					$message = _t( "Your password for %1\$s has been reset.  Your credentials are as follows---\nUsername: %2\$s\nPassword: %3\$s", array( Site::get_url( 'habari' ), $user->username, $password ) );

					Utils::mail( $user->email, _t( '[%1$s] Password has been reset for %2$s', array( Options::get( 'title' ), $user->displayname ) ), $message );
					Session::notice( _t( 'A new password has been sent to the user.' ) );
				}
				else {
					Session::notice( _t( 'There was a problem resetting the password.  It was not reset.' ) );
				}

				// Clear the request - it should only work once
				unset( $user->info->password_reset );
				$user->info->commit();
			}
			else {
				Session::notice( _t( 'The supplied password reset token has expired or is invalid.' ) );
			}
		}
		// Display the login form.
		Utils::redirect( URL::get( 'auth', array( 'page' => 'login' ) ) );
	}

}
?>
