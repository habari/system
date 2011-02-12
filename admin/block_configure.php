<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php Options::out('title'); ?> &middot; <?php echo $admin_title; ?></title>
	<script type="text/javascript">
	var habari = {
		url: {
			habari: '<?php Site::out_url('habari'); ?>',
		}
	};
	</script>
	<?php
	Plugins::act( 'admin_header', $this );
	Stack::out( 'admin_header_javascript', array('Stack', 'scripts') );
	Stack::out( 'admin_stylesheet', array('Stack', 'styles') );
	?>
	<!--[if IE 7]>
	<link rel="stylesheet" type="text/css" href="<?php Site::out_url('admin_theme'); ?>/css/ie.css" media="screen">
	<![endif]-->

	<?php
	Plugins::act( 'admin_header_after', $this );
	?>

</head>
<body class="page-<?php echo $page; ?> modal">

<div id="spinner"></div>

<div id="page">

<?php echo $content; ?>


<?php
Plugins::act( 'admin_footer', $this );
Stack::out( 'admin_footer_javascript', array('Stack', 'scripts') );
?>

</div>

</body>
</html>
