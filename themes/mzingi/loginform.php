<?php
if ( isset( $error ) ) {
?>
<p><?php _e('That login is incorrect.'); ?></p>
<?php
}
if ( $user ) {
?>
<p><?php _e('You are logged in as'); ?> <?php echo $user->username; ?>.</p>
<p><?php _e('Want to'); ?> <a href="<?php Site::out_url( 'habari' ); ?>/user/logout"><?php _e('log out'); ?></a>?</p>
<?php
}
else {
?>
<form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>">
	<p>
		<label for="habari_username"><small><strong><?php _e('Name:'); ?></strong></small></label>
		<input type="text" size="25" name="habari_username" id="habari_username">
	</p>
	<p>
		<label for="habari_password"><small><strong><?php _e('Password:'); ?></strong></small></label>
		<input type="password" size="25" name="habari_password" id="habari_password">
	</p>
	<p>
		<input type="submit" value="<?php _e('GO!'); ?>">
	</p>
</form>
<?php
}
?>
