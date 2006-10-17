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
		global $db, $options;
		
		if(self::is_installed()) return true;
		
		// Create the tables
		foreach(self::get_schema() as $query) {
			$db->query($query);
		}
		
		// Insert a few post records
		
		Posts::create(array(
			'title'=>'First Post',
			'guid'=>'tag:localhost/first-post/4981704',
			'content'=>'This is my first post',
			'author'=>'owen',
			'pubdate'=>'2006-10-04 17:17:00',
			'status'=>'publish',
		));

		Posts::create(array(
			'title'=>'Second Post',
			'guid'=>'tag:localhost/second-post/7407395',
			'content'=>'This is my second post',
			'author'=>'owen',
			'pubdate'=>'2006-10-04 17:18:00',
			'status'=>'publish',
		));
			
		Posts::create(array (
			'title'=>'Third Post',
			'guid'=>'tag:localhost/third-post/4981704',
			'content'=>'This is my third post',
			'author'=>'owen',
			'pubdate'=>'2006-10-04 17:19:00',
			'status'=>'publish',
		));

		// insert a default admin user
		$password = sha1('password');
		$admin = new User(array (
			'username'=>'admin',
			'email'=>'admin@localhost',
			'password'=>$password
		));
		$admin->insert();
		
		$options->installed = true;
		
		$options->blog_title = "Habari Whitespace";
		$options->tag_line = "Spread the News";
		$options->about = "This is a test install of Habari";
		$base_url = $_SERVER['REQUEST_URI'];
		if(substr($base_url, -1, 1) != '/') $base_url = dirname($base_url) . '/';
		$options->base_url = $base_url;
			
		// Output any errors
		if($db->has_errors()) {
			Utils::debug('Errors:', $db->get_errors());
		}
	}
	
	static function get_schema()
	{
		$queries = array (
			'CREATE TABLE habari__posts ( 
				slug VARCHAR(255) NOT NULL PRIMARY KEY, 
				title VARCHAR(255), 
				guid VARCHAR(255) NOT NULL, 
				content LONGTEXT, 
				author VARCHAR(255) NOT NULL, 
				status VARCHAR(50) NOT NULL, 
				pubdate TIMESTAMP, 
				updated TIMESTAMP
			);',
			'CREATE TABLE habari__options (
			  name   varchar(50) PRIMARY KEY NOT NULL UNIQUE,
			  type   integer DEFAULT 0,
			  value  blob
			);',
			'CREATE TABLE habari__users (
			  username	varchar(20) PRIMARY KEY NOT NULL UNIQUE,
			  email		varchar(30) NOT NULL,
			  password	varchar(40) NOT NULL
			);'
		);
		return $queries;
	}

}


?>
