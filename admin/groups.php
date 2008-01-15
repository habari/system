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
foreach ( $groups as $id => $name )
{
        echo '<li>';
        echo '<form method="post" action=""><input type="hidden" name="group" value="' . $id . '"><input type="submit" name="delete_group" value="Delete"> ';
	echo '<input type="submit" name="edit_group" value="Edit"> ';
	echo $name . '</form>';
        echo '</li>';
}
?>
</ul>
</div>
<div class="column span-8">
<p>Members</p>
<?php
if ( isset( $group ) ) {
	if ( isset( $users) && ( ! empty( $users ) ) ) {
		echo '<form method="post" action="">';
		echo '<input type="hidden" name="group" value="' . $group . '">';
		echo Utils::html_select( 'add_user', $users );
		echo ' <input type="submit" value="Add"></form>';
	}
	if ( ! empty($group_members) ) {
	        echo '<ul>';
	        foreach ( $group_members as $member ) {
	                $user= User::get_by_id($member);
	                echo '<li><form method="post" action=""><input type="hidden" name="remove_user" value="' . $user->id . '">';
			echo '<input type="hidden" name="user_group" value="' . $group . '"><input type="submit" value="Remove"></form> ' . $user->username . '</li>';
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
</div>
</div>
<?php include('footer.php');?>
