<?php include('header.php');?>
<div class="container">
<hr>
<?php if(Session::has_messages()) {Session::messages_out();} ?>
<div class="column prepend-1 span-22 append-1">
<?php
	$currentuser = User::identify();
	if ( ! $currentuser ) {
		Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
		exit;
	}
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
		$who= "you";
		$possessive= "your";
	}
?>
<div class="column span-10 first">
	<h3><?php echo $possessive; ?> Profile</h3>
	<?php
	if ( Session::has_messages() ) {
	}
	else {
		echo "<p>Below are the data that Habari knows about " . $who . ".</p>";
	}
	?>
	<form name="update-profile" id="update-profile" action="<?php URL::out('admin', 'page=user'); ?>" method="post">
		<p><input type="hidden" name="user_id" value="<?php echo $user->id; ?>"></p>
		<p><label>Username:</label></p>
		<p><input type="text" name="username" value="<?php echo $user->username; ?>"></p>
		<p><label>Email address:</label></p>
		<p><input type="text" name="email" value="<?php echo $user->email; ?>"></p>
		<p><label>New Password:</label></p>
		<p><input type="password" name="pass1" value=""></p>
		<p><input type="password" name="pass2" value=""> (type again to confirm)</p>
		<p><label>Image URL:</label></p>
	        <p><input type="text" name="imageurl" value="<?php echo $user->info->imageurl; ?>"></p>

	    <?php Plugins::act( 'theme_admin_user', $user ); ?>

		<p><input type="submit" value="Update Profile!"></p>
	</form>
</div>
<div class="column span-10 prepend-1 last">
<?php
if ( Posts::count_by_author( $user->id, Post::status('published') ) ) {
	echo $possessive ." five most recent published posts:<br>\n";
	$posts = Posts::get( array( 'user_id' => $user->id, 'limit' => 5, 'status' => Post::status('published'), ) );
	if ( count( $posts ) > 0 ) {
		echo "<ul>\n";
		foreach ( $posts as $post ) {
			echo '<li><a href="' . $post->permalink . '">' . $post->title ."</a></li>\n";
		}
		echo "</ul>\n";
	}
}
else {
	echo "<p>No published posts.</p>\n";
}
if ( $user == $currentuser ) {
	echo $possessive . ' five most recent draft posts:<br>';
	$posts = Posts::get( array( 'user_id' => $user->id, 'limit' => 5, 'status' => Post::status('draft'), ) );
	if ( count( $posts ) > 0 ) {
		echo "<ul>\n";
		foreach ($posts as $post ) {
			echo '<li><a href="' . $post->permalink . '">' . $post->title . "</a></li>\n";
		}
		echo "</ul>\n";
	}
}
echo "<hr>\n";
if ( $user != $currentuser ) {
	echo '<form method="post" action="">'."\n";
	echo '<p><input type="hidden" name="delete" value="user"><p>'."\n";
	echo '<p><input type="hidden" name="user_id" value="' . $user->id . '"><p>'."\n";
	echo '<p><input type="submit" value="DELETE USER"><p>';
	echo "</form>\n";
}
?>
</div>
<div style="clear: both;"></div>
</div>
</div>
<?php include('footer.php');?>
