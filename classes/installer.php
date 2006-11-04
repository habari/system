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
		global $db;
		
		$db->get_row("SELECT * FROM habari__options LIMIT 1;");
		$db->clear_errors();
		return $db->queryok;
	}
	
	/**
	 * function install
	 * Installs base tables and starter data.
	 */
	static function install()
	{
		if(self::is_installed()) return true;
		// are we processing the POST?
		if ('install' == $_POST['action'])
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
		global $db, $db_connection;

		// determine the database type
		list($dbtype,$other) = explode( ':', $db_connection['connection_string'], 2 );
		// load the proper schema
		require_once HABARI_PATH . '/system/schema/schema.' . $dbtype . '.php';
		// create the tables
		foreach ($queries as $query)
		{
			$db->query($query);
		}

		// Create the default options
		$options = Options::o();
		
		$options->installed = true;
		
		$options->title = $_POST['title'];
		$options->tag_line = $_POST['tagline'];
		$options->about = $_POST['about'];
		$base_url = $_SERVER['REQUEST_URI'];
		if(substr($base_url, -1, 1) != '/') $base_url = dirname($base_url) . '/';
		$options->base_url = $base_url;
		$options->theme_dir = "k2";
		$options->version = "0.1alpha";

		// insert a default admin user
		$password = sha1($_POST['password']);
		$admin = new User(array (
			'username'=>$_POST['username'],
			'nickname'=>'admin',
			'email'=>$_POST['email'],
			'password'=>$password
		));
		$admin->insert();

		// Insert a post record
		Post::create(array(
			'title'=>'First Post',
			'content'=>'This is my first post',
			'author'=>$admin->username,
			'status'=>'publish',
		));
		
		// generate a random-ish number to use as the salt for
		// a SHA1 hash that will serve as the unique identifier for
		// this installation.  Also for use in cookies
		$options->GUID = sha1($base_url . Utils::nonce());
			
		// Output any errors
		if($db->has_errors()) {
			Utils::debug('Errors:', $db->get_errors());
		}
		echo "<p>Congratulations, Habari is now installed!</p>";
		echo "<p>Click <a href='$base_url'>here</a> to continue.</p>";
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
