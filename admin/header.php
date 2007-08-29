<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Habari Administration</title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php Site::out_url('admin_theme'); ?>/admin.css">
	<link rel="stylesheet" href="<?php Site::out_url('admin_theme'); ?>/css/screen.css" type="text/css" media="screen, projection">
	<link rel="stylesheet" href="<?php Site::out_url('admin_theme'); ?>/css/print.css" type="text/css" media="print">

	<!-- Show the grid and baseline
	<style type="text/css">
	.container { background: url(<?php Site::out_url('admin_theme'); ?>/css/lib/img/grid.png); } 
	</style>  
	-->
	
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
<div id="menubar">
	<div class="container">
		<div id="site-name">
			<a href="<?php Site::out_url('habari'); ?>" title="<?php Options::out('title'); ?>"><?php Options::out('title'); ?></a>
		</div>
		<ol id="menu">
			<?php
			$page = empty($page) ? 'admin' : $page;
			echo $mainmenu;
			?>
			<li class="menu-item" id="logout">
				<a href="<?php URL::out('user', 'page=logout'); ?>" title="logout of Habari">Logout</a>
			</li>
		</ol>
	</div>
</div>