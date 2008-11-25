<?php

class UserAdminPage extends AdminPage
{
	public function act_request_get()
	{
		// Get author list
		$author_list = Users::get_all();
		$authors[0] = _t('nobody');
		foreach ( $author_list as $author ) {
			$authors[ $author->id ]= $author->displayname;
		}
		$this->theme->authors = $authors;

		$this->theme->currentuser = User::identify();

		$this->theme->wsse = Utils::WSSE();

		$this->theme->display('user');

	}

	/**
	 * Handles post requests from the user profile page.
	 */
	public function act_request_post()
	{
		$extract = $this->handler_vars->filter_keys('nonce', 'timestamp', 'PasswordDigest');
		foreach($extract as $key => $value) {
			$$key = $value;
		}

		$wsse = Utils::WSSE( $nonce, $timestamp );
		if ( $PasswordDigest != $wsse['digest'] ) {
			Utils::redirect( URL::get( 'admin', 'page=users' ) );
		}

		// Keep track of whether we actually need to update any fields
		$update = FALSE;
		$results = array( 'page' => 'user' );
		$currentuser = User::identify();

		$fields = array( 'user_id' => 'id', 'delete' => NULL, 'username' => 'username', 'displayname' => 'displayname', 'email' => 'email', 'imageurl' => 'imageurl', 'pass1' => NULL, 'locale_tz' => 'locale_tz', 'locale_date_format' => 'locale_date_format', 'locale_time_format' => 'locale_time_format' );
		$fields = Plugins::filter( 'adminhandler_post_user_fields', $fields );
		$posted_fields = $this->handler_vars->filter_keys( array_keys( $fields ) );

		// Editing someone else's profile? If so, load that user's profile
		if ( isset($user_id) && ($currentuser->id != $user_id) ) {
			$user = User::get_by_id( $user_id );
			$results['user']= $user->username;
		}
		else {
			$user = $currentuser;
		}

		foreach ( $posted_fields as $posted_field => $posted_value ) {
			switch ( $posted_field ) {
				case 'delete': // Deleting a user
						if ( isset( $user_id ) && ( $currentuser->id != intval( $user_id ) ) ) {
							$username = $user->username;
							$posts = Posts::get( array( 'user_id' => $user_id, 'nolimit' => 1 ) );
							if ( isset( $reassign ) && ( 1 === intval( $reassign ) ) ) {
								// we're going to re-assign all of this user's posts
								$newauthor = isset( $author ) ? intval( $author ) : 1;
								Posts::reassign( $newauthor, $posts );
							}
							else {
								// delete posts
								foreach ( $posts as $post ) {
									$post->delete();
								}
							}
							$user->delete();
							Session::notice( sprintf( _t( '%s has been deleted' ), $username ) );
						}
					// redirect to main user list
					$results = array( 'page' => 'users' );
					Utils::redirect( URL::get( 'admin', $results ) );
					break;
				case 'username': // Changing username
					if ( isset( $username ) && ( $user->username != $username ) ) {
						// make sure the name isn't already used
						if ( $test = User::get_by_name( $username ) ) {
							Session::error( _t( 'That username is already in use!' ) );
							break;
						}
						$old_name = $user->username;
						$user->username = $username;
						Session::notice( sprintf( _t( '%1$s has been renamed to %2$s.' ), $old_name, $username ) );
						$results['user']= $username;
						$update = TRUE;
					}
					break;
				case 'email': // Changing e-mail address
					if ( isset( $email ) && ( $user->email != $email ) ) {
						$user->email = $email;
						Session::notice( sprintf( _t( '%1$s email has been changed to %2$s' ), $user->username, $email ) );
						$update = TRUE;
					}
					break;
				case 'pass1': // Changing password
					if ( isset( $pass1 ) && ( !empty( $pass1 ) ) ) {
						if ( isset( $pass2 ) && ( $pass1 == $pass2 ) ) {
							$user->password = Utils::crypt( $pass1 );
							if ( $user == $currentuser ) {
								$user->remember();
							}
							Session::notice( _t( 'Password changed successfully.' ) );
							$update = TRUE;
						}
						else {
							Session::error( _t( 'The passwords did not match, and were not changed.' ) );
						}
					}
					break;
				default:
					if ( isset( $this->handler_vars[$fields[$posted_field]] ) && ( $user->info->$fields[$posted_field] != $this->handler_vars[$fields[$posted_field]] ) ) {
						$user->info->$fields[$posted_field]= $this->handler_vars[$fields[$posted_field]];
						Session::notice( _t( 'Userinfo updated!' ) );
						$update = TRUE;
					}
					break;
			}
		}

		if ( $update == TRUE ) {
			$user->update();
		}
		else {
			Session::notice( 'Nothing changed.' );
		}

		Utils::redirect( URL::get( 'admin', $results ) );
	}
}

?>