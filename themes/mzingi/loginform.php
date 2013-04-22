<?php namespace Habari; ?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php
if ( $loggedin ) {
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
$(document).ready( function() {
	$('.reset_link').click(function(){$(this).closest('form').toggleClass('do_reset'); return false;});
});
</script>
<?php Plugins::act( 'theme_loginform_after' ); ?>
<?php
}
?>
