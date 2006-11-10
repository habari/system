<?php
	$user = User::identify();
	if ( ! $user )
	{
		die;
	}
?>
<div id="content-area">
	<h3>Your Profile</h3>
	<?php
	if ( isset( $settings['results'] ) &&  'success' == $settings['results'] )
	{
		echo "<p><strong>Your profile has been updated!</strong></p>";
	}
	else
	{
		echo "<p>Below are the data that Habari knows about you.</p>";
	}
	?>
	<form name="update-profile" id="update-profile" action="<?php Options::out('base_url'); ?>admin/user" method="post">
		<p><label>Nickname:</label></p>
		<p><input type="text" name="nickname" value="<?php echo $user->nickname; ?>" /></p>
		<p><label>Email address:</label></p>
		<p><input type="text" name="email" value="<?php echo $user->email; ?>"/></p>
		<p><label>New Password:</label></p>
		<?php
		if ( isset( $settings['error'] ) && 'pass' == $settings['error'] )
		{
			echo "<p><strong>The passwords you typed did not match.  Please try again!</strong></p>";
		}
		?>
		<p><input type="password" name="pass1" value=""/></p>
		<p><input type="password" name="pass2" value=""/> (type again to confirm)</p>
		<p><input type="submit" value="Update Profile!" /></p>
	</form>
</div>
