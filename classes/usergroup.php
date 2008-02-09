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
	private $db_permissions_denied= null;

	// these next three hold changes before they're committed to the DB
	private $member_ids= null;
	private $permissions_granted= null;
	private $permissions_denied= null;
	private $permissions_revoked= null;

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
		
		// if we have an ID, load this UserGroup's members & permissions
		if ( $this->id ) {
			if ( $result= DB::get_column( 'SELECT user_id FROM {users_groups} WHERE group_id= ?', array( $this->id ) ) ) {
				$this->db_member_ids= array_combine($result, $result);
			}

			if ( $results= DB::get_results( 'SELECT permission_id, denied FROM {groups_permissions} WHERE group_id=?', array( $this->id ) ) ) {
				foreach ( $results as $result ) {
					if ( 1 === (int) $result->denied ) {
						 $this->db_permissions_denied[ $result->permission_id ]= $result->permission_id;
					} else { 
						 $this->db_permissions_granted[ $result->permission_id ]= $result->permission_id;
					}
				}
			}
		}

		// set the temporary variables to hold the initial values
		// as pulled from the DB, if any
		$this->member_ids= $this->db_member_ids;
		$this->permissions_granted= $this->db_permissions_granted;
		$this->permissions_denied= $this->db_permissions_denied;
		
		// exclude a whole bunch of fields
		$this->exclude_fields( array( 'id', 'db_member_ids', 'db_permissions_granted', 'db_permissions_denied', 'member_ids', 'permissions_granted', 'permissions_denied', 'permissions_revoked' ) );
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

		// we process revoked permissions first, so that permissions
		// that are changed are first removed, and then applied
		if ( count( $this->permissions_revoked ) > 0 ) {
			foreach ( $this->permissions_revoked as $revoked ) {
				DB::query( 'DELETE FROM {groups_permissions} WHERE group_id=? AND permission_id=?', array( $this->id, $revoked ) );
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
					DB::query('INSERT INTO {groups_permissions} (group_id, permission_id, denied) VALUES (?, ?, 1)', array( $this->id, $perm ) );
				}
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
	 * @param mixed a user ID or name, or an array of the same
	**/
	public function add( $who )
	{
		if ( ! is_array( $who ) ) {
			$who= array( $who );
		}
		foreach ( $who as $id ) {
			if ( ! is_int( $id ) ) {
				$user= User::get( $id );
				$id= $user->id;
			}
			if ( ! isset( $this->member_ids[ $id ] ) ) {
				$this->member_ids[$id]= $id;
			}
		}
	}

	/**
	 * Remove one or more user from this group
	 * @param mixed a user ID or name, or an array of the same
	**/
	public function remove( $who )
	{
		if ( ! is_array( $who ) ) {
			$who= array( $who );
		}
		foreach ( $who as $id ) {
			if ( ! is_int( $id ) ) {
				$user= User::get( $id );
				$id= $user->id;
			}
			if ( isset( $this->member_ids[ $id ] ) ) {
				unset($this->member_ids[ $id ]);
			}
		}
	}

	/**
	 * Assign one or more new permissions to this group
	 * @param mixed A permission ID, name, or array of the same
	**/
	public function grant( $permissions )
	{
		if ( ! is_array( $permissions ) ) {
			$permissions= array( $permissions );
		}
		foreach ( $permissions as $permission ) {
			if ( ! is_numeric( $permission ) ) {
				$permission= ACL::permission_id( $permission );
			}
			// is this permission currently assigned to this group?
			if ( isset( $this->permissions_granted[ $permission ] ) ) {
				// skip this permission, and process the rest
				continue;
			}
		
			// is this permission currently denied to ths group?
			if ( isset( $permission, $this->permissions_denied[ $permission ] ) ) {
				// revoke it
				$this->permissions_revoked[ $permission ]= $permission;

				// we also need to update the temporary variable
				// so that the can() method works as expected
				// even before calls to update() occur
				unset( $this->permissions_denied[ $permission ] );
			}

			// finally, we grant this permission
			$this->permissions_granted[ $permission ]= $permission;
		}
	}

	/**
	 * Deny one or more permissions to this group
	 * @param mixed The permission ID or name to be denied, or an array of the same
	**/
	public function deny( $permissions )
	{
		if ( ! is_array( $permissions ) ) {
			$permissions= array( $permissions );
		}
		foreach( $permissions as $permission ) {
			if ( ! is_int( $permission ) ) {
				$permission= ACL::permission_id( $permission );
			}
			// short-circuit: is this permission already denied?
			if ( isset( $this->permissions_denied[ $permission ] ) ) {
				continue;
			}
	
			// is this permission currently granted?
			if ( isset( $this->permissions_granted[ $permission] ) ) {
				// revoke it
				$this->permissions_revoked[ $permission ]= $permission;

				// we also need to update the temporary variable
				// so that the can() method works as expected
				// even before calls to update() occur
				unset( $this->permissions_granted[ $permission ] );
			}

			// finally, we deny the permission
			$this->permissions_denied[ $permission ] = $permission;
		}
	}

	/**
	 * Remove one or more permissions from a group
	 * @param mixed a permission ID, name, or array of the same
	**/
	public function revoke( $permissions )
	{
		if ( ! is_array( $permissions ) ) {
			$permissions= array( $permissions );
		}
		foreach( $permissions as $permission ) {
			if ( ! is_int( $permission ) ) {
				$permission= ACL::permission_id( $permission );
			}
			if ( isset( $this->permissions_granted[ $permission ] ) ) {
				unset( $this->permissions_granted[ $permission ] );
			}
			if ( isset( $this->permissions_denied[ $permission ] ) ) {
				unset( $this->permissions_denied[ $permission] );
			}
			$this->permissions_revoked[ $permission ]= $permission;
		}
	}

	/**
	 * Determine whether members of a group can do something
	 * @param mixed a permission ID or name
	 * @return mixed If a permission is denied to this group, return boolean FALSE; if a permission is granted, return boolean TRUE; if a permission is neither granted nor denied, return an empty string.
	**/
	public function can( $permission )
	{
		if ( ! is_int( $permission ) ) {
			$permission= ACL::permission_id( $permission );
		}
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

	/**
	 * Given a group's ID, return its friendly name
	 * @param int a group's ID
	 * @return string the group's name
	**/
	public static function name( $id )
	{
		if ( ! is_int( $id ) ) {
			return false;
		}
		$name= DB::get_value( 'SELECT name FROM {groups} WHERE id=?', array( $id ) );
		if ( $name ) {
			return $name;
		}
		return $false;
	}

	/**
	 * Given a group's name, return its ID
	 * @param string a group's name
	 * @return int the group's ID
	**/
	public static function id( $name )
	{
		$id= DB::get_value( 'SELECT id FROM {groups} WHERE name=?', array( $id ) );
		if ( $id ) {
			return $id;
		}
		return false;
	}
}
?>
