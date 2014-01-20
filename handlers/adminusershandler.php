<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminUsersHandler Class
 * Handles user-related actions in the admin
 *
 */
class AdminUsersHandler extends AdminHandler
{
	/**
	 * Handles GET requests of a user page.
	 */
	public function get_user()
	{
		$edit_user = User::identify();
		$permission = false;

		// Check if the user is editing their own profile
		$self = $this->handler_vars['user'] == '' || User::get_by_name($this->handler_vars['user']) == $edit_user;
		if ($self) {
			if ( $edit_user->can( 'manage_self' ) || $edit_user->can( 'manage_users' ) ) {
				$permission = true;
			}
			$who = _t( "You" );
			$possessive = _t( "Your User Information" );
		}
		else {
			if ( $edit_user->can( 'manage_users' ) ) {
				$permission = true;
			}
			$edit_user = User::get_by_name( $this->handler_vars['user'] );
			$who = $edit_user->username;
			$possessive = _t( "%s's User Information", array( $who ) );
		}

		if ( !$permission ) {
			Session::error( _t( 'Access to that page has been denied by the administrator.' ) );
			$this->get_blank();
			return;
		}

		// Get author list
		$author_list = Users::get_all();
		$authors[0] = _t( 'nobody' );
		foreach ( $author_list as $author ) {
			$authors[ $author->id ] = $author->displayname;
		}

		unset( $authors[ $edit_user->id ] ); // We can't reassign posts to ourself

		$this->theme->authors = $authors;
		$this->theme->edit_user = $edit_user;
		$this->theme->who = $who;
		$this->theme->possessive = $possessive;

		// Redirect to the users management page if we're trying to edit a non-existent user
		if ( !$edit_user ) {
			Session::error( _t( 'No such user!' ) );
			Utils::redirect( URL::get( 'admin', 'page=users' ) );
		}

		$this->theme->edit_user = $edit_user;

		$field_sections = array(
			'user_info' => $possessive,
			'change_password' => _t( 'Change Password' ),
			'regional_settings' => _t( 'Regional Settings' ),
			'dashboard' => _t( 'Dashboard' ),
		);

			$form = new FormUI( 'User Options' );

		// Create a tracker for who we are dealing with
		$form->append( 'hidden', 'edit_user', 'edit_user' );
		$form->edit_user->value = $edit_user->id;

		// Generate sections
		foreach ( $field_sections as $key => $name ) {
			$fieldset = $form->append( 'wrapper', $key, $name );
			$fieldset->class = 'container settings';
			$fieldset->append( 'static', $key, '<h2>' . htmlentities( $name, ENT_COMPAT, 'UTF-8' ) . '</h2>' );
		}

		// User Info
		$displayname = $form->user_info->append( 'text', 'displayname', 'null:null', _t( 'Display Name' ), 'optionscontrol_text' );
		$displayname->class[] = 'important item clear';
		$displayname->value = $edit_user->displayname;

		$username = $form->user_info->append( 'text', 'username', 'null:null', _t( 'User Name' ), 'optionscontrol_text' );
		$username->class[] = 'item clear';
		$username->value = $edit_user->username;
		$username->add_validator( 'validate_username', $edit_user->username );

		$email = $form->user_info->append( 'text', 'email', 'null:null', _t( 'Email' ), 'optionscontrol_text' );
		$email->class[] = 'item clear';
		$email->value = $edit_user->email;
		$email->add_validator( 'validate_email' );

		$imageurl = $form->user_info->append( 'text', 'imageurl', 'null:null', _t( 'Portrait URL' ), 'optionscontrol_text' );
		$imageurl->class[] = 'item clear';
		$imageurl->value = $edit_user->info->imageurl;

		// Change Password
		$password1 = $form->change_password->append( 'text', 'password1', 'null:null', _t( 'New Password' ), 'optionscontrol_text' );
		$password1->class[] = 'item clear';
		$password1->type = 'password';
		$password1->value = '';
		$password1->autocomplete = 'off';

		$password2 = $form->change_password->append( 'text', 'password2', 'null:null', _t( 'New Password Again' ), 'optionscontrol_text' );
		$password2->class[] = 'item clear';
		$password2->type = 'password';
		$password2->value = '';
		$password2->autocomplete = 'off';
		
		$delete = $this->handler_vars->filter_keys( 'delete' );
		// don't validate password match if action is delete
		if ( !isset( $delete['delete'] ) ) {
			$password2->add_validator( 'validate_same', $password1, _t( 'Passwords must match.' ) );
		}

		// Regional settings
		$timezones = DateTimeZone::listIdentifiers();
		$timezones = array_merge( array_combine( array_values( $timezones ), array_values( $timezones ) ) );
		$locale_tz = $form->regional_settings->append( 'select', 'locale_tz', 'null:null', _t( 'Timezone' ) );
		$locale_tz->class[] = 'item clear';
		$locale_tz->value = $edit_user->info->locale_tz;
		$locale_tz->options = $timezones;
		$locale_tz->multiple = false;
		$locale_tz->template = 'optionscontrol_select';

		$locale_date_format = $form->regional_settings->append( 'text', 'locale_date_format', 'null:null', _t( 'Date Format' ), 'optionscontrol_text' );
		$locale_date_format->class[] = 'item clear';
		$locale_date_format->value = $edit_user->info->locale_date_format;
		if ( isset( $edit_user->info->locale_date_format ) && $edit_user->info->locale_date_format != '' ) {
			$current = HabariDateTime::date_create()->get( $edit_user->info->locale_date_format );
		}
		else {
			$current = HabariDateTime::date_create()->date;
		}
		$locale_date_format->helptext = _t( 'See <a href="%s">php.net/date</a> for details. Current format: %s', array( 'http://php.net/date', $current ) );

		$locale_time_format = $form->regional_settings->append( 'text', 'locale_time_format', 'null:null', _t( 'Time Format' ), 'optionscontrol_text' );
		$locale_time_format->class[] = 'item clear';
		$locale_time_format->value = $edit_user->info->locale_time_format;
		if ( isset( $edit_user->info->locale_time_format ) && $edit_user->info->locale_time_format != '' ) {
			$current = HabariDateTime::date_create()->get( $edit_user->info->locale_time_format );
		}
		else {
			$current = HabariDateTime::date_create()->time;
		}
		$locale_time_format->helptext = _t( 'See <a href="%s">php.net/date</a> for details. Current format: %s', array( 'http://php.net/date', $current ) );


		$spam_count = $form->dashboard->append( 'checkbox', 'dashboard_hide_spam_count', 'null:null', _t( 'Hide Spam Count' ), 'optionscontrol_checkbox' );
		$spam_count->class[] = 'item clear';
		$spam_count->helptext = _t( 'Hide the number of SPAM comments on your dashboard.' );
		$spam_count->value = $edit_user->info->dashboard_hide_spam_count;

		// Groups
		if(User::identify()->can('manage_groups')) {
			$fieldset = $form->append( 'wrapper', 'groups', _t('Groups'));
			$fieldset->class = 'container settings';
			$fieldset->append( 'static', 'groups', '<h2>' . htmlentities( _t('Groups'), ENT_COMPAT, 'UTF-8' ) . '</h2>' );
			$form->groups->append( 'checkboxes', 'user_group_membership', 'null:null', _t('Groups'), Utils::array_map_field(UserGroups::get_all(), 'name', 'id') );
			$form->user_group_membership->value = $edit_user->groups;
		}

		// Controls
		$controls = $form->append( 'wrapper', 'page_controls' );
		$controls->class = 'container controls transparent';

		$submit = $controls->append( 'submit', 'apply', _t( 'Apply' ), 'optionscontrol_submit' );
		$submit->class[] = 'pct30';

		$controls->append( 'static', 'reassign', '<span class="pct35 reassigntext">' . _t( 'Reassign posts to: %s', array( Utils::html_select( 'reassign', $authors ) ) ) . '</span><span class="minor pct5 conjunction">' . _t( 'and' ) . '</span><span class="pct30"><input type="submit" name="delete" value="' . _t( 'Delete' ) . '" class="delete button"></span>' );

		$form->on_success( array( $this, 'form_user_success' ) );

		// Let plugins alter this form
		Plugins::act( 'form_user', $form, $edit_user );

		$this->theme->form = $form->get();
		$this->theme->admin_page = $self ? _t( 'My Profile') : _t( 'User' );

		$this->theme->display( 'user' );

	}

