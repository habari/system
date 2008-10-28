<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title><?php _e('Install Habari'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex,nofollow">
	<link href="<?php Site::out_url('habari'); ?>/system/installer/style.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/scripts/jquery.js"></script>
	<script type="text/javascript">
	
	$(document).ready(function() {
		$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
		$('.help').hide();
	});
	</script>
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

		<?php include "locale_dropdown.php"; ?>

		<form action="" method="post">
		<input type="hidden" name="locale" value="<?php echo htmlspecialchars($locale); ?>">

		<div class="installstep ready">
			<h2>.htaccess<a href="#" class="help-me">(<?php _e('help'); ?>)</a></h2>
			<div class="options">
				<div class="inputfield">
					<?php _e('Your <strong>.htaccess</strong> file is not writable. In order to successfully install Habari, please paste the following into'); ?> <strong><?php echo HABARI_PATH . '/.htaccess'; ?></strong>:<br>
					<textarea class="config" tabindex="<?php echo $tab++ ?>"><?php echo $file_contents; ?></textarea>
					<div class="help">
						<strong>.htaccess</strong> <?php _e('is a file that tells your Apache web server
						to send requests to Habari for handling.
						Habari is not able to write this file to your server
						automatically, so you must create this file yourself to continue
						the installation.'); ?>  <a onclick="this.target='_blank';" href="<?php Site::out_url( 'habari' ); ?>/manual/index.html#Installation"><?php _e('Learn More...'); ?></a>
					</div>
			    </div>
			</div>
			
			<div class="bottom"></div>
		</div>

		<div class="next-section"></div>

		<div class="installstep ready">
			<h2><?php _e('Install'); ?></h2>
			<div class="options">

				<div class="inputfield submit">
					<p><?php _e('When you have successfully placed the .htaccess file, run the installer again.'); ?></p>
					<input type="submit" name="submit" value="<?php _e('Restart Installer'); ?>" tabindex="<?php echo $tab++ ?>">
				</div>

			</div>

			<div class="bottom"></div>
		</div>
		</form>
		


	</div>

</body>
</html>
