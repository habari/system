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
		$self = $this;
		FormUI::register( 'add_user', function( FormUI $form, $name ) use( $self ) {
			$form->set_settings( array( 'use_session_errors' => true ) );
			$form->append(
				FormControlText::create( 'username' )
					->set_properties( array( 'class' => 'columns three', 'placeholder' => _t( 'Username' ) ) )
					->add_validator( 'validate_username' )
					->add_validator( 'validate_required' )
					//->label(_t('Username'))->add_class('incontent')->set_template('control.label.outsideleft')
			);
			$form->append(
				FormControlText::create( 'email' )
					->set_properties( array( 'class' => 'columns four', 'placeholder' => _t( 'E-Mail' ) ) )
					->add_validator( 'validate_email' )
					->add_validator( 'validate_required' )
					//->label(_t('E-Mail'))->add_class('incontent')->set_template('control.label.outsideleft')
			);
			$password = FormControlPassword::create( 'password' )
				->set_properties( array( 'class' => 'columns three', 'placeholder' => _t( 'Password' ) ) )
				->add_validator( 'validate_required' );
			$form->append(
				//$password->label(_t('Password'))->add_class('incontent')->set_template('control.label.outsideleft')
				$password
			);
			$form->append(
				FormControlPassword::create( 'password_again' )
					->set_properties( array( 'class' => 'columns three', 'placeholder' => _t( 'Password Again' ) ) )
					->add_validator( 'validate_same', $password )
					//->label(_t('Password Again'))->add_class('incontent')->set_template('control.label.outsideleft')
			);
			$form->append( FormControlSubmit::create( 'newuser' )->set_caption( 'Add User' ) );
			$form->add_validator( array( $self, 'validate_add_user' ) );
			$form->on_success( array( $self, 'do_add_user' ) );
		});

		FormUI::register('delete_users', function(FormUI $form, $name) use ($self) {
			$form->set_settings(array('use_session_errors' => true));
			$form->append(
				FormControlAggregate::create('deletion_queue')
					->set_selector('.select_user')
					->label('Select All')
			);
			$author_list = Users::get_all();
			$authors[0] = _t( 'nobody' );
			foreach ( $author_list as $author ) {
				$authors[ $author->id ] = $author->displayname;
			}
			$form->append(
				FormControlSelect::create('reassign')
					->set_options($authors)
			);
			$form->append(
				FormControlSubmit::create('delete_selected')
					->set_caption( _t('Delete Selected') )
			);
			$form->add_validator(array($self, 'validate_delete_users'));
			$form->on_success(array($self, 'do_delete_users'));
		});

		FormUI::register('edit_user', function(FormUI $form, $name, $form_type, $data) use($self) {
			$form->set_settings(array('use_session_errors' => true));

			$edit_user = $data['edit_user'];
			$field_sections = array(
				'user_info' => _t('User Information'),
				'change_password' => _t( 'Change Password' ),
				'regional_settings' => _t( 'Regional Settings' ),
				'dashboard' => _t( 'Dashboard' ),
			);

			// Create a tracker for who we are dealing with
			$form->append( FormControlData::create('edit_user')->set_value($edit_user->id) );

			// Generate sections
			foreach ( $field_sections as $key => $name ) {
				$fieldset = $form->append( 'wrapper', $key, $name );
				$fieldset->add_class('container main settings');
				$fieldset->append( FormControlStatic::create($key)->set_static('<h2 class="lead">' . htmlentities( $name, ENT_COMPAT, 'UTF-8' ) . '</h2>') );
			}

			// User Info
			$displayname = FormControlText::create('displayname')->set_value($edit_user->displayname);
			$form->user_info->append( FormControlLabel::wrap(_t( 'Display Name' ), $displayname));

			$username = FormControlText::create('username')->add_validator('validate_username', $edit_user->username)->set_value($edit_user->username);
			$form->user_info->append( FormControlLabel::wrap(_t( 'User Name' ), $username) );

			$email = FormControlText::create('email')->add_validator('validate_email')->set_value($edit_user->email);
			$form->user_info->append(FormControlLabel::wrap(_t('Email'), $email));

			$imageurl = FormControlText::create('imageurl')->set_value($edit_user->info->imageurl);
			$form->user_info->append( FormControlLabel::wrap(_t( 'Portrait URL' ), $imageurl) );

			// Change Password
			$password1 = FormControlPassword::create('password1', null, array('autocomplete'=>'off'))->set_value('');
			$form->change_password->append( FormControlLabel::wrap(_t( 'New Password' ), $password1) );

			$password2 = FormControlPassword::create('password2', null, array('autocomplete'=>'off'))->set_value('');
			$form->change_password->append( FormControlLabel::wrap(_t( 'New Password Again' ), $password2) );

			$delete = $self->handler_vars->filter_keys( 'delete' );
			// don't validate password match if action is delete
			if ( !isset( $delete['delete'] ) ) {
				$password2->add_validator( 'validate_same', $password1, _t( 'Passwords must match.' ) );
			}

			// Regional settings
			$timezones = \DateTimeZone::listIdentifiers();
			$timezones = array_merge( array_combine( array_values( $timezones ), array_values( $timezones ) ) );
			$locale_tz = FormControlSelect::create('locale_tz', null, array('multiple'=>false))->set_options($timezones)->set_value($edit_user->info->locale_tz);
			$form->regional_settings->append( FormControlLabel::wrap(_t( 'Timezone' ), $locale_tz) );

			$locale_date_format = FormControlText::create('locale_date_format')->set_value($edit_user->info->locale_date_format);
			$form->regional_settings->append( FormControlLabel::wrap(_t( 'Date Format' ), $locale_date_format ));
			if ( isset( $edit_user->info->locale_date_format ) && $edit_user->info->locale_date_format != '' ) {
				$current = DateTime::create()->get( $edit_user->info->locale_date_format );
			}
			else {
				$current = DateTime::create()->date;
			}
			$locale_date_format->set_helptext(_t( 'See <a href="%s">php.net/date</a> for details. Current format: %s', array( 'http://php.net/date', $current )));

			$locale_time_format = FormControlText::create('locale_time_format')->set_value($edit_user->info->locale_time_format);
			$form->regional_settings->append( FormControlLabel::wrap(_t( 'Time Format' ), $locale_time_format) );
			if ( isset( $edit_user->info->locale_time_format ) && $edit_user->info->locale_time_format != '' ) {
				$current = DateTime::create()->get( $edit_user->info->locale_time_format );
			}
			else {
				$current = DateTime::create()->time;
			}
			$locale_time_format->set_helptext(_t( 'See <a href="%s">php.net/date</a> for details. Current format: %s', array( 'http://php.net/date', $current ) ));

			$locales = array_merge( array( '' => _t( 'System default' ) . ' (' . Options::get( 'locale', 'en-us' ) . ')' ), array_combine( Locale::list_all(), Locale::list_all() ) );
			$locale_lang = FormcontrolSelect::create( 'locale_lang', null, array( 'multiple' => false ) )->set_options( $locales )->set_value( $edit_user->info->locale_lang );
			$form->regional_settings->append( FormControlLabel::wrap( _t(' Language' ), $locale_lang ) );

			$spam_count = FormControlCheckbox::create('dashboard_hide_spam_count')
				->set_helptext(_t( 'Hide the number of SPAM comments on your dashboard.' ))->set_value($edit_user->info->dashboard_hide_spam_count);
			$form->dashboard->append( FormControlLabel::wrap(_t( 'Hide Spam Count' ), $spam_count ));

			// Groups
			if(User::identify()->can('manage_groups')) {
				$fieldset = $form->append( FormControlWrapper::create('groups'));
				$fieldset->add_class('container main settings');
				$fieldset->append( FormControlStatic::create('groups_title')->set_static('<h2 class="lead">' . htmlentities( _t('Groups'), ENT_COMPAT, 'UTF-8' ) . '</h2>' ));
				$fieldset->append( FormControlCheckboxes::create('user_group_membership')->set_options(Utils::array_map_field(UserGroups::get_all(), 'name', 'id'))->set_value($edit_user->groups) );
			}

			// Controls
			$controls = $form->append( FormControlWrapper::create('page_controls')->add_class('container controls transparent') );

			$apply = $controls->append( FormControlSubmit::create('apply')->set_caption(_t( 'Apply' )) );

			// Get author list
			$author_list = Users::get_all();
			$authors[0] = _t( 'nobody' );
			foreach ( $author_list as $author ) {
				$authors[ $author->id ] = $author->displayname;
			}

			unset( $authors[ $edit_user->id ] ); // We can't reassign this user's posts to themselves if we're deleting them

			$reassign = FormControlSelect::create('reassign')->set_options($authors);
			$reassign_label = FormControlLabel::wrap(_t('Reassign posts to:') , $reassign)->set_settings(array('wrap'=> '<span class="reassigntext">%s</span>'));
			$controls->append($reassign_label);
			$controls->append(FormControlStatic::create('conjunction')->set_static(_t('and'))->set_settings(array('wrap' => '<span class="conjunction">%s</span>')));
			$delete = $controls->append(FormControlSubmit::create('delete')->set_caption(_t('Delete'))->set_settings(array('wrap' => '<span>%s</span>'))->add_class('button'));

			$delete->on_success(array($self, 'edit_user_delete'));
			$delete->add_validator(array($self, 'validate_delete_user'));

			$apply->on_success( array( $self, 'edit_user_apply' ) );
			$apply->add_validator(array($self, 'validate_edit_user'));
		});
		parent::__construct();
	}

	/**
	 * Handles GET requests of a user page.
	 */
	public function get_user()
	{
		$permission = false;

		// Check if the user is editing their own profile
		if($this->handler_vars['user'] == '') {
			$edit_user = User::identify();
			$self = true;
		}
		else {
			$edit_user = User::get_by_name( $this->handler_vars['user'] );
			$self = $edit_user->id == User::identify()->id;
		}

		// Redirect to the users management page if we're trying to edit a non-existent user
		if ( !$edit_user ) {
			Session::error( _t( 'No such user!' ) );
			Utils::redirect( URL::get( 'display_users' ) );
		}

		// Check permissions to see this page
		if ($self && ( User::identify()->can( 'manage_self' ) || User::identify()->can( 'manage_users' ) ) ) {
			$permission = true;
		}
		elseif ( User::identify()->can( 'manage_users' ) ) {
			$permission = true;
		}

		// No permission? Show blank page.
		if ( !$permission ) {
			Session::error( _t( 'Access to that page has been denied by the administrator.' ) );
			$this->get_blank();
			return;
		}

		$this->theme->edit_user = $edit_user;

		$form = FormUI::build( 'edit_user', 'edit_user', array('edit_user' => $edit_user) );

		$this->theme->form = $form->get();
		$this->theme->admin_page = $self ? _t( 'My Profile') : _t( 'User Profile for %s', array(Utils::htmlspecialchars($edit_user->username)) );

		$this->theme->display( 'user' );
	}

	/**
	 * Validation for when a user is edited
	 * @param $unused
	 * @param FormControlSubmit $control The Apply button
	 * @param FormUI $form The editing form
	 * @return array An empty array if there are no errors, or strings describing the error.
	 */
	public function validate_edit_user($unused, $control, $form)
	{
		$errors = array();

		$self = $form->edit_user->value == User::identify()->id;
		if($self) {
			if(!User::identify()->can('manage_users') && !User::identify()->can('manage_self')) {
				$errors[] = _t( 'You have insufficient permissions to manage your own user account.' );
			}
		}
		else {
			if(!User::identify()->can('manage_users')) {
				$errors[] = _t( 'You have insufficient permissions to manage users.' );
			}
		}

		return $errors;
	}

	/**
	 * Validation for when a user is deleted
	 * @param $unused
	 * @param FormControlSubmit $control The Delete button
	 * @param FormUI $form The editing form
	 * @return array An empty array if there are no errors, or strings describing the error.
	 */
	public function validate_delete_user($unused, $control, $form)
	{
		$errors = array();

		if(!User::identify()->can('manage_users')) {
			$errors[] = _t( 'You have insufficient permissions to delete users.' );
		}

		if(intval( $form->reassign->value ) == intval($form->edit_user->value)) {
			$errors[] = _t( 'You may not assign posts from deleted users to a user that is being deleted' );
		}

		return $errors;
	}

	/**
	 * The on_success handler of the Delete button on the user profile editing page
	 * @param FormUI $form
	 */
	public function edit_user_delete(FormUI $form)
	{
		$edit_user = User::get_by_id( $form->edit_user->value );

		// We're going to delete the user before we need it, so store the username
		$username = $edit_user->username;

		$posts = Posts::get( array( 'user_id' => $edit_user->id, 'nolimit' => true ) );

		if ( $form->reassign->value != 0 ) {
			// we're going to re-assign all of this user's posts
			$newauthor = $form->reassign->value;
			Posts::reassign( $newauthor, $posts );
			$success = $edit_user->delete();
		}
		else {
			// delete user, then delete posts
			$success = $edit_user->delete();

			// delete posts
			if($success) {
				/** @var Post $post */
				foreach ( $posts as $post ) {
					$post->delete();
				}
			}
		}

		if($success) {
			Session::notice( _t( '%s has been deleted', array( $username ) ) );
		}
		else {
			Session::error( _t( 'There was a problem deleting %s', array( $username ) ) );
		}

		Utils::redirect( URL::get( 'admin', array( 'page' => 'users' ) ) );

	}

	/**
	 * The on_success handler of the Apply button on the user profile editing page
	 * @param FormUI $form
	 */
	public function edit_user_apply( FormUI $form )
	{
		$edit_user = User::get_by_id( $form->edit_user->value );

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
		$info_fields = array( 'displayname', 'imageurl', 'locale_tz', 'locale_lang', 'locale_date_format', 'locale_time_format', 'dashboard_hide_spam_count' );

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
	 * Handles GET requests of the users page.
	 */
	public function get_users()
	{
		// Get author list
		$author_list = Users::get_all();
		$authors[0] = _t( 'nobody' );
		foreach ( $author_list as $author ) {
			$authors[ $author->id ] = $author->displayname;
		}
		$this->theme->authors = $authors;
		$this->theme->currentuser = User::identify();

		$this->theme->add_user_form = FormUI::build('add_user', 'add_user')->get();
		$this->theme->delete_users_form = FormUI::build('delete_users', 'delete_users')->get();

		$this->theme->display( 'users' );
	}

	/**
	 * Handles POST requests from the Users listing (ie: creating a new user, deleting from the user list)
	 */
	public function post_users()
	{
		// Process the forms on this page, if they were submitted.
		$redirect_to = URL::get('admin', array('page' => 'users'));
		FormUI::build('add_user', 'add_user')->post_redirect($redirect_to);
		FormUI::build('delete_users', 'delete_users')->post_redirect($redirect_to);
	}

	/**
	 * Validation for the add_user form
	 * @param mixed $unused This is technically the value of the form itself, which is unknown
	 * @param FormUI $form The add_user form
	 * @return array An array of errors, or an empty array if no errors
	 */
	public function validate_add_user($unused, $form) {
		$errors = array();

		if(!User::identify()->can('manage_users')) {
			$errors[] = _t( 'You have insufficient permissions to add users.' );
		}

		return $errors;
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

	/**
	 * Validation for the delete_users form
	 * @param mixed $unused This is technically the value of the form itself, which is unknown
	 * @param FormUI $form The delete_users form
	 * @return array An array of errors, or an empty array if no errors
	 */
	public function validate_delete_users($unused, $form) {
		$errors = array();

		if(!User::identify()->can('manage_users')) {
			$errors[] = _t( 'You have insufficient permissions to delete users.' );
		}

		$assign = intval( $form->reassign->value );
		if(in_array($assign, $form->deletion_queue->value)) {
			$errors[] = _t( 'You may not assign posts from deleted users to a user that is being deleted' );
		}

		if ( count($form->deletion_queue->value) == 0 ) {
			$errors[] = _t( 'No users selected to delete!' );
		}

		return $errors;
	}

	/**
	 * Success method for the delete_users form
	 * @param FormUI $form The delete_users form
	 */
	public function do_delete_users( FormUI $form )
	{
		$success = true;

		// Get the user to assign deleted users' posts to
		$assign = intval( $form->reassign->value );

		if(in_array($assign, $form->deletion_queue->value)) {
			Session::error( _t( 'You may not assign posts from deleted users to a user that is being deleted' ) );
			return false;
		}

		$count = 0;

		if ( count($form->deletion_queue->value) == 0 ) {
			Session::notice( _t( 'No users deleted.' ) );
			return false;
		}

		foreach ( $form->deletion_queue->value as $id ) {
			$user = User::get_by_id( $id );

			$posts = Posts::get( array( 'user_id' => $user->id, 'nolimit' => 1) );

			$one_success = $user->delete();

			if ( $one_success && count($posts) ) {
				if ( 0 == $assign ) {
					/** @var Post $post */
					foreach ( $posts as $post ) {
						$post->delete();
					}
				}
				else {
					Posts::reassign( $assign, $posts );
				}
			}

			$success = $success && $one_success;
			$count++;
		}

		if ( $success ) {
			$msg_status = sprintf(_n( 'Deleted one user.', 'Deleted %s users.', $count ), $count);
			Session::notice( $msg_status );
			return true;
		}
		else {
			$msg_status = _t( 'There was a problem deleting users.' );
			Session::error( $msg_status );
			return false;
		}
	}

}
?>
