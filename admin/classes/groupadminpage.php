<?php

class GroupAdminPage extends AdminPage
{
	public function get_group()
	{
		$this->post_group();
	}

	public function post_group()
	{

		$group= UserGroup::get_by_id($this->handler_vars['id']);

		if(isset($this->handler_vars['nonce'])) {
			$wsse = Utils::WSSE( $this->handler_vars['nonce'], $this->handler_vars['timestamp'] );

			if ( isset($this->handler_vars['digest']) && $this->handler_vars['digest'] != $wsse['digest'] ) {
				Session::error( _t('WSSE authentication failed.') );
			}


			if(isset($this->handler_vars['delete'])) {
				$group->delete();
				Utils::redirect(URL::get('admin', 'page=groups'));
				exit;
			}

			if(isset($this->handler_vars['user'])) {
				foreach($this->handler_vars['user'] as $user => $status) {
					if($status == 1) {
						$group->add($user);
					}
					else {
						$group->remove($user);
					}
				}

				Utils::redirect(URL::get('admin', 'page=group&id=' . $group->id));
			}

		}

		$group= UserGroup::get_by_id($this->handler_vars['id']);



		$potentials= array();
		$users= Users::get_all();
		$members= $group->members;
		foreach($users as $user) {			
			if(in_array($user->id, $members)) {
				$user->membership= TRUE;
			}
			else {
				$potentials[$user->id]= $user->displayname;
				$user->membership= FALSE;
			}

		}
		$this->theme->potentials= $potentials;
		$this->theme->users = $users;
		$this->theme->members = $members;

		$permissions= ACL::all_permissions();

		foreach($permissions as $permission) {
			if($level= ACL::group_can($group->id, $permission->id)) {
				$permission->access= $level;
			}
		}

		$this->theme->permissions= $permissions;

		$this->theme->groups= UserGroups::get_all();
		$this->theme->group= $group;
		$this->theme->id= $group->id;

		$this->theme->wsse = Utils::WSSE();

		$this->display('group');
	}
}

?>