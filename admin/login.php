<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php printf( _t('Login to %s'), Options::get( 'title' ) ); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

	<script type="text/javascript">
	var habari = {
		url: { habari: '<?php Site::out_url('habari'); ?>' }
	};
	</script>

	<?php
		Plugins::act( 'admin_header', $this );
		Stack::out( 'admin_header_javascript', array('Stack', 'scripts') );
		Stack::out( 'admin_stylesheet', array('Stack', 'styles') );
	?>

</head>
<body class="login">

	<div id="page">

		<h1><a href="<?php Site::out_url('habari'); ?>" title="<?php _e('Go to Site'); ?>"><?php Options::out('title'); ?></a></h1>

		<div class="container">
			<?php Plugins::act( 'theme_loginform_before' ); ?>
				<form method="post" action="<?php URL::out( 'auth', array( 'page' => 'login' ) ); ?>">
					<?php // TODO: Use Javascript to add this or automatically hide it on load rather than show it ?>
					<p id="reset_message" style="display:none; margin-bottom:20px;">
						<?php _e('Please enter the username you wish to reset the password for.  A unique password reset link will be emailed to that user.'); ?>
					</p>

					<p>
						<label for="habari_username" class="incontent abovecontent"><?php _e('Name'); ?></label><input type="text" name="habari_username" id="habari_username"<?php if (isset( $habari_username )) { ?> value="<?php echo Utils::htmlspecialchars( $habari_username ); ?>"<?php } ?> placeholder="<?php _e('name'); ?>" class="styledformelement">
					</p>
					<p>
						<label for="habari_password" class="incontent abovecontent"><?php _e('Password'); ?></label><input type="password" name="habari_password" id="habari_password" placeholder="<?php _e('password'); ?>" class="styledformelement">
					</p>
					<?php Plugins::act( 'theme_loginform_controls' ); ?>
					<p>
						<input class="submit" type="submit" name="submit_button" value="<?php _e('Login'); ?>">
					</p>
					<p id="password_utils">
						<input class="submit" type="submit" name="submit_button" value="<?php _e('Reset password'); ?>">
					</p>

				</form>
				<?php Plugins::act( 'theme_loginform_after' ); ?>
		</div>

	</div>

<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
?>
	<script type="text/javascript">
	var password_label;
	$(document).ready( function() {
		<?php Session::messages_out( true, array( 'Format', 'humane_messages' ) ); ?>
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
		// TODO: Stop this submitting the form when we click the Reset Password the first time when the field is populated.
		$("#password_utils input").click(function() {
			// Hide password box
			$("p:has(input[name=habari_password])").hide();
			// Hide Login button
			$("p:has(input[name=submit_button])").first().hide();
			// Show message that explains things a bit better
			$("p#reset_message").fadeIn();
			// Unbind click function
			$("#password_utils input").unbind('click');
			return false;
		});
	});
  </script>
<?php
	include ('db_profiling.php');
?>
</body>
</html>
