<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
	<title>Habari Administration</title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo Options::o()->base_url; ?>system/admin/admin.css" />
</head>
<body>
<div id="page">
	<div id="header">
		<h1><?php echo Options::o()->blog_title; ?> &raquo; <small><a href="<?php echo Options::o()->base_url; ?>" title="View <?php echo Options::o()->blog_title; ?>">view site</a> + <a href="" title="<?php echo Options::o()->base_url; ?>admin/profile">edit your profile</a> + <a href="<?php echo Options::o()->base_url; ?>logout" title="logout of Habari">logout</a></small></h1>
	</div>
	<div id="menu">
		<ul id="menu-items">
			<li><a href="<?php echo Options::o()->base_url; ?>admin/" title="Overview of your site">Dashboard</a></li>
			<li><a href="<?php echo Options::o()->base_url; ?>admin/content" title="Edit the content of your site">Content</a></li>
			<li><a href="<?php echo Options::o()->base_url; ?>admin/options" title="edit your site options">Options</a></li>
			<li>Themes</li>
			<li>Plugins</li>
		</ul>
	</div>