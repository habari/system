<?php include('header.php');?>

<?php // Fetch user information
	$currentuser = User::identify();
	if ( ! $currentuser ) {
		Utils::redirect( URL::get( 'user', array( 'page' => 'login' ) ) );
	}
	// are we looking at the current user's profile, or someone else's?
	// $user will contain the username specified on the URL
	// http://example.com/admin/user/skippy
	if ( isset( $user ) && is_string( $user ) && ( $user != $currentuser->username ) ) {
		$user = User::get_by_name( $user );
		if ( ! $user ) {
			echo "<p class='error'>" . _t('No such user!') . "</p>";
		}
		$who = $user->username;
		$possessive = sprintf( _t("%s's User Information"), $user->username );;
	}
	else {
		$user = $currentuser;
		$who = _t("You");
		$possessive = _t("Your User Information");
	}
?>


<div class="container navigation">
	<span class="pct40">
		<form>
		<select class="navigationdropdown" onChange="navigationDropdown.filter();" name="navigationdropdown">
			<option value="all"><?php _e('All options'); ?></option>
		</select>
		</form>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input id="search" type="search" placeholder="<?php _e('search users'); ?>" autosave="habarisettings" results="10">
	</span>
</div>


<div class="container transparent userstats">
<?php
	$message_bits = array();
	$post_statuses = Post::list_post_statuses();
	unset( $post_statuses[array_search( 'any', $post_statuses )] );
	foreach ( $post_statuses as $status_name => $status_id ) {
		$count = Posts::count_by_author( $user->id, $status_id );
		if ( $count > 0 ) {
			$message = '<strong><a href="' . URL::get( 'admin', array( 'page' => 'posts', 'user_id' => $user->id, 'type' => Post::type( 'any' ), 'status' => $status_id ) ) . '">';
			$message.= sprintf( '%d ' . _n( _t( $status_name . ' post' ), _t( $status_name . ' posts' ), $count ), $count ) ;
			$message.= '</a></strong>';
			$message_bits[]= $message;
		}
	}
	echo Format::and_list( $message_bits );
?>
</div>


<form name="update-profile" id="update-profile" action="<?php URL::out('admin', 'page=user'); ?>" method="post">
<div class="container settings user userinformation" id="userinformation">

	<h2><?php echo $possessive; ?></h2>

		<input type="hidden" name="user_id" value="<?php echo $user->id; ?>">

		<div class="item clear" id="displayname">
		<span class="pct20">
			<label for="displayname"><?php _e('Display Name'); ?></label>
			</span>
		<span class="pct80">
				<input type="text" name="displayname" class="border big" value="<?php echo $user->info->displayname; ?>">
			</span>
		</div>

		<div class="item clear" id="username">
		<span class="pct20">
			<label for="username"><?php _e('User Name'); ?></label>
			</span>
		<span class="pct80">
				<input type="text" name="username" class="border" value="<?php echo $user->username; ?>">
			</span>
		</div>	

		<div class="item clear" id="email">
		<span class="pct20">
			<label for="email"><?php _e('E-Mail'); ?></label>
			</span>
		<span class="pct80">
				<input type="text" name="email" class="border" value="<?php echo $user->email; ?>">
			</span>
		</div>	

		<div class="item clear" id="portraiturl">
		<span class="pct20">
			<label for="imageurl"><?php _e('Portrait URL'); ?></label>
			</span>
		<span class="pct80">
			<input type="text" name="imageurl" class="border" value="<?php echo $user->info->imageurl; ?>">
			</span>
		</div>	
</div>


<div class="container settings user changepassword" id="changepassword">

	<h2><?php _e('Change Password'); ?></h2>

		<div class="item clear" id="password">
		<span class="pct20">
				<label for="sitetagline"><?php _e('Password'); ?></label>
			</span>
		<span class="pct80">
				<input type="password" name="pass1" class="border" value="">
			</span>
		</div>	

		<div class="item clear" id="passwordagain">
		<span class="pct20">
				<label for="sitetagline"><?php _e('Password Again'); ?></label>
			</span>
		<span class="pct80">
				<input type="password" name="pass2" class="border" value="">
			</span>
		</div>	
</div>

<div class="container settings regionalsettings" id="regionalsettings">
	<h2>Regional Settings</h2>

	<div class="item clear" id="timezone">
		<span class="pct20">
			<label for="timezone">Timezone</label>
		</span>
		<span class="pct20">
			<select id="timezone" name="locale_tz">
			<?php foreach (DateTimeZone::listIdentifiers() as $tz_identifier) : ?>
				<option value="<?php echo $tz_identifier; ?>" <?php echo ( $user->info->locale_tz == $tz_identifier) ? 'selected="selected"' : '' ?>><?php echo $tz_identifier; ?></option>
			<?php endforeach; ?>
			</select>
		</span>
	</div>

	<div class="item clear" id="date_format">
		<span class="pct20">
			<label for="date_format">Date Format</label>
            <em>Use a date format string usable by the php date()
            function. See <a href="http://php.net/date">php.net/date</a> for
            details</em>
		</span>
		<span class="pct20">
			<input type="text" name="locale_date_format" class="border" value="<?php echo $user->info->locale_date_format ?>">
		</span>
		<span class="pct80 helptext">
			<span><?php HabariDateTime::date_create()->out($user->info->locale_date_format) ?></span>
		</span>
	</div>


	<div class="item clear" id="time_format">
		<span class="pct20">
			<label for="time_format">Time Format</label>
            <em>Use a date format string usable by the php date()
            function. See <a href="http://php.net/date">php.net/date</a> for
            details</em>
		</span>
		<span class="pct20">
			<input type="text" name="locale_time_format" class="border" value="<?php echo $user->info->locale_time_format ?>">
		</span>
		<span class="pct80 helptext">
			<span><?php HabariDateTime::date_create()->out($user->info->locale_time_format) ?></span>
		</span>
	</div>
</div>

<?php Plugins::act( 'theme_admin_user', $user ); ?>



<div class="container controls transparent">
	<span class="pct25">
		<input type="submit" value="<?php _e('Apply'); ?>" class="button save">
	</span>
	<span class="pct40 reassigntext">
		<?php printf( _t('Reassign posts to: %s'), Utils::html_select('reassign', $authors )); ?>
	</span>
	<span class="minor pct10 conjunction">
		<?php _e('and'); ?>
	</span>
	<span class="pct20">
		<input type="submit" name="delete" value="<?php _e('Delete'); ?>" class="delete button">
	</span>

	<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
	<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
	<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">

</div>
</form>

<!-- Not sure what to do with this just now, so I'm commenting it out and returning later

<?php
if ( Posts::count_by_author( $user->id, Post::status('published') ) ) {
	echo $possessive . _t(' five most recent published posts:') . "<br>\n";
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
	echo "<p>" . _t('No published posts.') . "</p>\n";
}
if ( $user == $currentuser ) {
	echo $possessive . _t(' five most recent draft posts:') . '<br>';
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
	echo '<p><ul><li><input type="radio" name="reassign" id="purge" value="0" checked>' . _t('Delete posts') . '</li>';
	$author_list = DB::get_results('SELECT id,username FROM ' . DB::table('users') . ' WHERE username <> ? ORDER BY username ASC', array( $user->username) );
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
