<?php

/**
 * Access Control List class
 *
 * The default Habari ACL class implements groups, and group permissions
 * Users are assigned to one or more groups.
 * Groups are assigned one or more permissions.
 * Membership in any group that grants a permission
 * means you have that permission.  Membership in any group that denies
 * that permission denies the user that permission, even if another group
 * grants that permission.
 *
 * @package Habari
 **/

class ACL {
	/**
	 * How to handle a permission request for a permission that is not in the permission list.
	 * For example, if you request $user->can('some non-existant permission') then this value is returned.
	 * It's true at the moment because that allows access to all features for upgrading users.
	 * @todo Decide if this is a setting we need or want to change, or perhaps it should be an option.
	 **/
	const ACCESS_NONEXISTANT_PERMISSION = true;

	private static $access_ids = array();
	private static $access_names = array();

	/**
	 * Static initializer to fill the $access_ids array
	 */
	public static function __static()
	{
		self::$access_ids = DB::get_keyvalue( 'SELECT description, id FROM {permissions};' );

		if ( ! isset(self::$access_ids) ) {
			self::$access_ids = array();
		}
		self::$access_names = array_flip( self::$access_ids );
	}

	/**
	 * Convert a permission access name (read, write, full, denied) into an ID
	 * @param string The access name
	 * @return mixed the ID of the permission, or boolean FALSE if it does not exist
	 **/
	public static function access_id( $name )
	{
		// if $name is numeric, assume it is already an access ID
		if ( is_numeric( $name ) ) {
			return $name;
		}
		return isset( self::$access_ids[$name] ) ? self::$access_ids[$name] : FALSE;
	}

	/**
	 * Convert a permission access ID into a name
	 * @param ID The access ID
	 * @return mixed the name of the permission, or boolean FALSE if it does not exist
	 **/
	public static function access_name( $id )
	{
		// if $id is not numeric, assume it is already an access name
		if ( ! is_numeric( $id ) ) {
			return $id;
		}
		return isset( self::$access_names[$id] ) ? self::$access_names[$id] : FALSE;
	}

	/**
	 * Check access. Implements hierarchy of access terms.
	 * @param mixed $access_given The ID or name of the access given
	 * @param mixed $access_check The ID or name of the access to check against
	 * @return bool Returns true if the given access meets exceeds the access to check against
	 */
	public static function access_check( $access_given, $access_check )
	{
		$access_given = self::access_name( $access_given );
		$access_check = self::access_name( $access_check );

		if ( $access_given == 'deny' ) {
			return false;
		}
		if ( $access_given == 'full' ) {
			return true;
		}
		if ( $access_given == 'read' || $access_given == 'write' ) {
			if ( $access_check == 'full' ) {
				return false;
			}
			if ( $access_given == $access_check ) {
				return true;
			}
			else {
				return false;
			}
		}
		// unknown access
		return false;
	}

	/**
	 * Create a new permission, and save it to the permission tokens table
	 * @param string The name of the permission
	 * @param string The description of the permission
	 * @return mixed the ID of the newly created permission, or boolean FALSE
	**/
	public static function create_permission( $name, $description )
	{
		$name = self::normalize_permission( $name );
		// first, make sure this isn't a duplicate
		if ( ACL::token_exists( $name ) ) {
			return false;
		}
		$allow = true;
		// Plugins have the opportunity to prevent adding this permission
		$allow = Plugins::filter('permission_create_allow', $allow, $name, $description );
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('permission_create_before', $name, $description);
		$result = DB::query('INSERT INTO {tokens} (name, description) VALUES (?, ?)', array( $name, $description) );

		if ( ! $result ) {
			// if it didn't work, don't bother trying to log it
			return false;
		}
		EventLog::log('New permission created: ' . $name, 'info', 'default', 'habari');
		Plugins::act('permission_create_after', $name, $description );
		return $result;
	}

