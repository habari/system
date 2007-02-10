<?php
define('MIN_PHP_VERSION', '5.1.0');

/**
 * The class which responds to installer actions
 */
class InstallHandler extends ActionHandler {
  
	private $theme= null;

	/**
	 * Entry point for installation.  The reason there is a begin_install
	 * method to handle is that conceivably, the user can stop installation
	 * mid-install and need an alternate entry point action at a later time.
	 */
	public function act_begin_install()
	{
		// set the default values now, which will be overriden as we go
		$this->form_defaults();

		// Create a new theme to handle the display of the installer
		$this->theme= Themes::create('installer', 'RawPHPEngine', HABARI_PATH . '/system/installer/');
		if (! $this->meets_all_requirements())
		{
			$this->display('requirements');
		}

		/*
		 * OK, so requirements are met.
		 * Let's check the config.php file if no POST data was submitted
		*/
		if ( (! file_exists(Site::get_config()) ) && ( ! isset($_POST['db_user']) ) )
		{
			// no config file, and no HTTP POST
			$this->display('db_setup');
		}

		// we got here, so we either have a config file, or an HTTP POST

		// try to load any values that might be defined in config.php
		if ( file_exists( Site::get_config() ) ) {
			include( Site::get_config() );
			if ( isset( $db_connection ) ) {
				list($this->handler_vars['db_type'],$remainder)= explode(':', $db_connection['connection_string']);
				if ('sqlite' == $this->handler_vars['db_type'] ) {
					// SQLite uses less info.
					// we stick the path in db_host
					$this->handler_vars['db_host']= $remainder;
				} else {
					list($host,$name)= explode(';', $remainder);
					list($discard, $this->handler_vars['db_host'])=
explode('=', $host);
					list($discard, $this->handler_vars['db_schema'])= explode('=', $name);
					$this->handler_vars['db_user']= $db_connection['username'];
					$this->handler_vars['db_pass']= $db_connection['password'];
					$this->handler_vars['table_prefix']= $db_connection['prefix'];
				}
			}
			// if a $blog_data array exists in config.php, use it
			// to pre-load values for the installer
			// ** this is completely optional **
			if ( isset( $blog_data ) ) {
				foreach ( $blog_data as $blog_datum => $value ) {
					$this->handler_vars[$blog_datum]= $value;
				}
			}
		}

		// now merge in any HTTP POST values that might have been sent
		// these will override the defaults and the config.php values
		$this->handler_vars= array_merge($this->handler_vars, $_POST);
		
		// quick hack to set the SQLite datafile value to $db_host
		if ( isset( $this->handler_vars['db_file'] ) ) {
			$this->handler_vars['db_host']= $this->handler_vars['db_file'];
		}

		// we need details for the admin user to install
		if ( ( '' == $this->handler_vars['admin_username'] )
			|| ( '' == $this->handler_vars['admin_pass'] )
			|| ( '' == $this->handler_vars['admin_email']) )
		{
			// if none of the above are set, display the form
			$this->display('db_setup');
		}

		// we got here, so we have all the info we need to install
		// first, try to write the config file
		if (! $this->write_config_file())
		{
			$this->theme->assign('form_errors', array('write_file'=>'Could not write config.php file...'));
			$this->display('db_setup');
		}

		// try to install the database
		if (! $this->install_db())
		{
			// the installation failed for some reason.
			// re-display the form
			$this->display('db_setup');
		}
		return true;
	}

	/**
	 * Helper function to remove code repetition
	 *
	 * @param template_name Name of template to use
	 */
	private function display($template_name)
	{
		foreach ($this->handler_vars as $key=>$value)
		{
			$this->theme->assign($key, $value);
		}
		$this->theme->display($template_name);
		exit;
	}

