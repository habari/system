<?php
/**
 * Habari UserRecord Class
 *
 * Requires PHP 5.0.4 or later
 * @package Habari
 */

class User extends QueryRecord
{
	private static $identity = null;  // Static storage for the currently logged-in User record

	public function __construct($paramarray = array())
	{
		// Defaults
		$this->fields = array_merge(
			array(
				'id' => '',
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
		global $db, $options;
		// Is the logged-in user not cached already?
		if ( self::$identity == null ) {
			// see if there's a cookie
			$cookie = "habari_" . $options->GUID;
			if ( ! isset($_COOKIE[$cookie]) ) {
				// no cookie, so stop processing
				return false;
			} else {
				$userid = substr($_COOKIE[$cookie], 40);
				$cookiepass = substr($_COOKIE[$cookie], 0, 40);
				// now try to load this user from the database
				$user = $db->get_row("SELECT * FROM habari__users WHERE id = ?", array($userid), 'User');
				if ( ! $user ) {
					return false;
				}
				if ( sha1($user->password . $userid) == $cookiepass ) {
					// Cache the user in the static variable
					self::$identity = $user;
					return $user;
				} else {
					return false;
				}
			}
		}
		else {
			return self::$identity;
		}
	}
	
	/**
	 * function insert
	 * Saves a new user to the users table
	 */	 	 	 	 	
	public function insert()
	{
		return parent::insert( 'habari__users' );
	}

	/**
	 * function update
	 * Updates an existing user in the users table
	 */	 	 	 	 	
	public function update()
	{
		return parent::update( 'habari__users' );
	}

	/**
	* function remember
	* sets a cookie on the client machine for future logins
	*/
	public function remember()
	{
		global $options;
		// set the cookie
		$cookie = "habari_" . $options->GUID;
		$content = sha1($this->password . $this->id) . $this->id;
		setcookie($cookie, $content, time() + 604800, $options->siteurl);
	}

	/** function forget
	* delete a cookie from the client machine
	*/
	public function forget()
	{
		global $options;
		// delete the cookie
		$cookie = "habari_" . $options->GUID;
		setcookie($cookie, ' ', time() - 86400, $options->siteurl);
		die('ok');
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
		/* DEBUG */
		#Utils::debug('authenticate');

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
		$user = $db->get_row( "SELECT * FROM habari__users WHERE {$what} = ?", array( $who ), 'User' );
		if ( ! $user ) {
			self::$identity = null;
			return false;
		}
		if (sha1($pw) == $user->password) {
			// valid credentials were supplied
			// set the cookie
			$user->remember();
			self::$identity = $user;
			/* DEBUG */
			#Utils::debug('authed');
			return self::$identity;
		} else {
			self::$identity = null;
			return false;
		}
	}

	/**
	* function get
	* fetches a user from the database by name, ID, or email address
	*/
	
	public static function get($who = '')
	{
		global $db;

		if ('' === $who) {
			return false;
		}
		$what = 'username';
		// was a user ID given to us?
		if ( is_int($who) ) {
			$what = 'ID';
		} elseif ( strstr($who, '@') ) {
			// was an email address given?
			$what = 'email';
		}
		$user = $db->get_row( "SELECT * FROM habari__users WHERE {$what} = ?", array( $who ), 'User' );
		if ( ! $user ) {
			return false;
		} else {
			return $user;
		}
	}
	
	public static function get_all()
	{
		global $db;
		$list_users = $db->get_results( "SELECT * FROM habari__users ORDER BY ID DESC" );
			if ( is_array( $list_users ) ) {
				return $list_users;
			} else {
				return false;
			}
	}
}
?>
