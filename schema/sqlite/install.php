<?php

class SQLiteInstall extends InstallSchema {
	
	public function info()
	{
		return array();
	}

	public function action_install_form_installform( $form, $theme )
	{
		$form->databasesetup_options->append('text', 'databasefile', 'null:null', _t('Data file'));
		$form->databasefile->value = $theme->databasefile;
		$form->databasefile->required = true;
		$form->databasefile->help = _t('<strong>Data file</strong> is the SQLite file that will store 
		your Habari data.  This should be the complete path to where your data file 
		resides.') . ' <a href="#"> '. _t('Learn More...') . '</a>';
	}

	public function action_install_complete( $theme )
	{
		if ( $db_type == 'sqlite' ) {
			if ( !$this->secure_sqlite() ) {
				$this->handler_vars['sqlite_contents'] = implode( "\n", $this->sqlite_contents() );
				$this->display( 'sqlite' );
			}
		}
	}

	public function action_install_existing_config( $theme, $remainder )
	{
		// SQLite uses less info.
		// we stick the path in db_host
		$theme->handler_vars['db_file']= $remainder;
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
		if ( FALSE === strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) ) {
			// .htaccess is only needed on Apache
			return false;
		}
		if ( !file_exists( HABARI_PATH . '/.htaccess') ) {
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
		$current_files_contents = file_get_contents( HABARI_PATH . DIRECTORY_SEPARATOR . '.htaccess');
		if ( FALSE === strpos( $current_files_contents, $files_contents ) ) {
			// If not, append the files clause to the .htaccess file
			if ( $fh = fopen( HABARI_PATH . DIRECTORY_SEPARATOR . '.htaccess', 'a' ) ) {
				if ( FALSE === fwrite( $fh, $files_contents ) ) {
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

	/**
	 * Validate database credentials for SQLite
	 * Try to connect and verify if database name exists
	 */
	public function ajax_check_sqlite_credentials() {
		$db_file = $_POST['file'];
		$xml = new SimpleXMLElement('<response></response>');
		// Missing anything?
		if ( !isset( $db_file ) ) {
			$xml->addChild( 'status', 0 );
			$xml_error = $xml->addChild( 'error' );
			$xml_error->addChild( 'id', '#databasefile' );
			$xml_error->addChild( 'message', _t('The database file was left empty.') );
		}
		if ( !isset( $xml_error ) ) {
			if ( ! is_writable( dirname( $db_file ) ) ) {
				$xml->addChild( 'status', 0 );
				$xml_error = $xml->addChild( 'error' );
				$xml_error->addChild( 'id', '#databasefile' );
				$xml_error->addChild( 'message', _t('SQLite requires that the directory that holds the DB file be writable by the web server.') );
			} elseif ( file_exists( $db_file ) && ( ! is_writable( $db_file ) ) ) {
				$xml->addChild( 'status', 0 );
				$xml_error = $xml->addChild( 'error' );
				$xml_error->addChild( 'id', '#databasefile' );

				$xml_error->addChild( 'message', _t('The SQLite data file is not writable by the web server.') );
			} else {
				// Can we connect to the DB?
				$pdo = 'sqlite:' . $db_file;
				$connect = DB::connect( $pdo, null, null );

				// Don't leave empty files laying around
				DB::disconnect();
				if ( file_exists( $db_file ) ) {
					unlink($db_file);
				}

				switch ($connect) {
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
		header("Content-type: text/xml");
		header("Cache-Control: no-cache");
		print $xml;
	}

}

?>