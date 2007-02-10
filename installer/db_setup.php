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
	
	function setDatabaseType(el)
	{
		$('.forsqlite').hide();
		$('.formysql').hide();

		switch($(el).fieldValue()) {
		case 'mysql':
			$('.formysql').show();
			break;
		case 'sqlite':
			$('.forsqlite').show();
			break;
		}
	}
	
	function checkField() 
	{
		if ($(this).val() == '') {
			showwarning = false;
			fieldclass = 'normal';
		}
		else {
			showwarning = false;
			fieldclass = 'valid';

			// These checks should be done via an ajax call
			switch($(this).attr('id')) {
			case 'databasename':
				if($(this).val() != 'habari') {
					showwarning = true;
					warningtext = 'Habari could not find a database with that name on the server. <br />Please specify the name of an existing database. <a href="#" taborder="0">Learn More...</a>';
				}
				break;
			case 'databasehost':
				if($(this).val() != 'localhost') {
					showwarning = true;
					warningtext = 'Habari could not find a MySQL server at the specified address. <br />Please provide a correct host name or address. <a href="#" taborder="0">Learn More...</a>';
				}
			}
			fieldclass = showwarning ? 'invalid' : 'valid';
		}


		if(showwarning == false) {
			$(this).parents('.inputfield').find('.warning:visible').fadeOut();
		}
		if(showwarning == true) {
			$(this).parents('.inputfield').find('.warning:hidden').html(warningtext).fadeIn();
		}
		$(this).parents('.inputfield').removeClass('invalid').removeClass('valid').addClass(fieldclass);
	}
	
	$(document).ready(function() {
		$('.help-me').click(function(){$(this).parents('.installstep').find('.help').slideToggle();return false;})
		$('.help').hide();
		//$('.ready').removeClass('ready');
		$('.installstep:first').addClass('ready');

		$('#databasehost').blur(checkField);
		$('#databasename').blur(checkField);
		
	});
	</script>
  </head>
  <body id="installer">

<div id="wrapper">

<div id="masthead">
	<h1>Habari</h1>
	<p>Developer Review</p>
</div>
<form action="" method="post" autocomplete="off">
<div class="installstep ready">
	<h2>Database Setup<a href="#" class="help-me">(help)</a></h2>
	<div class="options">
		<div class="inputfield">
			<label for="databasetype">Database Type</label>
			<select id="databasetype" name="db_type" onchange="setDatabaseType(this)">
				<option value="mysql">MySQL</option>
				<option value="sqlite">SQLite</option>
				<!--  Not supported yet:  <option value="mysql">Postgres</option>  -->
			</select>
			<div class="help">
				<strong>Database Type</strong> specifies the type of database to which 
				Habari will connect.  Changing this setting may affect the other fields
				that are available here. <a href="#">Learn More...</a>
			</div>
		</div>
		
		<div class="inputfield formysql">
			<label for="databasehost">Database Host</label>
			<input type="text" id="databasehost" name="db_host" value="<?php echo $db_host; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Database Host</strong> is the host (domain) name or server IP
				address of the server that runs the MySQL database to 
				which Habari will connect.  If MySQL is running on your web server,
				and most of the time it is, "localhost" is usually a good value
				for this field.  <a href="#">Learn More...</a>
			</div>
		</div>

		<div class="inputfield forsqlite">
			<label for="databasehost">Data file</label>
			<input type="text" id="databasehost" name="db_file" value="<?php echo $db_host; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Data file</strong> is the SQLite file that will store your Habari data.  This should be the complete path to where your data file resides. <a href="#">Learn More...</a>
			</div>
		</div>
		
		<div class="inputfield formysql">
			<label for="databaseuser">Username</label>
			<input type="text" id="databaseuser" name="db_user" value="<?php echo $db_user; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Database User</strong> is the username used to connect Habari 
				to the MySQL database.  <a href="#">Learn More...</a>
			</div>
		</div>
		
		<div class="inputfield formysql">
			<label for="databasepass">Password</label>
			<input type="password" id="databasepass" name="db_pass" value="<?php echo $db_pass; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Database Password</strong> is the password used to connect
				the specified user to the MySQL database.  <a href="#">Learn More...</a>
			</div>
		</div>
		
		<div class="inputfield formysql">
			<label for="databasetype">Database Name</label>
			<input type="text" id="databasename" name="db_schema" value="<?php echo $db_schema; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Database Name</strong> is the name of the MySQL database to 
				which Habari will connect.  <a href="#">Learn More...</a>
			</div>
		</div>
		
	</div>
	<div class="advanced-options">

		<div class="inputfield">
			<label for="databasetype">Table Prefix</label>
			<input type="text" id="tableprefix" name="table_prefix" value="<?php echo $table_prefix; ?>" />
			<div class="warning"></div>
			<div class="help">
				<strong>Table Prefix</strong> is a prefix that will be appended to
				each table that Habari creates in the database, making it easy to
				distinguish thode tables in the database from those of other 
				installations.  <a href="#">Learn More...</a>
			</div>
		</div>

	</div>
	<div class="bottom"></div>
</div>

<div class="next-section"></div>

<div class="installstep ready">
	<h2>Site Configuration<a href="#" class="help-me">(help)</a></h2>
	<div class="options">
		
		<div class="inputfield">
			<label for="sitename">Site Name</label>
			<input type="text" id="sitename" name="blog_title" value="<?php echo $blog_title; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Site Name</strong> is the name of your site as it will appear
				to your visitors.  <a href="#">Learn More...</a>
			</div>
		</div>
		
		<div class="inputfield">
			<label for="adminuser">Username</label>
			<input type="text" id="adminuser" name="admin_username" value="<?php echo $admin_username; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Username</strong> is the username of the initial user in Habari.  <a href="#">Learn More...</a>
			</div>
		</div>
		
		<div class="inputfield">
			<label for="adminpass">Password</label>
			<input type="password" id="adminpass" name="admin_pass" value="<?php echo $admin_pass; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Password</strong> is the password of the initial user in Habari.  <a href="#">Learn More...</a>
			</div>
		</div>
		
		<div class="inputfield">
			<label for="adminemail">Admin Email</label>
			<input type="text" id="adminemail" name="admin_email" value="<?php echo $admin_email; ?>" />
			<img class="status" src="/system/installer/images/ready.png" />
			<div class="warning"></div>
			<div class="help">
				<strong>Admin Email</strong> is the email address of the first user
				account.  <a href="#">Learn More...</a>
			</div>
		</div>
		
	</div>
	<div class="advanced-options">

	</div>
	<div class="bottom"></div>
</div>

<div class="next-section"></div>

<div class="installstep ready">
	<h2>Install</h2>
	<div class="options">
		
		<div class="inputfield submit">
			<div>Habari now has all of the information needed to install itself on your server.</div>
			<input type="submit" name="submit" value="Install Habari" />
		</div>
		
	</div>
		
	<div class="bottom"></div>
</div>
</form>
</div>

  </body>
</html>
