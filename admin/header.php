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
			<?php $page = empty($page) ? 'admin' : $page; ?>
			<?php
			
			?>
			<?php echo $mainmenu; ?>
			<li class="menu-item" id="logout">
				<a href="<?php URL::out('user', 'page=logout'); ?>" title="logout of Habari">Logout</a>
			</li>
		</ol>
	</div>
