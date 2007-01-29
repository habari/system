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
        <h1>config.php</h1>
      </div>
      <div id="page">
	Your <strong>config.php</strong> file is not writable.  In order to successfully install Habari, please paste the following into <strong><?php echo $config_file; ?></strong>:<br />
	<pre>
	<?php echo $file_contents; ?>
	</pre>
     </div>
    </div>
  </body>
</html>
