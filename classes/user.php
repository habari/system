<?php

/**
 * Habari UserRecord Class
 *
 * @package Habari
 *
 * @todo TODO Fix this documentation!
 *
 * Includes an instance of the UserInfo class; for holding inforecords about the user
 * If the User object describes an existing user; use the internal info object to get, set, unset and test for existence (isset) of
 * info records
 * <code>
 *	$this->info = new UserInfo ( 1 );  // Info records of user with id = 1
 * $this->info->option1= "blah"; // set info record with name "option1" to value "blah"
 * $info_value= $this->info->option1; // get value of info record with name "option1" into variable $info_value
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
	private static $identity= null;

	private $info= null;

	private $group_list= null;

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
		$this->info= new UserInfo ( $this->fields['id'] );
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
			return self::$identity;
		}
		if(isset($_SESSION['user_id'])) {
			if ( $user = User::get_by_id( $_SESSION['user_id'] ) ) {
				// Cache the user in the static variable
				self::$identity = $user;
				return $user;
			}
		}
		return false;
	}

	/**
	 * Creates a new user object and saves it to the database
	 * @param array An associative array of user fields
	 * @return User the User object that was created
	**/
	public static function create( $paramarray )
	{
		$user= new User( $paramarray );
		$user->insert();
		return $user;
	}

	/**
	 * Save a new user to the users table
	 */
	public function insert()
	{
		$result= parent::insert( DB::table('users') );
		$this->fields['id'] = DB::last_insert_id(); // Make sure the id is set in the user object to match the row id
		$this->info->set_key( $this->id );
		/* If a new user is being created and inserted into the db, info is only safe to use _after_ this set_key call. */
		// $this->info->option_default= "saved";
		$this->info->commit();
		EventLog::log( 'New user created: ' . $this->username, 'info', 'default', 'habari' );

		return $result;
	}

	/**
	 * Updates an existing user in the users table
	 */
	public function update()
	{
		$this->info->commit();
		return parent::update( DB::table('users'), array( 'id' => $this->id ) );
	}

	/**
	 * Delete a user account
	 */
	public function delete()
	{
		if(isset($this->info))
			$this->info->delete_all();
		EventLog::log( 'User deleted: ' . $this->username, 'info', 'default', 'habari' );
		return parent::delete( DB::table('users'), array( 'id' => $this->id ) );
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
		Session::clear_userid($_SESSION['user_id']);
		unset($_SESSION['user_id']);
		$home = Options::get('base_url');
		header( "Location: " . $home );
		exit;
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

		/* TODO execute auth plugins here */

		if ( strpos( $who, '@' ) !== FALSE ) {
			// we were given an email address
			$user= User::get_by_email( $who );
		}
		else {
			$user= User::get_by_name( $who );
		}

		if ( ! $user ) {
			// No such user.
			EventLog::log( 'Login attempt for non-existant user ' . $who, 'warning', 'authentication', 'habari' );
			self::$identity= null;
			return false;
		}

		if ( Utils::crypt( $pw, $user->password ) ) {
			// valid credentials were supplied
			self::$identity= $user;
			Plugins::act( 'user_authenticate_successful', self::$identity );
			EventLog::log( 'Successful login for ' . $user->username, 'info', 'authentication', 'habari' );
			// set the cookie
			$user->remember();
			return self::$identity;
		}
		else {
			// Wrong password.
			EventLog::log( 'Wrong password for user ' . $user->username, 'warning', 'authentication', 'habari' );
			self::$identity= null;
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
		if ( is_int( $who ) ) {
			// Got a User ID
			$user= User::get_by_id( $who );
		}
		elseif ( strpos( $who, '@' ) !== FALSE ) {
			// Got an email address
			$user= User::get_by_email( $who );
		}
		else {
			// Got username
			$user= User::get_by_name( $who );
		}
		// $user will be a user object, or false depending on the
		// results of the get_by_* method called above
		return $user;
	}

	/**
	 * Select a user from the database by their ID
	 * @param int $id The user's ID
	 * @return object User object, or false
	**/
	public static function get_by_id ( $id )
	{
		if ( 0 == $id ) {
			return false;
		}
		$user= DB::get_row( 'SELECT * FROM ' . DB::table('users') . ' WHERE id = ?', array( $id ), 'User' );
		return $user;
	}

	/**
	 * Select a user from the database by their login name
	 * @param string $who the user's name
	 * @return object User object, or false
	**/
	public static function get_by_name( $who )
	{
		if ( '' === $who ) {
			return false;
		}
		$user= DB::get_row( 'SELECT * FROM ' . DB::table('users') . ' WHERE username = ?', array( $who ), 'User');
		return $user;
	}

	/**
	 * Select a user from the database by their email address
	 * @param string $who the user's email address
	 * @return object User object, or false
	**/
	public static function get_by_email( $who )
	{
		if ( '' === $who ) {
			return false;
		}
		$user= DB::get_row( 'SELECT * FROM ' . DB::table('users') . ' WHERE email = ?', array( $who ), 'User');
		return $user;
	}

	/**
	 * Select a user from the database by userinfo
	 * @param string $who the meta info.
	 * @return object User object, or false
	**/
	public static function by_userinfo( $who ) {
		if( '' == $who ) {
			return false;
		}
		$user_id= DB::get_results( "SELECT user_id FROM " . DB::table('userinfo') . " WHERE value = '$who'" );
		$user= DB::get_row( 'SELECT * FROM ' . DB::table('users') . ' WHERE id = ?', array( $user_id[0]->user_id ), 'User' );
		return $user;
	}

	/**
	* Fetches all the users from the DB.
	* @todo TODO still need some checks for only authors.
	* @return array
	*/
	public static function get_all()
	{
		$list_users= DB::get_results( 'SELECT * FROM ' . DB::table('users') . ' ORDER BY username ASC', array(), 'User' );
		if ( is_array( $list_users ) ) {
			return $list_users;
		}
		else {
			return array();
		}
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
		$cookie= 'comment_' . Options::get('GUID');
		$commenter= array();
		if ( User::identify() ) {
			$commenter['name']= User::identify()->username;
			$commenter['email']= User::identify()->email;
			$commenter['url']= Site::get_url('habari');
		} elseif ( isset($_COOKIE[$cookie]) ) {
			list($commenter['name'], $commenter['email'], $commenter['url']) = explode('#', urldecode( $_COOKIE[$cookie] ) );
		} else {
			$commenter['name']= '';
			$commenter['email']= '';
			$commenter['url']= '';
		}
		return $commenter;
	}

	public function can( $permission, $to = null )
	{
		return ACL::user_can( $this->id, $permission );
	}

	/**
	 * function groups
	 * Returns an array of groups to which this user belongs
	 * @param bool Whether to refresh the cache
	 * @return Array an array of group IDs to which this user belongs
	**/
	private function list_groups( $refresh= false )
	{
		if ( ( empty( $this->group_list ) ) || $refresh ) {
			$this->group_list= DB::get_column( 'SELECT group_id FROM ' . DB::table('users_groups') . ' WHERE user_id=?', array( $this->id ) );
		}
		return $this->group_list;
	}

	/**
	 * function in_group
	 * Whether or not this user is is in the specified group
	 * @param int a group ID
	 * @return bool Whether or not this user is in the specified group
	**/
	public function in_group( $group )
	{
		$groups= $this->list_groups();
		if ( in_array( intval($group), $groups ) ) {
			return true;
		}
		return false;
	}

	/**
	 * function add_to_group
	 * @param int A group ID
	**/
	public function add_to_group( $group )
	{
		UserGroup::add_user( intval($group), $this->id );
	}

	/**
	 * function remove_from_group
	 * removes this user from a group
	 * @param int A group ID
	**/
	public function remove_from_group( $group )
	{
		UserGroup::remove_user( intval($group), $this->id );
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
		if ( $name == 'info' ) {
			if ( ! isset( $this->info ) ) {
				$this->info= new UserInfo( $this->fields['id'] );
			}
			else {
				$this->info->set_key( $this->fields['id'] );
			}
			return $this->info;
		}
		if ( $name == 'groups' ) {
			return $this->list_groups();
		}
		return parent::__get( $name );
	}

	/**
	 * Returns a set of properties used by URL::get to create URLs
	 * @return array Properties of this post used to build a URL
	 */
	public function get_url_args()
	{
		return array_merge( URL::extract_args( $this->info, 'info_' ), $this->to_array() );
	}

}

?>
