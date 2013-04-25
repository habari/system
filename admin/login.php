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
		.off_reset {}
		
		.on_reset, input[type=submit].on_reset {
			display: none;
		}
		.do_reset .on_reset, .do_reset input[type=submit].on_reset {
			display: block;
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
	<div id="page" class="container">
		<div class="columns six offset-by-five">
			<?php echo $form; ?>
			<p class="poweredby"><?php Options::out('title'); ?> is powered by <a href="http://habariproject.org/" title="<?php _e('Go to the Habari site'); ?>">Habari <?php echo Version::get_habariversion(); ?></a>.</p>
		</div>
	</div>
<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
?>

<script type="text/javascript">
	$(document).ready( function() {
		<?php Session::messages_out( true, Method::create( '\\Habari\\Format', 'humane_messages' ) ); ?>
		$('.reset_link').click(function(){$(this).closest('form').toggleClass('do_reset'); return false;});
	});
</script>
<?php
	include ('db_profiling.php');
?>
</body>
</html>
