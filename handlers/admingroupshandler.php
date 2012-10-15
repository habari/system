<?php
/**
 * @package Habari
 *
 */

/**
 * Habari AdminGroupsHandler Class
 * Handles group-related actions in the admin
 *
 */
class AdminGroupsHandler extends AdminHandler
{
	/**
	 * Handles GET requests for the groups page.
	 */
	public function get_groups()
	{
		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();
		$groups = UserGroups::get_all();
		$this->theme->groups = Plugins::filter('admin_groups_visible', $groups);
		$this->display( 'groups' );
	}

	/**
	 * Handles POST requests for the groups page.
	 */
	public function post_groups()
	{
		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();
		$this->theme->groups = UserGroups::get_all();
		$this->update_groups( $this->handler_vars, false );
		Utils::redirect( URL::get( 'admin', 'page=groups' ) );
	}

	/**
	 * Handles GET requests for a group's page.
	 */
	public function get_group()
	{
		$group = UserGroup::get_by_id( $this->handler_vars['id'] );
		if ( null == $group ) {
			Utils::redirect( URL::get( 'admin', 'page=groups' ) );
		}
		else {

			$tokens = ACL::all_tokens( 'id' );
			$access_names = ACL::$access_names;
			$access_names[] = 'deny';

			// attach access bitmasks to the tokens
			foreach ( $tokens as $token ) {
				$token->access = ACL::get_group_token_access( $group->id, $token->id );
			}

			// separate tokens into groups
			$grouped_tokens = array();
			foreach ( $tokens as $token ) {
				$grouped_tokens[$token->token_group][( $token->token_type ) ? 'crud' : 'bool'][] = $token;
			}

			$potentials = array();

			$users = Users::get_all();

			$users[] = User::anonymous();

			$members = $group->members;
			$jsusers = array();
			foreach ( $users as $user ) {
				$jsuser = new StdClass();
				$jsuser->id = $user->id;
				$jsuser->username = $user->username;
				$jsuser->member = in_array( $user->id, $members );

				$jsusers[$user->id] = $jsuser;
			}

			$this->theme->potentials = $potentials;
			$this->theme->users = $users;
			$this->theme->members = $members;

			$js = '$(function(){groupManage.init(' . json_encode( $jsusers ) . ');});';

			Stack::add( 'admin_header_javascript', $js, 'groupmanage', 'admin-js' );

			$this->theme->access_names = $access_names;
			$this->theme->grouped_tokens = $grouped_tokens;

			$this->theme->groups = UserGroups::get_all();
			$this->theme->group = $group;
			$this->theme->id = $group->id;

			$this->theme->wsse = Utils::WSSE();

			$this->display( 'group' );
		}

	}

	/**
	 * Handles POST requests to a group's page.
	 */
	public function post_group()
	{
		$group = UserGroup::get_by_id( $this->handler_vars['id'] );
		$tokens = ACL::all_tokens();

		if ( isset( $this->handler_vars['nonce'] ) ) {
			$wsse = Utils::WSSE( $this->handler_vars['nonce'], $this->handler_vars['timestamp'] );

			if ( isset( $this->handler_vars['digest'] ) && $this->handler_vars['digest'] != $wsse['digest'] ) {
				Session::error( _t( 'WSSE authentication failed.' ) );
			}

			if ( isset( $this->handler_vars['delete'] ) ) {
				$group->delete();
				Utils::redirect( URL::get( 'admin', 'page=groups' ) );
			}

			if ( isset( $this->handler_vars['user'] ) ) {
				$users = $this->handler_vars['user'];
				foreach ( $users as $user => $status ) {
					if ( $status == 1 ) {
						$group->add( $user );
					}
					else {
						$group->remove( $user );
					}
				}

				foreach ( $tokens as $token ) {
					$bitmask = new Bitmask( ACL::$access_names );
					if ( isset( $this->handler_vars['tokens'][$token->id]['deny'] ) ) {
						$bitmask->value = 0;
						$group->deny( $token->id );
					}
					else {
						foreach ( ACL::$access_names as $name ) {
							if ( isset( $this->handler_vars['tokens'][$token->id][$name] ) ) {
								$bitmask->$name = true;
							}
						}
						if ( isset( $this->handler_vars['tokens'][$token->id]['full'] ) ) {
							$bitmask->value = $bitmask->full;
						}
						if ( $bitmask->value != 0 ) {
							$group->grant( $token->id, $bitmask );
						}
						else {
							$group->revoke( $token->id );
						}
					}
				}
			}

		}

		Session::notice( _t( 'Updated permissions.' ), 'permissions' );

		Utils::redirect( URL::get( 'admin', 'page=group' ) . '?id=' . $group->id );

	}

