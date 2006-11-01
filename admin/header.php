<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
	<title>Habari Administration</title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php Options::out('base_url'); ?>system/admin/admin.css" />
</head>
<body>
<div id="page">
	<div id="header">
		<h1>Welcome back <?php echo User::identify()->nickname; ?> &raquo; <a href="<?php Options::out('base_url'); ?>" title="View <?php Options::out('title'); ?>">view site</a> + <a href="<?php echo URL::out('admin', 'page=user'); ?>" title="<?php URL::out('admin', 'page=profile'); ?>">edit your profile</a> + <a href="<?php URL::out('logout'); ?>" title="logout of Habari">logout</a></h1>
	</div>
	<div id="menu">
		<ul id="menu-items">
			<li id="current-item"><a href="<?php Options::out('base_url'); ?>admin/" title="Overview of your site">Overview</a></li>
			<li><a href="<?php URL::out('admin', 'page=content'); ?>" title="Edit the content of your site">Content</a></li>
			<li><a href="<?php URL::out('admin', 'page=options'); ?>" title="edit your site options">Options</a></li>
			<li><a href="<?php URL::out('admin', 'page=themes'); ?>" title="manage your sites themes">Themes</a></li>
			<li><a href="<?php URL::out('admin', 'page=plugins'); ?>" title="edit your sites plugins">Plugins</a></li>
		</ul>
	</div>
