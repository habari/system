<?php
/**
 * @package Habari
 *
 */

namespace Habari;

/**
 * Habari AdminUsersHandler Class
 * Handles user-related actions in the admin
 *
 */
class AdminUsersHandler extends AdminHandler
{
	public function __construct()
	{
		FormUI::register('add_user', function(FormUI $form, $name){
			$form->set_settings(array('use_session_errors' => true));
			$form->append(
				FormControlText::create('username')
					->add_validator('validate_username')
					->add_validator('validate_required')
					->label(_t('Username'))->add_class('incontent')->set_template('control.label.outsideleft')
			);
			$form->append(
				FormControlText::create('email')
					->add_validator('validate_email')
					->add_validator('validate_required')
					->label(_t('E-Mail'))->add_class('incontent')->set_template('control.label.outsideleft')
			);
			$password = FormControlPassword::create('password')
				->add_validator('validate_required');
			$form->append(
				$password->label(_t('Password'))->add_class('incontent')->set_template('control.label.outsideleft')
			);
			$form->append(
				FormControlPassword::create('password_again')
					->add_validator('validate_same', $password)
					->label(_t('Password Again'))->add_class('incontent')->set_template('control.label.outsideleft')
			);
			$form->append(FormControlSubmit::create('newuser')->set_caption('Add User'));
			$form->on_success(array($this, 'do_add_user'));
		});
		parent::__construct();
	}

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
		$form->append( FormControlHidden::create('edit_user')->set_value($edit_user->id) );

		// Generate sections
		foreach ( $field_sections as $key => $name ) {
			$fieldset = $form->append( 'wrapper', $key, $name );
			$fieldset->add_class('container settings');
			$fieldset->append( FormControlStatic::create($key)->set_static('<h2>' . htmlentities( $name, ENT_COMPAT, 'UTF-8' ) . '</h2>') );
		}

		// User Info
		$displayname = FormControlText::create('displayname')->add_class('important item clear')->set_value($edit_user->displayname);
		$form->user_info->append( FormControlLabel::wrap(_t( 'Display Name' ), $displayname));

		$username = FormControlText::create('username')->add_class('item clear')->add_validator('validate_username', $edit_user->username)->set_value($edit_user->username);
		$form->user_info->append( FormControlLabel::wrap(_t( 'User Name' ), $username) );

		$email = FormControlText::create('email')->add_class('item clear')->add_validator('validate_email')->set_value($edit_user->email);
		$form->user_info->append(FormControlLabel::wrap(_t('Email'), $email));

		$imageurl = FormControlText::create('imageurl')->add_class('item clear')->set_value($edit_user->info->imageurl);
		$form->user_info->append( FormControlLabel::wrap(_t( 'Portrait URL' ), $imageurl) );

		// Change Password
		$password1 = FormControlPassword::create('password1', null, array('autocomplete'=>'off'))->add_class('item clear')->set_value('');
		$form->change_password->append( FormControlLabel::wrap(_t( 'New Password' ), $password1) );

		$password2 = FormControlPassword::create('password2', null, array('autocomplete'=>'off'))->add_class('item clear')->set_value('');
		$form->change_password->append( FormControlLabel::wrap(_t( 'New Password Again' ), $password2) );

		$delete = $this->handler_vars->filter_keys( 'delete' );
		// don't validate password match if action is delete
		if ( !isset( $delete['delete'] ) ) {
			$password2->add_validator( 'validate_same', $password1, _t( 'Passwords must match.' ) );
		}

		// Regional settings
		$timezones = \DateTimeZone::listIdentifiers();
		$timezones = array_merge( array_combine( array_values( $timezones ), array_values( $timezones ) ) );
		$locale_tz = FormControlSelect::create('locale_tz', null, array('multiple'=>false))->add_class('item clear')->set_options($timezones)->set_value($edit_user->info->locale_tz);
		$form->regional_settings->append( FormControlLabel::wrap(_t( 'Timezone' ), $locale_tz) );

		$locale_date_format = FormControlText::create('locale_date_format')->add_class('item clear')->set_value($edit_user->info->locale_date_format);
		$form->regional_settings->append( FormControlLabel::wrap(_t( 'Date Format' ), $locale_date_format ));
		if ( isset( $edit_user->info->locale_date_format ) && $edit_user->info->locale_date_format != '' ) {
			$current = DateTime::create()->get( $edit_user->info->locale_date_format );
		}
		else {
			$current = DateTime::create()->date;
		}
		$locale_date_format->set_helptext(_t( 'See <a href="%s">php.net/date</a> for details. Current format: %s', array( 'http://php.net/date', $current )));

