<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Habari Administration</title>

	<link rel="stylesheet" href="<?php Site::out_url('habari'); ?>/3rdparty/blueprint/screen.css" type="text/css" media="screen, projection">
	<link rel="stylesheet" href="<?php Site::out_url('habari'); ?>/3rdparty/blueprint/print.css" type="text/css" media="print">
	<link href="<?php Site::out_url( 'habari' ); ?>/system/installer/style.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" media="screen" href="<?php Site::out_url('admin_theme'); ?>/css/admin.css">
	

	<script src="<?php Site::out_url('scripts'); ?>/jquery.js" type="text/javascript"></script>
	<script src="<?php Site::out_url('admin_theme'); ?>/admin.js" type="text/javascript"></script>
	<?php
		Plugins::act( 'admin_header', $this );
		Stack::out( 'admin_stylesheet', '<link rel="stylesheet" type="text/css" href="%s" media="%s">'."\r\n" );
		Stack::out( 'admin_header_javascript', '<script src="%s" type="text/javascript"></script>'."\r\n" );
	?>
		<style type="text/css">
		body {
			text-align: center;
			background: #f1f1f1;
			font-family: Verdana;
		}
		
		#databasesetup {
			margin:0 auto;
		}

		.submit
		{
			width: auto !important;
			position: relative;
		}
		.bottom {
			margin-bottom: 50px;
		}

		</style>
</head>
<ul id="menu">
	<li id="site-name">
		<a href="<?php Site::out_url('habari'); ?>" title="<?php Options::out('title'); ?>"><?php Options::out('title'); ?></a>
	</li>
	
</ul>

	<div id="wrapper">
		<div id="masthead">
			<h1>Habari</h1>
		</div>
		<form method="post" action="<?php URL::out( 'user', array( 'page' => 'login' ) ); ?>">
		<div class="installstep ready done" id="databasesetup">
			<h2>Login to <?php Options::out( 'title' ); ?></h2>
			<div class="options">
				<div class="inputfield">
					<p>
						<label for="habari_username">Name:</label>
						<input type="text" size="25" name="habari_username" id="habari_username">
					</p>
					<p>
						<label for="habari_password">Password:</label>
						<input type="password" size="25" name="habari_password" id="habari_password">
					</p>
					<p>
						<input class="submit" type="submit" value="GO!">
					</p>
				</div>
			</div>			
			<div class="bottom"></div>
		</div>
	

		<div id="footer">
			<p class="left"><a href="<?php Site::out_url( 'habari' ); ?>/manual/index.html" onclick="popUp(this.href); return false;" title="Read the user manual">Manual</a> - 
				<a href="http://wiki.habariproject.org/" title="Read the Habari wiki">Wiki</a> - 
				<a href="http://groups.google.com/group/habari-users" title="Ask the community">Mailing List</a>
			</p>

			
		</div>

		<?php
			Plugins::act( 'admin_footer', $this );
			Stack::out( 'admin_footer_javascript', ' <script src="%s" type="text/javascript"></script>'."\r\n" );
			include ('db_profiling.php');
		?>
		</div>
		</body>
		</html>