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
			<?php echo $form; ?>
		</div>

	</div>

<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
?>
<script type="text/javascript">
	var password_label;
	$(document).ready( function() {
		<?php Session::messages_out( true, Method::create( '\\Habari\\Format', 'humane_messages' ) ); ?>
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
<?php
	include ('db_profiling.php');
?>
</body>
</html>
