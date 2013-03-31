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
	<style>
		.off_reset {
		}
		.on_reset {
			display: none;
		}
		.do_reset .on_reset {
			display: initial;
		}
		.do_reset .off_reset {
			display: none;
		}

	</style>

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
	$(document).ready( function() {
		<?php Session::messages_out( true, Method::create( '\\Habari\\Format', 'humane_messages' ) ); ?>
		$('.reset_link').click(function(){$(this).closest('form').toggleClass('do_reset')});
	});
</script>
<?php
	include ('db_profiling.php');
?>
</body>
</html>
