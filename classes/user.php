<?php
/**
 * @package Habari
 *
 * @property-read UserInfo $info The UserInfo object for this user
 * @property-read array $groups An array of the group ids to which this user belongs
 * @property-read string $displayname This user's display name, or their user name if the display name is empty
 * @property-read boolean $loggedin Whether or not this user is currently identified
 */

/**
 * Habari UserRecord Class
 *
 * @todo TODO Fix this documentation!
 *
 * Includes an instance of the UserInfo class; for holding inforecords about the user
 * If the User object describes an existing user; use the internal info object to get, set, unset and test for existence (isset) of
 * info records
 * <code>
 * $this->info = new UserInfo ( 1 );  // Info records of user with id = 1
 * $this->info->option1 = "blah"; // set info record with name "option1" to value "blah"
 * $info_value = $this->info->option1; // get value of info record with name "option1" into variable $info_value
 * if ( isset ($this->info->option1) )  // test for existence of "option1"
 * unset ( $this->info->option1 ); // delete "option1" info record
 * </code>
 *
 * @property UserInfo $info Metadata stored about this user in the userinfo table
 *
 */
class User extends QueryRecord implements FormStorage, IsContent
{
	/**
	 * Static storage for the currently logged-in User record
	 */
	private static $identity = null;

	private $inforecords = null;

	private $group_list = null;

	protected $url_args;

	/**
	 * Get default fields for this record.
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
	 * Constructor for the User class
	 * @param array $paramarray an associative array of initial User fields
	 */
	public function __construct( $paramarray = array() )
	{
		// Defaults
		$this->fields = array_merge(
			self::default_fields(),
			$this->fields );
		parent::__construct( $paramarray );
		$this->exclude_fields( 'id' );
		/* $this->fields['id'] could be null in case of a new user. If so, the info object is _not_ safe to use till after set_key has been called. Info records can be set immediately in any other case. */

	}

	/**
	 * Build and return the anonymous user
	 * @return object user object
	 */
	public static function anonymous()
	{
		static $anonymous = null;
		if ( $anonymous == null ) {
			$anonymous = new User();
			$anonymous->id = 0;
			$anonymous->username = _t( 'Anonymous' );
			Plugins::act( 'create_anonymous_user', $anonymous );
		}
		return $anonymous;
	}

	/**
	 * Check for the existence of a cookie, and return a user object of the user, if successful
	 * @return User user object, or false if no valid cookie exists
	 */
	public static function identify()
	{
		$out = false;
		// Let plugins set the user
		if ( $out = Plugins::filter('user_identify', $out) ) {
			self::$identity = $out;
		}
		// If we have a user_id for this user in their session, use it to get the user object
		if ( isset( $_SESSION['user_id'] ) ) {
			// If the user is already cached in this static class, use it
			if ( isset(self::$identity) ) {
				$out = self::$identity;
			}
			// If the user_id in the session is a valid one, cache it in this static class and use it
			else if ( $user = self::get_by_id( intval( $_SESSION['user_id'] ) ) ) {
				// Cache the user in the static variable
				self::$identity = $user;
				$out = $user;
			}
		}
		// Is the visitor a non-anonymous user
		if ( $out instanceof User ) {
			// Is this user acting as another user?
			if ( isset( $_SESSION['sudo'] ) ) {
				// Return the User for the sudo user id instead
				$out = self::get_by_id( intval( $_SESSION['sudo'] ) );
			}
		}
		else {
			$out = self::anonymous();
		}
		return $out;
	}

	/**
	 * Creates a new user object and saves it to the database
	 * @param array An associative array of user fields
	 * @return User the User object that was created
	**/
	public static function create( $paramarray )
	{
		$user = new User( $paramarray );
		$user->insert();
		return $user;
	}

