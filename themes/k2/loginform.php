<!-- loginform -->
<?php
if ( isset( $error ) && ( $error == 'Bad credentials' ) ) {
?>
     <p>That login is incorrect.</p>

<?php
}
if ( isset( $user ) && ( $user instanceOf User ) ) {
?>
     <p>You are logged in as <a href="<?php URL::out( 'admin', 'page=user&user=' . $user->username ) ?>" title="Edit Your Profile"><?php echo $user->username; ?></a>.</p>
     <p>Want to <a href="<?php Site::out_url( 'habari' ); ?>/user/logout">log out</a>?</p>
<?php
}
else {
?>
	<?php Plugins::act( 'theme_loginform_before' ); ?>
     <form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>" id="loginform">
      <p>
       <label for="habari_username">Name:</label>
       <input type="text" size="25" name="habari_username" id="habari_username">
      </p>
      <p>
       <label for="habari_password">Password:</label>
       <input type="password" size="25" name="habari_password" id="habari_password">
      </p>
      <?php Plugins::act( 'theme_loginform_controls' ); ?>
      <p>
       <input type="submit" value="Sign in">
      </p>
     </form>
     <?php Plugins::act( 'theme_loginform_after' ); ?>
<?php
}
?>
<!-- /loginform -->
