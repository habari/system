<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Login to <?php Options::out( 'title' ); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 

	<link rel="stylesheet" href="<?php Site::out_url('habari'); ?>/3rdparty/blueprint/screen.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?php Site::out_url('habari'); ?>/3rdparty/blueprint/print.css" type="text/css" media="print">
	<link rel="stylesheet" href="<?php Site::out_url('admin_theme'); ?>/css/admin.css" type="text/css" media="screen">

	<script src="<?php Site::out_url('scripts'); ?>/jquery.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/jquery.dimensions.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/ui.mouse.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/ui.tabs.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/ui.sortable.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/ui.sortable.ext.js" type="text/javascript"></script>
	<script type="text/javascript">
	var habari = {
		url: { habari: '<?php Site::out_url('habari'); ?>' }
	};
	</script>
	<script src="<?php Site::out_url('admin_theme'); ?>/js/media.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('admin_theme'); ?>/js/admin.js" type="text/javascript"></script>

</head>
<body class="login">

	<div id="page">

		<h1><a href="<?php Site::out_url('habari'); ?>" title="Go to Site"><?php Options::out('title'); ?></a></h1>

		<div class="container">

				<form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>">

					<p>
						<label for="habari_username" class="incontent">Name</label><input type="text" name="habari_username" id="habari_username" class="styledformelement">
					</p>
					<p>
						<label for="habari_password" class="incontent">Password</label><input type="password" name="habari_password" id="habari_password" class="styledformelement">
					</p>
					<p>
						<!--<span class="remember"><input type="checkbox" name="remember"><label for="remember">Remember Me</label></span>-->
						<input class="submit" type="submit" value="Login">
					</p>

				</form>

		</div>

	</div>

<?php
	Plugins::act( 'admin_footer', $this );
	Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
	include ('db_profiling.php');
?>
</body>
</html>
