<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
	<title>Habari Administration</title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php Site::out_url('admin_theme'); ?>/admin.css" />
	<script src="<?php Site::out_url('scripts'); ?>/jquery.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('scripts'); ?>/support.js" type="text/javascript"></script>
	<script type="text/javascript">
  		$(document).ready(function(){
		    $("tbody/tr:nth-child(even)").addClass("even");
		    $(".dashbox ul>li:nth-child(even)").addClass("even");
		    $("#stats td+td").addClass('value');
				$("#menu .menu-item").hover(
					function(){ $("ul", this).fadeIn("fast"); }, 
					function() { } 
				);
		  	if (document.all) {
					$("#menu .menu-item").hoverClass("sfHover");
				}
		  });
			$.fn.hoverClass = function(c) {
				return this.each(function(){
					$(this).hover( 
						function() { $(this).addClass(c);  },
						function() { $(this).removeClass(c); }
					);
				});
			};  
	</script>
</head>
<body>
<div id="page">
	<div id="menubar">
		<div id="site-name">
			<a href="<?php Site::out_url('habari'); ?>" title="<?php Options::out('title'); ?>"><?php Options::out('title'); ?></a>
		</div>
		<ol id="menu">
			<?php $page = empty($page) ? 'overview' : $page; ?>
			<li class="menu-item" <?php echo ($page == 'overview') ? 'id="current-item"' : ''; ?>>
				<a href="<?php URL::out('admin', 'page='); ?>" title="Overview of your site">Admin</a>
				<ul class="menu-list">
					<li><a href="<?php URL::out('admin', 'page=options'); ?>">Options</a></li>
					<li><a href="<?php URL::out('admin', 'page=plugins'); ?>">Plugins</a></li>
					<li><a href="<?php URL::out('admin', 'page=themes'); ?>">Themes</a></li>
					<li><a href="<?php URL::out('admin', 'page=users'); ?>">Users</a></li>
					<li><a href="<?php URL::out('admin', 'page=import'); ?>">Import</a></li>
				</ul>
			</li>
			<li class="menu-item" <?php echo ($page == 'publish') ? 'id="current-item"' : ''; ?>>
				<a href="<?php URL::out('admin', 'page=publish'); ?>" title="Edit the content of your site">Publish</a>
				<ul class="menu-list">
					<li><a href="<?php URL::out('admin', 'page=publish&content_type=entry'); ?>">Post Entry</a></li>
					<li><a href="<?php URL::out('admin', 'page=publish&content_type=page'); ?>">Page</a></li>
				</ul>
			</li>
			<li class="menu-item" <?php echo ($page == 'options') ? 'id="current-item"' : ''; ?>>
				<a href="<?php URL::out('admin', 'page=options'); ?>" title="edit your site options">Manage</a>
				<ul class="menu-list">
					<li><a href="<?php URL::out('admin', 'page=content'); ?>">Content</a></li>
					<li><a href="<?php URL::out('admin', 'page=moderate'); ?>">Comments</a></li>
					<li><a href="<?php URL::out('admin', 'page=spam'); ?>">Spam</a></li>
				</ul>
			</li>
			<li class="menu-item" id="logout">
				<a href="<?php URL::out('user', 'page=logout'); ?>" title="logout of Habari">Logout</a>
			</li>
		</ol>
	</div>
