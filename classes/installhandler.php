<?php
/**
 * @package Habari
 *
 */

define( 'MIN_PHP_VERSION', '5.2.0' );

/**
 * The class which responds to installer actions
 */
class InstallHandler extends ActionHandler
{

	/**
	 * Entry point for installation.  The reason there is a begin_install
	 * method to handle is that conceivably, the user can stop installation
	 * mid-install and need an alternate entry point action at a later time.
	 */
	public function act_begin_install()
	{
		// Create a new theme to handle the display of the installer
		$this->theme = Themes::create( 'installer', 'RawPHPEngine', HABARI_PATH . '/system/installer/' );

		/**
		 * Set user selected Locale or default
		 */
		$this->theme->locales = HabariLocale::list_all();
		if ( isset( $_POST['locale'] ) && $_POST['locale'] != null ) {
			HabariLocale::set( $_POST['locale'] );
			$this->theme->locale = $_POST['locale'];
			$this->handler_vars['locale'] = $_POST['locale'];
		}
		else {
			HabariLocale::set( 'en-us' );
			$this->theme->locale = 'en-us';
			$this->handler_vars['locale'] = 'en-us';
		}

		/*
		 * Check .htaccess first because ajax doesn't work without it.
		*/
		if ( ! $this->check_htaccess() ) {
			$this->handler_vars['file_contents'] = htmlentities( implode( "\n", $this->htaccess() ) );
			$this->display( 'htaccess' );
		}

		// Dispatch AJAX requests.
		if ( isset( $_POST['ajax_action'] ) ) {
			switch ( $_POST['ajax_action'] ) {
				case 'check_mysql_credentials':
					self::ajax_check_mysql_credentials();
					exit;
					break;
				case 'check_pgsql_credentials':
					self::ajax_check_pgsql_credentials();
					exit;
					break;
				case 'check_sqlite_credentials':
					self::ajax_check_sqlite_credentials();
					exit;
					break;
			}
		}
		// set the default values now, which will be overriden as we go
		$this->form_defaults();

		if ( ! $this->meets_all_requirements() ) {
			$this->display( 'requirements' );
		}

		/*
		 * Add the AJAX hooks
		 */
		Plugins::register( array( 'InstallHandler', 'ajax_check_mysql_credentials' ), 'ajax_', 'check_mysql_credentials' );
		Plugins::register( array( 'InstallHandler', 'ajax_check_pgsql_credentials' ), 'ajax_', 'check_pgsql_credentials' );

		/*
		 * Let's check the config.php file if no POST data was submitted
		 */
		if ( ( ! file_exists( Site::get_dir( 'config_file' ) ) ) && ( ! isset( $_POST['admin_username'] ) ) ) {
			// no config file, and no HTTP POST
			$this->display( 'db_setup' );
		}

		// try to load any values that might be defined in config.php
		if ( file_exists( Site::get_dir( 'config_file' ) ) ) {
			include( Site::get_dir( 'config_file' ) );

			// check for old style config (global variable, pre-dates registry based config
			if ( !Config::exists( 'db_connection' ) && isset( $db_connection ) ) {
				// found old style config...

				// set up registry:
				Config::set( 'db_connection', $db_connection );

				// assign handler vars (for config file write)
				$this->set_handler_vars_from_db_connection();

				// write new config file
				if ( $this->write_config_file( true ) ) {
					// successful, so redirect:
					Utils::redirect( Site::get_url( 'habari' ) );
				}
			}

			if ( Config::exists( 'db_connection' ) ) {
				$this->set_handler_vars_from_db_connection();
			}
			// if a $blog_data array exists in config.php, use it
			// to pre-load values for the installer
			// ** this is completely optional **
			if ( isset( $blog_data ) ) {
				foreach ( $blog_data as $blog_datum => $value ) {
					$this->handler_vars[$blog_datum] = $value;
				}
			}
		}

		// now merge in any HTTP POST values that might have been sent
		// these will override the defaults and the config.php values
		$this->handler_vars = $this->handler_vars->merge( $_POST );

		// we need details for the admin user to install
		if ( ( '' == $this->handler_vars['admin_username'] )
			|| ( '' == $this->handler_vars['admin_pass1'] )
			|| ( '' == $this->handler_vars['admin_pass2'] )
			|| ( '' == $this->handler_vars['admin_email'] )
		) {
			// if none of the above are set, display the form
			$this->display( 'db_setup' );
		}

		$db_type = $this->handler_vars['db_type'];
		if ( $db_type == 'mysql' || $db_type == 'pgsql' ) {
			$this->handler_vars['db_host'] = $_POST["{$db_type}_db_host"];
			$this->handler_vars['db_user'] = $_POST["{$db_type}_db_user"];
			$this->handler_vars['db_pass'] = $_POST["{$db_type}_db_pass"];
			$this->handler_vars['db_schema'] = $_POST["{$db_type}_db_schema"];
		}


		// we got here, so we have all the info we need to install

		// make sure the admin password is correct
		if ( $this->handler_vars['admin_pass1'] !== $this->handler_vars['admin_pass2'] ) {
			$this->theme->assign( 'form_errors', array( 'password_mismatch'=>_t( 'Password mis-match.' ) ) );
			$this->display( 'db_setup' );
		}

		// check whether prefix is valid
		if ( isset( $this->handler_vars['table_prefix'] ) && ( preg_replace( '/[^a-zA-Z_]/', '', $this->handler_vars['table_prefix'] ) !== $this->handler_vars['table_prefix'] ) ) {
			$this->theme->assign( 'form_errors', array( 'table_prefix' => _t( 'Allowed characters are A-Z, a-z and "_".' ) ) );
			$this->display( 'db_setup' );
		}

		// Make sure we still have a valid connection
		if ( ! call_user_func( array( $this, "check_{$db_type}" ) ) ) {
			$this->display( 'db_setup' );
		}

		// try to write the config file
		if ( ! $this->write_config_file() ) {
			$this->theme->assign( 'form_errors', array( 'write_file'=>_t( 'Could not write config.php file&hellip;' ) ) );
			$this->display( 'db_setup' );
		}

		// try to install the database
		if ( ! $this->install_db() ) {
			// the installation failed for some reason.
			// re-display the form
			$this->display( 'db_setup' );
		}

		// activate plugins on POST
		if ( count( $_POST ) > 0 ) {
			$this->activate_plugins();
		}



		// Installation complete. Secure sqlite if it was chosen as the database type to use
		if ( $db_type == 'sqlite' ) {
			if ( !$this->secure_sqlite() ) {
				$this->theme->sqlite_contents = implode( "\n", $this->sqlite_contents() );
				$this->display( 'sqlite' );
			}
		}

		EventLog::log( _t( 'Habari successfully installed.' ), 'info', 'default', 'habari' );
		Utils::redirect( Site::get_url( 'habari' ) );
	}

	/*
	 * Helper function to grab list of plugins
	 */
	public function get_plugins()
	{
		$all_plugins = Plugins::list_all();
		$recommended_list = array(
			'coredashmodules.plugin.php',
			'coreblocks.plugin.php',
			'habarisilo.plugin.php',
			'pingback.plugin.php',
			'spamchecker.plugin.php',
			'undelete.plugin.php',
			'autop.plugin.php'
		);

		foreach ( $all_plugins as $file ) {
			$plugin = array();
			$plugin_id = Plugins::id_from_file( $file );
			$plugin['plugin_id'] = $plugin_id;
			$plugin['file'] = $file;

			$error = '';
			if ( Utils::php_check_file_syntax( $file, $error ) ) {
				$plugin['debug'] = false;
				// get this plugin's info()
				$plugin['active'] = false;
				$plugin['verb'] = _t( 'Activate' );
				$plugin['actions'] = array();
				$plugin['info'] = Plugins::load_info( $file );
				$plugin['recommended'] = in_array( basename( $file ), $recommended_list );

			}
			else {
				// We can't get the plugin info due to an error
				// This will show up in the plugin panel, just continue through install
				continue;
			}

			$plugins[$plugin_id] = $plugin;
		}

		return $plugins;
	}