	/**
	 * Handles form submission from a user's page.
	 */
	public function form_user_success( $form )
	{
		$edit_user = User::get_by_id( $form->edit_user->value );
		$current_user = User::identify();
		$permission = false;

		// Check if the user is editing their own profile
		if ($edit_user->id == $current_user->id) {
			if ( $edit_user->can( 'manage_self' ) || $edit_user->can( 'manage_users' ) ) {
				$permission = true;
			}
		}
		else {
			if ( $current_user->can( 'manage_users' ) ) {
				$permission = true;
			}
		}

		if ( !$permission ) {
			Session::error( _t( 'Access to that page has been denied by the administrator.' ) );
			$this->get_blank();
			return;
		}

		// Let's check for deletion
		if ( Controller::get_var( 'delete' ) != null ) {
			if ( $current_user->id != $edit_user->id ) {

				// We're going to delete the user before we need it, so store the username
				$username = $edit_user->username;

				$posts = Posts::get( array( 'user_id' => $edit_user->id, 'nolimit' => true ) );

				if ( ( Controller::get_var( 'reassign' ) != null ) && ( Controller::get_var( 'reassign' ) != 0 ) && ( Controller::get_var( 'reassign' ) != $edit_user->id ) ) {
					// we're going to re-assign all of this user's posts
					$newauthor = Controller::get_var( 'reassign' );
					Posts::reassign( $newauthor, $posts );
					$edit_user->delete();
				}
				else {
					// delete user, then delete posts
					$edit_user->delete();

					// delete posts
					foreach ( $posts as $post ) {
						$post->delete();
					}
				}

				Session::notice( _t( '%s has been deleted', array( $username ) ) );

				Utils::redirect( URL::get( 'admin', array( 'page' => 'users' ) ) );
			}
			else {
				Session::notice( _t( 'You cannot delete yourself.' ) );
			}
		}

		$update = false;

		// Change username
		if ( isset( $form->username ) && $edit_user->username != $form->username->value ) {
			Session::notice( _t( '%1$s has been renamed to %2$s.', array( $edit_user->username, $form->username->value ) ) );
			$edit_user->username = $form->username->value;
			$update = true;
		}

		// Change email
		if ( isset( $form->email ) && $edit_user->email != $form->email->value ) {
			$edit_user->email = $form->email->value;
			$update = true;
		}

		// Change password
		if ( isset( $form->password1 ) && !( Utils::crypt( $form->password1->value, $edit_user->password ) ) && ( $form->password1->value != '' ) ) {
			Session::notice( _t( 'Password changed.' ) );
			$edit_user->password = Utils::crypt( $form->password1->value );
			$edit_user->update();
		}

		// Change group membership
		if(User::identify()->can('manage_groups')) {
			$allgroups = UserGroups::get_all();
			$new_groups = $form->user_group_membership->value;
			foreach($allgroups as $group) {
				if(!$edit_user->in_group($group) && in_array($group->id, $new_groups)) {
					$edit_user->add_to_group($group);
				}
				if($edit_user->in_group($group) && !in_array($group->id, $new_groups)) {
					$edit_user->remove_from_group($group);
				}
			}
		}

		// Set various info fields
		$info_fields = array( 'displayname', 'imageurl', 'locale_tz', 'locale_date_format', 'locale_time_format', 'dashboard_hide_spam_count' );

		// let plugins easily specify other user info fields to pick
		$info_fields = Plugins::filter( 'adminhandler_post_user_fields', $info_fields );

		foreach ( $info_fields as $info_field ) {
			if ( isset( $form->{$info_field} ) && ( $edit_user->info->{$info_field} != $form->{$info_field}->value ) && !empty( $form->{$info_field}->value ) ) {
				$edit_user->info->{$info_field} = $form->$info_field->value;
				$update = true;
			}
			else if ( isset( $edit_user->info->{$info_field} ) && empty( $form->{$info_field}->value ) ) {
				unset( $edit_user->info->{$info_field} );
				$update = true;
			}
		}

		// Let plugins tell us to update
		$update = Plugins::filter( 'form_user_update', $update, $form, $edit_user );
		$form->save();

		if ( $update ) {
			$edit_user->update();
			Session::notice( _t( 'User updated.' ) );
		}

		Utils::redirect( URL::get( 'admin', array( 'page' => 'user', 'user' => $edit_user->username ) ) );
	}

