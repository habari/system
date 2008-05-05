<?php include('header.php');?>
<div class="container">
<hr>
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
		foreach ( $users as $user ) {
			echo '<input type="checkbox" name="user_id[]" value="' . $user->id . '"';
			if ( in_array( $user->id, $group_edit->members ) ) {
				echo ' checked';
			}
			echo '"> ' . $user->username . '<br>';
		}
		echo '<input type="submit" name="users" value="Submit"></form>';
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
		echo '<table><tr><th>Granted</th><th>Permission</th><th>Denied</th></tr>';
		foreach( $permissions as $perm ) {
			echo '<tr>';
			if(  isset( $permissions_granted[ $perm->id ] ) ) {
				// indicate that this permission is granted
			} elseif ( isset( $permissions_denied[ $perm->id ] ) ) {
				// indicate that this permission is denied
			}
			echo "<td><input type='checkbox' name='grant[]' value='{$perm->id}'";
			if ( in_array( $perm->id, $permissions_granted ) ) {
				echo ' checked';
			}
			echo "></td><td> {$perm->description} </td><td>";
			echo "<input type='checkbox' name='deny[]' value='{$perm->id}'";
			if ( in_array( $perm->id, $permissions_denied ) ) {
				echo ' checked';
			}
			echo '></td></tr>';
		}
		echo '<tr><td colspan="3"><input type="submit" name="permissions" value="' . _t('Submit') . '"></td>';
		echo '</table></form>';
	} else {
		echo '<p>No permissions.</p>';
	}
}
?>
</div>
</div>
<?php include('footer.php');?>