		$locale_time_format = FormControlText::create('locale_time_format')->add_class('item clear')->set_value($edit_user->info->locale_time_format);
		$form->regional_settings->append( FormControlLabel::wrap(_t( 'Time Format' ), $locale_time_format) );
		if ( isset( $edit_user->info->locale_time_format ) && $edit_user->info->locale_time_format != '' ) {
			$current = DateTime::create()->get( $edit_user->info->locale_time_format );
		}
		else {
			$current = DateTime::create()->time;
		}
		$locale_time_format->set_helptext(_t( 'See <a href="%s">php.net/date</a> for details. Current format: %s', array( 'http://php.net/date', $current ) ));


		$spam_count = FormControlCheckbox::create('dashboard_hide_spam_count')->add_class('item clear')
			->set_helptext(_t( 'Hide the number of SPAM comments on your dashboard.' ))->set_value($edit_user->info->dashboard_hide_spam_count);
		$form->dashboard->append( FormControlLabel::wrap(_t( 'Hide Spam Count' ), $spam_count ));

		// Groups
		if(User::identify()->can('manage_groups')) {
			$fieldset = $form->append( FormControlWrapper::create('groups'));
			$fieldset->add_class('container settings');
			$fieldset->append( FormControlStatic::create('groups_title')->set_static('<h2>' . htmlentities( _t('Groups'), ENT_COMPAT, 'UTF-8' ) . '</h2>' ));
			$fieldset->append( FormControlCheckboxes::create('user_group_membership')->set_options(Utils::array_map_field(UserGroups::get_all(), 'name', 'id'))->set_value($edit_user->groups) );
		}

		// Controls
		$controls = $form->append( FormControlWrapper::create('page_controls')->add_class('container controls transparent') );

		$submit = $controls->append( FormControlSubmit::create('apply')->set_caption(_t( 'Apply' ))->add_class('pct30') );

		$controls->append( 'static', 'reassign', '<span class="pct35 reassigntext">' . _t( 'Reassign posts to: %s', array( Utils::html_select( 'reassign', $authors ) ) ) . '</span>
		<span class="minor pct5 conjunction">' . _t( 'and' ) . '</span>
		<span class="pct30"><input type="submit" name="delete" value="' . _t( 'Delete' ) . '" class="delete button"></span>' );

		$reassign = FormControlSelect::create('reassign')->set_options($authors);
		$reassign_label = FormControlLabel::wrap(_t('Reassign posts to:') , $reassign)->set_properties(array('wrap'=> '<span class="pct35 reassigntext">%s</span>'));
		$controls->append($reassign_label);
		$controls->append(FormControlStatic::create('conjunction')->set_static(_t('and'))->set_properties(array('wrap' => '<span class="minor pct5 conjunction">%s</span>')));
		$controls->append(FormControlSubmit::create('delete')->set_caption(_t('Delete'))->set_properties(array('wrap' => '<span class="pct30">%s</span>'))->add_class('delete button'));

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
			if ( isset( $form->{$info_field} ) && ( $edit_user->info->{$info_field} != $form->{$info_field}->value ) ) {
				$edit_user->info->{$info_field} = $form->$info_field->value;
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

		$this->theme->add_user_form = FormUI::build('add_user', 'add_user')->get();
		$this->theme->currentuser = User::identify();

		$this->theme->display( 'users' );
	}

	/**
	 * Handles POST requests from the Users listing (ie: creating a new user)
	 */
	public function post_users()
	{
		$this->get_users();

		FormUI::build('add_user', 'add_user')->get();

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

		if ( isset( $delete ) ) {
			$action = 'delete';
		}

		if ( isset( $action ) && ( 'delete' == $action ) ) {

			$this->update_users( $this->handler_vars );

		}

		Utils::redirect(URL::get('admin', array('page' => 'users')));
	}


	/**
	 * Success method for the add_user form
	 * @param FormUI $form The add_user form
	 */
	public function do_add_user(FormUI $form) {

		$user = new User( array( 'username' => $form->username->value, 'email' => $form->email->value, 'password' => Utils::crypt( $form->password->value ) ) );
		if ( $user->insert() ) {
			Session::notice( _t( "Added user '%s'", array( $form->username->value ) ) );
			$form->clear();
		}
		else {
			$dberror = DB::get_last_error();
			Session::error( $dberror[2], 'adduser' );
		}
	}

}
?>
