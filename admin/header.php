<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
	<title>Habari Administration</title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php Options::out('base_url'); ?>system/admin/admin.css" />
	<script src="<?php Options::out('base_url'); ?>scripts/jquery.js"></script>
	<script>
	window.onload = function() {
	    $("tr:nth-child(even)").addClass("even");
	    $("ul>li:nth-child(even)").addClass("even");
	};
	</script>
</head>
<body>
<div id="page">
	<div id="menu">
		<div id="site-name">
			<a href="<?php Options::out('base_url'); ?>" title="<?php Options::out('title'); ?>"><?php Options::out('title'); ?></a>
		</div>
		<div id="logout">
			<a href="<?php URL::out('logout'); ?>" title="logout of Habari"><img src="/system/admin/images/logout.png" alt="Logout of Habari" /></a>
		</div>
		<ol id="menu-items">
			<?php $page = empty(URL::o()->settings['page']) ? 'overview' : URL::o()->settings['page']; ?>
			<li <?php echo ($page == 'overview') ? 'id="current-item"' : ''; ?>><a href="<?php Options::out('base_url'); ?>admin/" title="Overview of your site">Admin</a></li>
			<li <?php echo ($page == 'publish') ? 'id="current-item"' : ''; ?>><a href="<?php URL::out('admin', 'page=publish'); ?>" title="Edit the content of your site">Publish</a></li>
			<li <?php echo ($page == 'options') ? 'id="current-item"' : ''; ?>><a href="<?php URL::out('admin', 'page=options'); ?>" title="edit your site options">Manage</a></li>
		</ol>
	</div>
