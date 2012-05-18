<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include('header.php');?>

<?php
	// @todo this should be done in adminhandler, not here
	$theme->currentuser = User::identify();
?>


<div class="container navigation">
	<div class="pct40">

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
	</div>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input id="search" type="search" placeholder="<?php _e('search users'); ?>" autofocus="autofocus">
	</span>
</div>

<form method="post" action="" autocomplete="off">
<div class="container users">
	
	<div class="addnewuser item">

		<label for="new_username" class="incontent"><?php _e( 'Username' ); ?></label>
		<input type="text" name="new_username" id="new_username" value="<?php echo ( isset( $settings['new_username'] ) ) ? $settings['new_username'] : ''; ?>" class="border">

			<label for="new_email" class="incontent"><?php _e( 'E-Mail' ); ?></label>
			<input type="text" id="new_email" name="new_email" value="<?php echo ( isset( $settings['new_email'] ) ) ? $settings['new_email'] : ''; ?>" class="border">

			<label for="new_pass1" class="incontent"><?php _e( 'Password' ); ?></label>
			<input type="password" name="new_pass1" id="new_pass1" class="border">

			<label for="new_pass2" class="incontent"><?php _e( 'Password Again' ); ?></label>
			<input type="password" name="new_pass2" id="new_pass2" class="border">

		<input type="submit" name="newuser" value="<?php _e('Add User'); ?>">

	</div>

	<?php $theme->display('users_items'); ?>
</div>

<div class="container transparent">
	<div class="controls item">
		<span class="checkboxandselected pct25">
			<input type="checkbox" id="master_checkbox" name="master_checkbox">
			<label class="selectedtext minor none" for="master_checkbox"><?php _e('None selected'); ?></label>
		</span>

		<input type="hidden" name="nonce" id="nonce" value="<?php echo $wsse['nonce']; ?>">
		<input type="hidden" name="timestamp" id="timestamp" value="<?php echo $wsse['timestamp']; ?>">
		<input type="hidden" name="password_digest" id="password_digest" value="<?php echo $wsse['digest']; ?>">
	
		<span class="reassign minor">
			<?php printf( _t('Reassign posts to %s'), Utils::html_select('reassign', $authors )); ?> and
			<input type="submit" name="delete" value="<?php _e('Delete Selected'); ?>">
		</span>
	</div>
</div>
</form>






<script type="text/javascript">

itemManage.updateURL = habari.url.ajaxUpdateUsers;
itemManage.fetchURL = "<?php echo URL::get('admin_ajax', array('context' => 'users')) ?>";
itemManage.fetchReplace = $('.manage.users');

</script>

<?php include('footer.php');?>
