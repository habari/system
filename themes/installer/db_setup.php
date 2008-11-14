<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title><?php _e('Install Habari'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex,nofollow">
	<link href="<?php Site::out_url('habari'); ?>/system/themes/installer/style.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/scripts/jquery.js"></script>
	<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/system/themes/installer/script.js"></script>
	<?php Stack::out( 'installer_javascript', '<script type="text/javascript" src="%s"></script>'."\r\n" ) ?>
</head>

<body id="installer">
	
<?php $tab = 1; ?>

	<div id="wrapper">

		<div id="masthead">
			<h1>Habari</h1>
			<div id="footer">
				<p class="left"><a href="<?php Site::out_url( 'habari' ); ?>/doc/manual/index.html" onclick="popUp(this.href); return false;" title="<?php _e('Read the user manual'); ?>" tabindex="<?php echo $tab++ ?>"><?php _e('Manual'); ?></a> &middot;
					<a href="http://wiki.habariproject.org/" title="<?php _e('Read the Habari wiki'); ?>" tabindex="<?php echo $tab++ ?>">Wiki</a> &middot;
					<a href="http://groups.google.com/group/habari-users" title="<?php _e('Ask the community'); ?>" tabindex="<?php echo $tab++ ?>"><?php _e('Mailing List'); ?></a>
				</p>
			</div>
		</div>

		<?php $theme->form_locales()->out() ?>

		<?php $theme->form_installform()->out() ?>

	</div>

</body>
</html>
