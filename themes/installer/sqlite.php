<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="robots" content="noindex,nofollow" />
    <link href="<?php Site::out_url('habari'); ?>/system/installer/style.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/scripts/jquery.js"></script>
    
	<title><?php _e('Install Habari'); ?></title>
	<script type="text/javascript">
	
	$(document).ready(function() {
		$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
		$('.help').hide();
	});
	</script>
		
  </head>
  <body id="installer">
  <?php include "locale_dropdown.php"; ?>

<div id="wrapper">

<form action="" method="post">
<input type="hidden" name="locale" value="<?php echo htmlspecialchars($locale); ?>">

<div id="masthead">
	<h1>Habari</h1>
	<p><?php _e('Developer Review'); ?></p>
</div>
<div class="installstep ready">
	<h2>.htaccess<a href="#" class="help-me">(<?php _e('help'); ?>)</a></h2>
	<div class="options">
		<div class="inputfield">
			<?php printf(_t('Your <b>.htaccess</b> file is not writable. In order to secure your SQLite database, please paste the following into <b>%s</b>:'), HABARI_PATH . '/.htaccess'); ?><br />
			<textarea class="config"><?php echo $sqlite_contents; ?></textarea>
			<div class="help">
				<?php _e('Your SQLite database is a file on your server like
				any other file. You can enhance it\'s security by including a section
				in your .htaccess file that disallows access to it by readers on the web.
				Habari is not able to write this section in your .htaccess file
				automatically, so you must add this section yourself to gain the enhanced
				security it offers you.'); ?>  <a onclick="this.target='_blank';" href="<?php Site::out_url( 'habari' ); ?>/manual/index.html#Installation"><?php _e('Learn More...'); ?></a>
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
			<div><?php _e('When you have successfully placed the .htaccess file, run the installer again.'); ?></div>
			<input type="submit" name="submit" value="<?php _e('Restart Installer'); ?>" />
		</div>
		
	</div>
		
	<div class="bottom"></div>
</div>
</form>
</div>
  </body>
</html>