	/**
	 * Helper function to remove code repetition
	 *
	 * @param template_name Name of template to use
	 */
	private function display( $template_name )
	{
		foreach ( $this->handler_vars as $key=>$value ) {
			$this->theme->assign( $key, $value );
		}

		$this->theme->assign( 'plugins', $this->get_plugins() );

		$this->theme->display( $template_name );

		exit;
	}

	/*
	 * sets default values for the form
	 */
	public function form_defaults()
	{
		$formdefaults['db_type'] = 'mysql';
		$formdefaults['db_host'] = 'localhost';
		$formdefaults['db_user'] = '';
		$formdefaults['db_pass'] = '';
		$formdefaults['db_file'] = 'habari.db';
		$formdefaults['db_schema'] = 'habari';
		$formdefaults['table_prefix'] = isset( Config::get( 'db_connection' )->prefix ) ? Config::get( 'db_connection' )->prefix : 'habari__';
		$formdefaults['admin_username'] = 'admin';
		$formdefaults['admin_pass1'] = '';
		$formdefaults['admin_pass2'] = '';
		$formdefaults['blog_title'] = 'My Habari';
		$formdefaults['admin_email'] = '';

		foreach ( $formdefaults as $key => $value ) {
			if ( !isset( $this->handler_vars[$key] ) ) {
				$this->handler_vars[$key] = $value;
			}
		}
	}

	/**
	 * Gathers information about the system in order to make sure
	 * requirements for install are met
	 *
	 * @returns bool  are all requirements met?
	 */
	private function meets_all_requirements()
	{
		// Required extensions, this list will augment with time
		// Even if they are enabled by default, it seems some install turn them off
		// We use the URL in the Installer template to link to the installation page
		$required_extensions = array(
			'date' => 'http://php.net/datetime',
			'pdo' => 'http://php.net/pdo',
			'hash' => 'http://php.net/hash',
			'json' => 'http://php.net/json',
			'mbstring' => 'http://php.net/mbstring',
			'pcre' => 'http://php.net/pcre',
			'session' => 'http://php.net/session',
			'simplexml' => 'http://php.net/simplexml',
			'spl' => 'http://php.net/spl',
			'tokenizer' => 'http://php.net/tokenizer',
			);
		$requirements_met = true;

		/* Check versions of PHP */
		$php_version_ok = version_compare( phpversion(), MIN_PHP_VERSION, '>=' );
		$this->theme->assign( 'php_version_ok', $php_version_ok );
		$this->theme->assign( 'PHP_OS', PHP_OS );;
		$this->theme->assign( 'PHP_VERSION', phpversion() );
		if ( ! $php_version_ok ) {
			$requirements_met = false;
		}
		/* Check for mod_rewrite on Apache */
		$mod_rewrite = true;
		if ( function_exists( 'apache_get_modules' ) && !in_array( 'mod_rewrite', apache_get_modules() ) ) {
			$requirements_met = false;
			$mod_rewrite = false;
		}
		$this->theme->assign( 'mod_rewrite', $mod_rewrite );
		/* Check for required extensions */
		$missing_extensions = array();
		foreach ( $required_extensions as $ext_name => $ext_url ) {
			if ( !extension_loaded( $ext_name ) ) {
				$missing_extensions[$ext_name] = $ext_url;
				$requirements_met = false;
			}
		}
		$this->theme->assign( 'missing_extensions', $missing_extensions );

		if ( extension_loaded( 'pdo' ) ) {
			/* Check for PDO drivers */
			$pdo_drivers = PDO::getAvailableDrivers();
			if ( ! empty( $pdo_drivers ) ) {
				$pdo_drivers = array_combine( $pdo_drivers, $pdo_drivers );
				// Include only those drivers that we include database support for
				$pdo_schemas = array_map( 'basename', Utils::glob( HABARI_PATH . '/system/schema/*', GLOB_ONLYDIR ) );
				$pdo_schemas = array_combine( $pdo_schemas, $pdo_schemas );

				$pdo_drivers = array_intersect_key(
					$pdo_drivers,
					$pdo_schemas
				);
				$pdo_missing_drivers = array_diff(
					$pdo_schemas,
					$pdo_drivers
				);

				$pdo_drivers_ok = count( $pdo_drivers );
				$this->theme->assign( 'pdo_drivers_ok', $pdo_drivers_ok );
				$this->theme->assign( 'pdo_drivers', $pdo_drivers );
				$this->theme->assign( 'pdo_missing_drivers', $pdo_missing_drivers );
			}
			else {
				$pdo_drivers_ok = false;
				$this->theme->assign( 'pdo_drivers_ok', $pdo_drivers_ok );
			}
			if ( ! $pdo_drivers_ok ) {
				$requirements_met = false;
			}
		}
		else {
			$this->theme->assign( 'pdo_drivers_ok', false );
			$this->theme->assign( 'pdo_drivers', array() );
			$requirements_met = false;
		}

		if ( $requirements_met && ! @preg_match( '/\p{L}/u', 'a' ) ) {
			$requirements_met = false;
		}

		/**
		 * $local_writable is used in the template, but never set in Habari
		 * Won't remove the template code since it looks like it should be there
		 *
		 * This will only meet the requirement so there's no "undefined variable" exception
		 */
		$this->theme->assign( 'local_writable', true );

		return $requirements_met;
	}

	/**
	 * Attempts to install the database.  Returns the result of
	 * the installation, adding errors to the theme if any
	 * occur
	 *
	 * @return bool result of installation
	 */
	private function install_db()
	{
		$db_host = $this->handler_vars['db_host'];
		$db_type = $this->handler_vars['db_type'];
		$db_schema = $this->handler_vars['db_schema'];
		$db_user = $this->handler_vars['db_user'];
		$db_pass = $this->handler_vars['db_pass'];

		switch ( $db_type ) {
			case 'mysql':
			case 'pgsql':
				// MySQL & PostgreSQL requires specific connection information
				if ( empty( $db_user ) ) {
					$this->theme->assign( 'form_errors', array( "{$db_type}_db_user"=>_t( 'User is required.' ) ) );
					return false;
				}
				if ( empty( $db_schema ) ) {
					$this->theme->assign( 'form_errors', array( "{$db_type}_db_schema"=>_t( 'Name for database is required.' ) ) );
					return false;
				}
				if ( empty( $db_host ) ) {
					$this->theme->assign( 'form_errors', array( "{$db_type}_db_host"=>_t( 'Host is required.' ) ) );
					return false;
				}
				break;
			case 'sqlite':
				// If this is a SQLite database, let's check that the file
				// exists and that we can access it.
				if ( ! $this->check_sqlite() ) {
					return false;
				}
				break;
		}

		if ( isset( $this->handler_vars['table_prefix'] ) ) {
			// store prefix in the Config singleton so DatabaseConnection can access it
			Config::set( 'db_connection', array( 'prefix' => $this->handler_vars['table_prefix'], ) );
		}

		if ( ! $this->connect_to_existing_db() ) {
			$this->theme->assign( 'form_errors', array( "{$db_type}_db_user"=>_t( 'Problem connecting to supplied database credentials' ) ) );
			return false;
		}

		DB::begin_transaction();
		/* Let's install the DB tables now. */
		$create_table_queries = $this->get_create_table_queries(
			$this->handler_vars['db_type'],
			$this->handler_vars['table_prefix'],
			$this->handler_vars['db_schema']
		);
		DB::clear_errors();
		DB::dbdelta( $create_table_queries, true, true, true );

		if ( DB::has_errors() ) {
			$error = DB::get_last_error();
			$this->theme->assign( 'form_errors', array( 'db_host'=>sprintf( _t( 'Could not create schema tables&hellip; %s' ), $error['message'] ) ) );
			DB::rollback();
			return false;
		}

		// Cool.  DB installed. Create the default options
		// but check first, to make sure
		if ( ! Options::get( 'installed' ) ) {
			if ( ! $this->create_default_options() ) {
				$this->theme->assign( 'form_errors', array( 'options'=>_t( 'Problem creating default options' ) ) );
				DB::rollback();
				return false;
			}
		}

		// Create the Tags vocabulary
		if ( ! $this->create_tags_vocabulary() ) {
			$this->theme->assign( 'form_errors', array( 'options'=>_t( 'Problem creating tags vocabulary' ) ) );
			DB::rollback();
			return false;
		}

		// Create the standard post types and statuses
		if ( ! $this->create_base_post_types() ) {
			$this->theme->assign( 'form_errors', array( 'options'=>_t( 'Problem creating base post types' ) ) );
			DB::rollback();
			return false;
		}

		// Let's setup the admin user and group now.
		// But first, let's make sure that no users exist
		$all_users = Users::get_all();
		if ( count( $all_users ) < 1 ) {
			$user = $this->create_admin_user();
			if ( ! $user ) {
				$this->theme->assign( 'form_errors', array( 'admin_user'=>_t( 'Problem creating admin user.' ) ) );
				DB::rollback();
				return false;
			}
			$admin_group = $this->create_admin_group( $user );
			if ( ! $admin_group ) {
				$this->theme->assign( 'form_errors', array( 'admin_user'=>_t( 'Problem creating admin group.' ) ) );
				DB::rollback();
				return false;
			}
			// create default tokens
			ACL::rebuild_permissions( $user );
		}

		// create a first post, if none exists
		if ( ! Posts::get( array( 'count' => 1 ) ) ) {
			if ( ! $this->create_first_post() ) {
				$this->theme->assign( 'form_errors', array( 'post'=>_t( 'Problem creating first post.' ) ) );
				DB::rollback();
				return false;
			}
		}

		/* Post::save_tags() closes transaction, until we fix that, check and reconnect if needed */
		if ( !DB::in_transaction() ) {
			DB::begin_transaction();
		}

		/* Store current DB version so we don't immediately run dbdelta. */
		Version::save_dbversion();

		/* Ready to roll. */
		DB::commit();
		return true;
	}