	/**
	 * Handles POST requests from the user profile page.
	 */
	public function post_user()
	{
		$this->get_user();
	}

	/**
	 * Update an array of POSTed users.
	 */
	public function update_users( $handler_vars )
	{
		if ( isset( $handler_vars['delete'] ) ) {

			$currentuser = User::identify();

			$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
			if ( isset( $handler_vars['digest'] ) && $handler_vars['digest'] != $wsse['digest'] ) {
				Session::error( _t( 'WSSE authentication failed.' ) );
				return Session::messages_get( true, 'array' );
			}

			foreach ( $_POST as $id => $delete ) {

				// skip POST elements which are not user ids
				if ( preg_match( '/^p\d+$/', $id ) && $delete ) {
					$id = (int) substr( $id, 1 );

					$ids[] = array( 'id' => $id );

				}

			}

			if ( isset( $handler_vars['checkbox_ids'] ) ) {
				$checkbox_ids = $handler_vars['checkbox_ids'];
				foreach ( $checkbox_ids as $id => $delete ) {
					if ( $delete ) {
						$ids[] = array( 'id' => $id );
					}
				}
			}

			$count = 0;

			if ( ! isset( $ids ) ) {
				Session::notice( _t( 'No users deleted.' ) );
				return Session::messages_get( true, 'array' );
			}

			foreach ( $ids as $id ) {
				$id = $id['id'];
				$user = User::get_by_id( $id );

				if ( $currentuser != $user ) {
					$assign = intval( $handler_vars['reassign'] );

					if ( $user->id == $assign ) {
						return;
					}

					$posts = Posts::get( array( 'user_id' => $user->id, 'nolimit' => 1) );

					$user->delete();

					if ( isset( $posts[0] ) ) {
						if ( 0 == $assign ) {
							foreach ( $posts as $post ) {
								$post->delete();
							}
						}
						else {
							Posts::reassign( $assign, $posts );
						}
					}
				}
				else {
					$msg_status = _t( 'You cannot delete yourself.' );
				}

				$count++;
			}

			if ( !isset( $msg_status ) ) {
				$msg_status = _t( 'Deleted %d users.', array( $count ) );
			}

			Session::notice( $msg_status );
		}
	}

