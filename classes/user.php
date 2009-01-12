<?php
/**
 * @package Habari
 *
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
 */
class User extends QueryRecord
{
	/**
	 * Static storage for the currently logged-in User record
	 */
	private static $identity = null;

	private $info = null;

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
		parent::__construct($paramarray);
		$this->exclude_fields('id');
		$this->info = new UserInfo ( $this->fields['id'] );
		 /* $this->fields['id'] could be null in case of a new user. If so, the info object is _not_ safe to use till after set_key has been called. Info records can be set immediately in any other case. */

	}

	/**
	 * Check for the existence of a cookie, and return a user object of the user, if successful
	 * @return object user object, or false if no valid cookie exists
	 */
	public static function identify()
	{
		// Is the logged-in user not cached already?
		if ( isset(self::$identity) ) {
			// is this user acting as another user?
			if ( isset($_SESSION['sudo']) ) {
				// if so, let's return that user data
				return self::get_by_id( intval($_SESSION['sudo']) );
			}
			// otherwise return the logged-in user
			return self::$identity;
		}
		if(isset($_SESSION['user_id'])) {
			if ( $user = self::get_by_id( intval($_SESSION['user_id']) ) ) {
				// Cache the user in the static variable
				self::$identity = $user;
				return $user;
			}
		}
		$anonymous = new User();
		Plugins::act('create_anonymous_user', $anonymous);
		return $anonymous;
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
		$allow = Plugins::filter('user_insert_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('user_insert_before', $this);
		$this->exclude_fields('id');
		$result = parent::insertRecord( DB::table('users') );
		$this->fields['id'] = DB::last_insert_id(); // Make sure the id is set in the user object to match the row id
		$this->info->set_key( $this->id );
		/* If a new user is being created and inserted into the db, info is only safe to use _after_ this set_key call. */
		// $this->info->option_default = "saved";
		$this->info->commit();
		EventLog::log( sprintf(_t('New user created: %s'), $this->username), 'info', 'default', 'habari' );
		Plugins::act('user_insert_after', $this);

		return $result;
	}

	/**
	 * Updates an existing user in the users table
	 */
	public function update()
	{
		$allow = true;
		$allow = Plugins::filter('user_update_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('user_update_before', $this);
		$this->info->commit();
		$result = parent::updateRecord( DB::table('users'), array( 'id' => $this->id ) );
		Plugins::act('user_update_after', $this);
		return $result;
	}

	/**
	 * Delete a user account
	 */
	public function delete()
	{
		$allow = true;
		$allow = Plugins::filter('user_delete_allow', $allow, $this);
		if ( ! $allow ) {
			return;
		}
		Plugins::act('user_delete_before', $this);
		if(isset($this->info)) {
			$this->info->delete_all();
		}
		// remove all this user's permissions
		DB::query( 'DELETE FROM {user_token_permissions} WHERE user_id=?', array( $this->id ) );
		// remove user from any groups
		DB::query( 'DELETE FROM {users_groups} WHERE user_id=?', array( $this->id ) );
		EventLog::log( sprintf(_t('User deleted: %s'), $this->username), 'info', 'default', 'habari' );
		$result = parent::deleteRecord( DB::table('users'), array( 'id' => $this->id ) );
		Plugins::act('user_delete_after', $this);
		return $result;
	}

	/**
	 * Save the user id into the session
	 */
	public function remember()
	{
		$_SESSION['user_id'] = $this->id;
		Session::set_userid($this->id);
	}

	/**
	 * Delete the user id from the session
	 */
	public function forget()
	{
		// is this user acting as another user?
		if ( isset( $_SESSION['sudo'] ) ) {
			// if so, remove the sudo token, but don't log out
			// the user
			unset( $_SESSION['sudo'] );
			Utils::redirect( Site::get_url( 'admin' ) );
		}
		Plugins::act( 'user_forget', $this );
		Session::clear_userid($_SESSION['user_id']);
		unset($_SESSION['user_id']);
		$home = Options::get('base_url');
		Utils::redirect( Site::get_url( 'habari' ) );
	}

	/**
	* Check a user's credentials to see if they are legit
	* -- calls all auth plugins BEFORE checking local database.
	*
	* @todo Actually call plugins
	*
	* @param string $who A username or email address
	* @param string $pw A password
	* @return object a User object, or false
	*/
	public static function authenticate( $who, $pw )
	{
		if ( '' === $who || '' === $pw ) {
			return false;
		}

		$user = new StdClass();
		$require = false;
		$user = Plugins::filter('user_authenticate', $user, $who, $pw);
		if($user instanceof User) {
			self::$identity = $user;
			Plugins::act( 'user_authenticate_successful', self::$identity );
			EventLog::log( sprintf(_t('Successful login for %s'), $user->username), 'info', 'authentication', 'habari' );
			// set the cookie
			$user->remember();
			return self::$identity;
		}
		elseif(!is_object($user)) {
			Plugins::act( 'user_authenticate_failure', 'plugin' );
			EventLog::log( sprintf(_t('Login attempt (via authentication plugin) for non-existent user %s'), $who), 'warning', 'authentication', 'habari' );
			Session::error('Invalid username/password');
			self::$identity = null;
			return false;
		}

		if ( strpos( $who, '@' ) !== FALSE ) {
			// we were given an email address
			$user = self::get_by_email( $who );
		}
		else {
			$user = self::get_by_name( $who );
		}

		if ( ! $user ) {
			// No such user.
			Plugins::act( 'user_authenticate_failure', 'non-existent' );
			EventLog::log( sprintf(_t('Login attempt for non-existent user %s'), $who), 'warning', 'authentication', 'habari' );
			Session::error('Invalid username/password');
			self::$identity = null;
			return false;
		}

		if ( Utils::crypt( $pw, $user->password ) ) {
			// valid credentials were supplied
			self::$identity = $user;
			Plugins::act( 'user_authenticate_successful', self::$identity );
			EventLog::log( sprintf(_t('Successful login for %s'), $user->username), 'info', 'authentication', 'habari' );
			// set the cookie
			$user->remember();
			return self::$identity;
		}
		else {
			// Wrong password.
			Plugins::act( 'user_authenticate_failure', 'bad_pass' );
			EventLog::log( sprintf(_t('Wrong password for user %s'), $user->username), 'warning', 'authentication', 'habari' );
			Session::error('Invalid username/password');
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
	 * @return object User object, or FALSE
	 */
	public static function get( $who )
	{
		if ( is_numeric( $who ) ) {
			// Got a User ID
			$user = self::get_by_id( $who );
		}
		elseif ( strpos( $who, '@' ) !== FALSE ) {
			// Got an email address
			$user = self::get_by_email( $who );
		}
		else {
			// Got username
			$user = self::get_by_name( $who );
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
		$user = self::get( $user );
		return $user->id;
	}

	/**
	 * Returns the number of posts written by this user
	 * @param mixed A status on which to filter posts (published, draft, etc).  If FALSE, no filtering will be performed.  Default: no filtering
	 * @return int The number of posts written by this user
	**/
	public function count_posts( $status = FALSE )
	{
		return Posts::count_by_author( $this->id, $status );
	}

	/**
	 * Returns an array of information about the commenter
	 * If this is a logged-in user, then return details from their user profile.
	 * If this is a returning commenter, then return details from their cookie
	 * otherwise return empty strings.
	 * @return Array an array of name, email and URL
	**/
	public static function commenter()
	{
		$cookie = 'comment_' . Options::get('GUID');
		$commenter = array();
		if ( self::identify() ) {
			$commenter['name'] = self::identify()->username;
			$commenter['email'] = self::identify()->email;
			$commenter['url'] = Site::get_url('habari');
		}
		elseif ( isset($_COOKIE[$cookie]) ) {
			list($commenter['name'], $commenter['email'], $commenter['url']) = explode('#', urldecode( $_COOKIE[$cookie] ) );
		}
		else {
			$commenter['name'] = '';
			$commenter['email'] = '';
			$commenter['url'] = '';
		}
		return $commenter;
	}

	/**
	 * Determine if a user has a specific permission
	 *
	 * @param string $permission The name of the permission to detect
	 * @param string $access The type of access to check for (read, write, full, etc.)
	 * @return boolean True if this user has the requested permission, false if not
	 */
	public function can( $permission, $access = 'any' )
	{
		return ACL::user_can( $this, $permission, $access );
	}

	/**
	 * Determine if a user has been denied a specific permission
	 *
	 * @param string $permission The name of the permission to detect
	 * @return boolean True if this user has the requested permission, false if not
	 */
	public function cannot( $permission )
	{
		return ACL::user_cannot( $this, $permission );
	}

	/**
	 * Assign one or more new permissions to this user
	 * @param mixed A permission token ID, name, or array of the same
	**/
	public function grant( $permissions, $access = 'full' )
	{
		$permissions = Utils::single_array( $permissions );
		// Use ids internally for all permissions
		$permissions = array_map(array('ACL', 'token_id'), $permissions);

		foreach ( $permissions as $permission ) {
			ACL::grant_user( $this->id, $permission, $access );
			EventLog::log( _t( 'User %1$s: Access to %2$s changed to %3$s', array( $this->username, ACL::token_name( $permission ), $access ) ), 'notice', 'user', 'habari' );
		}
	}

	/**
	 * Deny one or more permissions to this user
	 * @param mixed The permission ID or name to be denied, or an array of the same
	**/
	public function deny( $permissions )
	{
		$this->grant( $permissions, 'deny' );
	}

	/**
	 * Remove one or more permissions from a user
	 * @param mixed a permission ID, name, or array of the same
	**/
	public function revoke( $permissions )
	{
		$permissions = Utils::single_array( $permissions );
		// get token IDs
		$permissions = array_map(array('ACL', 'token_id'), $permissions);
		foreach ( $permissions as $permission ) {
			ACL::revoke_user_permission( $this->id, $permission );
			EventLog::log( _t( 'User %1$s: Permission to %2$s revoked.', array( $this->username, ACL::token_name( $permission ) ) ), 'notice', 'user', 'habari' );
		}
	}

	/**
	 * function groups
	 * Returns an array of groups to which this user belongs
	 * @param bool Whether to refresh the cache
	 * @return Array an array of group IDs to which this user belongs
	**/
	private function list_groups( $refresh = false )
	{
		if ( ( empty( $this->group_list ) ) || $refresh ) {
			$this->group_list = DB::get_column( 'SELECT group_id FROM ' . DB::table('users_groups') . ' WHERE user_id=?', array( $this->id ) );
		}
		return $this->group_list;
	}

	/**
	 * function in_group
	 * Whether or not this user is is in the specified group
	 * @param int $group a group ID or name
	 * @return bool Whether or not this user is in the specified group
	**/
	public function in_group( $group )
	{
		$groups = $this->list_groups();
		return in_array( UserGroup::id($group), $groups );
	}

	/**
	 * function add_to_group
	 * @param mixed $group A group ID or name
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
	 * @param mixed $group A group ID or name
	**/
	public function remove_from_group( $group )
	{
		UserGroup::remove( $group, $this->id );
		EventLog::log( _t( 'User %1$s: Removed from group %2$s.', array( $this->username, $group->name ) ), 'notice', 'user', 'habari' );
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
		switch ($name) {
			case 'info':
				if ( ! isset( $this->info ) ) {
					$this->info = new UserInfo( $this->fields['id'] );
				}
				else {
					$this->info->set_key( $this->fields['id'] );
				}
				return $this->info;
			case 'groups':
				return $this->list_groups();
			case 'displayname':
				return ( empty($this->info->displayname) ) ? $this->username : $this->info->displayname;
			case 'loggedin':
				return $this->id != 0;
			default:
				return parent::__get( $name );
		}
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

}

?>