	/**
	 * Handles AJAX requests to update groups.
	 */
	public function ajax_update_groups( $handler_vars )
	{
		Utils::check_request_method( array( 'POST' ) );

		echo json_encode( $this->update_groups( $handler_vars ) );
	}

	/**
	 * Handles AJAX requests from the groups page.
	 */
	public function ajax_groups( $handler_vars )
	{
		Utils::check_request_method( array( 'GET', 'HEAD' ) );

		$this->create_theme();

		$output = '';

		foreach ( UserGroups::get_all() as $group ) {
			$this->theme->group = $group;

			$group = UserGroup::get_by_id( $group->id );
			$users = array();
			foreach ( $group->members as $id ) {
				$user = $id == 0 ? User::anonymous() : User::get_by_id( $id );
				if ( $user->id == 0 ) {
					$users[] = '<strong>' . $user->displayname . '</strong>';
				}
				else {
					$users[] = '<strong><a href="' . URL::get( 'admin', 'page=user&id=' . $user->id ) . '">' . $user->displayname . '</a></strong>';
				}
			}

			$this->theme->users = $users;

			$output .= $this->theme->fetch( 'groups_item' );
		}

		$ar = new AjaxResponse();
		$ar->data = array(
			'items' => $output
		);
		$ar->out();
	}

	/**
	 * Add or delete groups.
	 */
	public function update_groups( $handler_vars, $ajax = true )
	{
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( ( isset( $handler_vars['digest'] ) && $handler_vars['digest'] != $wsse['digest'] ) || ( isset( $handler_vars['password_digest'] ) && $handler_vars['password_digest'] != $wsse['digest'] ) ) {
			Session::error( _t( 'WSSE authentication failed.' ) );
			return Session::messages_get( true, 'array' );
		}

		if ( isset( $handler_vars['password_digest'] ) || isset( $handler_vars['digest'] ) ) {

			if ( ( isset( $handler_vars['action'] ) && $handler_vars['action'] == 'add' ) || isset( $handler_vars['newgroup'] ) ) {
				if ( isset( $handler_vars['newgroup'] ) ) {
					$name = trim( $handler_vars['new_groupname'] );
				}
				else {
					$name = trim( $handler_vars['name'] );
				}

				$settings = array( 'name' => $name );

				$this->theme->addform = $settings;

				if ( UserGroup::exists( $name ) ) {
					Session::notice( _t( 'The group %s already exists', array( $name ) ) );
					if ( $ajax ) {
						return Session::messages_get( true, 'array' );
					}
					else {
						return;
					}
				}
				elseif ( empty( $name ) ) {
					Session::notice( _t( 'The group must have a name' ) );
					if ( $ajax ) {
						return Session::message_get( true, 'array' );
					}
					else {
						return;
					}
				}
				else {
					$groupdata = array(
						'name' => $name
					);
					$group = UserGroup::create( $groupdata );
					Session::notice( _t( 'Added group %s', array( $name ) ) );
					// reload the groups
					$this->theme->groups = UserGroups::get_all();

					$this->theme->addform = array();
				}

				if ( $ajax ) {
					return Session::messages_get( true, 'array' );
				}
				else {
					if ( !$ajax ) {
						Utils::redirect( URL::get( 'admin', 'page=groups' ) );
					}
				}

			}

			if ( isset( $handler_vars['action'] ) && $handler_vars['action'] == 'delete' && $ajax == true ) {

				$ids = array();

				foreach ( $_POST as $id => $delete ) {

					// skip POST elements which are not group ids
					if ( preg_match( '/^p\d+$/', $id ) && $delete ) {
						$id = (int) substr( $id, 1 );

						$ids[] = array( 'id' => $id );

					}

				}

				$count = 0;

				if ( !isset( $ids ) ) {
					Session::notice( _t( 'No groups deleted.' ) );
					return Session::messages_get( true, 'array' );
				}

				foreach ( $ids as $id ) {
					$id = $id['id'];
					$group = UserGroup::get_by_id( $id );

					$group->delete();

					$count++;
				}

				if ( !isset( $msg_status ) ) {
					$msg_status = _t( 'Deleted %d groups.', array( $count ) );
				}

				Session::notice( $msg_status );

				return Session::messages_get( true, 'array' );
			}
		}

	}

}
?>

