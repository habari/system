<?php
/**
* Habari UserGroup Class
* @package Habari
**/
class UserGroup extends QueryRecord
{
	/**
	 * Static storage for this group's info
	 * these first three hold the original values as fetched from the DB
	 * These are associative arrays where the key and value are the same.
	 * This allows us to use isset() for various checks, rather than
	 * in_array(), and it allows us to avoid some array iterations
	**/
	private $db_member_ids= null;
	private $db_permissions_granted= null;
	private $db_permissons_denied= null;

	// these next three hold changes before they're committed to the DB
	private $member_ids= null;
	private $permissions_granted= null;
	private $permissions_denied= null;
	private $permissions_revoked= null;
	private $toggle_permissions= null;

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
			$this->fields );
		parent::__construct( $paramarray );

		// set up default, empty properties
		$this->permissions_revoked= array();
		$this->db_member_ids= array();
		$this->db_permissions_granted= array();
		$this->db_permissions_denied= array();
		$this->toggle_permissions= array();
		// $this->toggle_permission['grant']= array();
		// $this->toggle_permissions['deny']= array();
		
		// if we have an ID, load this UserGroup's members & permissions
		if ( $this->id ) {
			if ( $result= DB::get_column( 'SELECT user_id FROM {users_groups} WHERE group_id= ?', array( $this->id ) ) ) {
				$this->db_member_ids= array_combine($result, $result);
			}

			if ( $result= DB::get_column( 'SELECT permission_id FROM {groups_permissions} WHERE group_id=? AND denied=0 ', array( $this->id ) ) ) {
				$this->db_permissions_granted = array_combine($result, $result);
			}

			if ( $result= DB::get_column( 'SELECT permission_id FROM {groups_permissions} WHERE group_id=? AND denied=1', array( $this->id ) ) ) {
				$this->db_permissions_denied = array_combine($result, $result);
			}
		}

		// set the temporary variables to hold the initial values
		// as pulled from the DB, if any
		$this->member_ids= $this->db_member_ids;
		$this->permissions_granted= $this->db_permissions_granted;
		$this->permissions_denied= $this->db_permissions_denied;
		
		// exclude a whole bunch of fields
		$this->exclude_fields( array( 'id', 'db_member_ids', 'db_permissions_granted', 'db_permissions_denied', 'member_ids', 'permissions_granted', 'permissons_denied', 'permissions_revoked', 'toggle_permissions' ) );
	}

	/**
	 * Create a new UserGroup object and save it to the database
	 * @param array An associative array of UserGroup fields
	 * @return UserGroup the UserGroup that was created
	**/
	public static function create( $paramarray )
	{
		$usergroup= new UserGroup( $paramarray );
		if ( $usergroup->insert() ) {
			return $usergroup;
		} else {
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
		EventLog::log('New group created: ' . $this->name, 'info', 'default', 'habari');
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

		// figure out what needs to be changed
		// we do this by comparing the various temporary arrays against
		// the arrays that hold the DB values
		if ( $this->member_ids != $this->db_member_ids ) {
			$added= array_diff_assoc( $this->member_ids, $this->db_member_ids);
			if ( count( $added ) > 0 ) {
				// one or more members added to this group
				foreach ( $added as $id ) { 
					DB::query('INSERT INTO {users_groups} (user_id, group_id) VALUES (?, ?)', array( $id, $this->id) );
				}
			}
			$removed= array_diff_assoc( $this->db_member_ids, $this->member_ids );
			if ( count( $removed ) > 0 ) {
				// one or more members removed from this group
				foreach ( $removed as $id ) {
					DB::query('DELETE FROM {users_groups} WHERE user_id=? AND group_id=?', array( $id, $this->id ) );
				}
			}
		}

		// were any permissions toggled?  We do this to economize hits
		// to the DB: rather than execute separate DELETE then INSERT
		// commands, we can execute a single UPDATE
		if ( ! empty( $this->toggle_permissions['grant'] ) ) {
			// grant permissions previously denied
			$ids= implode( ',', $this->toggle_permissions['grant'] );
			$results= DB::query( "UPDATE {groups_permissions} set denied=0 WHERE id IN ($ids)" );
			//update DB arrays, to prevent the next set of checks 
			// from trying to insert this permission
			foreach ( $this->toggle_permissions['grant'] as $id ) {
				$this->db_permissions_granted[ $id ]= $id;
				if ( isset( $this->db_permissions_denied[$id] ) ) {
					// this should make the db array match
					// its corresponding temp array
					unset( $this->db_permissions_denied[$id] );
				}
			}
		}
		if ( ! empty( $this->toggle_permissions['deny'] ) ) {
			$ids= implode( ',', $this->toggle_permissions['deny'] );
			$results= DB::query( "UPDATE {groups_permissions} set denied=1 WHERE id IN ($ids)" );
			//update DB arrays again
			foreach ( $this->toggle_permissions['deny'] as $id ) {
				$this->db_permissions_denied[ $id ]= $id;
				if  ( isset( $this->db_permissions_granted[$id] ) ) {
					// this should make the db array match
					// its corresponding temp array
					unset( $this->db_permissions_granted[$id] );
				}
			}
		}

		// now, we compare the current list of granted permissions with
		// those currently in the DB.  If any new ones have been added,
		// update the DB to reflect this
		if ( $this->permissions_granted != $this->db_permissions_granted ) {
			$granted= array_diff_assoc( $this->permissions_granted, $this->db_permissions_granted );
			if ( count( $granted ) > 0 ) {
			// one or more permissions granted
				foreach( $granted as $perm ) {
					DB::query('INSERT INTO {groups_permissions} (group_id, permission_id, denied ) VALUES (?, ?, 0)', array( $this->id, $perm) );
				}
			}
		}

		// and do the same for the denied permissions
		if ( $this->permissions_denied != $this->db_permissions_denied ) {
			$denied= array_diff_assoc( $this->permissions_denied, $this->db_permissions_denied );
			if ( count( $denied ) > 0 ) {
				// one or more permissions denied
				foreach( $denied as $perm ) {
					DB::query('INSERT INTO {groups_permissions} (group_id, permission_id, denied) VAlUED (?, ? 1)', array( $this->id, $perm ) );
				}
			}
		}

		// finally, were any previously assigned permissions
		// (granted or denied) removed?  If so, take them out of the DB
		if ( count( $this->permissions_revoked ) > 0 ) {
			// one or more permissions revoked
			foreach ( $this->permissions_revoked as $perm ) {
				DB::query( 'DELETE FROM {users_groups} WHERE group_id=? AND permission_id=?', array( $this->id, $perm ) );
			}
		}

		Plugins::act('usergroup_update_after', $this);

		// *whew* We're all done!
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
	 * function members
	 * returns an array of user IDs belogning to this UserGroup
	 * @return array an array of user IDs
	**/
	public function members()
	{
		return $this->member_ids;
	}
	
	/**
	 * Add a user to this group
	 * @param mixed a user ID or name
	**/
	public function add( $id )
	{
		if ( ! is_int( $id ) ) {
			$user= User::get( $id );
			$id= $user->id;
		}
		if ( isset( $this->member_ids[ $id ] ) ) {
			// this user is already a member
			return false; 
		}
		$this->member_ids[$id]= $id;
		return true;
	}

	/**
	 * Remove a user from this group
	 * @param mixed a user ID or name
	**/
	public function remove( $id )
	{
		if ( ! is_int( $id ) ) {
			$user= User::get( $id );
			$id= $user->id;
		}
		if ( ! isset( $this->member_ids[ $id ] ) ) {
			// this user is not a member of this group
			return false;
		}
		unset($this->member_ids[ $id ]);
		return true;
	}

	/**
	 * Assign a new permission to this group
	 * @param int A permission ID
	**/
	public function grant( $permission )
	{
		// is this permisson currently assigned to this group?
		if ( isset( $this->permissions_granted[ $permission ] ) ) {
			// we can short-circuit and stop processing
			return true;
		}
		
		// is this permission currently denied to ths group?
		if ( isset( $permission, $this->permissions_denied[ $permission ] ) ) {
			// we need to toggle the denied bit, which is a single
			// UPDATE operation, rather than a DELETE + INSERT
			$this->toggle_permissions['grant']= $permission;

			// we also need to update the temporary variable
			// so that the can() method works as expected
			// even before calls to update() occur
			unset( $this->permissions_denied[ $permission ] );
		}

		// finally, we grant this permission
		$this->permissions_granted[ $permission ]= $permission;
	}

	/**
	 * Deny a permission to this group
	 * @param int The permission ID to be denied
	**/
	public function deny( $permission )
	{
		// short-circuit: is this permission already denied?
		if ( isset( $this->permissions_denied[ $permission ] ) ) {
			return true;
		}

		// is this permission currently granted?
		if ( isset( $this->permissions_granted[ $permission] ) ) {
			// we need to toggle the denied bit, which is a single
			// UPDATE operation, rather than a DELETE + INSERT
			$this->toggle_permissions['deny']= $permission;

			// we also need to update the temporary variable
			// so that the can() method works as expected
			// even before calls to update() occur
			unset( $this->permissions_granted[ $permission ] );
		}

		// finally, we deny the permission
		$this->permissions_denied[ $permission ] = $permission;
	}

	/**
	 * Remove a permission from a group
	 * @param int a permission ID
	**/
	public function revoke( $permission )
	{
		if ( isset( $this->permissons_granted[ $permission ] ) ) {
			unset( $this->permissions_granted[ $permission ] );
		}
		if ( isset( $permission, $this->permissions_denied[ $permission ] ) ) {
			unset( $this->permissions_denied[ $permission] );
		}
		$this->permissions_revoked[ $permission ]= $permission;
	}

	/**
	 * Determine whether members of a group can do something
	 * @param string a text description of a permission
	 * @return mixed If a permission is denied to this group, return boolean FALSE; if a permission is granted, return boolean TRUE; if a permission is neither granted nor denied, return an empty string.
	**/
	public function can( $permission )
	{
		if ( isset( $this->permissions_denied[ $permission ] ) ) {
			return false;
		}
		if ( isset( $this->permissions_granted[ $permission ] ) ) {
			return true;
		}
		return '';
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
		} else {
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
		if ( 0 == $id ) {
			return false;
		}
		return DB::get_row( 'SELECT * FROM {groups} WHERE id=?', array( $id ), 'UserGroup' );
	}

	/**
	 * Select a group from the DB by its name
	 * @param string A group name
	 * @return mixed A UserGroup object, or boolean FALSE
	**/
	public static function get_by_name( $name )
	{
		if ( '' == $name ) {
			return false;
		}
		return DB::get_row( 'SELECT * FROM {groups} WHERE name=?', array( $name ), 'UserGroup' );
	}

	/**
	 * Determine whether a group exists
	 * @param mixed The name or ID of the group
	 * @return bool Whether the group exists or not
	**/
	public static function exists( $group )
	{
		if ( is_int( $group ) ) {
			$query= 'id';
		} else {
			$query= 'name';
		}
		return DB::get_value("SELECT COUNT(id) FROM {groups} WHERE $query=?", array( $group ) );
	}

}
?>