	/**
	 * Remove a permission, and any assignments of it
	 * @param mixed a permission ID or name
	 * @return bool whether the permission was deleted or not
	**/
	public static function destroy_permission( $permission )
	{
		// make sure the permission exists, first
		if ( ! self::token_exists( $permission ) ) {
			return false;
		}

		// grab token ID
		$permission = self::token_id( $permission );

		$allow = true;
		// plugins have the opportunity to prevent deletion
		$allow = Plugins::filter('permission_destroy_allow', $allow, $permission);
		if ( ! $allow ) {
			return false;
		}
		Plugins::act('permission_destroy_before', $permission );
		// capture the permission token name
		$name = DB::get_value( 'SELECT name FROM {tokens} WHERE id=?', array( $permission ) );
		// remove all references to this permissions
		$result = DB::query( 'DELETE FROM {group_token_permissions} WHERE permission_id=?', array( $permission ) );
		$result = DB::query( 'DELETE FROM {user_token_permissions} WHERE permission_id=?', array( $permission ) );
		// remove this permission
		$result = DB::query( 'DELETE FROM {tokens} WHERE id=?', array( $permission ) );
		if ( ! $result ) {
			// if it didn't work, don't bother trying to log it
			return false;
		}
		EventLog::log( sprintf(_t('Permission deleted: %s'), $name), 'info', 'default', 'habari');
		Plugins::act('permission_destroy_after', $permission );
		return $result;
	}

	/**
	 * Get an array of QueryRecord objects containing all permissions
	 * @param string the order in which to sort the returning array
	 * @return array an array of QueryRecord objects containing all permissions
	**/
	public static function all_permissions( $order = 'id' )
	{
		$order = strtolower( $order );
		if ( ( 'id' != $order ) && ( 'name' != $order ) && ( 'description' != $order ) ) {
			$order = 'id';
		}
		$permissions = DB::get_results( 'SELECT id, name, description FROM {tokens} ORDER BY ' . $order );
		return $permissions ? $permissions : array();
	}

	/**
	 * Get a permission token's name by its ID
	 * @param int a permission ID
	 * @return string the name of the permission, or boolean FALSE
	**/
	public static function token_name( $id )
	{
		if ( ! is_int( $id ) ) {
			return false;
		} else {
			return DB::get_value( 'SELECT name FROM {tokens} WHERE id=?', array( $id ) );
		}
	}

	/**
	 * Get a permission token's ID by its name
	 * @param string the name of the permission
	 * @return int the permission's ID
	**/
	public static function token_id( $name )
	{
		if( is_numeric($name) ) {
			return $name;
		}
		$name = self::normalize_permission( $name );
		return DB::get_value( 'SELECT id FROM {tokens} WHERE name=?', array( $name ) );
	}

	/**
	 * Fetch a permission token's description from the DB
	 * @param mixed a permission name or ID
	 * @return string the description of the permission
	**/
	public static function token_description( $permission )
	{
		if ( is_int( $permission) ) {
			$query = 'id';
		} else {
			$query = 'name';
			$permission = self::normalize_permission( $permission );
		}
		return DB::get_value( "SELECT description FROM {tokens} WHERE $query=?", array( $permission ) );
	}

	/**
	 * Determine whether a permission token exists
	 * @param mixed a permission name or ID
	 * @return bool whether the permission exists or not
	**/
	public static function token_exists( $permission )
	{
		if ( is_numeric( $permission ) ) {
			$query = 'id';
		}
		else {
			$query = 'name';
			$permission = self::normalize_permission( $permission );
		}
		return ( (int) DB::get_value( "SELECT COUNT(id) FROM {tokens} WHERE $query=?", array( $permission ) ) > 0 );
	}

	/**
	 * Determine whether a group can perform a specific action
	 * @param mixed $group A group ID or name
	 * @param mixed $permission An action ID or name
	 * @param string $access Check for 'read', 'write', or 'full' access
	 * @return bool Whether the group can perform the action
	**/
	public static function group_can( $group, $permission, $access = 'full' )
	{
		// Use only numeric ids internally
		$group = UserGroup::id( $group );
		$permission = self::token_id( $permission );
		$sql = 'SELECT permission_id FROM {group_token_permissions} WHERE
			group_id=? AND token_id=?;';

		$result = DB::get_value( $sql, array( $group, $permission) );
		if ( isset( $result ) && self::access_check( $result, $access ) ) {
			// the permission has been granted to this group
			return true;
		}
		// either the permission hasn't been granted, or it's been
		// explicitly denied.
		return false;
	}

