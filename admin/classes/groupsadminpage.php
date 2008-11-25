<?php

class GroupsAdminPage extends AdminPage
{
	public function ajax_update_groups($handler_vars)
	{
		echo json_encode( $this->update_groups( $handler_vars ) );
	}

	public function ajax_groups($handler_vars)
	{
		$theme_dir = Plugins::filter( 'admin_theme_dir', Site::get_dir( 'admin_theme', TRUE ) );
		$this->theme = Themes::create( 'admin', 'RawPHPEngine', $theme_dir );

		$output= '';

		foreach(UserGroups::get_all() as $group) {
			$this->theme->group= $group;

			$group= UserGroup::get_by_id($group->id);
			$users= array();
			foreach($group->members as $id) {
				$user= User::get_by_id($id);
				$users[]= '<strong><a href="' . URL::get('admin', 'page=user&id=' . $user->id) . '">' . $user->displayname . '</a></strong>';
			}

			$this->theme->users= $users;

			$output .= $this->theme->fetch('groups_item');
		}

		echo json_encode(array(
			'items' => $output
		));
	}

	public function update_groups($handler_vars, $ajax = TRUE) {
		$wsse = Utils::WSSE( $handler_vars['nonce'], $handler_vars['timestamp'] );
		if ( (isset($handler_vars['digest']) && $handler_vars['digest'] != $wsse['digest']) || (isset($handler_vars['PasswordDigest']) && $handler_vars['PasswordDigest'] != $wsse['digest']) ) {
			Session::error( _t('WSSE authentication failed.') );
			return Session::messages_get( true, 'array' );
		}
						
		if(isset($handler_vars['PasswordDigest']) || isset($handler_vars['digest'])) {
			
			if(( isset($handler_vars['action']) && $handler_vars['action'] == 'add') || isset($handler_vars['newgroup'])) {
				if(isset($handler_vars['newgroup'])) {
					$name= $handler_vars['new_groupname'];
				}
				else {
					$name= $handler_vars['name'];
				}

				$settings= array('name' => $name);

				$this->theme->addform= $settings;

				if ( UserGroup::exists($name) ) {
					Session::notice( sprintf(_t( 'The group %s already exists'), $name ) );
					if($ajax) {
						return Session::messages_get( true, 'array' );
					}
					else {
						return;
					}
				}
				else {
					$groupdata = array(
						'name' => $name
					);
					$group = UserGroup::create($groupdata);
					Session::notice( sprintf(_t( 'Added group %s'), $name ) );
					// reload the groups
					$this->theme->groups = UserGroups::get_all();

					$this->theme->addform= array();
				}

				if($ajax) {
					return Session::messages_get( true, 'array' );
				}
				else {
					if(!$ajax) {
						Utils::redirect(URL::get('admin', 'page=groups'));
						exit;
					}
				}

			}

			if( isset($handler_vars['action']) && $handler_vars['action'] == 'delete' && $ajax = true) {



				$ids= array();

				foreach ( $_POST as $id => $delete ) {

					// skip POST elements which are not log ids
					if ( preg_match( '/^p\d+/', $id ) && $delete ) {
						$id = substr($id, 1);

						$ids[] = array( 'id' => $id );

					}

				}

				$count = 0;

				if( !isset($ids) ) {
					Session::notice( _t('No groups deleted.') );
					return Session::messages_get( true, 'array' );
				}

				foreach ( $ids as $id ) {
					$id = $id['id'];
					$group = UserGroup::get_by_id( $id );

					$group->delete();

					$count++;
				}

				if ( !isset($msg_status) ) {
					$msg_status = sprintf( _t('Deleted %d groups.'), $count );
				}

				Session::notice( $msg_status );

				return Session::messages_get( true, 'array' );
			}
		}

	}

	public function get_groups()
	{
		$this->post_groups();
	}

	public function post_groups()
	{

		// prepare the WSSE tokens
		$this->theme->wsse = Utils::WSSE();

		$this->theme->groups = UserGroups::get_all();

		$this->update_groups($this->handler_vars, false);

		$this->display( 'groups' );
	}
}

?>