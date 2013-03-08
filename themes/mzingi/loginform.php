<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php
$user = User::identify();
if ( $user->id ) {
	?>
	<p><?php _e('You are logged in as %s.', array($user->username)); ?></p>
	<p><?php _e('Want to <a href="%s">log out</a>?', array(Site::get_url('habari') . '/auth/logout')); ?></p>
	<?php
}
else {
?>
<?php Plugins::act( 'theme_loginform_before' ); ?>
<?php if ( Session::has_messages() ) Session::messages_out(); ?>
<?php echo $form; ?>
<script type="text/javascript">
	var password_label;
	$(document).ready( function() {
		// No session output via JS, just hide the stuff we don't need
		$("#reset_message").hide();
		$("#reset_button").hide();
		password_label = $('label[for=habari_password]');
		// to fix autofill issues, we need to check the password field on every keyup
		$('#habari_username').keyup( function() {
			setTimeout( "labeler.check( password_label );", 10 );
		} ).click( function() {
			setTimeout( "labeler.check( password_label );", 50 );
		} );
		// for autofill without user input
		setTimeout( function(){ labeler.check( password_label ); }, 10 );
		
		// Make the login form a bit more intuitive when requesting a password reset
		$("#reset_link").click(function() {
			// Hide password box (and surrounding container)
			$("#habari_password").parent().hide();
			// Hide Login button and link
			$("#submit_button").hide();
			$("#reset_link").hide();
			// Show message that explains things a bit better
			$("p#reset_message").fadeIn();
			// Show real button
			$("#reset_button").fadeIn();
			return false;
		});
	});
</script>
<?php Plugins::act( 'theme_loginform_after' ); ?>
<?php
}
?>
