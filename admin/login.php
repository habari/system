<?php namespace Habari; ?>
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
		Stack::out( 'admin_header_javascript', Method::create('\\Habari\\Stack', 'scripts') );
		Stack::out( 'admin_stylesheet', Method::create('\\Habari\\Stack', 'styles') );
	?>

</head>
<body class="login">

	<div id="page">

		<h1><a href="<?php Site::out_url('habari'); ?>" title="<?php _e('Go to Site'); ?>"><?php Options::out('title'); ?></a></h1>

		<div class="container">
			<?php
				$form = new FormUI( 'habari_login' );
				$form->set_option( 'form_action', URL::get( 'auth', array( 'page' => 'login' ) ) );
				$form->append( 'static', 'reset_message', '<p id="reset_message" style="margin-bottom:20px;">' . _t('Please enter the username you wish to reset the password for.  A unique password reset link will be emailed to that user.') . '</p>' );
				$form->append( 'text', 'habari_username', 'null:null', _t('Name') );
				$form->habari_username->template = 'admincontrol_text';
				$form->append( 'password', 'habari_password', 'null:null', _t('Password') );
				$form->habari_password->template = 'admincontrol_password';
				$form->append( 'submit', 'submit_button', _t('Login') );
				$form->append( 'button', 'passwordreset_button', _t('Reset password') );
				$form->out();
			?>
		</div>

	</div>

<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
?>
	<script type="text/javascript">
	var password_label;
	$(document).ready( function() {
		<?php Session::messages_out( true, Method::create( '\Habari\Format', 'humane_messages' ) ); ?>
		$("#reset_message").hide();
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
		$("#passwordreset_button input").click(function() {
			// Hide password box (and surrounding container)
			$("#habari_password").parent().hide();
			// Hide Login button
			$("#submit_button").hide();
			// Show message that explains things a bit better
			$("p#reset_message").fadeIn();
			// Unbind click function
			$("#passwordreset_button input").unbind('click');
			return false;
		});
	});
  </script>
<?php
	include ('db_profiling.php');
?>
</body>
</html>