	/**
	 * Validate database credentials for MySQL
	 * Try to connect and verify if database name exists
	 */
	public function check_mysql()
	{
		// Can we connect to the DB?
		$pdo = 'mysql:host=' . $this->handler_vars['db_host'] . ';dbname=' . $this->handler_vars['db_schema'];
		if ( isset( $this->handler_vars['table_prefix'] ) ) {
			// store prefix in the Config singleton so DatabaseConnection can access it
			Config::set( 'db_connection', array( 'prefix' => $this->handler_vars['table_prefix'], ) );
		}
		try {
			$connect = DB::connect( $pdo, $this->handler_vars['db_user'], $this->handler_vars['db_pass'] );
			return true;
		}
		catch( PDOException $e ) {
			if ( strpos( $e->getMessage(), '[1045]' ) ) {
				$this->theme->assign( 'form_errors', array( 'mysql_db_pass' => _t( 'Access denied. Make sure these credentials are valid.' ) ) );
			}
			else if ( strpos( $e->getMessage(), '[1049]' ) ) {
				$this->theme->assign( 'form_errors', array( 'mysql_db_schema' => _t( 'That database does not exist.' ) ) );
			}
			else if ( strpos( $e->getMessage(), '[2005]' ) ) {
				$this->theme->assign( 'form_errors', array( 'mysql_db_host' => _t( 'Could not connect to host.' ) ) );
			}
			else {
				$this->theme->assign( 'form_errors', array( 'mysql_db_host' => $e->getMessage() ) );
			}
			return false;
		}
	}

	/**
	 * Validate database credentials for PostgreSQL
	 * Try to connect and verify if database name exists
	 */
	public function check_pgsql()
	{
		// Can we connect to the DB?
		$pdo = 'pgsql:host=' . $this->handler_vars['db_host'] . ';dbname=' . $this->handler_vars['db_schema'];
		if ( isset( $this->handler_vars['table_prefix'] ) ) {
			// store prefix in the Config singleton so DatabaseConnection can access it
			Config::set( 'db_connection', array( 'prefix' => $this->handler_vars['table_prefix'], ) );
		}
		try {
			$connect = DB::connect( $pdo, $this->handler_vars['db_user'], $this->handler_vars['db_pass'] );
			return true;
		}
		catch( PDOException $e ) {
			if ( strpos( $e->getMessage(), '[1045]' ) ) {
				$this->theme->assign( 'form_errors', array( 'pgsql_db_pass' => _t( 'Access denied. Make sure these credentials are valid.' ) ) );
			}
			else if ( strpos( $e->getMessage(), '[1049]' ) ) {
				$this->theme->assign( 'form_errors', array( 'pgsql_db_schema' => _t( 'That database does not exist.' ) ) );
			}
			else if ( strpos( $e->getMessage(), '[2005]' ) ) {
				$this->theme->assign( 'form_errors', array( 'pgsql_db_host' => _t( 'Could not connect to host.' ) ) );
			}
			else {
				$this->theme->assign( 'form_errors', array( 'pgsql_db_host' => $e->getMessage() ) );
			}
			return false;
		}
	}

