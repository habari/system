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
		<input id="search" type="search" placeholder="<?php _e('search users'); ?>" autosave="habarisettings" results="10">
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
		
		<input type="submit" value="<?php _e('Delete Selected'); ?>" class="delete button">
	</div>

</div>
</form>

<div class="container users addnewuser settings">

	<h2><?php _e('Add New User'); ?></h2>

	<form method="post" action="">

	<span class="pct25">
		<label for="new_username" class="incontent">Username</label>
		<input type="text" size="40" name="new_username" id="new_username" value="<?php echo ( isset( $settings['new_username'] ) ) ? $settings['new_username'] : ''; ?>" class="styledformelement">
	</span>

	<span class="pct25">
		<label for="new_email" class="incontent">E-Mail</label>
		<input type="text" size="40" id="new_email" name="new_email" value="<?php echo ( isset( $settings['new_email'] ) ) ? $settings['new_email'] : ''; ?>" class="styledformelement">
	</span>
	<span class="pct25">
		<label for="new_pass1" class="incontent">Password</label>
		<input type="password" size="40" name="new_pass1" id="new_pass1" class="styledformelement">
	</span>
	<span class="pct25 last-child">
		<label for="new_pass2" class="incontent">Password Again</label>
		<input type="password" size="40" name="new_pass2" id="new_pass2" class="styledformelement">
	</span>

	<input type="hidden" name="action" value="newuser">
	<input type="submit" value="<?php _e('Add User'); ?>">
	</form>

</div>

<script type="text/javascript">
itemManage.updateURL = habari.url.ajaxUpdateUsers;
itemManage.removeURL = habari.url.ajaxUpdateUsers;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'users')) ?>";
itemManage.fetchReplace = $('.manage.users');
itemManage.inEdit = false;

</script>

<?php include('footer.php');?>
