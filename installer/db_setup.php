<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Install Habari</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex,nofollow">
	<link href="<?php Site::out_url('habari'); ?>/system/installer/style.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/scripts/jquery.js"></script>
	<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/system/installer/script.js"></script>
</head>

<body id="installer">

	<div id="wrapper">

		<div id="masthead">
			<h1>Habari</h1>
			<div id="footer">
				<p class="left"><a href="<?php Site::out_url( 'habari' ); ?>/doc/manual/index.html" onclick="popUp(this.href); return false;" title="Read the user manual">Manual</a> &middot;
					<a href="http://wiki.habariproject.org/" title="Read the Habari wiki">Wiki</a> &middot;
					<a href="http://groups.google.com/group/habari-users" title="Ask the community">Mailing List</a>
				</p>
			</div>
		</div>

		<form action="" method="post" id="installform">
		<div class="installstep ready" id="databasesetup">
			<h2>Database Setup</h2>
			<a href="#" class="help-me">Help</a>
			<div class="options">

				<div class="inputfield">
					<label for="db_type">Database Type <strong>*</strong></label>
					<?php echo Utils::html_select( 'db_type', $pdo_drivers, $db_type ); ?>
					<div class="help">
						<strong>Database Type</strong> specifies the type of database to which
						Habari will connect.  Changing this setting may affect the other fields
						that are available here.  If the database engine that you wanted to use
						is not in this list, you may need to install a PDO driver to enable it.  <a href="#">Learn More...</a>
					</div>
				</div>

				<h3 class="javascript-disabled">MySQL Settings</h3>
				<div class="javascript-disabled">Use the settings below only if you have selected MySQL as your database engine.</div>

				<div class="inputfield formysql">
					<label for="databasehost">Database Host <strong>*</strong></label>
					<input type="text" id="mysqldatabasehost" name="mysql_db_host" value="<?php echo $db_host; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database Host</strong> is the host (domain) name or server IP
						address of the server that runs the MySQL database to
						which Habari will connect.  If MySQL is running on your web server,
						and most of the time it is, "localhost" is usually a good value
						for this field.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield formysql">
					<label for="databaseuser">Username <strong>*</strong></label>
					<input type="text" id="mysqldatabaseuser" name="mysql_db_user" value="<?php echo $db_user; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database User</strong> is the username used to connect Habari
						to the MySQL database.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield formysql">
					<label for="databasepass">Password <strong>*</strong></label>
					<input type="password" id="mysqldatabasepass" name="mysql_db_pass" value="<?php /* echo $db_pass; */ ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database Password</strong> is the password used to connect
						the specified user to the MySQL database.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield formysql">
					<label for="databasetype">Database Name <strong>*</strong></label>
					<input type="text" id="mysqldatabasename" name="mysql_db_schema" value="<?php echo $db_schema; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database Name</strong> is the name of the MySQL database to
						which Habari will connect.  <a href="#">Learn More...</a>
					</div>
				</div>

				<h3 class="javascript-disabled">PostgreSQL Settings</h3>
				<div class="javascript-disabled">Use the settings below only if you have selected PostgreSQL as your database engine.</div>

				<div class="inputfield forpgsql">
					<label for="databasehost">Database Host <strong>*</strong></label>
					<input type="text" id="pgsqldatabasehost" name="pgsql_db_host" value="<?php echo $db_host; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database Host</strong> is the host (domain) name or server IP
						address of the server that runs the PostgreSQL database to
						which Habari will connect.  If PostgreSQL is running on your web server,
						and most of the time it is, "localhost" is usually a good value
						for this field.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield forpgsql">
					<label for="databaseuser">Username <strong>*</strong></label>
					<input type="text" id="pgsqldatabaseuser" name="pgsql_db_user" value="<?php echo $db_user; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database User</strong> is the username used to connect Habari
						to the PostgreSQL database.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield forpgsql">
					<label for="databasepass">Password <strong>*</strong></label>
					<input type="password" id="pgsqldatabasepass" name="pgsql_db_pass" value="<?php /* echo $db_pass; */ ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database Password</strong> is the password used to connect
						the specified user to the PostgreSQL database.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield forpgsql">
					<label for="databasetype">Database Name <strong>*</strong></label>
					<input type="text" id="pgsqldatabasename" name="pgsql_db_schema" value="<?php echo $db_schema; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Database Name</strong> is the name of the PostgreSQL database to
						which Habari will connect.  <a href="#">Learn More...</a>
					</div>
				</div>

				<h3 class="javascript-disabled">SQLite Settings <strong>*</strong></h3>
				<div class="javascript-disabled">Use the settings below only if you have selected SQLite as your database engine.</div>

				<div class="inputfield forsqlite">
					<label for="databasefile">Data file <strong>*</strong></label>
					<input type="text" id="databasefile" name="db_file" value="<?php echo $db_file; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Data file</strong> is the SQLite file that will store your Habari data.  This should be the complete path to where your data file resides. <a href="#">Learn More...</a>
					</div>
				</div>

			</div>

			<div class="advanced-options">

				<div class="inputfield">
					<label for="databasetype">Table Prefix</label>
					<input type="text" id="tableprefix" name="table_prefix" value="<?php echo $table_prefix; ?>" alt="">
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

		<div class="installstep ready" id="siteconfiguration">
			<h2>Site Configuration<a href="#" class="help-me">(help)</a></h2>
			<div class="options">

				<div class="inputfield">
					<label for="sitename">Site Name <strong>*</strong></label>
					<input type="text" id="sitename" name="blog_title" value="<?php echo $blog_title; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Site Name</strong> is the name of your site as it will appear
						to your visitors.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield">
					<label for="adminuser">Username <strong>*</strong></label>
					<input type="text" id="adminuser" name="admin_username" value="<?php echo $admin_username; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Username</strong> is the username of the initial user in Habari.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield">
					<label for="adminpass1">Password <strong>*</strong></label>
					<input type="password" id="adminpass1" name="admin_pass1" value="<?php echo $admin_pass1; ?>">
					<label for="adminpass2">Password (again) <strong>*</strong></label>
					<input type="password" id="adminpass2" name="admin_pass2" value="<?php echo $admin_pass2; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Password</strong> is the password of the initial user in Habari.  <a href="#">Learn More...</a>
					</div>
				</div>

				<div class="inputfield">
					<label for="adminemail">Admin Email <strong>*</strong></label>
					<input type="text" id="adminemail" name="admin_email" value="<?php echo $admin_email; ?>">
					<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="">
					<div class="warning"></div>
					<div class="help">
						<strong>Admin Email</strong> is the email address of the first user
						account.  <a href="#">Learn More...</a>
					</div>
				</div>

			</div>

			<div class="bottom"></div>
		</div>

		<div class="next-section"></div>

		<div class="installstep ready" id="install">
			<h2>Install</h2>
			<div class="options">

				<div class="inputfield submit">
					<p>Habari now has all of the information needed to install itself on your server.</p>
					<input type="submit" name="submit" value="Install Habari" id="submitinstall">
				</div>

			</div>

			<div class="bottom"></div>
		</div>
		</form>



	</div>

</body>
</html>
