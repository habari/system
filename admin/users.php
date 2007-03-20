<?php include('header.php');?>
<div id="content-area">
<div class="dashboard-block c3" id="welcome">
<?php
$currentuser = User::identify();
if ( ! $currentuser )
{
	die;
}
if ( isset( $result ) ) {
	switch( $result ) {
		case 'success':
			echo '<p class="update">' . $username;
			_e(' has been created!');
			echo '</p>';
			break;
	}
}
?>
<h1>User Managment</h1>
<p>Add, edit and remove users from your site from this interface.</p>
<p><strong>Users</strong></p>
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
	echo Posts::count_by_author( $user->id, Post::status('published') ) . ' published posts, ' . Posts::count_by_author( $user->id, Post::status('draft') ) . ' pending drafts, and ' . Posts::count_by_author( $user->id, Post::status('private') ) . ' private posts.';
	echo '</li>';
}
?>
</ul>
</div>
<div class="dashboard-block c3" id="welcome">
<?php
if ( isset( $settings['error'] ) && ( '' != $settings['error'] ) )
{
	echo "<p><strong>" . $settings['error'] . "</strong></p>";
}
?>
<form method="post" action="">
<p><strong>Add a new user</strong></p>
<p>Username: <input type="text" size="40" name="username" value="<?php echo ( isset( $settings['username'] ) ) ? $settings['username'] : ''; ?>" /></p>
<p>Email: <input type="text" size="40" name="email" value="<?php echo ( isset( $settings['email'] ) ) ? $settings['email'] : ''; ?>" /></p>
<p>Password (twice to confirm):</p>
<p><input type="password" size="40" name="pass1" /></p>
<p><input type="password" size="40" name="pass2" /></p>
<input type="hidden" name="action" value="newuser" />
<p><input type="submit" value="Add User" /></p>
</form>
</div>

<?php include('footer.php');?>
