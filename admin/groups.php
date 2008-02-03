<?php include('header.php');?>
<div class="container">
<hr>
<?php if(Session::has_messages()) {Session::messages_out();} ?>
<?php
$currentuser = User::identify();
?>
<h3>Group Management</h3>
<div class="column span-8">
<p>Groups</p>
<form method="post" action="">
<input type="textarea" size="20" name="add_group" />
<input type="submit" value="Add">
</form>
<ul>
<?php
foreach ( $groups as $group ) {
	echo '<li>';
	echo '<form method="post" action=""><input type="hidden" name="group" value="' . $group->name . '"><input type="submit" name="delete_group" value="Delete"> ';
	echo '<input type="submit" name="edit_group" value="Edit"> ';
	echo $group->name . '</form>';
	echo '</li>';
}
?>
</ul>
</div>
<div class="column span-8">
<p>Members</p>
<?php
if ( isset( $group_edit ) ) {
	if ( isset( $users) && ( ! empty( $users ) ) ) {
		echo '<p>Editing members of ' . $group_edit->name . '</p>';
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="group" value="' . $group_edit->name . '">';
		$user_data= array();
		foreach ( $users as $user ) {
			$user_data[$user->id]= $user->username;
		}
		echo Utils::html_select( 'add_user', $user_data );
		echo '<input type="submit" value="Add"></form>';
	}
	if ( ! empty($members) ) {
		$users= Users::get( array( 'where' => 'id in (' . implode(',', $members) . ')' ) );
		echo '<ul>';
		foreach ( $users as $member ) {
			echo '<li><form method="post" action=""><input type="hidden" name="remove_user" value="' . $member->id . '">';
			echo '<input type="hidden" name="group" value="' . $group_edit->name . '"><input type="submit" value="Remove"> ' . $member->username . '</form></li>';
		}
		echo '</ul>';
	} else {
		echo '<p>No members.</p>';
	}
}
?>
</div>
<div class="column span-8 last">
<p>Permissions</p>
<?php
if ( isset( $group_edit ) ) {
	if ( isset( $permissions) && ( ! empty( $permissions ) ) ) {
		echo '<p>Editing Permissions of ' . $group_edit->name . '</p>';
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="group" value="' . $group_edit->name . '">';
		$permission_data= array();
		foreach ( $permissions as $permission ) {
			$permission_data[$permission->id]= $permission->name;
		}
		echo Utils::html_select( 'grant_permission', $permission_data );
		echo '<input type="submit" value="Grant"></form>';
		echo Utils::html_select( 'deny_permission', $permission_data );
		echo '<input type="submit" value="Deny"></form>';
	}
	if ( ! empty($permissions_set) ) {
		echo '<ul>';
		foreach ( $permissions_set as $permission_set ) {
			echo '<li><form method="post" action=""><input type="hidden" name="permission_set" value="' . $permission_set->id . '">';
			echo '<input type="hidden" name="group" value="' . $group_edit->name . '">';
			echo '<input type="submit" value="Revoke"> ' . $permission->name . '</form></li>';
		}
		echo '</ul>';
	} else {
		echo '<p>No permissions.</p>';
	}
}
?>
</div>
</div>
<?php include('footer.php');?>
