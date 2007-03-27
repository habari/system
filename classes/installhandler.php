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
		 * now let's check .htaccess
		*/
		if ( ! $this->check_htaccess() )
		{
			$this->handler_vars['file_contents']= implode( "\n", $this->htaccess() );
			$this->display('htaccess');
		}

		/*
		 * Let's check the config.php file if no POST data was submitted
		*/
		if ( (! file_exists(Site::get_dir('config_file') ) ) && ( ! isset($_POST['db_user']) ) )
		{
			// no config file, and no HTTP POST
			$this->display('db_setup');
		}

		// we got here, so we either have a config file, or an HTTP POST

		// try to load any values that might be defined in config.php
		if ( file_exists( Site::get_dir('config_file') ) ) {
			include( Site::get_dir('config_file') );
			if ( isset( $db_connection ) ) {
				list( $this->handler_vars['db_type'], $remainder )= explode( ':', $db_connection['connection_string'] );
				switch( $this->handler_vars['db_type'] ) {
				case 'sqlite':
					// SQLite uses less info.
					// we stick the path in db_host
					$this->handler_vars['db_file']= $remainder;
					break;
				case 'mysql':
					list($host,$name)= explode(';', $remainder);
					list($discard, $this->handler_vars['db_host'])= explode('=', $host);
					list($discard, $this->handler_vars['db_schema'])= explode('=', $name);
					break;				
				}
				$this->handler_vars['db_user']= $db_connection['username'];
				$this->handler_vars['db_pass']= $db_connection['password'];
				$this->handler_vars['table_prefix']= $db_connection['prefix'];
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
		
		// we need details for the admin user to install
		if ( ( '' == $this->handler_vars['admin_username'] )
			|| ( '' == $this->handler_vars['admin_pass1'] )
			|| ( '' == $this->handler_vars['admin_pass2'] )
			|| ( '' == $this->handler_vars['admin_email']) )
		{
			// if none of the above are set, display the form
			$this->display('db_setup');
		}

		// we got here, so we have all the info we need to install

		// make sure the admin password is correct
		if ( $this->handler_vars['admin_pass1'] !== $this->handler_vars['admin_pass2'] )
		{
			$this->theme->assign( 'form_errors', array('password_mismatch'=>'Password mismatch!') );
			$this->display('db_setup');
		}

		// try to write the config file
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
		$formdefaults['db_file'] = 'habari.db';
		$formdefaults['db_schema'] = 'habari';
		$formdefaults['table_prefix'] = isset($GLOBALS['db_connection']['prefix']) ? $GLOBALS['db_connection']['prefix'] : 'habari__';
		$formdefaults['admin_username'] = 'admin';
		$formdefaults['admin_pass1'] = '';
		$formdefaults['admin_pass2'] = '';
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

		switch($db_type) {
		case 'mysql':
			// MySQL requires specific connection information
			if (empty($db_user)) {
				$this->theme->assign('form_errors', array('db_user'=>'User is required.'));
				return false;
			}
			if (empty($db_schema)) {
				$this->theme->assign('form_errors', array('db_schema'=>'Name for database is required.'));
				return false;
			}
			if (empty($db_host)) {
				$this->theme->assign('form_errors', array('db_host'=>'Host is required.'));
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

		if (! $this->connect_to_existing_db()) {
			$this->theme->assign('form_errors', array('db_user'=>'Problem connecting to supplied database credentials'));
			return false;
		}

		DB::begin_transaction();
		/* Let's install the DB tables now. */ 
		$create_table_queries= $this->get_create_table_queries();
		foreach ($create_table_queries as $query) {
			if (! DB::query($query)) {
				$error= DB::get_last_error();
				$this->theme->assign('form_errors', array('db_host'=>'Could not create schema tables...' . $error['message']));
				DB::rollback();
				return false;
			}
		}

		/* Cool.  DB installed.  Let's setup the admin user now. */
		if (! $this->create_admin_user()) {
			$this->theme->assign('form_errors', array('admin_user'=>'Problem creating admin user.'));
			DB::rollback();
			return false;
		}
	
		/* Create the default options */
		if (! $this->create_default_options()) {
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
		global $db_connection;
		if($config= $this->get_config_file()) {
			$config = preg_replace('/<\\?php(.*)\\?'.'>/ims', '$1', $config);
			// Update the $db_connection global from the config that is aobut to be written:
			eval($config);  

			/* Attempt to connect to the database host */
			return DB::connect();
		}
		// If we couldn't create the config from the template, return an error
		return false;
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
		$admin_pass= $this->handler_vars['admin_pass1'];

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

		// Insert a post record
		Post::create(array(
			'title'=>'First Post',
			'content'=>'This is my first post',
			'user_id'=>1,
			'status'=>Post::status('published'),
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
		Options::set( 'theme_name', 'k2' );
		Options::set( 'theme_dir' , HABARI_PATH . '/user/themes/k2/' );
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
		$file_path= HABARI_PATH . "/system/schema/{$db_type}/schema.sql";
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
		$queries= preg_split('/(\\r\\n|\\r|\\n)\\1/', $schema_sql);
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
	* Gets the configuration template, inserts the variables into it, and returns it as a string
	* 
	* @return string The config.php template for the db_type schema
	*/
	private function get_config_file()
	{
		if (! ($file_contents= file_get_contents(HABARI_PATH . "/system/schema/" . $this->handler_vars['db_type'] . "/config.php"))) {
			return false;
		}
		$vars= array_map('addslashes', $this->handler_vars);
		$file_contents= str_replace(
			array_map(array('Utils', 'map_array'), array_keys($vars)), 
			$vars,
			$file_contents
		);
		return $file_contents;
	}

	/**
	 * Writes the configuration file with the variables needed for 
	 * initialization of the application
	 *
	 * @return  bool  Did the file get written?
	 */
	private function write_config_file()
	{
		// first, check if a config.php file exists
		if ( file_exists( Site::get_dir('config_file' ) ) )
		{
			// set the defaults for comprison
			$db_host= $this->handler_vars['db_host']; 
			$db_type= $this->handler_vars['db_type']; 
			$db_schema= $this->handler_vars['db_schema']; 
			$db_user= $this->handler_vars['db_user']; 
			$db_pass= $this->handler_vars['db_pass']; 
			$table_prefix= $this->handler_vars['table_prefix']; 

			// set the connection string
			if ( 'sqlite' == $db_type ) { 
				// remember, we're using $db_host to define
				// the path to the SQLite data file
				$connection_string= "$db_type:$db_host"; 
			} else { 
				$connection_string= "$db_type:host=$db_host;dbname=$db_schema"; 
			} 

			// load the config.php file
			include( Site::get_dir('config_file') );
			
			// and now we compare the values defined there to
			// the values POSTed to the installer
			if ( isset($db_connection) && 
				( $db_connection['connection_string'] == $connection_string )
				&& ( $db_connection['username'] == $db_user )
				&& ( $db_connection['password'] == $db_pass )
				&& ( $db_connection['prefix'] == $table_prefix ) )
			{
				// the values are the same, so don't bother
				// trying to write to config.php
				return true;
			}
		}
		if (! ($file_contents= file_get_contents(HABARI_PATH . "/system/schema/" . $this->handler_vars['db_type'] . "/config.php"))) {
			return false;
		}
		if($file_contents= $this->get_config_file()) {
			if ($file= @fopen(Site::get_dir('config_file'), 'w')) {
				if (fwrite($file, $file_contents, strlen($file_contents))) {
					fclose($file);
					return true;
				}
			}
			$this->handler_vars['config_file']= HABARI_PATH . Site::get_dir('config') . '/config.php';
			$this->handler_vars['file_contents']= htmlspecialchars($file_contents);
			$this->display('config');
			return false;
		}
		return false;  // Only happens when config.php template does not exist.
	}

	/**
	 * returns an array of .htaccess declarations used by Habari
	**/
	public function htaccess()
	{
		return array(
			'open_block' => '### HABARI START',
			'engine_on' => 'RewriteEngine On',
			'rewrite_cond_f' => 'RewriteCond %{REQUEST_FILENAME} !-f',
			'rewrite_cond_d' => 'RewriteCond %{REQUEST_FILENAME} !-d',
			'rewrite_rule' => 'RewriteRule . index.php [PT]',
			'close_block' => '### HABARI END',
		);
	}

	/**
	 * checks for the presence of an .htaccess file
	 * invokes write_htaccess() as needed
	**/
	public function check_htaccess()
	{
		if ( FALSE === strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) )
		{
			// .htaccess is only needed on Apache
			// @TODO: add support for IIS and lighttpd rewrites
			return true;
		}
		if ( ! file_exists( HABARI_PATH . '/.htaccess') )
		{
			// no .htaccess exists.  Try to create one
			return $this->write_htaccess(FALSE);
		}
		$htaccess= file_get_contents( HABARI_PATH . '/.htaccess');
		if ( FALSE === strpos( $htaccess, 'HABARI' ) )
		{
			// the Habari block does not exist in this file
			// so try to create it
			return $this->write_htaccess(TRUE);
		}
		else
		{
			// the Habari block exists, but we need to make sure
			// it is correct.
			// @TODO: FIXME!
		}
		return true;
	}

	/**
	 * attempts to write the .htaccess file if none exists
	 * or to write the Habari-specific portions to an existing .htaccess
	 * @param bool whether an .htaccess file already exists or not
	 * @param bool whether to remove and re-create any existing Habari block
	**/
	public function write_htaccess( $exists = FALSE, $update = FALSE )
	{
		$file_contents= "\n" . implode( "\n", $this->htaccess() ) . "\n";
		if ( ! $exists )
		{
			if ( ! is_writable( HABARI_PATH ) )
			{
				// we can't create the file
				return false;
			}
		}
		else
		{
			if ( ! is_writable( HABARI_PATH . '/.htaccess' ) )
			{
				// we can't update the file
				return false;
			}
		}
		if ( ! $update )
		{
			// we're either creating a new .htaccess, or adding
			// the Habari block to an existing .htaccess which
			// previously lacked it.  As such, simply open the
			// .htaccess file in append mode, and add the contents
			if ( $fh= fopen( HABARI_PATH . '/.htaccess', 'a' ) )
			{
				if ( FALSE !== fwrite( $fh, $file_contents ) )
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			// we're updating an existing but incomplete .htaccess
			// care must be take only to remove the Habari bits
			// @TODO: FIXME!!
		}
		return true;
	}
}
?>