	/**
	 * Checks for the existance of a SQLite datafile
	 * tries to create it if it does not exist
	**/
	private function check_sqlite()
	{
		$db_file = $this->handler_vars['db_file'];
		if ( $db_file == basename( $db_file ) ) { // The filename was given without a path
			$db_file = Site::get_path( 'user', true ) . $db_file;
		}
		if ( file_exists( $db_file ) && is_writable( $db_file ) && is_writable( dirname( $db_file ) ) ) {
			// the file exists, and is writable.  We're all set
			return true;
		}

		// try to figure out what the problem is.
		if ( file_exists( $db_file ) ) {
			// the DB file exists, why can't we access it?
			if ( ! is_writable( $db_file ) ) {
				$this->theme->assign( 'form_errors', array( 'db_file'=>_t( 'Cannot write to %s. The SQLite data file is not writable by the web server.', array( $db_file ) ) ) );
				return false;
			}
			if ( ! is_writable( dirname( $db_file ) ) ) {
				$this->theme->assign( 'form_errors', array( 'db_file'=>_t( 'Cannot write to %s directory. SQLite requires that the directory that holds the DB file be writable by the web server.', array( $db_file ) ) ) );
				return false;
			}
		}

		if ( ! file_exists( $db_file ) ) {
			// let's see if the directory is writable
			// so that we could create the file
			if ( ! is_writable( dirname( $db_file ) ) ) {
				$this->theme->assign( 'form_errors', array( 'db_file'=>_t( 'Cannot write to %s directory. The SQLite data file does not exist, and it cannot be created in the specified directory. SQLite requires that the directory containing the database file be writable by the web server.', array( $db_file ) ) ) );
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks that there is a database matching the supplied
	 * arguments.
	 *
	 * @return  bool  Database exists with credentials?
	 */
	private function connect_to_existing_db()
	{
		if ( $config = $this->get_config_file() ) {
			$config = preg_replace( '/<\\?php(.*)\\?'.'>/ims', '$1', $config );
			// Update the db_connection from the config that is about to be written:
			eval( $config );

			/* Attempt to connect to the database host */
			try {
				DB::connect();
				return true;
			}
			catch( PDOException $e ) {
				$this->theme->assign( 'form_errors', array( 'db_user'=>_t( 'Problem connecting to supplied database credentials' ) ) );
				return false;

			}
		}
		// If we couldn't create the config from the template, return an error
		return false;
	}

	/**
	 * Creates the administrator user from form information
	 *
	 * @return  mixed. the user on success, false on failure
	 */
	private function create_admin_user()
	{
		$admin_username = $this->handler_vars['admin_username'];
		$admin_email = $this->handler_vars['admin_email'];
		$admin_pass = $this->handler_vars['admin_pass1'];

		if ( $admin_pass{0} == '{' ) {
			// looks like we might have a crypted password
			$password = $admin_pass;

			// but let's double-check
			$algo = strtolower( substr( $admin_pass, 1, 3 ) );
			if ( ( 'ssh' != $algo ) && ( 'sha' != $algo ) ) {
				// we do not have a crypted password
				// so let's encrypt it
				$password = Utils::crypt( $admin_pass );
			}
		}
		else {
			$password = Utils::crypt( $admin_pass );
		}

		// Insert the admin user
		$user = User::create( array (
			'username'=>$admin_username,
			'email'=>$admin_email,
			'password'=>$password
		) );

		return $user;
	}

	/**
	 * Creates the admin group using the created user
	 *
	 * @param $user User the administrative user who is installing
	 * @return  mixed  the user group on success, false on failure
	 */
	private function create_admin_group( $user )
	{
		// Create the admin group
		$group = UserGroup::create( array( 'name' => _t( 'admin' ) ) );
		if ( ! $group ) {
			return false;
		}
		$group->add( $user->id );
		return $group;
	}

	private function create_anonymous_group()
	{
		// Create the anonymous group
		$group = UserGroup::create( array( 'name' => _t( 'anonymous' ) ) );
		if ( ! $group ) {
			return false;
		}
		$group->grant( 'post_entry', 'read' );
		$group->grant( 'post_page', 'read' );

		// Add the anonumous user to the anonymous group
		$group->add( 0 );
	}

	/**
	 * Write the default options
	 */
	private function create_default_options()
	{
		// Create the default options

		Options::set( 'installed', true );

		Options::set( 'title', $this->handler_vars['blog_title'] );
		Options::set( 'pagination', '5' );
		Options::set( 'atom_entries', '5' );
		Options::set( 'theme_name', 'k2' );
		Options::set( 'theme_dir', 'k2' );
		Options::set( 'comments_require_id', 1 );
		Options::set( 'locale', $this->handler_vars['locale'] );
		Options::set( 'timezone', 'UTC' );
		Options::set( 'dateformat', 'Y-m-d' );
		Options::set( 'timeformat', 'g:i a' );
		Options::set( 'log_min_severity', 3 );		// the default logging level - 3 should be 'info'
		Options::set( 'spam_percentage', 100 );

		// generate a random-ish number to use as the salt for
		// a SHA1 hash that will serve as the unique identifier for
		// this installation.  Also for use in cookies
		Options::set( 'GUID', sha1( Utils::nonce() ) );

		// Let's prepare the EventLog here, as well
		EventLog::register_type( 'default', 'habari' );
		EventLog::register_type( 'user', 'habari' );
		EventLog::register_type( 'authentication', 'habari' );
		EventLog::register_type( 'content', 'habari' );
		EventLog::register_type( 'comment', 'habari' );

		// Add the cronjob to trim the log so that it doesn't get too big
		CronTab::add_daily_cron( 'trim_log', array( 'EventLog', 'trim' ), _t( 'Trim the log table' ) );
		
		// Add the cronjob to check for plugin updates
		CronTab::add_daily_cron( 'update_check', array( 'Update', 'cron' ), _t( 'Perform a check for plugin updates.' ) );

		return true;
	}

	/**
	 * Add the standard post types and statuses to the database
	 */
	private function create_base_post_types()
	{
		// first, let's create our default post types of
		// "entry" and "page"
		Post::add_new_type( 'entry' );
		Post::add_new_type( 'page' );

		// now create post statuses for
		// "published" and "draft"
		// Should "private" status be added here, or through a plugin?
		Post::add_new_status( 'draft' );
		Post::add_new_status( 'published' );
		Post::add_new_status( 'scheduled', true );

		return true;
	}

	/**
	 * Add the tags vocabulary
	 */
	private function create_tags_vocabulary()
	{
		$vocabulary = new Vocabulary( array( 'name' => 'tags', 'description' => 'Habari\'s tags implementation', 'features' => array( 'multiple', 'free' ) ) );
		$vocabulary->insert();

		return true;
	}

	/**
	 * Create the first post
	**/
	private function create_first_post()
	{
		$users = Users::get();
		Post::create( array(
			'title' => 'Habari',
			'content' => _t( 'This site is running <a href="http://habariproject.org/">Habari</a>, a state-of-the-art publishing platform!  Habari is a community-driven project created and supported by people from all over the world.  Please visit <a href="http://habariproject.org/">http://habariproject.org/</a> to find out more!' ),
			'user_id' => $users[0]->id,
			'status' => Post::status( 'published' ),
			'content_type' => Post::type( 'entry' ),
			'tags' => 'habari',
		) );

		return true;
	}

	/**
	 * Install schema tables from the respective RDBMS schema
	 * @param $db_type string The schema string for the database
	 * @param $table_prefix string The prefix to use on each table name
	 * @param $db_schema string The database name
	 * @return array Array of queries to execute
	 */
	private function get_create_table_queries( $db_type, $table_prefix, $db_schema )
	{
		/* Grab the queries from the RDBMS schema file */
		$file_path = HABARI_PATH . "/system/schema/{$db_type}/schema.sql";
		$schema_sql = trim( file_get_contents( $file_path ), "\r\n " );
		$schema_sql = str_replace( '{$schema}', $db_schema, $schema_sql );
		$schema_sql = str_replace( '{$prefix}', $table_prefix, $schema_sql );

		/*
		 * Just in case anyone creates a schema file with separate statements
		 * not separated by two newlines, let's clean it here...
		 * Likewise, let's clean up any separations of *more* than two newlines
		 */
		$schema_sql = str_replace( array( "\r\n", "\r", ), array( "\n", "\n" ), $schema_sql );
		$schema_sql = preg_replace( "/;\n([^\n])/", ";\n\n$1", $schema_sql );
		$schema_sql = preg_replace( "/\n{3,}/", "\n\n", $schema_sql );
		$queries = preg_split( '/(\\r\\n|\\r|\\n)\\1/', $schema_sql );
		return $queries;
	}


	/**
	 * Returns an RDMBS-specific CREATE SCHEMA plus user SQL expression(s)
	 *
	 * @return  string[]  array of SQL queries to execute
	 */
	private function get_create_schema_and_user_queries()
	{
		$db_host = $this->handler_vars['db_host'];
		$db_type = $this->handler_vars['db_type'];
		$db_schema = $this->handler_vars['db_schema'];
		$db_user = $this->handler_vars['db_user'];
		$db_pass = $this->handler_vars['db_pass'];

		$queries = array();
		switch ( $db_type ) {
			case 'mysql':
				$queries[] = 'CREATE DATABASE ' . $db_schema . ';';
				$queries[] = 'GRANT ALL ON ' . $db_schema . '.* TO \'' . $db_user . '\'@\'' . $db_host . '\' ' .
				'IDENTIFIED BY \'' . $db_pass . '\';';
				break;
			case 'pgsql':
				$queries[] = 'CREATE DATABASE ' . $db_schema . ';';
				$queries[] = 'GRANT ALL ON DATABASE ' . $db_schema . ' TO ' . $db_user . ';';
				break;
			default:
				die( _t( 'currently unsupported.' ) );
		}
		return $queries;
	}

	/**
	* Gets the configuration template, inserts the variables into it, and returns it as a string
	*
	* @return string The config.php template for the db_type schema
	*/
	private function get_config_file()
	{
		if ( ! ( $file_contents = file_get_contents( HABARI_PATH . "/system/schema/" . $this->handler_vars['db_type'] . "/config.php" ) ) ) {
			return false;
		}

		$vars = array();
		foreach ( $this->handler_vars as $k => $v ) {
			$vars[$k] = addslashes( $v );
		}
		$keys = array();
		foreach ( array_keys( $vars ) as $v ) {
			$keys[] = Utils::map_array( $v );
		}

		$file_contents = str_replace(
			$keys,
			$vars,
			$file_contents
		);
		return $file_contents;
	}

	/**
	 * Writes the configuration file with the variables needed for
	 * initialization of the application
	 *
	 * @param Bool $ignore_registry skip the configuration registry check (used in config rewrite)
	 * @return  bool  Did the file get written?
	 */
	private function write_config_file( $ignore_registry = false )
	{
		// first, check if a config.php file exists
		if ( file_exists( Site::get_dir( 'config_file' ) ) ) {
			// set the defaults for comparison
			$db_host = $this->handler_vars['db_host'];
			$db_file = $this->handler_vars['db_file'];
			$db_type = $this->handler_vars['db_type'];
			$db_schema = $this->handler_vars['db_schema'];
			$db_user = $this->handler_vars['db_user'];
			$db_pass = $this->handler_vars['db_pass'];
			$table_prefix = $this->handler_vars['table_prefix'];

			// set the connection string
			switch ( $db_type ) {
				case 'mysql':
					$connection_string = "$db_type:host=$db_host;dbname=$db_schema";
					break;
				case 'pgsql':
					$connection_string = "$db_type:host=$db_host dbname=$db_schema";
					break;
				case 'sqlite':
					$connection_string = "$db_type:$db_file";
					break;
			}

			// load the config.php file
			include( Site::get_dir( 'config_file' ) );

			// and now we compare the values defined there to
			// the values POSTed to the installer
			if ( !$ignore_registry && Config::exists( 'db_connection' ) &&
				( Config::get( 'db_connection' )->connection_string == $connection_string )
				&& ( Config::get( 'db_connection' )->username == $db_user )
				&& ( Config::get( 'db_connection' )->password == $db_pass )
				&& ( Config::get( 'db_connection' )->prefix == $table_prefix )
			) {
				// the values are the same, so don't bother
				// trying to write to config.php
				return true;
			}
		}
		if ( ! ( $file_contents = file_get_contents( HABARI_PATH . "/system/schema/" . $this->handler_vars['db_type'] . "/config.php" ) ) ) {
			return false;
		}
		if ( $file_contents = $this->get_config_file() ) {
			if ( $file = @fopen( Site::get_dir( 'config_file' ), 'w' ) ) {
				if ( fwrite( $file, $file_contents, strlen( $file_contents ) ) ) {
					fclose( $file );
					return true;
				}
			}
			$this->handler_vars['config_file'] = Site::get_dir( 'config_file' );
			$this->handler_vars['file_contents'] = Utils::htmlspecialchars( $file_contents );
			$this->display( 'config' );
			return false;
		}
		return false;  // Only happens when config.php template does not exist.
	}

	public function activate_plugins()
	{
		// extract checked plugin IDs from $_POST
		$plugin_ids = array();
		foreach ( $_POST as $id => $activate ) {
			if ( preg_match( '/plugin_\w+/u', $id ) && $activate ) {
				$id = substr( $id, 7 );
				$plugin_ids[] = $id;
			}
		}

		// set the user_id in the session in case plugin activation methods need it
		if ( ! $u = User::get_by_name( $this->handler_vars['admin_username'] ) ) {
			// @todo die gracefully
			die( _t( 'No admin user found' ) );
		}
		$u->remember();

		// loop through all plugins to find matching plugin files
		$plugin_files = Plugins::list_all();
		foreach ( $plugin_files as $file ) {
			$id = Plugins::id_from_file( $file );
			if ( in_array( $id, $plugin_ids ) ) {
				Plugins::activate_plugin( $file );
			}
		}

		// unset the user_id session variable
		Session::clear_userid( $_SESSION['user_id'] );
		unset( $_SESSION['user_id'] );
	}

	/**
	 * returns an array of .htaccess declarations used by Habari
	 */
	public function htaccess()
	{
		$htaccess = array(
			'open_block' => '### HABARI START',
			'engine_on' => 'RewriteEngine On',
			'rewrite_cond_f' => 'RewriteCond %{REQUEST_FILENAME} !-f',
			'rewrite_cond_d' => 'RewriteCond %{REQUEST_FILENAME} !-d',
			'rewrite_favicon' => 'RewriteCond %{REQUEST_URI} !=/favicon.ico',
			'rewrite_base' => '#RewriteBase /',
			'rewrite_rule' => 'RewriteRule . index.php [PT]',
			'hide_habari' => 'RewriteRule ^(system/(classes|locale|schema|$)) index.php [PT]',
			//'http_auth' => 'RewriteRule .* [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L] # Make sure HTTP auth works in PHP-CGI configs',
			'close_block' => '### HABARI END',
		);
		$rewrite_base = trim( dirname( $_SERVER['SCRIPT_NAME'] ), '/\\' );
		if ( $rewrite_base != '' ) {
			$htaccess['rewrite_base'] = 'RewriteBase /' . $rewrite_base;
		}

		return $htaccess;
	}

	/**
	 * checks for the presence of an .htaccess file
	 * invokes write_htaccess() as needed
	 */
	public function check_htaccess()
	{
		// default is assume we have mod_rewrite
		$this->handler_vars['no_mod_rewrite'] = false;

		// If this is the mod_rewrite check request, then bounce it as a success.
		if ( strpos( $_SERVER['REQUEST_URI'], 'check_mod_rewrite' ) !== false ) {
			echo 'ok';
			exit;
		}

		if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) ) {
			// .htaccess is only needed on Apache
			// @TODO: add support for IIS and lighttpd rewrites
			return true;
		}

		$result = false;
		if ( file_exists( HABARI_PATH . '/.htaccess' ) ) {
			$htaccess = file_get_contents( HABARI_PATH . '/.htaccess' );
			if ( false === strpos( $htaccess, 'HABARI' ) ) {
				// the Habari block does not exist in this file
				// so try to create it
				$result = $this->write_htaccess( true );
			}
			else {
				// the Habari block exists
				$result = true;
			}
		}
		else {
			// no .htaccess exists.  Try to create one
			$result = $this->write_htaccess();
		}
		if ( $result ) {
			// the Habari block exists, but we need to make sure
			// it is correct.
			// Check that the rewrite rules actually do the job.
			$test_ajax_url = Site::get_url( 'habari' ) . '/check_mod_rewrite';
			$rr = new RemoteRequest( $test_ajax_url, 'POST', 5 );
			try {
				$rr_result = $rr->execute();
			}
			catch ( Exception $e ) {
				$result = $this->write_htaccess( true, true, true );
			}
		}
		return $result;
	}

	/**
	 * attempts to write the .htaccess file if none exists
	 * or to write the Habari-specific portions to an existing .htaccess
	 * @param bool whether an .htaccess file already exists or not
	 * @param bool whether to remove and re-create any existing Habari block
	 * @param bool whether to try a rewritebase in the .htaccess
	**/
	public function write_htaccess( $exists = false, $update = false, $rewritebase = true )
	{
		$htaccess = $this->htaccess();
		if ( $rewritebase ) {
			$rewrite_base = trim( dirname( $_SERVER['SCRIPT_NAME'] ), '/\\' );
			$htaccess['rewrite_base'] = 'RewriteBase /' . $rewrite_base;
		}
		$file_contents = "\n" . implode( "\n", $htaccess ) . "\n";

		if ( ! $exists ) {
			if ( ! is_writable( HABARI_PATH ) ) {
				// we can't create the file
				return false;
			}
		}
		else {
			if ( ! is_writable( HABARI_PATH . '/.htaccess' ) ) {
				// we can't update the file
				return false;
			}
		}
		if ( $update ) {
			// we're updating an existing but incomplete .htaccess
			// care must be take only to remove the Habari bits
			$htaccess = file_get_contents( HABARI_PATH . '/.htaccess' );
			$file_contents = preg_replace( '%### HABARI START.*?### HABARI END%ims', $file_contents, $htaccess );
			// Overwrite the existing htaccess with one that includes the modified Habari rewrite block
			$fmode = 'w';
		}
		else {
			// Append the Habari rewrite block to the existing file.
			$fmode = 'a';
		}
		//Save the htaccess
		if ( $fh = fopen( HABARI_PATH . '/.htaccess', $fmode ) ) {
			if ( false === fwrite( $fh, $file_contents ) ) {
				return false;
			}
			fclose( $fh );
		}
		else {
			return false;
		}

		return true;
	}

	/**
	 * returns an array of Files declarations used by Habari
	 */
	public function sqlite_contents()
	{
		$db_file = basename( $this->handler_vars['db_file'] );
		$contents = array(
			'### HABARI SQLITE START',
			'<Files "' . $db_file . '">',
			'Order deny,allow',
			'deny from all',
			'</Files>',
			'### HABARI SQLITE END'
		);

		return $contents;
	}

	/**
	 * attempts to write the Files clause to the .htaccess file
	 * if the clause for this sqlite doesn't exist.
	 * @return bool success or failure
	**/
	public function secure_sqlite()
	{
		if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) ) {
			// .htaccess is only needed on Apache
			// @TODO: Notify people on other servers to take measures to secure the SQLite file.
			return true;
		}
		if ( !file_exists( HABARI_PATH . '/.htaccess' ) ) {
			// no .htaccess to write to
			return false;
		}
		if ( !is_writable( HABARI_PATH . DIRECTORY_SEPARATOR . '.htaccess' ) ) {
			// we can't update the file
			return false;
		}

