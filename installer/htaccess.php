<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="robots" content="noindex,nofollow" />
    <link href="system/installer/style.css" rel="stylesheet" type="text/css" />
    
	  <title>Install Habari</title>
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
	<p>Developer Review</p>
</div>
<div class="installstep ready">
	<h2>.htaccess<a href="#" class="help-me">(help)</a></h2>
	<div class="options">
		<div class="inputfield">
			Your <strong>.htaccess</strong> file is not writable.  In order to successfully install Habari, please paste the following into <strong><?php echo HABARI_PATH . '/.htaccess'; ?></strong>:<br />
			<textarea class="config"><?php echo $file_contents; ?></textarea>
			<div class="help">
				<strong>.htaccess</strong> is a file that tells your Apache web server
				to send requests to Habari for handling.
				Habari is not able to write this file to your server
				automatically, so you must create this file yourself to continute
				the installation.  <a onclick="this.target='_blank';" href="<?php Site::out_url( 'habari' ); ?>/manual/index.html#Installation">Learn More...</a>
			</div>
    </div>
	</div>
	<div class="bottom"></div>
</div>


<div class="next-section"></div>

<div class="installstep ready">
	<h2>Install</h2>
	<div class="options">
		
		<div class="inputfield submit">
			<div>When you have successfully placed the .htaccess file, run the installer again.</div>
			<input type="submit" name="submit" value="Restart Installer" />
		</div>
		
	</div>
		
	<div class="bottom"></div>
</div>
</form>
</div>
  </body>
</html>