	/**
	 * Save a new user to the users table
	 */
	public function insert()
	{
		$allow = true;
		$allow = Plugins::filter( 'user_insert_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'user_insert_before', $this );
		$this->exclude_fields( 'id' );
		$result = parent::insertRecord( DB::table( 'users' ) );
		$this->fields['id'] = DB::last_insert_id(); // Make sure the id is set in the user object to match the row id
		$this->info->set_key( $this->id );
		/* If a new user is being created and inserted into the db, info is only safe to use _after_ this set_key call. */
		// $this->info->option_default = "saved";

		// Set the default timezone, date format, and time format
		$this->info->locale_tz = Options::get( 'timezone' );
		$this->info->locale_date_format = Options::get( 'dateformat' );
		$this->info->locale_time_format = Options::get( 'timeformat' );

		$this->info->commit();

		if ( $result ) {
			// Add the user to the default authenticated group if it exists
			if ( UserGroup::exists( 'authenticated' ) ) {
				$this->add_to_group( 'authenticated' );
			}
		}

		EventLog::log( _t( 'New user created: %s', array( $this->username ) ), 'info', 'default', 'habari' );
		Plugins::act( 'user_insert_after', $this );

		return $result;
	}

	/**
	 * Updates an existing user in the users table
	 */
	public function update()
	{
		$allow = true;
		$allow = Plugins::filter( 'user_update_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'user_update_before', $this );
		$this->info->commit();
		$result = parent::updateRecord( DB::table( 'users' ), array( 'id' => $this->id ) );
		Plugins::act( 'user_update_after', $this );
		return $result;
	}

	/**
	 * Delete a user account
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter( 'user_delete_allow', $allow, $this );
		if ( ! $allow ) {
			return;
		}
		Plugins::act( 'user_delete_before', $this );

		// remove any userinfo records
		$this->info->delete_all();

		// remove all this user's permissions
		DB::query( 'DELETE FROM {user_token_permissions} WHERE user_id=?', array( $this->id ) );
		// remove user from any groups
		DB::query( 'DELETE FROM {users_groups} WHERE user_id=?', array( $this->id ) );
		EventLog::log( _t( 'User deleted: %s', array( $this->username ) ), 'info', 'default', 'habari' );
		$result = parent::deleteRecord( DB::table( 'users' ), array( 'id' => $this->id ) );
		Plugins::act( 'user_delete_after', $this );
		return $result;
	}

	/**
	 * Save the user id into the session
	 */
	public function remember()
	{
		if(!isset($_SESSION['sudo'])) {
			$_SESSION['user_id'] = $this->id;
		}
			ACL::clear_caches();
		if(!isset($_SESSION['sudo'])) {
			Session::set_userid( $this->id );
		}
	}

	/**
	 * Delete the user id from the session
	 * @param boolean $redirect Redirect the user to base_url after destroying session?
	 */
	public function forget( $redirect = true )
	{

		// if the user is not actually logged in, just return so we don't throw any errors later
		if ( $this->loggedin != true ) {
			return;
		}

		// is this user acting as another user?
		if ( isset( $_SESSION['sudo'] ) ) {
			// if so, remove the sudo token, but don't log out
			// the user
			unset( $_SESSION['sudo'] );

			if ( $redirect ) {
				Utils::redirect( Site::get_url( 'admin' ) );
			}
			else {
				// we want to return, not continue processing, or we'd log out the user too
				return;
			}
		}
		ACL::clear_caches();
		Plugins::act( 'user_forget', $this );
		Session::clear_userid( $_SESSION['user_id'] );
		unset( $_SESSION['user_id'] );

		if ( $redirect ) {
			Utils::redirect( Site::get_url( 'habari' ) );
		}
	}

	/**
	* Check a user's credentials to see if they are legit
	* -- calls all auth plugins BEFORE checking local database.
	*
	* @param string $who A username
	* @param string $pw A password
	* @return User|boolean a User object, or false
	*/
	public static function authenticate( $who, $pw )
	{
		if ( '' === $who || '' === $pw ) {
			return false;
		}

		$user = new StdClass();
		$require = false;
		$user = Plugins::filter( 'user_authenticate', $user, $who, $pw );
		if ( $user instanceof User ) {
			self::$identity = $user;
			Plugins::act( 'user_authenticate_successful', self::$identity );
			EventLog::log( _t( 'Successful login for %s', array( $user->username ) ), 'info', 'authentication', 'habari' );
			// set the cookie
			$user->remember();
			return self::$identity;
		}
		elseif ( !is_object( $user ) ) {
			Plugins::act( 'user_authenticate_failure', 'plugin' );
			EventLog::log( _t( 'Login attempt (via authentication plugin) for non-existent user %s', array( $who ) ), 'warning', 'authentication', 'habari' );
			Session::error( _t( 'Invalid username/password' ) );
			self::$identity = null;
			return false;
		}

		// Check by name first. Allows for the '@' to be in the username, without it being an email address
		$user = self::get_by_name( $who );

		if ( ! $user ) {
			// No such user.
			Plugins::act( 'user_authenticate_failure', 'non-existent' );
			EventLog::log( _t( 'Login attempt for non-existent user %s', array( $who ) ), 'warning', 'authentication', 'habari' );
			Session::error( _t( 'Invalid username/password' ) );
			self::$identity = null;
			return false;
		}

		if ( Utils::crypt( $pw, $user->password ) ) {
			// valid credentials were supplied
			self::$identity = $user;
			Plugins::act( 'user_authenticate_successful', self::$identity );
			EventLog::log( _t( 'Successful login for %s', array( $user->username ) ), 'info', 'authentication', 'habari' );
			// set the cookie
			$user->remember();
			return self::$identity;
		}
		else {
			// Wrong password.
			Plugins::act( 'user_authenticate_failure', 'bad_pass' );
			EventLog::log( _t( 'Wrong password for user %s', array( $user->username ) ), 'warning', 'authentication', 'habari' );
			Session::error( _t( 'Invalid username/password' ) );
			self::$identity = null;
			return false;
		}
	}

	/**
	 * Fetch a user from the database by name, ID, or email address.
	 * This is a wrapper function that will invoke the appropriate
	 * get_by_* method.
	 *
	 * @param mixed $who user ID, username, or e-mail address
	 * @return object User object, or false
	 */
	public static function get( $who )
	{
		if ( $who instanceof User ) {
			$user = $who;
		}
		elseif ( is_numeric( $who ) ) {
			// Got a User ID
			$user = self::get_by_id( $who );
		}
		else {
			// Got username or email
			$user = self::get_by_name( $who );
			if ( ! $user && strpos( $who, '@' ) !== false ) {
				// Got an email address
				$user = self::get_by_email( $who );
			}
		}
		// $user will be a user object, or false depending on the
		// results of the get_by_* method called above
		return $user;
	}

	/**
	 * Select a user from the database by its id
	 *
	 * @param int $id The id of a user
	 * @return User The User object of the specified user
	 */
	public static function get_by_id( $id )
	{
		if ( 0 == $id ) {
			return false;
		}

		$params = array(
			'id' => $id,
			'limit' => 1,
			'fetch_fn' => 'get_row',
			);

		return Users::get( $params );
	}

	/**
	 * Select a user from the database by its username
	 *
	 * @param string $username The name of a user
	 * @return User The User object of the specified user
	 */
	public static function get_by_name( $username )
	{
		if ( '' == $username ) {
			return false;
		}

		$params = array(
			'username' => $username,
			'limit' => 1,
			'fetch_fn' => 'get_row',
			);

		return Users::get( $params );
	}

	/**
	 * Select a user from the database by its email address
	 *
	 * @param string $email The email address of a user
	 * @return User The User object of the specified user
	 */
	public static function get_by_email( $email )
	{
		if ( '' === $email ) {
			return false;
		}

		$params = array(
			'email' => $email,
			'limit' => 1,
			'fetch_fn' => 'get_row',
			);

		return Users::get( $params );
	}

	/**
	 * Return the id of a user
	 *
	 * @param mixed $user The id, name, or email address of a user
	 * @return integer The id of the specified user or false if that user doesn't exist
	 */
	public static function get_id( $user )
	{
		if ( is_int( $user ) ) {
			return $user;
		}
		$user = self::get( $user );
		return $user->id;
	}

	/**
	 * Returns the number of posts written by this user
	 * @param mixed A status on which to filter posts (published, draft, etc).  If false, no filtering will be performed.  Default: no filtering
	 * @return int The number of posts written by this user
	 */
	public function count_posts( $status = false )
	{
		return Posts::count_by_author( $this->id, $status );
	}

	/**
	 * Returns an array of information about the commenter
	 * If this is a logged-in user, then return details from their user profile.
	 * If this is a returning commenter, then return details from their cookie
	 * otherwise return empty strings.
	 * @return Array an array of name, email and URL
	 */
	public static function commenter()
	{
		$cookie = 'comment_' . Options::get( 'GUID' );
		$commenter = array();
		if ( self::identify() ) {
			$commenter['name'] = self::identify()->username;
			$commenter['email'] = self::identify()->email;
			$commenter['url'] = Site::get_url( 'habari' );
		}
		elseif ( isset( $_COOKIE[$cookie] ) ) {
			list( $commenter['name'], $commenter['email'], $commenter['url'] ) = explode( '#', urldecode( $_COOKIE[$cookie] ) );
		}
		else {
			$commenter['name'] = '';
			$commenter['email'] = '';
			$commenter['url'] = '';
		}
		return $commenter;
	}

	/**
	 * Determine if a user has a specific token permission
	 *
	 * @param string $token The name of the token for which to check permission
	 * @param string $access The type of access to check for (read, write, full, etc.)
	 * @return boolean True if this user has the requested access, false if not
	 */
	public function can( $token, $access = 'any' )
	{
		return ACL::user_can( $this, $token, $access );
	}

	/**
	 * Determine if a user has any of a set of tokens
	 *
	 * @param array $token_access An array of tokens and the permissions to
	 * check for each of them.
	 * @return boolean True if this user has the requested access, false if not
	 */
	public function can_any( $token_access = array() )
	{
		$token_access = Utils::single_array( $token_access );

		foreach ( $token_access as $token => $access ) {
			$access = Utils::single_array( $access );
			foreach ( $access as $mask ) {
				if ( is_bool( $mask ) ) {
					if ( $this->can( $token ) ) {
						return true;
					}
				}
				else {
					if ( $this->can( $token, $mask ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Determine if a user has been denied access to a specific token
	 *
	 * @param string $token The name of the token to detect
	 * @return boolean True if this user has been denied access to the requested token, false if not
	 */
	public function cannot( $token )
	{
		return ACL::user_cannot( $this, $token );
	}

	/**
	 * Assign permissions to one or more new tokens to this user
	 * @param mixed A token ID, name, or array of the same
	 * @param string The access to grant
	**/
	public function grant( $tokens, $access = 'full' )
	{
		$tokens = Utils::single_array( $tokens );
		// Use ids internally for all tokens
		$tokens = array_map( array( 'ACL', 'token_id' ), $tokens );

		foreach ( $tokens as $token ) {
			ACL::grant_user( $this->id, $token, $access );
			EventLog::log( _t( 'User %1$s: Access to %2$s changed to %3$s', array( $this->username, ACL::token_name( $token ), $access ) ), 'notice', 'user', 'habari' );
		}
	}

	/**
	 * Deny permissions to one or more tokens to this user
	 * @param mixed The token ID or name to be denied, or an array of the same
	**/
	public function deny( $tokens )
	{
		$this->grant( $tokens, 'deny' );
	}

	/**
	 * Remove permissions to one or more tokens from a user
	 * @param mixed a token ID, name, or array of the same
	**/
	public function revoke( $tokens )
	{
		$tokens = Utils::single_array( $tokens );
		// get token IDs
		$tokens = array_map( array( 'ACL', 'token_id' ), $tokens );
		foreach ( $tokens as $token ) {
			ACL::revoke_user_token( $this->id, $token );
			EventLog::log( _t( 'User %1$s: Permission to %2$s revoked.', array( $this->username, ACL::token_name( $token ) ) ), 'notice', 'user', 'habari' );
		}
	}

	/**
	 * Returns an array of groups to which this user belongs
	 * @param bool Whether to refresh the cache
	 * @return Array an array of group IDs to which this user belongs
	**/
	private function list_groups( $refresh = false )
	{
		if ( ( empty( $this->group_list ) ) || $refresh ) {
			$this->group_list = DB::get_column( 'SELECT group_id FROM {users_groups} WHERE user_id=?', array( $this->id ) );
		}
		return $this->group_list;
	}

	/**
	 * function in_group
	 * Whether or not this user is is in the specified group
	 * @param int|string $group a group ID or name
	 * @return bool Whether or not this user is in the specified group
	**/
	public function in_group( $group )
	{
		$groups = $this->list_groups();
		return in_array( UserGroup::id( $group ), $groups );
	}

	/**
	 * function add_to_group
	 * @param integer|string|UserGroup $group A group ID, name, or UserGroup instance
	 * @return null
	**/
	public function add_to_group( $group )
	{
		$group = UserGroup::get( $group );
		if ( $group instanceOf UserGroup ) {
			$group->add( $this->id );
			EventLog::log( _t( ' User %1$s: Added to %2$s group.', array( $this->username, $group->name ) ), 'notice', 'user', 'habari' );
		}
	}

	/**
	 * function remove_from_group
	 * removes this user from a group
	 * @param integer|string|UserGroup $group A group ID, name, or UserGroup instance
	 * @return null
	**/
	public function remove_from_group( $group )
	{
		$group = UserGroup::get( $group );
		if ( $group instanceOf UserGroup ) {
			$group->remove( $this->id );
			EventLog::log( _t( ' User %1$s: Removed from %2$s group.', array( $this->username, $group->name ) ), 'notice', 'user', 'habari' );
		}
	}

	/**
	 * Capture requests for the info object so that it can be initialized properly when
	 * the constructor is bypassed (see PDO::FETCH_CLASS pecularities). Passes all other
	 * requests to parent.
	 *
	 * @param string $name requested field name
	 * @return mixed the requested field value
	 */
	public function __get( $name )
	{
		$fieldnames = array_merge( array_keys( $this->fields ), array( 'groups', 'displayname', 'loggedin', 'info' ) );
		$filter = false;
		if ( !in_array( $name, $fieldnames ) && strpos( $name, '_' ) !== false ) {
			$field_matches = implode('|', $fieldnames);
			if(preg_match( '/^(' . $field_matches . ')_(.+)$/', $name, $matches )) {
				list( $junk, $name, $filter )= $matches;
			}
		}

		switch ( $name ) {
			case 'info':
				$out = $this->get_info();
				break;
			case 'groups':
				$out = $this->list_groups();
				break;
			case 'displayname':
				$out = ( empty( $this->info->displayname ) ) ? $this->username : $this->info->displayname;
				break;
			case 'loggedin':
				$out = $this->id != 0;
				break;
			default:
				$out = parent::__get( $name );
				break;
		}

		$out = Plugins::filter( "user_get", $out, $name, $this );
		$out = Plugins::filter( "user_{$name}", $out, $this );
		if ( $filter ) {
			$out = Plugins::filter( "user_{$name}_{$filter}", $out, $this );
		}
		return $out;
	}

	/**
	 * function get_info
	 * Gets the info object for this user, which contains data from the userinfo table
	 * related to this user.
	 * @return UserInfo object
	 */
	private function get_info()
	{
		if ( ! isset( $this->inforecords ) ) {
			// If this user isn't in the database yet...
			if (  0 == $this->id ) {
				$this->inforecords = new UserInfo();
			}
			else {
				$this->inforecords = new UserInfo( $this->id );
			}
		}
		else {
			$this->inforecords->set_key( $this->id );
		}
		return $this->inforecords;
	}


	/**
	 * Returns a set of properties used by URL::get to create URLs
	 * @return array Properties of this post used to build a URL
	 */
	public function get_url_args()
	{
		if ( !$this->url_args ) {
			$this->url_args = array_merge( URL::extract_args( $this->info, 'info_' ), $this->to_array() );
		}
		return $this->url_args;
	}

	/**
	 * Stores a form value into the object
	 *
	 * @param string $key The name of a form component that will be stored
	 * @param mixed $value The value of the form component to store
	 */
	function field_save($key, $value)
	{
		$this->info->$key = $value;
		$this->info->commit();
	}

	/**
	 * Loads form values from an object
	 *
	 * @param string $key The name of a form component that will be loaded
	 * @return mixed The stored value returned
	 */
	function field_load($key)
	{
		return $this->info->$key;
	}

	/**
	 * Returns the content type of the object instance
	 *
	 * @return array An array of content types that this object represents, starting with the most specific
	 */
	function content_type()
	{
		return array(
			$this->id . '.user',
			$this->username . '.user',
			'user'
		);
	}
}

?>
