<?php

class MySQLInstall extends Plugin {
	
	public function info()
	{
		return array();
	}
	
	public function load()
	{
		Stack::add('installer_javascript', array($this->get_url().'/mysqlinstall.js'));
		parent::load();
	}

	public function action_install_form_installform( $form, $theme )
	{
		$form->db_type->options['mysql'] = 'MySQL';
		$form->db_type->selected = 'mysql';
		
		$form->databasesetup_options->append('fieldset', 'mysqlsettings', _t('MySQL Settings'));
		
		$form->mysqlsettings->append('text', 'mysqldatabasehost', 'null:null', _t('Database Host'));
		$form->mysqldatabasehost->value = $theme->mysqldatabasehost;
		$form->mysqldatabasehost->required = true;
		$form->mysqldatabasehost->help = _t('<strong>Database Host</strong> is the host (domain) name or server IP
		address of the server that runs the MySQL database to
		which Habari will connect.  If MySQL is running on your web server,
		and most of the time it is, "localhost" is usually a good value
		for this field.') . ' <a href="#"> '. _t('Learn More...') . '</a>';
		
		$form->mysqlsettings->append('text', 'mysqldatabaseuser', 'null:null', _t('Username'));
		$form->mysqldatabaseuser->value = $theme->mysqldatabaseuser;
		$form->mysqldatabaseuser->required = true;
		$form->mysqldatabaseuser->help = _t('<strong>Database User</strong> is the username used to connect Habari
		to the MySQL database.') . ' <a href="#"> '. _t('Learn More...') . '</a>';
		
		$form->mysqlsettings->append('text', 'mysqldatabasepass', 'null:null', _t('Password'));
		$form->mysqldatabasepass->value = $theme->mysqldatabasepass;
		$form->mysqldatabasepass->required = true;
		$form->mysqldatabasepass->help = _t('<strong>Database Password</strong> is the password used to connect
		the specified user to the MySQL database.') . ' <a href="#"> '. _t('Learn More...') . '</a>';
		
		$form->mysqlsettings->append('text', 'mysqldatabasename', 'null:null', _t('Database Name'));
		$form->mysqldatabasename->value = $theme->mysqldatabasename;
		$form->mysqldatabasename->required = true;
		$form->mysqldatabasename->help = _t('<strong>Database Name</strong> is the name of the MySQL database to
		which Habari will connect.') . ' <a href="#"> '. _t('Learn More...') . '</a>';
	}
	/*
	public function action_install_existing_config( $theme, $remainder )
	{
		list($host,$name)= explode(';', $remainder);
		list($discard, $theme->handler_vars['db_host'])= explode('=', $host);
		list($discard, $theme->handler_vars['db_schema'])= explode('=', $name);
	}
	*/
	public function action_ajax_check_mysql_credentials() {
		$xml = new SimpleXMLElement('<response></response>');
		// Missing anything?
		if ( !isset( $_POST['host'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#mysqldatabasehost' );
			$xml_error->addChild( 'message', _t('The database host field was left empty.') );
		}
		if ( !isset( $_POST['database'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#mysqldatabasename' );
			$xml_error->addChild( 'message', _t('The database name field was left empty.') );
		}
		if ( !isset( $_POST['user'] ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#mysqldatabaseuser' );
			$xml_error->addChild( 'message', _t('The database user field was left empty.') );
		}
		if ( !isset( $xml_error ) ) {
			// Can we connect to the DB?
			$pdo = 'mysql:host=' . $_POST['host'] . ';dbname=' . $_POST['database'];
			try {
				$connect = DB::connect( $pdo, $_POST['user'], $_POST['pass'] );
				$xml->addChild( 'status', 1 );
			}
			catch(Exception $e) {
				$xml->addChild( 'status', 0 );
				$xml_error = $xml->addChild( 'error' );
				if ( strpos( $e->getMessage(), '[1045]' ) ) {
					$xml_error->addChild( 'id', '#mysqldatabaseuser' );
					$xml_error->addChild( 'id', '#mysqldatabasepass' );
					$xml_error->addChild( 'message', _t('Access denied. Make sure these credentials are valid.') );
				}
				else if ( strpos( $e->getMessage(), '[1049]' ) ) {
					$xml_error->addChild( 'id', '#mysqldatabasename' );
					$xml_error->addChild( 'message', _t('That database does not exist.') );
				}
				else if ( strpos( $e->getMessage(), '[2005]' ) ) {
					$xml_error->addChild( 'id', '#mysqldatabasehost' );
					$xml_error->addChild( 'message', _t('Could not connect to host.') );
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
		header("Content-type: text/xml");
		header("Cache-Control: no-cache");
		print $xml;
	}
}
?>