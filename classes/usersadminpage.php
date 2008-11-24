<?php

class UsersAdminPage extends AdminPage
{
	/**
	 * handles AJAX from /users
	 * used to delete users and fetch new ones
	 */
	public function act_ajax_delete($handler_vars)
	{
		echo json_encode( $this->update_users( $handler_vars ) );
	}
	
	/**
	 * Handles ajax requests from the manage users page
	 */
	public function act_ajax_get()
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$this->theme->currentuser = User::identify();
		$items = $this->theme->fetch( 'users_items' );

		$output = array(
			'items' => $items,
		);
		echo json_encode($output);
	}

	public function update_users($handler_vars)
	{
		if( isset($handler_vars['delete']) ) {

			$currentuser = User::identify();

			$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
			if ( isset($handler_vars['digest']) && $handler_vars['digest'] != $wsse['digest'] ) {
				Session::error( _t('WSSE authentication failed.') );
				return Session::messages_get( true, 'array' );
			}

			foreach ( $_POST as $id => $delete ) {

				// skip POST elements which are not log ids
				if ( preg_match( '/^p\d+/', $id ) && $delete ) {
					$id = substr($id, 1);

					$ids[] = array( 'id' => $id );

				}

			}

			if ( isset( $handler_vars['checkbox_ids'] ) ) {
				foreach ( $handler_vars['checkbox_ids'] as $id => $delete ) {
					if ( $delete ) {
						$ids[] = array( 'id' => $id );
					}
				}
			}

			$count = 0;

			if( ! isset($ids) ) {
				Session::notice( _t('No users deleted.') );
				return Session::messages_get( true, 'array' );
			}

			foreach ( $ids as $id ) {
				$id = $id['id'];
				$user = User::get_by_id( $id );

				if ( $currentuser != $user ) {
					if ( $handler_vars['reassign'] != 0 ) {
						$assign = intval($handler_vars['reassign']);

						if ( $user->id == $assign ) {
							return;
						}

						$posts = Posts::get( array( 'user_id' => $user->id, 'nolimit' => 1) );

						if ( isset($posts[0]) ) {
							Posts::reassign( $assign, $posts );
						}
					}

					$user->delete();
				} else {
					$msg_status = _t('You cannot delete yourself.');
				}

				$count++;
			}

			if ( !isset($msg_status) ) {
				$msg_status = sprintf( _t('Deleted %d users.'), $count );
			}

			Session::notice( $msg_status );
		}
	}

	/**
	 * Assign values needed to display the users listing
	 *
	 */
	private function fetch_users($params = NULL)
	{
		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();

		// Get author list
		$author_list = Users::get_all();
		$authors[0] = _t('nobody');
		foreach ( $author_list as $author ) {
			$authors[ $author->id ]= $author->displayname;
		}
		$this->theme->authors = $authors;
	}

	public function act_request_get()
	{
		return $this->act_request_post();
	}

	/**
	 * Handles post requests from the Users listing (ie: creating a new user)
	 */
	public function act_request_post()
	{
		$this->fetch_users();

		$extract = $this->handler_vars->filter_keys('newuser', 'delete', 'new_pass1', 'new_pass2', 'new_email', 'new_username');
		foreach($extract as $key => $value) {
			$$key = $value;
		}

		if(isset($newuser)) {
			$action = 'newuser';
		}
		elseif(isset($delete)) {
			$action = 'delete';
		}

		$error = '';
		if ( isset( $action ) && ( 'newuser' == $action ) ) {
			if ( !isset( $new_pass1 ) || !isset( $new_pass2 ) || empty( $new_pass1 ) || empty( $new_pass2 ) ) {
				Session::error( _t( 'Password is required.' ), 'adduser' );
			}
			else if ( $new_pass1 !== $new_pass2 ) {
				Session::error( _t( 'Password mis-match.'), 'adduser' );
			}
			if ( !isset( $new_email ) || empty( $new_email ) || ( !strstr( $new_email, '@' ) ) ) {
				Session::error( _t( 'Please supply a valid email address.' ), 'adduser' );
			}
			if ( !isset( $new_username ) || empty( $new_username ) ) {
				Session::error( _t( 'Please supply a user name.' ), 'adduser' );
			}
			// safety check to make sure no such username exists
			$user = User::get_by_name( $new_username );
			if ( isset( $user->id ) ) {
				Session::error( _t( 'That username is already assigned.' ), 'adduser' );
			}
			if ( !Session::has_errors( 'adduser' ) ) {
				$user = new User( array( 'username' => $new_username, 'email' => $new_email, 'password' => Utils::crypt( $new_pass1 ) ) );
				if ( $user->insert() ) {
					Session::notice( sprintf( _t( "Added user '%s'" ), $new_username ) );
				}
				else {
					$dberror = DB::get_last_error();
					Session::error( $dberror[2], 'adduser' );
				}
			}
			else {
				$settings = array();
				if ( isset($username) ) {
					$settings['new_username'] = $new_username;
				}
				if ( isset( $new_email ) ) {
					$settings['new_email'] = $new_email;
				}
				$this->theme->assign( 'settings', $settings );
			}
		} else if ( isset( $action ) && ( 'delete' == $action ) ) {

			$this->update_users($this->handler_vars);

		}

		$this->theme->display('users');
	}
}

?>