		// Get the files clause
		$sqlite_contents = $this->sqlite_contents();
		$files_contents = "\n" . implode( "\n", $sqlite_contents ) . "\n";

		// See if it already exists
		$current_files_contents = file_get_contents( HABARI_PATH . DIRECTORY_SEPARATOR . '.htaccess' );
		if ( false === strpos( $current_files_contents, $files_contents ) ) {
			// If not, append the files clause to the .htaccess file
			if ( $fh = fopen( HABARI_PATH . DIRECTORY_SEPARATOR . '.htaccess', 'a' ) ) {
				if ( false === fwrite( $fh, $files_contents ) ) {
					// Can't write to the file
					return false;
				}
				fclose( $fh );
			}
			else {
				// Can't open the file
				return false;
			}
		}
		// Success!
		return true;
	}

	private function upgrade_db_pre ( $current_version )
	{

		// this is actually a stripped-down version of DatabaseConnection::upgrade() - it doesn't support files

		$upgrade_functions = get_class_methods( $this );

		$upgrades = array();

		foreach ( $upgrade_functions as $fn ) {

			// match all methods named "upgrade_db_pre_<rev#>"
			if ( preg_match( '%^upgrade_db_pre_([0-9]+)$%i', $fn, $matches ) ) {

				$upgrade_version = intval( $matches[1] );

				if ( $upgrade_version > $current_version ) {

					$upgrades[ sprintf( '%010s_1', $upgrade_version ) ] = $fn;

				}

			}

		}

		// sort the upgrades by revision, ascending
		ksort( $upgrades );


		foreach ( $upgrades as $upgrade ) {

			$result =& call_user_func( array( $this, $upgrade ) );

			// if we failed, abort
			if ( $result === false ) {
				break;
			}

		}

	}

	private function upgrade_db_post ( $current_version )
	{

		// this is actually a stripped-down version of DatabaseConnection::upgrade() - it doesn't support files

		$upgrade_functions = get_class_methods( $this );

		$upgrades = array();

		foreach ( $upgrade_functions as $fn ) {

			// match all methods named "upgrade_db_post_<rev#>"
			if ( preg_match( '%^upgrade_db_post_([0-9]+)$%i', $fn, $matches ) ) {

				$upgrade_version = intval( $matches[1] );

				if ( $upgrade_version > $current_version ) {

					$upgrades[ sprintf( '%010s_1', $upgrade_version ) ] = $fn;

				}

			}

		}

		// sort the upgrades by revision, ascending
		ksort( $upgrades );

		foreach ( $upgrades as $upgrade ) {

			$result = call_user_func( array( $this, $upgrade ) );

			// if we failed, abort
			if ( $result === false ) {
				break;
			}

		}

	}

	private function upgrade_db_pre_1345 ()
	{

		// fix duplicate tag_slugs

		// first, get all the tags with duplicate entries
		$query = 'select id, tag_slug, tag_text from {tags} where tag_slug in ( select tag_slug from {tags} group by tag_slug having count(*) > 1 ) order by id';
		$tags = DB::get_results( $query );

		// assuming we got some tags to fix...
		if ( count( $tags ) > 0 ) {

			$slug_to_id = array();
			$fix_tags = array();

			foreach ( $tags as $tag_row ) {

				// skip the first tag text so we end up with something, presumably the first tag entered (it had the lowest ID in the db)
				if ( !isset( $fix_tags[ $tag_row->tag_slug ] ) ) {
					$slug_to_id[ $tag_row->tag_slug ] = $tag_row->id;		// collect the slug => id so we can rename with an absolute id later
					$fix_tags[ $tag_row->tag_slug ] = array();
				}
				else {
					$fix_tags[ $tag_row->tag_slug ][ $tag_row->id ] = $tag_row->tag_text;
				}

			}

			foreach ( $fix_tags as $tag_slug => $tag_texts ) {

				Tags::rename( $slug_to_id[ $tag_slug ], array_keys( $tag_texts ) );

			}

		}

		return true;

	}

	/**
	 * Upgrade the database when the database version stored is lower than the one in source
	 * @todo Make more db-independent
	 */
	public function upgrade_db()
	{
		if ( Options::get( 'db_upgrading' ) ) {
			// quit with an error message.
			$this->display_currently_upgrading();
		}

		// don't allow duplicate upgrades.
		Options::set( 'db_upgrading', true );

		// This database-specific code needs to be moved into the schema-specific functions
		list( $schema, $remainder )= explode( ':', Config::get( 'db_connection' )->connection_string );
		switch ( $schema ) {
			case 'sqlite':
				$db_name = '';
				break;
			case 'mysql':
				list( $host,$name ) = explode( ';', $remainder );
				list( $discard, $db_name ) = explode( '=', $name );
				break;
			case 'pgsql':
				list( $host,$name ) = explode( ' ', $remainder );
				list( $discard, $db_name ) = explode( '=', $name );
				break;
		}

		Cache::purge();

		// get the current db version
		$version = Options::get( 'db_version' );

		// do some pre-dbdelta ad-hoc hacky hack code
		$this->upgrade_db_pre( $version );

		// run schema-specific upgrade scripts for before dbdelta
		DB::upgrade_pre( $version );

		// Get the queries for this database and apply the changes to the structure
		$queries = $this->get_create_table_queries( $schema, Config::get( 'db_connection' )->prefix, $db_name );

		DB::dbdelta( $queries );

		// Apply data changes to the database based on version, call the db-specific upgrades, too.
		$this->upgrade_db_post( $version );

		// run schema-specific upgrade scripts for after dbdelta
		DB::upgrade_post( $version );

		Version::save_dbversion();
		Options::delete( 'db_upgrading' );
	}

	private function display_currently_upgrading()
	{
		// Error template.
		$error_template = "<html><head><title>%s</title></head><body><h1>%s</h1><p>%s</p></body></html>";

		// Format page with localized messages.
		$error_page = sprintf( $error_template,
			_t( "Site Maintenance" ), // page title
			_t( "Habari is currently being upgraded." ), // H1 tag
			_t( "Try again in a little while." ) // Error message.
		);

		// Set correct HTTP header and die.
		header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
		die( $error_page );
	}

	private function upgrade_db_post_1310 ()
	{

		// Auto-truncate the log table
		if ( ! CronTab::get_cronjob( 'truncate_log' ) ) {
			CronTab::add_daily_cron( 'truncate_log', array( 'Utils', 'truncate_log' ), _t( 'Truncate the log table' ) );
		}

		return true;

	}

	private function upgrade_db_post_1794 ()
	{

		Post::add_new_status( 'scheduled', true );

		return true;

	}

	private function upgrade_db_post_1845 ()
	{

		// Strip the base path off active plugins
		$base_path = array_map( create_function( '$s', 'return str_replace(\'\\\\\', \'/\', $s);' ), array( HABARI_PATH ) );
		$activated = Options::get( 'active_plugins' );
		if ( is_array( $activated ) ) {
			foreach ( $activated as $plugin ) {
				$index = array_search( $plugin, $activated );
				$plugin = str_replace( $base_path, '', $plugin );
				$activated[$index] = $plugin;
			}
			Options::set( 'active_plugins', $activated );
		}

		return true;

	}

	private function upgrade_db_post_2264()
	{
		// create admin group
		$admin_group = UserGroup::get_by_name( 'admin' );
		if ( ! ( $admin_group instanceOf UserGroup ) ) {
			$admin_group = UserGroup::create( array( 'name' => 'admin' ) );
		}

		// add all users to the admin group
		$users = Users::get_all();
		$ids = array();
		foreach ( $users as $user ) {
			$ids[] = $user->id;
		}
		$admin_group->add( $ids );

		return true;
	}

	private function upgrade_db_post_2707 ()
	{

		// sets a default timezone and date / time formats for the options page
		if ( !Options::get( 'timezone' ) ) {
			Options::set( 'timezone', 'UTC' );
		}

		if ( !Options::get( 'dateformat' ) ) {
			Options::set( 'dateformat', 'Y-m-d' );
		}

		if ( !Options::get( 'timeformat' ) ) {
			Options::set( 'timeformat', 'H:i:s' );
		}

		return true;

	}

	private function upgrade_db_post_2786 ()
	{

		// fixes all the bad post2tag fields that didn't get deleted when a post was deleted
		DB::query( 'DELETE FROM {tag2post} WHERE post_id NOT IN ( SELECT DISTINCT id FROM {posts} )' );

		// now, delete any tags that have no posts left
		DB::query( 'DELETE FROM {tags} WHERE id NOT IN ( SELECT DISTINCT tag_id FROM {tag2post} )' );

		return true;

	}

	private function upgrade_db_post_3030()
	{
		// Create the admin group
		$group = UserGroup::create( array( 'name' => 'admin' ) );
		if ( ! $group ) {
			return false;
		}

		// Add the default tokens
		ACL::create_default_tokens();

		// Give admin group access to the super_user token
		$group->grant( 'super_user' );

		// Until now, all users were admins, restore that
		$all_users = Users::get_all();
		foreach ( $all_users as $user ) {
			$group->add( $user );
		}

		// Create the anonymous group
		$this->create_anonymous_group();
	}

	private function upgrade_db_post_3124()
	{
		ACL::rebuild_permissions();
	}

	private function upgrade_db_post_3158()
	{
		// delete own_post_typeX tokens rather than rebuild the whole default token set
		foreach ( Post::list_active_post_types() as $name => $posttype ) {
				ACL::destroy_token( 'own_post_' . Utils::slugify( $name ) );
		}

		ACL::destroy_token( 'own_posts_any' );
		ACL::create_token( 'own_posts', _t( 'Permissions on one\'s own posts' ), 'Content', true );
	}

	private function upgrade_db_post_3236()
	{
		// Add a default to the number of posts of a feed
		$atom_entries = Options::get( 'atom_entries' );
		if ( empty( $atom_entries ) ) {
			Options::set( 'atom_entries', '5' );
		}
		// Create the default authenticated group
		$authenticated_group = UserGroup::get_by_name( _t( 'authenticated' ) );
		if ( ! $authenticated_group instanceof UserGroup ) {
			$authenticated_group = UserGroup::create( array( 'name' => _t( 'authenticated' ) ) );
		}
		$authenticated_group->grant( 'post_entry', 'read' );
		$authenticated_group->grant( 'post_page', 'read' );
		$authenticated_group->grant( 'comment' );

	}

	private function upgrade_db_post_3539()
	{

		// get the global option
		$hide = Options::get( 'dashboard__hide_spam_count' );

		// if it was set to hide, get all our available users and set their info values instead
		if ( $hide == true ) {

			$users = Users::get();

			foreach ( $users as $user ) {

				$user->info->dashboard_hide_spam_count = 1;
				$user->update();

			}

		}

		Options::delete( 'dashboard__hide_spam_count' );

		return true;

	}

	private function upgrade_db_post_3698()
	{
		ACL::create_token( 'manage_self', _t( 'Edit own profile' ), 'Administration' );
	}

	private function upgrade_db_post_3701()
	{
		ACL::create_token( 'manage_dash_modules', _t( 'Manage dashboard modules' ), 'Administration' );
	}

	private function upgrade_db_post_3749()
	{
		$type_id = Vocabulary::object_type_id( 'post' );

		$vocabulary = Vocabulary::create( array( 'name' => 'tags', 'description' => 'Habari\'s tags implementation', 'features' => array( 'multiple', 'free' ) ) );

		$new_tag = null;
		$post_ids = array();
		$prefix = Config::get( 'db_connection' )->prefix;

		$results = DB::get_results( "SELECT id, tag_text, tag_slug from {$prefix}tags" );

		foreach ( $results as $tag ) {
			$new_tag = $vocabulary->add_term( $tag->tag_text );
			$post_ids = DB::get_column( "SELECT post_id FROM {$prefix}tag2post WHERE tag_id = ?", array( $tag->id ) );
			foreach ( $post_ids as $id ) {
				DB::insert( "{object_terms}", array( 'term_id' => $new_tag->id, 'object_id' => $id, 'object_type_id' => $type_id ) );
			}
		}
	}

	private function upgrade_db_post_4291()
	{
		// get all plugins so the legacy ones can be deactivated.
		$active_plugins = Plugins::list_active();
		$all_plugins = Installhandler::get_plugins();

		$legacy_plugins = array();
		foreach ( $all_plugins as $plugin ) {
			if ( !isset( $plugin[ 'info' ] ) ) {
				$key = array_search( $plugin[ 'file' ], $active_plugins );
				$legacy_plugins[ $key ] = $plugin[ 'file' ];
			}
		}
		$valid_plugins = array_diff_key( Options::get( 'active_plugins' ), $legacy_plugins );

		// valid_plugins contains only working plugins, but the classnames are missing. The following was previously upgrade_db_post_3484()
		$new_plugins = array();
		if ( is_array( $valid_plugins ) ) {
			foreach ( $valid_plugins as $filename ) {
				if ( !file_exists( $filename ) ) {
					// try adding base path to stored path
					$filename = HABARI_PATH . $filename;
				}
				if ( file_exists( $filename ) ) {
					// it is now safe to do this since plugins with info() functions are not in $valid_plugins
					require_once( $filename );
					$class = Plugins::class_from_filename( $filename );
					$short_file = substr( $filename, strlen( HABARI_PATH ) );
					if ( $class ) {
						$new_plugins[ $class ] = $short_file;
					}
				}
			}
		}
		// replace option with only the usuable plugins
		Options::set( 'active_plugins', $new_plugins );
	}

	private function upgrade_db_post_4382()
	{

		// add the new logging limit option
		Options::set( 'log_min_severity', 3 );		// 3 is 'info'

	}
	
	private function upgrade_db_post_4571()
	{
		
		// remove the old truncate_log cronjob
		CronTab::delete_cronjob( 'truncate_log' );
		
		// add the new trim_log cronjob
		CronTab::add_daily_cron( 'trim_log', array( 'EventLog', 'trim' ), _t( 'Trim the log table' ) );
		
	}
	
	private function upgrade_db_post_4588()
	{
		
		// Add the cronjob to check for plugin updates
		CronTab::add_daily_cron( 'update_check', array( 'Update', 'cron' ), _t( 'Perform a check for plugin updates.' ) );
		
	}
	
	private function upgrade_db_post_4763()
	{
		
		// delete the base_url option, which is no longer used
		Options::delete( 'base_url' );
		
	}

	private function upgrade_db_post_4770()
	{
		// Add CRUD access tokens for other users' unpublished posts
		ACL::create_token( 'post_unpublished', _t( "Permissions to other users' unpublished posts" ), _t( 'Content' ), true );

		// If a group doesn't have super_user permission, deny access to post_unpublished
		$groups = UserGroups::get_all();
		foreach ( $groups as $group ) {
			if ( !ACL::group_can( $group->id, 'super_user', 'read' ) ) {
				$group->deny( 'post_unpublished' );
			}
		}
	}
	
	private function upgrade_db_post_4980 ( ) {
		
		Options::set( 'spam_percentage', 100 );
		
	}
	
	/**
	 * Validate database credentials for MySQL
	 * Try to connect and verify if database name exists
	 */
	public function ajax_check_mysql_credentials()
	{
		$xml = new SimpleXMLElement( '<response></response>' );
		// Missing anything?
		if ( !isset( $_POST['host'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#mysqldatabasehost' );
			$xml_error->addChild( 'message', _t( 'The database host field was left empty.' ) );
		}
		if ( !isset( $_POST['database'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#mysqldatabasename' );
			$xml_error->addChild( 'message', _t( 'The database name field was left empty.' ) );
		}
		if ( !isset( $_POST['user'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#mysqldatabaseuser' );
			$xml_error->addChild( 'message', _t( 'The database user field was left empty.' ) );
		}
		if ( isset( $_POST['table_prefix'] ) && ( preg_replace( '/[^a-zA-Z_]/', '', $_POST['table_prefix'] ) !== $_POST['table_prefix'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#tableprefix' );
			$xml_error->addChild( 'message', _t( 'Allowed characters are A-Z, a-z and "_".' ) );
		}
		if ( !isset( $xml_error ) ) {
			// Can we connect to the DB?
			$pdo = 'mysql:host=' . $_POST['host'] . ';dbname=' . $_POST['database'];
			try {
				$connect = DB::connect( $pdo, $_POST['user'], $_POST['pass'] );
				$xml->addChild( 'status', 1 );
			}
			catch( Exception $e ) {
				$xml->addChild( 'status', 0 );
				$xml_error = $xml->addChild( 'error' );
				if ( strpos( $e->getMessage(), '[1045]' ) ) {
					$xml_error->addChild( 'id', '#mysqldatabaseuser' );
					$xml_error->addChild( 'id', '#mysqldatabasepass' );
					$xml_error->addChild( 'message', _t( 'Access denied. Make sure these credentials are valid.' ) );
				}
				else if ( strpos( $e->getMessage(), '[1049]' ) ) {
					$xml_error->addChild( 'id', '#mysqldatabasename' );
					$xml_error->addChild( 'message', _t( 'That database does not exist.' ) );
				}
				else if ( strpos( $e->getMessage(), '[2005]' ) ) {
					$xml_error->addChild( 'id', '#mysqldatabasehost' );
					$xml_error->addChild( 'message', _t( 'Could not connect to host.' ) );
				}
				else {
					$xml_error->addChild( 'id', '#mysqldatabaseuser' );
					$xml_error->addChild( 'id', '#mysqldatabasepass' );
					$xml_error->addChild( 'id', '#mysqldatabasename' );
					$xml_error->addChild( 'id', '#mysqldatabasehost' );
					$xml_error->addChild( 'message', $e->getMessage() );
				}
			}
		}
		$xml = $xml->asXML();
		ob_clean();
		header( "Content-type: application/xml" );
		header( "Cache-Control: no-cache" );
		print $xml;
	}

	/**
	 * Validate database credentials for PostgreSQL
	 * Try to connect and verify if database name exists
	 */
	public function ajax_check_pgsql_credentials()
	{
		$xml = new SimpleXMLElement( '<response></response>' );
		// Missing anything?
		if ( !isset( $_POST['host'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#pgsqldatabasehost' );
			$xml_error->addChild( 'message', _t( 'The database host field was left empty.' ) );
		}
		if ( !isset( $_POST['database'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#pgsqldatabasename' );
			$xml_error->addChild( 'message', _t( 'The database name field was left empty.' ) );
		}
		if ( !isset( $_POST['user'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#pgsqldatabaseuser' );
			$xml_error->addChild( 'message', _t( 'The database user field was left empty.' ) );
		}
		if ( isset( $_POST['table_prefix'] ) && ( preg_replace( '/[^a-zA-Z_]/', '', $_POST['table_prefix'] ) !== $_POST['table_prefix'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#tableprefix' );
			$xml_error->addChild( 'message', _t( 'Allowed characters are A-Z, a-z and "_".' ) );
		}
		if ( !isset( $xml_error ) ) {
			// Can we connect to the DB?
			$pdo = 'pgsql:host=' . $_POST['host'] . ' dbname=' . $_POST['database'];
			try {
				$connect = DB::connect( $pdo, $_POST['user'], $_POST['pass'] );
				$xml->addChild( 'status', 1 );
			}
			catch( Exception $e ) {
				$xml->addChild( 'status', 0 );
				$xml_error = $xml->addChild( 'error' );
				if ( strpos( $e->getMessage(), '[1045]' ) ) {
					$xml_error->addChild( 'id', '#pgsqldatabaseuser' );
					$xml_error->addChild( 'id', '#pgsqldatabasepass' );
					$xml_error->addChild( 'message', _t( 'Access denied. Make sure these credentials are valid.' ) );
				}
				else if ( strpos( $e->getMessage(), '[1049]' ) ) {
					$xml_error->addChild( 'id', '#pgsqldatabasename' );
					$xml_error->addChild( 'message', _t( 'That database does not exist.' ) );
				}
				else if ( strpos( $e->getMessage(), '[2005]' ) ) {
					$xml_error->addChild( 'id', '#pgsqldatabasehost' );
					$xml_error->addChild( 'message', _t( 'Could not connect to host.' ) );
				}
				else {
					$xml_error->addChild( 'id', '#pgsqldatabaseuser' );
					$xml_error->addChild( 'id', '#pgsqldatabasepass' );
					$xml_error->addChild( 'id', '#pgsqldatabasename' );
					$xml_error->addChild( 'id', '#pgsqldatabasehost' );
					$xml_error->addChild( 'message', $e->getMessage() );
				}
			}
		}
		$xml = $xml->asXML();
		ob_clean();
		header( "Content-type: application/xml" );
		header( "Cache-Control: no-cache" );
		print $xml;
	}

	/**
	 * Validate database credentials for SQLite
	 * Try to connect and verify if database name exists
	 */
	public function ajax_check_sqlite_credentials()
	{
		$db_file = $_POST['file'];
		$xml = new SimpleXMLElement( '<response></response>' );
		// Missing anything?
		if ( !isset( $db_file ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#databasefile' );
			$xml_error->addChild( 'message', _t( 'The database file was left empty.' ) );
		}
		if ( !isset( $xml_error ) ) {
			if ( $db_file == basename( $db_file ) ) { // The filename was given without a path
				$db_file = Site::get_path( 'user', true ) . $db_file;
			}
			if ( ! is_writable( dirname( $db_file ) ) ) {
				$xml->addChild( 'status', 0 );
				$xml_error = $xml->addChild( 'error' );
				$xml_error->addChild( 'id', '#databasefile' );
				$xml_error->addChild( 'message', _t( 'Cannot write to %s directory. SQLite requires that the directory that holds the DB file be writable by the web server.', array( dirname( $db_file ) ) ) );
			}
			elseif ( file_exists( Site::get_path( 'user', true ) . $db_file ) && ( ! is_writable( Site::get_path( 'user', true ) . $db_file ) ) ) {
				$xml->addChild( 'status', 0 );
				$xml_error = $xml->addChild( 'error' );
				$xml_error->addChild( 'id', '#databasefile' );

				$xml_error->addChild( 'message', _t( 'Cannot write to %s. The SQLite data file is not writable by the web server.', array( $db_file ) ) );
			}
			else {
				// Can we connect to the DB?
				$pdo = 'sqlite:' . $db_file;
				$connect = DB::connect( $pdo, null, null );

				// Disconnect, but no longer delete the file - it could already have contents!
				DB::disconnect();

				switch ( $connect ) {
					case true:
						// We were able to connect to an existing database file.
						$xml->addChild( 'status', 1 );
						break;
					default:
						// We can't create the database file, send an error message.
						$xml->addChild( 'status', 0 );
						$xml_error = $xml->addChild( 'error' );
						// TODO: Add error codes handling for user-friendly messages
						$xml_error->addChild( 'id', '#databasefile' );
						$xml_error->addChild( 'message', $connect->getMessage() );
				}
			}
		}
		$xml = $xml->asXML();
		ob_clean();
		header( "Content-type: application/xml" );
		header( "Cache-Control: no-cache" );
		print $xml;
	}

	/**
	 * Sets up install handler variables from an existing DB connection (registry)
	 *
	 * @return void
	 */
	private function set_handler_vars_from_db_connection()
	{
		list( $this->handler_vars['db_type'], $remainder )= explode( ':', Config::get( 'db_connection' )->connection_string );
		switch ( $this->handler_vars['db_type'] ) {
			case 'sqlite':
				// SQLite uses less info.
				// we stick the path in db_host
				$this->handler_vars['db_file'] = $remainder;
				break;
			case 'mysql':
				list( $host,$name ) = explode( ';', $remainder );
				list( $discard, $this->handler_vars['db_host'] ) = explode( '=', $host );
				list( $discard, $this->handler_vars['db_schema'] ) = explode( '=', $name );
				break;
			case 'pgsql':
				list( $host,$name )= explode( ' ', $remainder );
				list( $discard, $this->handler_vars['db_host'] ) = explode( '=', $host );
				list( $discard, $this->handler_vars['db_schema'] ) = explode( '=', $name );
				break;
		}
		$this->handler_vars['db_user'] = Config::get( 'db_connection' )->username;
		$this->handler_vars['db_pass'] = Config::get( 'db_connection' )->password;
		$this->handler_vars['table_prefix'] = Config::get( 'db_connection' )->prefix;
	}

}
?>
