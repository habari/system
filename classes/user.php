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

	/**
	* static function default_fields
	* @return array an array of the fields used in the User table
	*/
	public static function default_fields()
	{
		return array(
			'id' => '',
			'username' => '',
			'email' => '',
			'password' => ''
		);
	}

	/**
	* constructor  __construct
	* Constructor for the User class
	* @param array an associative array of initial User fields
	*/
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields );
		parent::__construct($paramarray);
		$this->exclude_fields('id');
	}

	/**
	* function identify
	* checks for the existence of a cookie, and returns a user object of the user, if successful
	* @return user object, or false if no valid cookie exists
	**/	
	public static function identify()
	{
		// Is the logged-in user not cached already?
		if ( self::$identity == null ) {
			// see if there's a cookie
			$cookie = "habari_" . Options::get('GUID');
			if ( ! isset($_COOKIE[$cookie]) ) {
				// no cookie, so stop processing
				return false;
			} else {
				$userid = substr($_COOKIE[$cookie], 40);
				$cookiepass = substr($_COOKIE[$cookie], 0, 40);
				// now try to load this user from the database
				$user = DB::get_row('SELECT * FROM ' . DB::o()->users . ' WHERE id = ?', array($userid), 'User');
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
		return parent::insert( DB::o()->users );
	}

	/**
	 * function update
	 * Updates an existing user in the users table
	 */	 	 	 	 	
	public function update()
	{
		return parent::update( DB::o()->users, array( 'id' => $this->id ) );
	}

	/**
	 * function delete
	 * delete a user account
	**/
	public function delete()
	{
		return parent::delete( DB::o()->users, array( 'id' => $this->id ) );
	}

	/**
	* function remember
	* sets a cookie on the client machine for future logins
	*/
	public function remember()
	{
		// set the cookie
		$cookie = "habari_" . Options::get('GUID');
		$content = sha1($this->password . $this->id) . $this->id;
		setcookie($cookie, $content, time() + 604800, Options::get('siteurl'));
	}

	/** function forget
	* delete a cookie from the client machine
	*/
	public function forget()
	{
		// delete the cookie
		$cookie = "habari_" . Options::get('GUID');
		setcookie($cookie, ' ', time() - 86400, Options::get('siteurl'));
		$home = Options::get('host_url');
		header( "Location: " . $home );
		exit;
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
		$user = DB::get_row( 'SELECT * FROM ' . DB::o()->users . " WHERE {$what} = ?", array( $who ), 'User' );
		if ( ! $user ) {
			self::$identity = null;
			return false;
		}
		if (sha1($pw) == $user->password) {
			// valid credentials were supplied
			// set the cookie
			$user->remember();
			self::$identity = $user;
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
		if ('' === $who) {
			return false;
		}
		$what = 'username';
		// was a user ID given to us?
		if ( is_numeric($who) ) {
			$what = 'id';
		} elseif ( strstr($who, '@') ) {
			// was an email address given?
			$what = 'email';
		}
		$user = DB::get_row( 'SELECT * FROM ' . DB::o()->users . " WHERE {$what} = ?", array( $who ), 'User' );
		if ( ! $user ) {
			return false;
		} else {
			return $user;
		}
	}
	
	/**
	* function get_all()
	* fetches all the users from the DB.
	* still need some checks for only authors.
	*/
	
	public static function get_all()
	{
		$list_users = DB::get_results( 'SELECT * FROM ' . DB::o()->users . ' ORDER BY ID DESC' );
			if ( is_array( $list_users ) ) {
				return $list_users;
			} else {
				return false;
			}
	}

	/**
	 * function count_posts()
	 * returns the number of posts written by this user
	 * @param mixed A status on which to filter posts (approved, unapproved).  If FALSE, no filtering will be performed.  Default: Post::STATUS_APPROVED
	 * @return int The number of posts written by this user
	**/
	public function count_posts( $status = Post::STATUS_APPROVED )
	{
		return Posts::count_by_author( $this->id, $status );
	}
/**
 * Returns the karma of this person, relative to the object passed in.
 * The object can be any object
 * You will usually not actually call this yourself, but will instead
 * call one of the functions following - is_admin(), is_drafter(), or
 * is_publisher().
 * 
 * @param mixed $obj An object, or an ACL object, or an ACL name
 * @return int $karma
 */
function karma( $obj = '' ) {
    // What was the argument?

    // It was a string, such as 'everything'.
    if ( is_string( $obj ) ) {
        $acl = new acl( $obj );
        return $acl->karma( $this );

    // It was an object  ....
    } elseif ( is_object( $obj ) ) {
        // What kind of object is it?
        $type = get_class( $obj );

        // Special case - acl object
        if ( $type == 'acl' ) {
            // It's already an ACL ...
            return $obj->karma( $user );
        } else {
            // It's some other object
            $acl = new acl( $obj );
            return $acl->karma( $user );
        }
    } else {
        // Run screaming from the room
        error_log("Weirdness passed to karma()");
        return 0;
    }

    // Special case - no argument
    if ($type == '') {
        // What's this users greatest karma, anywhere?
        $karma = DB::get_row( "SELECT max(karma) as k
            FROM  acl
            WHERE userid = ? ",
            array( $this->id ) );
        return $karma ? $karma->k : 0;
    } else {
        // Um ... how did we get here?
        error_log( "Not sure how we got here" );
    }
}

/**
 * Returns 1 or 0 (true or false) indicating whether the person in
 * question is an admin with respect to the object passed in. The
 * argument can be an actual object (such as a page or cms object), or
 * it can be the name of a module (such as 'registrar' or 'everything').
 * In the event that no argument is passed, the return value will be the
 * highest karma of this user with respect to anything. The implied
 * meaning is "is this user an admin anywhere?"
 * 
 * @param mixed $obj
 * @return boolean $return
 */
function is_admin( $obj = '' ) {
    return ( $this->karma($obj) == 10 or 
        ( $obj != 'everything' and $this->karma('everything') == 10 ) )
        ? 1 : 0 ;
}

/**
 * Returns 1 or 0 (true or false) indicating whether the person in
 * question is a publisher with respect to the object passed
 * in. The meaning is the same as with the is_admin() function
 * 
 * @param object $obj
 * @return boolean $return
 */
function is_publisher( $obj = '' ) {
    return ( $this->karma( $obj) >= 8 or 
        ( $obj != 'everything' and $this->is_publisher('everything') ) )
        ? 1 : 0 ;
}

/**
 * Returns 1 or 0 (true or false) indicating whether the person in
 * question is a drafter with respect to the object passed
 * in. The meaning is the same as with the is_admin() function.
 * 
 * @param object $obj
 * @return boolean $return
 */
function is_drafter( $obj = '' ) {
    return ( $this->karma( $obj) >= 5 or 
        ( $obj != 'everything' and $this->is_drafter('everything') ) )
        ? 1 : 0 ;
}


}
?>
