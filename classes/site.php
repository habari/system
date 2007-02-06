<?php
/**
 * Habari Site class
 * Contains functions for getting details about the site directories and URLs.
 *
 * @package Habari
 */
class Site
{
	const CONFIG_LOCAL = 0;
	const CONFIG_SUBDIR = 1;
	const CONFIG_SUBDOMAIN = 2;

	// this variable holds the path to the config.php file
	static $config_path;
	static $config_dir;	
	static $config_type = Site::CONFIG_LOCAL;

	/**
	 * Site constructor
	 * This class should not be instantiated.
	 **/	 	 	
	private function __construct()
	{
	}


	/**
	 * returns the full filesystem path to the config file to use
	 *
	 * @return string the filesystem path to the config file
	**/
	static function get_config_path()
	{
		if ( self::$config_path )
		{
			// shortcut for subsequent calls
			return self::$config_path;
		}

		// use this, by default
		self::$config_path= HABARI_PATH;

		// get an array of directories in /user/sites/ that
		// contain a config.php file
		$config_dirs = preg_replace('/^' . preg_quote(HABARI_PATH, '/') . '\/user\/sites\/(.*)\/config.php/', '$1', glob(HABARI_PATH . '/user/sites/*/config.php') );

		if ( empty($config_dirs ) )
		{
			// no site-specific configurations exists
			// use the default
			return self::$config_path;
		}
		$server= explode('.', $_SERVER['SERVER_NAME']);
		if ( isset( $_SERVER['SERVER_PORT'] )
			&& ( 80 != $_SERVER['SERVER_PORT'] )
			&& ( 443 != $_SERVER['SERVER_PORT'] ) )
		{
			array_unshift($server, $_SERVER['SERVER_PORT'] . '.');
		}
		$request= explode('/', trim($_SERVER['REQUEST_URI'], '/') );
		
		// walk through the potential directories looking for a match
		// step 1: walk the path
		for ($x= count($request); $x >= 0; $x--)
		{
			//step 2: walk the host
			for ($y= count($server); $y > 0; $y--)
			{
				$match= trim(implode('.', array_slice($server, -$y)) . '.' . implode('.', array_slice($request, 0, $x)), '.');
				if (in_array($match, $config_dirs) )
				{
					self::$config_dir= $match;
					self::$config_path= HABARI_PATH . '/user/sites/' . self::$config_dir;
					if ( $x > 0 ) {
						self::$config_type = Site::CONFIG_SUBDIR;
					}
					else {
						self::$config_type = Site::CONFIG_SUBDOMAIN;
					}
					break 2;
				}
			}
		}
		return self::$config_path;
	}
	
	/**
	 * Returns the path relative to HABARI_PATH to the config file to use
	 *
	 * @return string the relative path to the config file
	**/
	public static function get_config_dir()
	{
		self::get_config_path();
		return self::$config_dir;
	}
	
	/**
	 * Returns the type of config that has been applied, local, subdirectory, or subdomain
	 *
	 * @return integer A Site::CONFIG_* constant representing the type of config applied.
	**/
	public static function get_config_type()
	{
		self::get_config_path();
		return self::$config_type;
	}
	
	/**
	 * Returns the host used to answer the request
	 *
	 * @return string The protocol, hostname and port of the request 
	**/
	public static function get_host()
	{
		// If we're running on a port other than 80, add the port number
		// to the value returned from host_url
		$port= 80; // Default in case not set.
		if ( isset( $_SERVER['SERVER_PORT'] ) ) {
			$port= $_SERVER['SERVER_PORT'];
		}
		$portpart = '';
		if ( $port != 80 ) {
			$portpart= ":{$port}";
		}

		return Utils::end_in_slash('http://' . Options::get('hostname') . $portpart);
	}
	
	/**
	 * Returns the URL to the user directory based on the config
	 *
	 * @return string The URL to the user directory
	**/
	public static function get_user_url()
	{
		$host = Site::get_host();
		$url = $host . Site::get_base_url() . '/' . Site::get_user_dir();
		return $url; 
	}
	
	
	/**
	 * Returns the URL relative to the site root to the directory containing the core index.php file
	 *
	 * @return string The URL path containing the core index.php file
	**/
	public static function get_base_url()
	{
		$dir = dirname($_SERVER["SCRIPT_NAME"]);
		if( $dir == '\\' ) {
			$dir = '';
		}
		return $dir;
	}
	
	/**
	 * Returns the path relative to HABARI_PATH to the user directory based on the config type
	 *
	 * @return string The path to the user directory
	**/
	public static function get_user_dir()
	{
		switch(self::get_config_type()) {
		case Site::CONFIG_LOCAL:
			return 'user';
		default:
			return 'user/sites/' . Site::get_config_dir();
		}	
	}
}

?>
