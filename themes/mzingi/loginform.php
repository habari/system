<?php
if ( isset( $error ) ) {
?>
<p>That login is incorrect.</p>
<?php
}
if ( $user ) {
?>
<p>You are logged in as <?php echo $user->username; ?>.</p>
<p>Want to <a href="<?php Site::out_url( 'habari' ); ?>/user/logout">log out</a>?</p>
<?php
}
else {
?>
<form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>">
	<p>
		<label for="habari_username"><small><strong>Name:</strong></small></label>
		<input type="text" size="25" name="habari_username" id="habari_username">
	</p>
	<p>
		<label for="habari_password"><small><strong>Password:</strong></small></label>
		<input type="password" size="25" name="habari_password" id="habari_password">
	</p>
	<p>
		<input type="submit" value="GO!">
	</p>
</form>
<?php
}
?>
