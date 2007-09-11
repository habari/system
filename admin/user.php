<?php include('header.php');?>
<div class="container">
<hr />
<?php
	$currentuser = User::identify();
	if ( ! $currentuser ) {
		Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
		exit;
	}
	if ( isset( $result ) && 'deleted' == $result ) {
		echo 'The user ' . $user . ' has been deleted.';
	}
	else {
	// are we looking at the current user's profile, or someone else's?
	// $user will contain the username specified on the URL
	// http://example.com/admin/user/skippy
	if ( isset( $user ) && ( $user != $currentuser->username ) ) {
		$user = User::get_by_name( $user );
		if ( ! $user ) {
			echo "No such user!";
		}
		$who= $user->username;
		$possessive= $user->username . "'s";
	}
	else {
		$user= $currentuser;
		$who= "You";
		$possessive= "Your";
	}
?>
<div style="width: 45%; float: left; border-right: 1px solid #000; text-align: left;">
	<h3><?php echo $possessive; ?> Profile</h3>
	<?php
	if ( isset( $result ) && 'success' == $result ) {
		echo '<p class="update"><strong>' . $possessive . ' profile has been updated!</strong></p>';
	}
	else {
		echo "<p>Below are the data that Habari knows about " . $who . ".</p>";
	}
	?>
	<form name="update-profile" id="update-profile" action="<?php URL::out('admin', 'page=user'); ?>" method="post">
		<input type="hidden" name="user_id" value="<?php echo $user->id; ?>">
		<p><label>Username:</label></p>
		<p><input type="text" name="username" value="<?php echo $user->username; ?>"></p>
		<p><label>Email address:</label></p>
		<p><input type="text" name="email" value="<?php echo $user->email; ?>"></p>
		<p><label>New Password:</label></p>
		<?php
		if ( isset( $settings['error'] ) && 'pass' == $settings['error'] )
		{
			echo "<p><strong>The passwords you typed did not match.  Please try again!</strong></p>";
		}
		?>
		<p><input type="password" name="pass1" value=""></p>
		<p><input type="password" name="pass2" value=""> (type again to confirm)</p>
		<p><label>Image URL:</label></p>
	        <p><input type="text" name="imageurl" value="<?php echo $user->info->imageurl; ?>"></p>
	        
	    <?php Plugins::act( 'theme_admin_user, $user ); ?>
	    
		<p><input type="submit" value="Update Profile!"></p>
	</form>
</div>
<div style="width: 45%; float: left; margin-left: 2px;">
<?php
if ( Posts::count_by_author( $user->id, Post::status('published') ) ) {
	echo $possessive ." five most recent published posts:<br>\n";
	echo "<ul>\n";
	foreach ($posts = Posts::get( array( 'user_id' => $user->id,
						'limit' => 5,
						'status' => Post::status('published'),
					) ) as $post )
	{
		echo '<li><a href="' . $post->permalink . '">' . $post->title ."</a></li>\n";
	}
	echo "</ul>\n";
}
else {
	echo "<p>No published posts.</p>\n";
}
if ( $user == $currentuser ) {
	echo $possessive . ' five most recent draft posts:<br><ul>';
	foreach ($posts = Posts::get( array( 'user_id' => $user->id,
						'limit' => 5,
						'status' => Post::status('draft'),
					) ) as $post )
	{
		echo '<li><a href="' . $post->permalink . '">' . $post->title . "</a></li>\n";
	}
	echo "</ul>\n";
}
echo "<p></p>\n";
if ( $user != $currentuser ) {
	echo "<form method='post'>";
	echo "<div style='width: 100%, background: red;'>\n";
	echo "<input type='hidden' name='delete' value='user'>\n";
	echo "<input type='hidden' name='user_id' value='" . $user->id . "'>\n";
	echo "<input type='submit' value='DELETE USER'>\n";
	echo "</form>\n";
}
?>
</div>
<div style="clear: both;"></div>
<?php
}
?>
</div>
<?php include('footer.php');?>
