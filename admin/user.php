<?php include('header.php');?>

<?php // Fetch user information
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
			echo "<p class='error'>No such user!</p>";
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


<div class="container navigation">
	<span class="pct40">
		<form>
		<select class="navigationdropdown" onChange="navigationDropdown.changePage(this.form.navigationdropdown)" name="navigationdropdown">
			<?php /*
			foreach ( Users::get_all() as $user ) {
				if ( $user->username == $currentuser->username ) {
					$url = Url::get( 'admin', 'page=user' );
				}
				else {
					$url = Url::get( 'user_profile', array( 'page' => 'user', 'user' => $user->username ) );
				}
				echo '<option id="' . $user->id . '" value="' . $url . '">' . $user->displayname . '</option>';
			} */ ?>
			<option value="">Complete User List</option>
		</select>
		</form>
	</span>
	<span class="or pct20">
		or
	</span>
	<span class="pct40">
		<input type="search" placeholder="search users" autosave="habarisettings" results="10"></input>
	</span>
</div>


<form name="update-profile" id="update-profile" action="<?php URL::out('admin', 'page=user'); ?>" method="post">
<div class="container settings user userinformation">

	<h2><?php echo $possessive; ?> User Information</h2>

		<input type="hidden" name="user_id" value="<?php echo $user->id; ?>">

		<div class="item clear" id="displayname">
			<span class="column span-5">
				<label for="sitename">Display Name</label>
			</span>
			<span class="column span-14 last">
				<input type="text" name="displayname" class="border big" value="<?php echo $user->info->displayname; ?>"></input>
			</span>
		</div>

		<div class="item clear" id="username">
			<span class="column span-5">
				<label for="sitetagline">User Name</label>
			</span>
			<span class="column span-14 last">
				<input type="text" name="username" class="border" value="<?php echo $user->username; ?>"></input>
			</span>
		</div>	

		<div class="item clear" id="email">
			<span class="column span-5">
				<label for="sitetagline">E-Mail</label>
			</span>
			<span class="column span-14 last">
				<input type="text" name="email" class="border" value="<?php echo $user->email; ?>"></input>
			</span>
		</div>	

		<div class="item clear" id="portraiturl">
			<span class="column span-5">
				<label for="sitetagline">Portrait URL</label>
			</span>
			<span class="column span-14 last">
				<input type="text" name="imageurl" class="border" value=""></input>
			</span>
		</div>	
</div>


<div class="container settings user changepassword">

	<h2>Change Password</h2>

		<div class="item clear" id="password">
			<span class="column span-5">
				<label for="sitetagline">Password</label>
			</span>
			<span class="column span-14 last">
				<input type="password" name="pass1" class="border" value=""></input>
			</span>
		</div>	

		<div class="item clear" id="passwordagain">
			<span class="column span-5">
				<label for="sitetagline">Password Again</label>
			</span>
			<span class="column span-14 last">
				<input type="password" name="pass2" class="border" value=""></input>
			</span>
		</div>	
</div>

<?php Plugins::act( 'theme_admin_user', $user ); ?>

<div class="container transparent">
	<input type="submit" value="Apply" class="savebutton"></input>
</div>
</form>

<!-- Not sure what to do with this just now, so I'm commenting it out and returning later

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
	echo '<p><ul><li><input type="radio" name="reassign" id="purge" value="0" checked>Delete posts</li>';
	$author_list= DB::get_results('SELECT id,username FROM ' . DB::table('users') . ' WHERE username <> ? ORDER BY username ASC', array( $user->username) );
	foreach ( $author_list as $author ) {
		$authors[ $author->id ]= $author->username;
	}
	echo '<li><input type="radio" name="reassign" id="reassign" value="1">';
	printf( _t('Reassign posts to: %s'), Utils::html_select('Author', $authors ));
	echo '</li></ul>';
	echo '<p><input type="submit" value="'. _t('DELETE USER') . '"><p>';
	echo "</form>\n";
}
?>

-->

<?php include('footer.php');?>
