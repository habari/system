<?php
/**
* Habari UserGroup Class
* @package Habari
**/
class UserGroup extends QueryRecord
{
	// These arrays hold the current membership and permission settings for this group
	// These arrays are NOT matched key and value pairs (the are not stored like array('foo'=>'foo') )
	private $member_ids= array();
	private $permissions_granted= array();
	private $permissions_denied= array();

	/**
	 * get default fields for this record
	 * @return array an array of the fields used in the UserGroup table
	**/
	public static function default_fields()
	{
		return array(
			'id' => '',
			'name' => ''
		);
	}

	/**
	 * Constructor for the UserGroup class
	 * @param array $paramarray an associative array of UserGroup fields
	**/
	public function __construct( $paramarray= array() )
	{
		$this->fields= array_merge(
			self::default_fields(),
			$this->fields
		);
		parent::__construct( $paramarray );

		// if we have an ID, load this UserGroup's members & permissions
		if ( $this->id ) {
			if ( $result= DB::get_column( 'SELECT user_id FROM {users_groups} WHERE group_id= ?', array( $this->id ) ) ) {
				$this->member_ids= $result;
			}

			if ( $results= DB::get_results( 'SELECT permission_id, denied FROM {groups_permissions} WHERE group_id=?', array( $this->id ) ) ) {
				foreach ( $results as $result ) {
					if ( 1 === (int) $result->denied ) {
						 $this->permissions_denied[]= $result->permission_id;
					}
					else {
						 $this->permissions_granted[]= $result->permission_id;
					}
				}
			}
		}

		// exclude field keys from the $this->fields array that should not be updated in the database on insert/update
		$this->exclude_fields( array( 'id' ) );
	}

	/**
	 * Create a new UserGroup object and save it to the database
	 * @param array $paramarray An associative array of UserGroup fields
	 * @return UserGroup the UserGroup that was created
	**/
	public static function create( $paramarray )
	{
		$usergroup= new UserGroup( $paramarray );
		if ( $usergroup->insert() ) {
			return $usergroup;
		}
		else {
			return $false;
		}
	}

	/**
	 * Save a new UserGroup to the UserGroup table
	**/
	public function insert()
	{
		$allow= true;
		// plugins have the opportunity to prevent insertion
		$allow= Plugins::filter('usergroup_insert_allow', $allow, $this);
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('usergroup_insert_before', $this);
		$this->exclude_fields('id');
		$result= parent::insertRecord( DB::table('groups') );
		$this->fields['id']= DB::last_insert_id();

		$this->set_permissions();

		EventLog::log( sprintf(_t('New group created: %s'), $this->name), 'info', 'default', 'habari');
		Plugins::act('usergroup_insert_after', $this);
		return $result;
	}

	/**
	 * Updates an existing UserGroup in the DB
	**/
	public function update()
	{
		$allow= true;
		// plugins have the opportunity to prevent modification
		$allow= Plugins::filter('usergroup_update_allow', $allow, $this);
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('usergroup_update_before', $this);

		$this->set_permissions();

		EventLog::log(sprintf(_t('User Group updated: %s'), $this->name), 'info', 'default', 'habari');
		Plugins::act('usergroup_update_after', $this);
	}

	/**
	 * Set the permissions for this group
	 */
	protected function set_permissions()
	{
		// Remove all users from this group in preparation for adding the current list
		DB::query('DELETE FROM {users_groups} WHERE group_id=?', array( $this->id ) );
		// Add the current list of users into the group
		foreach( $this->member_ids as $user_id ) {
			DB::query('INSERT INTO {users_groups} (user_id, group_id) VALUES (?, ?)', array( $user_id, $this->id) );
		}

		// Remove all permissions from this group in preparation for adding the current list
		DB::query( 'DELETE FROM {groups_permissions} WHERE group_id=?', array( $this->id ) );
		// Add the current list of permissions into the group
		foreach( $this->permissions_granted as $grant_id ) {
			DB::query('INSERT INTO {groups_permissions} (group_id, permission_id, denied ) VALUES (?, ?, 0)', array( $this->id, $grant_id) );
		}
		foreach( $this->permissions_denied as $deny_id ) {
			DB::query('INSERT INTO {groups_permissions} (group_id, permission_id, denied ) VALUES (?, ?, 1)', array( $this->id, $deny_id) );
		}
	}

	/**
	 * Delete a UserGroup
	**/
	public function delete()
	{
		$allow= true;
		// plugins have the opportunity to prevent deletion
		$allow= Plugins::filter('usergroup_delete_allow', $allow, $this);
		 if ( ! $allow ) {
		 	return;
		}

		Plugins::act('usergroup_delete_before', $this);
		// remove all this group's permissions
		$results= DB::query( 'DELETE FROM {groups_permissions} WHERE group_id=?', array( $this->id ) );
		// remove all this group's members
		$results= DB::query( 'DELETE FROM {users_groups} WHERE group_id=?', array( $this->id ) );
		// remove this group
		$result= parent::deleteRecord( DB::table('groups'), array( 'id' => $this->id ) );
		Plugins::act('usergroup_delete_after', $this);
		return $result;
	}

