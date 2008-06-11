<?php include('header.php');?>

<?php $theme->currentuser = User::identify(); ?>


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
			<option value=""><?php _e('Complete User List'); ?></option>
		</select>
		</form>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input type="search" placeholder="<?php _e('search users'); ?>" autosave="habarisettings" results="10">
	</span>
</div>

<form method="post" action=""><div class="container manage users">

<?php $theme->display('users_items'); ?>

</div>

<div class="container transparent">

	<div class="item users controls">
		<span class="pct25">
			<input type="checkbox">
			<span class="selectedtext minor none"><?php _e('None selected'); ?></span>
		</span>
		<input type="hidden" name="action" value="delete">
		<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
		<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
		<input type="hidden" name="PasswordDigest" id="PasswordDigest" value="<?php echo $wsse['digest']; ?>">
		
		<span class="pct50 minor reassigntext"><?php printf( _t('Reassign posts to: %s'), Utils::html_select('reassign', $authors )); ?></span>
		
		<input type="submit" value="<?php _e('Delete'); ?>" class="submitbutton">
	</div>

</div>
</form>

<div class="container users addnewuser settings">

	<h2><?php _e('Add New User'); ?></h2>

	<form method="post" action="">

	<span class="pct25">
		<label for="username" class="incontent">Username</label>
		<input type="text" size="40" name="username" id="username" value="<?php echo ( isset( $settings['username'] ) ) ? $settings['username'] : ''; ?>" class="styledformelement">
	</span>

	<span class="pct25">
		<label for="email" class="incontent">E-Mail</label>
		<input type="text" size="40" id="email" name="email" value="<?php echo ( isset( $settings['email'] ) ) ? $settings['email'] : ''; ?>" class="styledformelement">
	</span>
	<span class="pct25">
		<label for="pass1" class="incontent">Password</label>
		<input type="password" size="40" name="pass1" id="pass1" class="styledformelement">
	</span>
	<span class="pct25 last-child">
		<label for="pass2" class="incontent">Password Again</label>
		<input type="password" size="40" name="pass2" id="pass2" class="styledformelement">
	</span>

	<input type="hidden" name="action" value="newuser">
	<input type="submit" value="<?php _e('Add User'); ?>">
	</form>

</div>

<?php include('footer.php');?>
