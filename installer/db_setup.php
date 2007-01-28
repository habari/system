<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="robots" content="noindex,nofollow" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="stylesheet" type="text/css" media="all" href="system/installer/style.css" />
    <title>Installing habari</title>
    <script type="text/javascript">// <![CDATA[
    
    var updateElements= function() {
    	var install_root= document.getElementById( 'install_root' ).checked;
    	var entire= document.getElementById( 'entire-db' );
    	
    	entire.style.display= ( install_root ? 'block' : 'none' );
    }
    
    var addInstallMethodListener= function() {
    	document.getElementById( 'install_root' ).addEventListener( 'click', updateElements, true );
    	document.getElementById( 'install_tables' ).addEventListener( 'click', updateElements, true );
    }
        
    window.addEventListener( 'load', updateElements, true );
    window.addEventListener( 'load', addInstallMethodListener, true );
	// ]]></script>
  </head>
  <body>
    <div id="container">
      <div id="header">
        <h1>Install <em>habari</em></h1>
      </div>
      <div id="page">
        <form action="" method="post" autocomplete="off">
          <h2>Installation Method</h2>
          <p class="instructions">
          You can either install the habari database yourself (if you have appropriate permissions to do so), or
          you can alternately install the habari tables into a database that has already been installed for you
          (for instance, if you are on a shared web host and do not have the ability to create databases yourself).  
          <em>Choose the method of installation</em>.
          </p>
          <div class="row">
            <label for="install_tables">Install Tables in Existing Database</label>
            <input type="radio" id="install_tables" name="install_method" value="noroot" checked="true" />
          </div>
          <div class="row">
            <label for="install_root">Install Entire Database</label>
            <input type="radio" id="install_root" name="install_method" value="root" />
          </div>
          <div id="entire-db">
          <h2>Install Entire Database</h2>
          <p class="instructions">
            In order to install the database properly, this installation script
            needs to be able to log in to your database server as a user who
            has permissions to create a new database and a new database user.
            Typically, this "super" user is called "root", which is what is entered
            in the field below by default.
          </p>
          <div class="row">
            <label for="db_root_user">Username with SUPER privileges</label>
            <input type="textbox" name="db_root_user" value="<?php echo isset($db_root_user) ? $db_root_user : 'root';?>" size="30" maxlength="50" />
            <?php $error_id= 'db_root_user'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="db_root_pass">Password for super user</label>
            <input type="password" name="db_root_pass" value="<?php echo isset($db_root_pass) ? $db_root_pass : '';?>" size="30" maxlength="50" />
            <?php $error_id= 'db_root_pass'; include "form.error.php";?>
          </div>
          </div>
          <h2>Database Information</h2>
            <?php $error_id= 'db_general'; include "form.error.php";?>
           <div class="row">
            <label for="db_type">Database Type</label>
            <select name="db_type">
             <option selected="true" value="mysql">MySQL</option>
             <option value="sqlite">SQLite</option>
             <option value="pgsql">PostgreSQL</option>
            </select>
            <?php $error_id= 'db_type'; include "form.error.php";?>
           </div>
           <div class="row">
            <label for="db_host">Host (Server)</label>
            <input type="textbox" name="db_host" value="<?php echo isset($db_host) ? $db_host : 'localhost';?>" size="30" maxlength="50" />
            <?php $error_id= 'db_host'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="db_user">Database User</label>
            <input type="textbox" name="db_user" value="<?php echo isset($db_user) ? $db_user : 'habari';?>" size="30" maxlength="50" />
            <?php $error_id= 'db_user'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="db_pass">Database Password</label>
            <input type="password" name="db_pass" value="<?php echo isset($db_pass) ? $db_pass : '';?>" size="30" maxlength="50" />
            <?php $error_id= 'db_pass'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="db_schema">Name of Database</label>
            <input type="textbox" name="db_schema" value="<?php echo isset($db_schema) ? $db_schema : 'habari';?>" size="30" maxlength="50" />
            <?php $error_id= 'db_schema'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="table_prefix" class="optional">Prefix for Tables</label>
            <input type="textbox" name="table_prefix" value="<?php echo isset($table_prefix) ? $table_prefix : '';?>" size="30" maxlength="50" />
            <?php $error_id= 'table_prefix'; include "form.error.php";?>
          </div>
          <h2>Admin User Information</h2>
          <div class="row">
            <label for="admin_username">Username of Administrator</label>
            <input type="textbox" name="admin_username" value="<?php echo isset($admin_username) ? $admin_username : 'Admin';?>" size="30" maxlength="50" />
            <?php $error_id= 'admin_username'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="admin_email">Email of Administrator</label>
            <input type="textbox" name="admin_email" value="<?php echo isset($admin_email) ? $admin_email : 'admin@mydomain.com';?>" size="30" maxlength="50" />
            <?php $error_id= 'admin_email'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="admin_pass">Password for Administrator</label>
            <input type="password" name="admin_pass" value="<?php echo isset($admin_pass) ? $admin_pass : '';?>" size="30" maxlength="50" />
            <?php $error_id= 'admin_pass'; include "form.error.php";?>
          </div>
          <h2>Blog Information</h2>
          <div class="row">
            <label for="blog_title">Blog Title</label>
            <input type="textbox" name="blog_title" value="<?php echo isset($blog_title) ? $blog_title : 'My Blog';?>" size="50" maxlength="150" />
            <?php $error_id= 'blog_title'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="blog_tagline">Blog Tagline</label>
            <input type="textbox" name="blog_tagline" value="<?php echo isset($blog_tagline) ? $blog_tagline : 'My Tagline';?>" size="30" maxlength="150" />
            <?php $error_id= 'blog_tagline'; include "form.error.php";?>
          </div>
          <div class="row">
            <label for="blog_about">About</label>
            <input type="textbox" name="blog_about" value="<?php echo isset($blog_about) ? $blog_about : 'About Me';?>" size="30" maxlength="150" />
            <?php $error_id= 'blog_about'; include "form.error.php";?>
          </div>
        
          <div style="clear: both;">
            <input type="submit" value="Install Habari" />
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