	/**
	 * Determine whether a user can perform a specific action
	 * @param mixed $user A user object, user ID or a username
	 * @param mixed $permission A permission ID or name
	 * @param string $access Check for 'read', 'write', or 'full' access
	 * @return bool Whether the user can perform the action
	**/
	public static function user_can( $user, $permission, $access = 'full' )
	{
		// Use only numeric ids internally
		$permission = self::token_id( $permission );

		/**
		 * Do we allow perms that don't exist?
		 * When ACL is functional ACCESS_NONEXISTANT_PERMISSION should be false by default.
		 */
		if ( is_null( $permission ) ) {
			return self::ACCESS_NONEXISTANT_PERMISSION;
		}

		// if we were given a user ID, use that to fetch the group membership from the DB
		if ( is_numeric( $user ) ) {
			$user_id = $user;
		} else {
			// otherwise, make sure we have a User object, and get
			// the groups from that
			if ( ! $user instanceof User ) {
				$user = User::get( $user );
			}
			$user_id = $user->id;
		}

		/**
		 * Jay Pipe's explanation of the following SQL
		 * 1) Look into user_permissions for the user and the token.
		 * If exists, use that permission flag for the check. If not,
		 * go to 2)
		 *
		 * 2) Look into the group_permissions joined to
		 * users_groups for the user and the token.  Order the results
		 * by the permission_id flag. The lower the flag value, the
		 * fewest permissions that group has. Use the first record's
		 * permission flag to check the ACL.
		 *
		 * This gives the system very fine grained control and grabbing
		 * the permission flag and can be accomplished in a single SQL
		 * call.
		 */
		$sql = <<<SQL
SELECT permission_id
  FROM {user_token_permissions}
  WHERE user_id = :user_id
  AND token_id = :token_id
UNION ALL
SELECT gp.permission_id
  FROM {users_groups} ug
  INNER JOIN {group_token_permissions} gp
  ON ug.group_id = gp.group_id
  AND ug.user_id = :user_id
  AND gp.token_id = :token_id;
SQL;
		$result = DB::get_value( $sql, array( ':user_id' => $user_id, ':token_id' => $permission ) );

		if ( isset( $result ) && self::access_check( $result, $access ) ) {
			return true;
		}

		// either the permission hasn't been granted, or it's been
		// explicitly denied.
		return false;
	}

	/**
	 * Grant a permission to a group
	 * @param integer $group_id The group ID
	 * @param mixed $token_id The name or ID of the permission token to grant
	 * @param string $access The kind of access to assign the group
	 * @return Result of the DB query
	 **/
	public static function grant_group( $group_id, $token_id, $access = 'full' )
	{
		// DB::update will insert if the token is not already in the group tokens table
		$result = DB::update(
			'{group_token_permissions}',
			array( 'permission_id' => self::$access_ids[$access] ),
			array( 'group_id' => $group_id, 'token_id' => self::token_id( $token_id ) )
		);

		$ug = UserGroup::get_by_id( $group_id );
		$ug->clear_permissions_cache();

		return $result;
	}

	/**
	 * Grant a permission to a user
	 * @param integer $user_id The user ID
	 * @param integer $token_id The name or ID of the permission token to grant
	 * @param string $access The kind of access to assign the group
	 * @return Result of the DB query
	 **/
	public static function grant_user( $user_id, $token_id, $access = 'full' )
	{
		$result = DB::update(
			'{user_token_permissions}',
			array( 'permission_id' => self::$access_ids[$access] ),
			array( 'user_id' => $user_id, 'token_id' => self::token_id( $token_id ) )
		);

		return $result;
	}

	/**
	 * Deny permission to a group
	 * @param integer $group_id The group ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return Result of the DB query
	 **/
	public static function deny_group( $group_id, $token_id )
	{
		self::grant_group( $group_id, $token_id, 'deny' );
	}

	/**
	 * Deny permission to a user
	 * @param integer $user_id The user ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return Result of the DB query
	 **/
	public static function deny_user( $user_id, $token_id )
	{
		self::grant_user( $group_id, $token_id, 'deny' );
	}

	/**
	 * Remove a permission from the group permissions table
	 * @param integer $group_id The group ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return the result of the DB query
	 **/
	public static function revoke_group_permission( $group_id, $token_id )
	{
		$result = DB::delete( '{group_token_permissions}',
			array( 'group_id' => $group_id, 'token_id' => $token_id ) );

		$ug = UserGroup::get_by_id( $group_id );
		$ug->clear_permissions_cache();

		return $result;
	}

	/**
	 * Remove a permission from the user permissions table
	 * @param integer $user_id The user ID
	 * @param mixed $token_id The name or ID of the permission token
	 * @return the result of the DB query
	 **/
	public static function revoke_user_permission( $user_id, $token_id )
	{
		$result = DB::delete( '{user_token_permissions}',
			array( 'user_id' => $user_id, 'token_id' => $token_id ) );

		return $result;
	}

	/**
	 * Convert a permission name into a valid format
	 *
	 * @param string $name The name of a permission
	 * @return string The permission with spaces converted to underscores and all lowercase
	 */
	public static function normalize_permission( $name )
	{
		return strtolower( preg_replace( '/\s+/', '_', trim($name) ) );
	}
}
?>