	/*
	 * sets default values for the form
	 */
	public function form_defaults() {
		$formdefaults['db_type'] = 'mysql';
		$formdefaults['db_host'] = 'localhost';
		$formdefaults['db_user'] = '';
		$formdefaults['db_pass'] = '';
		$formdefaults['db_schema'] = 'habari';
		$formdefaults['table_prefix'] = isset($GLOBALS['db_connection']['prefix']) ? $GLOBALS['db_connection']['prefix'] : '';
		$formdefaults['admin_username'] = 'admin';
		$formdefaults['admin_pass'] = '';
		$formdefaults['blog_title'] = 'My Habari';
		$formdefaults['admin_email'] = '';

		foreach( $formdefaults as $key => $value ) {
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
		$requirements_met= true;

		/* Check that directory to write config.php is writeable */
		$local_writeable= is_writeable(HABARI_PATH) || file_exists(HABARI_PATH . '/config.php');
		$this->theme->assign('local_writeable', $local_writeable);
		$this->theme->assign('HABARI_PATH', HABARI_PATH);
		if (! $local_writeable)
		{
			$requirements_met= false;
		}
		/* Check versions of PHP */
		$php_version_ok= version_compare(phpversion(), MIN_PHP_VERSION, '>=');
		$this->theme->assign('php_version_ok', $php_version_ok);
		$this->theme->assign('PHP_OS', PHP_OS);;
		$this->theme->assign('PHP_VERSION',  phpversion());
		if (! $php_version_ok)
		{
			$requirements_met= false;
		}
		/* Check for PDO extension */
		$pdo_extension_ok= extension_loaded('pdo');
		$this->theme->assign('pdo_extension_ok', $pdo_extension_ok);
		if (! $pdo_extension_ok)
		{
			$requirements_met= false;
		}
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
		$db_host= $this->handler_vars['db_host'];
		$db_type= $this->handler_vars['db_type'];
		$db_schema= $this->handler_vars['db_schema'];
		$db_user= $this->handler_vars['db_user'];
		$db_pass= $this->handler_vars['db_pass'];

		if ( 'sqlite' != $db_type ) {
			// databases other than SQLite require specific connection information
			if (empty($db_user))
			{
				$this->theme->assign('form_errors', array('db_user'=>'User is required.'));
				return false;
			}
			if (empty($db_schema))
			{
				$this->theme->assign('form_errors', array('db_schema'=>'Name for database is required.'));
				return false;
			}
			if (empty($db_host))
			{
				$this->theme->assign('form_errors', array('db_host'=>'Host is required.'));
				return false;
			}
		} else {
		// If this is a SQLite database, let's check that the file
		// exists and that we can access it.
			if ( ! $this->check_sqlite() ) {
				return false;
			}
                }

		if (! $this->connect_to_existing_db())
		{
			$this->theme->assign('form_errors', array('db_user'=>'Problem connecting to supplied database credentials'));
			return false;
		}

		DB::begin_transaction();
		/* Let's install the DB tables now. */ 
		$create_table_queries= $this->get_create_table_queries();
		foreach ($create_table_queries as $query)
		{
			if (! DB::query($query))
			{
				$error= DB::get_last_error();
				$this->theme->assign('form_errors', array('db_host'=>'Could not create schema tables...' . $error['message']));
				DB::rollback();
				return false;
			}
		}

		/* Cool.  DB installed.  Let's setup the admin user now. */
		if (! $this->create_admin_user())
		{
			$this->theme->assign('form_errors', array('admin_user'=>'Problem creating admin user.'));
			DB::rollback();
			return false;
		}
  
		/* Create the default options */
		if (! $this->create_default_options())
		{
			$this->theme->assign('form_errors', array('options'=>'Problem creating default options'));
			DB::rollback();
			return false;
		}

		DB::commit();
		return true;
	}

	/**
	 * Checks for the existance of a SQLite datafile
	 * tries to create it if it does not exist
	**/
	private function check_sqlite() {
		$db_file = $this->handler_vars['db_host'];
		if ( file_exists( $db_file ) && is_writable( $db_file ) ) {
			// the file exists, and it writable.  We're all set
			return true;
		}

		// try to figure out what the problem is.
		if ( file_exists( $db_file ) && ! is_writable( $db_file ) ) {
			$this->theme->assign('form_errors', array('db_file'=>'The SQLite data file is not writable.') );
			return false;
		}

		if ( ! file_exists( $db_file ) ) {
			// let's see if the directory is writable
			// so that we could create the file
			$dir= dirname( $db_file );
			if ( ! is_writable( $dir ) ) {
				$this->theme->assign('form_errors', array('db_file'=>'The SQLite data file does not exist, and it cannot be created in the specified directory.') );
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
		$db_user= $this->handler_vars['db_user'];
		$db_pass= $this->handler_vars['db_pass'];
		$db_host= $this->handler_vars['db_host'];
		$db_type= $this->handler_vars['db_type'];
		$db_schema= $this->handler_vars['db_schema'];

		/* Create a PDO connection string based on the database type */
		if ( 'sqlite' == $db_type ) {
			$connect_string= $db_type . ':' . $db_host;
		} else {
			$connect_string= $db_type . ':host=' . $db_host . ';dbname=' . $db_schema;
		}

		/* Reset the global table prefix */
		$GLOBALS['db_connection']['prefix']= $this->handler_vars['table_prefix'];

		/* Attempt to connect to the database host */
		return DB::connect($connect_string, $db_user, $db_pass);
	}

	/**
	 * Creates the administrator user from form information
	 *
	 * @return  bool  Creation successful?
	 */
	private function create_admin_user()
	{
		$admin_username= $this->handler_vars['admin_username'];
		$admin_email= $this->handler_vars['admin_email'];
		$admin_pass= $this->handler_vars['admin_pass'];

		if ($admin_pass{0} == '{') {
			// looks like we might have a crypted password
			$password= $admin_pass;

			// but let's double-check
			$algo = strtolower( substr( $admin_pass, 1, 3) );
			if ( ('ssh' != $algo) && ( 'sha' != $algo) ) {
				// we do not have a crypted password
				// so let's encrypt it
				$password= Utils::crypt($admin_pass);
			 }
		} else {
			$password= Utils::crypt($admin_pass);
		}

		$admin= new User(array (
			'username'=>$admin_username,
			'email'=>$admin_email,
			'password'=>$password
		));
		$admin->insert();
		/** @todo Why is the User an insert() call and Post a create() call? */
		// Insert a post record
		Post::create(array(
			'title'=>'First Post',
			'content'=>'This is my first post',
			'user_id'=>1,
			'status'=>1,
			'tags'=>'deleteme',
		));
		
		// generate a random-ish number to use as the salt for
		// a SHA1 hash that will serve as the unique identifier for
		// this installation.  Also for use in cookies
		$options->GUID = sha1(Controller::get_base_url() . Utils::nonce());
		return true; 
	}

	/**
	 * Write the default options
	 */
	private function create_default_options()
	{
		// Create the default options
		
		Options::set('installed', true);
		
		Options::set('title', $this->handler_vars['blog_title']);
		Options::set('base_url', substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1));
		Options::set('version', '0.1alpha');
		Options::set('pagination', '5');

		Options::set('GUID', sha1(Options::get('base_url') . Utils::nonce()));
		return true;
	}

	/**
	 * Install schema tables from the respective RDBMS schema
	 */
	private function get_create_table_queries()
	{
		$table_prefix= $this->handler_vars['table_prefix'];
		$db_type= $this->handler_vars['db_type'];
		$db_schema= $this->handler_vars['db_schema'];

		/* Grab the queries from the RDBMS schema file */
		$file_path= HABARI_PATH . '/system/schema/schema.' . $db_type . '.sql';
		$schema_sql= trim(file_get_contents($file_path), "\r\n ");
		$schema_sql= str_replace('{$schema}',$db_schema, $schema_sql);
		$schema_sql= str_replace('{$prefix}',$table_prefix, $schema_sql);

		/* 
		 * Just in case anyone creates a schema file with separate statements
		 * not separated by two newlines, let's clean it here...
		 * Likewise, let's clean up any separations of *more* than two newlines
		 */
		$schema_sql= preg_replace("/;\n{1}([^\n])/", ";\n\n$1", $schema_sql);
		$schema_sql= preg_replace("/\n{3,}/","\n\n", $schema_sql);
		$queries= explode("\n\n", $schema_sql);
		return $queries;
	}

	/**
	 * Returns an RDMBS-specific CREATE SCHEMA plus user SQL expression(s)
	 *
	 * @return  string[]  array of SQL queries to execute
	 */
	private function get_create_schema_and_user_queries()
	{
		$db_host= $this->handler_vars['db_host'];
		$db_type= $this->handler_vars['db_type'];
		$db_schema= $this->handler_vars['db_schema'];
		$db_user= $this->handler_vars['db_user'];
		$db_pass= $this->handler_vars['db_pass'];

		$queries= array();
		switch ($db_type)
		{
		case 'mysql':
			$queries[]= 'CREATE DATABASE `' . $db_schema . '`;';
			$queries[]= 'GRANT ALL ON `' . $db_schema . '`.* TO \'' . $db_user . '\'@\'' . $db_host . '\' ' . 
			'IDENTIFIED BY \'' . $db_pass . '\';';
			break;
		default:
			die('currently unsupported.');
		}
		return $queries;
	}

	/**
	 * Writes the configuration file with the variables needed for 
	 * initialization of the application
	 *
	 * @return  bool  Did the file get written?
	 */
	private function write_config_file()
	{
		$db_host= $this->handler_vars['db_host'];
		$db_type= $this->handler_vars['db_type'];
		$db_schema= $this->handler_vars['db_schema'];
		$db_user= $this->handler_vars['db_user'];
		$db_pass= $this->handler_vars['db_pass'];
		$table_prefix= $this->handler_vars['table_prefix'];

		if ( 'sqlite' == $db_type ) {
			$connection_string= "$db_type:$db_host";
		} else {
			$connection_string= "$db_type:host=$db_host;dbname=$db_schema";
		}
		if ( file_exists( Site::get_config() ) )
		{
			include( Site::get_config() );
			if ( isset($db_connection) && ( $db_connection['connection_string'] ==
					$connection_string )
				&& ( $db_connection['username'] ==
					$db_user )
				&& ( $db_connection['password'] ==
					$db_pass )
				&& ( $db_connection['prefix'] ==
					$table_prefix ) )
			{
				// don't bother writing anything, the supplied
				// credentials are the same
				return true;
			}
		}

		$placeholders= array(
			'{$db_host}'
			, '{$db_type}'
			, '{$db_schema}'
			, '{$db_user}'
			, '{$db_pass}'
			, '{$table_prefix}'
		);

		$replacements= array(
			$db_host
			, $db_type
			, $db_schema
			, $db_user
			, $db_pass
			, $table_prefix
		);
  
		if (! ($file_contents= file_get_contents(HABARI_PATH . '/system/installer/config.php.tpl'))) {
			return false;
		}
		$file_contents= str_replace($placeholders, $replacements, $file_contents);
		if ($file= @fopen(Site::get_config(), 'w')) {
			if (fwrite($file, $file_contents, strlen($file_contents))) {
				fclose($file);
			}
			return true;      
		}
		$this->handler_vars['config_file']= HABARI_PATH . Site::get_config_dir() . '/config.php';
		$this->handler_vars['file_contents']= htmlspecialchars($file_contents);
		$this->display('config');
		return false;
	}
}
?>
