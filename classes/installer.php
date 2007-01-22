<?php
/**
 * Habari Installer Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class Installer
{

	static function is_installed()
	{
		DB::get_row('SELECT * FROM ' . DB::o()->options . ' LIMIT 1;');
		DB::clear_errors();
		return DB::o()->queryok;
	}
	
	/**
	 * function install
	 * Installs base tables and starter data.
	 */
	static function install()
	{
		if(self::is_installed()) return true;
		// are we processing the POST?
		if ( isset($_POST['action']) && ( 'install' == $_POST['action'] ) )
		{
			self::installhandler();
			return true;
		}

		// if we got here, we need to interact with the user
		echo "<p>Welcome to <strong>Habari</strong>!  Answer the questions below to get started.</p>";
		echo "<form method='post'><input type='hidden' name='action' value='install' />";
		echo "<input type='text' size='40' name='title' value='Blog Title' /><br />";
		echo "<input type='text' size='40' name='tagline' value='Tagline' /><br />";
		echo "<input type='text' size='40' name='about' value='About this blog' /><br />";
		echo "<input type='text' size='40' name='username' value='Username' /><br />";
		echo "<input type='text' size='40' name='email' value='user@email.com' /><br />";
		echo "<input type='text' size='40' name='password' value='Password' /><br />";
		echo "<input type='submit' value='GO!' />";
		die;

	}

	static function installhandler()
	{
		global $db_connection;

		// determine the database type
		list($dbtype,$other) = explode( ':', $db_connection['connection_string'], 2 );
		// assign schema file to variable
		$schema_file = HABARI_PATH . '/system/schema/schema.' . $dbtype . '.php';
		// load the proper schema if it exists
		if ( file_exists($schema_file) )
		{
			require_once( $schema_file );
		} else
		{
			$error = "<p><strong>ERROR!  There is no schema file!</strong><br /><br />";
			$error .= "You have selected " . $dbtype . " as the database engine for use with Habari, but the installer cannot find the schema file that describes the database.<br />\nThe file should be:<br />\n";
			$error .= "&nbsp;&nbsp;&nbsp;<code>" . $schema_file . "</code></p>";
			die( $error ); // FixMe: we need proper error messaging class
		}
		// create the tables
		DB::clear_errors();
		foreach ($queries as $query)
		{
			DB::query($query);
			if ( DB::has_errors() )
			{
				Error::raise('There was an error creating the Habari database tables. Installation halted.');
			}
		}

		// Create the default options
		$options = Options::o();
		
		$options->installed = true;
		
		$options->title = $_POST['title'];
		$options->tagline = $_POST['tagline'];
		$options->about = $_POST['about'];
		$options->base_url = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1);
		$options->theme_dir = 'k2';
		$options->version = '0.1alpha';
		$options->pagination = '5';

		// insert a default admin user
		$password = sha1($_POST['password']);
		$admin = new User(array (
			'username'=>$_POST['username'],
			'email'=>$_POST['email'],
			'password'=>$password
		));
		$admin->insert();

		// Insert a post record
		Post::create(array(
			'title'=>'First Post',
			'content'=>'This is my first post',
			'user_id'=>1,
			'status'=>1,
		));
		
		// generate a random-ish number to use as the salt for
		// a SHA1 hash that will serve as the unique identifier for
		// this installation.  Also for use in cookies
		$options->GUID = sha1($options->base_url . Utils::nonce());
			
		// Output any errors
		if(DB::has_errors()) {
			Utils::debug('Errors:', DB::get_errors());
		}
		echo "<p>Congratulations, Habari is now installed!</p>";
		echo "<p>Click <a href='{$options->host_url}'>here</a> to continue.</p>";
		die;
	}
	
	static function get_schema()
	{
		global $db_connection;
		list($dbtype,$other) = explode( ':', $db_connection['connection_string'], 2 );
		$schema = file_get_contents(HABARI_PATH . '/system/schema/schema.' . $dbtype);
		return $schema;
	}

}


?>
