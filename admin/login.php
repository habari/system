<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php printf( _t('Login to %s'), Options::get( 'title' ) ); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<?php	Stack::add('admin_stylesheet', array(Site::get_url('admin_theme') . '/css/admin.css', 'screen'), 'admin'); ?>

	<script src="<?php Site::out_url('vendor'); ?>/jquery.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('vendor'); ?>/jquery-ui.min.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('vendor'); ?>/humanmsg/humanmsg.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('vendor'); ?>/jquery.hotkeys.js" type="text/javascript"></script>

	<script type="text/javascript">
	var habari = {
		url: { habari: '<?php Site::out_url('habari'); ?>' }
	};
	</script>
	<script src="<?php Site::out_url('admin_theme'); ?>/js/admin.js" type="text/javascript"></script>

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
	})
  </script>
<?php
	include ('db_profiling.php');
?>
</body>
</html>
