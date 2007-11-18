<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Habari Administration</title>

	<link rel="stylesheet" href="<?php Site::out_url('habari'); ?>/3rdparty/blueprint/screen.css" type="text/css" media="screen, projection">
	<link rel="stylesheet" href="<?php Site::out_url('habari'); ?>/3rdparty/blueprint/print.css" type="text/css" media="print">
	<link rel="stylesheet" type="text/css" media="screen" href="<?php Site::out_url('admin_theme'); ?>/css/admin.css">

	<script src="<?php Site::out_url('scripts'); ?>/jquery.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('admin_theme'); ?>/admin.js" type="text/javascript"></script>
	<?php
		Plugins::act( 'admin_header', $this );
		Stack::out( 'admin_stylesheet', '<link rel="stylesheet" type="text/css" href="%s" media="%s">'."\r\n" );
		Stack::out( 'admin_header_javascript', '<script src="%s" type="text/javascript"></script>'."\r\n" );
	?>
</head>
<body>

<ul id="menu">
	<li id="site-name">
		<a href="<?php Site::out_url('habari'); ?>" title="<?php Options::out('title'); ?>"><?php Options::out('title'); ?></a>
	</li>
	<?php
		$page = empty($page) ? 'admin' : $page;
		echo $mainmenu;
	?>
</ul>

<div id="wrapper">