<?php
$currentuser = User::identify();
if ( ! $currentuser )
{
	die;
}
if ( isset( $settings['message'] ) && ( '' != $settings['message'] ) )
{
	echo "<p><strong>" . $settings['message'] . "</strong></p>";
}
?>
<div style="width: 45%; float: left; border-right: 1px solid #000; text-align: left;">
<strong>Users</strong>
<ul>
<?php
foreach ( User::get_all() as $user )
{
	if ( $user->username == $currentuser->username )
	{
		$url = Url::get('admin', 'page=user');
	}
	else
	{
		$url = Url::get('admin', 'page=user/' . $user->username);
	}
	echo '<li>';
	echo '<a href="' . $url . '">' . $user->username . '</a><br />';
	echo Posts::count_by_author( $user->id, Post::STATUS_PUBLISHED ) . ' published posts, ' . Posts::count_by_author( $user->id, Post::STATUS_DRAFT ) . ' pending drafts, and ' . Posts::count_by_author( $user->id, Post::STATUS_PRIVATE ) . ' private posts.';
	echo '</li>';
}
?>
</ul>
</div>
<div style="width: 45%; float: left; margin-left: 2px;">
<?php
if ( isset( $settings['error'] ) && ( '' != $settings['error'] ) )
{
	echo "<p><strong>" . $settings['error'] . "</strong></p>";
}
?>
<form method="post" action="">
<strong>Add a new user</strong><br />
Username:<br />
<input type="text" size="40" name="username" value="<?php echo ( isset( $settings['username'] ) ) ? $settings['username'] : ''; ?>" /><br />
Email:<br />
<input type="text" size="40" name="email" value="<?php echo ( isset( $settings['email'] ) ) ? $settings['email'] : ''; ?>" /><br />
Password (twice to confirm):<br />
<input type="password" size="40" name="pass1" /><br />
<input type="password" size="40" name="pass2" /><br />
<input type="hidden" name="action" value="newuser" />
<input type="submit" value="Add User" />
</form>
</div>
<div style="clear: both;"></div>