	/**
	 * function __get
	 * magic get function for returning virtual properties of the class
	 * @param mixed the property to get
	 * @return mixed the property
	**/
	public function __get( $param )
	{
		switch ( $param ) {
			case 'members':
				return $this->member_ids;
				break;
			case 'granted':
				return $this->permissions_granted;
				break;
			case 'denied':
				return $this->permissions_denied;
				break;
			default:
				return parent::__get( $param );
				break;
		}
	}

	/**
	 * Add one or more users to this group
	 * @param mixed $users a user ID or name, or an array of the same
	**/
	public function add( $users )
	{
		$users = Utils::single_array( $users );
		// Use ids internally for all users
		$users = array_map(array('User', 'get_id'), $users);
		// Remove users from group membership
		$this->member_ids = $this->member_ids + $users;
		// List each group member exactly once
		$this->member_ids = array_unique($this->member_ids);
	}

	/**
	 * Remove one or more user from this group
	 * @param mixed $users A user ID or name, or an array of the same
	**/
	public function remove( $users )
	{
		$users = Utils::single_array( $users );
		// Use ids internally for all users
		$users = array_map(array('User', 'get_id'), $users);
		// Remove users from group membership
		$this->member_ids = array_diff( $this->member_ids, $users);
	}

	/**
	 * Assign one or more new permissions to this group
	 * @param mixed A permission ID, name, or array of the same
	**/
	public function grant( $permissions )
	{
		$permissions = Utils::single_array( $permissions );
		// Use ids internally for all permissions
		$permissions = array_map(array('ACL', 'permission_id'), $permissions);
		// Merge the new permissions
		$this->permissions_granted = $this->permissions_granted + $permissions;
		// List each permission exactly once
		$this->permissions_granted = array_unique($this->permissions_granted);
		// Remove granted permissions from the denied list
		$this->permissions_denied = array_diff($this->permissions_denied, $this->permissions_granted);
	}

	/**
	 * Deny one or more permissions to this group
	 * @param mixed The permission ID or name to be denied, or an array of the same
	**/
	public function deny( $permissions )
	{
		$permissions = Utils::single_array( $permissions );
		// Use ids internally for all permissions
		$permissions = array_map(array('ACL', 'permission_id'), $permissions);
		// Merge the new permissions
		$this->permissions_denied = $this->permissions_denied + $permissions;
		// List each permission exactly once
		$this->permissions_denied = array_unique($this->permissions_denied);
		// Remove denied permissions from the granted list
		$this->permissions_granted = array_diff($this->permissions_granted, $this->permissions_denied);
	}

	/**
	 * Remove one or more permissions from a group
	 * @param mixed a permission ID, name, or array of the same
	**/
	public function revoke( $permissions )
	{
		$permissions = Utils::single_array( $permissions );
		// Remove permissions from the granted list
		$this->permissions_granted = array_diff($this->permissions_granted, $permissions);
		// Remove permissions from the denied list
		$this->permissions_denied = array_diff($this->permissions_denied, $permissions);
	}

	/**
	 * Determine whether members of a group can do something.
	 * This function should not be used to determine composite permissions among several groups
	 * @param mixed a permission ID or name
	 * @return boolean If this group has been granted and not denied this permission, return true.  Otherwise, return false.
	 * @see ACL::group_can()
	 * @see ACL::user_can()
	**/
	public function can( $permission )
	{
		$permission= ACL::permission_id( $permission );
		if ( in_array( $permission, $this->permissions_denied ) ) {
			return false;
		}
		if ( in_array( $permission, $this->permissions_granted ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Fetch a group from the database by ID or name.
	 * This is a wrapper for get_by_id() and get_by_name()
	 * @param mixed $group A group ID or name
	 * @return mixed UserGroup object, or boolean FALSE
	*/
	public static function get( $group )
	{
		if ( is_int( $group ) ) {
			return self::get_by_id( $group );
		}
		else {
			return self::get_by_name( $group );
		}
	}

	/**
	 * Select a group from the DB by its ID
	 * @param int A group ID
	 * @return mixed A UserGroup object, or boolean FALSE
	**/
	public static function get_by_id( $id )
	{
		return DB::get_row( 'SELECT * FROM {groups} WHERE id=?', array( $id ), 'UserGroup' );
	}

	/**
	 * Select a group from the DB by its name
	 * @param string A group name
	 * @return mixed A UserGroup object, or boolean FALSE
	**/
	public static function get_by_name( $name )
	{
		return DB::get_row( 'SELECT * FROM {groups} WHERE name=?', array( $name ), 'UserGroup' );
	}

	/**
	 * Determine whether a group exists
	 * @param mixed The name or ID of the group
	 * @return bool Whether the group exists or not
	**/
	public static function exists( $group )
	{
		return self::id($group) !== null;
	}

	/**
	 * Given a group's ID, return its friendly name
	 * @param int a group's ID
	 * @return string the group's name
	**/
	public static function name( $id )
	{
		$check_field = is_int( $id ) ? 'id' : 'name';
		$name = DB::get_value( "SELECT name FROM {groups} WHERE {$check_field}=?", array( $id ) );
		return $name;  // get_value returns false if no record is returned
	}

	/**
	 * Given a group's name, return its ID
	 * @param string a group's name
	 * @return int the group's ID
	**/
	public static function id( $name )
	{
		$check_field = is_int( $name ) ? 'id' : 'name';
		$id = DB::get_value( "SELECT id FROM {groups} WHERE {$check_field}=?", array( $name ) );
		return $id; // get_value returns false if no record is returned
	}
}
?>
