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
	* function identify
	* checks for the existence of a cookie, and returns a user object of the user, if successful
	* @return user object, or false if no valid cookie exists
	**/	
	public static function identify()
	{
		global $db;

		// Is the logged-in user not cached already?
		if ( self::$me == null ) {
			// see if there's a cookie
			$cookie = "habari_" . $options->GUID;
			if ( ! isset($_COOKIE[$cookie]) ) {
				// no cookie, so stop processing
				return false;
			} else {
				$username = substr($_COOKIE[$cookie], 40);
				$cookiepass = substr($_COOKIE[$cookie], 0, 40);
				// now try to load this user from the database
				$results = $db->get_results("SELECT * FROM habari__users WHERE username = ?", array($username), User);
				if (! $results) {
					return false;
				}
				$dbuser = $results[0];
				if ( sha1($dbuser->password) == $cookiepass ) {
					// Cache the user in the static variable
					self::$me = $dbuser;
					return $dbuser;
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
		$cookie = "habari_" . $options->GUID;
		$content = sha1($this->password) . $this->username;
		setcookie($cookie, $content, time() + 604800, $options->siteurl);
	}

	/** function forget
	* delete a cookie from the client machine
	*/
	public function forget()
	{
		// delete the cookie
		$cookie = "habari_" . $options->GUID;
		setcookie($cookie, ' ', time() - 86400, $options->siteurl);
	}

	/** function authenticate
	* checks a user's credentials to see if they are legit
	* -- calls all auth plugins BEFORE checking local database
	* @param string A username or email address
	* @param string A password
	* @return a User object, or false
	*/
	public static function authenticate($who = '', $pw = '')
	{
		global $db;

		if ( (! $who ) || (! $pw ) ) {
			return false;
		}
		$what = "username";

		/*
			execute auth plugins here
		*/

		// were we given an email address, rather than a username?
		// this is a rough-shod approach, assuming that the @ character
		// won't appear in a username
		if ( strstr($who, '@') ) {
			// yes?  see if this email address has a username
			$what = "email";
		}
		$results = $db->get_results( "SELECT * FROM habari__users WHERE {$what} = ?", array( $who ), 'User' );
		if ( ! $results ) {
			return false;
		}
		$user = $results[0];
		if (sha1($pw) == $user->password) {
			// valid credentials were supplied
			// set the cookie
			$user->remember();
			return $user;
		} else {
			return false;
		}
	}

}


?>
