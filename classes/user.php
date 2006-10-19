<?php
/**
 * Habari UserRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class User extends QueryRecord
{
	private static $me = null;  // Static storage for the currently logged-in User record

	public function __construct($paramarray = array())
	{
		// Defaults
		$this->fields = array_merge(
			array(
				'username' => '', 
				'email' => '', 
				'password' => ''
			),
			$this->fields
		);
		parent::__construct($paramarray);
	}

	/**
	* function me
	* checks for the existence of a cookie, and returns a user object of the user, if successful
	* @return user object, or false if no valid cookie exists
	**/	
	public static function me()
	{
		// Is the logged-in user not cached already?
		if ( self::$me == null ) {
			// see if there's a cookie
			if ( ! isset($_COOKIE['habari']) ) {
				// no cookie, so stop processing
				return false;
			} else {
				$cookie = "habari_" . $options->GUID;
				$username = substr($_COOKIE[$cookie], 40);
				$cookiepass = substr($_COOKIE[$cookie], 0, 40);
				// now try to load this user from the database
				$dbuser = $db->get_results("SELECT * FROM habari__users WHERE username = ?", $username);
				if ( sha1($dbuser->pass) == $cookiepass ) {
					// Cache the user in the static variable
					self::$me = new User ( 
						array(
							"username" => $dbuser->username,
							"password" => $dbuser->password,
							"email" => $dbuser->email,
						)
					);
					return self::$me;
				} else {
					return false;
				}
			}
		}
		else {
			return self::$me;
		}
	}
	
	/**
	 * function insert
	 * Saves a new user to the users table
	 */	 	 	 	 	
	public function insert()
	{
		parent::insert( 'habari__users' );
	}

	/**
	 * function update
	 * Updates an existing user in the users table
	 */	 	 	 	 	
	public function update()
	{
		parent::update( 'habari__users' );
	}

	/**
	* function remember
	* sets a cookie on the client machine for future logins
	*/
	public function remember()
	{
		// set the cookie
	}

	/** function forget
	* delete a cookie from the client machine
	*/
	public function forget()
	{
		// delete the cookie
	}

	/** function authenticate
	* checks a user's credentials to see if they are legit
	* -- calls all auth plugins BEFORE checking local database
	*/
	public function authenticate()
	{
		/*
		   execute auth plugins here
		*/
	}

}


?>
