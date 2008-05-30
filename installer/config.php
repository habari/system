<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="robots" content="noindex,nofollow" />
    <link href="system/installer/style.css" rel="stylesheet" type="text/css" />
    
	  <title><?php _e('Install Habari'); ?></title>
		<script type="text/javascript" src="/scripts/jquery.js"></script>
		<script type="text/javascript" src="/scripts/jquery.form.js"></script>
	<script type="text/javascript">
	
	$(document).ready(function() {
		$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
		$('.help').hide();
	});
	</script>
		
  </head>
  <body id="installer">

<div id="wrapper">

<form action="" method="get">

<div id="masthead">
	<h1>Habari</h1>
	<p><?php _e('Developer Review'); ?></p>
</div>
<div class="installstep ready">
	<h2>Config.php<a href="#" class="help-me">(<?php _e('help'); ?>)</a></h2>
	<div class="options">
		<div class="inputfield">
			<?php _e('Your <strong>config.php</strong> file is not writable. In order to successfully install Habari, please paste the following into'); ?> <strong><?php echo $config_file; ?></strong>:<br />
			<textarea class="config"><?php echo $file_contents; ?></textarea>
			<div class="help">
				<?php _e('<strong>config.php</strong> is a file that tells Habari how to connect
				to your database. Habari is not able to write this file to your server
				automatically, so you must create this file yourself to continute
				the installation.'); ?> <a href="#"><?php _e('Learn More...'); ?></a>
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
			<div><?php _e('When you have successfully placed the config file, run the installer again.'); ?></div>
			<input type="submit" name="submit" value="<?php _e('Restart Installer'); ?>" />
		</div>
		
	</div>
		
	<div class="bottom"></div>
</div>
</form>
</div>
  </body>
</html>
