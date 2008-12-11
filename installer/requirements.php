<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="robots" content="no index,no follow" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link href="<?php Site::out_url('habari'); ?>/system/installer/style.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/scripts/jquery.js"></script>
	  <title><?php _e('Install Habari'); ?></title>
  </head>
<body id="installer">
<?php include "locale_dropdown.php"; ?>
    <div id="container">
      <div id="header">
        <h1><?php _e('Before you install <em>Habari</em>...'); ?></h1>
      </div>
      <div id="page">
        <?php if (! $local_writable) {?>
          <h2><?php _e('Writable directory needed'); ?>...</h2>
          <?php if ($PHP_OS != 'WIN') {?>
            <p class="instructions">
              <?php _e('Before you can install Habari, you first need to make the install directory writable by php, so that the installation script can write your configuration information properly. The exact process of server and the ownership of the directory.'); ?>
            </p>
            <p>
              <?php _e('If your webserver is part of the group which owns the directory, you\'ll need to add group write permissions to the directory. The procedure for this is as follows:'); ?>
            </p>
            <ol>
              <li>
                <?php _e('Open a terminal window, and then change to the installation directory:'); ?>
                <pre><strong>$&gt;</strong> cd <?php echo $HABARI_PATH;?></pre>
              </li>
              <li>
              <?php _e('Change the <em>mode</em> (permissions) of the current directory:'); ?>
                <pre><strong>$&gt;</strong> chmod g+w .</pre><br />
                <pre><strong>$&gt;</strong> chmod g+x .</pre>
                <p class="note">
                  <?php _e('<em>Note</em>: You may need to use <strong>sudo</strong> and enter an administrator password if you do not own the directory.'); ?>
                </p>
              </li>
            </ol>
            <p>
              <?php _e('If the webserver is not part of the group which owns the directory, you will need to <strong>temporarily</strong> grant world write permissions to the directory:'); ?>
            </p>
            <ol>
            <li>
                <pre><strong>$&gt;</strong> chmod o+w .</pre><br />
                <pre><strong>$&gt;</strong> chmod o+x .</pre>
            </li>
            </ol>
            <p>
              <strong><?php _e('Be sure to remove the write permissions on the directory as soon as the installation is completed.'); ?></strong>
            </p>
          <?php } else {?>
            <strong>@todo Windows instructions</strong>
          <?php }?>
        <?php }?>
        <?php if (! $php_version_ok) {?>
          <h2><?php _e('PHP Upgrade needed...'); ?></h2>
          <p class="instructions">
            <em>Habari</em> <?php printf(_t('requires PHP 5.2 or newer. Your current PHP version is %s.'), PHP_VERSION); ?>
          </p>
          <strong>@todo Upgrading PHP instructions</strong>
        <?php }?>
        <?php if (!empty($missing_extensions)) {
	 	foreach ($missing_extensions as $ext_name => $ext_url) {
			$missing_ext_html[]= '<a href="' . $ext_url . '">' . $ext_name . '</a>';
		}
		$missing_ext_html = implode( ', ', $missing_ext_html );
		?>
          <h2><?php _e('Missing Extensions'); ?></h2>
          <p class="instructions">
            <em>Habari</em> <?php _e('requires that the following PHP extensions to be installed:'); ?> <?php echo $missing_ext_html; ?>. <?php _e('Please contact your web hosting provider if you do not have access to your server.'); ?>
          </p>
        <?php }?>
	<?php if ( ! $pdo_drivers_ok ) { ?>
	  <h2><?php _e('No PDO drivers enabled'); ?></h2>
	    <p class="instructions"><em>Habari</em> <?php _e('requires that at least one <a href="http://www.php.net/pdo">PDO driver</a> be installed. Please ask your hosting provider to enable one of the PDO drivers supported by Habari.'); ?></p>
	<?php } ?>
     </div>
    </div>
  </body>
</html>
