<?php include('header.php'); ?>
<div class="container">
<hr>
<?php
$currentuser = User::identify();
?>
<h3><?php _e('Group Management'); ?></h3>
<div class="span-8">
<p><?php _e('Groups'); ?></p>
<form method="post" action="">
<input type="textarea" size="20" name="add_group" />
<input type="submit" value="<?php _e('Add'); ?>">
</form>
<ul>
<?php
foreach ( $groups as $group ) {
	echo '<li>';
	echo '<form method="post" action=""><input type="hidden" name="group" value="' . $group->name . '"><input type="submit" name="delete_group" value="' . _t('Delete') . '"> ';
	echo '<input type="submit" name="edit_group" value="' . _t('Edit') . '"> ';
	echo $group->name . '</form>';
	echo '</li>';
}
?>
</ul>
</div>
<div class="span-8">
<p><?php _e('Members'); ?></p>
<?php
if ( isset( $group_edit ) ) {
	if ( isset( $users) && ( ! empty( $users ) ) ) {
		echo '<p>' . _t('Editing members of ') . $group_edit->name . '</p>';
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="group" value="' . $group_edit->name . '">';
		foreach ( $users as $user ) {
			echo '<input type="checkbox" name="user_id[]" value="' . $user->id . '"';
			if ( in_array( $user->id, $group_edit->members ) ) {
				echo ' checked';
			}
			echo '"> ' . $user->username . '<br>';
		}
		echo '<input type="submit" name="users" value="' . _t('Submit') . '"></form>';
	} else {
		echo '<p>' . _t('No members.') . '</p>';
	}
}
?>
</div>
<div class="span-8 last">
<p><?php _e('Permissions'); ?></p>
<?php
if ( isset( $group_edit ) ) {
	if ( isset( $permissions) && ( ! empty( $permissions ) ) ) {
		echo '<p>' . _t('Editing Permissions of ') . $group_edit->name . '</p>';
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="group" value="' . $group_edit->name . '">';
		echo '<table><tr><th>' . _t('Granted') . '</th><th>' . _t('Permission') . '</th><th>' . _t('Denied') . '</th></tr>';
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
		echo '<p>' . _t('No permissions.') . '</p>';
	}
}
?>
</div>
</div>
<?php include('footer.php');?>
