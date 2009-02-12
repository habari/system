<?php /*

  Copyright 2007-2009 The Habari Project <http://www.habariproject.org/>

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.

*/ ?>
<?php
if ( isset( $error ) ) {
?>
<p><?php _e('That login is incorrect.'); ?></p>
<?php
}
if ( $loggedin ) {
?>
<p><?php _e('You are logged in as'); ?> <?php echo $user->username; ?>.</p>
<p><?php _e('Want to'); ?> <a href="<?php Site::out_url( 'habari' ); ?>/user/logout"><?php _e('log out'); ?></a>?</p>
<?php
}
else {
?>
<?php Plugins::act( 'theme_loginform_before' ); ?>
<form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>">
	<p>
		<label for="habari_username"><small><strong><?php _e('Name:'); ?></strong></small></label>
		<input type="text" size="25" name="habari_username" id="habari_username">
	</p>
	<p>
		<label for="habari_password"><small><strong><?php _e('Password:'); ?></strong></small></label>
		<input type="password" size="25" name="habari_password" id="habari_password">
	</p>
	<?php Plugins::act( 'theme_loginform_controls' ); ?>
	<p>
		<input type="submit" value="<?php _e('GO!'); ?>">
	</p>
</form>
<?php Plugins::act( 'theme_loginform_after' ); ?>
<?php
}
?>