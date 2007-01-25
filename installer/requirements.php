<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="robots" content="no index,no follow" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="stylesheet" type="text/css" media="all" href="system/installer/style.css" />
  </head>
  <body>
    <div id="container">
      <div id="header">
        <h1>Before you install <em>habari</em>...</h1>
      </div>
      <div id="page">
        <?php if (! $local_writeable) {?>
          <h2>Writeable directory needed...</h2>
          <?php if ($PHP_OS != 'WIN') {?>
            <p class="instructions">
              Before you can install habari, you first need to make the install directory
              "writeable", so that the installation script can write your configuration 
              information properly. This is a simple process, outlined in the steps below.
            </p>
            <ol>
              <li>
                Open a terminal window, and then change to the installation directory:
                <pre><strong>$></strong> cd <?php echo $HABARI_PATH;?></pre>
              </li>
              <li>
              Change the <em>mode</em> (permissions) of the current directory:
                <pre><strong>$></strong> chmod g+w .</pre>
                <p class="note">
                  <em>Note</em>: You may need to use <strong>sudo</strong> and enter
                  an administrator password on certain distributions.
                </p>
              </li>
            </ol>
          <?php } else {?>
            <strong>@todo Windows instructions</strong>
          <?php }?>
        <?php }?>
        <?php if (! $php_version_ok) {?>
          <h2>PHP Upgrade needed...</h2>
          <p class="instructions">
            <em>habari</em> requires PHP 5.1 or newer.  Your current PHP version is <?php echo $PHP_VERSION;?>.
          </p>
          <strong>@todo Upgrading PHP instructions</strong>
        <?php }?>
        <?php if (! $pdo_extension_ok) {?>
          <h2>PDO extension needed...</h2>
          <p class="instructions">
            <em>habari</em> requires that the PDO PHP extension  be installed.
          </p>
          <strong>@todo Installing PDO instructions</strong>
        <?php }?>
     </div>
    </div>
  </body>
</html>
