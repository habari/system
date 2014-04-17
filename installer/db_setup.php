<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php include( 'header.php' ); ?>
<?php if ( !isset( $form_errors ) ) $form_errors = array(); ?>
<?php function elem_if_set($a, $k) { if (isset($a[$k])) { return $a[$k]; } } ?>

<form action="" method="post" id="installform">
<input type="hidden" name="locale" value="<?php echo Utils::htmlspecialchars($locale); ?>" />

<div class="installstep ready" id="databasesetup">
	<h2><?php _e('Database Setup'); ?></h2>
	<a href="#" class="help-me"><?php _e('Help'); ?></a>
	<div class="options">

		<div class="inputfield">
			<label for="db_type"><?php _e('Database Type'); ?> <strong>*</strong></label>
			<select id="db_type" name="db_type" tabindex="<?php echo $tab++; ?>">
			<?php
				foreach($pdo_drivers as $value => $text){
					echo '<option value="'.$value.'"';
					if ($db_type == (string)$value) {
						echo ' selected="selected"';
					}
					echo '>' . $text . "</option>\n";
				}
				foreach($pdo_missing_drivers as $value => $text){
					echo '<optgroup label="' . _t('%1$s - missing PDO driver, see help', array($text)) . '"></optgroup>';
				}
			?>
			</select>
			<div class="help">
				<?php _e('<strong>Database Type</strong> specifies the type of database to which Habari will connect.  Changing this setting may affect the other fields that are available here.  If the database engine that you wanted to use is not in this list, you may need to install a PDO driver to enable it.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<h3 class="javascript-disabled"><?php _e('MySQL Settings'); ?></h3>
		<div class="javascript-disabled"><?php _e('Use the settings below only if you have selected MySQL as your database engine.'); ?></div>

		<div class="inputfield formysql">
			<label for="mysqldatabasehost"><?php _e('Database Host'); ?> <strong>*</strong></label>
			<input type="text" id="mysqldatabasehost" name="mysql_db_host" value="<?php echo $db_host; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'mysql_db_host'); ?></div>
			<div class="help">
				<?php _e('<strong>Database Host</strong> is the host (domain) name or server IP address of the server that runs the MySQL database to which Habari will connect.  If MySQL is running on your web server, and most of the time it is, "localhost" is usually a good value for this field.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield formysql">
			<label for="mysqldatabaseuser"><?php _e('Username'); ?> <strong>*</strong></label>
			<input type="text" id="mysqldatabaseuser" name="mysql_db_user" value="<?php echo $db_user; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'mysql_db_user'); ?></div>
			<div class="help">
				<?php _e('<strong>Database User</strong> is the username used to connect Habari to the MySQL database.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield formysql">
			<label for="mysqldatabasepass"><?php _e('Password'); ?> <strong>*</strong></label>
			<input type="password" id="mysqldatabasepass" name="mysql_db_pass" value="<?php /* echo $db_pass; */ ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'mysql_db_pass'); ?></div>
			<div class="help">
				<?php _e('<strong>Database Password</strong> is the password used to connect the specified user to the MySQL database.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield formysql">
			<label for="mysqldatabasename"><?php _e('Database Name'); ?> <strong>*</strong></label>
			<input type="text" id="mysqldatabasename" name="mysql_db_schema" value="<?php echo $db_schema; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'mysql_db_schema'); ?></div>
			<div class="help">
				<?php _e('<strong>Database Name</strong> is the name of the MySQL database to which Habari will connect.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<h3 class="javascript-disabled"><?php _e('PostgreSQL Settings'); ?></h3>
		<div class="javascript-disabled"><?php _e('Use the settings below only if you have selected PostgreSQL as your database engine.'); ?></div>

		<div class="inputfield forpgsql">
			<label for="pgsqldatabasehost"><?php _e('Database Host'); ?> <strong>*</strong></label>
			<input type="text" id="pgsqldatabasehost" name="pgsql_db_host" value="<?php echo $db_host; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'pgsql_db_host'); ?></div>
			<div class="help">
				<?php _e('<strong>Database Host</strong> is the host (domain) name or server IP address of the server that runs the PostgreSQL database to which Habari will connect.  If PostgreSQL is running on your web server, and most of the time it is, "localhost" is usually a good value for this field.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield forpgsql">
			<label for="pgsqldatabaseuser"><?php _e('Username'); ?> <strong>*</strong></label>
			<input type="text" id="pgsqldatabaseuser" name="pgsql_db_user" value="<?php echo $db_user; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'pgsql_db_user'); ?></div>
			<div class="help">
				<?php _e('<strong>Database User</strong> is the username used to connect Habari to the PostgreSQL database.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield forpgsql">
			<label for="pgsqldatabasepass"><?php _e('Password'); ?> <strong>*</strong></label>
			<input type="password" id="pgsqldatabasepass" name="pgsql_db_pass" value="<?php /* echo $db_pass; */ ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'pgsql_db_pass'); ?></div>
			<div class="help">
				<?php _e('<strong>Database Password</strong> is the password used to connect the specified user to the PostgreSQL database.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield forpgsql">
			<label for="pgsqldatabasename"><?php _e('Database Name'); ?> <strong>*</strong></label>
			<input type="text" id="pgsqldatabasename" name="pgsql_db_schema" value="<?php echo $db_schema; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'pgsql_db_schema'); ?></div>
			<div class="help">
				<?php _e('<strong>Database Name</strong> is the name of the PostgreSQL database to which Habari will connect.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<h3 class="javascript-disabled"><?php _e('SQLite Settings'); ?> <strong>*</strong></h3>
		<div class="javascript-disabled"><?php _e('Use the settings below only if you have selected SQLite as your database engine.'); ?></div>

		<div class="inputfield forsqlite">
			<label for="databasefile"><?php _e('Data file'); ?> <strong>*</strong></label>
			<input type="text" id="databasefile" name="db_file" value="<?php echo $db_file; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'db_file'); ?></div>
			<div class="help">
				<?php _e('<strong>Data file</strong> is the SQLite file that will store your Habari data.  This should be the path to where your data file resides, relative to the Habari user directory.'); ?> <a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>
	</div>

	<div class="advanced-options">
		<div class="inputfield">
			<label for="tableprefix"><?php _e('Table Prefix'); ?></label>
			<input type="text" id="tableprefix" name="table_prefix" value="<?php echo $table_prefix; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'table_prefix'); ?></div>
			<div class="help">
				<?php _e('<strong>Table Prefix</strong> is a prefix that will be appended to each table that Habari creates in the database, making it easy to distinguish those tables in the database from those of other installations.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>
		<div class="inputfield">
			<button type="button" name="check_db_connection" id="check_db_connection" tabindex="<?php echo $tab++ ?>"><?php _e('Check Database Connection'); ?></button>
		</div>
	</div>
	<div class="bottom"></div>
</div>

<div class="next-section"></div>

<div class="installstep ready" id="siteconfiguration">
	<h2><?php _e('Site Configuration'); ?></h2>
	<a href="#" class="help-me"><?php _e('Help'); ?></a>
	<div class="options">

		<div class="inputfield">
			<label for="sitename"><?php _e('Site Name'); ?> <strong>*</strong></label>
			<input type="text" id="sitename" name="blog_title" value="<?php echo $blog_title; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'blog_title'); ?></div>
			<div class="help">
				<?php _e('<strong>Site Name</strong> is the name of your site as it will appear to your visitors.'); ?>  <a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield">
			<label for="adminuser"><?php _e('Username'); ?> <strong>*</strong></label>
			<input type="text" id="adminuser" name="admin_username" value="<?php echo $admin_username; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'admin_username'); ?></div>
			<div class="help">
				<?php _e('<strong>Username</strong> is the username of the initial user in Habari.'); ?>  <a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield">
			<label for="adminpass1"><?php _e('Password'); ?> <strong>*</strong></label>
			<input type="password" id="adminpass1" name="admin_pass1" value="<?php echo $admin_pass1; ?>" tabindex="<?php echo $tab++ ?>" />
			<label for="adminpass2"><?php _e('Password (again)'); ?> <strong>*</strong></label>
			<input type="password" id="adminpass2" name="admin_pass2" value="<?php echo $admin_pass2; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'admin_pass'); ?></div>
			<div class="help">
				<?php _e('<strong>Password</strong> is the password of the initial user in Habari. Both password fields must match.'); ?>  <a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

		<div class="inputfield">
			<label for="adminemail"><?php _e('Admin Email'); ?> <strong>*</strong></label>
			<input type="text" id="adminemail" name="admin_email" value="<?php echo $admin_email; ?>" tabindex="<?php echo $tab++ ?>" />
			<img class="status" src="<?php Site::out_url( 'habari' ); ?>/system/installer/images/ready.png" alt="" />
			<div class="warning"><?php echo elem_if_set( $form_errors, 'admin_email'); ?></div>
			<div class="help">
				<?php _e('<strong>Admin Email</strong> is the email address of the first user account.'); ?>
				<a href="#"><?php _e('Learn More&hellip;'); ?></a>
			</div>
		</div>

	</div>
	<div class="bottom"></div>
</div>

<div class="next-section"></div>

<div class="installstep ready" id="themeselection">
	<h2><?php _e('Theme Selection'); ?></h2>
	<a href="#" class="help-items"><?php _e('Help'); ?></a>
	<div class="options items">
		<?php foreach($themes as $key => $theme) { ?>
			<div class="item clear clearfix">
				<div class="head">
					<span class="checkbox">
						<input type="radio" name="theme_dir" value="<?php echo $theme['dir']; ?>"
							id="theme_<?php echo $theme['dir']; ?>" tabindex="<?php echo $tab++ ?>"
							class="theme_selection"
							data-requires="<?php echo isset($theme['requires']) ? (string)InstallHandler::get_feature_list($theme['requires']) : ''; ?>"
							data-provides="<?php echo isset($theme['provides']) ? (string)InstallHandler::get_feature_list($theme['provides']) : ''; ?>"
							data-conflicts="<?php echo isset($theme['conflicts']) ? (string)InstallHandler::get_feature_list($theme['conflicts']) : ''; ?>"
							/>
					</span>
					<label for="theme_<?php echo $theme['dir']; ?>" class="name"><?php echo $theme['info']->name; ?> <span class="version"><?php echo $theme['info']->version; ?></span></label>
					<label for="theme_<?php echo $theme['dir']; ?>" class="image"><img src="<?php echo $theme['screenshot']; ?>" width="150px"></label>
				</div>
				<div class="item-help"><?php echo $theme['info']->description; ?></div>
			</div>
		<?php } ?>
	</div>
	<div class="bottom"></div>
</div>

<div class="next-section"></div>

<div class="installstep ready" id="pluginactivation">
	<h2><?php _e('Plugin Activation'); ?></h2>
	<a href="#" class="help-me"><?php _e('Help'); ?></a>
	<div class="options items">
		<?php foreach($plugins as $plugin) { ?>
		<?php if ( !isset($plugin['info']) ) { ?>

			<div class="item clear">
				<div class="head">
					<p><?php printf( _t('The plugin file %s is a legacy plugin, and does not include an XML info file.'), $plugin['file'] ); ?></p>
				</div>
			</div>

			<?php } else { ?>
			<div class="item clear">
				<div class="head">
					<span class="checkbox"><input
						type="checkbox"
						name="plugin_<?php echo $plugin['plugin_id']; ?>"
						id="plugin_<?php echo $plugin['plugin_id']; ?>"
						<?php if ($plugin['recommended']) echo ' checked="checked"'; ?>
						tabindex="<?php echo $tab++ ?>"
						class="plugin_selection"
						data-requires="<?php echo $plugin['requires']; ?>"
						data-provides="<?php echo $plugin['provides']; ?>"
						data-conflicts="<?php echo $plugin['conflicts']; ?>"
						/></span><label for="plugin_<?php echo $plugin['plugin_id']; ?>"><span class="name"><?php echo $plugin['info']->name; ?></span> <span class="version"><?php echo $plugin['info']->version; ?></span><span class="feature_note"></span></label>
				</div>
				<div class="help"><?php echo $plugin['info']->description; ?></div>
			</div>
			<?php } ?>
		<?php } ?>
		<div class="controls item">
			<span class="checkbox"><input type="checkbox" name="checkbox_controller" id="checkbox_controller" tabindex="<?php echo $tab++ ?>" /></span>
			<label for="checkbox_controller">None Selected</label>
		</div>
	</div>
	<div class="bottom"></div>
</div>

<div class="next-section"></div>

<div class="installstep ready" id="install">
	<h2><?php _e('Install'); ?></h2>
	<div class="options">
		<div class="inputfield submit">
			<p><?php _e('Habari now has all of the information needed to install itself on your server.'); ?></p>
			<p id="feature_error"><?php _e('The selected theme and plugins require additional features:'); ?> <span id="unfulfilled_feature_list"></span></p>
			<input type="submit" name="submit" value="<?php _e('Install Habari'); ?>" id="submitinstall" tabindex="<?php echo $tab++ ?>" />
		</div>
	</div>
	<div class="bottom"></div>
</div>

</form>

<script type="text/javascript" src="<?php Site::out_url('habari'); ?>/system/installer/script.js"></script>

<?php include( 'footer.php' ); ?>