	/**
	 * Assign values needed to display the users listing
	 *
	 */
	private function fetch_users( $params = null )
	{
		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();

		// Get author list
		$author_list = Users::get_all();
		$authors[0] = _t( 'nobody' );
		foreach ( $author_list as $author ) {
			$authors[ $author->id ] = $author->displayname;
		}
		$this->theme->authors = $authors;
	}

	/**
	 * Handles GET requests of the users page.
	 */
	public function get_users()
	{
		$this->fetch_users();

		$this->theme->display( 'users' );
	}

	/**
	 * Handles POST requests from the Users listing (ie: creating a new user)
	 */
	public function post_users()
	{
		$wsse = Utils::WSSE( $this->handler_vars['nonce'], $this->handler_vars['timestamp'] );
		if ( $this->handler_vars['password_digest'] != $wsse['digest'] ) {
			Session::error( _t( 'WSSE authentication failed.' ) );
			return Session::messages_get( true, 'array' );
		}

		$this->fetch_users();

		$extract = $this->handler_vars->filter_keys( 'newuser', 'delete', 'new_pass1', 'new_pass2', 'new_email', 'new_username' );
		foreach ( $extract as $key => $value ) {
			$$key = $value;
		}

		if ( isset( $newuser ) ) {
			$action = 'newuser';
		}
		elseif ( isset( $delete ) ) {
			$action = 'delete';
		}

		$error = '';
		if ( isset( $action ) && ( 'newuser' == $action ) ) {
			if ( !isset( $new_pass1 ) || !isset( $new_pass2 ) || empty( $new_pass1 ) || empty( $new_pass2 ) ) {
				Session::error( _t( 'Password is required.' ), 'adduser' );
			}
			else if ( $new_pass1 !== $new_pass2 ) {
				Session::error( _t( 'Password mis-match.' ), 'adduser' );
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
					Session::notice( _t( "Added user '%s'", array( $new_username ) ) );
				}
				else {
					$dberror = DB::get_last_error();
					Session::error( $dberror[2], 'adduser' );
				}
			}
			else {
				$settings = array();
				if ( isset( $new_username ) ) {
					$settings['new_username'] = $new_username;
				}
				if ( isset( $new_email ) ) {
					$settings['new_email'] = $new_email;
				}
				$this->theme->assign( 'settings', $settings );
			}
		}
		else if ( isset( $action ) && ( 'delete' == $action ) ) {

			$this->update_users( $this->handler_vars );

		}

		$this->theme->display( 'users' );
	}

}
?>
